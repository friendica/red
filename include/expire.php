<?php /** @file */

require_once('boot.php');
require_once('include/cli_startup.php');

function expire_run($argv, $argc){

	cli_startup();

	$r = q("select id from item where (item_restrict & %d)>0 and not (item_restrict & %d)>0 and changed < %s - INTERVAL %s",
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

	$r = q("delete from item where ( item_restrict & %d )>0 and changed < %s - INTERVAL %s",
		intval(ITEM_PENDING_REMOVE),
		db_utcnow(), db_quoteinterval('36 DAY')
	);

	// make this optional as it could have a performance impact on large sites

	if(intval(get_config('system','optimize_items')))
		q("optimize table item");

	logger('expire: start', LOGGER_DEBUG);
	

	$r = q("SELECT channel_id, channel_address, channel_expire_days from channel where channel_expire_days != 0");
	if($r && count($r)) {
		foreach($r as $rr) {
			logger('Expire: ' . $rr['channel_address'] . ' interval: ' . $rr['channel_expire_days'], LOGGER_DEBUG);
			item_expire($rr['channel_id'],$rr['channel_expire_days']);
		}
	}


	$x = get_sys_channel();
	if($x) {

		// this should probably just fetch the channel_expire_days from the sys channel,
		// but there's no convenient way to set it.

		$expire_days = get_config('externals','expire_days');
		if($expire_days === false)
			$expire_days = 30;
		if($expire_days)
			item_expire($x['channel_id'],$expire_days);
	}


	return;
}

if (array_search(__file__,get_included_files())===0){
  expire_run($argv,$argc);
  killme();
}
