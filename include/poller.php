<?php /** @file */

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
		SET account_flags = (account_flags | %d) 
		where not (account_flags & %d) 
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

		// If this is a directory server, request a sync with an upstream
		// directory at least once a day, up to once every poll interval. 
		// Pull remote changes and push local changes.
		// potential issue: how do we keep from creating an endless update loop? 

		$dirmode = get_config('system','directory_mode');
		if($dirmode == DIRECTORY_MODE_SECONDARY || $dirmode == DIRECTORY_MODE_PRIMARY) {
			require_once('include/dir_fns.php');
			sync_directories($dirmode);
		}

//		update_suggestions();

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
		FROM abook LEFT JOIN account on abook_account = account_id where 1
		$sql_extra 
		AND (( abook_flags = %d ) OR  ( abook_flags = %d )) 
		AND (( account_flags = %d ) OR ( account_flags = %d )) $abandon_sql ORDER BY RAND()",

		intval(ABOOK_FLAG_HIDDEN),
		intval(0),
		intval(ACCOUNT_OK),
		intval(ACCOUNT_UNVERIFIED)     // FIXME

	);

	if(! $contacts) {
		return;
	}

	foreach($contacts as $contact) {

		$update  = false;

		$t = $contact['abook_updated'];
		$c = $contact['abook_connected'];


		if($c == $t) {
			if(datetime_convert('UTC','UTC', 'now') > datetime_convert('UTC','UTC', $t . " + 1 day"))
				$update = true;
		}
		else {
			// if we've never connected with them, start the mark for death countdown from now

			if($c === '0000-00-00 00:00:00') {
				$r = q("update abook set abook_connected = '%s'  where abook_id = %d limit 1",
					dbesc(datetime_convert()),
					intval($abook['abook_id'])
				);
				$c = datetime_convert();
				$update = true;
			}

			// He's dead, Jim

			if(datetime_convert('UTC','UTC', 'now') > datetime_convert('UTC','UTC', $c . " + 30 day")) {
				$r = q("update abook set abook_flags = (abook_flags & %d) where abook_id = %d limit 1",
					intval(ABOOK_FLAG_ARCHIVED),
					intval($contact['abook_id'])
				);
				$update = false;
				continue;
			}

			// might be dead, so maybe don't poll quite so often

			// recently deceased, so keep up the regular schedule for 3 days
 
			if((datetime_convert('UTC','UTC', 'now') > datetime_convert('UTC','UTC', $c . " + 3 day"))
			 && (datetime_convert('UTC','UTC', 'now') > datetime_convert('UTC','UTC', $t . " + 1 day")))
				$update = true;

			// After that back off and put them on a morphine drip

			if(datetime_convert('UTC','UTC', 'now') > datetime_convert('UTC','UTC', $t . " + 2 day")) {
				$update = true;
			}
		}

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
