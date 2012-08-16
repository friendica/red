<?php

require_once('include/account.php');

function zregister_init(&$a) {
	$a->page['template'] = 'full';

	$cmd = ((argc() > 1) ? argv(1) : '');


	if($cmd === 'invite_check.json') {
		$result = check_account_invite($_REQUEST['invite_code']);
		json_return_and_die($result);
	}

	if($cmd === 'email_check.json') {
		$result = check_account_email($_REQUEST['email']);
		json_return_and_die($result);
	}

	if($cmd === 'password_check.json') {
		$result = check_account_password($_REQUEST['password']);
		json_return_and_die($result);
	}
}


function zregister_post(&$a) {

	$max_dailies = intval(get_config('system','max_daily_registrations'));
	if($max_dailies) {
		$r = q("select count(*) as total from account where account_created > UTC_TIMESTAMP - INTERVAL 1 day");
		if($r && $r[0]['total'] >= $max_dailies) {
			notice( t('Maximum daily site registrations exceeded. Please try again tomorrow.') . EOL);
			return;
		}
	}

	if(! x($_POST,'tos')) {
		notice( t('Please indicate acceptance of the Terms of Service. Registration failed.') . EOL);
		return;
	}

	$policy = get_config('system','register_policy');

	switch($policy) {

		case REGISTER_OPEN:
			$flags = ACCOUNT_UNVERIFIED;
			break;

		case REGISTER_APPROVE:
			$flags = ACCOUNT_UNVERIFIED | ACCOUNT_BLOCKED;
			break;

		default:
		case REGISTER_CLOSED:
			// TODO check against service class and fix this line
			//		if((! x($_SESSION,'authenticated') && (! x($_SESSION,'administrator')))) {
			//			notice( t('Permission denied.') . EOL );
			//			return;
			//		}
			$flags = ACCOUNT_UNVERIFIED | ACCOUNT_BLOCKED;
			break;
	}

	$arr = $_POST;
	$arr['account_flags'] = $flags;

	$result = create_account($arr);

	if(! $result['success']) {
		notice($result['message']);
		return;
	}

	$user = $result['user'];
 
	$using_invites = get_config('system','invitation_only');
	$num_invites   = get_config('system','number_invites');
	$invite_id  = ((x($_POST,'invite_id'))  ? notags(trim($_POST['invite_id']))  : '');


	if($policy == REGISTER_OPEN ) {

		if($using_invites && $invite_id) {
			q("delete * from register where hash = '%s' limit 1", dbesc($invite_id));
			set_pconfig($user['uid'],'system','invites_remaining',$num_invites);
		}

		$email_tpl = get_intltext_template("register_open_eml.tpl");
		$email_tpl = replace_macros($email_tpl, array(
			'$sitename' => $a->config['sitename'],
			'$siteurl' =>  $a->get_baseurl(),
			'$email'    => $user['email'],
			'$password' => $result['password'],
		));

		$res = mail($user['email'], sprintf(t('Registration details for %s'), $a->config['sitename']),
			$email_tpl, 
				'From: ' . t('Administrator') . '@' . $_SERVER['SERVER_NAME'] . "\n"
				. 'Content-type: text/plain; charset=UTF-8' . "\n"
				. 'Content-transfer-encoding: 8bit' );

		if($res) {
			info( t('Registration successful. Please check your email for validation instructions.') . EOL ) ;
			goaway(z_root());
		}
	}
	elseif($policy == REGISTER_APPROVE) {

		if(! strlen($a->config['admin_email'])) {
			notice( t('Your registration can not be processed.') . EOL);
			goaway(z_root());
		}

		$hash = random_string();
		$r = q("INSERT INTO `register` ( `hash`, `created`, `uid`, `password`, `language` ) VALUES ( '%s', '%s', %d, '%s', '%s' ) ",
			dbesc($hash),
			dbesc(datetime_convert()),
			intval($user['uid']),
			dbesc($result['password']),
			dbesc($a->language)
		);

		$r = q("SELECT `language` FROM `user` WHERE `email` = '%s' LIMIT 1",
			dbesc($a->config['admin_email'])
		);
		if(count($r))
			push_lang($r[0]['language']);
		else
			push_lang('en');

		if($using_invites && $invite_id) {
			q("delete * from register where hash = '%s' limit 1", dbesc($invite_id));
			set_pconfig($user['uid'],'system','invites_remaining',$num_invites);
		}

		$email_tpl = get_intltext_template("register_verify_eml.tpl");
		$email_tpl = replace_macros($email_tpl, array(
				'$sitename' => $a->config['sitename'],
				'$siteurl' =>  $a->get_baseurl(),
				'$username' => $user['username'],
				'$email' => $user['email'],
				'$password' => $result['password'],
				'$uid' => $user['uid'],
				'$hash' => $hash
		 ));

		$res = mail($a->config['admin_email'], sprintf(t('Registration request at %s'), $a->config['sitename']),
			$email_tpl,
				'From: ' . t('Administrator') . '@' . $_SERVER['SERVER_NAME'] . "\n"
				. 'Content-type: text/plain; charset=UTF-8' . "\n"
				. 'Content-transfer-encoding: 8bit' );

		pop_lang();

		if($res) {
			info( t('Your registration is pending approval by the site owner.') . EOL ) ;
			goaway(z_root());
		}

	}

	return;
}







