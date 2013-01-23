<?php

function authenticate_success($user_record, $login_initial = false, $interactive = false,$return = false,$update_lastlog = false) {

	$a = get_app();

	$_SESSION['addr'] = $_SERVER['REMOTE_ADDR'];

	if(x($user_record,'account_id')) {
		$a->account = $user_record;
		$_SESSION['account_id'] = $user_record['account_id'];
		$_SESSION['authenticated'] = 1;
		
		if($login_initial || $update_lastlog) {
			q("update account set account_lastlog = '%s' where account_id = %d limit 1",
				dbesc(datetime_convert()),
				intval($_SESSION['account_id'])
			);
			$a->account['account_lastlog'] = datetime_convert();
			call_hooks('logged_in', $a->account);

		}

		$uid_to_load = (((x($_SESSION,'uid')) && (intval($_SESSION['uid']))) 
			? intval($_SESSION['uid']) 
			: intval($a->account['account_default_channel'])
		);

		if($uid_to_load) {
			change_channel($uid_to_load);
		}

	}
	else {
		$_SESSION['uid'] = $user_record['uid'];
		$_SESSION['theme'] = $user_record['theme'];
		$_SESSION['authenticated'] = 1;
		$_SESSION['page_flags'] = $user_record['page-flags'];
		$_SESSION['my_url'] = $a->get_baseurl() . '/channel/' . $user_record['nickname'];
		$_SESSION['my_address'] = $user_record['nickname'] . '@' . substr($a->get_baseurl(),strpos($a->get_baseurl(),'://')+3);

		$a->user = $user_record;

		if($interactive) {
			if($a->user['login_date'] === '0000-00-00 00:00:00') {
				$_SESSION['return_url'] = 'profile_photo/new';
				$a->module = 'profile_photo';
				info( t("Welcome ") . $a->user['username'] . EOL);
				info( t('Please upload a profile photo.') . EOL);
			}
			else
				info( t("Welcome back ") . $a->user['username'] . EOL);
		}

		$member_since = strtotime($a->user['register_date']);
		if(time() < ($member_since + ( 60 * 60 * 24 * 14)))
			$_SESSION['new_member'] = true;
		else
			$_SESSION['new_member'] = false;
		if(strlen($a->user['timezone'])) {
			date_default_timezone_set($a->user['timezone']);
			$a->timezone = $a->user['timezone'];
		}

		$master_record = $a->user;	

		if((x($_SESSION,'submanage')) && intval($_SESSION['submanage'])) {
			$r = q("select * from user where uid = %d limit 1",
				intval($_SESSION['submanage'])
			);
			if(count($r))
				$master_record = $r[0];
		}

		$r = q("SELECT `uid`,`username`,`nickname` FROM `user` WHERE `password` = '%s' AND `email` = '%s'",
			dbesc($master_record['password']),
			dbesc($master_record['email'])
		);
		if($r && count($r))
			$a->identities = $r;
		else
			$a->identities = array();

		$r = q("select `user`.`uid`, `user`.`username`, `user`.`nickname` 
			from manage left join user on manage.mid = user.uid 
			where `manage`.`uid` = %d",
			intval($master_record['uid'])
		);
		if($r && count($r))
			$a->identities = array_merge($a->identities,$r);

		if($login_initial)
			logger('auth_identities: ' . print_r($a->identities,true), LOGGER_DEBUG);

		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 1 LIMIT 1",
			intval($_SESSION['uid']));
		if(count($r)) {
			$a->contact = $r[0];
			$a->cid = $r[0]['id'];
			$_SESSION['cid'] = $a->cid;
		}

		header('X-Account-Management-Status: active; name="' . $a->user['username'] . '"; id="' . $a->user['nickname'] .'"');

		if($login_initial) {
			$l = get_browser_language();

			q("UPDATE `user` SET `login_date` = '%s', `language` = '%s' WHERE `uid` = %d LIMIT 1",
				dbesc(datetime_convert()),
				dbesc($l),
				intval($_SESSION['uid'])
			);


		}
	}

	if($login_initial)
		call_hooks('logged_in', $user_record);
	

	if($return || x($_SESSION,'workflow')) {
		unset($_SESSION['workflow']);
		return;
	}

	if(($a->module !== 'home') && x($_SESSION,'login_return_url') && strlen($_SESSION['login_return_url'])) {
		$return_url = $_SESSION['login_return_url'];
		unset($_SESSION['login_return_url']);
		goaway($a->get_baseurl() . '/' . $return_url);
	}

}


