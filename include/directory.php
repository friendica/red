<?php

	require_once("boot.php");

	$a = new App;

	@include(".htconfig.php");
	require_once("dba.php");
	$db = new dba($db_host, $db_user, $db_pass, $db_data);
		unset($db_host, $db_user, $db_pass, $db_data);


	if($argc != 2)
		exit;

	load_config('system');

	$a->set_baseurl(get_config('system','url'));

	$dir = get_config('system','directory_submit_url');

	if(! strlen($dir))
		exit;

	fetch_url($dir . '?url=' . bin2hex($argv[1]));

	exit;

