<?php
require_once("boot.php");
require_once('include/cli_startup.php');
require_once('include/queue_fn.php');
require_once('include/zot.php');

function queue_run($argv, $argc){

	cli_startup();

	global $a;

	require_once('include/items.php');
	require_once('include/bbcode.php');

	if(argc() > 1)
		$queue_id = argv(1);
	else
		$queue_id = 0;

	$deadguys = array();

	logger('queue: start');

	$r = q("DELETE FROM outq WHERE outq_created < UTC_TIMESTAMP() - INTERVAL 3 DAY");

	if($queue_id) {
		$r = q("SELECT * FROM outq WHERE outq_hash = '%s' LIMIT 1",
			dbesc($queue_id)
		);
	}
	else {

		// For the first 12 hours we'll try to deliver every 15 minutes
		// After that, we'll only attempt delivery once per hour. 

		$r = q("SELECT * FROM outq WHERE outq_delivered = 0 and (( outq_created > UTC_TIMESTAMP() - INTERVAL 12 HOUR and outq_updated < UTC_TIMESTAMP() - INTERVAL 15 MINUTE ) OR ( outq_updated < UTC_TIMESTAMP() - INTERVAL 1 HOUR ))");
	}
	if(! $r)
		return;

	foreach($r as $rr) {
		if(in_array($rr['outq_hub'],$deadguys))
			continue;
		$result = zot_zot($rr['outq_posturl'],$rr['outq_notify']); 
		if($result['success']) {
			zot_process_response($rr['outq_posturl'],$result, $rr);				
		}
		else {
			$deadguys[] = $rr['outq_hub'];
			$y = q("update outq set outq_updated = '%s' where outq_hash = '%s' limit 1",
				dbesc(datetime_convert()),
				dbesc($rr['outq_hash'])
			);
		}
	}
}

if (array_search(__file__,get_included_files())===0){
  queue_run($argv,$argc);
  killme();
}
