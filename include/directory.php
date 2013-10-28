<?php /** @file */

require_once('boot.php');
require_once('include/zot.php');
require_once('include/cli_startup.php');
require_once('include/dir_fns.php');


function directory_run($argv, $argc){

	cli_startup();		 

	if($argc != 2)
		return;

	logger('directory update', LOGGER_DEBUG);

	$dirmode = get_config('system','directory_mode');
	if($dirmode === false)
		$dirmode = DIRECTORY_MODE_NORMAL;

	$x = q("select * from channel where channel_id = %d limit 1",
		intval($argv[1])
	);
	if(! $x)
		return;

	$channel = $x[0];


	if(($dirmode == DIRECTORY_MODE_PRIMARY) || ($dirmode == DIRECTORY_MODE_STANDALONE)) {
		syncdirs($argv[1]);

		q("update channel set channel_dirdate = '%s' where channel_id = %d limit 1",
			dbesc(datetime_convert()),
			intval($channel['channel_id'])
		);


		// Now update all the connections
		proc_run('php','include/notifier.php','refresh_all',$channel['channel_id']);
		return;
	}

	$directory = find_upstream_directory($dirmode);

	if($directory) {
		$url = $directory['url'] . '/post';
	}
	else {
		$url = DIRECTORY_FALLBACK_MASTER . '/post';
	}

	// ensure the upstream directory is updated

	$packet = zot_build_packet($channel,'refresh');
	$z = zot_zot($url,$packet);

	// re-queue if unsuccessful

	if(! $z['success']) {

		// FIXME - we aren't updating channel_dirdate if we have to queue
		// the directory packet. That means we'll try again on the next poll run.

		$hash = random_string();
		q("insert into outq ( outq_hash, outq_account, outq_channel, outq_posturl, outq_async, outq_created, outq_updated, outq_notify, outq_msg ) 
			values ( '%s', %d, %d, '%s', %d, '%s', '%s', '%s', '%s' )",
			dbesc($hash),
			intval($channel['channel_account_id']),
			intval($channel['channel_id']),
			dbesc($url),
			intval(1),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			dbesc($packet),
			dbesc('')
		);
	}
	else {
		q("update channel set channel_dirdate = '%s' where channel_id = %d limit 1",
			dbesc(datetime_convert()),
			intval($channel['channel_id'])
		);
	}

	// Now update all the connections

	proc_run('php','include/notifier.php','refresh_all',$channel['channel_id']);

}

if (array_search(__file__,get_included_files())===0){
  directory_run($argv,$argc);
  killme();
}
