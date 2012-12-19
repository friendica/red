<?php
require_once("boot.php");
require_once('include/cli_startup.php');
require_once('include/queue_fn.php');

function queue_run($argv, $argc){

	cli_startup();
	global $a;

	require_once('include/items.php');
	require_once('include/bbcode.php');

	if(argc() > 1)
		$queue_id = intval(argv(1));
	else
		$queue_id = 0;

	$deadguys = array();

	logger('queue: start');

	$interval = ((get_config('system','delivery_interval') === false) ? 2 : intval(get_config('system','delivery_interval')));

//	$r = q("select * from deliverq where 1");
//FIXME
	$r = array();

return;


	if(count($r)) {
		foreach($r as $rr) {
			logger('queue: deliverq');
			proc_run('php','include/delivery.php',$rr['cmd'],$rr['item'],$rr['contact']);
			if($interval)
				@time_sleep_until(microtime(true) + (float) $interval);
		}
	}

	$r = q("SELECT `queue`.*, `contact`.`name`, `contact`.`uid` FROM `queue` 
		LEFT JOIN `contact` ON `queue`.`cid` = `contact`.`id` 
		WHERE `queue`.`created` < UTC_TIMESTAMP() - INTERVAL 3 DAY");
	if(count($r)) {
		foreach($r as $rr) {
			logger('Removing expired queue item for ' . $rr['name'] . ', uid=' . $rr['uid']);
			logger('Expired queue data :' . $rr['content'], LOGGER_DATA);
		}
		q("DELETE FROM `queue` WHERE `created` < UTC_TIMESTAMP() - INTERVAL 3 DAY");
	}
		
	if($queue_id) {
		$r = q("SELECT `id` FROM `queue` WHERE `id` = %d LIMIT 1",
			intval($queue_id)
		);
	}
	else {

		// For the first 12 hours we'll try to deliver every 15 minutes
		// After that, we'll only attempt delivery once per hour. 

		$r = q("SELECT `id` FROM `queue` WHERE (( `created` > UTC_TIMESTAMP() - INTERVAL 12 HOUR && `last` < UTC_TIMESTAMP() - INTERVAL 15 MINUTE ) OR ( `last` < UTC_TIMESTAMP() - INTERVAL 1 HOUR ))");
	}
	if(! count($r)){
		return;
	}

	if(! $queue_id)
		call_hooks('queue_predeliver', $a, $r);


	// delivery loop

	foreach($r as $q_item) {

		// queue_predeliver hooks may have changed the queue db details, 
		// so check again if this entry still needs processing

		if($queue_id) {
			$qi = q("select * from queue where `id` = %d limit 1",
				intval($queue_id)
			);
		}
		else {
			$qi = q("SELECT * FROM `queue` WHERE `id` = %d AND `last` < UTC_TIMESTAMP() - INTERVAL 15 MINUTE ",
				intval($q_item['id'])
			);
		}
		if(! count($qi))
			continue;


		$c = q("SELECT * FROM `contact` WHERE `id` = %d LIMIT 1",
			intval($qi[0]['cid'])
		);
		if(! count($c)) {
			remove_queue_item($q_item['id']);
			continue;
		}
		if(in_array($c[0]['notify'],$deadguys)) {
				logger('queue: skipping known dead url: ' . $c[0]['notify']);
				update_queue_time($q_item['id']);
				continue;
		}

		$u = q("SELECT `user`.*, `user`.`pubkey` AS `upubkey`, `user`.`prvkey` AS `uprvkey` 
			FROM `user` WHERE `uid` = %d LIMIT 1",
			intval($c[0]['uid'])
		);
		if(! count($u)) {
			remove_queue_item($q_item['id']);
			continue;
		}

		$data      = $qi[0]['content'];
		$public    = $qi[0]['batch'];
		$contact   = $c[0];
		$owner     = $u[0];

		$deliver_status = 0;

		switch($contact['network']) {
			case NETWORK_DFRN:
				logger('queue: dfrndelivery: item ' . $q_item['id'] . ' for ' . $contact['name']);
				$deliver_status = dfrn_deliver($owner,$contact,$data);

				if($deliver_status == (-1)) {
					update_queue_time($q_item['id']);
					$deadguys[] = $contact['notify'];
				}
				else {
					remove_queue_item($q_item['id']);
				}
				break;

			default:
				$params = array('owner' => $owner, 'contact' => $contact, 'queue' => $q_item, 'result' => false);
				call_hooks('queue_deliver', $a, $params);
		
				if($params['result'])
						remove_queue_item($q_item['id']);
				else
						update_queue_time($q_item['id']);
	
				break;

		}
	}
		
	return;

}

if (array_search(__file__,get_included_files())===0){
  queue_run($argv,$argc);
  killme();
}
