<?php /** @file */


function locs_post(&$a) {

	if(! local_channel())
		return;

	$channel = $a->get_channel();

	if($_REQUEST['primary']) {
		$hubloc_id = intval($_REQUEST['primary']);
		if($hubloc_id) {

			$r = q("select hubloc_id from hubloc where hubloc_id = %d and hubloc_hash = '%s' limit 1",
				intval($hubloc_id),
				dbesc($channel['channel_hash'])
			);

			if(! $r) {
				notice( t('Location not found.') . EOL);
				return;
			}

			$r = q("update hubloc set hubloc_flags = (hubloc_flags - %d) where (hubloc_flags & %d)>0 and hubloc_hash = '%s' ",
				intval(HUBLOC_FLAGS_PRIMARY),
				intval(HUBLOC_FLAGS_PRIMARY),
				dbesc($channel['channel_hash'])
			);
			$r = q("update hubloc set hubloc_flags = (hubloc_flags | %d) where hubloc_id = %d and hubloc_hash = '%s'",
				intval(HUBLOC_FLAGS_PRIMARY),
				intval($hubloc_id),
				dbesc($channel['channel_hash'])
			);

			proc_run('php','include/notifier.php','location',$channel['channel_id']);
			return;
		}			
	}

	if($_REQUEST['drop']) {
		$hubloc_id = intval($_REQUEST['drop']);

		if($hubloc_id) {
			$r = q("select hubloc_id, hubloc_flags from hubloc where hubloc_id = %d and hubloc_url != '%s' and hubloc_hash = '%s' limit 1",
				intval($hubloc_id),
				dbesc(z_root()),
				dbesc($channel['channel_hash'])
			);

			if(! $r) {
				notice( t('Location not found.') . EOL);
				return;
			}
			if($r[0]['hubloc_flags'] & HUBLOC_FLAGS_PRIMARY) {
				notice( t('Primary location cannot be removed.') . EOL);
				return;
			}
			$r = q("update hubloc set hubloc_flags = (hubloc_flags | %d) where hubloc_id = %d and hubloc_hash = '%s'",
				intval(HUBLOC_FLAGS_DELETED),
				intval($hubloc_id),
				dbesc($channel['channel_hash'])
			);
			proc_run('php','include/notifier.php','location',$channel['channel_id']);
			return;
		}			
	}
}



function locs_content(&$a) {



	if(! local_channel()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$channel = $a->get_channel();

	$r = q("select * from hubloc where hubloc_hash = '%s'",
		dbesc($channel['channel_hash'])
	);

	if(! $r) {
		notice( t('No locations found.') . EOL);
		return;
	}


	for($x = 0; $x < count($r); $x ++) {
		$r[$x]['primary'] = (($r[$x]['hubloc_flags'] & HUBLOC_FLAGS_PRIMARY) ? true : false);
		$r[$x]['deleted'] = (($r[$x]['hubloc_flags'] & HUBLOC_FLAGS_DELETED) ? true : false);
	}



	$o = replace_macros(get_markup_template('locmanage.tpl'), array(
		'$header' => t('Manage Channel Locations'),
		'$loc' => t('Location (address)'),
		'$mkprm' => t('Primary Location'),
		'$drop' => t('Drop location'),
		'$submit' => t('Submit'),
		'$hubs' => $r
	));

	return $o;
}