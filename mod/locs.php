<?php /** @file */


/**
	Placeholder file at present. This is going to involve a bit of work.

	This file will deal with the deletion of channels and management of hublocs.

	We need to provide the following functionality:

	- Delete my account and all channels from the entire network

	- Delete my account and all channels from this server

	- Delete a channel from the entire network

	- Delete a channel from this server

	- List all hub locations for this channel

	- Remove this/some hub location from this channel

	- promote this/some hub location to primary

	- Remove hub location 'xyz' from this channel, (this should possibly only be allowed if that hub has been down for a period of time)

	- Some of these actions should probably require email verification

*/


function locs_post(&$a) {

	if(! local_user())
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
			$r = q("update hubloc set hubloc_flags = (hubloc_flags ^ %d) where (hubloc_flags & %d) and hubloc_hash = '%s' ",
				intval(HUBLOC_FLAGS_PRIMARY),
				intval(HUBLOC_FLAGS_PRIMARY),
				dbesc($channel['channel_hash'])
			);
			$r = q("update hubloc set hubloc_flags = (hubloc_flags & %d) where hubloc_id = %d and hubloc_hash = '%s' limit 1",
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
			$r = q("update hubloc set hubloc_flags = (hubloc_flags & %d) where hubloc_id = %d and hubloc_hash = '%s' limit 1",
				intval(HUBLOC_FLAGS_DELETED),
				intval($hubloc_id),
				dbesc($channel['channel_hash'])
			);
			proc_run('php','include/notifier.php','location',$channel['channel_id']);
			return;
		}			
	}
}