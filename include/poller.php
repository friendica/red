<?php
require_once("boot.php");

function poller_run($argv, $argc){
  global $a, $db;

  if(is_null($a)){
    $a = new App;
  }
  
  if(is_null($db)){
    @include(".htconfig.php");
    require_once("dba.php");
    $db = new dba($db_host, $db_user, $db_pass, $db_data);
    unset($db_host, $db_user, $db_pass, $db_data);
  };

	require_once('session.php');
	require_once('datetime.php');
	require_once('simplepie/simplepie.inc');
	require_once('include/items.php');
	require_once('include/Contact.php');

	$a->set_baseurl(get_config('system','url'));

	logger('poller: start');
	
	// run queue delivery process in the background

	$php_path = ((x($a->config,'php_path') && strlen($a->config['php_path'])) ? $a->config['php_path'] : 'php');
	//proc_close(proc_open("\"$php_path\" \"include/queue.php\" &", array(), $foo));
	proc_run($php_path,"include/queue.php");


	$hub_update = false;
	$force = false;

	if(($argc > 1) && ($argv[1] == 'force'))
		$force = true;

	if(($argc > 1) && intval($argv[1])) {
		$manual_id = intval($argv[1]);
		$force = true;
	}

	$sql_extra = (($manual_id) ? " AND `id` = $manual_id " : "");

	// 'stat' clause is a temporary measure until we have federation subscriptions working both directions
	$contacts = q("SELECT * FROM `contact` 
		WHERE ( ( `network` = 'dfrn' AND ( `dfrn-id` != '' OR (`issued-id` != '' AND `duplex` = 1)))
		OR ( `network` IN ( 'stat', 'feed' ) AND `poll` != '' ))
		$sql_extra 
		AND `self` = 0 AND `blocked` = 0 AND `readonly` = 0 ORDER BY RAND()");

	if(! count($contacts)){
		return;
	}

	foreach($contacts as $contact) {

		if($contact['priority'] || $contact['subhub']) {

			$hub_update = true;
			$update     = false;

			$t = $contact['last-update'];

			// We should be getting everything via a hub. But just to be sure, let's check once a day.
			// (You can make this more or less frequent if desired by setting 'pushpoll_frequency' appropriately)
			// This also lets us update our subscription to the hub, and add or replace hubs in case it
			// changed. We will only update hubs once a day, regardless of 'pushpoll_frequency'. 


			if($contact['subhub']) {
				$interval = get_config('system','pushpoll_frequency');
				$contact['priority'] = (($interval !== false) ? intval($interval) : 3);
				$hub_update = false;

				if(datetime_convert('UTC','UTC', 'now') > datetime_convert('UTC','UTC', $t . " + 1 day"))
						$hub_update = true;
			}


			/**
			 * Based on $contact['priority'], should we poll this site now? Or later?
			 */			

			switch ($contact['priority']) {
				case 5:
					if(datetime_convert('UTC','UTC', 'now') > datetime_convert('UTC','UTC', $t . " + 1 month"))
						$update = true;
					break;					
				case 4:
					if(datetime_convert('UTC','UTC', 'now') > datetime_convert('UTC','UTC', $t . " + 1 week"))
						$update = true;
					break;
				case 3:
					if(datetime_convert('UTC','UTC', 'now') > datetime_convert('UTC','UTC', $t . " + 1 day"))
						$update = true;
					break;
				case 2:
					if(datetime_convert('UTC','UTC', 'now') > datetime_convert('UTC','UTC', $t . " + 12 hour"))
						$update = true;
					break;
				case 1:
				default:
					if(datetime_convert('UTC','UTC', 'now') > datetime_convert('UTC','UTC', $t . " + 1 hour"))
						$update = true;
					break;
			}
			if((! $update) && (! $force))
				continue;
		}

		$importer_uid = $contact['uid'];

		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 1 LIMIT 1",
			intval($importer_uid)
		);
		if(! count($r))
			continue;

		$importer = $r[0];

		logger("poller: poll: IMPORTER: {$importer['name']}, CONTACT: {$contact['name']}");

		$last_update = (($contact['last-update'] === '0000-00-00 00:00:00') 
			? datetime_convert('UTC','UTC','now - 30 days', ATOM_TIME)
			: datetime_convert('UTC','UTC',$contact['last-update'], ATOM_TIME)
		);

		if($contact['network'] === 'dfrn') {

			$idtosend = $orig_id = (($contact['dfrn-id']) ? $contact['dfrn-id'] : $contact['issued-id']);

			if(intval($contact['duplex']) && $contact['dfrn-id'])
				$idtosend = '0:' . $orig_id;
			if(intval($contact['duplex']) && $contact['issued-id'])
				$idtosend = '1:' . $orig_id;		

			$url = $contact['poll'] . '?dfrn_id=' . $idtosend 
				. '&dfrn_version=' . DFRN_PROTOCOL_VERSION 
				. '&type=data&last_update=' . $last_update ;
	
			$xml = fetch_url($url);

			logger('poller: handshake with url ' . $url . ' returns xml: ' . $xml, LOGGER_DATA);


			if(! $xml) {
				logger("poller: $url appears to be dead - marking for death ");
				// dead connection - might be a transient event, or this might
				// mean the software was uninstalled or the domain expired. 
				// Will keep trying for one month.
				mark_for_death($contact);

				// set the last-update so we don't keep polling

				$r = q("UPDATE `contact` SET `last-update` = '%s' WHERE `id` = %d LIMIT 1",
					dbesc(datetime_convert()),
					intval($contact['id'])
				);

				continue;
			}

			if(! strstr($xml,'<?xml')) {
				logger('poller: response from ' . $url . ' did not contain XML.');
				$r = q("UPDATE `contact` SET `last-update` = '%s' WHERE `id` = %d LIMIT 1",
					dbesc(datetime_convert()),
					intval($contact['id'])
				);
				continue;
			}


			$res = simplexml_load_string($xml);

			if(intval($res->status) == 1) {
				logger("poller: $url replied status 1 - marking for death ");

				// we may not be friends anymore. Will keep trying for one month.
				// set the last-update so we don't keep polling

				$r = q("UPDATE `contact` SET `last-update` = '%s' WHERE `id` = %d LIMIT 1",
					dbesc(datetime_convert()),
					intval($contact['id'])
				);

				mark_for_death($contact);
			}
			else {
				if($contact['term-date'] != '0000-00-00 00:00:00') {
					logger("poller: $url back from the dead - removing mark for death");
					unmark_for_death($contact);
				}
			}

			if((intval($res->status) != 0) || (! strlen($res->challenge)) || (! strlen($res->dfrn_id)))
				continue;

			$postvars = array();

			$sent_dfrn_id = hex2bin((string) $res->dfrn_id);
			$challenge    = hex2bin((string) $res->challenge);

			$final_dfrn_id = '';

			if(($contact['duplex']) && strlen($contact['prvkey'])) {
				openssl_private_decrypt($sent_dfrn_id,$final_dfrn_id,$contact['prvkey']);
				openssl_private_decrypt($challenge,$postvars['challenge'],$contact['prvkey']);
			}
			else {
				openssl_public_decrypt($sent_dfrn_id,$final_dfrn_id,$contact['pubkey']);
				openssl_public_decrypt($challenge,$postvars['challenge'],$contact['pubkey']);
			}

			$final_dfrn_id = substr($final_dfrn_id, 0, strpos($final_dfrn_id, '.'));

			if(strpos($final_dfrn_id,':') == 1)
				$final_dfrn_id = substr($final_dfrn_id,2);

			if($final_dfrn_id != $orig_id) {

				// did not decode properly - cannot trust this site 
				continue;
			}

			$postvars['dfrn_id'] = $idtosend;
			$postvars['dfrn_version'] = DFRN_PROTOCOL_VERSION;

			$xml = post_url($contact['poll'],$postvars);
		}
		else {
			// $contact['network'] !== 'dfrn'

			$xml = fetch_url($contact['poll']);
		}

		logger('poller: received xml : ' . $xml, LOGGER_DATA);

		if(! strlen($xml))
			continue;

		consume_feed($xml,$importer,$contact,$hub,1);

		// do it twice. Ensures that children of parents which may be later in the stream aren't tossed

		consume_feed($xml,$importer,$contact,$hub,1);


		if((strlen($hub)) && ($hub_update) 
			&& (($contact['rel'] == REL_BUD) || (($contact['network'] === 'stat') && (! $contact['readonly'])))) {
			logger('poller: subscribing to hub(s) : ' . $hub . ' contact name : ' . $contact['name'] . ' local user : ' . $importer['name']);
			$hubs = explode(',', $hub);
			if(count($hubs)) {
				foreach($hubs as $h) {
					$h = trim($h);
					if(! strlen($h))
						continue;
					subscribe_to_hub($h,$importer,$contact);
				}
			}
		}


		$r = q("UPDATE `contact` SET `last-update` = '%s' WHERE `id` = %d LIMIT 1",
			dbesc(datetime_convert()),
			intval($contact['id'])
		);

		// loop - next contact
	}  
		
	return;
}

if (array_search(__file__,get_included_files())===0){
  poller_run($argv,$argc);
  killme();
}
