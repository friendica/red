<?php

require_once('include/zot.php');
require_once('include/crypto.php');


function identity_check_service_class($account_id) {
	$ret = array('success' => false, $message => '');
	
	$r = q("select count(entity_id) as total from entity were entity_account_id = %d ",
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

	$primary = true;
		
	$r = q("insert into entity ( entity_account_id, entity_primary, 
		entity_name, entity_address, entity_global_id, entity_prvkey,
		entity_pubkey, entity_pageflags )
		values ( %d, %d, '%s', '%s', '%s', '%s', '%s', %d ) ",

		intval($arr['account_id']),
		intval($primary),
		dbesc($name),
		dbesc($nick),
		dbesc($guid),
		dbesc($key['prvkey']),
		dbesc($key['pubkey']),
		intval(PAGE_NORMAL)
	);
			
	$r = q("select * from entity where entity_account_id = %d 
		and entity_global_id = '%s' limit 1",
		intval($arr['account_id']),
		dbesc($guid)
	);

	if(! ($r && count($r))) {
		$ret['message'] = t('Unable to retrieve created identity');
		return $ret;
	}
	
	$ret['entity'] = $r[0];

	set_default_login_identity($arr['account_id'],$ret['entity']['entity_id'],false);
	
	// Create a verified hub location pointing to this site.

	$r = q("insert into hubloc ( hubloc_guid, hubloc_guid_sig, hubloc_flags, 
		hubloc_url, hubloc_url_sig, hubloc_callback, hubloc_sitekey )
		values ( '%s', '%s', %d, '%s', '%s', '%s', '%s' )",
		dbesc($ret['entity']['entity_global_id']),
		dbesc(base64url_encode(rsa_sign($ret['entity']['entity_global_id'],$ret['entity']['entity_prvkey']))),
		intval(($primary) ? HUBLOC_FLAGS_PRIMARY : 0),
		dbesc(z_root()),
		dbesc(base64url_encode(rsa_sign(z_root(),$ret['entity']['entity_prvkey']))),
		dbesc(z_root() . '/post'),
		dbesc(get_config('system','pubkey'))
	);
	if(! $r)
		logger('create_identity: Unable to store hub location');

	$newuid = $ret['entity']['entity_id'];

	$r = q("INSERT INTO `profile` ( `aid`, `uid`, `profile_name`, `is_default`, `name`, `photo`, `thumb`)
		VALUES ( %d, %d, '%s', %d, '%s', '%s', '%s') ",
		intval($ret['entity']['entity_account_id']),
		intval($newuid),
		t('default'),
		1,
		dbesc($ret['entity']['entity_name']),
		dbesc($a->get_baseurl() . "/photo/profile/{$newuid}"),
		dbesc($a->get_baseurl() . "/photo/avatar/{$newuid}")
	);

	$r = q("INSERT INTO `contact` ( `aid`, `uid`, `created`, `self`, `name`, `nick`, `photo`, `thumb`, `micro`, `blocked`, `pending`, `url`, `name_date`, `uri_date`, `avatar_date`, `closeness` )
			VALUES ( %d, %d, '%s', 1, '%s', '%s', '%s', '%s', '%s', 0, 0, '%s', '%s', '%s', '%s', 0 ) ",
			intval($ret['entity']['entity_account_id']),
			intval($newuid),
			datetime_convert(),
			dbesc($ret['entity']['entity_name']),
			dbesc($ret['entity']['entity_address']),
			dbesc($a->get_baseurl() . "/photo/profile/{$newuid}"),
			dbesc($a->get_baseurl() . "/photo/avatar/{$newuid}"),
			dbesc($a->get_baseurl() . "/photo/micro/{$newuid}"),
			dbesc($a->get_baseurl() . "/profile/{$ret['entity']['entity_address']}"),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			dbesc(datetime_convert())
	);

	// Create a group with no members. This allows somebody to use it 
	// right away as a default group for new contacts. 

	require_once('include/group.php');
	group_add($newuid, t('Friends'));

	call_hooks('register_account', $newuid);
 
	$ret['success'] = true;
	return $ret;

}

// set default identity for account_id to identity_id
// if $force is false only do this if there is no current default

function set_default_login_identity($account_id,$entity_id,$force = true) {
	$r = q("select account_default_entity from account where account_id = %d limit 1",
		intval($account_id)
	);
	if(($r) && (count($r)) && ((! intval($r[0]['account_default_entity'])) || $force)) {
		$r = q("update account set account_default_entity = %d where account_id = %d limit 1",
			intval($entity_id),
			intval($account_id)
		);
	}
}

