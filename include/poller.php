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

	// Check for a lockfile.  If it exists, but is over an hour old, it's stale.  Ignore it.
	$lockfile = 'store/[data]/poller';
	if((file_exists($lockfile)) && (filemtime($lockfile) > (time() - 3600)) 
		&& (! get_config('system','override_poll_lockfile'))) {
		logger("poller: Already running");
		return;
	}
	
	// Create a lockfile.  Needs two vars, but $x doesn't need to contain anything.
	file_put_contents($lockfile, $x);

	logger('poller: start');
	
	// run queue delivery process in the background

	proc_run('php',"include/queue.php");
	

	// expire any expired mail

	q("delete from mail where expires != '%s' and expires < %s ",
		dbesc(NULL_DATE),
		db_utcnow()
	);

	// expire any expired items

	$r = q("select id from item where expires != '%s' and expires < %s 
		and ( item_restrict & %d ) = 0 ",
		dbesc(NULL_DATE),
		db_utcnow(),
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

	$r = q("select channel_id from channel where channel_dirdate < %s - INTERVAL %s",
		db_utcnow(), 
		db_quoteinterval('30 DAY')
	);
	if($r) {
		foreach($r as $rr) {
			proc_run('php','include/directory.php',$rr['channel_id'],'force');
			if($interval)
				@time_sleep_until(microtime(true) + (float) $interval);
		}
	}

	// publish any applicable items that were set to be published in the future
	// (time travel posts)

	$r = q("select id from item where ( item_restrict & %d ) > 0 and created <= %s ",
		intval(ITEM_DELAYED_PUBLISH),
		db_utcnow()
	);
	if($r) {
		foreach($r as $rr) {
			$x = q("update item set item_restrict = ( item_restrict & ~%d ) where id = %d",
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

	$d1 = intval(get_config('system','last_expire_day'));
	$d2 = intval(datetime_convert('UTC','UTC','now','d'));

	// Allow somebody to staggger daily activities if they have more than one site on their server,
	// or if it happens at an inconvenient (busy) hour.

	$h1 = intval(get_config('system','cron_hour'));
	$h2 = intval(datetime_convert('UTC','UTC','now','G'));

	$dirmode = get_config('system','directory_mode');

	/**
	 * Cron Daily
	 *
	 * Actions in the following block are executed once per day, not on every poller run
	 *
	 */

	if(($d2 != $d1) && ($h1 == $h2)) {

		require_once('include/dir_fns.php');
		check_upstream_directory();

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


			z_check_cert();

			require_once('include/hubloc.php');
			prune_hub_reinstalls();

			require_once('include/Contact.php');
			mark_orphan_hubsxchans();


			// get rid of really old poco records

			q("delete from xlink where xlink_updated < %s - INTERVAL %s and xlink_static = 0 ",
				db_utcnow(), db_quoteinterval('14 DAY')
			);

			$dirmode = intval(get_config('system','directory_mode'));
			if($dirmode == DIRECTORY_MODE_SECONDARY) {
				logger('regdir: ' . print_r(z_fetch_url(get_directory_primary() . '/regdir?f=&url=' . z_root() . '&realm=' . get_directory_realm()),true));
			}

			/**
			 * End Cron Weekly
			 */
		}

		update_birthdays();

		//update statistics in config
		require_once('include/statistics_fns.php');
		update_channels_total_stat();
		update_channels_active_halfyear_stat();
		update_channels_active_monthly_stat();
		update_local_posts_stat();

		// expire any read notifications over a month old

		q("delete from notify where seen = 1 and date < %s - INTERVAL %s",
			db_utcnow(), db_quoteinterval('30 DAY')
		);

		// expire any expired accounts
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

		require_once('include/hubloc.php');
		remove_obsolete_hublocs();

		/**
		 * End Cron Daily
		 */
	}

	// update any photos which didn't get imported properly
	// This should be rare

	$r = q("select xchan_photo_l, xchan_hash from xchan where xchan_photo_l != '' and xchan_photo_m = '' 
		and xchan_photo_date < %s - INTERVAL %s",
		db_utcnow(), 
		db_quoteinterval('1 DAY')
	);
	if($r) {
		require_once('include/photo/photo_driver.php');
		foreach($r as $rr) {
			$photos = import_profile_photo($rr['xchan_photo_l'],$rr['xchan_hash']);
			$x = q("update xchan set xchan_photo_l = '%s', xchan_photo_m = '%s', xchan_photo_s = '%s', xchan_photo_mimetype = '%s'
				where xchan_hash = '%s'",
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


	$sql_extra = (($manual_id) ? " AND abook_id = " . intval($manual_id) . " " : "");

	reload_plugins();

	$d = datetime_convert();

	// TODO check to see if there are any cronhooks before wasting a process

	if(! $restart)
		proc_run('php','include/cronhooks.php');

	// Only poll from those with suitable relationships

	$abandon_sql = (($abandon_days) 
		? sprintf(" AND account_lastlog > %s - INTERVAL %s ", db_utcnow(), db_quoteinterval(intval($abandon_days).' DAY')) 
		: '' 
	);

	$randfunc = db_getfunc('RAND');
	
	$contacts = q("SELECT abook_id, abook_flags, abook_updated, abook_connected, abook_closeness, abook_xchan, abook_channel, xchan_network
		FROM abook LEFT JOIN xchan on abook_xchan = xchan_hash LEFT JOIN account on abook_account = account_id
		$sql_extra 
		AND (( abook_flags & %d ) > 0 OR  ( abook_flags = %d )) 
		AND (( account_flags = %d ) OR ( account_flags = %d )) $abandon_sql ORDER BY $randfunc",
		intval(ABOOK_FLAG_HIDDEN|ABOOK_FLAG_PENDING|ABOOK_FLAG_UNCONNECTED|ABOOK_FLAG_FEED),
		intval(0),
		intval(ACCOUNT_OK),
		intval(ACCOUNT_UNVERIFIED)     // FIXME

	);

	if($contacts) {

		foreach($contacts as $contact) {

			if($contact['abook_flags'] & ABOOK_FLAG_SELF)
				continue;

			$update  = false;

			$t = $contact['abook_updated'];
			$c = $contact['abook_connected'];

			if($contact['abook_flags'] & ABOOK_FLAG_FEED) {
				$min = service_class_fetch($contact['abook_channel'],'minimum_feedcheck_minutes');
				if(! $min)
					$min = intval(get_config('system','minimum_feedcheck_minutes'));
				if(! $min)
					$min = 60;
				$x = datetime_convert('UTC','UTC',"now - $min minutes");
				if($c < $x) {
					proc_run('php','include/onepoll.php',$contact['abook_id']);
					if($interval)
						@time_sleep_until(microtime(true) + (float) $interval);
				}
				continue;
			}


			if($contact['xchan_network'] !== 'zot')
				continue;

			if($c == $t) {
				if(datetime_convert('UTC','UTC', 'now') > datetime_convert('UTC','UTC', $t . " + 1 day"))
					$update = true;
			}
			else {

				// if we've never connected with them, start the mark for death countdown from now

				if($c == NULL_DATE) {
					$r = q("update abook set abook_connected = '%s'  where abook_id = %d",
						dbesc(datetime_convert()),
						intval($contact['abook_id'])
					);
					$c = datetime_convert();
					$update = true;
				}

				// He's dead, Jim

				if(strcmp(datetime_convert('UTC','UTC', 'now'),datetime_convert('UTC','UTC', $c . " + 30 day")) > 0) {	
					$r = q("update abook set abook_flags = (abook_flags | %d) where abook_id = %d",
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

			if($contact['abook_flags'] & (ABOOK_FLAG_PENDING|ABOOK_FLAG_ARCHIVED|ABOOK_FLAG_IGNORED))
				continue;

			if((! $update) && (! $force))
					continue;

			proc_run('php','include/onepoll.php',$contact['abook_id']);
			if($interval)
				@time_sleep_until(microtime(true) + (float) $interval);

		}
	}

	if($dirmode == DIRECTORY_MODE_SECONDARY || $dirmode == DIRECTORY_MODE_PRIMARY) {
		$r = q("SELECT u.ud_addr, u.ud_id, u.ud_last FROM updates AS u INNER JOIN (SELECT ud_addr, max(ud_id) AS ud_id FROM updates WHERE ( ud_flags & %d ) = 0 AND ud_addr != '' AND ( ud_last = '%s' OR ud_last > %s - INTERVAL %s ) GROUP BY ud_addr) AS s ON s.ud_id = u.ud_id ",
			intval(UPDATE_FLAGS_UPDATED),
			dbesc(NULL_DATE),
			db_utcnow(), db_quoteinterval('7 DAY')
		);
		if($r) {
			foreach($r as $rr) {

				// If they didn't respond when we attempted before, back off to once a day
				// After 7 days we won't bother anymore

				if($rr['ud_last'] != NULL_DATE)
					if($rr['ud_last'] > datetime_convert('UTC','UTC', 'now - 1 day'))
						continue;
				proc_run('php','include/onedirsync.php',$rr['ud_id']);
				if($interval)
					@time_sleep_until(microtime(true) + (float) $interval);
			}
		}
	}

	set_config('system','lastpoll',datetime_convert());

	//All done - clear the lockfile	
	@unlink($lockfile);

	return;
}

if (array_search(__file__,get_included_files())===0){
  poller_run($argv,$argc);
  killme();
}
