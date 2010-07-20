<?php

require_once('simplepie/simplepie.inc');
require_once('include/items.php');


function dfrn_notify_post(&$a) {

	$dfrn_id = notags(trim($_POST['dfrn_id']));
	$challenge = notags(trim($_POST['challenge']));
	$data = $_POST['data'];
	$r = q("SELECT * FROM `challenge` WHERE `dfrn-id` = '%s' AND `challenge` = '%s' LIMIT 1",
		dbesc($dfrn_id),
		dbesc($challenge)
	);
	if(! count($r))
		xml_status(3);

	$r = q("DELETE FROM `challenge` WHERE `dfrn-id` = '%s' AND `challenge` = '%s' LIMIT 1",
		dbesc($dfrn_id),
		dbesc($challenge)
	);

	// find the local user who owns this relationship.

	$r = q("SELECT `id`, `uid` FROM `contact` WHERE `issued-id` = '%s' LIMIT 1",
		dbesc($dfrn_id)
	);
	if(! count($r))
		xml_status(3);


	$importer = $r[0];

	$feed = new SimplePie();
	$feed->set_raw_data($data);
	$feed->enable_order_by_date(false);
	$feed->init();

	foreach($feed->get_items() as $item) {

		$rawdelete = $item->get_item_tags("http://purl.org/atompub/tombstones/1.0", 'deleted-entry');
		print_r($rawdelete);
		if($deleted) {
			// pick out ref and when from attribs
			// check hasn't happened already, verify ownership and then process it


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
			if($feed->get_item_quantity() == 1) {
				// remote reply to our post. Import and then notify everybody else.
				$datarray = get_atom_elements($item);
				$urn = explode(':',$parent_urn);
				$datarray['type'] = 'remote-comment';
				$datarray['parent-uri'] = $parent_uri;
				$datarray['uid'] = $importer['uid'];
				$datarray['contact-id'] = $importer['id'];
				$posted_id = post_remote($a,$datarray);

				$r = q("SELECT `parent` FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
					intval($posted_id),
					intval($importer['uid'])
				);
				if(count($r)) {
					$r1 = q("UPDATE `item` SET `last-child` = 0 WHERE `uid` = %d AND `parent` = %d",
						intval($importer['uid']),
						intval($r[0]['parent'])
					);
				}
				$r2 = q("UPDATE `item` SET `last-child` = 1 WHERE `uid` = %d AND `id` = %d LIMIT 1",
						intval($importer['uid']),
						intval($posted_id)
				);

				$url = $a->get_baseurl();

				proc_close(proc_open("php include/notifier.php $url comment-import $posted_id > remote-notify.log &", array(),$foo));

				xml_status(0);
				return;

			}
			else {
				// regular comment that is part of this total conversation. Have we seen it? If not, import it.

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
				$datarray['contact-id'] = $importer['id'];
				$r = post_remote($a,$datarray);
				continue;
			}
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
			$datarray['contact-id'] = $importer['id'];
			$r = post_remote($a,$datarray);
			continue;

		}
	
	}

	xml_status(0);
	killme();

}


function dfrn_notify_content(&$a) {

	if(x($_GET,'dfrn_id')) {
		// initial communication from external contact
		$hash = random_string();

		$status = 0;

		$r = q("DELETE FROM `challenge` WHERE `expire` < " . intval(time()));

		$r = q("INSERT INTO `challenge` ( `challenge`, `dfrn-id`, `expire` )
			VALUES( '%s', '%s', '%s') ",
			dbesc($hash),
			dbesc(notags(trim($_GET['dfrn_id']))),
			intval(time() + 60 )
		);

		$r = q("SELECT * FROM `contact` WHERE `issued-id` = '%s' AND `blocked` = 0 AND `pending` = 0 LIMIT 1",
			dbesc($_GET['dfrn_id']));
		if((! count($r)) || (! strlen($r[0]['prvkey'])))
			$status = 1;

		$challenge = '';

		openssl_private_encrypt($hash,$challenge,$r[0]['prvkey']);
		$challenge = bin2hex($challenge);
		echo '<?xml version="1.0" encoding="UTF-8"?><dfrn_notify><status>' .$status . '</status><dfrn_id>' . $_GET['dfrn_id'] . '</dfrn_id>'
			. '<challenge>' . $challenge . '</challenge></dfrn_notify>' . "\r\n" ;
		session_write_close();
		exit;
		
	}

}