<?php /** @file */

require_once('boot.php');

// Everything we need to boot standalone 'background' processes

function cli_startup() {

	global $a, $db, $default_timezone;

	if(is_null($a)) {
		$a = new App;
	}
  
	if(is_null($db)) {
	    @include(".htconfig.php");

		$a->timezone = ((x($default_timezone)) ? $default_timezone : 'UTC');
		date_default_timezone_set($a->timezone);

    	require_once('include/dba/dba_driver.php');
	    $db = dba_factory($db_host, $db_port, $db_user, $db_pass, $db_data, $db_type);
    	unset($db_host, $db_port, $db_user, $db_pass, $db_data, $db_type);
  	};

	require_once('include/session.php');

	load_config('system');

	$a->set_baseurl(get_config('system','baseurl'));

	load_hooks();

}