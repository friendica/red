<?php

require_once('library/simplepie/simplepie.inc');
require_once('include/items.php');
require_once('include/event.php');


function dfrn_notify_post(&$a) {

	$dfrn_id      = ((x($_POST,'dfrn_id'))      ? notags(trim($_POST['dfrn_id']))   : '');
	$dfrn_version = ((x($_POST,'dfrn_version')) ? (float) $_POST['dfrn_version']    : 2.0);
	$challenge    = ((x($_POST,'challenge'))    ? notags(trim($_POST['challenge'])) : '');
	$data         = ((x($_POST,'data'))         ? $_POST['data']                    : '');
	$key          = ((x($_POST,'key'))          ? $_POST['key']                     : '');
	$dissolve     = ((x($_POST,'dissolve'))     ? intval($_POST['dissolve'])        :  0);
	$perm         = ((x($_POST,'perm'))         ? notags(trim($_POST['perm']))      : 'r');

	$writable = (-1);
	if($dfrn_version >= 2.21) {
		$writable = (($perm === 'rw') ? 1 : 0);
	}

	$direction = (-1);
	if(strpos($dfrn_id,':') == 1) {
		$direction = intval(substr($dfrn_id,0,1));
		$dfrn_id = substr($dfrn_id,2);
	}

	$r = q("SELECT * FROM `challenge` WHERE `dfrn-id` = '%s' AND `challenge` = '%s' LIMIT 1",
		dbesc($dfrn_id),
		dbesc($challenge)
	);
	if(! count($r)) {
		logger('dfrn_notify: could not match challenge to dfrn_id ' . $dfrn_id . ' challenge=' . $challenge);
		xml_status(3);
	}

	$r = q("DELETE FROM `challenge` WHERE `dfrn-id` = '%s' AND `challenge` = '%s' LIMIT 1",
		dbesc($dfrn_id),
		dbesc($challenge)
	);

	// find the local user who owns this relationship.

	$sql_extra = '';
	switch($direction) {
		case (-1):
			$sql_extra = sprintf(" AND ( `issued-id` = '%s' OR `dfrn-id` = '%s' ) ", dbesc($dfrn_id), dbesc($dfrn_id));
			break;
		case 0:
			$sql_extra = sprintf(" AND `issued-id` = '%s' AND `duplex` = 1 ", dbesc($dfrn_id));
			break;
		case 1:
			$sql_extra = sprintf(" AND `dfrn-id` = '%s' AND `duplex` = 1 ", dbesc($dfrn_id));
			break;
		default:
			xml_status(3);
			break; // NOTREACHED
	}
		 

	$r = q("SELECT	`contact`.*, `contact`.`uid` AS `importer_uid`, 
					`contact`.`pubkey` AS `cpubkey`, 
					`contact`.`prvkey` AS `cprvkey`, 
					`contact`.`thumb` AS `thumb`, 
					`contact`.`url` as `url`,
					`contact`.`name` as `senderName`,
					`user`.* 
			FROM `contact` 
			LEFT JOIN `user` ON `contact`.`uid` = `user`.`uid` 
			WHERE `contact`.`blocked` = 0 AND `contact`.`pending` = 0 
				AND `user`.`nickname` = '%s' $sql_extra LIMIT 1",
		dbesc($a->argv[1])
	);

	if(! count($r)) {
		logger('dfrn_notify: contact not found for dfrn_id ' . $dfrn_id);
		xml_status(3);
		//NOTREACHED
	}

	// $importer in this case contains the contact record for the remote contact joined with the user record of our user. 

	$importer = $r[0];

	if(($writable != (-1)) && ($writable != $importer['writable'])) {
		q("UPDATE `contact` SET `writable` = %d WHERE `id` = %d LIMIT 1",
			intval($writable),
			intval($importer['id'])
		);
		$importer['writable'] = $writable;
	}

	logger('dfrn_notify: received notify from ' . $importer['name'] . ' for ' . $importer['username']);
	logger('dfrn_notify: data: ' . $data, LOGGER_DATA);

	if($dissolve == 1) {

		/**
		 * Relationship is dissolved permanently
		 */

		require_once('include/Contact.php'); 
		contact_remove($importer['id']);
		logger('relationship dissolved : ' . $importer['name'] . ' dissolved ' . $importer['username']);
		xml_status(0);

	}

	if(strlen($key)) {
		$rawkey = hex2bin(trim($key));
		logger('rino: md5 raw key: ' . md5($rawkey));
		$final_key = '';

		if($dfrn_version >= 2.1) {
			if((($importer['duplex']) && strlen($importer['cprvkey'])) || (! strlen($importer['cpubkey']))) {
				openssl_private_decrypt($rawkey,$final_key,$importer['cprvkey']);
			}
			else {
				openssl_public_decrypt($rawkey,$final_key,$importer['cpubkey']);
			}
		}
		else {
			if((($importer['duplex']) && strlen($importer['cpubkey'])) || (! strlen($importer['cprvkey']))) {
				openssl_public_decrypt($rawkey,$final_key,$importer['cpubkey']);
			}
			else {
				openssl_private_decrypt($rawkey,$final_key,$importer['cprvkey']);
			}
		}

		logger('rino: received key : ' . $final_key);
		$data = aes_decrypt(hex2bin($data),$final_key);
		logger('rino: decrypted data: ' . $data, LOGGER_DATA);
	}


	if($importer['readonly']) {
		// We aren't receiving stuff from this person. But we will quietly ignore them
		// rather than a blatant "go away" message.
		logger('dfrn_notify: ignoring');
		xml_status(0);
		//NOTREACHED
	}

	// Consume notification feed. This may differ from consuming a public feed in several ways
	// - might contain email or friend suggestions
	// - might contain remote followup to our message
	//		- in which case we need to accept it and then notify other conversants
	// - we may need to send various email notifications

	$feed = new SimplePie();
	$feed->set_raw_data($data);
	$feed->enable_order_by_date(false);
	$feed->init();

	// handle friend suggestion notification

	$sugg = $feed->get_feed_tags( NAMESPACE_DFRN, 'suggest' );
	if(isset($sugg[0]['child'][NAMESPACE_DFRN])) {
		$base = $sugg[0]['child'][NAMESPACE_DFRN];
		$fsugg = array();
		$fsugg['uid'] = $importer['importer_uid'];
		$fsugg['cid'] = $importer['id'];
		$fsugg['name'] = notags(unxmlify($base['name'][0]['data']));
		$fsugg['photo'] = notags(unxmlify($base['photo'][0]['data']));
		$fsugg['url'] = notags(unxmlify($base['url'][0]['data']));
		$fsugg['request'] = notags(unxmlify($base['request'][0]['data']));
		$fsugg['body'] = escape_tags(unxmlify($base['note'][0]['data']));

		// Does our member already have a friend matching this description?

		$r = q("SELECT * FROM `contact` WHERE `name` = '%s' AND `url` = '%s' AND `uid` = %d LIMIT 1",
			dbesc($fsugg['name']),
			dbesc($fsugg['url']),
			intval($fsugg['uid'])
		);
		if(count($r))
			xml_status(0);

		// Do we already have an fcontact record for this person?

		$fid = 0;
		$r = q("SELECT * FROM `fcontact` WHERE `url` = '%s' AND `name` = '%s' AND `request` = '%s' LIMIT 1",
			dbesc($fsugg['url']),
			dbesc($fsugg['name']),
			dbesc($fsugg['request'])
		);
		if(count($r)) {
			$fid = $r[0]['id'];
		}
		if(! $fid)
			$r = q("INSERT INTO `fcontact` ( `name`,`url`,`photo`,`request` ) VALUES ( '%s', '%s', '%s', '%s' ) ",
			dbesc($fsugg['name']),
			dbesc($fsugg['url']),
			dbesc($fsugg['photo']),
			dbesc($fsugg['request'])
		);
		$r = q("SELECT * FROM `fcontact` WHERE `url` = '%s' AND `name` = '%s' AND `request` = '%s' LIMIT 1",
			dbesc($fsugg['url']),
			dbesc($fsugg['name']),
			dbesc($fsugg['request'])
		);
		if(count($r)) {
			$fid = $r[0]['id'];
		}
		// database record did not get created. Quietly give up.
		else
			xml_status(0);

		$hash = random_string();
 
		$r = q("INSERT INTO `intro` ( `uid`, `fid`, `contact-id`, `note`, `hash`, `datetime`, `blocked` )
			VALUES( %d, %d, %d, '%s', '%s', '%s', %d )",
			intval($fsugg['uid']),
			intval($fid),
			intval($fsugg['cid']),
			dbesc($fsugg['body']),
			dbesc($hash),
			dbesc(datetime_convert()),
			intval(0)
		);

		// TODO - send email notify (which may require a new notification preference)

		xml_status(0);
	}

	$ismail = false;

	$rawmail = $feed->get_feed_tags( NAMESPACE_DFRN, 'mail' );
	if(isset($rawmail[0]['child'][NAMESPACE_DFRN])) {

		logger('dfrn_notify: private message received');

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
		$msg['seen'] = 0;
		$msg['replied'] = 0;
		$msg['uri'] = notags(unxmlify($base['id'][0]['data']));
		$msg['parent-uri'] = notags(unxmlify($base['in-reply-to'][0]['data']));
		$msg['created'] = datetime_convert(notags(unxmlify('UTC','UTC',$base['sentdate'][0]['data'])));
		
		dbesc_array($msg);

		$r = dbq("INSERT INTO `mail` (`" . implode("`, `", array_keys($msg)) 
			. "`) VALUES ('" . implode("', '", array_values($msg)) . "')" );

		// send email notification if requested.

		require_once('bbcode.php');
		if($importer['notify-flags'] & NOTIFY_MAIL) {

			push_lang($importer['language']);

			// name of the automated email sender
			$msg['notificationfromname']	= t('Administrator');
			// noreply address to send from
			$msg['notificationfromemail']	= t('noreply') . '@' . $a->get_hostname();				

			// text version
			// process the message body to display properly in text mode
			// 		1) substitute a \n character for the "\" then "n", so it behaves properly (it doesn't come in as a \n character)
			//		2) remove escape slashes
			//		3) decode any bbcode from the message editor
			//		4) decode any encoded html tags
			//		5) remove html tags
			$msg['textversion']
				= strip_tags(html_entity_decode(bbcode(stripslashes(str_replace(array("\\r\\n", "\\r", "\\n"), "\n",$msg['body']))),ENT_QUOTES,'UTF-8'));
				
			// html version
			// process the message body to display properly in text mode
			// 		1) substitute a <br /> tag for the "\" then "n", so it behaves properly (it doesn't come in as a \n character)
			//		2) remove escape slashes
			//		3) decode any bbcode from the message editor
			//		4) decode any encoded html tags
			$msg['htmlversion']	
				= html_entity_decode(bbcode(stripslashes(str_replace(array("\\r\\n", "\\r","\\n\\n" ,"\\n"), "<br />\n",$msg['body']))));

			// load the template for private message notifications
			$tpl = get_intltext_template('mail_received_html_body_eml.tpl');
			$email_html_body_tpl = replace_macros($tpl,array(
				'$username'     => $importer['username'],
				'$siteName'		=> $a->config['sitename'],			// name of this site
				'$siteurl'		=> $a->get_baseurl(),				// descriptive url of this site
				'$thumb'		=> $importer['thumb'],				// thumbnail url for sender icon
				'$email'		=> $importer['email'],				// email address to send to
				'$url'			=> $importer['url'],				// full url for the site
				'$from'			=> $msg['from-name'],				// name of the person sending the message
				'$title'		=> stripslashes($msg['title']),			// subject of the message
				'$htmlversion'	=> $msg['htmlversion'],					// html version of the message
				'$mimeboundary'	=> $msg['mimeboundary'],				// mime message divider
				'$hostname'		=> $a->get_hostname()				// name of this host
			));
			
			// load the template for private message notifications
			$tpl = get_intltext_template('mail_received_text_body_eml.tpl');
			$email_text_body_tpl = replace_macros($tpl,array(
				'$username'     => $importer['username'],
				'$siteName'		=> $a->config['sitename'],			// name of this site
				'$siteurl'		=> $a->get_baseurl(),				// descriptive url of this site
				'$thumb'		=> $importer['thumb'],				// thumbnail url for sender icon
				'$email'		=> $importer['email'],				// email address to send to
				'$url'			=> $importer['url'],				// full url for the site
				'$from'			=> $msg['from-name'],				// name of the person sending the message
				'$title'		=> stripslashes($msg['title']),			// subject of the message
				'$textversion'	=> $msg['textversion'],					// text version of the message
				'$mimeboundary'	=> $msg['mimeboundary'],				// mime message divider
				'$hostname'		=> $a->get_hostname()				// name of this host
			));

			// use the EmailNotification library to send the message
			require_once("include/EmailNotification.php");
			EmailNotification::sendTextHtmlEmail(
				$msg['notificationfromname'],
				$msg['notificationfromemail'],
				$msg['notificationfromemail'],
				$importer['email'],
				t('New mail received at ') . $a->config['sitename'],
				$email_html_body_tpl,
				$email_text_body_tpl
			);

			pop_lang();
		}
		xml_status(0);
		// NOTREACHED
	}	
	
	logger('dfrn_notify: feed item count = ' . $feed->get_item_quantity());

	// process any deleted entries

	$del_entries = $feed->get_feed_tags(NAMESPACE_TOMB, 'deleted-entry');
	if(is_array($del_entries) && count($del_entries)) {
		foreach($del_entries as $dentry) {
			$deleted = false;
			if(isset($dentry['attribs']['']['ref'])) {
				$uri = $dentry['attribs']['']['ref'];
				$deleted = true;
				if(isset($dentry['attribs']['']['when'])) {
					$when = $dentry['attribs']['']['when'];
					$when = datetime_convert('UTC','UTC', $when, 'Y-m-d H:i:s');
				}
				else
					$when = datetime_convert('UTC','UTC','now','Y-m-d H:i:s');
			}
			if($deleted) {

				$r = q("SELECT * FROM `item` WHERE `uri` = '%s' AND `uid` = %d AND `contact-id` = %d LIMIT 1",
					dbesc($uri),
					intval($importer['importer_uid']),
					intval($importer['id'])
				);

				if(count($r)) {
					$item = $r[0];

					if(! $item['deleted'])
						logger('dfrn_notify: deleting item ' . $item['id'] . ' uri=' . $item['uri'], LOGGER_DEBUG);

					if($item['uri'] == $item['parent-uri']) {
						$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s'
							WHERE `parent-uri` = '%s' AND `uid` = %d",
							dbesc($when),
							dbesc(datetime_convert()),
							dbesc($item['uri']),
							intval($importer['importer_uid'])
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
							q("UPDATE `item` SET `last-child` = 0, `changed` = '%s' WHERE `parent-uri` = '%s' AND `uid` = %d ",
								dbesc(datetime_convert()),
								dbesc($item['parent-uri']),
								intval($item['uid'])
							);
							// who is the last child now? 
							$r = q("SELECT `id` FROM `item` WHERE `parent-uri` = '%s' AND `type` != 'activity' AND `deleted` = 0 AND `uid` = %d
								ORDER BY `created` DESC LIMIT 1",
									dbesc($item['parent-uri']),
									intval($importer['importer_uid'])
							);
							if(count($r)) {
								q("UPDATE `item` SET `last-child` = 1 WHERE `id` = %d LIMIT 1",
									intval($r[0]['id'])
								);
							}	
						}
					}	
				}
			}
		}
	}


	foreach($feed->get_items() as $item) {

		$is_reply = false;		
		$item_id = $item->get_id();
		$rawthread = $item->get_item_tags( NAMESPACE_THREAD, 'in-reply-to');
		if(isset($rawthread[0]['attribs']['']['ref'])) {
			$is_reply = true;
			$parent_uri = $rawthread[0]['attribs']['']['ref'];
		}

		if($is_reply) {
			if($feed->get_item_quantity() == 1) {
				logger('dfrn_notify: received remote comment');
				$is_like = false;
				// remote reply to our post. Import and then notify everybody else.
				$datarray = get_atom_elements($feed,$item);
				$datarray['type'] = 'remote-comment';
				$datarray['wall'] = 1;
				$datarray['parent-uri'] = $parent_uri;
				$datarray['uid'] = $importer['importer_uid'];
				$datarray['contact-id'] = $importer['id'];
				if(($datarray['verb'] == ACTIVITY_LIKE) || ($datarray['verb'] == ACTIVITY_DISLIKE)) {
					$is_like = true;
					$datarray['type'] = 'activity';
					$datarray['gravity'] = GRAVITY_LIKE;
					$datarray['last-child'] = 0;
				}
				$posted_id = item_store($datarray);
				$parent = 0;

				if($posted_id) {
					$r = q("SELECT `parent` FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
						intval($posted_id),
						intval($importer['importer_uid'])
					);
					if(count($r))
						$parent = $r[0]['parent'];
			
					if(! $is_like) {
						$r1 = q("UPDATE `item` SET `last-child` = 0, `changed` = '%s' WHERE `uid` = %d AND `parent` = %d",
							dbesc(datetime_convert()),
							intval($importer['importer_uid']),
							intval($r[0]['parent'])
						);

						$r2 = q("UPDATE `item` SET `last-child` = 1, `changed` = '%s' WHERE `uid` = %d AND `id` = %d LIMIT 1",
							dbesc(datetime_convert()),
							intval($importer['importer_uid']),
							intval($posted_id)
						);
					}

					if($posted_id && $parent) {
				
						proc_run('php',"include/notifier.php","comment-import","$posted_id");
					
						if((! $is_like) && ($importer['notify-flags'] & NOTIFY_COMMENT) && (! $importer['self'])) {
							push_lang($importer['language']);
							require_once('bbcode.php');
							$from = stripslashes($datarray['author-name']);

							// name of the automated email sender
							$msg['notificationfromname']	= stripslashes($datarray['author-name']);;
							// noreply address to send from
							$msg['notificationfromemail']	= t('noreply') . '@' . $a->get_hostname();				

							// text version
							// process the message body to display properly in text mode
							$msg['textversion']
								= html_entity_decode(strip_tags(bbcode(stripslashes($datarray['body']))), ENT_QUOTES, 'UTF-8');
				
							// html version
							// process the message body to display properly in text mode
							$msg['htmlversion']	
								= html_entity_decode(bbcode(stripslashes(str_replace(array("\\r\\n", "\\r","\\n\\n" ,"\\n"), "<br />\n",$datarray['body']))));

							// load the template for private message notifications
							$tpl = get_intltext_template('cmnt_received_html_body_eml.tpl');
							$email_html_body_tpl = replace_macros($tpl,array(
								'$username'     => $importer['username'],
								'$sitename'		=> $a->config['sitename'],			// name of this site
								'$siteurl'		=> $a->get_baseurl(),				// descriptive url of this site
								'$thumb'		=> $datarray['author-avatar'],			// thumbnail url for sender icon
								'$email'		=> $importer['email'],				// email address to send to
								'$url'			=> $datarray['author-link'],			// full url for the site
								'$from'			=> $from,					// name of the person sending the message
								'$body'			=> $msg['htmlversion'],			// html version of the message
								'$display'		=> $a->get_baseurl() . '/display/' . $importer['nickname'] . '/' . $posted_id,
							));
			
							// load the template for private message notifications
							$tpl = get_intltext_template('cmnt_received_text_body_eml.tpl');
							$email_text_body_tpl = replace_macros($tpl,array(
								'$username'     => $importer['username'],
								'$sitename'		=> $a->config['sitename'],			// name of this site
								'$siteurl'		=> $a->get_baseurl(),				// descriptive url of this site
								'$thumb'		=> $datarray['author-avatar'],			// thumbnail url for sender icon
								'$email'		=> $importer['email'],				// email address to send to
								'$url'			=> $datarray['author-link'],			// full url for the site
								'$from'			=> $from,					// name of the person sending the message
								'$body'			=> $msg['textversion'],				// text version of the message
								'$display'		=> $a->get_baseurl() . '/display/' . $importer['nickname'] . '/' . $posted_id,
							));

							// use the EmailNotification library to send the message
							require_once("include/EmailNotification.php");
							EmailNotification::sendTextHtmlEmail(
								$msg['notificationfromname'],
								t("Administrator") . '@' . $a->get_hostname(),
								t("noreply") . '@' . $a->get_hostname(),
								$importer['email'],
								sprintf( t('%s commented on an item at %s'), $from , $a->config['sitename']),
								$email_html_body_tpl,
								$email_text_body_tpl
							);
							pop_lang();
						}
					}
					xml_status(0);
					// NOTREACHED
				}
			}
			else {

				// regular comment that is part of this total conversation. Have we seen it? If not, import it.

				$item_id  = $item->get_id();
				$datarray = get_atom_elements($feed,$item);

				$r = q("SELECT `uid`, `last-child`, `edited`, `body` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
					dbesc($item_id),
					intval($importer['importer_uid'])
				);

				// Update content if 'updated' changes

				if(count($r)) {
					if((x($datarray,'edited') !== false) && (datetime_convert('UTC','UTC',$datarray['edited']) !== $r[0]['edited'])) {  
						$r = q("UPDATE `item` SET `body` = '%s', `edited` = '%s' WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
							dbesc($datarray['body']),
							dbesc(datetime_convert('UTC','UTC',$datarray['edited'])),
							dbesc($item_id),
							intval($importer['importer_uid'])
						);
					}

					// update last-child if it changes

					$allow = $item->get_item_tags( NAMESPACE_DFRN, 'comment-allow');
					if(($allow) && ($allow[0]['data'] != $r[0]['last-child'])) {
						$r = q("UPDATE `item` SET `last-child` = 0, `changed` = '%s' WHERE `parent-uri` = '%s' AND `uid` = %d",
							dbesc(datetime_convert()),
							dbesc($parent_uri),
							intval($importer['importer_uid'])
						);
						$r = q("UPDATE `item` SET `last-child` = %d , `changed` = '%s'  WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
							intval($allow[0]['data']),
							dbesc(datetime_convert()),
							dbesc($item_id),
							intval($importer['importer_uid'])
						);
					}
					continue;
				}

				$datarray['parent-uri'] = $parent_uri;
				$datarray['uid'] = $importer['importer_uid'];
				$datarray['contact-id'] = $importer['id'];
				if(($datarray['verb'] == ACTIVITY_LIKE) || ($datarray['verb'] == ACTIVITY_DISLIKE)) {
					$datarray['type'] = 'activity';
					$datarray['gravity'] = GRAVITY_LIKE;
				}
				$posted_id = item_store($datarray);

				// find out if our user is involved in this conversation and wants to be notified.
			
				if(($datarray['type'] != 'activity') && ($importer['notify-flags'] & NOTIFY_COMMENT)) {

					$myconv = q("SELECT `author-link`, `author-avatar` FROM `item` WHERE `parent-uri` = '%s' AND `uid` = %d AND `parent` != 0 ",
						dbesc($parent_uri),
						intval($importer['importer_uid'])
					);
					if(count($myconv)) {
						$importer_url = $a->get_baseurl() . '/profile/' . $importer['nickname'];
						foreach($myconv as $conv) {
							if(! link_compare($conv['author-link'],$importer_url))
								continue;

							push_lang($importer['language']);
							require_once('bbcode.php');
							$from = stripslashes($datarray['author-name']);
							
							// name of the automated email sender
							$msg['notificationfromname']	= stripslashes($datarray['author-name']);;
							// noreply address to send from
							$msg['notificationfromemail']	= t('noreply') . '@' . $a->get_hostname();				

							// text version
							// process the message body to display properly in text mode
							$msg['textversion']
								= html_entity_decode(strip_tags(bbcode(stripslashes($datarray['body']))), ENT_QUOTES, 'UTF-8');
				
							// html version
							// process the message body to display properly in text mode
							$msg['htmlversion']	
								= html_entity_decode(bbcode(stripslashes(str_replace(array("\\r\\n", "\\r","\\n\\n" ,"\\n"), "<br />\n",$datarray['body']))));

							// load the template for private message notifications
							$tpl = get_intltext_template('cmnt_received_html_body_eml.tpl');
							$email_html_body_tpl = replace_macros($tpl,array(
								'$username'     => $importer['username'],
								'$sitename'		=> $a->config['sitename'],				// name of this site
								'$siteurl'		=> $a->get_baseurl(),					// descriptive url of this site
								'$thumb'		=> $datarray['author-avatar'],				// thumbnail url for sender icon
								'$url'			=> $datarray['author-link'],				// full url for the site
								'$from'			=> $from,						// name of the person sending the message
								'$body'			=> $msg['htmlversion'],					// html version of the message
								'$display'		=> $a->get_baseurl() . '/display/' . $importer['nickname'] . '/' . $posted_id,
							));
			
							// load the template for private message notifications
							$tpl = get_intltext_template('cmnt_received_text_body_eml.tpl');
							$email_text_body_tpl = replace_macros($tpl,array(
								'$username'     => $importer['username'],
								'$sitename'		=> $a->config['sitename'],				// name of this site
								'$siteurl'		=> $a->get_baseurl(),					// descriptive url of this site
								'$thumb'		=> $datarray['author-avatar'],				// thumbnail url for sender icon
								'$url'			=> $datarray['author-link'],				// full url for the site
								'$from'			=> $from,						// name of the person sending the message
								'$body'			=> $msg['textversion'],					// text version of the message
								'$display'		=> $a->get_baseurl() . '/display/' . $importer['nickname'] . '/' . $posted_id,
							));

							// use the EmailNotification library to send the message
							require_once("include/EmailNotification.php");
							EmailNotification::sendTextHtmlEmail(
								$msg['notificationfromname'],
								t("Administrator@") . $a->get_hostname(),
								t("noreply") . '@' . $a->get_hostname(),
								$importer['email'],
								sprintf( t('%s commented on an item at %s'), $from , $a->config['sitename']),
								$email_html_body_tpl,
								$email_text_body_tpl
							);
							pop_lang();
							break;
						}
					}
				}
				continue;
			}
		}

		else {

			// Head post of a conversation. Have we seen it? If not, import it.


			$item_id  = $item->get_id();
			$datarray = get_atom_elements($feed,$item);

			if((x($datarray,'object-type')) && ($datarray['object-type'] === ACTIVITY_OBJ_EVENT)) {
				$ev = bbtoevent($datarray['body']);
				if(x($ev,'desc') && x($ev,'start')) {
					$ev['cid'] = $importer['id'];
					$ev['uid'] = $importer['uid'];
					$ev['uri'] = $item_id;
					$ev['edited'] = $datarray['edited'];

					$r = q("SELECT * FROM `event` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
						dbesc($item_id),
						intval($importer['uid'])
					);
					if(count($r))
						$ev['id'] = $r[0]['id'];
					$xyz = event_store($ev);
					continue;
				}
			}

			$r = q("SELECT `uid`, `last-child`, `edited`, `body` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
				dbesc($item_id),
				intval($importer['importer_uid'])
			);

			// Update content if 'updated' changes

			if(count($r)) {
				if((x($datarray,'edited') !== false) && (datetime_convert('UTC','UTC',$datarray['edited']) !== $r[0]['edited'])) {  
					$r = q("UPDATE `item` SET `body` = '%s', `edited` = '%s' WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
						dbesc($datarray['body']),
						dbesc(datetime_convert('UTC','UTC',$datarray['edited'])),
						dbesc($item_id),
						intval($importer['importer_uid'])
					);
				}

				// update last-child if it changes

				$allow = $item->get_item_tags( NAMESPACE_DFRN, 'comment-allow');
				if($allow && $allow[0]['data'] != $r[0]['last-child']) {
					$r = q("UPDATE `item` SET `last-child` = %d , `changed` = '%s' WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
						intval($allow[0]['data']),
						dbesc(datetime_convert()),
						dbesc($item_id),
						intval($importer['importer_uid'])
					);
				}
				continue;
			}

			$datarray['parent-uri'] = $item_id;
			$datarray['uid'] = $importer['importer_uid'];
			$datarray['contact-id'] = $importer['id'];
			$r = item_store($datarray);
			continue;
		}
	}

	xml_status(0);
	// NOTREACHED

}


