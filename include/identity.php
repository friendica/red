<?php /** @file */

require_once('include/zot.php');
require_once('include/crypto.php');


/**
 * @function identity_check_service_class($account_id)
 *     Called when creating a new channel. Checks the account's service class and number
 * of current channels to determine whether creating a new channel is within the current
 * service class constraints.
 *
 * @param int $account_id
 *     Account_id used for this request
 *
 * @returns array
 *       'success' => boolean true if creating a new channel is allowed for this account
 *       'message' => if success is false, optional error text
 */
 

function identity_check_service_class($account_id) {
	$ret = array('success' => false, $message => '');
	
	$r = q("select count(channel_id) as total from channel where channel_account_id = %d and not ( channel_pageflags & %d )>0 ",
		intval($account_id),
		intval(PAGE_REMOVED)
	);
	if(! ($r && count($r))) {
		$ret['total_identities'] = 0;
		$ret['message'] = t('Unable to obtain identity information from database');
		return $ret;
	} 

	$ret['total_identities'] = intval($r[0]['total']);

	if(! account_service_class_allows($account_id,'total_identities',$r[0]['total'])) {
		$result['message'] .= upgrade_message();
		return $result;
	}

	$ret['success'] = true;
	return $ret;
}


/**
 * @function validate_channelname($name)
 *     Determine if the channel name is allowed when creating a new channel.
 * This action is pluggable.
 *
 * @param string $name
 *
 * @returns nil return if name is valid, or string describing the error state.
 *
 * We're currently only checking for an empty name or one that exceeds our storage limit (255 chars).
 * 255 chars is probably going to create a mess on some pages. 
 * Plugins can set additional policies such as full name requirements, character sets, multi-byte
 * length, etc. 
 *
 */

function validate_channelname($name) {

	if(! $name)
		return t('Empty name');
	if(strlen($name) > 255)
		return t('Name too long');
	$arr = array('name' => $name);
	call_hooks('validate_channelname',$arr);
	if(x($arr,'message'))
		return $arr['message'];
	return;
}


/**
 * @function create_sys_channel()
 *     Create a system channel - which has no account attached
 *
 */

function create_sys_channel() {
	if(get_sys_channel())
		return;

    // Ensure that there is a host keypair.

    if((! get_config('system','pubkey')) && (! get_config('system','prvkey'))) {
		require_once('include/crypto.php');
        $hostkey = new_keypair(4096);
        set_config('system','pubkey',$hostkey['pubkey']);
        set_config('system','prvkey',$hostkey['prvkey']);
    }

	create_identity(array(
		'account_id' => 'xxx',  // This will create an identity with an (integer) account_id of 0, but account_id is required
		'nickname' => 'sys',
		'name' => 'System',
		'pageflags' => PAGE_SYSTEM,
		'publish' => 0,
		'xchanflags' => XCHAN_FLAGS_SYSTEM
	));
}

function get_sys_channel() {
	$r = q("select * from channel left join xchan on channel_hash = xchan_hash where (channel_pageflags & %d)>0 limit 1",
		intval(PAGE_SYSTEM)
	);
	if($r)
		return $r[0];
	return false;
}

function is_sys_channel($channel_id) {
	$r = q("select channel_pageflags from channel where channel_id = %d limit 1",
		intval($channel_id)
	);
	if(($r) && ($r[0]['channel_pageflags'] & PAGE_SYSTEM))
		return true;
	return false;
}


/**
 * @channel_total()
 *   Return the total number of channels on this site. No filtering is performed except to check PAGE_REMOVED
 *
 * @returns int 
 *   on error returns boolean false
 *
 */

function channel_total() {
	$r = q("select channel_id from channel where not ( channel_pageflags & %d )>0",
		intval(PAGE_REMOVED)
	);

	if(is_array($r))
		return count($r);
	return false;
}


/**
 * @function create_identity($arr)
 *     Create a new channel
 * Also creates the related xchan, hubloc, profile, and "self" abook records, and an 
 * empty "Friends" group/collection for the new channel
 *
 * @param array $arr
 *       'name'       => full name of channel
 *       'nickname'   => "email/url-compliant" nickname
 *       'account_id' => account_id to attach with this channel
 *       [other identity fields as desired]
 *
 * @returns array
 *     'success' => boolean true or false
 *     'message' => optional error text if success is false
 *     'channel' => if successful the created channel array
 */
 
