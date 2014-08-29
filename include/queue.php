<?php /** @file */
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
		// This currently only handles the default queue drivers ('zot' or '') which we will group by posturl 
		// so that we don't start off a thousand deliveries for a couple of dead hubs.
		// The zot driver will deliver everything destined for a single hub once contact is made (*if* contact is made).
		// Other drivers will have to do something different here and may need their own query.
 
		$r = q("SELECT * FROM outq WHERE outq_delivered = 0 and (( outq_created > UTC_TIMESTAMP() - INTERVAL 12 HOUR and outq_updated < UTC_TIMESTAMP() - INTERVAL 15 MINUTE ) OR ( outq_updated < UTC_TIMESTAMP() - INTERVAL 1 HOUR )) group by outq_posturl");
	}
	if(! $r)
		return;

	foreach($r as $rr) {
		if(in_array($rr['outq_posturl'],$deadguys))
			continue;

		if($rr['outq_driver'] === 'post') {
			$result = z_post_url($rr['outq_posturl'],$rr['outq_msg']); 
			if($result['success'] && $result['return_code'] < 300) {
				logger('deliver: queue post success to ' . $rr['outq_posturl'], LOGGER_DEBUG);
				$y = q("update outq set outq_delivered = '%s' where outq_hash = '%s' limit 1",
					dbesc($rr['ouq_hash'])
				);
			}
			else {
				$y = q("update outq set outq_updated = '%s' where outq_hash = '%s' limit 1",
					dbesc(datetime_convert()),
					dbesc($rr['outq_hash'])
				);
			}
			continue;
		}
		$result = zot_zot($rr['outq_posturl'],$rr['outq_notify']); 
		if($result['success']) {
			zot_process_response($rr['outq_posturl'],$result, $rr);				
		}
		else {
			$deadguys[] = $rr['outq_posturl'];
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
