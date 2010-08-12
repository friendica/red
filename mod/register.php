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

	case REGISTER_APPROVE:
		$blocked = 1;
		$verified = 0;
		break;

	default:
	case REGISTER_CLOSED:
		if((! x($_SESSION,'authenticated') && (! x($_SESSION,'administrator')))) {
			notice( t('Permission denied.') . EOL );
			return;
		}
		$blocked = 1;
		$verified = 0;
		break;
	}

	if(x($_POST,'username'))
		$username = notags(trim($_POST['username']));
	if(x($_POST['nickname']))
		$nickname = notags(trim($_POST['nickname']));
	if(x($_POST,'email'))
		$email = notags(trim($_POST['email']));

	if((! x($username)) || (! x($email)) || (! x($nickname))) {
		notice( t('Please enter the required information.') . EOL );
		return;
	}

	$err = '';

	// TODO fix some of these regex's for int'l/utf-8.

	if(!eregi('[A-Za-z0-9._%-]+@[A-Za-z0-9._%-]+\.[A-Za-z]{2,6}',$email))
		$err .= t(' Not a valid email address.');
	if(strlen($username) > 48)
		$err .= t(' Please use a shorter name.');
	if(strlen($username) < 3)
		$err .= t(' Name too short.');

	// I don't really like having this rule, but it cuts down
	// on the number of auto-registrations by Russian spammers

	if(! preg_match("/^[a-zA-Z]* [a-zA-Z]*$/",$username))
		$err .= t(' That doesn\'t appear to be your full name.');


	$r = q("SELECT `uid` FROM `user` 
		WHERE `email` = '%s' LIMIT 1",
		dbesc($email)
	);

	if($r !== false && count($r))
		$err .= t(' Your email address is already registered on this system.') ;

	if(! preg_match("/^[a-zA-Z][a-zA-Z0-9\-\_]*$/",$nickname))
		$err .= t(' Nickname <strong>must</strong> start with a letter and contain only letters, numbers, dashes, or underscore.') ;
	$r = q("SELECT `uid` FROM `user`
               	WHERE `nickname` = '%s' LIMIT 1",
               	dbesc($nickname)
	);
	if(count($r))
		$err .= t(' Nickname is already registered. Please choose another.');

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

	$r = q("INSERT INTO `user` ( `username`, `password`, `email`, `nickname`,
		`pubkey`, `prvkey`, `verified`, `blocked` )
		VALUES ( '%s', '%s', '%s', '%s', '%s', '%s', %d, %d )",
		dbesc($username),
		dbesc($new_password_encoded),
		dbesc($email),
		dbesc($nickname),
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
		notice( t('An error occurred during registration. Please try again.') . EOL );
		return;
	} 		

	if(x($newuid) !== false) {
		$r = q("INSERT INTO `profile` ( `uid`, `profile-name`, `is-default`, `name`, `photo`, `thumb` )
			VALUES ( %d, '%s', %d, '%s', '%s', '%s' ) ",
			intval($newuid),
			'default',
			1,
			dbesc($username),
			dbesc($a->get_baseurl() . "/photo/profile/{$newuid}.jpg"),
			dbesc($a->get_baseurl() . "/photo/avatar/{$newuid}.jpg")

		);
		if($r === false) {
			notice( t('An error occurred creating your default profile. Please try again.') . EOL );
			// Start fresh next time.
			$r = q("DELETE FROM `user` WHERE `uid` = %d",
				intval($newuid));
			return;
		}
		$r = q("INSERT INTO `contact` ( `uid`, `created`, `self`, `name`, `photo`, `thumb`, `blocked`, `pending`, `url`,
			`request`, `notify`, `poll`, `confirm`, `name-date`, `uri-date`, `avatar-date` )
			VALUES ( %d, '%s', 1, '%s', '%s', '%s', 0, 0, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) ",
			intval($newuid),
			datetime_convert(),
			dbesc($username),
			dbesc($a->get_baseurl() . "/photo/profile/{$newuid}.jpg"),
			dbesc($a->get_baseurl() . "/photo/avatar/{$newuid}.jpg"),
			dbesc($a->get_baseurl() . "/profile/$nickname"),
			dbesc($a->get_baseurl() . "/dfrn_request/$nickname"),
			dbesc($a->get_baseurl() . "/dfrn_notify/$nickname"),
			dbesc($a->get_baseurl() . "/dfrn_poll/$nickname"),
			dbesc($a->get_baseurl() . "/dfrn_confirm/$nickname"),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			dbesc(datetime_convert())
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

		$res = mail($email, t('Registration details for ') . $a->config['sitename'],
			$email_tpl, 'From: ' . t('Administrator@') . $_SERVER[SERVER_NAME]);


		if($res) {
			notice( t('Registration successful. Please check your email for further instructions.') . EOL ) ;
			goaway($a->get_baseurl());
		}
		else {
			notice( t('Failed to send email message. Here is the message that failed.') . $email_tpl . EOL );
		}
	}
	elseif($a->config['register_policy'] == REGISTER_APPROVE) {
		if(! strlen($a->config['admin_email'])) {
			notice( t('Your registration can not be processed.') . EOL);
			goaway($a->get_baseurl());
		}

		$hash = random_string();
		$r = q("INSERT INTO `register` ( `hash`, `created`, `uid`, `password` ) VALUES ( '%s', '%s', %d, '%s' ) ",
			dbesc($hash),
			dbesc(datetime_convert()),
			intval($newuid),
			dbesc($new_password)
		);

		$email_tpl = file_get_contents("view/register_verify_eml.tpl");
		$email_tpl = replace_macros($email_tpl, array(
				'$sitename' => $a->config['sitename'],
				'$siteurl' =>  $a->get_baseurl(),
				'$username' => $username,
				'$email' => $email,
				'$password' => $new_password,
				'$uid' => $newuid,
				'$hash' => $hash
		 ));

		$res = mail($a->config['admin_email'], t('Registration request at ') . $a->config['sitename'],
			$email_tpl,'From: ' .  t('Administrator@') . $_SERVER[SERVER_NAME]);

		if($res) {
			notice( t('Your registration is pending approval by the site owner.') . EOL ) ;
			goaway($a->get_baseurl());
		}

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
	$o = replace_macros($o, array(
		'$registertext' =>((x($a->config,'register_text'))
			? '<div class="error-message">' . $a->config['register_text'] . '</div>'
			: "" ),
		'$sitename' => $a->get_hostname()
	));
	return $o;

}}

