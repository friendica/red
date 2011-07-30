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


	$basedom = parse_xml_string($xml);

	if($basedom)
		logger('parsed dom');

	$atom = $basedom->children(NAMESPACE_ATOM1);

	logger('atom: ' . count($atom));
	$encrypted_header = json_decode(base64_decode($atom->encrypted_header));

	print_r($encrypted_header);
	
	$encrypted_aes_key_bundle = base64_decode($encrypted_header->aes_key);
	$ciphertext = base64_decode($encrypted_header->ciphertext);

	logger('encrypted_aes: ' . print_r($encrypted_aes_key_bundle,true));
	logger('ciphertext: ' . print_r($ciphertext,true));

	$outer_key_bundle = '';
	openssl_private_decrypt($encrypted_aes_key_bundle,$outer_key_bundle,$localprvkey);

	logger('outer_bundle: ' . print_r($outer_key_bundle,true));

	$j_outer_key_bundle = json_decode($outer_key_bundle);

	$outer_iv = base64_decode($j_outer_key_bundle->iv);
	$outer_key = base64_decode($j_outer_key_bundle->key);

	$decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $outer_key, $ciphertext, MCRYPT_MODE_CBC, $outer_iv);

	$decrypted = pkcs5_unpad($decrypted);

	logger('decrypted: ' . print_r($decrypted,true));

	/**
	 * $decrypted now contains something like
	 *
	 *  <decrypted_header>
	 *     <iv>8e+G2+ET8l5BPuW0sVTnQw==</iv>
	 *     <aes_key>UvSMb4puPeB14STkcDWq+4QE302Edu15oaprAQSkLKU=</aes_key>
	 *     <author>
	 *       <name>Ryan Hughes</name>
	 *       <uri>acct:galaxor@diaspora.pirateship.org</uri>
	 *     </author>
	 *  </decrypted_header>
	 */

	$idom = parse_xml_string($decrypted,false);

	print_r($idom);
	$inner_iv = base64_decode($idom->iv);
	$inner_aes_key = base64_decode($idom->aes_key);

	logger('inner_iv: ' . $inner_iv);

	$dom = $basedom->children(NAMESPACE_SALMON_ME);

	if($dom)
		logger('have dom');

	logger('dom: ' . count($dom));
	// figure out where in the DOM tree our data is hiding

	if($dom->provenance->data)
		$base = $dom->provenance;
	elseif($dom->env->data)
		$base = $dom->env;
	elseif($dom->data)
		$base = $dom;
	
	if(! $base) {
		logger('mod-diaspora: unable to locate salmon data in xml ');
		dt_return(400);
	}


	// Stash the signature away for now. We have to find their key or it won't be good for anything.
	$signature = base64url_decode($base->sig);

	logger('signature: ' . bin2hex($signature));

	openssl_public_encrypt('test',$rrr,$ryanpubkey);
	logger('rrr: ' . $rrr);

	$pubdecsig = '';
	openssl_public_decrypt($signature,$pubdecsig,$ryanpubkey);
	logger('decsig: ' . bin2hex($pubdecsig));

	// unpack the  data

	// strip whitespace so our data element will return to one big base64 blob
	$data = str_replace(array(" ","\t","\r","\n"),array("","","",""),$base->data);

	// stash away some other stuff for later

	$type = $base->data[0]->attributes()->type[0];
	$keyhash = $base->sig[0]->attributes()->keyhash[0];
	$encoding = $base->encoding;
	$alg = $base->alg;

	$signed_data = $data  . "\n" . '.' . base64url_encode($type) . "\n" . '.' . base64url_encode($encoding) . "\n" . '.' . base64url_encode($alg) . "\n";

	logger('signed data: ' . $signed_data);

	// decode the data
	$data = base64url_decode($data);

	// Now pull out the inner encrypted blob




	$inner_encrypted = base64_decode($data);

	$inner_decrypted = 
	$inner_decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $inner_aes_key, $inner_encrypted, MCRYPT_MODE_CBC, $inner_iv);

	$inner_decrypted = pkcs5_unpad($inner_decrypted);

	logger('inner_decrypted: ' . $inner_decrypted);



	if(! $author_link) {
		logger('mod-diaspora: Could not retrieve author URI.');
		receive_return(400);
	}

	// Once we have the author URI, go to the web and try to find their public key
	// *** or look it up locally ***

	logger('mod-diaspora: Fetching key for ' . $author_link );

// Get diaspora public key (pkcs#1) and convert to pkcs#8
// 	$key = get_diaspora_key($author_link);

//	$key = get_salmon_key($author_link,$keyhash);

	if(! $key) {
		logger('mod-salmon: Could not retrieve author key.');
		receive_return(400);
	}

// FIXME
// Use non salmon compliant signature

/*

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
*/

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




// figure out what kind of diaspora message we have, and process accordingly.





	receive_return(200);
}




