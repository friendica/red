<?php /** @file */

require_once('boot.php');
require_once('include/cli_startup.php');

function expire_run($argv, $argc){

	cli_startup();


	// perform final cleanup on previously delete items

	$r = q("select id from item where (item_restrict & %d) > 0 and (item_restrict & %d) = 0 
		and changed < %s - INTERVAL %s",
		intval(ITEM_DELETED),
		intval(ITEM_PENDING_REMOVE),
		db_utcnow(), db_quoteinterval('10 DAY')
	);
	if($r) {
		foreach($r as $rr) {
			drop_item($rr['id'],false,DROPITEM_PHASE2);
		}
	}

	// physically remove anything that has been deleted for more than two months
	// FIXME - this is a wretchedly inefficient query

	$r = q("delete from item where ( item_restrict & %d ) > 0 and changed < %s - INTERVAL %s",
		intval(ITEM_PENDING_REMOVE),
		db_utcnow(), db_quoteinterval('36 DAY')
	);

	// make this optional as it could have a performance impact on large sites

	if(intval(get_config('system','optimize_items')))
		q("optimize table item");

	logger('expire: start', LOGGER_DEBUG);
	
	$site_expire = get_config('system', 'default_expire_days');
	if(intval($site_expire)) {
		$r = q("SELECT channel_id, channel_address, channel_pageflags, channel_expire_days from channel where true");
	}
	else {
		$r = q("SELECT channel_id, channel_address, channel_pageflags, channel_expire_days from channel where channel_expire_days != 0");
	}

	if($r) {
		foreach($r as $rr) {

			// expire the sys channel separately
			if($rr['channel_pageflags'] & PAGE_SYSTEM)
				continue;

			// if the site expiration is non-zero and less than person expiration, use that
			logger('Expire: ' . $rr['channel_address'] . ' interval: ' . $rr['channel_expire_days'], LOGGER_DEBUG);
			item_expire($rr['channel_id'],
				((intval($site_expire) && intval($site_expire) < intval($rr['channel_expire_days'])) 
				? $site_expire 
				: $rr['channel_expire_days'])
			);
		}
	}


	$x = get_sys_channel();
	if($x) {

		// this should probably just fetch the channel_expire_days from the sys channel,
		// but there's no convenient way to set it.

		$expire_days = get_config('system','sys_expire_days');
		if($expire_days === false)
			$expire_days = 30;
		if($expire_days)
			item_expire($x['channel_id'],(($site_expire && $site_expire < $expire_days) ? $site_expire : $expire_days));
	}

	return;
}

if (array_search(__file__,get_included_files())===0){
  expire_run($argv,$argc);
  killme();
}