function create_identity($arr) {

	$a = get_app();
	$ret = array('success' => false);

	if(! $arr['account_id']) {
		$ret['message'] = t('No account identifier');
		return $ret;
	}
	$ret = identity_check_service_class($arr['account_id']);
	if (!$ret['success']) { 
		return $ret;
	}
	// save this for auto_friending
	$total_identities = $ret['total_identities'];


	$nick = mb_strtolower(trim($arr['nickname']));
	if(! $nick) {
		$ret['message'] = t('Nickname is required.');
		return $ret;
	}

	$name = escape_tags($arr['name']);
	$pageflags = ((x($arr,'pageflags')) ? intval($arr['pageflags']) : PAGE_NORMAL);
	$xchanflags = ((x($arr,'xchanflags')) ? intval($arr['xchanflags']) : XCHAN_FLAGS_NORMAL);
	$name_error = validate_channelname($arr['name']);
	if($name_error) {
		$ret['message'] = $name_error;
		return $ret;
	}

	if($nick === 'sys' && (! ($pageflags & PAGE_SYSTEM))) {
		$ret['message'] = t('Reserved nickname. Please choose another.');
		return $ret;
	}

	if(check_webbie(array($nick)) !== $nick) {
		$ret['message'] = t('Nickname has unsupported characters or is already being used on this site.');
		return $ret;
	}

	$guid = zot_new_uid($nick);
	$key = new_keypair(4096);


	$sig = base64url_encode(rsa_sign($guid,$key['prvkey']));
	$hash = make_xchan_hash($guid,$sig);

	// Force a few things on the short term until we can provide a theme or app with choice

	$publish = 1;

	if(array_key_exists('publish', $arr))
		$publish = intval($arr['publish']);

	$primary = true;
		
	if(array_key_exists('primary', $arr))
		$primary = intval($arr['primary']);


	$perms_sql = '';

	$role_permissions = null;
	$global_perms = get_perms();

	if(array_key_exists('permissions_role',$arr) && $arr['permissions_role']) {
		$role_permissions = get_role_perms($arr['permissions_role']);

		if($role_permissions) {
			foreach($role_permissions as $p => $v) {
				if(strpos($p,'channel_') !== false) {
					$perms_keys .= ', ' . $p;
					$perms_vals .= ', ' . intval($v);
				}
				if($p === 'directory_publish')
					$publish = intval($v);
			}
		}
	}
	else {
		$defperms = site_default_perms();
		foreach($defperms as $p => $v) {
			$perms_keys .= ', ' . $global_perms[$p][0];
			$perms_vals .= ', ' . intval($v);
		}
	}


	$expire = get_config('system', 'default_expire_days');
	$expire = (($expire===false)? '0': $expire);

	$r = q("insert into channel ( channel_account_id, channel_primary, 
		channel_name, channel_address, channel_guid, channel_guid_sig,
		channel_hash, channel_prvkey, channel_pubkey, channel_pageflags, channel_expire_days, channel_timezone $perms_keys )
		values ( %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, '%s' $perms_vals ) ",

		intval($arr['account_id']),
		intval($primary),
		dbesc($name),
		dbesc($nick),
		dbesc($guid),
		dbesc($sig),
		dbesc($hash),
		dbesc($key['prvkey']),
		dbesc($key['pubkey']),
		intval($pageflags),
		intval($expire),
		dbesc($a->timezone)
	);
			

	$r = q("select * from channel where channel_account_id = %d 
		and channel_guid = '%s' limit 1",
		intval($arr['account_id']),
		dbesc($guid)
	);

	if(! $r) {
		$ret['message'] = t('Unable to retrieve created identity');
		return $ret;
	}
	
	$ret['channel'] = $r[0];

	if(intval($arr['account_id']))
		set_default_login_identity($arr['account_id'],$ret['channel']['channel_id'],false);

	// Create a verified hub location pointing to this site.

	$r = q("insert into hubloc ( hubloc_guid, hubloc_guid_sig, hubloc_hash, hubloc_addr, hubloc_flags, 
		hubloc_url, hubloc_url_sig, hubloc_host, hubloc_callback, hubloc_sitekey, hubloc_network )
		values ( '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s' )",
		dbesc($guid),
		dbesc($sig),
		dbesc($hash),
		dbesc($ret['channel']['channel_address'] . '@' . get_app()->get_hostname()),
		intval(($primary) ? HUBLOC_FLAGS_PRIMARY : 0),
		dbesc(z_root()),
		dbesc(base64url_encode(rsa_sign(z_root(),$ret['channel']['channel_prvkey']))),
		dbesc(get_app()->get_hostname()),
		dbesc(z_root() . '/post'),
		dbesc(get_config('system','pubkey')),
		dbesc('zot')
	);
	if(! $r)
		logger('create_identity: Unable to store hub location');


	$newuid = $ret['channel']['channel_id'];

	$r = q("insert into xchan ( xchan_hash, xchan_guid, xchan_guid_sig, xchan_pubkey, xchan_photo_l, xchan_photo_m, xchan_photo_s, xchan_addr, xchan_url, xchan_follow, xchan_connurl, xchan_name, xchan_network, xchan_photo_date, xchan_name_date, xchan_flags ) values ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d)",
		dbesc($hash),
		dbesc($guid),
		dbesc($sig),
		dbesc($key['pubkey']),
		dbesc($a->get_baseurl() . "/photo/profile/l/{$newuid}"),
		dbesc($a->get_baseurl() . "/photo/profile/m/{$newuid}"),
		dbesc($a->get_baseurl() . "/photo/profile/s/{$newuid}"),
		dbesc($ret['channel']['channel_address'] . '@' . get_app()->get_hostname()),
		dbesc(z_root() . '/channel/' . $ret['channel']['channel_address']),
		dbesc(z_root() . '/follow?f=&url=%s'),
		dbesc(z_root() . '/poco/' . $ret['channel']['channel_address']),
		dbesc($ret['channel']['channel_name']),
		dbesc('zot'),
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		intval($xchanflags)
	);

	// Not checking return value. 
	// It's ok for this to fail if it's an imported channel, and therefore the hash is a duplicate
		

	$r = q("INSERT INTO profile ( aid, uid, profile_guid, profile_name, is_default, publish, name, photo, thumb)
		VALUES ( %d, %d, '%s', '%s', %d, %d, '%s', '%s', '%s') ",
		intval($ret['channel']['channel_account_id']),
		intval($newuid),
		dbesc(random_string()),
		t('Default Profile'),
		1,
		$publish,
		dbesc($ret['channel']['channel_name']),
		dbesc($a->get_baseurl() . "/photo/profile/l/{$newuid}"),
		dbesc($a->get_baseurl() . "/photo/profile/m/{$newuid}")
	);

	if($role_permissions) {
		$myperms = ((array_key_exists('perms_auto',$role_permissions) && $role_permissions['perms_auto']) ? intval($role_permissions['perms_accept']) : 0);
	}
	else
		$myperms = PERMS_R_STREAM|PERMS_R_PROFILE|PERMS_R_PHOTOS|PERMS_R_ABOOK
			|PERMS_W_STREAM|PERMS_W_WALL|PERMS_W_COMMENT|PERMS_W_MAIL|PERMS_W_CHAT
			|PERMS_R_STORAGE|PERMS_R_PAGES|PERMS_W_LIKE;

	$r = q("insert into abook ( abook_account, abook_channel, abook_xchan, abook_closeness, abook_created, abook_updated, abook_flags, abook_my_perms )
		values ( %d, %d, '%s', %d, '%s', '%s', %d, %d ) ",
		intval($ret['channel']['channel_account_id']),
		intval($newuid),
		dbesc($hash),
		intval(0),
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		intval(ABOOK_FLAG_SELF),
		intval($myperms)
	);

	if(intval($ret['channel']['channel_account_id'])) {

		// Save our permissions role so we can perhaps call it up and modify it later.

		if($role_permissions) {
			set_pconfig($newuid,'system','permissions_role',$arr['permissions_role']);
			if(array_key_exists('online',$role_permissions))
				set_pconfig($newuid,'system','hide_presence',1-intval($role_permissions['online']));
			if(array_key_exists('perms_auto',$role_permissions))
				set_pconfig($newuid,'system','autoperms',(($role_permissions['perms_auto']) ? $role_permissions['perms_accept'] : 0));
		}

		// Create a group with yourself as a member. This allows somebody to use it 
		// right away as a default group for new contacts. 

		require_once('include/group.php');
		group_add($newuid, t('Friends'));
		group_add_member($newuid,t('Friends'),$ret['channel']['channel_hash']);

		// if our role_permissions indicate that we're using a default collection ACL, add it.

		if(is_array($role_permissions) && $role_permissions['default_collection']) {
			$r = q("select hash from groups where uid = %d and name = '%s' limit 1",
				intval($newuid),
				dbesc( t('Friends') )
			);
			if($r) {
				q("update channel set channel_default_group = '%s', channel_allow_gid = '%s' where channel_id = %d",
					dbesc($r[0]['hash']),
					dbesc('<' . $r[0]['hash'] . '>'),
					intval($newuid)
				);
			}
		}

		// auto-follow any of the hub's pre-configured channel choices.
		// Only do this if it's the first channel for this account;
		// otherwise it could get annoying. Don't make this list too big
		// or it will impact registration time.

		$accts = get_config('system','auto_follow');
		if(($accts) && (! $total_identities)) {
			require_once('include/follow.php');
			if(! is_array($accts))
				$accts = array($accts);
			foreach($accts as $acct) {
				if(trim($acct))
					new_contact($newuid,trim($acct),$ret['channel'],false);
			}
		}

		call_hooks('register_account', $newuid);
	
		proc_run('php','include/directory.php', $ret['channel']['channel_id']);
	}

	$ret['success'] = true;
	return $ret;

}


