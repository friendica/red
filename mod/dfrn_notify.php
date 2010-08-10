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

	$r = q("SELECT * FROM `contact` WHERE `issued-id` = '%s' LIMIT 1",
		dbesc($dfrn_id)
	);
	if(! count($r)) {
		xml_status(3);
		return; //NOTREACHED
	}

	// We aren't really interested in anything this person has to say. But be polite and make them 
	// think we're listening intently by acknowledging receipt of their communications - which we quietly ignore.

	if($r[0]['readonly']) {
		xml_status(0);
		return; //NOTREACHED
	}
		
	$importer = $r[0];

	$feed = new SimplePie();
	$feed->set_raw_data($data);
	$feed->enable_order_by_date(false);
	$feed->init();

	$ismail = false;

	$rawmail = $feed->get_feed_tags( NAMESPACE_DFRN, 'mail' );
	if(isset($rawmail[0]['child'][NAMESPACE_DFRN])) {
		$ismail = true;
		$base = $rawmail[0]['child'][NAMESPACE_DFRN];

		$msg = array();
		$msg['uid'] = $importer['uid'];
		$msg['from-name'] = notags(unxmlify($base['sender'][0]['child'][NAMESPACE_DFRN]['name'][0]['data']));
		$msg['from-photo'] = notags(unxmlify($base['sender'][0]['child'][NAMESPACE_DFRN]['avatar'][0]['data']));
		$msg['from-url'] = notags(unxmlify($base['sender'][0]['child'][NAMESPACE_DFRN]['uri'][0]['data']));
		$msg['contact-id'] = $importer['id'];
		$msg['title'] = notags(unxmlify($base['subject'][0]['data']));
		$msg['body'] = escape_tags(unxmlify($base['content'][0]['data']));
		$msg['delivered'] = 1;
		$msg['seen'] = 0;
		$msg['replied'] = 0;
		$msg['uri'] = notags(unxmlify($base['id'][0]['data']));
		$msg['parent-uri'] = notags(unxmlify($base['in-reply-to'][0]['data']));
		$msg['created'] = datetime_convert(notags(unxmlify('UTC','UTC',$base['sentdate'][0]['data'])));

		$r = q("INSERT INTO `mail` (`" . implode("`, `", array_keys($msg)) 
			. "`) VALUES ('" . implode("', '", array_values($msg)) . "')" );

		// send email notification if requested.
		$r = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
			intval($importer['uid'])
		);
		require_once('bbcode.php');
		if((count($r)) && ($r[0]['notify_flags'] & NOTIFY_MAIL)) {
			$tpl = file_get_contents('view/mail_received_eml.tpl');			
			$email_tpl = replace_macros($tpl, array(
				'$sitename' => $a->config['sitename'],
				'$siteurl' =>  $a->get_baseurl(),
				'$username' => $r[0]['username'],
				'$email' => $r[0]['email'],
				'$from' => $msg['from-name'],
				'$fn' => $r[0]['name'],
				'$title' => $msg['title'],
				'$body' => strip_tags(bbcode($msg['body']))
			));
	
			$res = mail($r[0]['email'], t("New mail received at ") . $a->config['sitename'],
				$email_tpl,t("From: Administrator@") . $_SERVER[SERVER_NAME] );
			if(!$res) {
				notice( t("Email notification failed.") . EOL );
			}
		}

		xml_status(0);
		return;
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
					$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s'
						WHERE `parent-uri` = '%s'",
						dbesc($when),
						dbesc($r[0]['uri'])
					);
				}
				else {
					$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s' 
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

		$encrypted_id = '';
		$id_str = $_GET['dfrn_id'] . '.' . mt_rand(1000,9999);

		openssl_private_encrypt($id_str,$encrypted_id,$r[0]['prvkey']);
		$encrypted_id = bin2hex($encrypted_id);

		echo '<?xml version="1.0" encoding="UTF-8"?><dfrn_notify><status>' .$status . '</status><dfrn_id>' . $encrypted_id . '</dfrn_id>'
			. '<challenge>' . $challenge . '</challenge></dfrn_notify>' . "\r\n" ;
		session_write_close();
		exit;
		
	}

}