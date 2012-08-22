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

		$ret['guid'] = $e['entity_global_id'];
		$ret['url'] = z_root();
		$ret['primary'] = (bool) $e['entity_primary'];
		$ret['callback'] = z_root() . '/' . 'post';
		$ret['sitekey'] = get_config('system','pubkey');
		$ret['key'] = $e['pubkey'];

		$ret['hubs'] = array();
		$x = zot_get_hubloc(array($e['entity_global_id']));
		if($x && count($x)) {
			foreach($x as $hub) {
				$ret['hubs'][] = array(
						'primary' => (bool) $hub['hubloc_primary'],
						'url' => $hub['hubloc_url'],
						'callback' => $hub['hubloc_callback'],
						'sitekey' => $hub['hubloc_sitekey']
				);
			}
		}

			// more stuff

		json_return_and_die($ret);

	}
	$ret['message'] = 'Item not found.';
	json_return_and_die($ret);
}