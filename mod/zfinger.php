<?php

function zfinger_init(&$a) {

	require_once('include/zot.php');

	$ret = array('success' => false);

	$zguid = ((x($_REQUEST,'guid')) ? $_REQUEST['guid'] : '');
	$zaddr = ((x($_REQUEST,'address')) ? $_REQUEST['address'] : '');
		
	$r = null;

	if(strlen($zguid)) {
		$r = q("select * from entity where entity_global_id = '%s' limit 1",
			dbesc($zguid)
		);
	}
	elseif(strlen($zaddr)) {
		$r = q("select * from entity where entity_address = '%s' limit 1",
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

	$ret['success'] = true;

	// Communication details

	$ret['guid'] = $e['entity_global_id'];
	$ret['guid_sig'] = base64url_encode($e['entity_global_id'],$e['entity_prvkey']);
	$ret['key']  = $e['entity_pubkey'];
	$ret['name'] = $e['entity_name'];
	$ret['address'] = $e['entity_address'];

	// array of (verified) hubs this entity uses

	$ret['hubs'] = array();
	$x = zot_get_hubloc(array($e['entity_global_id']));
	if($x && count($x)) {
		foreach($x as $hub) {
			if(! ($hub['hubloc_flags'] & HUBLOC_FLAGS_UNVERIFIED)) {
				$ret['hubs'][] = array(
					'primary'  => (($hub['hubloc_flags'] & HUBLOC_FLAGS_PRIMARY) ? true : false),
					'url'      => $hub['hubloc_url'],
					'url_sig'  => base64url_encode($hub['hubloc_url'],$e['entity_prvkey']),
					'callback' => $hub['hubloc_callback'],
					'sitekey'  => $hub['hubloc_sitekey']
				);
			}
		}
	}


	// more stuff, e.g. the basic public profile

	json_return_and_die($ret);

}