/**
 * @function set_default_login_identity($account_id, $channel_id, $force = true)
 *       Set default channel to be used on login
 *
 * @param int $account_id
 *       login account
 * @param int $channel_id
 *       channel id to set as default for this account
 * @param boolean force
 *       if true, set this default unconditionally
 *       if $force is false only do this if there is no existing default
 * 
 * @returns nil
 */

function set_default_login_identity($account_id,$channel_id,$force = true) {
	$r = q("select account_default_channel from account where account_id = %d limit 1",
		intval($account_id)
	);
	if($r) {
		if((intval($r[0]['account_default_channel']) == 0) || ($force)) {
			$r = q("update account set account_default_channel = %d where account_id = %d",
				intval($channel_id),
				intval($account_id)
			);
		}
	}
}

/**
 * @function identity_basic_export($channel_id,$items = false)
 *     Create an array representing the important channel information
 * which would be necessary to create a nomadic identity clone. This includes
 * most channel resources and connection information with the exception of content.
 *
 * @param int $channel_id
 *     Channel_id to export
 * @param boolean $items
 *     Include channel posts (wall items), default false
 *
 * @returns array
 *     See function for details
 *
 */

function identity_basic_export($channel_id, $items = false) {

	/*
	 * Red basic channel export
	 */

	$ret = array();

	$ret['compatibility'] = array('project' => RED_PLATFORM, 'version' => RED_VERSION, 'database' => DB_UPDATE_VERSION);

	$r = q("select * from channel where channel_id = %d limit 1",
		intval($channel_id)
	);
	if($r)
		$ret['channel'] = $r[0];

	$r = q("select * from profile where uid = %d",
		intval($channel_id)
	);
	if($r)
		$ret['profile'] = $r;

	$xchans = array();
	$r = q("select * from abook where abook_channel = %d ",
		intval($channel_id)
	);
	if($r) {
		$ret['abook'] = $r;

		foreach($r as $rr)
			$xchans[] = $rr['abook_xchan'];
		stringify_array_elms($xchans);
	}

	if($xchans) {
		$r = q("select * from xchan where xchan_hash in ( " . implode(',',$xchans) . " ) ");
		if($r)
			$ret['xchan'] = $r;
		
		$r = q("select * from hubloc where hubloc_hash in ( " . implode(',',$xchans) . " ) ");
		if($r)
			$ret['hubloc'] = $r;
	}

	$r = q("select * from `groups` where uid = %d ",
		intval($channel_id)
	);

	if($r)
		$ret['group'] = $r;

	$r = q("select * from group_member where uid = %d ",
		intval($channel_id)
	);
	if($r)
		$ret['group_member'] = $r;

	$r = q("select * from pconfig where uid = %d",
		intval($channel_id)
	);
	if($r)
		$ret['config'] = $r;


	$r = q("select type, data from photo where scale = 4 and profile = 1 and uid = %d limit 1",
		intval($channel_id)
	);

	if($r) {
		$ret['photo'] = array('type' => $r[0]['type'], 'data' => base64url_encode($r[0]['data']));
	}


	// All other term types will be included in items, if requested.

	$r = q("select * from term where type in (%d,%d) and uid = %d",
		intval(TERM_SAVEDSEARCH),
		intval(TERM_THING),
		intval($channel_id)
	);
	if($r)
		$ret['term'] = $r;

	$r = q("select * from obj where obj_channel = %d",
		intval($channel_id)
	);

	if($r)
		$ret['obj'] = $r;


	if(! $items)
		return $ret;


	$r = q("select likes.*, item.mid from likes left join item on likes.iid = item.id where likes.channel_id = %d",
		intval($channel_id)
	);

	if($r)
		$ret['likes'] = $r;


	$r = q("select item_id.*, item.mid from item_id left join item on item_id.iid = item.id where item_id.uid = %d",
		intval($channel_id)
	);
	
	if($r)
		$ret['item_id'] = $r;	

	$key = get_config('system','prvkey');

	// warning: this may run into memory limits on smaller systems

	$r = q("select * from item where (item_flags & %d)>0 and not (item_restrict & %d)>0 and uid = %d",
		intval(ITEM_WALL),
		intval(ITEM_DELETED),
		intval($channel_id)
	);
	if($r) {
		$ret['item'] = array();
		xchan_query($r);
		$r = fetch_post_tags($r,true);
		foreach($r as $rr)
			$ret['item'][] = encode_item($rr,true);

	}
	return $ret;

}



/**
 *
 * @function : profile_load(&$a, $nickname, $profile)
 *     Generate
 * @param App $a
 * @param string $nickname
 * @param string $profile
 *
 * Summary: Loads a profile into the App structure.
 * The function requires a writeable copy of the main App structure, and the nickname
 * of a valid channel.
 *
 * Permissions of the current observer are checked. If a restricted profile is available
 * to the current observer, that will be loaded instead of the channel default profile.
 * 
 * The channel owner can set $profile to a valid profile_guid to preview that profile.
 *
 * The channel default theme is also selected for use, unless over-riden elsewhere.
 *
 */