function change_channel($change_channel) {

	$ret = false;

	if($change_channel) {
		$r = q("select channel.*, xchan.* from channel left join xchan on channel.channel_hash = xchan.xchan_hash where channel_id = %d and channel_account_id = %d limit 1",
			intval($change_channel),
			intval(get_account_id())
		);
		if($r) {
			$hash = $r[0]['channel_hash'];
			$_SESSION['uid'] = intval($r[0]['channel_id']);
			get_app()->set_channel($r[0]);
			$_SESSION['theme'] = $r[0]['channel_theme'];
			date_default_timezone_set($r[0]['channel_timezone']);
			$ret = $r[0];
		}
		$x = q("select * from xchan where xchan_hash = '%s' limit 1", 
			dbesc($hash)
		);
		if($x) {
			$_SESSION['my_url'] = $x[0]['xchan_url'];
			$_SESSION['my_address'] = $x[0]['xchan_addr'];

			get_app()->set_observer($x[0]);
			get_app()->set_perms(get_all_perms(local_user(),$hash));
		}
	}

	return $ret;

}

function permissions_sql($owner_id,$remote_verified = false,$groups = null) {

	$local_user = local_user();
	$remote_user = remote_user();

	/**
	 * Construct permissions
	 *
	 * default permissions - anonymous user
	 */

	$sql = " AND allow_cid = '' 
			 AND allow_gid = '' 
			 AND deny_cid  = '' 
			 AND deny_gid  = ''  
			
	";

	/**
	 * Profile owner - everything is visible
	 */

	if(($local_user) && ($local_user == $owner_id)) {
		$sql = ''; 
	}

	/**
	 * Authenticated visitor. Unless pre-verified, 
	 * check that the contact belongs to this $owner_id
	 * and load the groups the visitor belongs to.
	 * If pre-verified, the caller is expected to have already
	 * done this and passed the groups into this function.
	 */


	else {
		$observer = get_app()->get_observer();
		$groups = init_groups_visitor($remote_user);

		$gs = '<<>>'; // should be impossible to match

		if(is_array($groups) && count($groups)) {
			foreach($groups as $g)
				$gs .= '|<' . $g . '>';
		} 
		$sql = sprintf(
			" AND ( NOT (deny_cid like '<%s>' OR deny_gid REGEXP '%s')
			  AND ( allow_cid like '<%s>' OR allow_gid REGEXP '%s' OR ( allow_cid = '' AND allow_gid = '') )
			  )
			",
			dbesc(protect_sprintf( '%' . $remote_user . '%')),
			dbesc($gs),
			dbesc(protect_sprintf( '%' . $remote_user . '%')),
			dbesc($gs)
		);
	}
	return $sql;
}

function item_permissions_sql($owner_id,$remote_verified = false,$groups = null) {

	$local_user = local_user();
	$remote_user = remote_user();

	/**
	 * Construct permissions
	 *
	 * default permissions - anonymous user
	 */

	$sql = " AND not item_private ";
			

	/**
	 * Profile owner - everything is visible
	 */

	if(($local_user) && ($local_user == $owner_id)) {
		$sql = ''; 
	}

	/**
	 * Authenticated visitor. Unless pre-verified, 
	 * check that the contact belongs to this $owner_id
	 * and load the groups the visitor belongs to.
	 * If pre-verified, the caller is expected to have already
	 * done this and passed the groups into this function.
	 */


	else {
		$observer = get_app()->get_observer();
		$groups = init_groups_visitor($remote_user);

		$gs = '<<>>'; // should be impossible to match

		if(is_array($groups) && count($groups)) {
			foreach($groups as $g)
				$gs .= '|<' . $g . '>';
		} 
		$sql = sprintf(
			" AND ( NOT (deny_cid like '<%s>' OR deny_gid REGEXP '%s')
			  AND ( allow_cid like '<%s>' OR allow_gid REGEXP '%s' OR ( allow_cid = '' AND allow_gid = '') )
			  )
			",
			dbesc(protect_sprintf( '%' . $remote_user . '%')),
			dbesc($gs),
			dbesc(protect_sprintf( '%' . $remote_user . '%')),
			dbesc($gs)
		);
	}
	return $sql;
}



