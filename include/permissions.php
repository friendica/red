<?php /** @file */


function get_perms() {

// thinking about making element[2] a bitmask instead of boolean so that we can provide a list of applicable selections
// for any given permission. Currently we use the boolean to disallow write access to "everybody", but we also want to be
// able to handle troublesome settings such as allowing channel_w_stream to anybody in the network. You can allow it, but 
// there's no way to implement sending it. 

	$global_perms = array(
		// Read only permissions
		'view_stream'   => array('channel_r_stream',  intval(PERMS_R_STREAM),  true, t('Can view my "public" stream and posts'), ''),
		'view_profile'  => array('channel_r_profile', intval(PERMS_R_PROFILE), true, t('Can view my "public" channel profile'), ''),
		'view_photos'   => array('channel_r_photos',  intval(PERMS_R_PHOTOS),  true, t('Can view my "public" photo albums'), ''),
		'view_contacts' => array('channel_r_abook',   intval(PERMS_R_ABOOK),   true, t('Can view my "public" address book'), ''),
		'view_storage' => array('channel_r_storage',   intval(PERMS_R_STORAGE),   true, t('Can view my "public" file storage'), ''),
		'view_pages' => array('channel_r_pages',   intval(PERMS_R_PAGES),   true, t('Can view my "public" pages'), ''),

		// Write permissions
		'send_stream'   => array('channel_w_stream',  intval(PERMS_W_STREAM),  false, t('Can send me their channel stream and posts'), ''),
		'post_wall'     => array('channel_w_wall',    intval(PERMS_W_WALL),    false, t('Can post on my channel page ("wall")'), ''),
		'post_comments' => array('channel_w_comment', intval(PERMS_W_COMMENT), false, t('Can comment on my posts'), ''),
		'post_mail'     => array('channel_w_mail',    intval(PERMS_W_MAIL),    false, t('Can send me private mail messages'), ''),
		'post_photos'   => array('channel_w_photos',  intval(PERMS_W_PHOTOS),  false, t('Can post photos to my photo albums'), ''),
		'tag_deliver'   => array('channel_w_tagwall', intval(PERMS_W_TAGWALL), false, t('Can forward to all my channel contacts via post hashtags'), t('Advanced - useful for creating group forum channels')),
		'chat'          => array('channel_w_chat',    intval(PERMS_W_CHAT),    false, t('Can chat with me (when available)'), t('Requires compatible chat plugin')),
		'write_storage' => array('channel_w_storage',   intval(PERMS_W_STORAGE),   false, t('Can write to my "public" file storage'), ''),
		'write_pages' => array('channel_w_pages',   intval(PERMS_W_PAGES),   false, t('Can edit my "public" pages'), ''),

		'delegate'      => array('channel_a_delegate', intval(PERMS_A_DELEGATE),    false, t('Can administer my channel resources'), t('Extremely advanced. Leave this alone unless you know what you are doing')),
	);
	$ret = array('global_permissions' => $global_perms);
	call_hooks('global_permissions',$ret);
	return $ret['global_permissions'];
}


/**
 * get_all_perms($uid,$observer_xchan)
 *
 * @param $uid : The channel_id associated with the resource owner
 * @param $observer_xchan: The xchan_hash representing the observer
 *
 * @returns: array of all permissions, key is permission name, value is true or false
 */

