<?php /** @file */

/**
 * Send a zot packet to all hubs where this channel is duplicated, refreshing
 * such things as personal settings, channel permissions, address book updates, etc.
 */

require_once('include/zot.php');

function build_sync_packet($uid = 0, $packet = null) {
	$a = get_app();

	if(! $uid)
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

	$r = q("select xchan_guid, xchan_guid_sig from xchan where xchan_hash  = '%s' limit 1",
		dbesc($channel['channel_hash'])
	);
	if(! $r)
		return;

	$env_recips = array();
	$env_recips[] = array('guid' => $r[0]['xchan_guid'],'guid_sig' => $r[0]['xchan_guid_sig']);

	$info = (($packet) ? $packet : array());
	$info['type'] = 'channel_sync';

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

			// don't pass these elements, they should not be synchronised

			$disallowed = array('channel_id','channel_account_id','channel_primary','channel_prvkey');

			if(in_array($k,$disallowed))
				continue;

			$info['channel'][$k] = $v;
		}
	}

	$interval = ((get_config('system','delivery_interval') !== false) 
			? intval(get_config('system','delivery_interval')) : 2 );


	foreach($synchubs as $hub) {
		$hash = random_string();
		$n = zot_build_packet($channel,'notify',$env_recips,$hub['hubloc_sitekey'],null,$hash);
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
