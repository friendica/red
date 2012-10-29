<?php



	$global_perms = array(
		// Read only permissions
		'view_stream'   => array('channel_r_stream',  PERMS_R_STREAM,  true),
		'view_profile'  => array('channel_r_profile', PERMS_R_PROFILE, true),
		'view_photos'   => array('channel_r_photos',  PERMS_R_PHOTOS,  true),
		'view_contacts' => array('channel_r_abook',   PERMS_R_ABOOK,   true),

		// Write permissions
		'send_stream'   => array('channel_w_stream',  PERMS_W_STREAM,  false),
		'post_wall'     => array('channel_w_wall',    PERMS_W_WALL,    false),
		'tag_deliver'   => array('channel_w_tagwall', PERMS_W_TAGWALL, false),
		'post_comments' => array('channel_w_comment', PERMS_W_COMMENT, false),
		'post_mail'     => array('channel_w_mail',    PERMS_W_MAIL,    false),
		'post_photos'   => array('channel_w_photos',  PERMS_W_PHOTOS,  false),
		'chat'          => array('channel_w_chat',    PERMS_W_CHAT,    false),
	);


/**
 * get_all_perms($uid,$observer)
 *
 * @param $uid : The channel_id associated with the resource owner
 * @param $observer: The xchan_hash representing the observer
 *
 * @returns: array of all permissions, key is permission name, value is integer 0 or 1
 */

function get_all_perms($uid,$observer) {

	global $global_perms;

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
			$r = q("select %s, channel_hash from channel where channel_id = %d limit 1",
				dbesc($channel_perm),
				intval($uid)
			);

			$channel_checked = true;
		}

		if(! $r) {
			$ret[$perm_name] = 0;
			continue;
		}

		// Check if this $uid is actually the $observer

		if($r[0]['channel_hash'] === $observer) {
			$ret[$perm_name] = 1;
			continue;
		}

		// If it's an unauthenticated observer, we only need to see if PERMS_PUBLIC is set

		if(! $observer) {
			$ret[$perm_name] = (($r[0][$channel_perm] & PERMS_PUBLIC) ? 1 : 0);
			continue;
		}


		// If we're still here, we have an observer, which means they're in the network.

		if($r[0][$channel_perm] & PERMS_NETWORK) {
			$ret[$perm_name] = 1;
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
				$ret[$perm_name] = 1;
			else
				$ret[$perm_name] = 0;

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
			$ret[$perm_name] = 0;
			continue;
		}
		
		// If we're still going, they are a contact

		if($r && $r[0][$channel_perm] & PERMS_CONTACTS) {

			// Check if this is a write permission and they are being ignored

			if((! $global_perms[$permission][2]) && ($x[0]['abook_flags'] & ABOOK_FLAG_IGNORED)) {
				$ret[$perm_name] = 0;
				continue;
			}

			// Otherwise they're a contact, so they have permission

			$ret[$perm_name] = 1;
			continue;
		}

		// Permission granted to certain channels. Let's see if the observer is one of them

		if(($r) && ($r[0][$channel_perm] & PERMS_SPECIFIC)) {
			if(($x) && ($x[0]['abook_my_perms'] & $global_perms[$permission][1])) {
				$ret[$perm_name] = 1;
				continue;
			}
		}

		// No permissions allowed.

		$ret[$perm_name] = 0;
		continue;

	}

	return $ret;
}


function perm_is_allowed($uid,$observer,$permission) {

	global $global_perms;

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

	// If it's an unauthenticated observer, we only need to see if PERMS_PUBLIC is set

	if(! $observer) {
		return(($r[0][$channel_perm] & PERMS_PUBLIC) ? true : false);
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




function map_perms($channel,$zguid,$zsig) {

	$is_contact = false;
	$is_site    = false;
	$is_network = false;
	$is_anybody = true;


	// To avoid sending the lengthy target_sig with each request,
	// We should provide an array of results for each target
	// and let the sender match the signature.

	if(strlen($zguid) && strlen($zsig)) {
		
		$is_network = true;

		$r = q("select * from contact where guid = '%s' and uid = %d limit 1",
			dbesc($zguid),
			intval($channel['channel_id'])
		);
		if($r && count($r)) {
			$is_contact = true;
			$contact = $r[0];
		}
		$r = q("select * from channel where channel_guid = '%s'",
			dbesc($zguid)
		);
		if($r && count($r)) {
			foreach($r as $rr) {
				if(base64url_encode(rsa_sign($rr['channel_guid'],$rr['channel_prvkey'])) === $zsig) {
					$is_site = true;
					break;
				}
			}
		}
	}

	$perms = array(
		'view_stream'   => array('channel_r_stream',  PERMS_R_STREAM ),
		'view_profile'  => array('channel_r_profile', PERMS_R_PROFILE),
		'view_photos'   => array('channel_r_photos',  PERMS_R_PHOTOS),
		'view_contacts' => array('channel_r_abook',   PERMS_R_ABOOK),

		'send_stream'   => array('channel_w_stream',  PERMS_W_STREAM),
		'post_wall'     => array('channel_w_wall',    PERMS_W_WALL),
		'tag_deliver'   => array('channel_w_tagwall', PERMS_W_TAGWALL),
		'post_comments' => array('channel_w_comment', PERMS_W_COMMENT),
		'post_mail'     => array('channel_w_mail',    PERMS_W_MAIL),
		'post_photos'   => array('channel_w_photos',  PERMS_W_PHOTOS),
		'chat'          => array('channel_w_chat',    PERMS_W_CHAT),
	);


	$ret = array();

	foreach($perms as $k => $v) {
		$ret[$k] = z_check_perms($k,$v,$channel,$contact,$is_contact,$is_site,$is_network,$is_anybody);

	}

	return $ret;

}

function z_check_perms($k,$v,$channel,$contact,$is_contact,$is_site,$is_network,$is_anybody) {

	$allow = (($contact['self']) ? true : false);
	
	switch($channel[$v[0]]) {
		case PERMS_PUBLIC:
				if($is_anybody)
					$allow = true;
				break;
		case PERMS_NETWORK:
				if($is_network)
					$allow = true;
				break;
		case PERMS_SITE:
				if($is_site)
					$allow = true;
				break;
		case PERMS_CONTACTS:
				if($is_contact)
					$allow = true;
				break;
		case PERMS_SPECIFIC:
				if($is_contact && is_array($contact) && ($contact['my_perms'] & $v[1]))
					$allow = true;
				break;
		default:
				break;
	}
	return $allow; 
}

