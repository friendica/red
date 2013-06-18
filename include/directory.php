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

		// Now update all the connections
		proc_run('php','include/notifier.php','refresh_all',$channel['channel_id']);
		return;
	}

	$directory = find_upstream_directory($dirmode);

	if($directory) {
		$url = $directory['url'];
	}
	else {
		$url = DIRECTORY_FALLBACK_MASTER . '/post';
	}

	// ensure the upstream directory is updated

	$packet = zot_build_packet($channel,'refresh');
	$z = zot_zot($url,$packet);
	// re-queue if unsuccessful

	// Now update all the connections

	proc_run('php','include/notifier.php','refresh_all',$channel['channel_id']);

}

if (array_search(__file__,get_included_files())===0){
  directory_run($argv,$argc);
  killme();
}
