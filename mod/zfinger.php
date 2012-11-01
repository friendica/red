<?php

function zfinger_init(&$a) {

	require_once('include/zot.php');
	require_once('include/crypto.php');

	$ret = array('success' => false);

	$zguid   = ((x($_REQUEST,'guid')) ? $_REQUEST['guid'] : '');
	$zaddr   = ((x($_REQUEST,'address')) ? $_REQUEST['address'] : '');
	$ztarget = ((x($_REQUEST,'target'))      ? $_REQUEST['target']  : '');
	$zsig    = ((x($_REQUEST,'target_sig'))  ? $_REQUEST['target_sig']  : '');
		
	$r = null;

	if(strlen($zguid)) {
		$r = q("select channel.*, xchan.* from channel left join xchan on channel_hash = xchan_hash 
			where channel_guid = '%s' limit 1",
			dbesc($zguid)
		);
	}
	elseif(strlen($zaddr)) {
		$r = q("select channel.*, xchan.* from channel left join xchan on channel_hash = xchan_hash
			where channel_address = '%s' limit 1",
			dbesc($zaddr)
		);
	}
	else {
		$ret['message'] = 'Invalid request';
		json_return_and_die($ret);
	}

	if(! ($r && count($r))) {
		$ret['message'] = 'Item not found.';
		json_return_and_die($ret);
	}

	$e = $r[0];

	$id = $e['channel_id'];
//	$r = q("select contact.*, profile.* 
//		from contact left join profile on contact.uid = profile.uid
//		where contact.uid = %d && contact.self = 1 and profile.is_default = 1 limit 1",
//		intval($id)
//	);
//	if($r && count($r)) {
//		$profile = $r[0];
//	}


	$ret['success'] = true;

	// Communication details

	$ret['guid']          = $e['xchan_guid'];
	$ret['guid_sig']      = $e['xchan_guid_sig'];
	$ret['key']           = $e['xchan_pubkey'];
	$ret['name']          = $e['xchan_name'];
	$ret['name_updated']  = $e['xchan_name_date'];
	$ret['address']       = $e['xchan_addr'];
	$ret['photo']         = $e['xchan_photo_l'];
	$ret['photo_updated'] = $e['xchan_photo_date'];
	$ret['target']        = $ztarget;
	$ret['target_sig']    = $zsig;
	$ret['permissions']   = map_perms($r[0],$ztarget,$zsig);

//	$ret['profile']  = $profile;

	// array of (verified) hubs this channel uses

	$ret['hubs'] = array();
	$x = zot_get_hubloc(array($e['channel_hash']));
	if($x && count($x)) {
		foreach($x as $hub) {
			if(! ($hub['hubloc_flags'] & HUBLOC_FLAGS_UNVERIFIED)) {
				$ret['hubs'][] = array(
					'primary'  => (($hub['hubloc_flags'] & HUBLOC_FLAGS_PRIMARY) ? true : false),
					'url'      => $hub['hubloc_url'],
					'url_sig'  => $hub['hubloc_url_sig'],
					'host'     => $hub['hubloc_host'],
					'address'  => $hub['hubloc_addr'],
					'callback' => $hub['hubloc_callback'],
					'sitekey'  => $hub['hubloc_sitekey']
				);
			}
		}
	}

	json_return_and_die($ret);

}