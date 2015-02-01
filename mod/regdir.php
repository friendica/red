<?php


function regdir_init(&$a) {

	$result = array('success' => false);

	$url = $_REQUEST['url'];


	// we probably don't need the realm as we will find out in the probe.
	// What we may want to die is throw an error if you're trying to register in a different realm
	// so this configuration issue can be discovered.

	$realm = $_REQUEST['realm'];
	if(! $realm)
		$realm = DIRECTORY_REALM;

	$dirmode = intval(get_config('system','directory_mode'));

	if($dirmode == DIRECTORY_MODE_NORMAL) {
		$ret['message'] = t('This site is not a directory server');
		json_return_and_die($ret);
	}

	$m = null;
	if($url) {
		$m = parse_url($url);

		if((! $m) || (! @dns_get_record($m['host'], DNS_A + DNS_CNAME + DNS_PTR)) || (! filter_var($m['host'], FILTER_VALIDATE_IP) )) {
			$result['message'] = 'unparseable url';
			json_return_and_die($result);
		}

		$f = zot_finger('sys@' . $m['host']);
		if($f['success']) {
			$j = json_decode($f['body'],true);
			if($j['success'] && $j['guid']) {
				$x = import_xchan($j);
				if($x['success']) {
					$result['success'] = true;
					json_return_and_die($result);
				}
			}
		}

		json_return_and_die($result);
	}
	else {
		$r = q("select site_url from site where site_flags in ( 1, 2 ) and site_realm = '%s'",
			dbesc(get_directory_realm())
		);
		if($r) {
			$result['success'] = true;
			$result['directories'] = array();
			foreach($r as $rr)
				$result['directories'][] = $rr['site_url'];
			json_return_and_die($result);
		}
	}
	json_return_and_die($result);
		

}			