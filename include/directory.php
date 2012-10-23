<?php
require_once('boot.php');
require_once('include/cli_startup.php');

function directory_run($argv, $argc){

	cli_startup();		 

	if($argc != 2)
		return;

	$dir = get_config('system','directory_submit_url');

	if(! strlen($dir))
		return;

	$arr = array('url' => $argv[1]);

	call_hooks('globaldir_update', $arr);

	if(strlen($arr['url']))
		fetch_url($dir . '?url=' . bin2hex($arr['url']));

	return;
}

if (array_search(__file__,get_included_files())===0){
  directory_run($argv,$argc);
  killme();
}
