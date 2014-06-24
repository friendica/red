<?php /** @file */

require_once('include/cli_startup.php');
require_once('include/zot.php');


function deliver_run($argv, $argc) {

	cli_startup();

	$a = get_app();

	if($argc < 2)
		return;

	logger('deliver: invoked: ' . print_r($argv,true), LOGGER_DEBUG);

	for($x = 1; $x < $argc; $x ++) {
		$r = q("select * from outq where outq_hash = '%s' limit 1",
			dbesc($argv[$x])
		);
		if($r) {
			if($r[0]['outq_posturl'] === z_root() . '/post') {
				// local delivery
				// we should probably batch these and save a few delivery processes
				// If there is no outq_msg, this is a refresh_all message which does not require local handling
				if($r[0]['outq_msg']) {
					$msg = array('body' => json_encode(array('pickup' => array(array('notify' => json_decode($r[0]['outq_notify'],true),'message' => json_decode($r[0]['outq_msg'],true))))));

					zot_import($msg,z_root());
					$r = q("delete from outq where outq_hash = '%s' limit 1",
						dbesc($argv[$x])
					);
				}
			}
			else {
				$result = zot_zot($r[0]['outq_posturl'],$r[0]['outq_notify']); 
				if($result['success']) {
					zot_process_response($r[0]['outq_posturl'],$result, $r[0]);				
				}
				else {
					$y = q("update outq set outq_updated = '%s' where outq_hash = '%s' limit 1",
						dbesc(datetime_convert()),
						dbesc($argv[$x])
					);
				}
			}
		}
	}
}

if (array_search(__file__,get_included_files())===0){
  deliver_run($argv,$argc);
  killme();
}
