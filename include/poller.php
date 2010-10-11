<?php


	require_once('boot.php');

	$a = new App;

	@include('.htconfig.php');
	require_once('dba.php');
	$db = new dba($db_host, $db_user, $db_pass, $db_data);
		unset($db_host, $db_user, $db_pass, $db_data);

	require_once('session.php');
	require_once('datetime.php');
	require_once('simplepie/simplepie.inc');
	require_once('include/items.php');

	require_once('include/Contact.php');

	$debugging = get_config('system','debugging');

	$a->set_baseurl(get_config('system','url'));

	$contacts = q("SELECT * FROM `contact` 
		WHERE ( `dfrn-id` != '' OR (`issued-id` != '' AND `duplex` = 1)) 
		AND `self` = 0 AND `blocked` = 0 AND `readonly` = 0 ORDER BY RAND()");

	if(! count($contacts))
		killme();

	foreach($contacts as $contact) {

		if($contact['priority']) {

			$update = false;
			$t = $contact['last-update'];

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
			if(! $update)
				continue;
		}

		$importer_uid = $contact['uid'];

		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 1 LIMIT 1",
			intval($importer_uid)
		);
		if(! count($r))
			continue;

		$importer = $r[0];

		if($debugging)
			echo "IMPORTER: {$importer['name']}";

		$last_update = (($contact['last-update'] === '0000-00-00 00:00:00') 
			? datetime_convert('UTC','UTC','now - 30 days','Y-m-d\TH:i:s\Z')
			: datetime_convert('UTC','UTC',$contact['last-update'],'Y-m-d\TH:i:s\Z'));



		$idtosend = $orig_id = (($contact['dfrn-id']) ? $contact['dfrn-id'] : $contact['issued-id']);

		if(intval($contact['duplex']) && $contact['dfrn-id'])
			$idtosend = '0:' . $orig_id;
		if(intval($contact['duplex']) && $contact['issued-id'])
			$idtosend = '1:' . $orig_id;		

		$url = $contact['poll'] . '?dfrn_id=' . $idtosend . '&type=data&last_update=' . $last_update ;
		$xml = fetch_url($url);

		if($debugging) {
			echo "URL: " . $url . "\r\n";
			echo "XML: " . $xml . "\r\n";
		}

		if(! $xml) {
			// dead connection - might be a transient event, or this might
			// mean the software was uninstalled or the domain expired. 
			// Will keep trying for one month.
			mark_for_death($contact);
			continue;
		}


		$res = simplexml_load_string($xml);

		if(intval($res->status) == 1) {
			// we may not be friends anymore. Will keep trying for one month.
			mark_for_death($contact);
		}
		else {
			if($contact['term-date'] != '0000-00-00 00:00:00')
				unmark_for_death($contact);
		}

		if((intval($res->status) != 0) || (! strlen($res->challenge)) || (! strlen($res->dfrn_id)))
			continue;

		$postvars = array();

		$sent_dfrn_id = hex2bin($res->dfrn_id);
		$challenge    = hex2bin($res->challenge);

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


		$xml = post_url($contact['poll'],$postvars);

		if($debugging) {
			echo "XML response:" . $xml . "\r\n";
			echo "Length:" . strlen($xml) . "\r\n";
		}

		if(! strlen($xml))
			continue;


		consume_feed($xml,$importer,$contact,$hub);
		

		if((strlen($hub)) && ($contact['rel'] == REL_BUD) && ($contact['priority'] == 0)) {
			subscribe_to_hub($hub,$importer,$contact);
		}


		$r = q("UPDATE `contact` SET `last-update` = '%s' WHERE `id` = %d LIMIT 1",
			dbesc(datetime_convert()),
			intval($contact['id'])
		);

	}
		
	killme();



