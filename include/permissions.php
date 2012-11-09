<?php


function get_perms() {
	$global_perms = array(
		// Read only permissions
		'view_stream'   => array('channel_r_stream',  intval(PERMS_R_STREAM),  true, t('Can view my "public" stream and posts'), ''),
		'view_profile'  => array('channel_r_profile', intval(PERMS_R_PROFILE), true, t('Can view my "public" channel profile'), ''),
		'view_photos'   => array('channel_r_photos',  intval(PERMS_R_PHOTOS),  true, t('Can view my "public" photo albums'), ''),
		'view_contacts' => array('channel_r_abook',   intval(PERMS_R_ABOOK),   true, t('Can view my "public" address book'), ''),

		// Write permissions
		'send_stream'   => array('channel_w_stream',  intval(PERMS_W_STREAM),  false, t('Can send me their channel stream and posts'), ''),
		'post_wall'     => array('channel_w_wall',    intval(PERMS_W_WALL),    false, t('Can post on my channel page ("wall")'), ''),
		'post_comments' => array('channel_w_comment', intval(PERMS_W_COMMENT), false, t('Can comment on my posts'), ''),
		'post_mail'     => array('channel_w_mail',    intval(PERMS_W_MAIL),    false, t('Can send me private mail messages'), ''),
		'post_photos'   => array('channel_w_photos',  intval(PERMS_W_PHOTOS),  false, t('Can post photos to my photo albums'), ''),
		'tag_deliver'   => array('channel_w_tagwall', intval(PERMS_W_TAGWALL), false, t('Can forward to all my channel contacts via post tags'), t('Advanced - useful for creating group forum channels')),
		'chat'          => array('channel_w_chat',    intval(PERMS_W_CHAT),    false, t('Can chat with me (when available)'), t('Requires compatible chat plugin')),
	);
	return $global_perms;
}


/**
 * get_all_perms($uid,$observer)
 *
 * @param $uid : The channel_id associated with the resource owner
 * @param $observer: The xchan_hash representing the observer
 *
 * @returns: array of all permissions, key is permission name, value is true or false
 */

function get_all_perms($uid,$observer,$internal_use = true) {

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

		if(! $r) {
			$ret[$perm_name] = false;
			continue;
		}

		// Check if this $uid is actually the $observer

		if($r[0]['channel_hash'] === $observer) {
			$ret[$perm_name] = true;
			continue;
		}

		if($r[0][$channel_perm] & PERMS_PUBLIC) {
			$ret[$perm_name] = true;
			continue;
		}

		if(! $observer) {
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
					dbesc($observer)
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
		// and not blocked/ignored

		if(! $abook_checked) {
			$x = q("select abook_my_perms, abook_flags from abook 
				where abook_channel = %d and abook_xchan = '%s' limit 1",
				intval($uid),
				dbesc($observer)
			);
			$abook_checked = true;
		}


		// If they're blocked - they can't read or write
 
		if((! $x) || ($x[0]['abook_flags'] & ABOOK_FLAG_BLOCKED)) {
			$ret[$perm_name] = false;
			continue;
		}
		
		// If we're still going, they are a contact

		if($r && $r[0][$channel_perm] & PERMS_CONTACTS) {

			// Check if this is a write permission and they are being ignored
			// This flag is only visible internally.

			if(($internal_use) && (! $global_perms[$permission][2]) && ($x[0]['abook_flags'] & ABOOK_FLAG_IGNORED)) {
				$ret[$perm_name] = false;
				continue;
			}

			// Otherwise they're a contact, so they have permission

			$ret[$perm_name] = true;
			continue;
		}

		// Permission granted to certain channels. Let's see if the observer is one of them

		if(($r) && ($r[0][$channel_perm] & PERMS_SPECIFIC)) {
			if(($x) && ($x[0]['abook_my_perms'] & $global_perms[$permission][1])) {
				$ret[$perm_name] = true;
				continue;
			}
		}

		// No permissions allowed.

		$ret[$perm_name] = false;
		continue;

	}

	return $ret;
}


function perm_is_allowed($uid,$observer,$permission) {

	$global_perms = get_perms();

	// First find out what the channel owner declared permissions to be.

	$channel_perm = $global_perms[$permission][0];

	$r = q("select %s, channel_hash from channel where channel_id = %d limit 1",
		dbesc($channel_perm),
		intval($uid)
	);
	if(! $r)
		return false;

	// Check if this $uid is actually the $observer

	if($r[0]['channel_hash'] === $observer)
		return true;

	
	if($r[0][$channel_perm] & PERMS_PUBLIC)
		return true;

	// If it's an unauthenticated observer, we only need to see if PERMS_PUBLIC is set

	if(! $observer) {
		return false;
	}

	// If we're still here, we have an observer, which means they're in the network.

	if($r[0][$channel_perm] & PERMS_NETWORK)
		return true;


	// If PERMS_SITE is specified, find out if they've got an account on this hub

	if($r[0][$channel_perm] & PERMS_SITE) {
		$c = q("select channel_hash from channel where channel_hash = '%s' limit 1",
			dbesc($observer)
		);
		if($c)
			return true;
		return false;
	}	

	// If PERMS_CONTACTS or PERMS_SPECIFIC, they need to be in your address book
	// and not blocked/ignored

	$x = q("select abook_my_perms, abook_flags from abook where abook_channel = %d and abook_xchan = '%s' limit 1",
		intval($uid),
		dbesc($observer)
	);

	// If they're blocked - they can't read or write
 
	if((! $x) || ($x[0]['abook_flags'] & ABOOK_FLAG_BLOCKED))
		return false;
		
	// If we're still going, they are a contact

	if($r[0][$channel_perm] & PERMS_CONTACTS) {

		// Check if this is a write permission and they are being ignored

		if((! $global_perms[$permission][2]) && ($x[0]['abook_flags'] & ABOOK_FLAG_IGNORED))
			return false;

		// Otherwise they're a contact, so they have permission

		return true;
	}

	// Permission granted to certain channels. Let's see if the observer is one of them

	if($r[0][$channel_perm] & PERMS_SPECIFIC) {
		if($x[0]['abook_my_perms'] & $global_perms[$permission][1])
			return true;
	}

	// No permissions allowed.

	return false;		

}




