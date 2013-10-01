<?php /** @file */

require_once('boot.php');
require_once('include/cli_startup.php');
require_once('include/zot.php');
require_once('include/dir_fns.php');


function onedirsync_run($argv, $argc){


	cli_startup();
	$a = get_app();

	logger('onedirsync: start');
	
	if(($argc > 1) && (intval($argv[1])))
		$update_id = intval($argv[1]);

	if(! $update_id) {
		logger('onedirsync: no update');
		return;
	}
	
	$r = q("select * from updates where ud_id = %d limit 1",
		intval($update_id)
	);

	if(! $r)
		return;
	if($r['ud_flags'] & UPDATE_FLAGS_UPDATED)
		return;

	update_directory_entry($r[0]);		

	return;
}

if (array_search(__file__,get_included_files())===0){
  onedirsync_run($argv,$argc);
  killme();
}
