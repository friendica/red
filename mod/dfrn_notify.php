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

	$r = q("SELECT `contact`.*, `contact`.`uid` AS `importer_uid`, `user`.* FROM `contact` 
		LEFT JOIN `user` ON `contact`.`uid` = `user`.`uid` 
		WHERE ( `issued-id` = '%s' OR ( `duplex` = 1 AND `dfrn-id` = '%s' )) LIMIT 1",
		dbesc($dfrn_id),
		dbesc($dfrn_id)
	);

	if(! count($r)) {
		xml_status(3);
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

		if($importer['readonly']) {
			// We aren't receiving email from this person. But we will quietly ignore them
			// rather than a blatant "go away" message.
			xml_status(0);
			return; //NOTREACHED
		}

		$ismail = true;
		$base = $rawmail[0]['child'][NAMESPACE_DFRN];

		$msg = array();
		$msg['uid'] = $importer['importer_uid'];
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
		
		dbesc_array($msg);

		$r = q("INSERT INTO `mail` (`" . implode("`, `", array_keys($msg)) 
			. "`) VALUES ('" . implode("', '", array_values($msg)) . "')" );

		// send email notification if requested.

		require_once('bbcode.php');
		if($importer['notify-flags'] & NOTIFY_MAIL) {
			$tpl = file_get_contents('view/mail_received_eml.tpl');			
			$email_tpl = replace_macros($tpl, array(
				'$sitename' => $a->config['sitename'],
				'$siteurl' =>  $a->get_baseurl(),
				'$username' => $importer['username'],
				'$email' => $importer['email'],
				'$from' => $msg['from-name'],
				'$title' => $msg['title'],
				'$body' => strip_tags(bbcode($msg['body']))
			));

			$res = mail($importer['email'], t("New mail received at ") . $a->config['sitename'],
				$email_tpl,t("From: Administrator@") . $a->get_hostname() );
		}
		xml_status(0);
		return; // NOTREACHED
	}	

	if($importer['readonly']) {

		xml_status(0);
		return; // NOTREACHED
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
				intval($importer['importer_uid'])
			);
			if(count($r)) {
				$item = $r[0];
				if($item['uri'] == $item['parent-uri']) {
					$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s'
						WHERE `parent-uri` = '%s'",
						dbesc($when),
						dbesc(datetime_convert()),
						dbesc($item['uri'])
					);
				}
				else {
					$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s' 
						WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
						dbesc($when),
						dbesc(datetime_convert()),
						dbesc($uri),
						intval($importer['importer_uid'])
					);
					if($item['last-child']) {
						// ensure that last-child is set in case the comment that had it just got wiped.
						$q("UPDATE `item` SET `last-child` = 0, `changed` = '%s' WHERE `parent-uri` = '%s' AND `uid` = %d ",
							dbesc(datetime_convert()),
							dbesc($item['parent-uri']),
							intval($item['uid'])
						);
						// who is the last child now? 
						$r = q("SELECT `id` FROM `item` WHERE `parent-uri` = '%s' AND `type` != 'activity' AND `deleted` = 0 
							ORDER BY `edited` DESC LIMIT 1",
								dbesc($item['parent-uri'])
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
		$rawthread = $item->get_item_tags("http://purl.org/syndication/thread/1.0",'in-reply-to');
		if(isset($rawthread[0]['attribs']['']['ref'])) {
			$is_reply = true;
			$parent_uri = $rawthread[0]['attribs']['']['ref'];
		}


		if($is_reply) {
			if($feed->get_item_quantity() == 1) {
				// remote reply to our post. Import and then notify everybody else.
				$datarray = get_atom_elements($item);
				$datarray['type'] = 'remote-comment';
				$datarray['parent-uri'] = $parent_uri;
				$datarray['uid'] = $importer['importer_uid'];
				$datarray['contact-id'] = $importer['id'];
				$posted_id = post_remote($a,$datarray);

				$r = q("SELECT `parent` FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
					intval($posted_id),
					intval($importer['importer_uid'])
				);
				if(count($r)) {
					$r1 = q("UPDATE `item` SET `last-child` = 0, `changed` = '%s' WHERE `uid` = %d AND `parent` = %d",
						dbesc(datetime_convert()),
						intval($importer['importer_uid']),
						intval($r[0]['parent'])
					);
				}
				$r2 = q("UPDATE `item` SET `last-child` = 1, `changed` = '%s' WHERE `uid` = %d AND `id` = %d LIMIT 1",
						dbesc(datetime_convert()),
						intval($importer['importer_uid']),
						intval($posted_id)
				);

				$php_path = ((strlen($a->config['php_path'])) ? $a->config['php_path'] : 'php');

				proc_close(proc_open("\"$php_path\" \"include/notifier.php\" \"comment-import\" \"$posted_id\" &", 
					array(),$foo));

				if(($importer['notify-flags'] & NOTIFY_COMMENT) && (! $importer['self'])) {
					require_once('bbcode.php');
					$from = stripslashes($datarray['author-name']);
					$tpl = file_get_contents('view/cmnt_received_eml.tpl');			
					$email_tpl = replace_macros($tpl, array(
						'$sitename' => $a->config['sitename'],
						'$siteurl' =>  $a->get_baseurl(),
						'$username' => $importer['username'],
						'$email' => $importer['email'],
						'$from' => $from,
						'$body' => strip_tags(bbcode(stripslashes($datarray['body'])))
					));

					$res = mail($importer['email'], $from . t(" commented on your item at ") . $a->config['sitename'],
						$email_tpl,t("From: Administrator@") . $a->get_hostname() );
				}
				xml_status(0);
				return;

			}
			else {
				// regular comment that is part of this total conversation. Have we seen it? If not, import it.

				$item_id = $item->get_id();

				$r = q("SELECT `uid`, `last-child`, `edited` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
					dbesc($item_id),
					intval($importer['importer_uid'])
				);
				// FIXME update content if 'updated' changes
				if(count($r)) {
					$allow = $item->get_item_tags( NAMESPACE_DFRN, 'comment-allow');
					if($allow && $allow[0]['data'] != $r[0]['last-child']) {
						$r = q("UPDATE `item` SET `last-child` = %d, `changed` = '%s' WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
							intval($allow[0]['data']),
							dbesc(datetime_convert()),
							dbesc($item_id),
							intval($importer['importer_uid'])
						);
					}
					continue;
				}
				$datarray = get_atom_elements($item);
				$datarray['parent-uri'] = $parent_uri;
				$datarray['uid'] = $importer['importer_uid'];
				$datarray['contact-id'] = $importer['id'];
				$r = post_remote($a,$datarray);

				// find out if our user is involved in this conversation and wants to be notified.
			
				if($importer['notify-flags'] & NOTIFY_COMMENT) {

					$myconv = q("SELECT `author-link` FROM `item` WHERE `parent-uri` = '%s'",
						dbesc($parent_uri)
					);
					if(count($myconv)) {
						foreach($myconv as $conv) {
							if($conv['author-link'] != $importer['url'])
								continue;
							require_once('bbcode.php');
							$from = stripslashes($datarray['author-name']);
							$tpl = file_get_contents('view/cmnt_received_eml.tpl');			
							$email_tpl = replace_macros($tpl, array(
								'$sitename' => $a->config['sitename'],
								'$siteurl' =>  $a->get_baseurl(),
								'$username' => $importer['username'],
								'$email' => $importer['email'],
								'$from' => $from,
								'$body' => strip_tags(bbcode(stripslashes($datarray['body'])))
							));

							$res = mail($importer['email'], $from . t(" commented on an item at ") 
								. $a->config['sitename'],
								$email_tpl,t("From: Administrator@") . $a->get_hostname() );
							break;
						}
					}
				}
				continue;
			}
		}
		else {
			// Head post of a conversation. Have we seen it? If not, import it.

			$item_id = $item->get_id();
			$r = q("SELECT `uid`, `last-child`, `edited` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
				dbesc($item_id),
				intval($importer['importer_uid'])
			);
			if(count($r)) {
				$allow = $item->get_item_tags( NAMESPACE_DFRN, 'comment-allow');
				if($allow && $allow[0]['data'] != $r[0]['last-child']) {
					$r = q("UPDATE `item` SET `last-child` = %d, `changed` = '%s' WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
						intval($allow[0]['data']),
						dbesc(datetime_convert()),
						dbesc($item_id),
						intval($importer['importer_uid'])
					);
				}
				continue;
			}


			$datarray = get_atom_elements($item);
			$datarray['parent-uri'] = $item_id;
			$datarray['uid'] = $importer['importer_uid'];
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

		$r = q("SELECT * FROM `contact` WHERE ( `issued-id` = '%s' OR ( `duplex` = 1 AND `dfrn-id` = '%s'))
			AND `blocked` = 0 AND `pending` = 0 LIMIT 1",
			dbesc($_GET['dfrn_id']),
			dbesc($_GET['dfrn_id'])
		);
		if(! count($r))
			$status = 1;

		$challenge = '';
		$encrypted_id = '';
		$id_str = $_GET['dfrn_id'] . '.' . mt_rand(1000,9999);

		if($r[0]['duplex']) {
			openssl_public_encrypt($hash,$challenge,$r[0]['pubkey']);
			openssl_public_encrypt($id_str,$encrypted_id,$r[0]['pubkey']);
		}
		else {
			openssl_private_encrypt($hash,$challenge,$r[0]['prvkey']);
			openssl_private_encrypt($id_str,$encrypted_id,$r[0]['prvkey']);
		}

		$challenge    = bin2hex($challenge);
		$encrypted_id = bin2hex($encrypted_id);

		echo '<?xml version="1.0" encoding="UTF-8"?><dfrn_notify><status>' .$status . '</status><dfrn_id>' . $encrypted_id . '</dfrn_id>' . '<challenge>' . $challenge . '</challenge></dfrn_notify>' . "\r\n" ;
		session_write_close();
		exit;
		
	}

}