function get_all_perms($uid,$observer_xchan,$internal_use = true) {

	$global_perms = get_perms();

	// Save lots of individual lookups

	$r = null;
	$c = null;
	$x = null;

	$channel_checked = false;
	$onsite_checked  = false;
	$abook_checked   = false;

	$ret = array();

	foreach($global_perms as $perm_name => $permission) {

		// First find out what the channel owner declared permissions to be.

		$channel_perm = $permission[0];

		if(! $channel_checked) {
			$r = q("select * from channel where channel_id = %d limit 1",
				intval($uid)
			);
			$channel_checked = true;
		}

		// The uid provided doesn't exist. This would be a big fail.

		if(! $r) {
			$ret[$perm_name] = false;
			continue;
		}


		// Next we're going to check for blocked or ignored contacts.
		// These take priority over all other settings.

		if($observer_xchan) {
			if(! $abook_checked) {
				$x = q("select abook_my_perms, abook_flags from abook 
					where abook_channel = %d and abook_xchan = '%s' and not ( abook_flags & %d ) limit 1",
					intval($uid),
					dbesc($observer_xchan),
					intval(ABOOK_FLAG_SELF)
				);
				$abook_checked = true;
			}

			// If they're blocked - they can't read or write
 
			if(($x) && (($x[0]['abook_flags'] & ABOOK_FLAG_BLOCKED) || ($x[0]['abook_flags'] & ABOOK_FLAG_PENDING))) {
				$ret[$perm_name] = false;
				continue;
			}

			// Check if this is a write permission and they are being ignored
			// This flag is only visible internally.

			if(($x) && ($internal_use) && (! $global_perms[$perm_name][2]) && ($x[0]['abook_flags'] & ABOOK_FLAG_IGNORED)) {
				$ret[$perm_name] = false;
				continue;
			}
		}

		// Check if this $uid is actually the $observer_xchan - if it's your content
		// you always have permission to do anything

		if(($observer_xchan) && ($r[0]['channel_hash'] === $observer_xchan)) {
			$ret[$perm_name] = true;
			continue;
		}

		// Anybody at all (that wasn't blocked or ignored). They have permission.

		if($r[0][$channel_perm] & PERMS_PUBLIC) {
			$ret[$perm_name] = true;
			continue;
		}

		// From here on out, we need to know who they are. If we can't figure it
		// out, permission is denied.

		if(! $observer_xchan) {
			$ret[$perm_name] = false;
			continue;
		}

		// If we're still here, we have an observer, which means they're in the network.

		if($r[0][$channel_perm] & PERMS_NETWORK) {
			$ret[$perm_name] = true;
			continue;
		}

		// If PERMS_SITE is specified, find out if they've got an account on this hub

		if($r[0][$channel_perm] & PERMS_SITE) {
			if(! $onsite_checked) {
				$c = q("select channel_hash from channel where channel_hash = '%s' limit 1",
					dbesc($observer_xchan)
				);

				$onsite_checked = true;
			}
	
			if($c)
				$ret[$perm_name] = true;
			else
				$ret[$perm_name] = false;

			continue;
		}	

		// If PERMS_CONTACTS or PERMS_SPECIFIC, they need to be in your address book
		// $x is a valid address book entry



		if(! $x) {
			$ret[$perm_name] = false;
			continue;
		}
		

		if(($r) && ($r[0][$channel_perm] & PERMS_CONTACTS)) {

			// They're a contact, so they have permission

			$ret[$perm_name] = true;
			continue;
		}

		// Permission granted to certain channels. Let's see if the observer is one of them

		if(($r) && ($r[0][$channel_perm] & PERMS_SPECIFIC)) {
			if(($x[0]['abook_my_perms'] & $global_perms[$perm_name][1])) {
				$ret[$perm_name] = true;
				continue;
			}
		}

		// No permissions allowed.

		$ret[$perm_name] = false;
		continue;

	}


	$arr = array(
		'channel_id'    => $uid,
		'observer_hash' => $observer_xchan,
		'permissions'   => $ret);

	call_hooks('get_all_perms',$arr);
	return $arr['permissions'];
}


function perm_is_allowed($uid,$observer_xchan,$permission) {


	$arr = array(
		'channel_id'    => $uid,
		'observer_hash' => $observer_xchan,
		'permission'    => $permission,
		'result'        => false);

	call_hooks('perm_is_allowed',$arr);
	if($arr['result'])
		return true;

	$global_perms = get_perms();

	// First find out what the channel owner declared permissions to be.

	$channel_perm = $global_perms[$permission][0];

	$r = q("select %s, channel_hash from channel where channel_id = %d limit 1",
		dbesc($channel_perm),
		intval($uid)
	);
	if(! $r)
		return false;

	if($observer_xchan) {
		$x = q("select abook_my_perms, abook_flags from abook where abook_channel = %d and abook_xchan = '%s' and not ( abook_flags & %d ) limit 1",
			intval($uid),
			dbesc($observer_xchan),
			intval(ABOOK_FLAG_SELF)
		);

		// If they're blocked - they can't read or write
 
		if(($x) && (($x[0]['abook_flags'] & ABOOK_FLAG_BLOCKED) || ($x[0]['abook_flags'] & ABOOK_FLAG_PENDING)))
			return false;
		
		if(($x) && (! $global_perms[$permission][2]) && ($x[0]['abook_flags'] & ABOOK_FLAG_IGNORED))
			return false;

	}


	// Check if this $uid is actually the $observer_xchan

	if($r[0]['channel_hash'] === $observer_xchan)
		return true;

	
	if($r[0][$channel_perm] & PERMS_PUBLIC)
		return true;

	// If it's an unauthenticated observer, we only need to see if PERMS_PUBLIC is set

	if(! $observer_xchan) {
		return false;
	}

	// If we're still here, we have an observer, which means they're in the network.

	if($r[0][$channel_perm] & PERMS_NETWORK)
		return true;


	// If PERMS_SITE is specified, find out if they've got an account on this hub

	if($r[0][$channel_perm] & PERMS_SITE) {
		$c = q("select channel_hash from channel where channel_hash = '%s' limit 1",
			dbesc($observer_xchan)
		);
		if($c)
			return true;
		return false;
	}	

	if(! $x) {
		return false;
	}

	if($r[0][$channel_perm] & PERMS_CONTACTS) {
		return true;
	}

	// Permission granted to certain channels. Let's see if the observer is one of them

	if(($r) && $r[0][$channel_perm] & PERMS_SPECIFIC) {
		if($x[0]['abook_my_perms'] & $global_perms[$permission][1])
			return true;
	}




	// No permissions allowed.

	return false;		

}


// Check a simple array of observers against a permissions
// return a simple array of those with permission

function check_list_permissions($uid,$arr,$perm) {
	$result = array();
	if($arr)
		foreach($arr as $x)
			if(perm_is_allowed($uid,$x,$perm))
				$result[] = $x;
	return($result);
}

