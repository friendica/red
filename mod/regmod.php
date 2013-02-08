<?php

require_once('include/account.php');

function regmod_content(&$a) {

	global $lang;

	$_SESSION['return_url'] = $a->cmd;

	if(! local_user()) {
		info( t('Please login.') . EOL);
		$o .= '<br /><br />' . login(($a->config['system']['register_policy'] == REGISTER_CLOSED) ? 0 : 1);
		return $o;
	}

	if((!is_site_admin()) || (x($_SESSION,'submanage') && intval($_SESSION['submanage']))) {
		notice( t('Permission denied.') . EOL);
		return '';
	}

	if(argc() != 3)
		killme();

	$cmd  = argv(1);
	$hash = argv(2);

	if($cmd === 'deny') {
		if (!user_deny($hash)) killme();
	}

	if($cmd === 'allow') {
		if (!user_allow($hash)) killme();
	}
}
