<?php

require_once('library/asn1.php');

function salmon_key($pubkey) {
	$lines = explode("\n",$pubkey);
	unset($lines[0]);
	unset($lines[count($lines)]);
	$x = base64_decode(implode('',$lines));

	$r = ASN_BASE::parseASNString($x);

	$m = $r[0]->asnData[1]->asnData[0]->asnData[0]->asnData;
	$e = $r[0]->asnData[1]->asnData[0]->asnData[1]->asnData;


	return 'RSA' . '.' . $m . '.' . $e ;
}


function base64url_encode($s, $strip_padding = false) {

	$s = strtr(base64_encode($s),'+/','-_');

	if($strip_padding)
		$s = str_replace('=','',$s);

	return $s;
}

function base64url_decode($s) {

/*
 *  // Placeholder for new rev of salmon which strips base64 padding.
 *  // PHP base64_decode handles the un-padded input without requiring this step
 *  // Uncomment if you find you need it.
 *
 *	$l = strlen($s);
 *	if(! strpos($s,'=')) {
 *		$m = $l % 4;
 *		if($m == 2)
 *			$s .= '==';
 *		if($m == 3)
 *			$s .= '=';
 *	}
 *
 */

	return base64_decode(strtr($s,'-_','+/'));
}

function get_salmon_key($uri,$keyhash) {
	$ret = array();

	logger('Fetching salmon key');

	$arr = lrdd($uri);

	if(is_array($arr)) {
		foreach($arr as $a) {
			if($a['@attributes']['rel'] === 'magic-public-key') {
				$ret[] = $a['@attributes']['href'];
			}
		}
	}
	else {
		return '';
	}

	// We have found at least one key URL
	// If it's inline, parse it - otherwise get the key

	if(count($ret)) {
		for($x = 0; $x < count($ret); $x ++) {
			if(substr($ret[$x],0,5) === 'data:') {
				if(strstr($ret[$x],','))
					$ret[$x] = substr($ret[$x],strpos($ret[$x],',')+1);
				else
					$ret[$x] = substr($ret[$x],5);
			}
			else
				$ret[$x] = fetch_url($ret[$x]);
		}
	}


	logger('Key located: ' . print_r($ret,true));

	if(count($ret) == 1) {

		// We only found one one key so we don't care if the hash matches.
		// If it's the wrong key we'll find out soon enough because 
		// message verification will fail. This also covers some older 
		// software which don't supply a keyhash. As long as they only
		// have one key we'll be right. 

		return $ret[0];
	}
	else {
		foreach($ret as $a) {
			$hash = base64url_encode(hash('sha256',$a));
			if($hash == $keyhash)
				return $a;
		}
	}

	return '';
}

	
		
function slapper($owner,$url,$slap) {

	logger('slapper called. Data: ' . $slap);

	// does contact have a salmon endpoint? 

	if(! strlen($url))
		return;

	// add all namespaces to item

$namespaces = <<< EOT
<entry xmlns="http://www.w3.org/2005/Atom"
      xmlns:thr="http://purl.org/syndication/thread/1.0"
      xmlns:at="http://purl.org/atompub/tombstones/1.0"
      xmlns:media="http://purl.org/syndication/atommedia"
      xmlns:dfrn="http://purl.org/macgirvin/dfrn/1.0" 
      xmlns:as="http://activitystrea.ms/spec/1.0/"
      xmlns:georss="http://www.georss.org/georss" 
      xmlns:poco="http://portablecontacts.net/spec/1.0" 
      xmlns:ostatus="http://ostatus.org/schema/1.0" 
	  xmlns:statusnet="http://status.net/schema/api/1/" >													>
EOT;

	$slap = str_replace('<entry>',$namespaces,$slap);
	
	// create a magic envelope

	$data      = base64url_encode($slap);
	$data_type = 'application/atom+xml';
	$encoding  = 'base64url';
	$algorithm = 'RSA-SHA256';
	$keyhash   = base64url_encode(hash('sha256',salmon_key($owner['spubkey'])));

	// Setup RSA stuff to PKCS#1 sign the data

	set_include_path(get_include_path() . PATH_SEPARATOR . 'library' . PATH_SEPARATOR . 'phpsec');

	require_once('library/phpsec/Crypt/RSA.php');

    $rsa = new CRYPT_RSA();
    $rsa->signatureMode = CRYPT_RSA_SIGNATURE_PKCS1;
    $rsa->setHash('sha256');
	$rsa->loadKey($owner['sprvkey']);

	// precomputed base64url encoding of data_type, encoding, algorithm concatenated with periods

	$precomputed = '.YXBwbGljYXRpb24vYXRvbSt4bWw=.YmFzZTY0dXJs.UlNBLVNIQTI1Ng==';

	$signature  = base64url_encode($rsa->sign($data . $precomputed));

	$signature2  = base64url_encode($rsa->sign($data));

	$salmon_tpl = get_markup_template('magicsig.tpl');
	$salmon = replace_macros($salmon_tpl,array(
		'$data'      => $data,
		'$encoding'  => $encoding,
		'$algorithm' => $algorithm,
		'$keyhash'   => $keyhash,
		'$signature' => $signature
	));

	// slap them 
	post_url($url,$salmon, array(
		'Content-type: application/magic-envelope+xml',
		'Content-length: ' . strlen($salmon)
	));

	$a = get_app();
	$return_code = $a->get_curl_code();

	// check for success, e.g. 2xx

	if($return_code > 299) {

		logger('slapper: compliant salmon failed. Falling back to status.net hack');

		// Entirely likely that their salmon implementation is
		// non-compliant. Let's try once more, this time only signing
		// the data, without the precomputed blob 

		$salmon = replace_macros($salmon_tpl,array(
			'$data'      => $data,
			'$encoding'  => $encoding,
			'$algorithm' => $algorithm,
			'$keyhash'   => $keyhash,
			'$signature' => $signature2
		));

		// slap them 
		post_url($url,$salmon, array(
			'Content-type: application/magic-envelope+xml',
			'Content-length: ' . strlen($salmon)
		));
		$return_code = $a->get_curl_code();

	}
	logger('slapper returned ' . $return_code); 
	if(! $return_code)
		return(-1);
	return ((($return_code >= 200) && ($return_code < 300)) ? 0 : 1);
}

