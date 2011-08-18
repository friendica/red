<?php

function can_write_wall(&$a,$owner) {

	static $verified = 0;

	if((! (local_user())) && (! (remote_user())))
		return false;

	$uid = local_user();

	if(($uid) && ($uid == $owner)) {
		return true;
	}

	if(remote_user()) {

		// user remembered decision and avoid a DB lookup for each and every display item
		// DO NOT use this function if there are going to be multiple owners

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