<?php /** @file */
require_once("boot.php");
require_once('include/cli_startup.php');
require_once('include/queue_fn.php');
require_once('include/zot.php');

function queue_run($argv, $argc){

	cli_startup();

	global $a;

	require_once('include/items.php');
	require_once('include/bbcode.php');

	if(argc() > 1)
		$queue_id = argv(1);
	else
		$queue_id = 0;

	$deadguys = array();

	logger('queue: start');

	$r = q("DELETE FROM outq WHERE outq_created < %s - INTERVAL %s",
		db_utcnow(), db_quoteinterval('3 DAY')
	);

	if($queue_id) {
		$r = q("SELECT * FROM outq WHERE outq_hash = '%s' LIMIT 1",
			dbesc($queue_id)
		);
	}
	else {

		// For the first 12 hours we'll try to deliver every 15 minutes
		// After that, we'll only attempt delivery once per hour. 
		// This currently only handles the default queue drivers ('zot' or '') which we will group by posturl 
		// so that we don't start off a thousand deliveries for a couple of dead hubs.
		// The zot driver will deliver everything destined for a single hub once contact is made (*if* contact is made).
		// Other drivers will have to do something different here and may need their own query.

		// Note: this requires some tweaking as new posts to long dead hubs once a day will keep them in the 
		// "every 15 minutes" category. We probably need to prioritise them when inserted into the queue
		// or just prior to this query based on recent and long-term delivery history. If we have good reason to believe
		// the site is permanently down, there's no reason to attempt delivery at all, or at most not more than once 
		// or twice a day. 

		// FIXME: can we sort postgres on outq_priority and maintain the 'distinct' ?
		// The order by max(outq_priority) might be a dodgy query because of the group by.
		// The desired result is to return a sequence in the order most likely to be delivered in this run.
		// If a hub has already been sitting in the queue for a few days, they should be delivered last;
		// hence every failure should drop them further down the priority list.
 
		if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) {
			$prefix = 'DISTINCT ON (outq_posturl)';
			$suffix = 'ORDER BY outq_posturl';
		} else {
			$prefix = '';
			$suffix = 'GROUP BY outq_posturl ORDER BY max(outq_priority)';
		}
		$r = q("SELECT $prefix * FROM outq WHERE outq_delivered = 0 and (( outq_created > %s - INTERVAL %s and outq_updated < %s - INTERVAL %s ) OR ( outq_updated < %s - INTERVAL %s )) $suffix",
			db_utcnow(), db_quoteinterval('12 HOUR'),
			db_utcnow(), db_quoteinterval('15 MINUTE'),
			db_utcnow(), db_quoteinterval('1 HOUR')
		);
	}
	if(! $r)
		return;

	foreach($r as $rr) {
		if(in_array($rr['outq_posturl'],$deadguys))
			continue;

		if($rr['outq_driver'] === 'post') {
			$result = z_post_url($rr['outq_posturl'],$rr['outq_msg']); 
			if($result['success'] && $result['return_code'] < 300) {
				logger('queue: queue post success to ' . $rr['outq_posturl'], LOGGER_DEBUG);
				$y = q("delete from outq where outq_hash = '%s'",
					dbesc($rr['ouq_hash'])
				);
			}
			else {
				logger('queue: queue post returned ' . $result['return_code'] . ' from ' . $rr['outq_posturl'],LOGGER_DEBUG);
				$y = q("update outq set outq_updated = '%s', outq_priority = outq_priority + 10 where outq_hash = '%s'",
					dbesc(datetime_convert()),
					dbesc($rr['outq_hash'])
				);
			}
			continue;
		}
		$result = zot_zot($rr['outq_posturl'],$rr['outq_notify']); 
		if($result['success']) {
			logger('queue: deliver zot success to ' . $rr['outq_posturl'], LOGGER_DEBUG);			
			zot_process_response($rr['outq_posturl'],$result, $rr);				
		}
		else {
			$deadguys[] = $rr['outq_posturl'];
			logger('queue: deliver zot returned ' . $result['return_code'] . ' from ' . $rr['outq_posturl'],LOGGER_DEBUG);
			$y = q("update outq set outq_updated = '%s', outq_priority = outq_priority + 10 where outq_hash = '%s'",
				dbesc(datetime_convert()),
				dbesc($rr['outq_hash'])
			);
		}
	}
}

if (array_search(__file__,get_included_files())===0){
  queue_run($argv,$argc);
  killme();
}
