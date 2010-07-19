<?php

if(! function_exists('register_post')) {
function register_post(&$a) {

	$verified = 0;
	$blocked  = 1;

	switch($a->config['register_policy']) {

	
	case REGISTER_OPEN:
		$blocked = 0;
		$verified = 1;
		break;

	case REGISTER_VERIFY:
		$blocked = 1;
		$verify = 0;
		break;

	default:
	case REGISTER_CLOSED:
		if((! x($_SESSION,'authenticated') && (! x($_SESSION,'administrator')))) {
			notice( "Permission denied." . EOL );
			return;
		}
		$blocked = 1;
		$verified = 0;
		break;
	}

	if(x($_POST,'username'))
		$username = notags(trim($_POST['username']));
	if(x($_POST,'email'))
		$email =notags(trim($_POST['email']));

	if((! x($username)) || (! x($email))) {
		notice( "Please enter the required information.". EOL );
		return;
	}

	$err = '';

	if(!eregi('[A-Za-z0-9._%-]+@[A-Za-z0-9._%-]+\.[A-Za-z]{2,6}',$email))
		$err .= " Not valid email.";
	if(strlen($username) > 40)
		$err .= " Please use a shorter name.";
	if(strlen($username) < 3)
		$err .= " Name too short.";
	$r = q("SELECT `uid` FROM `user` 
		WHERE `email` = '%s' LIMIT 1",
		dbesc($email)
		);
	if($r !== false && count($r))
		$err .= " This email address is already registered.";
	if(strlen($err)) {
		notice( $err . EOL );
		return;
	}


	$new_password = autoname(6) . mt_rand(100,9999);
	$new_password_encoded = hash('whirlpool',$new_password);

	$res=openssl_pkey_new(array(
		'digest_alg' => 'whirlpool',
		'private_key_bits' => 4096,
		'encrypt_key' => false ));

	// Get private key

	$prvkey = '';

	openssl_pkey_export($res, $prvkey);

	// Get public key

	$pkey = openssl_pkey_get_details($res);
	$pubkey = $pkey["key"];

	$r = q("INSERT INTO `user` ( `username`, `password`, `email`,
		`pubkey`, `prvkey`, `verified`, `blocked` )
		VALUES ( '%s', '%s', '%s', '%s', '%s', %d, %d )",
		dbesc($username),
		dbesc($new_password_encoded),
		dbesc($email),
		dbesc($pubkey),
		dbesc($prvkey),
		intval($verified),
		intval($blocked)
		);

	if($r) {
		$r = q("SELECT `uid` FROM `user` 
			WHERE `username` = '%s' AND `password` = '%s' LIMIT 1",
			dbesc($username),
			dbesc($new_password_encoded)
			);
		if($r !== false && count($r))
			$newuid = intval($r[0]['uid']);
	}
	else {
		notice( "An error occurred during registration. Please try again." . EOL );
		return;
	} 		

	if(x($newuid) !== NULL) {
		$r = q("INSERT INTO `profile` ( `uid`, `profile-name`, `is-default`, `name`, `photo`, `thumb` )
			VALUES ( %d, '%s', %d, '%s', '%s', '%s' ) ",
			intval($newuid),
			'default',
			1,
			dbesc($username),
			dbesc($a->get_baseurl() . '/images/default-profile.jpg'),
			dbesc($a->get_baseurl() . '/images/default-profile-sm.jpg')

		);
		if($r === false) {
			notice( "An error occurred creating your default profile. Please try again." . EOL );
			// Start fresh next time.
			$r = q("DELETE FROM `user` WHERE `uid` = %d",
				intval($newuid));
			return;
		}
		$r = q("INSERT INTO `contact` ( `uid`, `created`, `self`, `name`, `photo`, `thumb`, `blocked`, `pending`, `url`,
			`request`, `notify`, `poll`, `confirm` )
			VALUES ( %d, '%s', 1, '%s', '%s', '%s', 0, 0, '%s', '%s', '%s', '%s', '%s' ) ",
			intval($newuid),
			datetime_convert(),
			dbesc($username),
			dbesc($a->get_baseurl() . '/images/default-profile.jpg'),
			dbesc($a->get_baseurl() . '/images/default-profile-sm.jpg'), 
			dbesc($a->get_baseurl() . '/profile/' . intval($newuid)),
			dbesc($a->get_baseurl() . '/dfrn_request/' . intval($newuid)),
			dbesc($a->get_baseurl() . '/dfrn_notify/' . intval($newuid)),
			dbesc($a->get_baseurl() . '/dfrn_poll/' . intval($newuid)),
			dbesc($a->get_baseurl() . '/dfrn_confirm/' . intval($newuid))

		);


	}

	if( $a->config['register_policy'] == REGISTER_OPEN ) {
		$email_tpl = file_get_contents("view/register_open_eml.tpl");
		$email_tpl = replace_macros($email_tpl, array(
				'$sitename' => $a->config['sitename'],
				'$siteurl' =>  $a->get_baseurl(),
				'$username' => $username,
				'$email' => $email,
				'$password' => $new_password,
				'$uid' => $newuid ));

		$res = mail($email,"Registration details for {$a->config['sitename']}",$email_tpl,"From: Administrator@{$_SERVER[SERVER_NAME]}");

	}

	if($res) {
		notice( "Registration successful. Please check your email for further instructions." . EOL ) ;
		goaway($a->get_baseurl());
	}
	else {
		notice( "Failed to send email message. Here is the message that failed. $email_tpl " . EOL );
	}
	
	return;
}}






if(! function_exists('register_content')) {
function register_content(&$a) {

	if($a->config['register_policy'] == REGISTER_CLOSED) {
		notice("Permission denied." . EOL);
		return;
	}

	$o = file_get_contents("view/register.tpl");
	$o = replace_macros($o, array('$registertext' =>((x($a->config,'register_text'))? $a->config['register_text'] : "" )));
	return $o;

}}

