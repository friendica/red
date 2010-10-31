<?php

// login/logout 

if((isset($_SESSION)) && (x($_SESSION,'authenticated')) && ((! (x($_POST,'auth-params'))) || ($_POST['auth-params'] !== 'login'))) {

	if(((x($_POST,'auth-params')) && ($_POST['auth-params'] === 'logout')) || ($a->module === 'logout')) {
	
		// process logout request

		unset($_SESSION['authenticated']);
		unset($_SESSION['uid']);
		unset($_SESSION['visitor_id']);
		unset($_SESSION['administrator']);
		unset($_SESSION['cid']);
		unset($_SESSION['theme']);
		unset($_SESSION['page_flags']);
		notice( t('Logged out.') . EOL);
		goaway($a->get_baseurl());
	}

	if(x($_SESSION,'uid')) {

		// already logged in user returning

		$r = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
			intval($_SESSION['uid'])
		);

		if(! count($r)) {
			goaway($a->get_baseurl());
		}

		// initialise user environment

		$a->user = $r[0];
		$_SESSION['theme'] = $a->user['theme'];
		$_SESSION['page_flags'] = $a->user['page-flags'];
		if(strlen($a->user['timezone']))
			date_default_timezone_set($a->user['timezone']);

		$_SESSION['my_url'] = $a->get_baseurl() . '/profile/' . $a->user['nickname'];

		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 1 LIMIT 1",
			intval($_SESSION['uid']));
		if(count($r)) {
			$a->contact = $r[0];
			$a->cid = $r[0]['id'];
			$_SESSION['cid'] = $a->cid;

		}
	}
}
else {

	if(isset($_SESSION)) {
		unset($_SESSION['authenticated']);
		unset($_SESSION['uid']);
		unset($_SESSION['visitor_id']);
		unset($_SESSION['administrator']);
		unset($_SESSION['cid']);
		unset($_SESSION['theme']);
		unset($_SESSION['my_url']);
		unset($_SESSION['page_flags']);
	}

	if(x($_POST,'password'))
		$encrypted = hash('whirlpool',trim($_POST['password']));

	if((x($_POST,'auth-params')) && $_POST['auth-params'] === 'login') {

		// process login request

		$r = q("SELECT * FROM `user` 
			WHERE ( `email` = '%s' OR `nickname` = '%s' ) AND `password` = '%s' AND `blocked` = 0 AND `verified` = 1 LIMIT 1",
			dbesc(trim($_POST['login-name'])),
			dbesc(trim($_POST['login-name'])),
			dbesc($encrypted));
		if(($r === false) || (! count($r))) {
			notice( t('Login failed.') . EOL );
			goaway($a->get_baseurl());
  		}
		$_SESSION['uid'] = $r[0]['uid'];
		$_SESSION['theme'] = $r[0]['theme'];
		$_SESSION['authenticated'] = 1;
		$_SESSION['page_flags'] = $r[0]['page-flags'];
		$_SESSION['my_url'] = $a->get_baseurl() . '/profile/' . $r[0]['nickname'];

		notice( t("Welcome back ") . $r[0]['username'] . EOL);
		$a->user = $r[0];
		if(strlen($a->user['timezone']))
			date_default_timezone_set($a->user['timezone']);

		$r = q("SELECT * FROM `contact` WHERE `uid` = %s AND `self` = 1 LIMIT 1",
			intval($_SESSION['uid']));
		if(count($r)) {
			$a->contact = $r[0];
			$a->cid = $r[0]['id'];
			$_SESSION['cid'] = $a->cid;
		}
		if(($a->module !== 'home') && isset($_SESSION['return_url']))
			goaway($a->get_baseurl() . '/' . $_SESSION['return_url']);
	}
}

// Returns an array of group id's this contact is a member of.
// This array will only contain group id's related to the uid of this
// DFRN contact. They are *not* neccessarily unique across the entire site. 


if(! function_exists('init_groups_visitor')) {
function init_groups_visitor($contact_id) {
	$groups = array();
	$r = q("SELECT `gid` FROM `group_member` 
		WHERE `contact-id` = %d ",
		intval($contact_id)
	);
	if(count($r)) {
		foreach($r as $rr)
			$groups[] = $rr['gid'];
	}
	return $groups;
}}


