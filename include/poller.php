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
					if(datetime_convert('UTC','UTC', 'now') > datetime_convert('UTC','UTC', t . " + 1 month"))
						$update = true;
					break;					
				case 4:
					if(datetime_convert('UTC','UTC', 'now') > datetime_convert('UTC','UTC', t . " + 1 week"))
						$update = true;
					break;
				case 3:
					if(datetime_convert('UTC','UTC', 'now') > datetime_convert('UTC','UTC', t . " + 1 day"))
						$update = true;
					break;
				case 2:
					if(datetime_convert('UTC','UTC', 'now') > datetime_convert('UTC','UTC', t . " + 12 hour"))
						$update = true;
					break;
				case 1:
				default:
					if(datetime_convert('UTC','UTC', 'now') > datetime_convert('UTC','UTC', t . " + 1 hour"))
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

		$feed = new SimplePie();
		$feed->set_raw_data($xml);
		$feed->enable_order_by_date(false);
		$feed->init();

		// Check at the feed level for updated contact name and/or photo

		$name_updated  = '';
		$new_name = '';
		$photo_timestamp = '';
		$photo_url = '';

		$rawtags = $feed->get_feed_tags( SIMPLEPIE_NAMESPACE_ATOM_10, author);
		if($rawtags) {
			$elems = $rawtags[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10];
			if($elems['name'][0]['attribs'][NAMESPACE_DFRN]['updated']) {
				$name_updated = $elems['name'][0]['attribs'][NAMESPACE_DFRN]['updated'];
				$new_name = $elems['name'][0]['data'];
			} 
			if(($elems['link'][0]['attribs']['']['rel'] === 'photo') && ($elems['link'][0]['attribs'][NAMESPACE_DFRN]['updated'])) {
				$photo_timestamp = datetime_convert('UTC','UTC',$elems['link'][0]['attribs'][NAMESPACE_DFRN]['updated']);
				$photo_url = $elems['link'][0]['attribs']['']['href'];
			}
		}
		if(! $photo_timestamp) {
			$photo_rawupdate = $feed->get_feed_tags(NAMESPACE_DFRN,'icon-updated');
			if($photo_rawupdate) {
				$photo_timestamp = datetime_convert('UTC','UTC',$photo_rawupdate[0]['data']);
				$photo_url = $feed->get_image_url();
			}
		}
		if(($photo_timestamp) && (strlen($photo_url)) && ($photo_timestamp > $contact['avatar-date'])) {

			require_once("Photo.php");
			$photo_failure = false;

			$r = q("SELECT `resource-id` FROM `photo` WHERE `contact-id` = %d AND `uid` = %d LIMIT 1",
				intval($contact['id']),
				intval($contact['uid'])
			);
			if(count($r)) {
				$resource_id = $r[0]['resource-id'];
				$img_str = fetch_url($photo_url,true);
				$img = new Photo($img_str);
				if($img) {
					q("DELETE FROM `photo` WHERE `resource-id` = '%s' AND contact-id` = %d AND `uid` = %d",
						dbesc($resource_id),
						intval($contact['id']),
						intval($contact['uid'])
					);

					$img->scaleImageSquare(175);
				
					$hash = $resource_id;
					$r = $img->store($contact['uid'], $contact['id'], $hash, basename($photo_url), t('Contact Photos') , 4);
					
					$img->scaleImage(80);
					$r = $img->store($contact['uid'], $contact['id'], $hash, basename($photo_url), t('Contact Photos') , 5);
					if($r)
						q("UPDATE `contact` SET `avatar-date` = '%s' WHERE `uid` = %d AND `id` = %d LIMIT 1",
							dbesc(datetime_convert()),
							intval($contact['uid']),
							intval($contact['id'])
						);
				}
			}
		}

		if(($name_updated) && (strlen($new_name)) && ($name_updated > $contact['name-date'])) {
			q("UPDATE `contact` SET `name` = '%s', `name-date` = '%s' WHERE `uid` = %d AND `id` = %d LIMIT 1",
				dbesc(notags(trim($new_name))),
				dbesc(datetime_convert()),
				intval($contact['uid']),
				intval($contact['id'])
			);
		}

		// Now process the feed
		if($feed->get_item_quantity()) {		
			foreach($feed->get_items() as $item) {

				$deleted = false;

				$rawdelete = $item->get_item_tags( NAMESPACE_TOMB, 'deleted-entry');
				if(isset($rawdelete[0]['attribs']['']['ref'])) {
					$uri = $rawthread[0]['attribs']['']['ref'];
					$deleted = true;
					if(isset($rawdelete[0]['attribs']['']['when'])) {
						$when = $rawthread[0]['attribs']['']['when'];
						$when = datetime_convert('UTC','UTC', $when, 'Y-m-d H:i:s');
					}
					else
						$when = datetime_convert('UTC','UTC','now','Y-m-d H:i:s');
				}
				if($deleted) {
					$r = q("SELECT * FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
						dbesc($uri),
						intval($importer['uid'])
					);
					if(count($r)) {
						$item = $r[0];
						if($item['uri'] == $item['parent-uri']) {
							$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s',
								`body` = '', `title` = ''
								WHERE `parent-uri` = '%s' AND `uid` = %d",
								dbesc($when),
								dbesc(datetime_convert()),
								dbesc($item['uri']),
								intval($importer['uid'])
							);
						}
						else {
							$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s',
								`body` = '', `title` = '' 
								WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
								dbesc($when),
								dbesc(datetime_convert()),
								dbesc($uri),
								intval($importer['uid'])
							);
							if($item['last-child']) {
								// ensure that last-child is set in case the comment that had it just got wiped.
								$q("UPDATE `item` SET `last-child` = 0, `changed` = '%s' WHERE `parent-uri` = '%s' AND `uid` = %d ",
									dbesc(datetime_convert()),
									dbesc($item['parent-uri']),
									intval($item['uid'])
								);
								// who is the last child now? 
								$r = q("SELECT `id` FROM `item` WHERE `parent-uri` = '%s' AND `type` != 'activity' AND `deleted` = 0 AND `uid` = %d 
									ORDER BY `edited` DESC LIMIT 1",
										dbesc($item['parent-uri']),
										intval($importer['uid'])
								);
								if(count($r)) {
									q("UPDATE `item` SET `last-child` = 1 WHERE `id` = %d LIMIT 1",
										intval($r[0]['id'])
									);
								}
							}	
						}
					}	
					continue;
				}


				$is_reply = false;		
				$item_id = $item->get_id();
				$rawthread = $item->get_item_tags( NAMESPACE_THREAD,'in-reply-to');
				if(isset($rawthread[0]['attribs']['']['ref'])) {
					$is_reply = true;
					$parent_uri = $rawthread[0]['attribs']['']['ref'];
				}


				if($is_reply) {
	
					// Have we seen it? If not, import it.
	
					$item_id = $item->get_id();
	
					$r = q("SELECT `uid`, `last-child`, `edited` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
						dbesc($item_id),
						intval($importer['uid'])
					);
					// FIXME update content if 'updated' changes
					if(count($r)) {
						$allow = $item->get_item_tags( NAMESPACE_DFRN, 'comment-allow');
						if($allow && $allow[0]['data'] != $r[0]['last-child']) {
							$r = q("UPDATE `item` SET `last-child` = 0, `changed` = '%s' WHERE `parent-uri` = '%s' AND `uid` = %d",
								dbesc(datetime_convert()),
								dbesc($parent_uri),
								intval($importer['uid'])
							);
							$r = q("UPDATE `item` SET `last-child` = %d , `changed` = '%s'  WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
								intval($allow[0]['data']),
								dbesc(datetime_convert()),
								dbesc($item_id),
								intval($importer['uid'])
							);


						}
						continue;
					}
					$datarray = get_atom_elements($item);
					$datarray['parent-uri'] = $parent_uri;
					$datarray['uid'] = $importer['uid'];
					$datarray['contact-id'] = $contact['id'];
					if(($datarray['verb'] == ACTIVITY_LIKE) || ($datarray['verb'] == ACTIVITY_DISLIKE)) {
						$datarray['type'] = 'activity';
						$datarray['gravity'] = GRAVITY_LIKE;
					}
	
					$r = item_store($datarray);
					continue;
				}

				else {
					// Head post of a conversation. Have we seen it? If not, import it.
	
					$item_id = $item->get_id();
					$r = q("SELECT `uid`, `last-child`, `edited` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
						dbesc($item_id),
						intval($importer['uid'])
					);
					if(count($r)) {
						$allow = $item->get_item_tags( NAMESPACE_DFRN, 'comment-allow');
						if($allow && $allow[0]['data'] != $r[0]['last-child']) {
							$r = q("UPDATE `item` SET `last-child` = %d , `changed` = '%s' WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
								intval($allow[0]['data']),
								dbesc(datetime_convert()),
								dbesc($item_id),
								intval($importer['uid'])
							);
						}
						continue;
					}

					$datarray = get_atom_elements($item);
					$datarray['parent-uri'] = $item_id;
					$datarray['uid'] = $importer['uid'];
					$datarray['contact-id'] = $contact['id'];
					$r = item_store($datarray);
					continue;
	
				}
			}
		}
		$r = q("UPDATE `contact` SET `last-update` = '%s' WHERE `id` = %d LIMIT 1",
			dbesc(datetime_convert()),
			intval($contact['id'])
		);

	}
		
	killme();



