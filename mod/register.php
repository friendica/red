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

	if(! valid_email($email))
		$err .= t(' Not a valid email address.');
	if(strlen($username) > 48)
		$err .= t(' Please use a shorter name.');
	if(strlen($username) < 3)
		$err .= t(' Name too short.');

	// I don't really like having this rule, but it cuts down
	// on the number of auto-registrations by Russian spammers
	
	$no_utf = get_config('system','no_utf');

	$pat = (($no_utf) ? '/^[a-zA-Z]* [a-zA-Z]*$/' : '/^\p{L}* \p{L}*$/u' ); 

	$loose_reg = get_config('system','no_regfullname');

	if((! $loose_reg) && (! preg_match($pat,$username)))
		$err .= t(' That doesn\'t appear to be your full name.');

	if(! allowed_email($email))
			$err .= t(' Your email domain is not among those allowed on this site.');

	$nickname = strtolower($nickname);
	if(! preg_match("/^[a-z][a-z0-9\-\_]*$/",$nickname))
		$err .= t(' Your "nickname" can only contain "a-z", "0-9", "-", and "_", and must also begin with a letter.');
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

	$sres=openssl_pkey_new(array(
		'encrypt_key' => false ));

	// Get private key

	$sprvkey = '';

	openssl_pkey_export($sres, $sprvkey);

	// Get public key

	$spkey = openssl_pkey_get_details($sres);
	$spubkey = $spkey["key"];

	$r = q("INSERT INTO `user` ( `username`, `password`, `email`, `nickname`,
		`pubkey`, `prvkey`, `spubkey`, `sprvkey`, `verified`, `blocked` )
		VALUES ( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d )",
		dbesc($username),
		dbesc($new_password_encoded),
		dbesc($email),
		dbesc($nickname),
		dbesc($pubkey),
		dbesc($prvkey),
		dbesc($spubkey),
		dbesc($sprvkey),
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
		$r = q("INSERT INTO `contact` ( `uid`, `created`, `self`, `name`, `nick`, `photo`, `thumb`, `micro`, `blocked`, `pending`, `url`,
			`request`, `notify`, `poll`, `confirm`, `name-date`, `uri-date`, `avatar-date` )
			VALUES ( %d, '%s', 1, '%s', '%s', '%s', '%s', '%s', 0, 0, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) ",
			intval($newuid),
			datetime_convert(),
			dbesc($username),
			dbesc($nickname),
			dbesc($a->get_baseurl() . "/photo/profile/{$newuid}.jpg"),
			dbesc($a->get_baseurl() . "/photo/avatar/{$newuid}.jpg"),
			dbesc($a->get_baseurl() . "/photo/micro/{$newuid}.jpg"),
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

	require_once('include/Photo.php');

	$photo = gravatar_img($email);
	$photo_failure = false;

	$filename = basename($photo);
	$img_str = fetch_url($photo,true);
	$img = new Photo($img_str);
	if($img->is_valid()) {

		$img->scaleImageSquare(175);
					
		$hash = photo_new_resource();

		$r = $img->store($newuid, 0, $hash, $filename, t('Profile Photos'), 4 );

		if($r === false)
			$photo_failure = true;

		$img->scaleImage(80);

		$r = $img->store($newuid, 0, $hash, $filename, t('Profile Photos'), 5 );

		if($r === false)
			$photo_failure = true;

		$img->scaleImage(48);

		$r = $img->store($newuid, 0, $hash, $filename, t('Profile Photos'), 6 );

		if($r === false)
			$photo_failure = true;

		if(! $photo_failure) {
			q("UPDATE `photo` SET `profile` = 1 WHERE `resource-id` = '%s' ",
				dbesc($hash)
			);
		}
	}

	if( $a->config['register_policy'] == REGISTER_OPEN ) {
		$email_tpl = load_view_file("view/register_open_eml.tpl");
		$email_tpl = replace_macros($email_tpl, array(
				'$sitename' => $a->config['sitename'],
				'$siteurl' =>  $a->get_baseurl(),
				'$username' => $username,
				'$email' => $email,
				'$password' => $new_password,
				'$uid' => $newuid ));

		$res = mail($email, t('Registration details for ') . $a->config['sitename'],
			$email_tpl, 'From: ' . t('Administrator') . '@' . $_SERVER['SERVER_NAME']);


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

		$email_tpl = load_view_file("view/register_verify_eml.tpl");
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
			$email_tpl,'From: ' .  t('Administrator') . '@' . $_SERVER['SERVER_NAME']);

		if($res) {
			notice( t('Your registration is pending approval by the site owner.') . EOL ) ;
			goaway($a->get_baseurl());
		}

	}
	
	return;
}}






if(! function_exists('register_content')) {
function register_content(&$a) {

	// logged in users can register others (people/pages/groups)
	// even with closed registrations, unless specifically prohibited by site policy.
	// 'block_extended_register' blocks all registrations, period.

	$block = get_config('system','block_extended_register');

	if((($a->config['register_policy'] == REGISTER_CLOSED) && (! getuid())) || ($block)) {
		notice("Permission denied." . EOL);
		return;
	}

	$o = load_view_file("view/register.tpl");
	$o = replace_macros($o, array(
		'$registertext' =>((x($a->config,'register_text'))
			? '<div class="error-message">' . $a->config['register_text'] . '</div>'
			: "" ),
		'$sitename' => $a->get_hostname()
	));
	return $o;

}}

