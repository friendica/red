<?php

require_once('include/items.php');
require_once('include/zot.php');

function zotfeed_init(&$a) {

	$result = array('success' => false);

	$mindate = (($_REQUEST['mindate']) ? datetime_convert('UTC','UTC',$_REQUEST['mindate']) : '');
	if(! $mindate)
		$mindate = datetime_convert('UTC','UTC', 'now - 1 month');

	if(get_config('system','block_public') && (! get_account_id()) && (! remote_user())) {
		$result['message'] = 'Public access denied';
		json_return_and_die($result);
	}

	$observer = $a->get_observer();


	$channel_address = ((argc() > 1) ? argv(1) : '');
	if($channel_address) {
		$r = q("select channel_id from channel where channel_address = '%s' limit 1",
			dbesc(argv(1))
		);
	}
	if(! $r) {
		$result['message'] = 'Channel not found.';
		json_return_and_die($result);
	}

	$result['messages'] = zot_feed($r[0]['channel_id'],$observer['xchan_hash'],$mindate);
	$result['success'] = true;
	json_return_and_die($result);


}
