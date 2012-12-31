<?php

require_once('boot.php');
require_once('include/cli_startup.php');


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



// FIXME
return;



	$contacts = q("SELECT abook.*, account.*
		FROM abook LEFT JOIN account on abook_account = account_id 
		AND abook_id = %d
		AND not ( abook_flags & %d ) AND not ( abook_flags & %d ) 
		AND not ( abook_flags & %d ) AND not ( abook_flags & %d ) 
		AND not ( abook_flags & %d ) AND ( account_flags & %d ) $abandon_sql ORDER BY RAND()",
		intval($contact_id),
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

	if(! $contacts)
		return;

	$contact = $contacts[0];
	$t = $contact['abook_updated'];


// end of last edits



	$importer_uid = $contact['uid'];
		
	$r = q("SELECT `contact`.*, `user`.`page-flags` FROM `contact` LEFT JOIN `user` on `contact`.`uid` = `user`.`uid` WHERE `user`.`uid` = %d AND `contact`.`self` = 1 LIMIT 1",
		intval($importer_uid)
	);
	if(! count($r))
		return;

	$importer = $r[0];

	logger("onepoll: poll: ({$contact['id']}) IMPORTER: {$importer['name']}, CONTACT: {$contact['name']}");

	$last_update = (($contact['last_update'] === '0000-00-00 00:00:00') 
		? datetime_convert('UTC','UTC','now - 7 days', ATOM_TIME)
		: datetime_convert('UTC','UTC',$contact['last_update'], ATOM_TIME)
	);

	if($contact['network'] === NETWORK_DFRN) {

		$idtosend = $orig_id = (($contact['dfrn_id']) ? $contact['dfrn_id'] : $contact['issued_id']);
		if(intval($contact['duplex']) && $contact['dfrn_id'])
			$idtosend = '0:' . $orig_id;
		if(intval($contact['duplex']) && $contact['issued_id'])
			$idtosend = '1:' . $orig_id;

		// they have permission to write to us. We already filtered this in the contact query.
		$perm = 'rw';

		$url = $contact['poll'] . '?dfrn_id=' . $idtosend 
			. '&dfrn_version=' . DFRN_PROTOCOL_VERSION 
			. '&type=data&last_update=' . $last_update 
			. '&perm=' . $perm ;

		$handshake_xml = fetch_url($url);
		$html_code = $a->get_curl_code();

		logger('onepoll: handshake with url ' . $url . ' returns xml: ' . $handshake_xml, LOGGER_DATA);


		if((! strlen($handshake_xml)) || ($html_code >= 400) || (! $html_code)) {
			logger("poller: $url appears to be dead - marking for death ");

			// dead connection - might be a transient event, or this might
			// mean the software was uninstalled or the domain expired. 
			// Will keep trying for one month.

			mark_for_death($contact);

			// set the last_update so we don't keep polling
			$r = q("UPDATE `contact` SET `last_update` = '%s' WHERE `id` = %d LIMIT 1",
				dbesc(datetime_convert()),
				intval($contact['id'])
			);

			return;
		}

		if(! strstr($handshake_xml,'<?xml')) {
			logger('poller: response from ' . $url . ' did not contain XML.');

			mark_for_death($contact);

			$r = q("UPDATE `contact` SET `last_update` = '%s' WHERE `id` = %d LIMIT 1",
				dbesc(datetime_convert()),
				intval($contact['id'])
			);
			return;
		}


		$res = parse_xml_string($handshake_xml);
	
		if(intval($res->status) == 1) {
			logger("poller: $url replied status 1 - marking for death ");

			// we may not be friends anymore. Will keep trying for one month.
			// set the last_update so we don't keep polling


			$r = q("UPDATE `contact` SET `last_update` = '%s' WHERE `id` = %d LIMIT 1",
				dbesc(datetime_convert()),
				intval($contact['id'])
			);
			mark_for_death($contact);
		}
		else {
			if($contact['term_date'] != '0000-00-00 00:00:00') {
				logger("poller: $url back from the dead - removing mark for death");
				unmark_for_death($contact);
			}
		}

		if((intval($res->status) != 0) || (! strlen($res->challenge)) || (! strlen($res->dfrn_id)))
			return;

		if(((float) $res->dfrn_version > 2.21) && ($contact['poco'] == '')) {
			q("update contact set poco = '%s' where id = %d limit 1",
				dbesc(str_replace('/channel/','/poco/', $contact['url'])),
				intval($contact['id'])
			);
		}

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
			logger('poller: ID did not decode: ' . $contact['id'] . ' orig: ' . $orig_id . ' final: ' . $final_dfrn_id);	
			// did not decode properly - cannot trust this site 
			return;
		}

		$postvars['dfrn_id'] = $idtosend;
		$postvars['dfrn_version'] = DFRN_PROTOCOL_VERSION;
		$postvars['perm'] = 'rw';

		$xml = post_url($contact['poll'],$postvars);

	}

	if($xml) {
		logger('poller: received xml : ' . $xml, LOGGER_DATA);
		if((! strstr($xml,'<?xml')) && (! strstr($xml,'<rss'))) {
			logger('poller: post_handshake: response from ' . $url . ' did not contain XML.');
			$r = q("UPDATE `contact` SET `last_update` = '%s' WHERE `id` = %d LIMIT 1",
				dbesc(datetime_convert()),
				intval($contact['id'])
			);
			return;
		}


		consume_feed($xml,$importer,$contact,$hub,1,1);


		// do it twice. Ensures that children of parents which may be later in the stream aren't tossed
	
		consume_feed($xml,$importer,$contact,$hub,1,2);


	}

	$updated = datetime_convert();

	$r = q("UPDATE `contact` SET `last_update` = '%s', `success_update` = '%s' WHERE `id` = %d LIMIT 1",
		dbesc($updated),
		dbesc($updated),
		intval($contact['id'])
	);


	// load current friends if possible.

	if($contact['poco']) {	
		$r = q("SELECT count(*) as total from glink 
			where `cid` = %d and updated > UTC_TIMESTAMP() - INTERVAL 1 DAY",
			intval($contact['id'])
		);
	}
	if(count($r)) {
		if(! $r[0]['total']) {
			poco_load($contact['id'],$importer_uid,0,$contact['poco']);
		}
	}

	return;
}

if (array_search(__file__,get_included_files())===0){
  onepoll_run($argv,$argc);
  killme();
}