function profile_load(&$a, $nickname, $profile = '') {

	logger('profile_load: ' . $nickname . (($profile) ? ' profile: ' . $profile : ''));

	$user = q("select channel_id from channel where channel_address = '%s' limit 1",
		dbesc($nickname)
	);
		
	if(! $user) {
		logger('profile error: ' . $a->query_string, LOGGER_DEBUG);
		notice( t('Requested channel is not available.') . EOL );
		$a->error = 404;
		return;
	}

	// get the current observer
	$observer = $a->get_observer();

	$can_view_profile = true;

	// Can the observer see our profile?
	require_once('include/permissions.php');
	if(! perm_is_allowed($user[0]['channel_id'],$observer['xchan_hash'],'view_profile')) {
		$can_view_profile = false;
	}

	if(! $profile) {
		$r = q("SELECT abook_profile FROM abook WHERE abook_xchan = '%s' and abook_channel = '%d' limit 1",
			dbesc($observer['xchan_hash']),
			intval($user[0]['channel_id'])
		);
		if($r)
			$profile = $r[0]['abook_profile'];
	}
	$p = null;

	if($profile) {
		$p = q("SELECT profile.uid AS profile_uid, profile.*, channel.* FROM profile
				LEFT JOIN channel ON profile.uid = channel.channel_id
				WHERE channel.channel_address = '%s' AND profile.profile_guid = '%s' LIMIT 1",
				dbesc($nickname),
				dbesc($profile)
		);
	}

	if(! $p) {
		$p = q("SELECT profile.uid AS profile_uid, profile.*, channel.* FROM profile
			LEFT JOIN channel ON profile.uid = channel.channel_id
			WHERE channel.channel_address = '%s' and not ( channel_pageflags & %d )>0 
			AND profile.is_default = 1 LIMIT 1",
			dbesc($nickname),
			intval(PAGE_REMOVED)
		);
	}


	if(! $p) {
		logger('profile error: ' . $a->query_string, LOGGER_DEBUG);
		notice( t('Requested profile is not available.') . EOL );
		$a->error = 404;
		return;
	}

	$q = q("select * from profext where hash = '%s' and channel_id = %d",
		dbesc($p[0]['profile_guid']),
		intval($p[0]['profile_uid'])
	);
	if($q) {

		$extra_fields = array();

		require_once('include/identity.php');
		$profile_fields_basic    = get_profile_fields_basic();
		$profile_fields_advanced = get_profile_fields_advanced();

		$advanced = ((feature_enabled(local_user(),'advanced_profiles')) ? true : false);
		if($advanced)
			$fields = $profile_fields_advanced;
		else
			$fields = $profile_fields_basic;

		foreach($q as $qq) {
			foreach($fields as $k => $f) {
				if($k == $qq['k']) {
					$p[0][$k] = $qq['v'];
					$extra_fields[] = $k;
					break;
				}
			}
		}
	}

	$p[0]['extra_fields'] = $extra_fields;

	$z = q("select xchan_photo_date from xchan where xchan_hash = '%s' limit 1",
		dbesc($p[0]['channel_hash'])
	);
	if($z)
		$p[0]['picdate'] = $z[0]['xchan_photo_date'];

	
	// fetch user tags if this isn't the default profile

	if(! $p[0]['is_default']) {
		$x = q("select `keywords` from `profile` where uid = %d and `is_default` = 1 limit 1",
				intval($profile_uid)
		);
		if($x && $can_view_profile)
			$p[0]['keywords'] = $x[0]['keywords'];
	}

	if($p[0]['keywords']) {
		$keywords = str_replace(array('#',',',' ',',,'),array('',' ',',',','),$p[0]['keywords']);
		if(strlen($keywords) && $can_view_profile)
			$a->page['htmlhead'] .= '<meta name="keywords" content="' . htmlentities($keywords,ENT_COMPAT,'UTF-8') . '" />' . "\r\n" ;

	}

	$a->profile = $p[0];
	$a->profile_uid = $p[0]['profile_uid'];
	$a->page['title'] = $a->profile['channel_name'] . " - " . $a->profile['channel_address'] . "@" . $a->get_hostname();

	$a->profile['permission_to_view'] = $can_view_profile;

	if($can_view_profile) {
		$online = get_online_status($nickname);
		$a->profile['online_status'] = $online['result'];

	}

	if(local_user()) {
		$a->profile['channel_mobile_theme'] = get_pconfig(local_user(),'system', 'mobile_theme');
		$_SESSION['mobile_theme'] = $a->profile['channel_mobile_theme'];
	}

	/**
	 * load/reload current theme info
	 */

	$_SESSION['theme'] = $p[0]['channel_theme'];

//	$a->set_template_engine(); // reset the template engine to the default in case the user's theme doesn't specify one

//	$theme_info_file = "view/theme/".current_theme()."/php/theme.php";
//	if (file_exists($theme_info_file)){
//		require_once($theme_info_file);
//	}

	return;
}

function profile_create_sidebar(&$a,$connect = true) {

	$block = (((get_config('system','block_public')) && (! local_user()) && (! remote_user())) ? true : false);

	$a->set_widget('profile',profile_sidebar($a->profile, $block, $connect));
	return;
}


/**
 *
 * Function: profile_sidebar
 *
 * Formats a profile for display in the sidebar.
 * It is very difficult to templatise the HTML completely
 * because of all the conditional logic.
 *
 * @parameter: array $profile
 *
 * Returns HTML string stuitable for sidebar inclusion
 * Exceptions: Returns empty string if passed $profile is wrong type or not populated
 *
 */



