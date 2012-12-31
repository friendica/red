<?php

require_once('boot.php');
require_once('include/cli_startup.php');


function poller_run($argv, $argc){

	cli_startup();

	$a = get_app();

	$maxsysload = intval(get_config('system','maxloadavg'));
	if($maxsysload < 1)
		$maxsysload = 50;
	if(function_exists('sys_getloadavg')) {
		$load = sys_getloadavg();
		if(intval($load[0]) > $maxsysload) {
			logger('system: load ' . $load . ' too high. Poller deferred to next scheduled run.');
			return;
		}
	}

	logger('poller: start');
	
	// run queue delivery process in the background

	proc_run('php',"include/queue.php");
	
	// expire any expired accounts

	q("UPDATE account 
		SET account_flags = account_flags | %d 
		where not account_flags & %d 
		and account_expires != '0000-00-00 00:00:00' 
		and account_expires < UTC_TIMESTAMP() ",
		intval(ACCOUNT_EXPIRED),
		intval(ACCOUNT_EXPIRED)
	);
  
	$abandon_days = intval(get_config('system','account_abandon_days'));
	if($abandon_days < 1)
		$abandon_days = 0;

	
	// once daily run birthday_updates and then expire in background

	$d1 = get_config('system','last_expire_day');
	$d2 = intval(datetime_convert('UTC','UTC','now','d'));

	if($d2 != intval($d1)) {

		update_suggestions();

		set_config('system','last_expire_day',$d2);
		proc_run('php','include/expire.php');
	}


	$manual_id  = 0;
	$generation = 0;

	$force      = false;
	$restart    = false;

	if((argc() > 1) && (argv(1) == 'force'))
		$force = true;

	if((argc() > 1) && (argv(1) == 'restart')) {
		$restart = true;
		$generation = intval(argv(2));
		if(! $generation)
			killme();		
	}

	if((argc() > 1) && intval(argv(1))) {
		$manual_id = intval(argv(1));
		$force     = true;
	}

	$interval = intval(get_config('system','poll_interval'));
	if(! $interval) 
		$interval = ((get_config('system','delivery_interval') === false) ? 3 : intval(get_config('system','delivery_interval')));

	$sql_extra = (($manual_id) ? " AND abook_id = $manual_id " : "");

	reload_plugins();

	$d = datetime_convert();

//TODO check to see if there are any cronhooks before wasting a process

	if(! $restart)
		proc_run('php','include/cronhooks.php');

	// Only poll from those with suitable relationships,
	// and which have a polling address and ignore Diaspora since 
	// we are unable to match those posts with a Diaspora GUID and prevent duplicates.

	$abandon_sql = (($abandon_days) 
		? sprintf(" AND account_lastlog > UTC_TIMESTAMP() - INTERVAL %d DAY ", intval($abandon_days)) 
		: '' 
	);

	$contacts = q("SELECT abook_id, abook_updated, abook_closeness, abook_channel 
		FROM abook LEFT JOIN account on abook_account = account_id 
		$sql_extra 
		AND not ( abook_flags & %d ) AND not ( abook_flags & %d ) 
		AND not ( abook_flags & %d ) AND not ( abook_flags & %d ) 
		AND not ( abook_flags & %d ) AND ( account_flags & %d ) $abandon_sql ORDER BY RAND()",

		intval(ABOOK_FLAG_BLOCKED),
		intval(ABOOK_FLAG_IGNORED),
		intval(ABOOK_FLAG_PENDING),
		intval(ABOOK_FLAG_ARCHIVED),
		intval(ABOOK_FLAG_SELF),
		intval(ACCOUNT_OK)

	);

	if(! $contacts) {
		return;
	}

	foreach($contacts as $contact) {

		$update  = false;

		$t = $contact['abook_updated'];

		if(datetime_convert('UTC','UTC', 'now') > datetime_convert('UTC','UTC', $t . " + 1 day"))
			$update = true;

		if((! $update) && (! $force))
				continue;

		proc_run('php','include/onepoll.php',$contact['abook_id']);
		if($interval)
			@time_sleep_until(microtime(true) + (float) $interval);

	}

	return;
}

if (array_search(__file__,get_included_files())===0){
  poller_run($argv,$argc);
  killme();
}
