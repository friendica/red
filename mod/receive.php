<?php

/**
 * Diaspora endpoint
 */



require_once('include/salmon.php');
require_once('library/simplepie/simplepie.inc');

function receive_return($val) {

	if($val >= 400)
		$err = 'Error';
	if($val >= 200 && $val < 300)
		$err = 'OK';

	logger('mod-diaspora returns ' . $val);	
	header($_SERVER["SERVER_PROTOCOL"] . ' ' . $val . ' ' . $err);
	killme();

}

function receive_post(&$a) {

	if($a->argc != 3 || $a->argv[1] !== 'users')
		receive_return(500);

	$guid = $a->argv[2];

	$r = q("SELECT * FROM `user` WHERE `guid` = '%s' LIMIT 1",
		dbesc($guid)
	);
	if(! count($r))
		salmon_return(500);

	$importer = $r[0];

	$xml = $_POST['xml'];

	logger('mod-diaspora: new salmon ' . $xml, LOGGER_DATA);

	if(! $xml)
		receive_return(500);

	// parse the xml

	$dom = simplexml_load_string($xml,'SimpleXMLElement',0,NAMESPACE_SALMON_ME);

	// figure out where in the DOM tree our data is hiding

	if($dom->provenance->data)
		$base = $dom->provenance;
	elseif($dom->env->data)
		$base = $dom->env;
	elseif($dom->data)
		$base = $dom;
	
	if(! $base) {
		logger('mod-diaspora: unable to locate salmon data in xml ');
		receive_return(400);
	}

	// Stash the signature away for now. We have to find their key or it won't be good for anything.
	$signature = base64url_decode($base->sig);

	// unpack the  data

	// strip whitespace so our data element will return to one big base64 blob
	$data = str_replace(array(" ","\t","\r","\n"),array("","","",""),$base->data);

	// stash away some other stuff for later

	$type = $base->data[0]->attributes()->type[0];
	$keyhash = $base->sig[0]->attributes()->keyhash[0];
	$encoding = $base->encoding;
	$alg = $base->alg;

	$signed_data = $data  . '.' . base64url_encode($type) . '.' . base64url_encode($encoding) . '.' . base64url_encode($alg);

	// decode the data
	$data = base64url_decode($data);

	// Remove the xml declaration
	$data = preg_replace('/\<\?xml[^\?].*\?\>/','',$data);

	// Create a fake feed wrapper so simplepie doesn't choke

	$tpl = get_markup_template('fake_feed.tpl');
	
	$base = substr($data,strpos($data,'<entry'));

	$feedxml = $tpl . $base . '</feed>';

	logger('mod-diaspora: Processed feed: ' . $feedxml);

	// Now parse it like a normal atom feed to scrape out the author URI
	
    $feed = new SimplePie();
    $feed->set_raw_data($feedxml);
    $feed->enable_order_by_date(false);
    $feed->init();

	logger('mod-diaspora: Feed parsed.');

	if($feed->get_item_quantity()) {
		foreach($feed->get_items() as $item) {
			$author = $item->get_author();
			$author_link = unxmlify($author->get_link());
			break;
		}
	}

	if(! $author_link) {
		logger('mod-diaspora: Could not retrieve author URI.');
		receive_return(400);
	}

	// Once we have the author URI, go to the web and try to find their public key

	logger('mod-salmon: Fetching key for ' . $author_link );


	$key = get_salmon_key($author_link,$keyhash);

	if(! $key) {
		logger('mod-salmon: Could not retrieve author key.');
		receive_return(400);
	}

	// Setup RSA stuff to verify the signature

	set_include_path(get_include_path() . PATH_SEPARATOR . 'library' . PATH_SEPARATOR . 'phpsec');

	require_once('library/phpsec/Crypt/RSA.php');

	$key_info = explode('.',$key);

	$m = base64url_decode($key_info[1]);
	$e = base64url_decode($key_info[2]);

	logger('mod-salmon: key details: ' . print_r($key_info,true));

    $rsa = new CRYPT_RSA();
    $rsa->signatureMode = CRYPT_RSA_SIGNATURE_PKCS1;
    $rsa->setHash('sha256');

    $rsa->modulus = new Math_BigInteger($m, 256);
    $rsa->k = strlen($rsa->modulus->toBytes());
    $rsa->exponent = new Math_BigInteger($e, 256);

    $verify = $rsa->verify($signed_data,$signature);

	if(! $verify) {
		logger('mod-diaspora: Message did not verify. Discarding.');
		receive_return(400);
	}

	logger('mod-diaspora: Message verified.');

	/* decrypt the sucker */
	/*
		// TODO
	*/

	/*
	*
	* If we reached this point, the message is good. Now let's figure out if the author is allowed to send us stuff.
	*
	*/

	$r = q("SELECT * FROM `contact` WHERE `network` = 'dspr' AND ( `url` = '%s' OR `alias` = '%s') 
		AND `uid` = %d LIMIT 1",
		dbesc($author_link),
		dbesc($author_link),
		intval($importer['uid'])
	);
	if(! count($r)) {
		logger('mod-diaspora: Author unknown to us.');
	}	

	// is this a follower? Or have we ignored the person?
	// If so we can not accept this post.

	if((count($r)) && (($r[0]['readonly']) || ($r[0]['rel'] == REL_VIP) || ($r[0]['blocked']))) {
		logger('mod-diaspora: Ignoring this author.');
		receive_return(202);
		// NOTREACHED
	}

	require_once('include/items.php');

	// Placeholder for hub discovery. We shouldn't find any hubs
	// since we supplied the fake feed header - and it doesn't have any.

	$hub = '';

	/**
	 *
	 * anti-spam measure: consume_feed will accept a follow activity from 
	 * this person (and nothing else) if there is no existing contact record.
	 *
	 */

	$contact_rec = ((count($r)) ? $r[0] : null);

	consume_feed($feedxml,$importer,$contact_rec,$hub);

	receive_return(200);
}