function zregister_content(&$a) {


	if(get_config('system','register_policy') == REGISTER_CLOSED) {
		notice("Permission denied." . EOL);
		return;
	}

	$max_dailies = intval(get_config('system','max_daily_registrations'));
	if($max_dailies) {
		$r = q("select count(*) as total from account where account_created > UTC_TIMESTAMP - INTERVAL 1 day");
		if($r && $r[0]['total'] >= $max_dailies) {
			logger('max daily registrations exceeded.');
			notice( t('This site has exceeded the number of allowed daily account registrations. Please try again tomorrow.') . EOL);
			return;
		}
	}

	// Configurable terms of service link

	$tosurl = get_config('system','tos_url');
	if(! $tosurl)
		$tosurl = $a->get_baseurl() . '/help/TermsOfService';

	$toslink = '<a href="' . $tosurl . '" >' . t('Terms of Service') . '</a>';

	// Configurable whether to restrict age or not - default is based on international legal requirements
	// This can be relaxed if you are on a restricted server that does not share with public servers

	if(get_config('system','no_age_restriction')) 
		$label_tos = sprintf( t('I accept the %s for this website'), $toslink);
	else
		$label_tos = sprintf( t('I am over 13 years of age and accept the %s for this website'), $toslink);

	$enable_tos = 1 - intval(get_config('system','no_termsofservice'));

	$email        = ((x($_REQUEST,'email'))        ? $_REQUEST['email']        :  "" );
	$password     = ((x($_REQUEST,'password'))     ? $_REQUEST['password']     :  "" );
	$password2    = ((x($_REQUEST,'password2'))    ? $_REQUEST['password2']    :  "" );
	$invite_code  = ((x($_REQUEST,'invite_code'))  ? $_REQUEST['invite_code']  :  "" );



	$o = replace_macros(get_markup_template('zregister.tpl'), array(

		'$title'        => t('Registration'),
		'$registertext' => get_config('system','register_text'),
		'$invitations'  => get_config('system','invitation_only'),
		'$invite_desc'  => t('Membership on this site is by invitation only.'),
		'$label_invite' => t('Please enter your invitation code'),
		'$invite_id'    => $invite_id,

		'$label_email'  => t('Your email address'),
		'$label_pass1'  => t('Choose a password'),
		'$label_pass2'  => t('Please re-enter your password'),
		'$label_tos'    => $label_tos,
		'$enable_tos'   => $enable_tos,	
		'$email'        => $email,
		'$pass1'        => $password,
		'$pass2'        => $password2,
		'$submit'       => t('Register')
	));

	return $o;

}

