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

	$interval = intval(get_config('system','poll_interval'));
	if(! $interval) 
		$interval = ((get_config('system','delivery_interval') === false) ? 3 : intval(get_config('system','delivery_interval')));


	logger('poller: start');
	
	// run queue delivery process in the background

	proc_run('php',"include/queue.php");
	

	// expire any expired mail

	q("delete from mail where expires != '0000-00-00 00:00:00' and expires < UTC_TIMESTAMP() ");

	// expire any expired items

	$r = q("select id from item where expires != '0000-00-00 00:00:00' and expires < UTC_TIMESTAMP() 
		and not ( item_restrict & %d ) ",
		intval(ITEM_DELETED)
	);
	if($r) {
		require_once('include/items.php');
		foreach($r as $rr)
			drop_item($rr['id'],false);
	}


	// Ensure that every channel pings a directory server once a month. This way we can discover
	// channels and sites that quietly vanished and prevent the directory from accumulating stale
	// or dead entries.

	$r = q("select channel_id from channel where channel_dirdate < UTC_TIMESTAMP() - INTERVAL 30 DAY");
	if($r) {
		foreach($r as $rr) {
			proc_run('php','include/directory.php',$rr['channel_id'],'force');
			if($interval)
				@time_sleep_until(microtime(true) + (float) $interval);
		}
	}

	// publish any applicable items that were set to be published in the future
	// (time travel posts)

	$r = q("select id from item where ( item_restrict & %d ) and created <= UTC_TIMESTAMP() ",
		intval(ITEM_DELAYED_PUBLISH)
	);
	if($r) {
		foreach($r as $rr) {
			$x = q("update item set item_restrict = ( item_restrict ^ %d ) where id = %d limit 1",
				intval(ITEM_DELAYED_PUBLISH),
				intval($rr['id'])
			);
			if($x) {
				proc_run('php','include/notifier.php','wall-new',$rr['id']);
			}
		}
	}

	$abandon_days = intval(get_config('system','account_abandon_days'));
	if($abandon_days < 1)
		$abandon_days = 0;

	
	// once daily run birthday_updates and then expire in background

	// FIXME: add birthday updates, both locally and for xprof for use
	// by directory servers

	$d1 = get_config('system','last_expire_day');
	$d2 = intval(datetime_convert('UTC','UTC','now','d'));

	$dirmode = get_config('system','directory_mode');

	/**
	 * Cron Daily
	 *
	 * Actions in the following block are executed once per day, not on every poller run
	 *
	 */

	if($d2 != intval($d1)) {

		call_hooks('cron_daily',datetime_convert());


		$d3 = intval(datetime_convert('UTC','UTC','now','N'));
		if($d3 == 7) {
		
			/**
			 * Cron Weekly
			 * 
			 * Actions in the following block are executed once per day only on Sunday (once per week).
			 *
			 */


			call_hooks('cron_weekly',datetime_convert());



			require_once('include/hubloc.php');
			prune_hub_reinstalls();

			require_once('include/Contact.php');
			mark_orphan_hubsxchans();

		}

		update_birthdays();

		// expire any read notifications over a month old

		q("delete from notify where seen = 1 and date < UTC_TIMESTAMP() - INTERVAL 30 DAY");

		// expire any expired accounts
		require_once('include/account.php');
		downgrade_accounts();

		// If this is a directory server, request a sync with an upstream
		// directory at least once a day, up to once every poll interval. 
		// Pull remote changes and push local changes.
		// potential issue: how do we keep from creating an endless update loop? 

		if($dirmode == DIRECTORY_MODE_SECONDARY || $dirmode == DIRECTORY_MODE_PRIMARY) {
			require_once('include/dir_fns.php');
			sync_directories($dirmode);
		}

		set_config('system','last_expire_day',$d2);

		proc_run('php','include/expire.php');
		proc_run('php','include/cli_suggest.php');

	}

	// update any photos which didn't get imported properly
	// This should be rare

	$r = q("select xchan_photo_l, xchan_hash from xchan where xchan_photo_l != '' and xchan_photo_m = '' 
		and xchan_photo_date < UTC_TIMESTAMP() - INTERVAL 1 DAY");
	if($r) {
		require_once('include/photo/photo_driver.php');
		foreach($r as $rr) {
			$photos = import_profile_photo($rr['xchan_photo_l'],$rr['xchan_hash']);
			$x = q("update xchan set xchan_photo_l = '%s', xchan_photo_m = '%s', xchan_photo_s = '%s', xchan_photo_mimetype = '%s'
				where xchan_hash = '%s' limit 1",
				dbesc($photos[0]),
				dbesc($photos[1]),
				dbesc($photos[2]),
				dbesc($photos[3]),
				dbesc($rr['xchan_hash'])
			);
		}
	}


	// pull in some public posts
        if(! get_config('system','disable_discover_tab'))
		proc_run('php','include/externals.php');


	$manual_id  = 0;
	$generation = 0;

	$force      = false;
	$restart    = false;

	if(($argc > 1) && ($argv[1] == 'force'))
		$force = true;

	if(($argc > 1) && ($argv[1] == 'restart')) {
		$restart = true;
		$generation = intval($argv[2]);
		if(! $generation)
			killme();		
	}

	if(($argc > 1) && intval($argv[1])) {
		$manual_id = intval($argv[1]);
		$force     = true;
	}


	$sql_extra = (($manual_id) ? " AND abook_id = $manual_id " : "");

	reload_plugins();

	$d = datetime_convert();

	//TODO check to see if there are any cronhooks before wasting a process

	if(! $restart)
		proc_run('php','include/cronhooks.php');

	// Only poll from those with suitable relationships

	$abandon_sql = (($abandon_days) 
		? sprintf(" AND account_lastlog > UTC_TIMESTAMP() - INTERVAL %d DAY ", intval($abandon_days)) 
		: '' 
	);


	$contacts = q("SELECT abook_id, abook_flags, abook_updated, abook_connected, abook_closeness, abook_channel
		FROM abook LEFT JOIN account on abook_account = account_id where 1
		$sql_extra 
		AND (( abook_flags & %d ) OR  ( abook_flags = %d )) 
		AND (( account_flags = %d ) OR ( account_flags = %d )) $abandon_sql ORDER BY RAND()",
		intval(ABOOK_FLAG_HIDDEN|ABOOK_FLAG_PENDING|ABOOK_FLAG_UNCONNECTED),
		intval(0),
		intval(ACCOUNT_OK),
		intval(ACCOUNT_UNVERIFIED)     // FIXME

	);

	if($contacts) {

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

				if($c == '0000-00-00 00:00:00') {
					$r = q("update abook set abook_connected = '%s'  where abook_id = %d limit 1",
						dbesc(datetime_convert()),
						intval($contact['abook_id'])
					);
					$c = datetime_convert();
					$update = true;
				}

				// He's dead, Jim

				if(strcmp(datetime_convert('UTC','UTC', 'now'),datetime_convert('UTC','UTC', $c . " + 30 day")) > 0) {	
					$r = q("update abook set abook_flags = (abook_flags | %d) where abook_id = %d limit 1",
						intval(ABOOK_FLAG_ARCHIVED),
						intval($contact['abook_id'])
					);
					$update = false;
					continue;
				}

				if($contact['abook_flags'] & ABOOK_FLAG_ARCHIVED) {
					$update = false;
					continue;
				}

				// might be dead, so maybe don't poll quite so often
	
				// recently deceased, so keep up the regular schedule for 3 days
 
				if((strcmp(datetime_convert('UTC','UTC', 'now'),datetime_convert('UTC','UTC', $c . " + 3 day")) > 0)
				 && (strcmp(datetime_convert('UTC','UTC', 'now'),datetime_convert('UTC','UTC', $t . " + 1 day")) > 0))
					$update = true;

				// After that back off and put them on a morphine drip

				if(strcmp(datetime_convert('UTC','UTC', 'now'),datetime_convert('UTC','UTC', $t . " + 2 day")) > 0) {	
					$update = true;
				}


			}

			if((! $update) && (! $force))
					continue;

			proc_run('php','include/onepoll.php',$contact['abook_id']);
			if($interval)
				@time_sleep_until(microtime(true) + (float) $interval);

		}
	}

	if($dirmode == DIRECTORY_MODE_SECONDARY || $dirmode == DIRECTORY_MODE_PRIMARY) {
		$r = q("select distinct ud_addr, updates.* from updates where not ( ud_flags & %d ) and ud_addr != '' and ( ud_last = '0000-00-00 00:00:00' OR ud_last > UTC_TIMESTAMP() - INTERVAL 7 DAY ) group by ud_addr ",
			intval(UPDATE_FLAGS_UPDATED)
		);
		if($r) {
			foreach($r as $rr) {

				// If they didn't respond when we attempted before, back off to once a day
				// After 7 days we won't bother anymore

				if($rr['ud_last'] != '0000-00-00 00:00:00')
					if($rr['ud_last'] > datetime_convert('UTC','UTC', 'now - 1 day'))
						continue;
				proc_run('php','include/onedirsync.php',$rr['ud_id']);
				if($interval)
					@time_sleep_until(microtime(true) + (float) $interval);
			}
		}
	}
	
	return;
}

if (array_search(__file__,get_included_files())===0){
  poller_run($argv,$argc);
  killme();
}
