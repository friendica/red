<?php

// login/logout 

if((x($_SESSION,'authenticated')) && (! ($_POST['auth-params'] == 'login'))) {
	if($_POST['auth-params'] == 'logout' || $a->module == "logout") {
		unset($_SESSION['authenticated']);
		unset($_SESSION['uid']);
		unset($_SESSION['visitor_id']);
		unset($_SESSION['administrator']);
		$_SESSION['sysmsg'] = "Logged out." . EOL;
		goaway($a->get_baseurl());
	}
	if(x($_SESSION,'uid')) {
		$r = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
			intval($_SESSION['uid']));
		if($r === NULL || (! count($r))) {
			goaway($a->get_baseurl());
		}
		$a->user = $r[0];
		if(strlen($a->user['timezone']))
			date_default_timezone_set($a->user['timezone']);

	}
}
else {
	unset($_SESSION['authenticated']);
	unset($_SESSION['uid']);
	unset($_SESSION['visitor_id']);
	unset($_SESSION['administrator']);
	$encrypted = hash('whirlpool',trim($_POST['password']));

	if((x($_POST,'auth-params')) && $_POST['auth-params'] == 'login') {
		$r = q("SELECT * FROM `user` 
			WHERE `email` = '%s' AND `password` = '%s' LIMIT 1",
			dbesc(trim($_POST['login-name'])),
			dbesc($encrypted));
		if(($r === false) || (! count($r))) {
			$_SESSION['sysmsg'] = 'Login failed.' . EOL ;
			goaway($a->get_baseurl());
  		}
		$_SESSION['uid'] = $r[0]['uid'];
		$_SESSION['admin'] = $r[0]['admin'];
		$_SESSION['authenticated'] = 1;
		if(x($r[0],'nickname'))
			$_SESSION['my_url'] = $a->get_baseurl() . '/profile/' . $r[0]['nickname'];
		else
			$_SESSION['my_url'] = $a->get_baseurl() . '/profile/' . $r[0]['uid'];

		$_SESSION['sysmsg'] = "Welcome back " . $r[0]['username'] . EOL;
		$a->user = $r[0];
		if(strlen($a->user['timezone']))
			date_default_timezone_set($a->user['timezone']);

	}
}

// Returns an array of group names this contact is a member of.
// Since contact-id's are unique and each "belongs" to a given user uid,
// this array will only contain group names related to the uid of this
// DFRN contact. They are *not* neccessarily unique across the entire site. 


if(! function_exists('init_groups_visitor')) {
function init_groups_visitor($contact_id) {
	$groups = array();
	$r = q("SELECT `group_member`.`gid`, `group`.`name` 
		FROM `group_member` LEFT JOIN `group` ON `group_member`.`gid` = `group`.`id` 
		WHERE `group_member`.`contact-id` = %d ",
		intval($contact_id)
	);
	if(count($r)) {
		foreach($r as $rr)
			$groups[] = $rr['name'];
	}
	return $groups;
}}