/*
 * Functions used to protect against Cross-Site Request Forgery
 * The security token has to base on at least one value that an attacker can't know - here it's the session ID and the private key.
 * In this implementation, a security token is reusable (if the user submits a form, goes back and resubmits the form, maybe with small changes;
 * or if the security token is used for ajax-calls that happen several times), but only valid for a certain amout of time (3hours).
 * The "typename" seperates the security tokens of different types of forms. This could be relevant in the following case:
 *    A security token is used to protekt a link from CSRF (e.g. the "delete this profile"-link).
 *    If the new page contains by any chance external elements, then the used security token is exposed by the referrer.
 *    Actually, important actions should not be triggered by Links / GET-Requests at all, but somethimes they still are,
 *    so this mechanism brings in some damage control (the attacker would be able to forge a request to a form of this type, but not to forms of other types).
 */ 
function get_form_security_token($typename = '') {
	$a = get_app();
	
	$timestamp = time();
	$sec_hash = hash('whirlpool', $a->user['guid'] . $a->user['prvkey'] . session_id() . $timestamp . $typename);
	
	return $timestamp . '.' . $sec_hash;
}

function check_form_security_token($typename = '', $formname = 'form_security_token') {
	if (!x($_REQUEST, $formname)) return false;
	$hash = $_REQUEST[$formname];
	
	$max_livetime = 10800; // 3 hours
	
	$a = get_app();
	
	$x = explode('.', $hash);
	if (time() > (IntVal($x[0]) + $max_livetime)) return false;
	
	$sec_hash = hash('whirlpool', $a->user['guid'] . $a->user['prvkey'] . session_id() . $x[0] . $typename);
	
	return ($sec_hash == $x[1]);
}

function check_form_security_std_err_msg() {
	return t('The form security token was not correct. This probably happened because the form has been opened for too long (>3 hours) before submitting it.') . EOL;
}
function check_form_security_token_redirectOnErr($err_redirect, $typename = '', $formname = 'form_security_token') {
	if (!check_form_security_token($typename, $formname)) {
		$a = get_app();
		logger('check_form_security_token failed: user ' . $a->user['guid'] . ' - form element ' . $typename);
		logger('check_form_security_token failed: _REQUEST data: ' . print_r($_REQUEST, true), LOGGER_DATA);
		notice( check_form_security_std_err_msg() );
		goaway($a->get_baseurl() . $err_redirect );
	}
}
function check_form_security_token_ForbiddenOnErr($typename = '', $formname = 'form_security_token') {
	if (!check_form_security_token($typename, $formname)) {
	    $a = get_app();
		logger('check_form_security_token failed: user ' . $a->user['guid'] . ' - form element ' . $typename);
		logger('check_form_security_token failed: _REQUEST data: ' . print_r($_REQUEST, true), LOGGER_DATA);
		header('HTTP/1.1 403 Forbidden');
		killme();
	}
}

// Returns an array of group id's this contact is a member of.
// This array will only contain group id's related to the uid of this
// DFRN contact. They are *not* neccessarily unique across the entire site. 


if(! function_exists('init_groups_visitor')) {
function init_groups_visitor($contact_id) {
	$groups = array();
	$r = q("SELECT gid FROM group_member WHERE xchan = '%s' ",
		dbesc($contact_id)
	);
	if(count($r)) {
		foreach($r as $rr)
			$groups[] = $rr['gid'];
	}
	return $groups;
}}





// This is used to determine which uid have posts which are visible to the logged in user (from the API) for the 
// public_timeline, and we can use this in a community page by making $perms_min = PERMS_NETWORK unless logged in. 
// Collect uids of everybody on this site who has opened their posts to everybody on this site (or greater visibility)
// We always include yourself if logged in because you can always see your own posts
// resolving granular permissions for the observer against every person and every post on the site
// will likely be too expensive. 
// Returns a string list of comma separated channel_ids suitable for direct inclusion in a SQL query

function stream_perms_api_uids($perms_min = PERMS_SITE) {
	$ret = array();
	if(local_user())
		$ret[] = local_user();
	$r = q("select channel_id from channel where channel_r_stream <= %d",
		intval($perms_min)
	);
	if($r)
		foreach($r as $rr)
			if(! in_array($rr['channel_id'],$ret))
				$ret[] = $rr['channel_id']; 

	$str = '';
	if($ret)
		foreach($ret as $rr) {
			if($str)
				$str .= ',';
			$str .= intval($rr); 
		}
	return $str;
}

