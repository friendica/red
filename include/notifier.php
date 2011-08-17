<?php
require_once("boot.php");

function notifier_run($argv, $argc){
	global $a, $db;

	if(is_null($a)){
		$a = new App;
	}
  
	if(is_null($db)) {
		@include(".htconfig.php");
		require_once("dba.php");
		$db = new dba($db_host, $db_user, $db_pass, $db_data);
		        unset($db_host, $db_user, $db_pass, $db_data);
	}

	require_once("session.php");
	require_once("datetime.php");
	require_once('include/items.php');
	require_once('include/bbcode.php');

	load_config('config');
	load_config('system');

	load_hooks();

	if($argc < 3)
		return;

	$a->set_baseurl(get_config('system','url'));

	logger('notifier: invoked: ' . print_r($argv,true));

	$cmd = $argv[1];

	switch($cmd) {

		case 'mail':
		default:
			$item_id = intval($argv[2]);
			if(! $item_id){
				return;
			}
			break;
	}

	$expire = false;
	$top_level = false;
	$recipients = array();
	$url_recipients = array();

	if($cmd === 'mail') {

		$message = q("SELECT * FROM `mail` WHERE `id` = %d LIMIT 1",
				intval($item_id)
		);
		if(! count($message)){
			return;
		}
		$uid = $message[0]['uid'];
		$recipients[] = $message[0]['contact-id'];
		$item = $message[0];

	}
	elseif($cmd === 'expire') {
		$expire = true;
		$items = q("SELECT * FROM `item` WHERE `uid` = %d AND `wall` = 1 
			AND `deleted` = 1 AND `changed` > UTC_TIMESTAMP - INTERVAL 10 MINUTE",
			intval($item_id)
		);
		$uid = $item_id;
		$item_id = 0;
		if(! count($items))
			return;
	}
	elseif($cmd === 'suggest') {
		$suggest = q("SELECT * FROM `fsuggest` WHERE `id` = %d LIMIT 1",
			intval($item_id)
		);
		if(! count($suggest))
			return;
		$uid = $suggest[0]['uid'];
		$recipients[] = $suggest[0]['cid'];
		$item = $suggest[0];
	}
	else {

		// find ancestors
		$r = q("SELECT * FROM `item` WHERE `id` = %d LIMIT 1",
			intval($item_id)
		);

		if((! count($r)) || (! intval($r[0]['parent']))) {
			return;
		}

		$parent_item = $r[0];
		$parent_id = intval($r[0]['parent']);
		$uid = $r[0]['uid'];
		$updated = $r[0]['edited'];

		$items = q("SELECT * FROM `item` WHERE `parent` = %d ORDER BY `id` ASC",
			intval($parent_id)
		);

		if(! count($items)) {
			return;
		}

		// avoid race condition with deleting entries

		if($items[0]['deleted']) {
			foreach($items as $item)
				$item['deleted'] = 1;
		}

		if(count($items) == 1 && $items[0]['uri'] === $items[0]['parent-uri'])
			$top_level = true;
	}

	$r = q("SELECT `contact`.*, `user`.`pubkey` AS `upubkey`, `user`.`prvkey` AS `uprvkey`, 
		`user`.`timezone`, `user`.`nickname`, `user`.`sprvkey`, `user`.`spubkey`, 
		`user`.`page-flags`, `user`.`prvnets`
		FROM `contact` LEFT JOIN `user` ON `user`.`uid` = `contact`.`uid` 
		WHERE `contact`.`uid` = %d AND `contact`.`self` = 1 LIMIT 1",
		intval($uid)
	);

	if(! count($r))
		return;

	$owner = $r[0];

	$hub = get_config('system','huburl');

	// If this is a public conversation, notify the feed hub
	$notify_hub = true;

	// fill this in with a single salmon slap if applicable
	$slap = '';

	if($cmd != 'mail' && $cmd != 'suggest') {

		require_once('include/group.php');

		$parent = $items[0];

		if($parent['wall'] == 0 && (! $expire)) {
			// local followup to remote post
			$followup = true;
			$notify_hub = false; // not public
			$conversant_str = dbesc($parent['contact-id']);
		}
		else {
			$followup = false;

			if((strlen($parent['allow_cid'])) 
				|| (strlen($parent['allow_gid'])) 
				|| (strlen($parent['deny_cid'])) 
				|| (strlen($parent['deny_gid']))) {
				$notify_hub = false; // private recipients, not public
			}

			$allow_people = expand_acl($parent['allow_cid']);
			$allow_groups = expand_groups(expand_acl($parent['allow_gid']));
			$deny_people  = expand_acl($parent['deny_cid']);
			$deny_groups  = expand_groups(expand_acl($parent['deny_gid']));

			$conversants = array();

			foreach($items as $item) {
				$recipients[] = $item['contact-id'];
				$conversants[] = $item['contact-id'];
				// pull out additional tagged people to notify (if public message)
				if($notify_hub && strlen($item['inform'])) {
					$people = explode(',',$item['inform']);
					foreach($people as $person) {
						if(substr($person,0,4) === 'cid:') {
							$recipients[] = intval(substr($person,4));
							$conversants[] = intval(substr($person,4));
						}
						else {
							$url_recipients[] = substr($person,4);
						}
					}
				}
			}

			logger('notifier: url_recipients' . print_r($url_recipients,true));

			$conversants = array_unique($conversants);


			$recipients = array_unique(array_merge($recipients,$allow_people,$allow_groups));
			$deny = array_unique(array_merge($deny_people,$deny_groups));
			$recipients = array_diff($recipients,$deny);

			$conversant_str = dbesc(implode(', ',$conversants));
		}

		$r = q("SELECT * FROM `contact` WHERE `id` IN ( $conversant_str ) AND `blocked` = 0 AND `pending` = 0");


		if(count($r))
			$contacts = $r;
	}

	$feed_template = get_markup_template('atom_feed.tpl');
	$mail_template = get_markup_template('atom_mail.tpl');

	$atom = '';
	$slaps = array();

	$hubxml = feed_hublinks();

	$birthday = feed_birthday($owner['uid'],$owner['timezone']);

	if(strlen($birthday))
		$birthday = '<dfrn:birthday>' . xmlify($birthday) . '</dfrn:birthday>';

	$atom .= replace_macros($feed_template, array(
			'$version'      => xmlify(FRIENDIKA_VERSION),
			'$feed_id'      => xmlify($a->get_baseurl() . '/profile/' . $owner['nickname'] ),
			'$feed_title'   => xmlify($owner['name']),
			'$feed_updated' => xmlify(datetime_convert('UTC', 'UTC', $updated . '+00:00' , ATOM_TIME)) ,
			'$hub'          => $hubxml,
			'$salmon'       => '',  // private feed, we don't use salmon here
			'$name'         => xmlify($owner['name']),
			'$profile_page' => xmlify($owner['url']),
			'$photo'        => xmlify($owner['photo']),
			'$thumb'        => xmlify($owner['thumb']),
			'$picdate'      => xmlify(datetime_convert('UTC','UTC',$owner['avatar-date'] . '+00:00' , ATOM_TIME)) ,
			'$uridate'      => xmlify(datetime_convert('UTC','UTC',$owner['uri-date']    . '+00:00' , ATOM_TIME)) ,
			'$namdate'      => xmlify(datetime_convert('UTC','UTC',$owner['name-date']   . '+00:00' , ATOM_TIME)) ,
			'$birthday'     => $birthday
	));

	if($cmd === 'mail') {
		$notify_hub = false;  // mail is  not public

		$body = fix_private_photos($item['body'],$owner['uid']);

		$atom .= replace_macros($mail_template, array(
			'$name'         => xmlify($owner['name']),
			'$profile_page' => xmlify($owner['url']),
			'$thumb'        => xmlify($owner['thumb']),
			'$item_id'      => xmlify($item['uri']),
			'$subject'      => xmlify($item['title']),
			'$created'      => xmlify(datetime_convert('UTC', 'UTC', $item['created'] . '+00:00' , ATOM_TIME)),
			'$content'      => xmlify($body),
			'$parent_id'    => xmlify($item['parent-uri'])
		));
	}
	elseif($cmd === 'suggest') {
		$notify_hub = false;  // suggestions are not public

		$sugg_template = get_markup_template('atom_suggest.tpl');

		$atom .= replace_macros($sugg_template, array(
			'$name'         => xmlify($item['name']),
			'$url'          => xmlify($item['url']),
			'$photo'        => xmlify($item['photo']),
			'$request'      => xmlify($item['request']),
			'$note'         => xmlify($item['note'])
		));

		// We don't need this any more

		q("DELETE FROM `fsuggest` WHERE `id` = %d LIMIT 1",
			intval($item['id'])
		);

	}
	else {
		if($followup) {
			foreach($items as $item) {  // there is only one item
				if(! $item['parent'])
					continue;
				if($item['id'] == $item_id) {
					logger('notifier: followup: item: ' . print_r($item,true), LOGGER_DATA);
					$slap  = atom_entry($item,'html',$owner,$owner,false);
					$atom .= atom_entry($item,'text',$owner,$owner,false);
				}
			}
		}
		else {
			foreach($items as $item) {

				if(! $item['parent'])
					continue;

				// private emails may be in included in public conversations. Filter them.

				if(($notify_hub) && $item['private'])
					continue;

				$contact = get_item_contact($item,$contacts);
				if(! $contact)
					continue;

				$atom .= atom_entry($item,'text',$contact,$owner,true);

				if(($top_level) && ($notify_hub) && ($item['author-link'] === $item['owner-link']) && (! $expire)) 
					$slaps[] = atom_entry($item,'html',$contact,$owner,true);
			}
		}
	}
	$atom .= '</feed>' . "\r\n";

	logger('notifier: ' . $atom, LOGGER_DATA);

	logger('notifier: slaps: ' . print_r($slaps,true), LOGGER_DATA);

	// If this is a public message and pubmail is set on the parent, include all your email contacts

	$mail_disabled = ((function_exists('imap_open') && (! get_config('system','imap_disabled'))) ? 0 : 1);

	if(! $mail_disabled) {
		if((! strlen($parent_item['allow_cid'])) && (! strlen($parent_item['allow_gid'])) 
			&& (! strlen($parent_item['deny_cid'])) && (! strlen($parent_item['deny_gid'])) 
			&& (intval($parent_item['pubmail']))) {
			$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `network` = '%s'",
				intval($uid),
				dbesc(NETWORK_MAIL)
			);
			if(count($r)) {
				foreach($r as $rr)
					$recipients[] = $rr['id'];
			}
		}
	}

	if($followup)
		$recip_str = $parent['contact-id'];
	else
		$recip_str = implode(', ', $recipients);

	$r = q("SELECT * FROM `contact` WHERE `id` IN ( %s ) AND `blocked` = 0 AND `pending` = 0 ",
		dbesc($recip_str)
	);

	// delivery loop

	require_once('include/salmon.php');

	if(count($r)) {
		foreach($r as $contact) {
			if($contact['self'])
				continue;

			$deliver_status = 0;

			switch($contact['network']) {
				case NETWORK_DFRN:
					logger('notifier: dfrndelivery: ' . $contact['name']);
					$deliver_status = dfrn_deliver($owner,$contact,$atom);

					logger('notifier: dfrn_delivery returns ' . $deliver_status);
	
					if($deliver_status == (-1)) {
						logger('notifier: delivery failed: queuing message');
						// queue message for redelivery
						q("INSERT INTO `queue` ( `cid`, `created`, `last`, `content`)
							VALUES ( %d, '%s', '%s', '%s') ",
							intval($contact['id']),
							dbesc(datetime_convert()),
							dbesc(datetime_convert()),
							dbesc($atom)
						);
					}
					break;
				case NETWORK_OSTATUS:

					// Do not send to otatus if we are not configured to send to public networks
					if($owner['prvnets'])
						break;
					if(get_config('system','ostatus_disabled') || get_config('system','dfrn_only'))
						break;

					if($followup && $contact['notify']) {
						logger('notifier: slapdelivery: ' . $contact['name']);
						$deliver_status = slapper($owner,$contact['notify'],$slap);

						if($deliver_status == (-1)) {
							// queue message for redelivery
							q("INSERT INTO `queue` ( `cid`, `created`, `last`, `content`)
								VALUES ( %d, '%s', '%s', '%s') ",
								intval($contact['id']),
								dbesc(datetime_convert()),
								dbesc(datetime_convert()),
								dbesc($slap)
							);

						}
					}
					else {

						// only send salmon if public - e.g. if it's ok to notify
						// a public hub, it's ok to send a salmon

						if((count($slaps)) && ($notify_hub) && (! $expire)) {
							logger('notifier: slapdelivery: ' . $contact['name']);
							foreach($slaps as $slappy) {
								if($contact['notify']) {
									$deliver_status = slapper($owner,$contact['notify'],$slappy);
									if($deliver_status == (-1)) {
										// queue message for redelivery
										q("INSERT INTO `queue` ( `cid`, `created`, `last`, `content`)
											VALUES ( %d, '%s', '%s', '%s') ",
											intval($contact['id']),
											dbesc(datetime_convert()),
											dbesc(datetime_convert()),
											dbesc($slappy)
										);								
									}
								}
							}
						}
					}
					break;

				case NETWORK_MAIL:
						
					if(get_config('system','dfrn_only'))
						break;

					// WARNING: does not currently convert to RFC2047 header encodings, etc.

					$addr = $contact['addr'];
					if(! strlen($addr))
						break;

					if($cmd === 'wall-new' || $cmd === 'comment-new') {

						$it = null;
						if($cmd === 'wall-new') 
							$it = $items[0];
						else {
							$r = q("SELECT * FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1", 
								intval($argv[2]),
								intval($uid)
							);
							if(count($r))
								$it = $r[0];
						}
						if(! $it)
							break;
						


						$local_user = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
							intval($uid)
						);
						if(! count($local_user))
							break;
						
						$reply_to = '';
						$r1 = q("SELECT * FROM `mailacct` WHERE `uid` = %d LIMIT 1",
							intval($uid)
						);
						if($r1 && $r1[0]['reply_to'])
							$reply_to = $r1[0]['reply_to'];
	
						$subject  = (($it['title']) ? $it['title'] : t("\x28no subject\x29")) ;
						$headers  = 'From: ' . $local_user[0]['username'] . ' <' . $local_user[0]['email'] . '>' . "\n";

						if($reply_to)
							$headers .= 'Reply-to: ' . $reply_to . "\n";

						$headers .= 'Message-id: <' . $it['uri'] . '>' . "\n";

						if($it['uri'] !== $it['parent-uri']) {
							$header .= 'References: <' . $it['parent-uri'] . '>' . "\n";
							if(! strlen($it['title'])) {
								$r = q("SELECT `title` FROM `item` WHERE `parent-uri` = '%s' LIMIT 1",
									dbesc($it['parent-uri'])
								);
								if(count($r)) {
									$subtitle = $r[0]['title'];
									if($subtitle) {
										if(strncasecmp($subtitle,'RE:',3))
											$subject = $subtitle;
										else
											$subject = 'Re: ' . $subtitle;
									}
								}
							}
						}

						$headers .= 'MIME-Version: 1.0' . "\n";
						$headers .= 'Content-Type: text/html; charset=UTF-8' . "\n";
						$headers .= 'Content-Transfer-Encoding: 8bit' . "\n\n";
						$html    = prepare_body($it);
						$message = '<html><body>' . $html . '</body></html>';
						logger('notifier: email delivery to ' . $addr);
						mail($addr, $subject, $message, $headers);
					}
					break;
				case NETWORK_DIASPORA:
					if(get_config('system','dfrn_only') || (! get_config('diaspora_enabled')))
						break;
					if($top_level) {
						diaspora_send_status($parent_item,$owner,$contact);
						break;
					}

					break;

				case NETWORK_FEED:
				case NETWORK_FACEBOOK:
					if(get_config('system','dfrn_only'))
						break;
				default:
					break;
			}
		}
	}
		
	// send additional slaps to mentioned remote tags (@foo@example.com)

	if($slap && count($url_recipients) && ($followup || $top_level) && $notify_hub && (! $expire)) {
		if(! get_config('system','dfrn_only')) {
			foreach($url_recipients as $url) {
				if($url) {
					logger('notifier: urldelivery: ' . $url);
					$deliver_status = slapper($owner,$url,$slap);
					// TODO: redeliver/queue these items on failure, though there is no contact record
				}
			}
		}
	}

	if((strlen($hub)) && ($notify_hub)) {
		$hubs = explode(',', $hub);
		if(count($hubs)) {
			foreach($hubs as $h) {
				$h = trim($h);
				if(! strlen($h))
					continue;
				$params = 'hub.mode=publish&hub.url=' . urlencode($a->get_baseurl() . '/dfrn_poll/' . $owner['nickname'] );
				post_url($h,$params);
				logger('pubsub: publish: ' . $h . ' ' . $params . ' returned ' . $a->get_curl_code());
				if(count($hubs) > 1)
					sleep(7);				// try and avoid multiple hubs responding at precisely the same time
			}
		}
	}

	if($notify_hub) {

		/**
		 *
		 * If you have less than 150 dfrn friends and it's a public message,
		 * we'll just go ahead and push them out securely with dfrn/rino.
		 * If you've got more than that, you'll have to rely on PuSH delivery.
		 *
		 */

		$max_allowed = ((get_config('system','maxpubdeliver') === false) ? 999 : intval(get_config('system','maxpubdeliver')));
				
		/**
		 *
		 * Only get the bare essentials and go back for the full record. 
		 * If you've got a lot of friends and we grab all the details at once it could exhaust memory. 
		 *
		 */

		$r = q("SELECT `id`, `name` FROM `contact` 
			WHERE `network` = NETWORK_DFRN AND `uid` = %d AND `blocked` = 0 AND `pending` = 0
			AND `rel` != %d ",
			intval($owner['uid']),
			intval(CONTACT_IS_SHARING)
		);

		if((count($r)) && (($max_allowed == 0) || (count($r) < $max_allowed))) {

			logger('pubdeliver: ' . print_r($r,true));

			foreach($r as $rr) {

				/* Don't deliver to folks who have already been delivered to */

				if(! in_array($rr['id'], $conversants)) {
					$n = q("SELECT * FROM `contact` WHERE `id` = %d LIMIT 1",
							intval($rr['id'])
					);

					if(count($n)) {
					
						logger('notifier: dfrnpubdelivery: ' . $n[0]['name']);
						$deliver_status = dfrn_deliver($owner,$n[0],$atom);
					}
				}
				else
					logger('notifier: dfrnpubdelivery: ignoring ' . $rr['name']);
			}
		}
	}

	return;
}

if (array_search(__file__,get_included_files())===0){
  notifier_run($argv,$argc);
  killme();
}