function dfrn_notify_content(&$a) {

	if(x($_GET,'dfrn_id')) {

		// initial communication from external contact, $direction is their direction.
		// If this is a duplex communication, ours will be the opposite.

		$dfrn_id = notags(trim($_GET['dfrn_id']));
		$dfrn_version = (float) $_GET['dfrn_version'];

		logger('dfrn_notify: new notification dfrn_id=' . $dfrn_id);

		$direction = (-1);
		if(strpos($dfrn_id,':') == 1) {
			$direction = intval(substr($dfrn_id,0,1));
			$dfrn_id = substr($dfrn_id,2);
		}

		$hash = random_string();

		$status = 0;

		$r = q("DELETE FROM `challenge` WHERE `expire` < " . intval(time()));

		$r = q("INSERT INTO `challenge` ( `challenge`, `dfrn-id`, `expire` )
			VALUES( '%s', '%s', %d ) ",
			dbesc($hash),
			dbesc($dfrn_id),
			intval(time() + 90 )
		);

		logger('dfrn_notify: challenge=' . $hash );

		$sql_extra = '';
		switch($direction) {
			case (-1):
				$sql_extra = sprintf(" AND ( `issued-id` = '%s' OR `dfrn-id` = '%s' ) ", dbesc($dfrn_id), dbesc($dfrn_id));
				$my_id = $dfrn_id;
				break;
			case 0:
				$sql_extra = sprintf(" AND `issued-id` = '%s' AND `duplex` = 1 ", dbesc($dfrn_id));
				$my_id = '1:' . $dfrn_id;
				break;
			case 1:
				$sql_extra = sprintf(" AND `dfrn-id` = '%s' AND `duplex` = 1 ", dbesc($dfrn_id));
				$my_id = '0:' . $dfrn_id;
				break;
			default:
				$status = 1;
				break; // NOTREACHED
		}

		$r = q("SELECT `contact`.*, `user`.`nickname` FROM `contact` LEFT JOIN `user` ON `user`.`uid` = `contact`.`uid` 
				WHERE `contact`.`blocked` = 0 AND `contact`.`pending` = 0 AND `user`.`nickname` = '%s' $sql_extra LIMIT 1",
				dbesc($a->argv[1])
		);

		if(! count($r))
			$status = 1;

		$challenge = '';
		$encrypted_id = '';
		$id_str = $my_id . '.' . mt_rand(1000,9999);

		if((($r[0]['duplex']) && strlen($r[0]['prvkey'])) || (! strlen($r[0]['pubkey']))) {
			openssl_private_encrypt($hash,$challenge,$r[0]['prvkey']);
			openssl_private_encrypt($id_str,$encrypted_id,$r[0]['prvkey']);
		}
		else {
			openssl_public_encrypt($hash,$challenge,$r[0]['pubkey']);
			openssl_public_encrypt($id_str,$encrypted_id,$r[0]['pubkey']);
		}

		$challenge    = bin2hex($challenge);
		$encrypted_id = bin2hex($encrypted_id);

		$rino = ((function_exists('mcrypt_encrypt')) ? 1 : 0);

		$rino_enable = get_config('system','rino_encrypt');

		if(! $rino_enable)
			$rino = 0;


		header("Content-type: text/xml");

		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n" 
			. '<dfrn_notify>' . "\r\n"
			. "\t" . '<status>' . $status . '</status>' . "\r\n"
			. "\t" . '<dfrn_version>' . DFRN_PROTOCOL_VERSION . '</dfrn_version>' . "\r\n"
			. "\t" . '<rino>' . $rino . '</rino>' . "\r\n" 
			. "\t" . '<dfrn_id>' . $encrypted_id . '</dfrn_id>' . "\r\n" 
			. "\t" . '<challenge>' . $challenge . '</challenge>' . "\r\n"
			. '</dfrn_notify>' . "\r\n" ;

		killme();
	}

}
