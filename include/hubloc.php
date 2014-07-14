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