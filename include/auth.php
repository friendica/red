<?php


function nuke_session() {
	unset($_SESSION['authenticated']);
	unset($_SESSION['uid']);
	unset($_SESSION['visitor_id']);
	unset($_SESSION['administrator']);
	unset($_SESSION['cid']);
	unset($_SESSION['theme']);
	unset($_SESSION['page_flags']);
}


// login/logout 




if((isset($_SESSION)) && (x($_SESSION,'authenticated')) && ((! (x($_POST,'auth-params'))) || ($_POST['auth-params'] !== 'login'))) {

	if(((x($_POST,'auth-params')) && ($_POST['auth-params'] === 'logout')) || ($a->module === 'logout')) {
	
		// process logout request

		nuke_session();
		notice( t('Logged out.') . EOL);
		goaway($a->get_baseurl());
	}

	if(x($_SESSION,'uid')) {

		// already logged in user returning

		$check = get_config('system','paranoia');
		// extra paranoia - if the IP changed, log them out
		if($check && ($_SESSION['addr'] != $_SERVER['REMOTE_ADDR'])) {
			nuke_session();
			goaway($a->get_baseurl());
		}

		$r = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
			intval($_SESSION['uid'])
		);

		if(! count($r)) {
			nuke_session();
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
		header('X-Account-Management-Status: active; name="' . $a->user['username'] . '"; id="' . $a->user['nickname'] .'"');
	}
}
else {

	if(isset($_SESSION)) {
		nuke_session();
	}

	if((x($_POST,'password')) && strlen($_POST['password']))
		$encrypted = hash('whirlpool',trim($_POST['password']));
	else {
		if((x($_POST,'openid_url')) && strlen($_POST['openid_url'])) {

			$noid = get_config('system','no_openid');

			$openid_url = trim($_POST['openid_url']);

			// validate_url alters the calling parameter

			$temp_string = $openid_url;

			// if it's an email address or doesn't resolve to a URL, fail.

			if(($noid) || (strpos($temp_string,'@')) || (! validate_url($temp_string))) {
				$a = get_app();
				notice( t('Login failed.') . EOL);
				goaway($a->get_baseurl());
				// NOTREACHED
			}

			// Otherwise it's probably an openid.

			require_once('library/openid.php');
			$openid = new LightOpenID;
			$openid->identity = $openid_url;
			$_SESSION['openid'] = $openid_url;
			$a = get_app();
			$openid->returnUrl = $a->get_baseurl() . '/openid'; 

			$r = q("SELECT `uid` FROM `user` WHERE `openid` = '%s' LIMIT 1",
				dbesc($openid_url)
			);
			if(count($r)) { 
				// existing account
				goaway($openid->authUrl());
				// NOTREACHED	
			}
			else {
				if($a->config['register_policy'] == REGISTER_CLOSED) {
					$a = get_app();
					notice( t('Login failed.') . EOL);
					goaway($a->get_baseurl());
					// NOTREACHED
				}
				// new account
				$_SESSION['register'] = 1;
				$openid->required = array('namePerson/friendly', 'contact/email', 'namePerson');
				$openid->optional = array('namePerson/first','media/image/aspect11','media/image/default');
				goaway($openid->authUrl());
				// NOTREACHED	
			}
		}
	}
	if((x($_POST,'auth-params')) && $_POST['auth-params'] === 'login') {


		$addon_auth = array(
			'name' => trim($_POST['openid_url']), 
			'password' => trim($_POST['password']),
			'authenticated' => 0
		);

		/**
		 *
		 * A plugin indicates successful login by setting 'authenticated' to non-zero value
		 * Plugins should never set 'authenticated' except to indicate success - as hooks may be chained
		 * and later plugins should not interfere with an earlier one that succeeded.
		 *
		 */

		call_hooks('authenticate', $addon_auth);

		if(! $addon_auth['authenticated']) {
			// process login request

			$r = q("SELECT * FROM `user` WHERE ( `email` = '%s' OR `nickname` = '%s' ) 
				AND `password` = '%s' AND `blocked` = 0 AND `verified` = 1 LIMIT 1",
				dbesc(trim($_POST['openid_url'])),
				dbesc(trim($_POST['openid_url'])),
				dbesc($encrypted)
			);
			if(($r === false) || (! count($r))) {
				notice( t('Login failed.') . EOL );
				goaway($a->get_baseurl());
  			}
		}

		$_SESSION['uid'] = $r[0]['uid'];
		$_SESSION['theme'] = $r[0]['theme'];
		$_SESSION['authenticated'] = 1;
		$_SESSION['page_flags'] = $r[0]['page-flags'];
		$_SESSION['my_url'] = $a->get_baseurl() . '/profile/' . $r[0]['nickname'];
		$_SESSION['addr'] = $_SERVER['REMOTE_ADDR'];

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
		q("UPDATE `user` SET `login_date` = '%s' WHERE `uid` = %d LIMIT 1",
			dbesc(datetime_convert()),
			intval($_SESSION['uid'])
		);

		call_hooks('logged_in', $a->user);

		header('X-Account-Management-Status: active; name="' . $a->user['username'] . '"; id="' . $a->user['nickname'] .'"');
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


