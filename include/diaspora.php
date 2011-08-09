<?php




function diaspora_base_message($type,$data) {

	$tpl = get_markup_template('diaspora_' . $type . '.tpl');
	if(! $tpl) 
		return '';
	return replace_macros($tpl,$data);

}


function diaspora_msg_build($msg,$user,$contact,$prvkey,$pubkey) {
	$a = get_app();

	$inner_aes_key = random_string(16);
	$b_inner_aes_key = base64_encode($inner_aes_key);
	$inner_iv = random_string(16);
	$b_inner_iv = base64_encode($inner_iv);

	$outer_aes_key = random_string(16);
	$b_outer_aes_key = base64_encode($outer_aes_key);
	$outer_iv = random_string(16);
	$b_outer_iv = base64_encode($outer_iv);
	
	$handle = 'acct:' . $user['nickname'] . '@' . substr($a->get_baseurl(), strpos('://') + 3);

	$padded_data = pkcs5_pad($msg);
	$inner_encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $inner_aes_key, $padded_data, MCRYPT_MODE_CBC, $inner_iv);

	$b64_data = base64_encode($inner_encrypted);
	$b64url_data = base64url_encode($b64_data);
	$b64url_stripped = str_replace(array("\n","\r"," ","\t"),array('','','',''),$b64url_data);
    $lines = str_split($b64url_stripped,60);
    $data = implode("\n",$lines);
	$data = $data . (($data[-1] != "\n") ? "\n" : '') ;
	$type = 'application/atom+xml';
	$encoding = 'base64url';
	$alg = 'RSA-SHA256';

	$signable_data = $data  . '.' . base64url_encode($type) . "\n" . '.' 
		. base64url_encode($encoding) . "\n" . '.' . base64url_encode($alg) . "\n";

	$signature = '';
	$result = openssl_sign($signable_data,$signature,$prvkey,'SHA256');

	$sig = base64url_encode($signature);

$decrypted_header = <<< EOT
<decrypted_header>
  <iv>$b_inner_iv</iv>
  <aes_key>$b_inner_aes_key</aes_key>
  <author>
    <name>{$contact['name']}</name>
    <uri>$handle</uri>
  </author>
</decrypted_header>
EOT;

	$decrypted_header = pkcs5_pad($decrypted_header);
	$ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $outer_aes_key, $decrypted_header, MCRYPT_MODE_CBC, $outer_iv);

	$outer_json = json_encode(array('iv' => $b64_outer_iv,'key' => $b64_outer_aes_key));
	$encrypted_outer_key_bundle = '';
	openssl_public_encrypt($outer_json,$encrypted_outer_key_bundle,$pubkey);
	
	$b64_encrypted_outer_key_bundle = base64_encode($encrypted_outer_key_bundle);
	$encrypted_header_json_object = json_encode(array('aes_key' => base64_encode($encrypted_outer_key_bundle), 
		'ciphertext' => base64_encode($ciphertext)));
	$encrypted_header = '<encrypted_header>' . base64_encode($encrypted_header_json_object) . '</encrypted_header>';

$magicenv = <<< EOT
<?xml version='1.0' encoding='UTF-8'?>
<entry xmlns='http://www.w3.org/2005/Atom'>
  $encrypted_header
  <me:env xmlns:me="http://salmon-protocol.org/ns/magic-env">
    <me:encoding>base64url</me:encoding>
    <me:alg>RSA-SHA256</me:alg>
    <me:data type="application/atom+xml">$data</me:data>
    <me:sig>$sig</me:sig>
  </me:env>
</entry>
EOT;

	return $magic_env;

}