function profile_sidebar($profile, $block = 0, $show_connect = true) {

	$a = get_app();

	$observer = $a->get_observer();

	$o = '';
	$location = false;
	$address = false;
	$pdesc = true;

	if((! is_array($profile)) && (! count($profile)))
		return $o;


	head_set_icon($profile['thumb']);

	$is_owner = (($profile['uid'] == local_user()) ? true : false);

	$profile['picdate'] = urlencode($profile['picdate']);

	call_hooks('profile_sidebar_enter', $profile);

	require_once('include/Contact.php');

	if($show_connect) {

		// This will return an empty string if we're already connected.

		$connect_url = rconnect_url($profile['uid'],get_observer_hash());
		$connect = (($connect_url) ? t('Connect') : '');
		if($connect_url) 
			$connect_url = sprintf($connect_url,urlencode($profile['channel_address'] . '@' . $a->get_hostname()));

		// premium channel - over-ride

		if($profile['channel_pageflags'] & PAGE_PREMIUM)
			$connect_url = z_root() . '/connect/' . $profile['channel_address'];
	}

	// show edit profile to yourself
	if($is_owner) {

		$profile['menu'] = array(
			'chg_photo' => t('Change profile photo'),
			'entries' => array(),
		);


		$multi_profiles = feature_enabled(local_user(), 'multi_profiles');
		if($multi_profiles) {
			$profile['edit'] = array($a->get_baseurl(). '/profiles', t('Profiles'),"", t('Manage/edit profiles'));
			$profile['menu']['cr_new'] = t('Create New Profile');
		}
		else
			$profile['edit'] = array($a->get_baseurl() . '/profiles/' . $profile['id'], t('Edit Profile'),'',t('Edit Profile'));
						
		$r = q("SELECT * FROM `profile` WHERE `uid` = %d",
				local_user());
		

		if($r) {
			foreach($r as $rr) {
				if(!($multi_profiles || $rr['is_default']))
					 continue;
				$profile['menu']['entries'][] = array(
					'photo'                => $rr['thumb'],
					'id'                   => $rr['id'],
					'alt'                  => t('Profile Image'),
					'profile_name'         => $rr['profile_name'],
					'isdefault'            => $rr['is_default'],
					'visible_to_everybody' => t('visible to everybody'),
					'edit_visibility'      => t('Edit visibility'),
				);
			}
		}
	}


	if((x($profile,'address') == 1)
		|| (x($profile,'locality') == 1)
		|| (x($profile,'region') == 1)
		|| (x($profile,'postal_code') == 1)
		|| (x($profile,'country_name') == 1))
		$location = t('Location:');

	$profile['homepage'] = linkify($profile['homepage']);

	$gender   = ((x($profile,'gender')   == 1) ? t('Gender:')   : False);
	$marital  = ((x($profile,'marital')  == 1) ? t('Status:')   : False);
	$homepage = ((x($profile,'homepage') == 1) ? t('Homepage:') : False);
	$profile['online']   = (($profile['online_status'] === 'online') ? t('Online Now') : False);
logger('online: ' . $profile['online']);

	if(! perm_is_allowed($profile['uid'],((is_array($observer)) ? $observer['xchan_hash'] : ''),'view_profile')) {
		$block = true;
	}

	if(($profile['hidewall'] && (! local_user()) && (! remote_user())) || $block ) {
		$location = $pdesc = $gender = $marital = $homepage = $online = False;
	}

	$firstname = ((strpos($profile['channel_name'],' '))
		? trim(substr($profile['channel_name'],0,strpos($profile['channel_name'],' '))) : $profile['channel_name']);
	$lastname = (($firstname === $profile['channel_name']) ? '' : trim(substr($profile['channel_name'],strlen($firstname))));

    $diaspora = array(
		'podloc'     => z_root(),
		'searchable' => (($block) ? 'false' : 'true'),
		'nickname'   => $profile['channel_address'],
		'fullname'   => $profile['channel_name'],
		'firstname'  => $firstname,
		'lastname'   => $lastname,
		'photo300'   => z_root() . '/photo/profile/300/' . $profile['uid'] . '.jpg',
		'photo100'   => z_root() . '/photo/profile/100/' . $profile['uid'] . '.jpg',
		'photo50'    => z_root() . '/photo/profile/50/'  . $profile['uid'] . '.jpg',
	);

	$contact_block = contact_block();

	$channel_menu = false;
	$menu = get_pconfig($profile['uid'],'system','channel_menu');
	if($menu && ! $block) {
		require_once('include/menu.php');
		$m = menu_fetch($menu,$profile['uid'],$observer['xchan_hash']);
		if($m)
			$channel_menu = menu_render($m);
	}
	$menublock = get_pconfig($profile['uid'],'system','channel_menublock');
	if ($menublock && (! $block)) {
		require_once('include/comanche.php');
		$channel_menu .= comanche_block($menublock);
	}

	$tpl = get_markup_template('profile_vcard.tpl');

	$o .= replace_macros($tpl, array(
		'$profile'       => $profile,
		'$connect'       => $connect,
		'$connect_url'   => $connect_url,
		'$location'      => $location,
		'$gender'        => $gender,
		'$pdesc'         => $pdesc,
		'$marital'       => $marital,
		'$homepage'      => $homepage,
		'$chanmenu'      => $channel_menu,
		'$diaspora'      => $diaspora,
		'$contact_block' => $contact_block,
	));

	$arr = array('profile' => &$profile, 'entry' => &$o);

	call_hooks('profile_sidebar', $arr);

	return $o;
}


// FIXME or remove


	function get_birthdays() {

		$a = get_app();
		$o = '';

		if(! local_user())
			return $o;

		$bd_format = t('g A l F d') ; // 8 AM Friday January 18
		$bd_short = t('F d');

		$r = q("SELECT `event`.*, `event`.`id` AS `eid`, `contact`.* FROM `event`
				LEFT JOIN `contact` ON `contact`.`id` = `event`.`cid`
				WHERE `event`.`uid` = %d AND `type` = 'birthday' AND `start` < '%s' AND `finish` > '%s'
				ORDER BY `start` ASC ",
				intval(local_user()),
				dbesc(datetime_convert('UTC','UTC','now + 6 days')),
				dbesc(datetime_convert('UTC','UTC','now'))
		);

		if($r && count($r)) {
			$total = 0;
			$now = strtotime('now');
			$cids = array();

			$istoday = false;
			foreach($r as $rr) {
				if(strlen($rr['name']))
					$total ++;
				if((strtotime($rr['start'] . ' +00:00') < $now) && (strtotime($rr['finish'] . ' +00:00') > $now))
					$istoday = true;
			}
			$classtoday = $istoday ? ' birthday-today ' : '';
			if($total) {
				foreach($r as &$rr) {
					if(! strlen($rr['name']))
						continue;

					// avoid duplicates

					if(in_array($rr['cid'],$cids))
						continue;
					$cids[] = $rr['cid'];

					$today = (((strtotime($rr['start'] . ' +00:00') < $now) && (strtotime($rr['finish'] . ' +00:00') > $now)) ? true : false);
					$sparkle = '';
					$url = $rr['url'];
					if($rr['network'] === NETWORK_DFRN) {
						$sparkle = " sparkle";
						$url = $a->get_baseurl() . '/redir/'  . $rr['cid'];
					}
	
					$rr['link'] = $url;
					$rr['title'] = $rr['name'];
					$rr['date'] = day_translate(datetime_convert('UTC', $a->timezone, $rr['start'], $rr['adjust'] ? $bd_format : $bd_short)) . (($today) ?  ' ' . t('[today]') : '');
					$rr['startime'] = Null;
					$rr['today'] = $today;
	
				}
			}
		}
		$tpl = get_markup_template("birthdays_reminder.tpl");
		return replace_macros($tpl, array(
			'$baseurl' => $a->get_baseurl(),
			'$classtoday' => $classtoday,
			'$count' => $total,
			'$event_reminders' => t('Birthday Reminders'),
			'$event_title' => t('Birthdays this week:'),
			'$events' => $r,
			'$lbr' => '{',  // raw brackets mess up if/endif macro processing
			'$rbr' => '}'

		));
	}


