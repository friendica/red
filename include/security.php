<?php

function authenticate_success($user_record, $login_initial = false, $interactive = false) {

	$a = get_app();

	$_SESSION['uid'] = $user_record['uid'];
	$_SESSION['theme'] = $user_record['theme'];
	$_SESSION['authenticated'] = 1;
	$_SESSION['page_flags'] = $user_record['page-flags'];
	$_SESSION['my_url'] = $a->get_baseurl() . '/profile/' . $user_record['nickname'];
	$_SESSION['addr'] = $_SERVER['REMOTE_ADDR'];

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
		$l = get_language();

		q("UPDATE `user` SET `login_date` = '%s', `language` = '%s' WHERE `uid` = %d LIMIT 1",
			dbesc(datetime_convert()),
			dbesc($l),
			intval($_SESSION['uid'])
		);

		call_hooks('logged_in', $a->user);

		if(($a->module !== 'home') && isset($_SESSION['return_url']))
			goaway($a->get_baseurl() . '/' . $_SESSION['return_url']);
	}

}



function can_write_wall(&$a,$owner) {

	static $verified = 0;

	if((! (local_user())) && (! (remote_user())))
		return false;

	$uid = local_user();

	if(($uid) && ($uid == $owner)) {
		return true;
	}

	if(remote_user()) {

		// use remembered decision and avoid a DB lookup for each and every display item
		// DO NOT use this function if there are going to be multiple owners

		// We have a contact-id for an authenticated remote user, this block determines if the contact
		// belongs to this page owner, and has the necessary permissions to post content

		if($verified === 2)
			return true;
		elseif($verified === 1)
			return false;
		else {

			$r = q("SELECT `contact`.*, `user`.`page-flags` FROM `contact` LEFT JOIN `user` on `user`.`uid` = `contact`.`uid` 
				WHERE `contact`.`uid` = %d AND `contact`.`id` = %d AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0 
				AND `user`.`blockwall` = 0 AND `readonly` = 0  AND ( `contact`.`rel` IN ( %d , %d ) OR `user`.`page-flags` = %d ) LIMIT 1",
				intval($owner),
				intval(remote_user()),
				intval(CONTACT_IS_SHARING),
				intval(CONTACT_IS_FRIEND),
				intval(PAGE_COMMUNITY)
			);

			if(count($r)) {
				$verified = 2;
				return true;
			}
			else {
				$verified = 1;
			}
		}
	}

	return false;
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

	elseif($remote_user) {

		if(! $remote_verified) {
			$r = q("SELECT id FROM contact WHERE id = %d AND uid = %d AND blocked = 0 LIMIT 1",
				intval($remote_user),
				intval($owner_id)
			);
			if(count($r)) {
				$remote_verified = true;
				$groups = init_groups_visitor($remote_user);
			}
		}
		if($remote_verified) {
		
			$gs = '<<>>'; // should be impossible to match

			if(is_array($groups) && count($groups)) {
				foreach($groups as $g)
					$gs .= '|<' . intval($g) . '>';
			} 

			$sql = sprintf(
				" AND ( allow_cid = '' OR allow_cid REGEXP '<%d>' ) 
				  AND ( deny_cid  = '' OR  NOT deny_cid REGEXP '<%d>' ) 
				  AND ( allow_gid = '' OR allow_gid REGEXP '%s' )
				  AND ( deny_gid  = '' OR NOT deny_gid REGEXP '%s')
				",
				intval($remote_user),
				intval($remote_user),
				dbesc($gs),
				dbesc($gs)
			);
		}
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

	$sql = " AND allow_cid = '' 
			 AND allow_gid = '' 
			 AND deny_cid  = '' 
			 AND deny_gid  = '' 
			 AND private = 0
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

	elseif($remote_user) {

		if(! $remote_verified) {
			$r = q("SELECT id FROM contact WHERE id = %d AND uid = %d AND blocked = 0 LIMIT 1",
				intval($remote_user),
				intval($owner_id)
			);
			if(count($r)) {
				$remote_verified = true;
				$groups = init_groups_visitor($remote_user);
			}
		}
		if($remote_verified) {
		
			$gs = '<<>>'; // should be impossible to match

			if(is_array($groups) && count($groups)) {
				foreach($groups as $g)
					$gs .= '|<' . intval($g) . '>';
			} 

			$sql = sprintf(
				" AND ( private = 0 OR ( private = 1 AND wall = 1 AND ( allow_cid = '' OR allow_cid REGEXP '<%d>' ) 
				  AND ( deny_cid  = '' OR  NOT deny_cid REGEXP '<%d>' ) 
				  AND ( allow_gid = '' OR allow_gid REGEXP '%s' )
				  AND ( deny_gid  = '' OR NOT deny_gid REGEXP '%s'))) 
				",
				intval($remote_user),
				intval($remote_user),
				dbesc($gs),
				dbesc($gs)
			);
		}
	}

	return $sql;
}


