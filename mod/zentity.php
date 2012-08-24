<?php

require_once('include/identity.php');

function zentity_init(&$a) {

	$cmd = ((argc() > 1) ? argv(1) : '');


	if($cmd === 'autofill.json') {
		require_once('library/urlify/URLify.php');
		$result = array('error' => false, 'message' => '');
		$n = trim($_REQUEST['name']);

		$x = strtolower(URLify::transliterate($n));

		$test = array();

		// first name
		$test[] = legal_webbie(substr($x,0,strpos($x,' ')));
		if($test[0]) {
			// first name plus first initial of last
			$test[] = ((strpos($x,' ')) ? $test[0] . legal_webbie(trim(substr($x,strpos($x,' '),2))) : '');
			// first name plus random number
			$test[] = $test[0] . mt_rand(1000,9999);
		}
		// fullname
		$test[] = legal_webbie($x);
		// fullname plus random number
		$test[] = legal_webbie($x) . mt_rand(1000,9999);

		json_return_and_die(check_webbie($test));
	}

	if($cmd === 'checkaddr.json') {
		require_once('library/urlify/URLify.php');
		$result = array('error' => false, 'message' => '');
		$n = trim($_REQUEST['nick']);

		$x = strtolower(URLify::transliterate($n));

		$test = array();

		$n = legal_webbie($x);
		if(strlen($n)) {
			$test[] = $n;
			$test[] = $n . mt_rand(1000,9999);
		}

		for($y = 0; $y < 100; $y ++)
			$test[] = 'id' . mt_rand(1000,9999);

		json_return_and_die(check_webbie($test));
	}


}


function zentity_post(&$a) {

	$arr = $_POST;

	if(($uid = intval(local_user())) == 0) {
		notice( t('Permission denied.') . EOL );
		return;
	}

	$result = create_identity($arr);

	if(! $result['success']) {
		notice($result['message']);
		return;
	}

	return;
}







function zentity_content(&$a) {


	if((! local_user()) && ($a->config['register_policy'] == REGISTER_CLOSED)) {
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
		$label_tos = 


	$email        = ((x($_REQUEST,'email'))        ? $_REQUEST['email']        :  "" );
	$password     = ((x($_REQUEST,'password'))     ? $_REQUEST['password']     :  "" );
	$password2    = ((x($_REQUEST,'password2'))    ? $_REQUEST['password2']    :  "" );
	$invite_code  = ((x($_REQUEST,'invite_code'))  ? $_REQUEST['invite_code']  :  "" );



	$o = replace_macros(get_markup_template('zentity.tpl'), array(

		'$title'        => t('Create Identity'),
		'$desc'         => t('An identity is a profile container for a personal profile, blog, public or private group/forum, celebrity page, and more. You may create as many of these as your provider allows.'),

		'$label_name'   => t('Full name'),
		'$label_nick'   => t('Choose a short nickname'),
		'$nick_desc'    => t('Your nickname will be used to create an easily remembered web address ("webbie") for your profile.'),
		'$label_import' => t('Check this box to import an existing identity file from another location'),
		'$name'         => $name,
		'$nickname'     => $nickname,
		'$submit'       => t('Create')
	));

	return $o;

}

