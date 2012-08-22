<?php

function zfinger_init(&$a) {

	require_once('include/zot.php');

	$ret = array('success' => false, 'message' => '');
	if(argc() > 1) {
		$zguid = argv(1);
		
		if(strlen($zguid)) {
			$r = q("select * from entity where entity_global_id = '%s' limit 1",
				dbesc($zguid)
			);
			if(! ($r && count($r))) {
				$ret['message'] = 'Item not found.';
				json_return_and_die($ret);
			}
		}
		else {
			$ret['message'] = 'Invalid request';
			json_return_and_die($ret);
		}
		$e = $r[0];

		$ret['success'] = true;

		// Communication details

		$ret['guid'] = $e['entity_global_id'];
		$ret['key']  = $e['pubkey'];

		// array of (verified) hubs this entity uses

		$ret['hubs'] = array();
		$x = zot_get_hubloc(array($e['entity_global_id']));
		if($x && count($x)) {
			foreach($x as $hub) {
				if(! ($hub['hubloc_flags'] & HUBLOC_FLAGS_UNVERIFIED)) {
					$ret['hubs'][] = array(
						'primary'  => (($hub['hubloc_flags'] & HUBLOC_FLAGS_PRIMARY) ? true : false),
						'url'      => $hub['hubloc_url'],
						'callback' => $hub['hubloc_callback'],
						'sitekey'  => $hub['hubloc_sitekey']
					);
				}
			}
		}


		// more stuff, e.g. the basic public profile

		json_return_and_die($ret);

	}
	$ret['message'] = 'Item not found.';
	json_return_and_die($ret);
}