<?php
/**
 * @file incldue/permissions.php
 *
 * This file conntains functions to check and work with permissions.
 */

/**
 * @brief Return an array with all available permissions.
 *
 * These are channel specific permissions.
 * The list of available permissions can get manipulated by the <i>hook</i>
 * <b>global_permissions</b>.
 *
 * @return array associative array containing all permissions
 */
function get_perms() {

// thinking about making element[2] a bitmask instead of boolean so that we can provide a list of applicable selections
// for any given permission. Currently we use the boolean to disallow write access to "everybody", but we also want to be
// able to handle troublesome settings such as allowing channel_w_stream to anybody in the network. You can allow it, but 
// there's no way to implement sending it. 

	$global_perms = array(
		// Read only permissions
		'view_stream'   => array('channel_r_stream',  intval(PERMS_R_STREAM),  true, t('Can view my normal stream and posts'), ''),
		'view_profile'  => array('channel_r_profile', intval(PERMS_R_PROFILE), true, t('Can view my default channel profile'), ''),
		'view_photos'   => array('channel_r_photos',  intval(PERMS_R_PHOTOS),  true, t('Can view my photo albums'), ''),
		'view_contacts' => array('channel_r_abook',   intval(PERMS_R_ABOOK),   true, t('Can view my connections'), ''),
		'view_storage'  => array('channel_r_storage', intval(PERMS_R_STORAGE), true, t('Can view my file storage'), ''),
		'view_pages'    => array('channel_r_pages',   intval(PERMS_R_PAGES),   true, t('Can view my webpages'), ''),

		// Write permissions
		'send_stream'   => array('channel_w_stream',  intval(PERMS_W_STREAM),  false, t('Can send me their channel stream and posts'), ''),
		'post_wall'     => array('channel_w_wall',    intval(PERMS_W_WALL),    false, t('Can post on my channel page ("wall")'), ''),
		'post_comments' => array('channel_w_comment', intval(PERMS_W_COMMENT), false, t('Can comment on or like my posts'), ''),
		'post_mail'     => array('channel_w_mail',    intval(PERMS_W_MAIL),    false, t('Can send me private mail messages'), ''),
		'post_photos'   => array('channel_w_photos',  intval(PERMS_W_PHOTOS),  false, t('Can post photos to my photo albums'), ''),
		'post_like'     => array('channel_w_like',    intval(PERMS_W_LIKE),    false, t('Can like/dislike stuff'), t('Profiles and things other than posts/comments')),

		'tag_deliver'   => array('channel_w_tagwall', intval(PERMS_W_TAGWALL), false, t('Can forward to all my channel contacts via post @mentions'), t('Advanced - useful for creating group forum channels')),
		'chat'          => array('channel_w_chat',    intval(PERMS_W_CHAT),    false, t('Can chat with me (when available)'), t('')),
		'write_storage' => array('channel_w_storage', intval(PERMS_W_STORAGE), false, t('Can write to my file storage'), ''),
		'write_pages'   => array('channel_w_pages',   intval(PERMS_W_PAGES),   false, t('Can edit my webpages'), ''),

		'republish'     => array('channel_a_republish', intval(PERMS_A_REPUBLISH), false, t('Can source my public posts in derived channels'), t('Somewhat advanced - very useful in open communities')),

		'delegate'      => array('channel_a_delegate', intval(PERMS_A_DELEGATE),   false, t('Can administer my channel resources'), t('Extremely advanced. Leave this alone unless you know what you are doing')),
	);
	$ret = array('global_permissions' => $global_perms);
	call_hooks('global_permissions', $ret);

	return $ret['global_permissions'];
}


/**
 * get_all_perms($uid,$observer_xchan)
 *
 * @param int $uid The channel_id associated with the resource owner
 * @param string $observer_xchan The xchan_hash representing the observer
 * @param bool $internal_use (default true)
 *
 * @returns array of all permissions, key is permission name, value is true or false
 */
