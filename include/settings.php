<?php /** @file */

function build_sync_packet($packet = null) {
	$a = get_app();

	$uid = local_user();
	if(! $uid)
		return;

	$channel = $a->get_channel();

	$h = q("select * from hubloc where hubloc_hash = '%s'",
		dbesc($channel['channel_hash'])
	);

	if(! $h)
		return;

	$synchubs = array();

	foreach($h as $x) {
		if($x['host'] == $a->get_hostname())
			continue;
		$synchubs[] = $x;
	}

	if(! $synchubs)
		return;

	$info = (($packet) ? $packet : array());

	if(array_key_exists($uid,$a->config) && array_key_exists('transient',$a->config[$uid])) {
		$settings = $a->config[$uid]['transient'];
		if($settings) {
			$info['config'] = $settings;
		}
	}
	
	if($channel) {
		$info['channel'] = array();
		foreach($channel as $k => $v) {
			if(strpos('channel_',$k) !== 0)
				continue;
			if($k === 'channel_id')
				continue;
			if($k === 'channel_account_id')
				continue;
			$info['channel'][$k] = $v;
		}
	}

	$interval = ((get_config('system','delivery_interval') !== false) 
			? intval(get_config('system','delivery_interval')) : 2 );


	foreach($synchubs as $hub) {
		$hash = random_string();
		$n = zot_build_packet($channel,'channel_sync');
		q("insert into outq ( outq_hash, outq_account, outq_channel, outq_posturl, outq_async, outq_created, outq_updated, outq_notify, outq_msg ) values ( '%s', %d, %d, '%s', %d, '%s', '%s', '%s', '%s' )",
			dbesc($hash),
			intval($channel['channel_account']),
			intval($channel['channel_id']),
			dbesc($hub['hubloc_callback']),
			intval(1),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			dbesc($n),
			dbesc(json_encode($info))
		);

		proc_run('php','include/deliver.php',$hash);
		if($interval)
			@time_sleep_until(microtime(true) + (float) $interval);
	}


}