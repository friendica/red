<?php

require_once('include/items.php');
require_once('include/zot.php');

function zotfeed_init(&$a) {

	$result = array('success' => false);

	$mindate = (($_REQUEST['mindate']) ? datetime_convert('UTC','UTC',$_REQUEST['mindate']) : '');
	if(! $mindate)
		$mindate = datetime_convert('UTC','UTC', 'now - 1 month');

	if(get_config('system','block_public') && (! get_account_id()) && (! remote_channel())) {
		$result['message'] = 'Public access denied';
		json_return_and_die($result);
	}

	$observer = $a->get_observer();


	$channel_address = ((argc() > 1) ? argv(1) : '');
	if($channel_address) {
		$r = q("select channel_id, channel_name from channel where channel_address = '%s' and not (channel_pageflags & %d)>0 limit 1",
			dbesc(argv(1)),
			intval(PAGE_REMOVED)
		);
	}
	else {
		$x = get_sys_channel();
		if($x)
			$r = array($x);
	}
	if(! $r) {
		$result['message'] = 'Channel not found.';
		json_return_and_die($result);
	}

	logger('zotfeed request: ' . $r[0]['channel_name'], LOGGER_DEBUG);

	$result['messages'] = zot_feed($r[0]['channel_id'],$observer['xchan_hash'],array('mindate' => $mindate));
	$result['success'] = true;
	json_return_and_die($result);


}