// FIXME


	function get_events() {

		require_once('include/bbcode.php');

		$a = get_app();

		if(! local_user())
			return $o;

		$bd_format = t('g A l F d') ; // 8 AM Friday January 18
		$bd_short = t('F d');

		$r = q("SELECT `event`.* FROM `event`
				WHERE `event`.`uid` = %d AND `type` != 'birthday' AND `start` < '%s' AND `start` > '%s'
				ORDER BY `start` ASC ",
				intval(local_user()),
				dbesc(datetime_convert('UTC','UTC','now + 6 days')),
				dbesc(datetime_convert('UTC','UTC','now - 1 days'))
		);

		if($r && count($r)) {
			$now = strtotime('now');
			$istoday = false;
			foreach($r as $rr) {
				if(strlen($rr['name']))
					$total ++;

				$strt = datetime_convert('UTC',$rr['convert'] ? $a->timezone : 'UTC',$rr['start'],'Y-m-d');
				if($strt === datetime_convert('UTC',$a->timezone,'now','Y-m-d'))
					$istoday = true;
			}
			$classtoday = (($istoday) ? 'event-today' : '');


			foreach($r as &$rr) {
				if($rr['adjust'])
					$md = datetime_convert('UTC',$a->timezone,$rr['start'],'Y/m');
				else
					$md = datetime_convert('UTC','UTC',$rr['start'],'Y/m');
				$md .= "/#link-".$rr['id'];

				$title = substr(strip_tags(bbcode($rr['desc'])),0,32) . '... ';
				if(! $title)
					$title = t('[No description]');

				$strt = datetime_convert('UTC',$rr['convert'] ? $a->timezone : 'UTC',$rr['start']);
				$today = ((substr($strt,0,10) === datetime_convert('UTC',$a->timezone,'now','Y-m-d')) ? true : false);
				
				$rr['link'] = $md;
				$rr['title'] = $title;
				$rr['date'] = day_translate(datetime_convert('UTC', $rr['adjust'] ? $a->timezone : 'UTC', $rr['start'], $bd_format)) . (($today) ?  ' ' . t('[today]') : '');
				$rr['startime'] = $strt;
				$rr['today'] = $today;
			}
		}

		$tpl = get_markup_template("events_reminder.tpl");
		return replace_macros($tpl, array(
			'$baseurl' => $a->get_baseurl(),
			'$classtoday' => $classtoday,
			'$count' => count($r),
			'$event_reminders' => t('Event Reminders'),
			'$event_title' => t('Events this week:'),
			'$events' => $r,
		));
	}


function advanced_profile(&$a) {
	require_once('include/text.php');
	if(! perm_is_allowed($a->profile['profile_uid'],get_observer_hash(),'view_profile'))
		return '';

	$o = '';

	$o .= '<h2>' . t('Profile') . '</h2>';

	if($a->profile['name']) {

		$tpl = get_markup_template('profile_advanced.tpl');
		
		$profile = array();
		
		$profile['fullname'] = array( t('Full Name:'), $a->profile['name'] ) ;
		
		if($a->profile['gender']) $profile['gender'] = array( t('Gender:'),  $a->profile['gender'] );
		
		$ob_hash = get_observer_hash();
		if($ob_hash && perm_is_allowed($a->profile['profile_uid'],$ob_hash,'post_like')) {
			$profile['canlike'] = true;
			$profile['likethis'] = t('Like this channel');
			$profile['profile_guid'] = $a->profile['profile_guid'];
		} 

		$likers = q("select liker, xchan.*  from likes left join xchan on liker = xchan_hash where channel_id = %d and target_type = '%s' and verb = '%s'",
			intval($a->profile['profile_uid']),
			dbesc(ACTIVITY_OBJ_PROFILE),
			dbesc(ACTIVITY_LIKE)
		);
		$profile['likers'] = array();
		$profile['like_count'] = count($likers);
		$profile['like_button_label'] = tt('Like','Likes',$profile['like_count'],'noun');
		if($likers) {
			foreach($likers as $l)
				$profile['likers'][] = array('name' => $l['xchan_name'],'url' => zid($l['xchan_url']));
		}

		if(($a->profile['dob']) && ($a->profile['dob'] != '0000-00-00')) {

			$val = '';

			if((substr($a->profile['dob'],5,2) === '00') || (substr($a->profile['dob'],8,2) === '00'))
				$val = substr($a->profile['dob'],0,4);
		
			$year_bd_format = t('j F, Y');
			$short_bd_format = t('j F');

			if(! $val) {
				$val = ((intval($a->profile['dob'])) 
					? day_translate(datetime_convert('UTC','UTC',$a->profile['dob'] . ' 00:00 +00:00',$year_bd_format))
					: day_translate(datetime_convert('UTC','UTC','2001-' . substr($a->profile['dob'],5) . ' 00:00 +00:00',$short_bd_format)));
			}
			$profile['birthday'] = array( t('Birthday:'), $val);

		}

		if($age = age($a->profile['dob'],$a->profile['timezone'],''))  $profile['age'] = array( t('Age:'), $age );
			

		if($a->profile['marital']) $profile['marital'] = array( t('Status:'), $a->profile['marital']);


		if($a->profile['with']) $profile['marital']['with'] = bbcode($a->profile['with']);

		if(strlen($a->profile['howlong']) && $a->profile['howlong'] !== NULL_DATE) {
				$profile['howlong'] = relative_date($a->profile['howlong'], t('for %1$d %2$s'));
		}

		if($a->profile['sexual']) $profile['sexual'] = array( t('Sexual Preference:'), $a->profile['sexual'] );

		if($a->profile['homepage']) $profile['homepage'] = array( t('Homepage:'), linkify($a->profile['homepage']) );

		if($a->profile['hometown']) $profile['hometown'] = array( t('Hometown:'), linkify($a->profile['hometown']) );

		if($a->profile['keywords']) $profile['keywords'] = array( t('Tags:'), $a->profile['keywords']);

		if($a->profile['politic']) $profile['politic'] = array( t('Political Views:'), $a->profile['politic']);

		if($a->profile['religion']) $profile['religion'] = array( t('Religion:'), $a->profile['religion']);

		if($txt = prepare_text($a->profile['about'])) $profile['about'] = array( t('About:'), $txt );

		if($txt = prepare_text($a->profile['interest'])) $profile['interest'] = array( t('Hobbies/Interests:'), $txt);

		if($txt = prepare_text($a->profile['likes'])) $profile['likes'] = array( t('Likes:'), $txt);

		if($txt = prepare_text($a->profile['dislikes'])) $profile['dislikes'] = array( t('Dislikes:'), $txt);

		if($txt = prepare_text($a->profile['contact'])) $profile['contact'] = array( t('Contact information and Social Networks:'), $txt);

		if($txt = prepare_text($a->profile['channels'])) $profile['channels'] = array( t('My other channels:'), $txt);

		if($txt = prepare_text($a->profile['music'])) $profile['music'] = array( t('Musical interests:'), $txt);
		
		if($txt = prepare_text($a->profile['book'])) $profile['book'] = array( t('Books, literature:'), $txt);

		if($txt = prepare_text($a->profile['tv'])) $profile['tv'] = array( t('Television:'), $txt);

		if($txt = prepare_text($a->profile['film'])) $profile['film'] = array( t('Film/dance/culture/entertainment:'), $txt);

		if($txt = prepare_text($a->profile['romance'])) $profile['romance'] = array( t('Love/Romance:'), $txt);
		
		if($txt = prepare_text($a->profile['work'])) $profile['work'] = array( t('Work/employment:'), $txt);

		if($txt = prepare_text($a->profile['education'])) $profile['education'] = array( t('School/education:'), $txt );

		if($a->profile['extra_fields']) {
			foreach($a->profile['extra_fields'] as $f) {
				$x = q("select * from profdef where field_name = '%s' limit 1",
					dbesc($f)
				);
				if($x && $txt = prepare_text($a->profile[$f]))
					$profile[$f] = array( $x[0]['field_desc'] . ':',$txt);
			}
			$profile['extra_fields'] = $a->profile['extra_fields'];
		}

		$things = get_things($a->profile['profile_guid'],$a->profile['profile_uid']);

//		logger('mod_profile: things: ' . print_r($things,true), LOGGER_DATA); 

        return replace_macros($tpl, array(
            '$title' => t('Profile'),
			'$canlike' => (($profile['canlike'])? true : false),
			'$likethis' => t('Like this thing'),
            '$profile' => $profile,
			'$things' => $things
        ));
    }

	return '';
}




