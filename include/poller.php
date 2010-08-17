<?php


require_once('boot.php');

$a = new App;

@include('.htconfig.php');
require_once('dba.php');
$db = new dba($db_host, $db_user, $db_pass, $db_data, $install);
	unset($db_host, $db_user, $db_pass, $db_data);

require_once('session.php');
require_once('datetime.php');
require_once('simplepie/simplepie.inc');
require_once('include/items.php');



	$a->set_baseurl(get_config('system','url'));

	$contacts = q("SELECT * FROM `contact` 
		WHERE `dfrn-id` != '' AND `self` = 0 AND `blocked` = 0 
		AND `readonly` = 0 ORDER BY RAND()");

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

		$last_update = (($contact['last-update'] == '0000-00-00 00:00:00') 
			? datetime_convert('UTC','UTC','now - 30 days','Y-m-d\TH:i:s\Z')
			: datetime_convert('UTC','UTC',$contact['last-update'],'Y-m-d\TH:i:s\Z'));

		$url = $contact['poll'] . '?dfrn_id=' . $contact['dfrn-id'] . '&type=data&last_update=' . $last_update ;

		$xml = fetch_url($url);
echo "URL: " . $url;
echo "XML: " . $xml;
		if(! $xml)
			continue;

		$res = simplexml_load_string($xml);

		if((intval($res->status) != 0) || (! strlen($res->challenge)) || (! strlen($res->dfrn_id)))
			continue;

		$postvars = array();

		$sent_dfrn_id = hex2bin($res->dfrn_id);

		$final_dfrn_id = '';
		openssl_public_decrypt($sent_dfrn_id,$final_dfrn_id,$contact['pubkey']);
		$final_dfrn_id = substr($final_dfrn_id, 0, strpos($final_dfrn_id, '.'));
		if($final_dfrn_id != $contact['dfrn-id']) {
			// did not decode properly - cannot trust this site 
			continue;
		}

		$postvars['dfrn_id'] = $contact['dfrn-id'];
		$challenge = hex2bin($res->challenge);

		openssl_public_decrypt($challenge,$postvars['challenge'],$contact['pubkey']);

		$xml = post_url($contact['poll'],$postvars);

echo "XML response:" . $xml . "\r\n";
echo "Length:" . strlen($xml) . "\r\n";

		if(! strlen($xml)) {
			// an empty response may mean there's nothing new - record the fact that we checked
			$r = q("UPDATE `contact` SET `last-update` = '%s' WHERE `id` = %d LIMIT 1",
				dbesc(datetime_convert()),
				intval($contact['id'])
			);
			continue;
		}

		$feed = new SimplePie();
		$feed->set_raw_data($xml);
		$feed->enable_order_by_date(false);
		$feed->init();

		$photo_rawupdate = $feed->get_feed_tags(NAMESPACE_DFRN,'icon-updated');
		if($photo_rawupdate) {
			$photo_timestamp = datetime_convert('UTC','UTC',$photo_rawupdate[0]['data']);
			$photo_url = $feed->get_image_url();
			if(strlen($photo_url) && $photo_timestamp > $contact['avatar-date']) {

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
		}

		
		foreach($feed->get_items() as $item) {

			$deleted = false;

			$rawdelete = $item->get_item_tags("http://purl.org/atompub/tombstones/1.0", 'deleted-entry');
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
					if($r[0]['uri'] == $r[0]['parent-uri']) {
						$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s',
							`body` = '', `title` = ''
							WHERE `parent-uri` = '%s'",
							dbesc($when),
							dbesc($r[0]['uri'])
						);
					}
					else {
						$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s',
							`body` = '', `title` = '' 
							WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
							dbesc($when),
							dbesc($uri),
							intval($importer['uid'])
						);
					}
				}	
				continue;
			}


			$is_reply = false;		
			$item_id = $item->get_id();
			$rawthread = $item->get_item_tags("http://purl.org/syndication/thread/1.0",'in-reply-to');
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
					$allow = $item->get_item_tags('http://purl.org/macgirvin/dfrn/1.0','comment-allow');
					if($allow && $allow[0]['data'] != $r[0]['last-child']) {
						$r = q("UPDATE `item` SET `last-child` = %d WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
							intval($allow[0]['data']),
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
				$r = post_remote($a,$datarray);
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
					$allow = $item->get_item_tags('http://purl.org/macgirvin/dfrn/1.0','comment-allow');
					if($allow && $allow[0]['data'] != $r[0]['last-child']) {
						$r = q("UPDATE `item` SET `last-child` = %d WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
							intval($allow[0]['data']),
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
				$r = post_remote($a,$datarray);
				continue;

			}

		}

		$r = q("UPDATE `contact` SET `last-update` = '%s' WHERE `id` = %d LIMIT 1",
			dbesc(datetime_convert()),
			intval($contact['id'])
		);

	}
		
	killme();



