<?php


function settings_init(&$a) {

	if((! x($_SESSION,'authenticated')) && (x($_SESSION,'uid'))) {
		$_SESSION['sysmsg'] .= "Permission denied." . EOL;
		$a->error = 404;
		return;
	}
	require_once("mod/profile.php");
	profile_load($a,$_SESSION['uid']);
}


function settings_post(&$a) {

	if((! x($_SESSION['authenticated'])) && (! (x($_SESSION,'uid')))) {
		$_SESSION['sysmsg'] .= "Permission denied." . EOL;
		return;
	}
	if(count($a->user) && x($a->user,'uid') && $a->user['uid'] != $_SESSION['uid']) {
		$_SESSION['sysmsg'] .= "Permission denied." . EOL;
		return;
	}
	if((x($_POST,'password')) || (x($_POST,'confirm'))) {

		$newpass = trim($_POST['password']);
		$confirm = trim($_POST['confirm']);

		$err = false;
		if($newpass != $confirm ) {
			$_SESSION['sysmsg'] .= "Passwords do not match. Password unchanged." . EOL;
			$err = true;
		}

		if((! x($newpass)) || (! x($confirm))) {
			$_SESSION['sysmsg'] .= "Empty passwords are not allowed. Password unchanged." . EOL;
			$err = true;
		}

		if(! $err) {
			$password = hash('whirlpool',$newpass);
			$r = q("UPDATE `user` SET `password` = '%s' WHERE `uid` = %d LIMIT 1",
				dbesc($password),
				intval($_SESSION['uid']));
			if($r)
				$_SESSION['sysmsg'] .= "Password changed." . EOL;
			else
				$_SESSION['sysmsg'] .= "Password update failed. Please try again." . EOL;
		}
	}

	$username = notags(trim($_POST['username']));
	$email = notags(trim($_POST['email']));
	if(x($_POST,'nick'))
		$nick = notags(trim($_POST['nick']));
	$timezone = notags(trim($_POST['timezone']));

	$username_changed = false;
	$email_changed = false;
	$nick_changed = false;
	$zone_changed = false;
	$err = '';

	if($username != $a->user['username']) {
		$username_changed = true;
        	if(strlen($username) > 40)
                	$err .= " Please use a shorter name.";
        	if(strlen($username) < 3)
                	$err .= " Name too short.";
	}
	if($email != $a->user['email']) {
		$email_changed = true;
        	if(!eregi('[A-Za-z0-9._%-]+@[A-Za-z0-9._%-]+\.[A-Za-z]{2,6}',$email))
                	$err .= " Not valid email.";
        	$r = q("SELECT `uid` FROM `user`
                	WHERE `email` = '%s' LIMIT 1",
                	dbesc($email)
                	);
	        if($r !== NULL && count($r))
        	        $err .= " This email address is already registered." . EOL;
	}
	if((x($nick)) && ($nick != $a->user['nickname'])) {
		$nick_changed = true;
		if(! preg_match("/^[a-zA-Z][a-zA-Z0-9\-\_]*$/",$nick))
			$err .= " Nickname must start with a letter and contain only contain letters, numbers, dashes, and underscore.";
		$r = q("SELECT `uid` FROM `user`
                	WHERE `nickname` = '%s' LIMIT 1",
                	dbesc($nick)
                	);
	        if($r !== NULL && count($r))
        	        $err .= " Nickname is already registered. Try another." . EOL;
	}
	else
		$nick = $a->user['nickname'];

        if(strlen($err)) {
                $_SESSION['sysmsg'] .= $err . EOL;
                return;
        }
	if($timezone != $a->user['timezone']) {
		$zone_changed = true;
		if(strlen($timezone))
			date_default_timezone_set($timezone);
	}
	if($email_changed || $username_changed || $nick_changed || $zone_changed ) {
		$r = q("UPDATE `user` SET `username` = '%s', `email` = '%s', `nickname` = '%s', `timezone` = '%s'  WHERE `uid` = %d LIMIT 1",
			dbesc($username),
			dbesc($email),
			dbesc($nick),
			dbesc($timezone),
			intval($_SESSION['uid']));
		if($r)
			$_SESSION['sysmsg'] .= "Settings updated." . EOL;
	}
	if($email_changed && $a->config['register_policy'] == REGISTER_VERIFY) {

		// FIXME - set to un-verified, blocked and redirect to logout

	}

	// Refresh the content display with new data

	$r = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
		intval($_SESSION['uid']));
	if(count($r))
		$a->user = $r[0];
}
		

if(! function_exists('settings_content')) {
function settings_content(&$a) {

	if((! x($_SESSION['authenticated'])) && (! (x($_SESSION,'uid')))) {
		$_SESSION['sysmsg'] .= "Permission denied." . EOL;
		return;
	}


	$username = $a->user['username'];
	$email    = $a->user['email'];
	$nickname = $a->user['nickname'];
	$timezone = $a->user['timezone'];


	if(x($nickname))
		$nickname_block = file_get_contents("view/settings_nick_set.tpl");
	else
		$nickname_block = file_get_contents("view/settings_nick_unset.tpl");

	$nickname_block = replace_macros($nickname_block,array(
		'$nickname' => $nickname,
		'$baseurl' => $a->get_baseurl()));	

	$o = file_get_contents('view/settings.tpl');

	$o = replace_macros($o,array(
		'$baseurl' => $a->get_baseurl(),
		'$uid' => $_SESSION['uid'],
		'$username' => $username,
		'$email' => $email,
		'$nickname_block' => $nickname_block,
		'$timezone' => $timezone,
		'$zoneselect' => select_timezone($timezone)
		));

	return $o;

}}