function get_my_url() {
	if(x($_SESSION,'zrl_override'))
		return $_SESSION['zrl_override'];
	if(x($_SESSION,'my_url'))
		return $_SESSION['my_url'];
	return false;
}

function get_my_address() {
	if(x($_SESSION,'zid_override'))
		return $_SESSION['zid_override'];
	if(x($_SESSION,'my_address'))
		return $_SESSION['my_address'];
	return false;
}

/**
 * @function zid_init(&$a)
 *   If somebody arrives at our site using a zid, add their xchan to our DB if we don't have it already.
 *   And if they aren't already authenticated here, attempt reverse magic auth.
 *
 * @hooks 'zid_init'
 *      string 'zid' - their zid
 *      string 'url' - the destination url
 *
 */

function zid_init(&$a) {
	$tmp_str = get_my_address();
	if(validate_email($tmp_str)) {
		proc_run('php','include/gprobe.php',bin2hex($tmp_str));
		$arr = array('zid' => $tmp_str, 'url' => $a->cmd);
		call_hooks('zid_init',$arr);		
		if(! local_user()) {
			$r = q("select * from hubloc where hubloc_addr = '%s' order by hubloc_connected desc limit 1",
				dbesc($tmp_str)
			);
			if($r && remote_user() && remote_user() === $r[0]['hubloc_hash'])
				return;
			logger('zid_init: not authenticated. Invoking reverse magic-auth for ' . $tmp_str);
			// try to avoid recursion - but send them home to do a proper magic auth
			$query = $a->query_string;
			$query = str_replace(array('?zid=','&zid='),array('?rzid=','&rzid='),$query);
			$dest = '/' . urlencode($query);
			if($r && ($r[0]['hubloc_url'] != z_root()) && (! strstr($dest,'/magic')) && (! strstr($dest,'/rmagic'))) {
				goaway($r[0]['hubloc_url'] . '/magic' . '?f=&rev=1&dest=' . z_root() . $dest);
			}
			else
				logger('zid_init: no hubloc found.');
		}
	}
}

/**
 * @function zid($s,$address = '')
 *   Adds a zid parameter to a url
 * @param string $s
 *   The url to accept the zid
 * @param boolean $address
 *   $address to use instead of session environment
 * @return string
 *
 * @hooks 'zid'
 *      string url - url to accept zid
 *      string zid - urlencoded zid
 *      string result - the return string we calculated, change it if you want to return something else
 */


function zid($s,$address = '') {
	if(! strlen($s) || strpos($s,'zid='))
		return $s;
	$has_params = ((strpos($s,'?')) ? true : false);
	$num_slashes = substr_count($s,'/');
	if(! $has_params)
		$has_params = ((strpos($s,'&')) ? true : false);
	$achar = strpos($s,'?') ? '&' : '?';

	$mine = get_my_url();
	$myaddr = (($address) ? $address : get_my_address());

	// FIXME checking against our own channel url is no longer reliable. We may have a lot
	// of urls attached to out channel. Should probably match against our site, since we
	// will not need to remote authenticate on our own site anyway.

	if($mine && $myaddr && (! link_compare($mine,$s)))
		$zurl = $s . (($num_slashes >= 3) ? '' : '/') . $achar . 'zid=' . urlencode($myaddr);
	else
		$zurl = $s;

	$arr = array('url' => $s, 'zid' => urlencode($myaddr), 'result' => $zurl);
	call_hooks('zid', $arr);
	return $arr['result'];
}

