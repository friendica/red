<?php
/**
 * @file include/security.php
 *
 * Some security related functions.
 */

/**
 * @param int $user_record The account_id
 * @param bool $login_initial default false
 * @param bool $interactive default false
 * @param bool $return
 * @param bool $update_lastlog
 */
function authenticate_success($user_record, $login_initial = false, $interactive = false, $return = false, $update_lastlog = false) {

	$a = get_app();

	$_SESSION['addr'] = $_SERVER['REMOTE_ADDR'];

	if(x($user_record, 'account_id')) {
		$a->account = $user_record;
		$_SESSION['account_id'] = $user_record['account_id'];
		$_SESSION['authenticated'] = 1;
		
		if($login_initial || $update_lastlog) {
			q("update account set account_lastlog = '%s' where account_id = %d",
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

	if($login_initial) {

		call_hooks('logged_in', $user_record);

		// might want to log success here
	}

	if($return || x($_SESSION, 'workflow')) {
		unset($_SESSION['workflow']);
		return;
	}

	if(($a->module !== 'home') && x($_SESSION,'login_return_url') && strlen($_SESSION['login_return_url'])) {
		$return_url = $_SESSION['login_return_url'];

		// don't let members get redirected to a raw ajax page update - this can happen
		// if DHCP changes the IP address at an unfortunate time and paranoia is turned on
		if(strstr($return_url,'update_'))
			$return_url = '';

		unset($_SESSION['login_return_url']);
		goaway($a->get_baseurl() . '/' . $return_url);
	}

	/* This account has never created a channel. Send them to new_channel by default */

	if($a->module === 'login') {
		$r = q("select count(channel_id) as total from channel where channel_account_id = %d and not ( channel_pageflags & %d)>0",
			intval($a->account['account_id']),
			intval(PAGE_REMOVED)
		);
		if(($r) && (! $r[0]['total']))
			goaway(z_root() . '/new_channel');
	}

	/* else just return */
}

/**
 * @brief Change to another channel with current logged-in account.
 *
 * @param int $change_channel The channel_id of the channel you want to change to
 *
 * @return bool|array false or channel record of the new channel
 */
function change_channel($change_channel) {

	$ret = false;

	if($change_channel) {
		$r = q("select channel.*, xchan.* from channel left join xchan on channel.channel_hash = xchan.xchan_hash where channel_id = %d and channel_account_id = %d and not ( channel_pageflags & %d)>0 limit 1",
			intval($change_channel),
			intval(get_account_id()),
			intval(PAGE_REMOVED)
		);

		// It's not there.  Is this an administrator, and is this the sys channel?
		if (is_developer()) {
			if (! $r) {
				if (is_site_admin()) {
					$r = q("select channel.*, xchan.* from channel left join xchan on channel.channel_hash = xchan.xchan_hash where channel_id = %d and ( channel_pageflags & %d) and not (channel_pageflags & %d )>0 limit 1",
						intval($change_channel),
						intval(PAGE_SYSTEM),
						intval(PAGE_REMOVED)
					);
				}
			}
		}

		if($r) {
			$hash = $r[0]['channel_hash'];
			$_SESSION['uid'] = intval($r[0]['channel_id']);
			get_app()->set_channel($r[0]);
			$_SESSION['theme'] = $r[0]['channel_theme'];
			$_SESSION['mobile_theme'] = get_pconfig(local_channel(),'system', 'mobile_theme');
			date_default_timezone_set($r[0]['channel_timezone']);
			$ret = $r[0];
		}
		$x = q("select * from xchan where xchan_hash = '%s' limit 1", 
			dbesc($hash)
		);
		if($x) {
			$_SESSION['my_url'] = $x[0]['xchan_url'];
			$_SESSION['my_address'] = $r[0]['channel_address'] . '@' . substr(get_app()->get_baseurl(), strpos(get_app()->get_baseurl(), '://') + 3);

			get_app()->set_observer($x[0]);
			get_app()->set_perms(get_all_perms(local_channel(), $hash));
		}
		if(! is_dir('store/' . $r[0]['channel_address']))
			@os_mkdir('store/' . $r[0]['channel_address'], STORAGE_DEFAULT_PERMISSIONS,true);
	}

	return $ret;
}

/**
 * @brief Creates an addiontal SQL where statement to check permissions.
 *
 * @param int $owner_id
 * @param bool $remote_verified default false, not used at all
 * @param string $groups this param is not used at all
 *
 * @return string additional SQL where statement
 */
function permissions_sql($owner_id, $remote_verified = false, $groups = null) {

	if(defined('STATUSNET_PRIVACY_COMPATIBILITY'))
		return '';

	$local_channel = local_channel();
	$remote_channel = remote_channel();

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

	if(($local_channel) && ($local_channel == $owner_id)) {
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
		$observer = get_observer_hash();
		if($observer) {
			$groups = init_groups_visitor($observer);

			$gs = '<<>>'; // should be impossible to match

			if(is_array($groups) && count($groups)) {
				foreach($groups as $g)
					$gs .= '|<' . $g . '>';
			} 
			$regexop = db_getfunc('REGEXP');
			$sql = sprintf(
				" AND ( NOT (deny_cid like '%s' OR deny_gid $regexop '%s')
				  AND ( allow_cid like '%s' OR allow_gid $regexop '%s' OR ( allow_cid = '' AND allow_gid = '') )
				  )
				",
				dbesc(protect_sprintf( '%<' . $observer . '>%')),
				dbesc($gs),
				dbesc(protect_sprintf( '%<' . $observer . '>%')),
				dbesc($gs)
			);
		}
	}

	return $sql;
}

/**
 * @brief Creates an addiontal SQL where statement to check permissions for an item.
 *
 * @param int $owner_id
 * @param bool $remote_verified default false, not used at all
 * @param string $groups this param is not used at all
 *
 * @return string additional SQL where statement
 */
function item_permissions_sql($owner_id, $remote_verified = false, $groups = null) {

	if(defined('STATUSNET_PRIVACY_COMPATIBILITY'))
		return '';

	$local_channel = local_channel();
	$remote_channel = remote_channel();

	/**
	 * Construct permissions
	 *
	 * default permissions - anonymous user
	 */

	$sql = " AND item_private=0 ";

	/**
	 * Profile owner - everything is visible
	 */

	if(($local_channel) && ($local_channel == $owner_id)) {
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
		$observer = get_observer_hash();

		if($observer) {
			$groups = init_groups_visitor($observer);

			$gs = '<<>>'; // should be impossible to match

			if(is_array($groups) && count($groups)) {
				foreach($groups as $g)
					$gs .= '|<' . $g . '>';
			}
			$regexop = db_getfunc('REGEXP');
			$sql = sprintf(
				" AND ( NOT (deny_cid like '%s' OR deny_gid $regexop '%s')
				  AND ( allow_cid like '%s' OR allow_gid $regexop '%s' OR ( allow_cid = '' AND allow_gid = '') )
				  )
				",
				dbesc(protect_sprintf( '%<' . $observer . '>%')),
				dbesc($gs),
				dbesc(protect_sprintf( '%<' . $observer . '>%')),
				dbesc($gs)
			);
		}
	}

	return $sql;
}

/**
 * @param string $observer_hash
 *
 * @return string additional SQL where statement
 */
function public_permissions_sql($observer_hash) {

	//$observer = get_app()->get_observer();
	$groups = init_groups_visitor($observer_hash);

	$gs = '<<>>'; // should be impossible to match

	if(is_array($groups) && count($groups)) {
		foreach($groups as $g)
			$gs .= '|<' . $g . '>';
	}
	$sql = '';
	if($observer_hash) {
		$regexop = db_getfunc('REGEXP');
		$sql = sprintf(
			" OR (( NOT (deny_cid like '%s' OR deny_gid $regexop '%s')
			  AND ( allow_cid like '%s' OR allow_gid $regexop '%s' OR ( allow_cid = '' AND allow_gid = '') )
			  ))
			",
			dbesc(protect_sprintf( '%<' . $observer_hash . '>%')),
			dbesc($gs),
			dbesc(protect_sprintf( '%<' . $observer_hash . '>%')),
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
	$r = q("SELECT hash FROM `groups` left join group_member on groups.id = group_member.gid WHERE xchan = '%s' ",
		dbesc($contact_id)
	);
	if(count($r)) {
		foreach($r as $rr)
			$groups[] = $rr['hash'];
	}
	return $groups;
}}



