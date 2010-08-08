<?php


function settings_init(&$a) {

	if(! local_user()) {
		notice("Permission denied." . EOL);
		$a->error = 404;
		return;
	}
	require_once("mod/profile.php");
	profile_load($a,$a->user['nickname']);
}


function settings_post(&$a) {


	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}
	if(count($a->user) && x($a->user,'uid') && $a->user['uid'] != $_SESSION['uid']) {
		notice( t('Permission denied.') . EOL);
		return;
	}
	if((x($_POST,'password')) || (x($_POST,'confirm'))) {

		$newpass = trim($_POST['password']);
		$confirm = trim($_POST['confirm']);

		$err = false;
		if($newpass != $confirm ) {
			notice( t('Passwords do not match. Password unchanged.') . EOL);
			$err = true;
		}

		if((! x($newpass)) || (! x($confirm))) {
			notice( t('Empty passwords are not allowed. Password unchanged.') . EOL);
			$err = true;
		}

		if(! $err) {
			$password = hash('whirlpool',$newpass);
			$r = q("UPDATE `user` SET `password` = '%s' WHERE `uid` = %d LIMIT 1",
				dbesc($password),
				intval($_SESSION['uid']));
			if($r)
				notice( t('Password changed.') . EOL);
			else
				notice( t('Password update failed. Please try again.') . EOL);
		}
	}

	$username = notags(trim($_POST['username']));
	$email = notags(trim($_POST['email']));
	$timezone = notags(trim($_POST['timezone']));

	$username_changed = false;
	$email_changed = false;
	$zone_changed = false;
	$err = '';

	if($username != $a->user['username']) {
		$username_changed = true;
        	if(strlen($username) > 40)
                	$err .= t(' Please use a shorter name.');
        	if(strlen($username) < 3)
                	$err .= t(' Name too short.');
	}
	if($email != $a->user['email']) {
		$email_changed = true;
        	if(!eregi('[A-Za-z0-9._%-]+@[A-Za-z0-9._%-]+\.[A-Za-z]{2,6}',$email))
                	$err .= t(' Not valid email.');
        	$r = q("SELECT `uid` FROM `user`
                	WHERE `email` = '%s' LIMIT 1",
                	dbesc($email)
                	);
	        if($r !== NULL && count($r))
        	        $err .= t(' This email address is already registered.');
	}

        if(strlen($err)) {
                notice($err . EOL);
                return;
        }
	if($timezone != $a->user['timezone']) {
		$zone_changed = true;
		if(strlen($timezone))
			date_default_timezone_set($timezone);
	}

	$str_group_allow = '';
	$group_allow = $_POST['group_allow'];
	if(is_array($group_allow)) {
		array_walk($group_allow,'sanitise_acl');
		$str_group_allow = implode('',$group_allow);
	}

	$str_contact_allow = '';
	$contact_allow = $_POST['contact_allow'];
	if(is_array($contact_allow)) {
		array_walk($contact_allow,'sanitise_acl');
		$str_contact_allow = implode('',$contact_allow);
	}

	$str_group_deny = '';
	$group_deny = $_POST['group_deny'];
	if(is_array($group_deny)) {
		array_walk($group_deny,'sanitise_acl');
		$str_group_deny = implode('',$group_deny);
	}

	$str_contact_deny = '';
	$contact_deny = $_POST['contact_deny'];
	if(is_array($contact_deny)) {
		array_walk($contact_deny,'sanitise_acl');
		$str_contact_deny = implode('',$contact_deny);
	}



	$r = q("UPDATE `user` SET `username` = '%s', `email` = '%s', `timezone` = '%s',  `allow_cid` = '%s', `allow_gid` = '%s', `deny_cid` = '%s', `deny_gid` = '%s' WHERE `uid` = %d LIMIT 1",
			dbesc($username),
			dbesc($email),
			dbesc($timezone),
			dbesc($str_contact_allow),
			dbesc($str_group_allow),
			dbesc($str_contact_deny),
			dbesc($str_group_deny),
			intval($_SESSION['uid'])
	);
	if($r)
		notice( t('Settings updated.') . EOL);

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

	if(! local_user()) {
		notice( t('Permission denied.') . EOL );
		return;
	}

	require_once('view/acl_selectors.php');

	$username = $a->user['username'];
	$email    = $a->user['email'];
	$nickname = $a->user['nickname'];
	$timezone = $a->user['timezone'];



	$nickname_block = file_get_contents("view/settings_nick_set.tpl");
	

	$nickname_subdir = '';
	if(strlen($a->get_path())) {
		$subdir_tpl = file_get_contents('view/settings_nick_subdir.tpl');
		$nickname_subdir = replace_macros($subdir_tpl, array(
			'$baseurl' => $a->get_baseurl(),
			'$nickname' => $nickname,
			'$hostname' => $a->get_hostname()
		));
	}


	$nickname_block = replace_macros($nickname_block,array(
		'$nickname' => $nickname,
		'$uid' => $_SESSION['uid'],
		'$subdir' => $nickname_subdir,
		'$basepath' => $a->get_hostname(),
		'$baseurl' => $a->get_baseurl()));	

	$o = file_get_contents('view/settings.tpl');

	$o = replace_macros($o,array(
		'$baseurl' => $a->get_baseurl(),
		'$uid' => $_SESSION['uid'],
		'$username' => $username,
		'$email' => $email,
		'$nickname_block' => $nickname_block,
		'$timezone' => $timezone,
		'$zoneselect' => select_timezone($timezone),
		'$permissions' => t('Default Post Permissions'),
		'$aclselect' => populate_acl($a->user)
	));

	return $o;

}}