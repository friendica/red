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


// Since these include the translation function - they couldn't be included
// in $global_perms without causing an include dependency, so we provide a parallel 
// array which isn't global.

function perms_text() {
	$perms_text = array(
		'view_stream' => t('Who can view your channel stream and posts'),
		'view_profile' => t('Who can view your channel profile'),
		'view_photos' => t('Who can view your photo albums'),
		'view_contacts' => t('Who can view your address book'),

		'send_stream' => t('Who can send you their channel stream and posts'),
		'post_wall' => t('Who can post on your channel page'),
		'post_comments' => t('Who can comment on your posts'),
		'post_mail' => t('Who can send you private mail messages'),
		'post_photos' => t('Who can post photos to your photo albums'),
		'tag_deliver' => t('Who can forward to all your channel contacts via post tags'),
		'chat' => t('Who can chat with you (when available)')
	);
	return $perms_text;
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

		// If it's an unauthenticated observer, we only need to see if PERMS_PUBLIC is set

		if(! $observer) {
			$ret[$perm_name] = (($r[0][$channel_perm] & PERMS_PUBLIC) ? true : false);
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