// Used from within PCSS themes to set theme parameters. If there's a
// puid request variable, that is the "page owner" and normally their theme
// settings take precedence; unless a local user sets the "always_my_theme" 
// system pconfig, which means they don't want to see anybody else's theme 
// settings except their own while on this site.

function get_theme_uid() {
	$uid = (($_REQUEST['puid']) ? intval($_REQUEST['puid']) : 0);
	if(local_user()) {
		if((get_pconfig(local_user(),'system','always_my_theme')) || (! $uid))
			return local_user();
	}
	if(! $uid) {
		$x = get_sys_channel();
		if($x)
			return $x['channel_id'];
	}	
	return $uid;
}

/**
* @function get_default_profile_photo($size = 175)
* 	Retrieves the path of the default_profile_photo for this system
* 	with the specified size.
* @param int $size
* 	one of (175, 80, 48)
* @returns string
*
*/

function get_default_profile_photo($size = 175) {
		$scheme = get_config('system','default_profile_photo');
		if(! $scheme)
			$scheme = 'rainbow_man';
		return 'images/default_profile_photos/' . $scheme . '/' . $size . '.png';
}


/**
 *
 * @function is_foreigner($s)
 *    Test whether a given identity is NOT a member of the Red Matrix
 * @param string $s;
 *    xchan_hash of the identity in question
 *
 * @returns boolean true or false
 *
 */

function is_foreigner($s) {
	return((strpbrk($s,'.:@')) ? true : false);
}


/**
 *
 * @function is_member($s)
 *    Test whether a given identity is a member of the Red Matrix
 * @param string $s;
 *    xchan_hash of the identity in question
 *
 * @returns boolean true or false
 *
 */

function is_member($s) {
	return((is_foreigner($s)) ? false : true);
}

function get_online_status($nick) {

	$ret = array('result' => false);

	if(get_config('system','block_public') && ! local_user() && ! remote_user())
		return $ret;

	$r = q("select channel_id, channel_hash from channel where channel_address = '%s' limit 1",
		dbesc(argv(1))
	);
	if($r) {
		$hide = get_pconfig($r[0]['channel_id'],'system','hide_online_status');
		if($hide)
			return $ret;
		$x = q("select cp_status from chatpresence where cp_xchan = '%s' and cp_room = 0 limit 1",
			dbesc($r[0]['channel_hash'])
		);
		if($x)
			$ret['result'] = $x[0]['cp_status'];
	}

	return $ret;
}


function remote_online_status($webbie) {

	$result = false;
	$r = q("select * from hubloc where hubloc_addr = '%s' limit 1",
		dbesc($webbie)
	);
	if(! $r)
		return $result;

	$url = $r[0]['hubloc_url'] . '/online/' . substr($webbie,0,strpos($webbie,'@'));

	$x = z_fetch_url($url);
	if($x['success']) {
		$j = json_decode($x['body'],true);
		if($j)
			$result = (($j['result']) ? $j['result'] : false);
	}
	return $result;

}


function get_channel_by_nick($nick) {
	$r = q("select * from channel where channel_address = '%s' limit 1",
		dbesc($nick)
	);
	return(($r) ? $r[0] : false);

}


function identity_selector() {
	if(local_user()) {
		$r = q("select channel.*, xchan.* from channel left join xchan on channel.channel_hash = xchan.xchan_hash where channel.channel_account_id = %d and (channel_pageflags & %d) = 0 order by channel_name ",
			intval(get_account_id()),
			intval(PAGE_REMOVED)
		);
		if(count($r) > 1) {
			$selected_channel = null;
			$account = get_app()->get_account();
			$o = replace_macros(get_markup_template('channel_id_select.tpl'),array(
				'$channels' => $r,
				'$selected' => local_user()
			));
			return $o;
		}
	}

	return '';
}


function is_public_profile() {
	if(! local_user())
		return false;
	if(intval(get_config('system','block_public')))
		return false;
	$channel = get_app()->get_channel();
	if($channel && $channel['channel_r_profile'] == PERMS_PUBLIC)
		return true;
	return false;
}

function get_profile_fields_basic($filter = 0) {

	$profile_fields_basic = (($filter == 0) ? get_config('system','profile_fields_basic') : null);
	if(! $profile_fields_basic)
		$profile_fields_basic = array('name','pdesc','chandesc','gender','dob','dob_tz','address','locality','region','postal_code','country_name','marital','sexual','homepage','hometown','keywords','about','contact');

	$x = array();
	if($profile_fields_basic)
		foreach($profile_fields_basic as $f)
			$x[$f] = 1;

	return $x;

}


function get_profile_fields_advanced($filter = 0) {
	$basic = get_profile_fields_basic($filter);
	$profile_fields_advanced = (($filter == 0) ? get_config('system','profile_fields_advanced') : null);
	if(! $profile_fields_advanced)
		$profile_fields_advanced = array('with','howlong','politic','religion','likes','dislikes','interest','channels','music','book','film','tv','romance','work','education');

	$x = array();
	if($basic)
		foreach($basic as $f => $v)
			$x[$f] = $v;
	if($profile_fields_advanced)
		foreach($profile_fields_advanced as $f)
			$x[$f] = 1;

	return $x;
}

/**
 * @function notifications_off($channel_id)
 *    Clear notifyflags for a channel - most likely during bulk import of content or other activity that is likely
 *    to generate huge amounts of undesired notifications.
 * @param int $channel_id
 *    The channel to disable notifications for
 * @returns int
 *    Current notification flag value. Send this to notifications_on() to restore the channel settings when finished
 *    with the activity requiring notifications_off(); 
 */



function notifications_off($channel_id) {
	$r = q("select channel_notifyflags from channel where channel_id = %d limit 1",
		intval($channel_id)
	);
	$x = q("update channel set channel_notifyflags = 0 where channel_id = %d",
		intval($channel_id)
	);

	return intval($r[0]['channel_notifyflags']);

}


function notifications_on($channel_id,$value) {
	$x = q("update channel set channel_notifyflags = %d where channel_id = %d",
		intval($value),
		intval($channel_id)
	);
	return $x;
}


function get_channel_default_perms($uid) {

	$r = q("select abook_my_perms from abook where abook_channel = %d and (abook_flags & %d) > 0 limit 1",
		intval($uid),
		intval(ABOOK_FLAG_SELF)
	);
	if($r)
		return $r[0]['abook_my_perms'];
	return 0;
}
