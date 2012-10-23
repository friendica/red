<?php

require_once('boot.php');
require_once('include/cli_startup.php');


function cronhooks_run($argv, $argc){

	cli_startup();

	logger('cronhooks: start');
	
	$d = datetime_convert();

	call_hooks('cron', $d);

	return;
}

if (array_search(__file__,get_included_files())===0){
  cronhooks_run($argv,$argc);
  killme();
}
