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
	
	$manual_id  = 0;
	$generation = 0;

	$force      = false;
	$restart    = false;

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
		AND (( abook_flags = %d ) OR ( abook_flags = %d )) 
		AND (( account_flags = %d ) OR ( account_flags = %d )) limit 1",
		intval($contact_id),
		intval(ABOOK_FLAG_HIDDEN),
		intval(0),
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

	$last_update = (($contact['abook_updated'] === '0000-00-00 00:00:00') 
		? datetime_convert('UTC','UTC','now - 7 days')
		: datetime_convert('UTC','UTC',$contact['abook_updated'])
	);

	// update permissions

	$x = zot_refresh($contact,$importer);

	$responded = false;
	$updated = datetime_convert();
	if(! $x) {
		// mark for death by not updating abook_connected, this is caught in include/poller.php
		q("update abook set abook_updated = '%s' where abook_id = %d limit 1",
			dbesc($updated),
			intval($contact['abook_id'])
		);
	}
	else {
		q("update abook set abook_updated = '%s', abook_connected = '%s' where abook_id = %d limit 1",
			dbesc($updated),
			dbesc($updated),
			intval($contact['abook_id'])
		);
		$responded = true;
	}

	if(! $responded)
		return;

	if($contact['xchan_connurl']) {
		$feedurl = str_replace('/poco/','/zotfeed/',$channel['xchan_connurl']);
		
		$x = z_fetch_url($feedurl . '?f=$mindate=' . $last_update);
		if($x['success']) {
			$total = 0;
			$j = json_decode($x['body'],true);
			if($j['success'] && $j['messages']) {
				foreach($j['messages'] as $message) {
					$results = process_delivery(array('hash' => $contact['xchan_hash']),$message,
						array(array('hash' => $importer['xchan_hash'])), false);
					$total ++;
				}
				logger("onepoll: $total messages processed");
			}
		}
	}
			

	// fetch some items
	// set last updated timestamp


	if($contact['xchan_connurl']) {	
		$r = q("SELECT xlink_id from xlink 
			where xlink_xchan = '%s' and xlink_updated > UTC_TIMESTAMP() - INTERVAL 1 DAY limit 1",
			intval($contact['xchan_hash'])
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
