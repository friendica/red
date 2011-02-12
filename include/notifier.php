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
	else {

		// find ancestors
		$r = q("SELECT `parent`, `uid`, `edited` FROM `item` WHERE `id` = %d LIMIT 1",
			intval($item_id)
		);

		if((! count($r)) || (! intval($r[0]['parent']))) {
			return;
		}

		$parent_id = intval($r[0]['parent']);
		$uid = $r[0]['uid'];
		$updated = $r[0]['edited'];

		$items = q("SELECT * FROM `item` WHERE `parent` = %d ORDER BY `id` ASC",
			intval($parent_id)
		);

		if(! count($items)){
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

	$r = q("SELECT `contact`.*, `user`.`timezone`, `user`.`nickname`, `user`.`sprvkey`, `user`.`spubkey`, `user`.`page-flags` 
		FROM `contact` LEFT JOIN `user` ON `user`.`uid` = `contact`.`uid` 
		WHERE `contact`.`uid` = %d AND `contact`.`self` = 1 LIMIT 1",
		intval($uid)
	);

	if(count($r))
		$owner = $r[0];
	else {
		return;
	}
	$hub = get_config('system','huburl');

	// If this is a public conversation, notify the feed hub
	$notify_hub = true;

	// fill this in with a single salmon slap if applicable
	$slap = '';

	if($cmd != 'mail') {

		require_once('include/group.php');

		$parent = $items[0];

		if($parent['type'] === 'remote') {
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

	$feed_template = load_view_file('view/atom_feed.tpl');
	$mail_template = load_view_file('view/atom_mail.tpl');

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

		$atom .= replace_macros($mail_template, array(
			'$name'         => xmlify($owner['name']),
			'$profile_page' => xmlify($owner['url']),
			'$thumb'        => xmlify($owner['thumb']),
			'$item_id'      => xmlify($item['uri']),
			'$subject'      => xmlify($item['title']),
			'$created'      => xmlify(datetime_convert('UTC', 'UTC', $item['created'] . '+00:00' , ATOM_TIME)),
			'$content'      => xmlify($item['body']),
			'$parent_id'    => xmlify($item['parent-uri'])
		));
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

				$contact = get_item_contact($item,$contacts);
				if(! $contact)
					continue;

				$atom   .= atom_entry($item,'text',$contact,$owner,true);

				if(($top_level) && ($notify_hub) && ($item['author-link'] === $item['owner-link'])) 
					$slaps[] = atom_entry($item,'html',$contact,$owner,true);
			}
		}
	}
	$atom .= '</feed>' . "\r\n";

	logger('notifier: ' . $atom, LOGGER_DATA);

	logger('notifier: slaps: ' . print_r($slaps,true), LOGGER_DATA);

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
				case 'dfrn':
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
				case 'stat':
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

						if(count($slaps) && $notify_hub) {
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
				case 'mail':
				case 'dspr':
				case 'feed':
				default:
					break;
			}
		}
	}
		
	// send additional slaps to mentioned remote tags (@foo@example.com)

	if($slap && count($url_recipients) && $followup && $notify_hub) {
		foreach($url_recipients as $url) {
			if($url) {
				logger('notifier: urldelivery: ' . $url);
				$deliver_status = slapper($owner,$url,$slap);
				// TODO: redeliver/queue these items on failure, though there is no contact record
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

		$max_allowed = ((get_config('system','maxpubdeliver') === false) ? 150 : intval(get_config('system','maxpubdeliver')));
				
		/**
		 *
		 * Only get the bare essentials and go back for the full record. 
		 * If you've got a lot of friends and we grab all the details at once it could exhaust memory. 
		 *
		 */

		$r = q("SELECT `id`, `name` FROM `contact` 
			WHERE `network` = 'dfrn' AND `uid` = %d AND `blocked` = 0 AND `pending` = 0
			AND `rel` != %d ",
			intval($owner['uid']),
			intval(REL_FAN)
		);

		if((count($r)) && (($max_allowed == 0) || (count($r) < $max_allowed))) {

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
  echo "run!";
  notifier_run($argv,$argc);
  killme();
}
