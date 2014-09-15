<?php /** @file */

function is_matrix_url($url) {
	$m = @parse_url($url);
	if($m['host']) {
		$r = q("select hubloc_url from hubloc where hubloc_host = '%s' limit 1",
			dbesc($m['host'])
		);
		if($r)
			return true;
	}
	return false;
}



function prune_hub_reinstalls() {

	$r = q("select site_url from site where true");
	if($r) {
		foreach($r as $rr) {
			$x = q("select count(*) as t, hubloc_sitekey, max(hubloc_connected) as c from hubloc where hubloc_url = '%s' group by hubloc_sitekey order by c",
				dbesc($rr['site_url'])
			);

			// see if this url has more than one sitekey, indicating it has been re-installed.

			if(count($x) > 1) {
				
				$d1 = datetime_convert('UTC','UTC',$x[0]['c']);
				$d2 = datetime_convert('UTC','UTC','now - 3 days');

				// allow some slop period, say 3 days - just in case this is a glitch or transient occurrence
				// Then remove any hublocs pointing to the oldest entry.

				if($d1 < $d2) {
					logger('prune_hub_reinstalls: removing dead hublocs at ' . $rr['site_url']);
					$y = q("delete from hubloc where hubloc_sitekey = '%s'",
						dbesc($x[0]['hubloc_sitekey'])
					);
				}
			}
		}
	}
}

function remove_obsolete_hublocs() {

	logger('remove_obsolete_hublocs',LOGGER_DEBUG);

	// Get rid of any hublocs which are ours but aren't valid anymore - 
	// e.g. they point to a different and perhaps transient URL that we aren't using.

	// I need to stress that this shouldn't happen. fix_system_urls() fixes hublocs
	// when it discovers the URL has changed. So it's unclear how we could end up
	// with URLs pointing to the old site name. But it happens. This may be an artifact
	// of an old bug or maybe a regression in some newer code. In any event, they
	// mess up communications and we have to take action if we find any. 

	// First make sure we have any hublocs (at all) with this URL and sitekey.
	// We don't want to perform this operation while somebody is in the process
	// of renaming their hub or installing certs.

	$r = q("select hubloc_id from hubloc where hubloc_url = '%s' and hubloc_sitekey = '%s'",
		dbesc(z_root()),
		dbesc(get_config('system','pubkey'))
	);
	if((! $r) || (! count($r)))
		return;

	$channels = array();

	// Good. We have at least one *valid* hubloc.

	// Do we have any invalid ones?

	$r = q("select hubloc_id from hubloc where hubloc_sitekey = '%s' and hubloc_url != '%s'",
		dbesc(get_config('system','pubkey')),
		dbesc(z_root())
	);
	$p = q("select hubloc_id from hubloc where hubloc_sitekey != '%s' and hubloc_url = '%s'",
		dbesc(get_config('system','pubkey')),
		dbesc(z_root())
	);
	if(is_array($r) && is_array($p))
		$r = array_merge($r,$p);

	if(! $r)
		return;

	// We've got invalid hublocs. Get rid of them.

	logger('remove_obsolete_hublocs: removing ' . count($r) . ' hublocs.');

	$interval = ((get_config('system','delivery_interval') !== false) 
			? intval(get_config('system','delivery_interval')) : 2 );

	foreach($r as $rr) {
		q("update hubloc set hubloc_flags = (hubloc_flags | %d) where hubloc_id = %d limit 1",
			intval(HUBLOC_FLAGS_DELETED),
			intval($rr['hubloc_id'])
		);

		$x = q("select channel_id from channel where channel_hash = '%s' limit 1",
			dbesc($rr['hubloc_hash']) 
		);
		if($x) {
//			proc_run('php','include/notifier.php','location',$x[0]['channel_id']);
//			if($interval)
//				@time_sleep_until(microtime(true) + (float) $interval);
		}
	}
}


	