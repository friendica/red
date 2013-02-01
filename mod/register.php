<?php

require_once('include/account.php');

function register_init(&$a) {

	$result = null;
	$cmd = ((argc() > 1) ? argv(1) : '');

	switch($cmd) {
		case 'invite_check.json':
			$result = check_account_invite($_REQUEST['invite_code']);
			break;
		case 'email_check.json':
			$result = check_account_email($_REQUEST['email']);
			break;
		case 'password_check.json':
			$result = check_account_password($_REQUEST['password']);
			break;
		default: 
			break;
	}
	if($result) {
		json_return_and_die($result);
	}
}


function register_post(&$a) {

	$max_dailies = intval(get_config('system','max_daily_registrations'));
	if($max_dailies) {
		$r = q("select count(account_id) as total from account where account_created > UTC_TIMESTAMP() - INTERVAL 1 day");
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
			if(! is_site_admin()) {
				notice( t('Permission denied.') . EOL );
				return;
			}
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
	require_once('include/security.php');


 	$using_invites = intval(get_config('system','invitation_only'));
	$num_invites   = intval(get_config('system','number_invites'));
	$invite_code   = ((x($_POST,'invite_code'))  ? notags(trim($_POST['invite_code']))  : '');

	if($using_invites && $invite_code) {
		q("delete * from register where hash = '%s' limit 1", dbesc($invite_code));
		set_pconfig($result['account']['account_id'],'system','invites_remaining',$num_invites);
	}

	if($policy == REGISTER_OPEN ) {
		$res = send_verification_email($result['email'],$result['password']);
		if($res) {
			info( t('Registration successful. Please check your email for validation instructions.') . EOL ) ;
		}
	}
	elseif($policy == REGISTER_APPROVE) {
		$res = send_reg_approval_email($result);
		if($res) {
			info( t('Your registration is pending approval by the site owner.') . EOL ) ;
		}
		else {
			notice( t('Your registration can not be processed.') . EOL);
		}
		goaway(z_root());
	}

	authenticate_success($result['account'],true,false,true);

	if(! strlen($next_page = get_config('system','workflow_register_next')))
		$next_page = 'new_channel';

	$_SESSION['workflow'] = true;
	
	goaway(z_root() . '/' . $next_page);

}







function register_content(&$a) {


	if(get_config('system','register_policy') == REGISTER_CLOSED) {
		notice("Permission denied." . EOL);
		return;
	}

	$max_dailies = intval(get_config('system','max_daily_registrations'));
	if($max_dailies) {
		$r = q("select count(account_id) as total from account where account_created > UTC_TIMESTAMP() - INTERVAL 1 day");
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

	$email        = ((x($_REQUEST,'email'))       ? strip_tags(trim($_REQUEST['email']))       :  "" );
	$password     = ((x($_REQUEST,'password'))    ? trim($_REQUEST['password'])                :  "" );
	$password2    = ((x($_REQUEST,'password2'))   ? trim($_REQUEST['password2'])               :  "" );
	$invite_code  = ((x($_REQUEST,'invite_code')) ? strip_tags(trim($_REQUEST['invite_code'])) :  "" );


	$o = replace_macros(get_markup_template('register.tpl'), array(

		'$title'        => t('Registration'),
		'$registertext' => get_config('system','register_text'),
		'$invitations'  => get_config('system','invitation_only'),
		'$invite_desc'  => t('Membership on this site is by invitation only.'),
		'$label_invite' => t('Please enter your invitation code'),
		'$invite_code'  => $invite_code,

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

