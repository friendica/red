<?php

require_once('include/cli_startup.php');
require_once('include/zot.php');

function gprobe_run($argv, $argc){

	cli_startup();

	$a = get_app();

	if($argc != 2)
		return;

	$url = hex2bin($argv[1]);

	$r = q("select * from xchan where xchan_addr = '%s' limit 1",
		dbesc($url)
	);

	if(! $r) {
		$x = zot_finger($url,null);
		if($x['success']) {
			$j = json_decode($x['body'],true);
			$y = import_xchan($j);
		}
	}

	return;
}

if (array_search(__file__,get_included_files())===0){
  gprobe_run($argv,$argc);
  killme();
}
