<?php /** @file */

function rsa_sign($data,$key,$alg = 'sha256') {
	if(! $key)
		return 'no key';
	$sig = '';
	if(defined(OPENSSL_ALGO_SHA256) && $alg === 'sha256')
		$alg = OPENSSL_ALGO_SHA256;
	openssl_sign($data,$sig,$key,$alg);
	return $sig;
}

function rsa_verify($data,$sig,$key,$alg = 'sha256') {

	if(! $key)
		return false;

	if(defined(OPENSSL_ALGO_SHA256) && $alg === 'sha256')
		$alg = OPENSSL_ALGO_SHA256;
	$verify = openssl_verify($data,$sig,$key,$alg);
	return $verify;
}

function pkcs5_pad ($text, $blocksize)
{
    $pad = $blocksize - (strlen($text) % $blocksize);
    return $text . str_repeat(chr($pad), $pad);
}

function pkcs5_unpad($text)
{
    $pad = ord($text{strlen($text)-1});
    if ($pad > strlen($text)) return false;
    if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false;
    return substr($text, 0, -1 * $pad);
} 

function AES256CBC_encrypt($data,$key,$iv) {
	return mcrypt_encrypt(
		MCRYPT_RIJNDAEL_128, 
		str_pad($key,32,"\0"), 
		pkcs5_pad($data,16), 
		MCRYPT_MODE_CBC, 
		str_pad($iv,16,"\0"));
}

function AES256CBC_decrypt($data,$key,$iv) {
	return pkcs5_unpad(mcrypt_decrypt(
		MCRYPT_RIJNDAEL_128, 
		str_pad($key,32,"\0"), 
		$data, 
		MCRYPT_MODE_CBC, 
		str_pad($iv,16,"\0")));
}

function crypto_encapsulate($data,$pubkey,$alg='aes256cbc') {
	if($alg === 'aes256cbc')
		return aes_encapsulate($data,$pubkey);

}


function aes_encapsulate($data,$pubkey) {
	if(! $pubkey)
		logger('aes_encapsulate: no key. data: ' . $data);
	$key = random_string(32,RANDOM_STRING_TEXT);
	$iv  = random_string(16,RANDOM_STRING_TEXT);
	$result['data'] = base64url_encode(AES256CBC_encrypt($data,$key,$iv),true);
	// log the offending call so we can track it down
	if(! openssl_public_encrypt($key,$k,$pubkey)) {
		$x = debug_backtrace();
		logger('aes_encapsulate: RSA failed. ' . print_r($x[0],true));
	}
	$result['alg'] = 'aes256cbc';
 	$result['key'] = base64url_encode($k,true);
	openssl_public_encrypt($iv,$i,$pubkey);
	$result['iv'] = base64url_encode($i,true);
	return $result;
}

function crypto_unencapsulate($data,$prvkey) {
	if(! $data)
		return;
	$alg = ((array_key_exists('alg',$data)) ? $data['alg'] : 'aes256cbc');
	if($alg === 'aes256cbc')
		return aes_unencapsulate($data,$prvkey);

}


function aes_unencapsulate($data,$prvkey) {
	openssl_private_decrypt(base64url_decode($data['key']),$k,$prvkey);
	openssl_private_decrypt(base64url_decode($data['iv']),$i,$prvkey);
	return AES256CBC_decrypt(base64url_decode($data['data']),$k,$i);
}

function new_keypair($bits) {

	$openssl_options = array(
		'digest_alg'       => 'sha1',
		'private_key_bits' => $bits,
		'encrypt_key'      => false 
	);

	$conf = get_config('system','openssl_conf_file');
	if($conf)
		$openssl_options['config'] = $conf;
	
	$result = openssl_pkey_new($openssl_options);

	if(empty($result)) {
		logger('new_keypair: failed');
		return false;
	}

	// Get private key

	$response = array('prvkey' => '', 'pubkey' => '');

	openssl_pkey_export($result, $response['prvkey']);

	// Get public key
	$pkey = openssl_pkey_get_details($result);
	$response['pubkey'] = $pkey["key"];

	return $response;

}

