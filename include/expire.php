<?php

require_once("boot.php");

function expire_run($argv, $argc){
	global $a, $db;

	if(is_null($a)) {
		$a = new App;
	}
  
	if(is_null($db)) {
	    @include(".htconfig.php");
    	require_once("dba.php");
	    $db = new dba($db_host, $db_user, $db_pass, $db_data);
    	unset($db_host, $db_user, $db_pass, $db_data);
  	};

	require_once('include/session.php');
	require_once('include/datetime.php');
	require_once('library/simplepie/simplepie.inc');
	require_once('include/items.php');
	require_once('include/Contact.php');

	load_config('config');
	load_config('system');


	$a->set_baseurl(get_config('system','url'));


	logger('expire: start');
	
	$r = q("SELECT `uid`,`username`,`expire` FROM `user` WHERE `expire` != 0");
	if(count($r)) {
		foreach($r as $rr) {
			logger('Expire: ' . $rr['username'] . ' interval: ' . $rr['expire'], LOGGER_DEBUG);
			item_expire($rr['uid'],$rr['expire']);
		}
	}

	return;
}

if (array_search(__file__,get_included_files())===0){
  expire_run($argv,$argc);
  killme();
}
