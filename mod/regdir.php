<?php

/**
 * With args, register a directory server for this realm.
 * With no args, return a JSON array of directory servers for this realm.
 *
 * @FIXME Not yet implemented: Some realms may require authentication to join their realm.
 * The RED_GLOBAL realm does not require authentication.
 * We would then need a flag in the site table to indicate that they've been
 * validated by the PRIMARY directory for that realm. Sites claiming to be PRIMARY
 * but are not the realm PRIMARY will be marked invalid.
 * 
 * @param App &$a
 */
function regdir_init(&$a) {

	$result = array('success' => false);

	$url = $_REQUEST['url'];
	$access_token = $_REQUEST['t'];
	$valid = 0;

	// we probably don't need the realm as we will find out in the probe.
	// What we may want to die is throw an error if you're trying to register in a different realm
	// so this configuration issue can be discovered.

	$realm = $_REQUEST['realm'];
	if(! $realm)
		$realm = DIRECTORY_REALM;

	if($realm === DIRECTORY_REALM) {
		$valid = 1;
	} else {
		$token = get_config('system','realm_token');
		if($token && $access_token != $token) {
			$result['message'] = 'This realm requires an access token';
			return;
		}
		$valid = 1;
	}

	$dirmode = intval(get_config('system','directory_mode'));

	if ($dirmode == DIRECTORY_MODE_NORMAL) {
		$ret['message'] = t('This site is not a directory server');
		json_return_and_die($ret);
	}

	$m = null;
	if ($url) {
		$m = parse_url($url);

		if ((! $m) || ((! @dns_get_record($m['host'], DNS_A + DNS_CNAME + DNS_PTR)) && (! filter_var($m['host'], FILTER_VALIDATE_IP) ))) {

			$result['message'] = 'unparseable url';
			json_return_and_die($result);
		}

		$f = zot_finger('[system]@' . $m['host']);
		if($f['success']) {
			$j = json_decode($f['body'],true);
			if($j['success'] && $j['guid']) {
				$x = import_xchan($j);
				if($x['success']) {
					$result['success'] = true;
				}
			}
		}

		if(! $result['success'])
			$valid = 0;

		q("update site set site_valid = %d where site_url = '%s' limit 1",
			intval($valid),
			strtolower($url)
		);

		json_return_and_die($result);
	} else {

		// We can put this in the sql without the condition after 31 august 2015 assuming
		// most directory servers will have updated by then
		// This just makes sure it happens if I forget

		$sql_extra = ((datetime_convert() > datetime_convert('UTC','UTC','2015-08-31')) ? ' and site_valid = 1 ' : '' );
		if ($dirmode == DIRECTORY_MODE_STANDALONE) {
			$r = array(array('site_url' => z_root()));
		} else {
			$r = q("select site_url from site where site_flags in ( 1, 2 ) and site_realm = '%s' $sql_extra ",
				dbesc(get_directory_realm())
			);
		}
		if ($r) {
			$result['success'] = true;
			$result['directories'] = array();
			foreach ($r as $rr)
				$result['directories'][] = $rr['site_url'];

			json_return_and_die($result);
		}
	}
	json_return_and_die($result);
}