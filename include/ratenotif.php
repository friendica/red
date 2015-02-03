<?php

require_once('include/cli_startup.php');
require_once('include/zot.php');
require_once('include/queue_fn.php');


function ratenotif_run($argv, $argc){

	cli_startup();

	$a = get_app();

	require_once("session.php");
	require_once("datetime.php");
	require_once('include/items.php');
	require_once('include/Contact.php');

	if($argc < 3)
		return;


	logger('ratenotif: invoked: ' . print_r($argv,true), LOGGER_DEBUG);

	$cmd = $argv[1];

	$item_id = $argv[2];


	if($cmd === 'rating') {
		$r = q("select * from xlink where xlink_id = %d and xlink_static = 1 limit 1",
			intval($item_id)
		);
		if(! $r) {
			logger('rating not found');
			return;
		}

		$encoded_item = array(
			'type' => 'rating', 
			'encoding' => 'zot',
			'target' => $r[0]['xlink_link'],
			'rating' => intval($r[0]['xlink_rating']),
			'rating_text' => $r[0]['xlink_rating_text'],
			'signature' => $r[0]['xlink_sig'],
			'edited' => $r[0]['xlink_updated']
		);
	}

	$channel = channelx_by_hash($r[0]['xlink_xchan']);
	if(! $channel) {
		logger('no channel');
		return;
	}


	$primary = get_directory_primary();

	if(! $primary)
		return;


	$interval = ((get_config('system','delivery_interval') !== false) 
		? intval(get_config('system','delivery_interval')) : 2 );

	$deliveries_per_process = intval(get_config('system','delivery_batch_count'));

	if($deliveries_per_process <= 0)
		$deliveries_per_process = 1;

	$deliver = array();

	$x = z_fetch_url($primary . '/regdir');
	if($x['success']) {
		$j = json_decode($x['body'],true);
		if($j && $j['success'] && is_array($j['directories'])) {

			foreach($j['directories'] as $h) {
//				if($h == z_root())
//					continue;

				$hash = random_string();
				$n = zot_build_packet($channel,'notify',null,null,$hash);

				q("insert into outq ( outq_hash, outq_account, outq_channel, outq_driver, outq_posturl, outq_async, outq_created, outq_updated, outq_notify, outq_msg ) values ( '%s', %d, %d, '%s', '%s', %d, '%s', '%s', '%s', '%s' )",
					dbesc($hash),
					intval($channel['channel_account_id']),
					intval($channel['channel_id']),
					dbesc('zot'),
					dbesc($h . '/post'),
					intval(1),
					dbesc(datetime_convert()),
					dbesc(datetime_convert()),
					dbesc($n),
					dbesc(json_encode($encoded_item))
				);
			}
			$deliver[] = $hash;

			if(count($deliver) >= $deliveries_per_process) {
				proc_run('php','include/deliver.php',$deliver);
				$deliver = array();
				if($interval)
					@time_sleep_until(microtime(true) + (float) $interval);
			}


			// catch any stragglers

			if(count($deliver)) {
			proc_run('php','include/deliver.php',$deliver);
			}
		}
	}
		
	logger('ratenotif: complete.');
	return;

}

if (array_search(__file__,get_included_files())===0){
  ratenotif_run($argv,$argc);
  killme();
}
