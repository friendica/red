<?php

require_once('include/zot.php');
require_once('include/crypto.php');


function identity_check_service_class($account_id) {
	$ret = array('success' => false, $message => '');
	
	$r = q("select count(channel_id) as total from channel were channel_account_id = %d ",
		intval($account_id)
	);
	if(! ($r && count($r))) {
		$ret['message'] = t('Unable to obtain identity information from database');
		return $ret;
	} 

	if(! service_class_allows($account_id,'total_identities',$r[0]['total'])) {
		$result['message'] .= upgrade_message();
		return $result;
	}

	$ret['success'] = true;
	return $ret;
}


// Required: name, nickname, account_id

// optional: pageflags

function create_identity($arr) {

	$a = get_app();
	$ret = array('success' => false);

	if(! $arr['account_id']) {
		$ret['message'] = t('No account identifier');
		return $ret;
	}

	$nick = trim($arr['nickname']);
	$name = escape_tags($arr['name']);
	$pageflags = ((x($arr,'pageflags')) ? intval($arr['pageflags']) : PAGE_NORMAL);

	if(check_webbie(array($nick)) !== $nick) {
		$ret['message'] = t('Nickname has unsupported characters or is already being used on this site.');
		return $ret;
	}

	$guid = zot_new_uid($nick);
	$key = new_keypair(4096);


	$sig = base64url_encode(rsa_sign($guid,$key['prvkey']));
	$hash = base64url_encode(hash('whirlpool',$guid . $sig,true));

	$primary = true;
		
	$r = q("insert into channel ( channel_account_id, channel_primary, 
		channel_name, channel_address, channel_guid, channel_guid_sig,
		channel_hash, channel_prvkey, channel_pubkey, channel_pageflags )
		values ( %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d ) ",

		intval($arr['account_id']),
		intval($primary),
		dbesc($name),
		dbesc($nick),
		dbesc($guid),
		dbesc($sig),
		dbesc($hash),
		dbesc($key['prvkey']),
		dbesc($key['pubkey']),
		intval(PAGE_NORMAL)
	);
			
	$r = q("select * from channel where channel_account_id = %d 
		and channel_guid = '%s' limit 1",
		intval($arr['account_id']),
		dbesc($guid)
	);

	if(! ($r && count($r))) {
		$ret['message'] = t('Unable to retrieve created identity');
		return $ret;
	}
	
	$ret['channel'] = $r[0];

	set_default_login_identity($arr['account_id'],$ret['channel']['channel_id'],false);


	// Create a verified hub location pointing to this site.

	$r = q("insert into hubloc ( hubloc_guid, hubloc_guid_sig, hubloc_hash, hubloc_flags, 
		hubloc_url, hubloc_url_sig, hubloc_callback, hubloc_sitekey )
		values ( '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s' )",
		dbesc($guid),
		dbesc($sig),
		dbesc($hash),
		intval(($primary) ? HUBLOC_FLAGS_PRIMARY : 0),
		dbesc(z_root()),
		dbesc(base64url_encode(rsa_sign(z_root(),$ret['channel']['channel_prvkey']))),
		dbesc(z_root() . '/post'),
		dbesc(get_config('system','pubkey'))
	);
	if(! $r)
		logger('create_identity: Unable to store hub location');


	$newuid = $ret['channel']['channel_id'];

	$r = q("insert into xchan ( xchan_hash, xchan_guid, xchan_guid_sig, xchan_photo, xchan_addr, xchan_profile, xchan_name, xchan_network, xchan_photo_date, xchan_name_date ) values ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
		dbesc($hash),
		dbesc($ret['channel']['channel_guid']),
		dbesc($sig),
		dbesc($a->get_baseurl() . "/photo/profile/{$newuid}"),
		dbesc($ret['channel']['channel_address'] . '@' . $a->get_hostname()),
		dbesc(z_root() . '/profile/' . $ret['channel']['channel_address']),
		dbesc($ret['channel']['channel_name']),
		dbesc('zot'),
		dbesc(datetime_convert()),
		dbesc(datetime_convert())
	);

	// Not checking return value. 
	// It's ok for this to fail if it's an imported channel, and therefore the hash is a duplicate
		

	$r = q("INSERT INTO `profile` ( `aid`, `uid`, `profile_name`, `is_default`, `name`, `photo`, `thumb`)
		VALUES ( %d, %d, '%s', %d, '%s', '%s', '%s') ",
		intval($ret['channel']['channel_account_id']),
		intval($newuid),
		t('default'),
		1,
		dbesc($ret['channel']['channel_name']),
		dbesc($a->get_baseurl() . "/photo/profile/{$newuid}"),
		dbesc($a->get_baseurl() . "/photo/avatar/{$newuid}")
	);


	// Create a group with no members. This allows somebody to use it 
	// right away as a default group for new contacts. 

	require_once('include/group.php');
	group_add($newuid, t('Friends'));

	call_hooks('register_account', $newuid);
 
	$ret['success'] = true;
	return $ret;

}

// set default identity for account_id to channel_id
// if $force is false only do this if there is no current default

function set_default_login_identity($account_id,$channel_id,$force = true) {
	$r = q("select account_default_channel from account where account_id = %d limit 1",
		intval($account_id)
	);
	if(($r) && (count($r)) && ((! intval($r[0]['account_default_channel'])) || $force)) {
		$r = q("update account set account_default_channel = %d where account_id = %d limit 1",
			intval($channel_id),
			intval($account_id)
		);
	}
}