function get_all_perms($uid, $observer_xchan, $internal_use = true) {

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
			if($r[0][$channel_perm] & PERMS_AUTHED) {
				$ret[$perm_name] = true;
				continue;
			}

			if(! $abook_checked) {
				$x = q("select abook_my_perms, abook_flags, xchan_network from abook left join xchan on abook_xchan = xchan_hash
					where abook_channel = %d and abook_xchan = '%s' and not ( abook_flags & %d )>0 limit 1",
					intval($uid),
					dbesc($observer_xchan),
					intval(ABOOK_FLAG_SELF)
				);
				if(! $x) {
					// not in address book, see if they've got an xchan
					$y = q("select xchan_network from xchan where xchan_hash = '%s' limit 1",
						dbesc($observer_xchan)
					);
				}

				$abook_checked = true;
			}

			// If they're blocked - they can't read or write

			if(($x) && ($x[0]['abook_flags'] & ABOOK_FLAG_BLOCKED)) {
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

		// system is blocked to anybody who is not authenticated

		if((! $observer_xchan) && intval(get_config('system', 'block_public'))) {
			$ret[$perm_name] = false;
			continue;
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

		// If we're still here, we have an observer, check the network.

		if($r[0][$channel_perm] & PERMS_NETWORK) {
			if(($x && $x[0]['xchan_network'] === 'zot') || ($y && $y[0]['xchan_network'] === 'zot')) {
				$ret[$perm_name] = true;
				continue;
			}
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

		// From here on we require that the observer be a connection and
		// handle whether we're allowing any, approved or specific ones

		if(! $x) {
			$ret[$perm_name] = false;
			continue;
		}

		// They are in your address book, but haven't been approved

		if($r[0][$channel_perm] & PERMS_PENDING) {
			$ret[$perm_name] = true;
			continue;
		}

		if($x[0]['abook_flags'] & ABOOK_FLAG_PENDING) {
			$ret[$perm_name] = false;
			continue;
		}

		// They're a contact, so they have permission

		if($r[0][$channel_perm] & PERMS_CONTACTS) {
			$ret[$perm_name] = true;
			continue;
		}

		// Permission granted to certain channels. Let's see if the observer is one of them

		if($r[0][$channel_perm] & PERMS_SPECIFIC) {
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

/**
 * @brief Checks if given permission is allowed for given observer on a channel.
 *
 * Checks if the given observer with the hash $observer_xchan has permission
 * $permission on channel_id $uid.
 * $permission is one defined in get_perms();
 *
 * @param int $uid The channel_id associated with the resource owner
 * @param string $observer_xchan The xchan_hash representing the observer
 * @param string $permission
 * @return bool true if permission is allowed for observer on channel
 */
function perm_is_allowed($uid, $observer_xchan, $permission) {

	$arr = array(
		'channel_id'    => $uid,
		'observer_hash' => $observer_xchan,
		'permission'    => $permission,
		'result'        => false);

	call_hooks('perm_is_allowed', $arr);
	if($arr['result'])
		return true;

	$global_perms = get_perms();

	// First find out what the channel owner declared permissions to be.

	$channel_perm = $global_perms[$permission][0];

	$r = q("select %s, channel_pageflags, channel_hash from channel where channel_id = %d limit 1",
		dbesc($channel_perm),
		intval($uid)
	);
	if(! $r)
		return false;

	if($observer_xchan) {
		if($r[0][$channel_perm] & PERMS_AUTHED)
			return true;

		$x = q("select abook_my_perms, abook_flags, xchan_network from abook left join xchan on abook_xchan = xchan_hash 
			where abook_channel = %d and abook_xchan = '%s' and not ( abook_flags & %d )>0 limit 1",
			intval($uid),
			dbesc($observer_xchan),
			intval(ABOOK_FLAG_SELF)
		);

		// If they're blocked - they can't read or write
 
		if(($x) && ($x[0]['abook_flags'] & ABOOK_FLAG_BLOCKED))
			return false;

		if(($x) && (! $global_perms[$permission][2]) && ($x[0]['abook_flags'] & ABOOK_FLAG_IGNORED))
			return false;

		if(! $x) {
			// not in address book, see if they've got an xchan
			$y = q("select xchan_network from xchan where xchan_hash = '%s' limit 1",
				dbesc($observer_xchan)
			);
		}
	}

	// system is blocked to anybody who is not authenticated

	if((! $observer_xchan) && intval(get_config('system', 'block_public')))
		return false;

	// Check if this $uid is actually the $observer_xchan

	if($r[0]['channel_hash'] === $observer_xchan)
		return true;

	if($r[0][$channel_perm] & PERMS_PUBLIC)
		return true;

	// If it's an unauthenticated observer, we only need to see if PERMS_PUBLIC is set

	if(! $observer_xchan) {
		return false;
	}

	// If we're still here, we have an observer, check the network.

	if($r[0][$channel_perm] & PERMS_NETWORK) {
		if (($x && $x[0]['xchan_network'] === 'zot') || ($y && $y[0]['xchan_network'] === 'zot'))
			return true;
	}

	// If PERMS_SITE is specified, find out if they've got an account on this hub

	if($r[0][$channel_perm] & PERMS_SITE) {
		$c = q("select channel_hash from channel where channel_hash = '%s' limit 1",
			dbesc($observer_xchan)
		);
		if($c)
			return true;

		return false;
	}

	// From here on we require that the observer be a connection and
	// handle whether we're allowing any, approved or specific ones

	if(! $x) {
		return false;
	}

	// They are in your address book, but haven't been approved

	if($r[0][$channel_perm] & PERMS_PENDING) {
		return true;
	}

	if($x[0]['abook_flags'] & ABOOK_FLAG_PENDING) {
		return false;
	}

	// They're a contact, so they have permission

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

function check_list_permissions($uid, $arr, $perm) {
	$result = array();
	if($arr)
		foreach($arr as $x)
			if(perm_is_allowed($uid, $x, $perm))
				$result[] = $x;

	return($result);
}

/**
 * @brief Sets site wide default permissions.
 *
 * @return array
 */
function site_default_perms() {

	$ret = array();

	$typical = array(
		'view_stream'   => PERMS_PUBLIC,
		'view_profile'  => PERMS_PUBLIC,
		'view_photos'   => PERMS_PUBLIC,
		'view_contacts' => PERMS_PUBLIC,
		'view_storage'  => PERMS_PUBLIC,
		'view_pages'    => PERMS_PUBLIC,
		'send_stream'   => PERMS_SPECIFIC,
		'post_wall'     => PERMS_SPECIFIC,
		'post_comments' => PERMS_SPECIFIC,
		'post_mail'     => PERMS_SPECIFIC,
		'post_photos'   => 0,
		'tag_deliver'   => PERMS_SPECIFIC,
		'chat'          => PERMS_SPECIFIC,
		'write_storage' => 0,
		'write_pages'   => 0,
		'delegate'      => 0,
		'post_like'     => PERMS_NETWORK
	);

	$global_perms = get_perms();

	foreach($global_perms as $perm => $v) {
		$x = get_config('default_perms', $perm);
		if($x === false)
			$x = $typical[$perm];
		$ret[$perm] = $x;
	}

	return $ret;
}


/**
 * @function get_role_perms($role)
 * @param string $role
 * 
 *   Given a string for the channel role ('social','forum', etc)
 * return an array of all permission fields pre-filled for this role.
 * This includes the channel permission scope indicators (anything beginning with 'channel_') as well as
 *    perms_auto:   true or false to create auto-permissions for this channel
 *    perms_follow: The permissions to apply when initiating a connection request to another channel
 *    perms_accept: The permissions to apply when accepting a connection request from another channel (not automatic)
 *    default_collection: true or false to make the default ACL include the channel's default collection 
 *    directory_publish: true or false to publish this channel in the directory
 * Any attributes may be extended (new roles defined) and modified (specific permissions altered) by plugins
 *
 */

function get_role_perms($role) {

	$ret = array();

	$ret['role'] = $role;

	switch($role) {
		case 'social':
			$ret['perms_auto'] = false;
			$ret['default_collection'] = false;
			$ret['directory_publish'] = true;
			$ret['online'] = true;
			$ret['perms_follow'] = PERMS_R_STREAM|PERMS_R_PROFILE|PERMS_R_PHOTOS|PERMS_R_ABOOK
				|PERMS_W_STREAM|PERMS_W_WALL|PERMS_W_COMMENT|PERMS_W_MAIL|PERMS_W_CHAT
				|PERMS_R_STORAGE|PERMS_R_PAGES|PERMS_A_REPUBLISH|PERMS_W_LIKE;
			$ret['perms_accept'] = PERMS_R_STREAM|PERMS_R_PROFILE|PERMS_R_PHOTOS|PERMS_R_ABOOK
				|PERMS_W_STREAM|PERMS_W_WALL|PERMS_W_COMMENT|PERMS_W_MAIL|PERMS_W_CHAT
				|PERMS_R_STORAGE|PERMS_R_PAGES|PERMS_A_REPUBLISH|PERMS_W_LIKE;
			$ret['channel_r_stream']    = PERMS_PUBLIC;
			$ret['channel_r_profile']   = PERMS_PUBLIC;
			$ret['channel_r_photos']    = PERMS_PUBLIC;
			$ret['channel_r_abook']     = PERMS_PUBLIC;
			$ret['channel_w_stream']    = PERMS_SPECIFIC;
			$ret['channel_w_wall']      = PERMS_SPECIFIC;
			$ret['channel_w_tagwall']   = PERMS_SPECIFIC;
			$ret['channel_w_comment']   = PERMS_SPECIFIC;
			$ret['channel_w_mail']      = PERMS_SPECIFIC;
			$ret['channel_w_photos']    = 0;
			$ret['channel_w_chat']      = PERMS_SPECIFIC;
			$ret['channel_a_delegate']  = 0;
			$ret['channel_r_storage']   = PERMS_PUBLIC;
			$ret['channel_w_storage']   = 0;
			$ret['channel_r_pages']     = PERMS_PUBLIC;
			$ret['channel_w_pages']     = 0;
			$ret['channel_a_republish'] = PERMS_SPECIFIC;
			$ret['channel_w_like']      = PERMS_NETWORK;

			break;

		case 'social_restricted':
			$ret['perms_auto'] = false;
			$ret['default_collection'] = true;
			$ret['directory_publish'] = true;
			$ret['online'] = true;
			$ret['perms_follow'] = PERMS_R_STREAM|PERMS_R_PROFILE|PERMS_R_PHOTOS|PERMS_R_ABOOK
				|PERMS_W_STREAM|PERMS_W_WALL|PERMS_W_COMMENT|PERMS_W_MAIL|PERMS_W_CHAT
				|PERMS_R_STORAGE|PERMS_R_PAGES|PERMS_W_LIKE;
			$ret['perms_accept'] = PERMS_R_STREAM|PERMS_R_PROFILE|PERMS_R_PHOTOS|PERMS_R_ABOOK
				|PERMS_W_STREAM|PERMS_W_WALL|PERMS_W_COMMENT|PERMS_W_MAIL|PERMS_W_CHAT
				|PERMS_R_STORAGE|PERMS_R_PAGES|PERMS_W_LIKE;
			$ret['channel_r_stream']    = PERMS_PUBLIC;
			$ret['channel_r_profile']   = PERMS_PUBLIC;
			$ret['channel_r_photos']    = PERMS_PUBLIC;
			$ret['channel_r_abook']     = PERMS_PUBLIC;
			$ret['channel_w_stream']    = PERMS_SPECIFIC;
			$ret['channel_w_wall']      = PERMS_SPECIFIC;
			$ret['channel_w_tagwall']   = PERMS_SPECIFIC;
			$ret['channel_w_comment']   = PERMS_SPECIFIC;
			$ret['channel_w_mail']      = PERMS_SPECIFIC;
			$ret['channel_w_photos']    = 0;
			$ret['channel_w_chat']      = PERMS_SPECIFIC;
			$ret['channel_a_delegate']  = 0;
			$ret['channel_r_storage']   = PERMS_PUBLIC;
			$ret['channel_w_storage']   = 0;
			$ret['channel_r_pages']     = PERMS_PUBLIC;
			$ret['channel_w_pages']     = 0;
			$ret['channel_a_republish'] = PERMS_SPECIFIC;
			$ret['channel_w_like']      = PERMS_SPECIFIC;

			break;

		case 'social_private':
			$ret['perms_auto'] = false;
			$ret['default_collection'] = true;
			$ret['directory_publish'] = false;
			$ret['online'] = false;
			$ret['perms_follow'] = PERMS_R_STREAM|PERMS_R_PROFILE|PERMS_R_PHOTOS|PERMS_R_ABOOK
				|PERMS_W_STREAM|PERMS_W_WALL|PERMS_W_COMMENT|PERMS_W_MAIL|PERMS_W_CHAT
				|PERMS_R_STORAGE|PERMS_R_PAGES|PERMS_W_LIKE;
			$ret['perms_accept'] = PERMS_R_STREAM|PERMS_R_PROFILE|PERMS_R_PHOTOS|PERMS_R_ABOOK
				|PERMS_W_STREAM|PERMS_W_WALL|PERMS_W_COMMENT|PERMS_W_MAIL|PERMS_W_CHAT
				|PERMS_R_STORAGE|PERMS_R_PAGES|PERMS_W_LIKE;
			$ret['channel_r_stream']    = PERMS_PUBLIC;
			$ret['channel_r_profile']   = PERMS_PUBLIC;
			$ret['channel_r_photos']    = PERMS_PUBLIC;
			$ret['channel_r_abook']     = PERMS_SPECIFIC;
			$ret['channel_w_stream']    = PERMS_SPECIFIC;
			$ret['channel_w_wall']      = PERMS_SPECIFIC;
			$ret['channel_w_tagwall']   = PERMS_SPECIFIC;
			$ret['channel_w_comment']   = PERMS_SPECIFIC;
			$ret['channel_w_mail']      = PERMS_SPECIFIC;
			$ret['channel_w_photos']    = 0;
			$ret['channel_w_chat']      = PERMS_SPECIFIC;
			$ret['channel_a_delegate']  = 0;
			$ret['channel_r_storage']   = PERMS_PUBLIC;
			$ret['channel_w_storage']   = 0;
			$ret['channel_r_pages']     = PERMS_PUBLIC;
			$ret['channel_w_pages']     = 0;
			$ret['channel_a_republish'] = PERMS_SPECIFIC;
			$ret['channel_w_like']      = PERMS_SPECIFIC;

			break;

		case 'forum':
			$ret['perms_auto'] = true;
			$ret['default_collection'] = false;
			$ret['directory_publish'] = true;
			$ret['online'] = false;
			$ret['perms_follow'] = PERMS_R_STREAM|PERMS_R_PROFILE|PERMS_R_PHOTOS|PERMS_R_ABOOK
				|PERMS_W_STREAM|PERMS_W_WALL|PERMS_W_COMMENT|PERMS_W_MAIL|PERMS_W_CHAT
				|PERMS_R_STORAGE|PERMS_R_PAGES|PERMS_A_REPUBLISH|PERMS_W_LIKE|PERMS_W_TAGWALL;
			$ret['perms_accept'] = PERMS_R_STREAM|PERMS_R_PROFILE|PERMS_R_PHOTOS|PERMS_R_ABOOK
				|PERMS_W_STREAM|PERMS_W_WALL|PERMS_W_COMMENT|PERMS_W_MAIL|PERMS_W_CHAT
				|PERMS_R_STORAGE|PERMS_R_PAGES|PERMS_A_REPUBLISH|PERMS_W_LIKE|PERMS_W_TAGWALL;
			$ret['channel_r_stream']    = PERMS_PUBLIC;
			$ret['channel_r_profile']   = PERMS_PUBLIC;
			$ret['channel_r_photos']    = PERMS_PUBLIC;
			$ret['channel_r_abook']     = PERMS_PUBLIC;
			$ret['channel_w_stream']    = 0;
			$ret['channel_w_wall']      = PERMS_SPECIFIC;
			$ret['channel_w_tagwall']   = PERMS_SPECIFIC;
			$ret['channel_w_comment']   = PERMS_SPECIFIC;
			$ret['channel_w_mail']      = PERMS_SPECIFIC;
			$ret['channel_w_photos']    = 0;
			$ret['channel_w_chat']      = PERMS_SPECIFIC;
			$ret['channel_a_delegate']  = 0;
			$ret['channel_r_storage']   = PERMS_PUBLIC;
			$ret['channel_w_storage']   = 0;
			$ret['channel_r_pages']     = PERMS_PUBLIC;
			$ret['channel_w_pages']     = 0;
			$ret['channel_a_republish'] = PERMS_SPECIFIC;
			$ret['channel_w_like']      = PERMS_NETWORK;

			break;

		case 'forum_restricted':
			$ret['perms_auto'] = false;
			$ret['default_collection'] = true;
			$ret['directory_publish'] = true;
			$ret['online'] = false;
			$ret['perms_follow'] = PERMS_R_STREAM|PERMS_R_PROFILE|PERMS_R_PHOTOS|PERMS_R_ABOOK
				|PERMS_W_STREAM|PERMS_W_WALL|PERMS_W_COMMENT|PERMS_W_MAIL|PERMS_W_CHAT
				|PERMS_R_STORAGE|PERMS_R_PAGES|PERMS_W_LIKE|PERMS_W_TAGWALL;
			$ret['perms_accept'] = PERMS_R_STREAM|PERMS_R_PROFILE|PERMS_R_PHOTOS|PERMS_R_ABOOK
				|PERMS_W_STREAM|PERMS_W_WALL|PERMS_W_COMMENT|PERMS_W_MAIL|PERMS_W_CHAT
				|PERMS_R_STORAGE|PERMS_R_PAGES|PERMS_W_LIKE|PERMS_W_TAGWALL;
			$ret['channel_r_stream']    = PERMS_PUBLIC;
			$ret['channel_r_profile']   = PERMS_PUBLIC;
			$ret['channel_r_photos']    = PERMS_PUBLIC;
			$ret['channel_r_abook']     = PERMS_PUBLIC;
			$ret['channel_w_stream']    = 0;
			$ret['channel_w_wall']      = PERMS_SPECIFIC;
			$ret['channel_w_tagwall']   = PERMS_SPECIFIC;
			$ret['channel_w_comment']   = PERMS_SPECIFIC;
			$ret['channel_w_mail']      = PERMS_SPECIFIC;
			$ret['channel_w_photos']    = 0;
			$ret['channel_w_chat']      = PERMS_SPECIFIC;
			$ret['channel_a_delegate']  = 0;
			$ret['channel_r_storage']   = PERMS_PUBLIC;
			$ret['channel_w_storage']   = 0;
			$ret['channel_r_pages']     = PERMS_PUBLIC;
			$ret['channel_w_pages']     = 0;
			$ret['channel_a_republish'] = PERMS_SPECIFIC;
			$ret['channel_w_like']      = PERMS_SPECIFIC;

			break;

		case 'forum_private':
			$ret['perms_auto'] = false;
			$ret['default_collection'] = true;
			$ret['directory_publish'] = false;
			$ret['online'] = false;
			$ret['perms_follow'] = PERMS_R_STREAM|PERMS_R_PROFILE|PERMS_R_PHOTOS|PERMS_R_ABOOK
				|PERMS_W_STREAM|PERMS_W_WALL|PERMS_W_COMMENT|PERMS_W_MAIL|PERMS_W_CHAT
				|PERMS_R_STORAGE|PERMS_R_PAGES|PERMS_W_LIKE;
			$ret['perms_accept'] = PERMS_R_STREAM|PERMS_R_PROFILE|PERMS_R_PHOTOS|PERMS_R_ABOOK
				|PERMS_W_STREAM|PERMS_W_WALL|PERMS_W_COMMENT|PERMS_W_MAIL|PERMS_W_CHAT
				|PERMS_R_STORAGE|PERMS_R_PAGES|PERMS_W_LIKE;
			$ret['channel_r_stream']    = PERMS_PUBLIC;
			$ret['channel_r_profile']   = PERMS_SPECIFIC;
			$ret['channel_r_photos']    = PERMS_SPECIFIC;
			$ret['channel_r_abook']     = PERMS_SPECIFIC;
			$ret['channel_w_stream']    = 0;
			$ret['channel_w_wall']      = PERMS_SPECIFIC;
			$ret['channel_w_tagwall']   = 0;
			$ret['channel_w_comment']   = PERMS_SPECIFIC;
			$ret['channel_w_mail']      = PERMS_SPECIFIC;
			$ret['channel_w_photos']    = 0;
			$ret['channel_w_chat']      = PERMS_SPECIFIC;
			$ret['channel_a_delegate']  = 0;
			$ret['channel_r_storage']   = PERMS_SPECIFIC;
			$ret['channel_w_storage']   = 0;
			$ret['channel_r_pages']     = PERMS_SPECIFIC;
			$ret['channel_w_pages']     = 0;
			$ret['channel_a_republish'] = PERMS_SPECIFIC;
			$ret['channel_w_like']      = PERMS_SPECIFIC;

			break;

		case 'feed':
			$ret['perms_auto'] = true;
			$ret['default_collection'] = false;
			$ret['directory_publish'] = true;
			$ret['online'] = false;
			$ret['perms_follow'] = PERMS_R_STREAM|PERMS_R_PROFILE|PERMS_R_PHOTOS|PERMS_R_ABOOK
				|PERMS_W_STREAM|PERMS_W_WALL|PERMS_W_COMMENT|PERMS_W_MAIL
				|PERMS_R_STORAGE|PERMS_R_PAGES|PERMS_A_REPUBLISH|PERMS_W_LIKE;
			$ret['perms_accept'] = PERMS_R_STREAM|PERMS_R_PROFILE|PERMS_R_PHOTOS|PERMS_R_ABOOK
				|PERMS_W_STREAM|PERMS_W_WALL|PERMS_W_COMMENT|PERMS_W_MAIL
				|PERMS_R_STORAGE|PERMS_R_PAGES|PERMS_A_REPUBLISH|PERMS_W_LIKE;
			$ret['channel_r_stream']    = PERMS_PUBLIC;
			$ret['channel_r_profile']   = PERMS_PUBLIC;
			$ret['channel_r_photos']    = PERMS_PUBLIC;
			$ret['channel_r_abook']     = PERMS_PUBLIC;
			$ret['channel_w_stream']    = PERMS_SPECIFIC;
			$ret['channel_w_wall']      = PERMS_SPECIFIC;
			$ret['channel_w_tagwall']   = PERMS_SPECIFIC;
			$ret['channel_w_comment']   = PERMS_SPECIFIC;
			$ret['channel_w_mail']      = PERMS_SPECIFIC;
			$ret['channel_w_photos']    = 0;
			$ret['channel_w_chat']      = PERMS_SPECIFIC;
			$ret['channel_a_delegate']  = 0;
			$ret['channel_r_storage']   = PERMS_PUBLIC;
			$ret['channel_w_storage']   = 0;
			$ret['channel_r_pages']     = PERMS_PUBLIC;
			$ret['channel_w_pages']     = 0;
			$ret['channel_a_republish'] = PERMS_NETWORK;
			$ret['channel_w_like']      = PERMS_NETWORK;

			break;

		case 'feed_restricted':
			$ret['perms_auto'] = false;
			$ret['default_collection'] = true;
			$ret['directory_publish'] = false;
			$ret['online'] = false;
			$ret['perms_follow'] = PERMS_R_STREAM|PERMS_R_PROFILE|PERMS_R_PHOTOS|PERMS_R_ABOOK
				|PERMS_W_STREAM|PERMS_W_WALL|PERMS_W_COMMENT|PERMS_W_MAIL
				|PERMS_R_STORAGE|PERMS_R_PAGES|PERMS_W_LIKE;
			$ret['perms_accept'] = PERMS_R_STREAM|PERMS_R_PROFILE|PERMS_R_PHOTOS|PERMS_R_ABOOK
				|PERMS_W_STREAM|PERMS_W_WALL|PERMS_W_COMMENT|PERMS_W_MAIL
				|PERMS_R_STORAGE|PERMS_R_PAGES|PERMS_W_LIKE;
			$ret['channel_r_stream']    = PERMS_PUBLIC;
			$ret['channel_r_profile']   = PERMS_PUBLIC;
			$ret['channel_r_photos']    = PERMS_PUBLIC;
			$ret['channel_r_abook']     = PERMS_PUBLIC;
			$ret['channel_w_stream']    = PERMS_SPECIFIC;
			$ret['channel_w_wall']      = PERMS_SPECIFIC;
			$ret['channel_w_tagwall']   = PERMS_SPECIFIC;
			$ret['channel_w_comment']   = PERMS_SPECIFIC;
			$ret['channel_w_mail']      = PERMS_SPECIFIC;
			$ret['channel_w_photos']    = 0;
			$ret['channel_w_chat']      = PERMS_SPECIFIC;
			$ret['channel_a_delegate']  = 0;
			$ret['channel_r_storage']   = PERMS_PUBLIC;
			$ret['channel_w_storage']   = 0;
			$ret['channel_r_pages']     = PERMS_PUBLIC;
			$ret['channel_w_pages']     = 0;
			$ret['channel_a_republish'] = PERMS_SPECIFIC;
			$ret['channel_w_like']      = PERMS_NETWORK;

			break;

		case 'soapbox':
			$ret['perms_auto'] = true;
			$ret['default_collection'] = false;
			$ret['directory_publish'] = true;
			$ret['online'] = false;
			$ret['perms_follow'] = PERMS_R_STREAM|PERMS_R_PROFILE|PERMS_R_PHOTOS|PERMS_R_ABOOK
				|PERMS_R_STORAGE|PERMS_R_PAGES|PERMS_A_REPUBLISH|PERMS_W_LIKE;
			$ret['perms_accept'] = PERMS_R_STREAM|PERMS_R_PROFILE|PERMS_R_PHOTOS|PERMS_R_ABOOK
				|PERMS_R_STORAGE|PERMS_R_PAGES|PERMS_A_REPUBLISH|PERMS_W_LIKE;
			$ret['channel_r_stream']    = PERMS_PUBLIC;
			$ret['channel_r_profile']   = PERMS_PUBLIC;
			$ret['channel_r_photos']    = PERMS_PUBLIC;
			$ret['channel_r_abook']     = PERMS_PUBLIC;
			$ret['channel_w_stream']    = 0;
			$ret['channel_w_wall']      = 0;
			$ret['channel_w_tagwall']   = 0;
			$ret['channel_w_comment']   = 0;
			$ret['channel_w_mail']      = 0;
			$ret['channel_w_photos']    = 0;
			$ret['channel_w_chat']      = 0;
			$ret['channel_a_delegate']  = 0;
			$ret['channel_r_storage']   = PERMS_PUBLIC;
			$ret['channel_w_storage']   = 0;
			$ret['channel_r_pages']     = PERMS_PUBLIC;
			$ret['channel_w_pages']     = 0;
			$ret['channel_a_republish'] = PERMS_SPECIFIC;
			$ret['channel_w_like']      = PERMS_NETWORK;

			break;

		case 'repository':
			$ret['perms_auto'] = true;
			$ret['default_collection'] = false;
			$ret['directory_publish'] = true;
			$ret['online'] = false;
			$ret['perms_follow'] = PERMS_R_STREAM|PERMS_R_PROFILE|PERMS_R_PHOTOS|PERMS_R_ABOOK
				|PERMS_W_STREAM|PERMS_W_WALL|PERMS_W_COMMENT|PERMS_W_MAIL|PERMS_W_CHAT
				|PERMS_R_STORAGE|PERMS_W_STORAGE|PERMS_R_PAGES|PERMS_A_REPUBLISH|PERMS_W_LIKE|PERMS_W_TAGWALL;
			$ret['perms_accept'] = PERMS_R_STREAM|PERMS_R_PROFILE|PERMS_R_PHOTOS|PERMS_R_ABOOK
				|PERMS_W_STREAM|PERMS_W_WALL|PERMS_W_COMMENT|PERMS_W_MAIL|PERMS_W_CHAT
				|PERMS_R_STORAGE|PERMS_W_STORAGE|PERMS_R_PAGES|PERMS_A_REPUBLISH|PERMS_W_LIKE|PERMS_W_TAGWALL;
			$ret['channel_r_stream']    = PERMS_PUBLIC;
			$ret['channel_r_profile']   = PERMS_PUBLIC;
			$ret['channel_r_photos']    = PERMS_PUBLIC; 			
			$ret['channel_r_abook']     = PERMS_PUBLIC;
			$ret['channel_w_stream']    = PERMS_SPECIFIC;
			$ret['channel_w_wall']      = PERMS_SPECIFIC;
			$ret['channel_w_tagwall']   = PERMS_SPECIFIC;
			$ret['channel_w_comment']   = PERMS_SPECIFIC;
			$ret['channel_w_mail']      = PERMS_SPECIFIC;
			$ret['channel_w_photos']    = PERMS_SPECIFIC;
			$ret['channel_w_chat']      = PERMS_SPECIFIC;
			$ret['channel_a_delegate']  = 0;
			$ret['channel_r_storage']   = PERMS_PUBLIC;
			$ret['channel_w_storage']   = PERMS_SPECIFIC;
			$ret['channel_r_pages']     = PERMS_PUBLIC;
			$ret['channel_w_pages']     = PERMS_SPECIFIC;
			$ret['channel_a_republish'] = PERMS_SPECIFIC;
			$ret['channel_w_like']      = PERMS_NETWORK;

			break;

		default:
			break;
	}

	$x = get_config('system','role_perms');
	// let system settings over-ride any or all 
	if($x && is_array($x) && array_key_exists($role,$x))
		$ret = array_merge($ret,$x[$role]);

	call_hooks('get_role_perms',$ret);

	return $ret;
}

/**
 * @brief Returns a list or roles, grouped by type
 *
 * @param string $current The current role
 * @return string Returns an array of roles, grouped by type
 */
function get_roles() {
	$roles = array(
		t('Social Networking') => array('social' => t('Mostly Public'), 'social_restricted' => t('Restricted'), 'social_private' => t('Private')),
		t('Community Forum') => array('forum' => t('Mostly Public'), 'forum_restricted' => t('Restricted'), 'forum_private' => t('Private')),
		t('Feed Republish') => array('feed' => t('Mostly Public'), 'feed_restricted' => t('Restricted')),
		t('Special Purpose') => array('soapbox' => t('Celebrity/Soapbox'), 'repository' => t('Group Repository')),
		t('Other') => array('custom' => t('Custom/Expert Mode')));

	return $roles;
}