// This is used to determine which uid have posts which are visible to the logged in user (from the API) for the 
// public_timeline, and we can use this in a community page by making
// $perms = (PERMS_NETWORK|PERMS_PUBLIC) unless logged in. 
// Collect uids of everybody on this site who has opened their posts to everybody on this site (or greater visibility)
// We always include yourself if logged in because you can always see your own posts
// resolving granular permissions for the observer against every person and every post on the site
// will likely be too expensive. 
// Returns a string list of comma separated channel_ids suitable for direct inclusion in a SQL query

function stream_perms_api_uids($perms = NULL ) {
	$perms = is_null($perms) ? (PERMS_SITE|PERMS_NETWORK|PERMS_PUBLIC) : $perms;

	$ret = array();
	if(local_channel())
		$ret[] = local_channel();
	$r = q("select channel_id from channel where channel_r_stream > 0 and (channel_r_stream & %d)>0 and not (channel_pageflags & %d)>0",
		intval($perms),
		intval(PAGE_ADULT|PAGE_CENSORED|PAGE_SYSTEM|PAGE_REMOVED)
	);
	if($r) {
		foreach($r as $rr)
			if(! in_array($rr['channel_id'], $ret))
				$ret[] = $rr['channel_id']; 
	}

	$str = '';
	if($ret) {
		foreach($ret as $rr) {
			if($str)
				$str .= ',';
			$str .= intval($rr); 
		}
	}
	logger('stream_perms_api_uids: ' . $str, LOGGER_DEBUG);

	return $str;
}

function stream_perms_xchans($perms = NULL ) {
	$perms = is_null($perms) ? (PERMS_SITE|PERMS_NETWORK|PERMS_PUBLIC) : $perms;

	$ret = array();
	if(local_channel())
		$ret[] = get_observer_hash();

	$r = q("select channel_hash from channel where channel_r_stream > 0 and (channel_r_stream & %d)>0 and not (channel_pageflags & %d)>0",
		intval($perms),
		intval(PAGE_ADULT|PAGE_CENSORED|PAGE_SYSTEM|PAGE_REMOVED)
	);
	if($r) {
		foreach($r as $rr)
			if(! in_array($rr['channel_hash'], $ret))
				$ret[] = $rr['channel_hash']; 
	}

	$str = '';
	if($ret) {
		foreach($ret as $rr) {
			if($str)
				$str .= ',';
			$str .= "'" . dbesc($rr) . "'"; 
		}
	}
	logger('stream_perms_xchans: ' . $str, LOGGER_DEBUG);

	return $str;
}
