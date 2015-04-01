<?php /** @file */

require_once('boot.php');
require_once('include/cli_startup.php');
require_once('include/zot.php');
require_once('include/socgraph.php');
require_once('include/Contact.php');


function onepoll_run($argv, $argc){


	cli_startup();
	$a = get_app();

	logger('onepoll: start');
	
	if(($argc > 1) && (intval($argv[1])))
		$contact_id = intval($argv[1]);

	if(! $contact_id) {
		logger('onepoll: no contact');
		return;
	}

	$d = datetime_convert();

	$contacts = q("SELECT abook.*, xchan.*, account.*
		FROM abook LEFT JOIN account on abook_account = account_id left join xchan on xchan_hash = abook_xchan 
		where abook_id = %d
		AND (( abook_flags & %d )>0 OR ( abook_flags = %d ))
		AND NOT ( abook_flags & %d )>0
		AND (( account_flags = %d ) OR ( account_flags = %d )) limit 1",
		intval($contact_id),
		intval(ABOOK_FLAG_HIDDEN|ABOOK_FLAG_PENDING|ABOOK_FLAG_UNCONNECTED|ABOOK_FLAG_FEED),
		intval(0),
		intval(ABOOK_FLAG_ARCHIVED|ABOOK_FLAG_BLOCKED|ABOOK_FLAG_IGNORED),
		intval(ACCOUNT_OK),
		intval(ACCOUNT_UNVERIFIED)
	);

	if(! $contacts) {
		logger('onepoll: abook_id not found: ' . $contact_id);
		return;
	}

	$contact = $contacts[0];

	$t = $contact['abook_updated'];

	$importer_uid = $contact['abook_channel'];
		
	$r = q("SELECT * from channel left join xchan on channel_hash = xchan_hash where channel_id = %d limit 1",
		intval($importer_uid)
	);

	if(! $r)
		return;

	$importer = $r[0];

	logger("onepoll: poll: ({$contact['id']}) IMPORTER: {$importer['xchan_name']}, CONTACT: {$contact['xchan_name']}");

	$last_update = ((($contact['abook_updated'] === $contact['abook_created']) || ($contact['abook_updated'] === NULL_DATE))
		? datetime_convert('UTC','UTC','now - 7 days')
		: datetime_convert('UTC','UTC',$contact['abook_updated'] . ' - 2 days')
	);

	if($contact['xchan_network'] === 'rss') {
		logger('onepoll: processing feed ' . $contact['xchan_name'], LOGGER_DEBUG);
		handle_feed($importer['channel_id'],$contact_id,$contact['xchan_hash']);
		q("update abook set abook_connected = '%s' where abook_id = %d",
			dbesc(datetime_convert()),
			intval($contact['abook_id'])
		);
		return;
	}
	
	if($contact['xchan_network'] !== 'zot')
		return;

	// update permissions

	$x = zot_refresh($contact,$importer);

	$responded = false;
	$updated   = datetime_convert();
	$connected = datetime_convert();
	if(! $x) {
		// mark for death by not updating abook_connected, this is caught in include/poller.php
		q("update abook set abook_updated = '%s' where abook_id = %d",
			dbesc($updated),
			intval($contact['abook_id'])
		);
	}
	else {
		q("update abook set abook_updated = '%s', abook_connected = '%s' where abook_id = %d",
			dbesc($updated),
			dbesc($connected),
			intval($contact['abook_id'])
		);
		$responded = true;
	}

	if(! $responded)
		return;

	if($contact['xchan_connurl']) {
		$fetch_feed = true;
		$x = null;

		if(! ($contact['abook_their_perms'] & PERMS_R_STREAM ))
			$fetch_feed = false;

		if($fetch_feed) {

			$feedurl = str_replace('/poco/','/zotfeed/',$contact['xchan_connurl']);		
			$feedurl .= '?f=&mindate=' . urlencode($last_update);

			$x = z_fetch_url($feedurl);

			logger('feed_update: ' . print_r($x,true), LOGGER_DATA);

		}

		if(($x) && ($x['success'])) {
			$total = 0;
			logger('onepoll: feed update ' . $contact['xchan_name'] . ' ' . $feedurl);

			$j = json_decode($x['body'],true);
			if($j['success'] && $j['messages']) {
				foreach($j['messages'] as $message) {
					$results = process_delivery(array('hash' => $contact['xchan_hash']), get_item_elements($message),
						array(array('hash' => $importer['xchan_hash'])), false);
					logger('onepoll: feed_update: process_delivery: ' . print_r($results,true), LOGGER_DATA);
					$total ++;
				}
				logger("onepoll: $total messages processed");
			}
		}
	}
			

	// update the poco details for this connection

	if($contact['xchan_connurl']) {	
		$r = q("SELECT xlink_id from xlink 
			where xlink_xchan = '%s' and xlink_updated > %s - INTERVAL %s and xlink_static = 0 limit 1",
			intval($contact['xchan_hash']),
			db_utcnow(), db_quoteinterval('1 DAY')
		);
		if(! $r) {
			poco_load($contact['xchan_hash'],$contact['xchan_connurl']);
		}
	}

	return;
}

if (array_search(__file__,get_included_files())===0){
  onepoll_run($argv,$argc);
  killme();
}
