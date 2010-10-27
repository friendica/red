<?php


// TODO: 
// add relevant contacts so they can use this

// There is a lot of debug stuff in here because this is quite a
// complicated process to try and sort out. 

require_once('include/salmon.php');
require_once('simplepie/simplepie.inc');

function salmon_return($val) {

	if($val >= 500)
		$err = 'Error';
	if($val == 200)
		$err = 'OK';
	
	header($_SERVER["SERVER_PROTOCOL"] . ' ' . $val . ' ' . $err);
	killme();

}

function salmon_post(&$a) {

	$xml = file_get_contents('php://input');
	
	$debugging = get_config('system','debugging');
	if($debugging)
		file_put_contents('salmon.out','New Salmon: ' . $xml . "\n",FILE_APPEND);

	$nick       = (($a->argc > 1) ? notags(trim($a->argv[1])) : '');
	$mentions   = (($a->argc > 2 && $a->argv[2] === 'mention') ? true : false);

	$r = q("SELECT * FROM `user` WHERE `nickname` = '%s' LIMIT 1",
		dbesc($nick)
	);
	if(! count($r))
		salmon_return(500);

	$importer = $r[0];

	// parse the xml

	$dom = simplexml_load_string($xml,'SimpleXMLElement',0,NAMESPACE_SALMON_ME);


	if($debugging)
		file_put_contents('salmon.out', "\n" . print_r($dom,true) . "\n" , FILE_APPEND);

	// figure out where in the DOM tree our data is hiding

	if($dom->provenance->data)
		$base = $dom->provenance;
	elseif($dom->env->data)
		$base = $dom->env;
	elseif($dom->data)
		$base = $dom;
	
	if(! $base) {
		if($debugging)
			file_put_contents('salmon.out', "\n" . 'Unable to find salmon data in XML' . "\n" , FILE_APPEND);
		salmon_return(500);
	}

	// Stash the signature away for now. We have to find their key or it won't be good for anything.


	$signature = base64url_decode($base->sig);

	if($debugging)
		file_put_contents('salmon.out', "\n" . 'Encoded Signature: ' . $base->sig . "\n" , FILE_APPEND);

	// unpack the  data

	// strip whitespace so our data element will return to one big base64 blob
	$data = str_replace(array(" ","\t","\r","\n"),array("","","",""),$base->data);

	// stash away some other stuff for later

	$type = $base->data[0]->attributes()->type[0];
	$keyhash = $base->sig[0]->attributes()->keyhash[0];
	$encoding = $base->encoding;
	$alg = $base->alg;

	// If we're talking to status.net or one of their ilk, they aren't following the magic envelope spec
	// and only signed the data element. We'll be nice and let them validate anyway. 

	$stnet_signed_data = $data;
	$signed_data = $data  . '.' . base64url_encode($type) . '.' . base64url_encode($encoding) . '.' . base64url_encode($alg);

	// decode the data
	$data = base64url_decode($data);

	// Remove the xml declaration
	$data = preg_replace('/\<\?xml[^\?].*\?\>/','',$data);

	// Create a fake feed wrapper so simplepie doesn't choke

	$tpl = load_view_file('view/fake_feed.tpl');
	
	$base = substr($data,strpos($data,'<entry'));

	$feedxml = $tpl . $base . '</feed>';

	if($debugging) {
		file_put_contents('salmon.out', 'Processed feed: ' . $feedxml . "\n", FILE_APPEND);
	}

	// Now parse it like a normal atom feed to scrape out the author URI
	
    $feed = new SimplePie();
    $feed->set_raw_data($feedxml);
    $feed->enable_order_by_date(false);
    $feed->init();

	if($debugging) {
		file_put_contents('salmon.out', "\n" . 'Feed parsed.' . "\n", FILE_APPEND);
	}


	if($feed->get_item_quantity()) {
		foreach($feed->get_items() as $item) {
			$author = $item->get_author();
			$author_link = unxmlify($author->get_link());
			break;
		}
	}

	if(! $author_link) {
		if($debugging)
			file_put_contents('salmon.out',"\n" . 'Could not retrieve author URI.' . "\n", FILE_APPEND);
		salmon_return(500);
	}

	// Once we have the author URI, go to the web and try to find their public key

	if($debugging) {
		file_put_contents('salmon.out', "\n" . 'Fetching key for ' . $author_link . "\n", FILE_APPEND);
	}

	$key = get_salmon_key($author_link,$keyhash);

	if(! $key) {
		if($debugging)
			file_put_contents('salmon.out',"\n" . 'Could not retrieve author key.' . "\n", FILE_APPEND);
		salmon_return(500);
	}

	// Setup RSA stuff to verify the signature

	set_include_path(get_include_path() . PATH_SEPARATOR . 'phpsec');

	require_once('phpsec/Crypt/RSA.php');

	$key_info = explode('.',$key);

	$m = base64url_decode($key_info[1]);
	$e = base64url_decode($key_info[2]);

	if($debugging)
		file_put_contents('salmon.out',"\n" . print_r($key_info,true) . "\n", FILE_APPEND);

    $rsa = new CRYPT_RSA();
    $rsa->signatureMode = CRYPT_RSA_SIGNATURE_PKCS1;
    $rsa->setHash('sha256');

    $rsa->modulus = new Math_BigInteger($m, 256);
    $rsa->k = strlen($rsa->modulus->toBytes());
    $rsa->exponent = new Math_BigInteger($e, 256);

	// We should have everything we need now. Let's see if it verifies.
	// If it fails with the proper data format, try again using just the data
	// (e.g. status.net)

    $verify = $rsa->verify($signed_data,$signature);

	if(! $verify)
	    $verify = $rsa->verify($stnet_signed_data,$signature);

	if(! $verify) {
		if($debugging)
			file_put_contents('salmon.out',"\n" . 'Message did not verify. Discarding.' . "\n", FILE_APPEND);
		salmon_return(500);
	}

	if($debugging)
		file_put_contents('salmon.out',"\n" . 'Message verified.' . "\n", FILE_APPEND);


	/*
	*
	* If we reached this point, the message is good. Now let's figure out if the author is allowed to send us stuff.
	*
	*/

	$r = q("SELECT * FROM `contact` WHERE `network` = 'stat' AND ( `url` = '%s' OR `lrdd` = '%s') 
		AND `uid` = %d LIMIT 1",
		dbesc($author_link),
		dbesc($author_link),
		intval($importer['uid'])
	);
	if(! count($r)) {
		if($debugging)
			file_put_contents('salmon.out',"\n" . 'Author unknown to us.' . "\n", FILE_APPEND);

	}	
	if((count($r)) && ($r[0]['readonly'])) {
		if($debugging)
			file_put_contents('salmon.out',"\n" . 'Ignoring this author.' . "\n", FILE_APPEND);
		salmon_return(200);
		// NOTREACHED
	}


	require_once('include/items.php');

	// Placeholder for hub discovery. We shouldn't find any hubs
	// since we supplied the fake feed header - and it doesn't have any.

	$hub = '';

	// consume_feed will only accept a follow activity from this person if there is no contact record.

	consume_feed($feedxml,$importer,((count($r)) ? $r[0] : null),$hub);

	salmon_return(200);
}




