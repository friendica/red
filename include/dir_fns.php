<?php

require_once('include/permissions.php');

function find_upstream_directory($dirmode) {
	return '';
}

function syncdirs($uid) {

	logger('syncdirs', LOGGER_DEBUG);

	$p = q("select channel.channel_hash, profile.* from profile left join channel on channel_id = uid where uid = %d and is_default = 1",
		intval($uid)
	);

	$profile = array();

	if($p) {
		$hash = $p[0]['channel_hash'];

		$profile['description'] = $p[0]['pdesc'];
		$profile['birthday']    = $p[0]['dob'];
		$profile['gender']      = $p[0]['gender'];
		$profile['marital']     = $p[0]['marital'];
		$profile['sexual']      = $p[0]['sexual'];
		$profile['locale']      = $p[0]['locality'];
		$profile['region']      = $p[0]['region'];
		$profile['postcode']    = $p[0]['postal_code'];
		$profile['country']     = $p[0]['country_name'];
		if($p[0]['keywords']) {
			$tags = array();
			$k = explode(' ',$p[0]['keywords']);
			if($k)
				foreach($k as $kk)
					if(trim($kk))
						$tags[] = trim($kk);
			if($tags)
				$profile['keywords'] = $tags;
		}

		if(perm_is_allowed($uid,'','view_profile')) {
			import_directory_profile($hash,$profile);

		}
		else {
			// they may have made it private
			$r = q("delete from xprof where xprof_hash = '%s' limit 1",
				dbesc($hash)
			);
			$r = q("delete from xtag where xtag_hash = '%s' limit 1",
				dbesc($hash)
			);
		}
	}

	// TODO send refresh zots to downstream directory servers
}
	
