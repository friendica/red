<?php


require_once('include/salmon.php');
require_once('include/crypto.php');


function zot_get($url,$args) {
	$argstr = '';
	foreach($args as $k => $v) {
		if($argstr)
			$argstr .= '&';
		$argstr .= $k . '=' . $v;
	}	
	$s = fetch_url($url . '?' . $argstr);
	if($s) {
		$j = json_decode($s);
		if($j)
			return($j);
	}
	return false;
}

function zot_post($url,$args) {
	$s = post_url($url,$args);
	if($s) {
		$j = json_decode($s);
		if($j)
			return($j);
	}
	return false;
}


function zot_prv_encode($s,$prvkey) {
	$x = '';
	$res = openssl_private_encrypt($s,$x,$prvkey);
	return base64url_encode($y);
}
function zot_pub_encode($s,$pubkey) {
	$x = '';
	$res = openssl_public_encrypt($s,$x,$pubkey);
	return base64url_encode($x);
}

function zot_prv_decode($s,$prvkey) {
	$s = base64url_decode($s);
	$x = '';
	openssl_private_decrypt($s,$x,$prvkey);
	return $x;
}

function zot_pub_decode($s,$pubkey) {
	$s = base64url_decode($s);
	$x = '';
	openssl_public_decrypt($s,$x,$pubkey);
	return $x;
}


function zot_getzid($url,$myaddress,$myprvkey) {
	$ret = array();
	$j = zot_get($url,array('sender' => $myaddress));
	if($j->zid_encoded)
		$ret['zid'] = zot_prv_decode($j->zid_encoded,$myprvkey);
	if($j->zkey_encoded)
		$ret['zkey'] = zot_prv_decode($j->zkey_encoded,$myprvkey);
	return $ret;
}

function zot_post_init($url,$zid,$myprvkey,$theirpubkey) {
	$ret = array();

	$zinit = random_string(32);

	$j = zot_get($url,array('zid' => $zid,'zinit' => $zinit));
	
	$a = get_app();
	if(! $a->get_curl_code())
		return ZCURL_TIMEOUT;
	if(! $j->zinit) {
		logger('zot_post_init: no zinit returned.');
		return false;
	}
	if(zot_pub_decode($j->zinit,$thierpubkey) !== $zinit) {
		logger('zot_post_init: incorrect zinit returned.');
		return false;
	}

	if($j->challenge) {
		$s = zot_prv_decode($j->challenge,$myprvkey);
		$s1 = substr($s,0,strpos($s,'.'));
		if($s1 != $zid) {
			logger("zot_post_init: incorrect zid returned");
			return false;
		}
		$ret['result'] = substr($s,strpos($s,'.') + 1);
		$ret['perms'] = $j->perms;
	}
	return $ret;
}


function zot_encrypt_data($data,&$key) {
	$key = random_string();
	return aes_encrypt($data,$key);
}


// encrypt the data prior to calling this function so it only need be done once per message
// regardless of the number of recipients.

function zot_post_data($url,$zid,$myprvkey,$theirpubkey,$encrypted_data,$key, $intro = false) {
	$i = zot_post_init($url,$zid,$myprvkey,$theirpubkey);
	if($i === ZCURL_TIMEOUT)
		return ZCURL_TIMEOUT;

	if((! $i) || (! array_key_exists('perms',$i)) || (! array_key_exists('result',$i)))
		return false;
	if((! stristr($i['perms'],'post')) && ($intro === false)) {
		logger("zot_post_data: no permission to post: url=$url zid=$zid");
		return false;
	} 
	$p = array();
	$p['zid'] = $zid;
	$p['result'] = zot_pub_encode($i['result'],$theirpubkey);
	$p['aes_key'] = zot_prv_encode($key,$myprvkey);
	$p['data'] = $encrypted_data;
	$s = zot_post($url,$p);
	$a = get_app();
	if(! $a->get_curl_code())
		return ZCURL_TIMEOUT;

	if($s) {
		$j = json_decode($s); 
		return $j;
	}
	return false;
}
	
function zot_deliver($recipients,$myprvkey,$data) {

	if(is_array($recipients) && count($recipients)) {

		$key = '';
		$encrypted = zot_encrypt_data($data,$key);


		foreach($recipients as $r) {
			$result = zot_post_data(
				$r['post'],
				$r['zid'],
				$myprvkey,
				$r['pubkey'],
				$encrypted,
				$key
			);
			if($result === false) {
				// post failed
				logger('zot_deliver: failed: ' . print_r($r,true));
			}
			elseif($result === ZCURL_TIMEOUT) {
				// queue for redelivery
			}
			elseif($result->error) {
				// failed at other end
				logger('zot_deliver: remote failure: ' . $result->error . ' ' . print_r($r,true));
			}
			elseif($result->success) {
				logger('zot_deliver: success ' . print_r($r,true, LOGGER_DEBUG));
			}
			else
				logger('zot_deliver: unknown failure.');
		}
	}
}


function zot_new_contact($user,$cc) {

	$zid = random_string(32);
	$zkey = random_string(32);

	logger("zot_new_contact: zid=$zid zkey=$zkey uid={$user['uid']} " . print_r($cc,true));

	$ret = array();
	$ret['zid_encoded'] = zot_pub_encode($zid,$cc['pubkey']);
	$ret['zkey_encoded'] = zot_pub_encode($zkey,$cc['pubkey']);
	return $ret;


	


}