<?php /** @file */

require_once('boot.php');
require_once('include/cli_startup.php');
require_once('include/zot.php');
require_once('include/dir_fns.php');


function onedirsync_run($argv, $argc){


	cli_startup();
	$a = get_app();

	logger('onedirsync: start ' . intval($argv[1]));
	
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
	if(($r[0]['ud_flags'] & UPDATE_FLAGS_UPDATED) || (! $r[0]['ud_addr']))
		return;

	// Have we probed this channel more recently than the other directory server
	// (where we received this update from) ?
	// If we have, we don't need to do anything except mark any older entries updated

	$x = q("select * from updates where ud_addr = '%s' and ud_date > '%s' and ( ud_flags & %d ) order by ud_date desc limit 1",
		dbesc($r[0]['ud_addr']),
		dbesc($r[0]['ud_date']),
		intval(UPDATE_FLAGS_UPDATED)
	);
	if($x) {
		$y = q("update updates set ud_flags = ( ud_flags | %d ) where ud_addr = '%s' and not ( ud_flags & %d ) and ud_date < '%s' ",
			intval(UPDATE_FLAGS_UPDATED),
			dbesc($r[0]['ud_addr']),
			intval(UPDATE_FLAGS_UPDATED),
			dbesc($x[0]['ud_date'])
		);
		return;
	}

	update_directory_entry($r[0]);		

	return;
}

if (array_search(__file__,get_included_files())===0){
  onedirsync_run($argv,$argc);
  killme();
}
