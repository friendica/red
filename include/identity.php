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

	// Force primary until importation works, then we'll offer a choice

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

	// Ensure that there is a host keypair.

	if((! get_config('system','pubkey')) && (! get_config('system','prvkey'))) {
		$hostkey = new_keypair(4096);
		set_config('system','pubkey',$hostkey['pubkey']);
		set_config('system','prvkey',$hostkey['prvkey']);
	}
	

	// Create a verified hub location pointing to this site.

	$r = q("insert into hubloc ( hubloc_guid, hubloc_guid_sig, hubloc_hash, hubloc_addr, hubloc_flags, 
		hubloc_url, hubloc_url_sig, hubloc_host, hubloc_callback, hubloc_sitekey )
		values ( '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s' )",
		dbesc($guid),
		dbesc($sig),
		dbesc($hash),
		dbesc($ret['channel']['channel_address'] . '@' . get_app()->get_hostname()),
		intval(($primary) ? HUBLOC_FLAGS_PRIMARY : 0),
		dbesc(z_root()),
		dbesc(base64url_encode(rsa_sign(z_root(),$ret['channel']['channel_prvkey']))),
		dbesc(get_app()->get_hostname()),
		dbesc(z_root() . '/post'),
		dbesc(get_config('system','pubkey'))
	);
	if(! $r)
		logger('create_identity: Unable to store hub location');


	$newuid = $ret['channel']['channel_id'];

	$r = q("insert into xchan ( xchan_hash, xchan_guid, xchan_guid_sig, xchan_pubkey, xchan_photo_l, xchan_photo_m, xchan_photo_s, xchan_addr, xchan_url, xchan_name, xchan_network, xchan_photo_date, xchan_name_date ) values ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
		dbesc($hash),
		dbesc($guid),
		dbesc($sig),
		dbesc($key['pubkey']),
		dbesc($a->get_baseurl() . "/photo/profile/l/{$newuid}"),
		dbesc($a->get_baseurl() . "/photo/profile/m/{$newuid}"),
		dbesc($a->get_baseurl() . "/photo/profile/s/{$newuid}"),
		dbesc($ret['channel']['channel_address'] . '@' . get_app()->get_hostname()),
		dbesc(z_root() . '/channel/' . $ret['channel']['channel_address']),
		dbesc($ret['channel']['channel_name']),
		dbesc('zot'),
		dbesc(datetime_convert()),
		dbesc(datetime_convert())
	);

	// Not checking return value. 
	// It's ok for this to fail if it's an imported channel, and therefore the hash is a duplicate
		

	$r = q("INSERT INTO profile ( aid, uid, profile_guid, profile_name, is_default, name, photo, thumb)
		VALUES ( %d, %d, '%s', '%s', %d, '%s', '%s', '%s') ",
		intval($ret['channel']['channel_account_id']),
		intval($newuid),
		dbesc(random_string()),
		t('Default Profile'),
		1,
		dbesc($ret['channel']['channel_name']),
		dbesc($a->get_baseurl() . "/photo/profile/l/{$newuid}"),
		dbesc($a->get_baseurl() . "/photo/profile/m/{$newuid}")
	);

	$r = q("insert into abook ( abook_account, abook_channel, abook_xchan, abook_closeness, abook_created, abook_updated, abook_flags )
		values ( %d, %d, '%s', %d, '%s', '%s', %d ) ",
		intval($ret['channel']['channel_account_id']),
		intval($newuid),
		dbesc($hash),
		intval(0),
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		intval(ABOOK_FLAG_SELF)
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

function identity_basic_export($channel_id) {

	/*
	 * Red basic channel export
	 */

	$ret = array();
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

	return $ret;
}




