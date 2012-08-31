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

	if(($arr['account_id'] = get_account_id()) === false) {
		notice( t('Permission denied.') . EOL );
		return;
	}

	$result = create_identity($arr);

	if(! $result['success']) {
		notice($result['message']);
		return;
	}

	if(! strlen($next_page = get_config('system','workflow_identity_next')))
		$next_page = 'settings';
	
	goaway(z_root() . '/' . $next_page);

}







function zentity_content(&$a) {

	if(! get_account_id()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$name         = ((x($_REQUEST,'name'))         ? $_REQUEST['name']         :  "" );
	$nickname     = ((x($_REQUEST,'nickname'))     ? $_REQUEST['nickname']     :  "" );


	$o = replace_macros(get_markup_template('zentity.tpl'), array(

		'$title'        => t('Create a Profile Channel'),
		'$desc'         => t('A channel is a collection of web resources attached to a profile page. This is typically used to create a personal profile, but may also be used to create a blog, public and private forums, celebrity pages, and more. You may create as many of these as your provider allows.'),

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

