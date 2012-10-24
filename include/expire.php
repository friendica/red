<?php

require_once('boot.php');
require_once('include/cli_startup.php');

function expire_run($argv, $argc){

	cli_startup();

	// physically remove anything that has been deleted for more than two months

	$r = q("delete from item where item_flags & %d and changed < UTC_TIMESTAMP() - INTERVAL 60 DAY",
		intval(ITEM_DELETED)
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

	return;
}

if (array_search(__file__,get_included_files())===0){
  expire_run($argv,$argc);
  killme();
}
