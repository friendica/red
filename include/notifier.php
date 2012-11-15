<?php

require_once("boot.php");
require_once('include/queue_fn.php');
require_once('include/html2plain.php');

/*
 * This file was at one time responsible for doing all deliveries, but this caused
 * big problems on shared hosting systems, where the process might get killed by the 
 * hosting provider and nothing would get delivered. 
 * It now only delivers one message under certain cases, and invokes a queued
 * delivery mechanism (include/deliver.php) to deliver individual contacts at 
 * controlled intervals.
 * This has a much better chance of surviving random processes getting killed
 * by the hosting provider. 
 * A lot of this code is duplicated in include/deliver.php until we have time to go back
 * and re-structure the delivery procedure based on the obstacles that have been thrown at 
 * us by hosting providers. 
 */

/*
 * The notifier is typically called with:
 *
 *		proc_run('php', "include/notifier.php", COMMAND, ITEM_ID);
 *
 * where COMMAND is one of the following:
 *
 *		activity				(in diaspora.php, dfrn_confirm.php, profiles.php)
 *		comment-import			(in diaspora.php, items.php)
 *		comment-new				(in item.php)
 *		drop					(in diaspora.php, items.php, photos.php)
 *		edit_post				(in item.php)
 *		event					(in events.php)
 *		expire					(in items.php)
 *		like					(in like.php, poke.php)
 *		mail					(in message.php)
 *		suggest					(in fsuggest.php)
 *		tag						(in photos.php, poke.php, tagger.php)
 *		tgroup					(in items.php)
 *		wall-new				(in photos.php, item.php)
 *
 * and ITEM_ID is the id of the item in the database that needs to be sent to others.
 *
 * ZOT 
 *       permission_updated     abook_id
 *
 */

require_once('include/cli_startup.php');
require_once('include/zot.php');


function notifier_run($argv, $argc){

	cli_startup();

	$a = get_app();

	require_once("session.php");
	require_once("datetime.php");
	require_once('include/items.php');
	require_once('include/bbcode.php');

	if($argc < 3)
		return;


	logger('notifier: invoked: ' . print_r($argv,true), LOGGER_DEBUG);

	$cmd = $argv[1];

	$item_id = $argv[2];

	$extra = (($argc > 3) ? $argv[3] : null);

	if(! $item_id)
		return;

	if($cmd == 'permission_update') {
		// Get the recipient	
		$r = q("select abook.*, hubloc.* from abook 
			left join hubloc on hubloc_hash = abook_xchan
			where abook_id = %d limit 1",
			intval($item_id)
		);
		if($r) {
			// Get the sender
			$s = q("select * from channel where channel_id = %d limit 1",
				intval($r[0]['abook_channel'])
			);
			if($s) {

				// send a refresh message to each hub they have registered here	
				$h = q("select * from hubloc where hubloc_hash = '%s'",
					dbesc($r[0]['hubloc_hash'])
				);
				if($h) {
					foreach($h as $hh) {
						$result = zot_notify($s[0],$hh['hubloc_callback'],'refresh',array(array(
							'guid' => $hh['hubloc_guid'],
							'guid_sig' => $hh['hubloc_guid_sig'],
							'url' => $hh['hubloc_url'])
						));	
						// should probably queue these
					}
				}
			}
		}
	}	


	$expire = false;
	$mail = false;
	$fsuggest = false;
	$top_level = false;
	$recipients = array();
	$url_recipients = array();
	$normal_mode = true;

	if($cmd === 'mail') {
		$normal_mode = false;
		$mail = true;
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
		$normal_mode = false;
		$expire = true;
		$items = q("SELECT * FROM `item` WHERE `uid` = %d AND `wall` = 1 
			AND `deleted` = 1 AND `changed` > UTC_TIMESTAMP() - INTERVAL 10 MINUTE",
			intval($item_id)
		);
		$uid = $item_id;
		$item_id = 0;
		if(! count($items))
			return;
	}
	elseif($cmd === 'suggest') {
		$normal_mode = false;
		$fsuggest = true;

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

		// Normal items - find ancestors

		$r = q("SELECT * FROM `item` WHERE `id` = %d and item_restrict = 0 LIMIT 1",
			intval($item_id)
		);

		if((! $r) || (! intval($r[0]['parent']))) {
			return;
		}

		xchan_query($r);

		$target_item = $r[0];
		$parent_id = intval($r[0]['parent']);
		$uid = $r[0]['uid'];
		$updated = $r[0]['edited'];

		$items = q("SELECT * from item where parent = %d and item_restrict = 0 order by id asc",
			intval($parent_id)
		);

		if(! count($items)) {
			return;
		}

		xchan_query($items);

		// avoid race condition with deleting entries

		if($items[0]['deleted']) {
			foreach($items as $item)
				$item['deleted'] = 1;
		}

		if((count($items) == 1) && ($items[0]['id'] === $target_item['id']) && ($items[0]['item_flags'] & ITEM_THREAD_TOP)) {
			logger('notifier: top level post');
			$top_level = true;
		}
	}

	$r = q("SELECT abook.*, channel.* from abook left join channel on abook_xchan = channel_hash 
		where channel_id = %d and (abook_flags & %d ) limit 1",
		intval($uid),
		intval(ABOOK_FLAG_SELF)
	);

	if(! count($r))
		return;

	$owner = $r[0];

// FIXME
//	$walltowall = ((($top_level) && ($owner['id'] != $items[0]['contact-id'])) ? true : false);

	$hub = get_config('system','huburl');

	// If this is a public conversation, notify the feed hub
	$public_message = true;

	// fill this in with a single salmon slap if applicable


	if(! ($mail || $fsuggest)) {

		require_once('include/group.php');

		$parent = $items[0];

		// This is IMPORTANT!!!!

		// We will only send a "notify owner to relay" or followup message if the referenced post
		// originated on our system by virtue of having our hostname somewhere
		// in the URI, AND it was a comment (not top_level) AND the parent originated elsewhere.

		// if $parent['wall'] == 1 we will already have the parent message in our array
		// and we will relay the whole lot.
 
		// expire sends an entire group of expire messages and cannot be forwarded.
		// However the conversation owner will be a part of the conversation and will 
		// be notified during this run.
		// Other DFRN conversation members will be alerted during polled updates.


		$relay_to_owner = false;
	
		$relay_origin_check = ((($target_item['item_flags'] & ITEM_ORIGIN) && (!($parent['item_flags'] & ITEM_ORIGIN))) ? true : false);

		/**
		 *
		 * Be VERY CAREFUL if you make any changes to the following several lines. Seemingly innocuous changes 
		 * have been known to cause runaway conditions which affected several servers, along with 
		 * permissions issues. 
		 *
		 */
 

		if((! $top_level) && (! ($parent['item_flags'] & ITEM_WALL)) && ($relay_origin_check) && (! $expire))
			$relay_to_owner = true;

//FIXME
//		if(($cmd === 'uplink') && (intval($parent['forum_mode']) == 1) && (! $top_level)) {
//			$relay_to_owner = true;			
//		} 

		if(! $relay_origin_check)
			$relay_to_owner = false;


		if($relay_to_owner) {
			logger('notifier: followup', LOGGER_DEBUG);
			// local followup to remote post
			$followup = true;
			$public_message = false; // not public
			$conversant_str = dbesc($parent['owner_xchan']);
		}
		else {
			$followup = false;

			// don't send deletions onward for other people's stuff

			if(($target_item['item_restrict'] & ITEM_DELETED) && (!($target_item['item_flags'] & ITEM_WALL))) {
				logger('notifier: ignoring delete notification for non-wall item');
				return;
			}

			if((strlen($parent['allow_cid'])) 
				|| (strlen($parent['allow_gid'])) 
				|| (strlen($parent['deny_cid'])) 
				|| (strlen($parent['deny_gid']))) {
				$public_message = false; // private recipients, not public
			}

// FIXME - expand_acl now takes xchan_hashes

			$allow_people = expand_acl($parent['allow_cid']);
			$allow_groups = expand_groups(expand_acl($parent['allow_gid']));
			$deny_people  = expand_acl($parent['deny_cid']);
			$deny_groups  = expand_groups(expand_acl($parent['deny_gid']));

			// if our parent is a public forum (forum_mode == 1), uplink to the origional author causing
			// a delivery fork. private groups (forum_mode == 2) do not uplink
// FIXME for tag delivery
//			if((intval($parent['forum_mode']) == 1) && (! $top_level) && ($cmd !== 'uplink')) {
//				proc_run('php','include/notifier.php','uplink',$item_id);
//			}

			$conversants = array();

			foreach($items as $item) {

				$recipients[]  = $item['author_xchan'];
				$conversants[] = $item['author_xchan'];

// FIXME add tagged people
/*				// pull out additional tagged people to notify (if public message)
				if($public_message && strlen($item['inform'])) {
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
*/
			}

			if($url_recipients)
				logger('notifier: url_recipients' . print_r($url_recipients,true), LOGGER_DEBUG);


			$conversants = array_unique($conversants);
			stringify_array_elms($conversants);
			$conversant_str = dbesc(implode(', ',$conversants));



			$recipients = array_unique(array_merge($recipients,$allow_people,$allow_groups));
			$deny = array_unique(array_merge($deny_people,$deny_groups));
			$recipients = array_diff($recipients,$deny);
		}

// FIXME - restrict this to those with permission to receive content

		$r = q("SELECT xchan.*, abook.* FROM xchan left join abook on xchan_hash = abook_chan 
			WHERE xchan_hash IN ( $conversant_str ) AND not ((abook_flags & %d) || (abook_flags & %d)) ",
			intval(ABOOK_FLAG_BLOCKED),
			intval(ABOOK_FLAG_ARCHIVED)
		);

		if($r)
			$contacts = $r;
	}



	// Now we have our item or items and a list of recipients
	// Convert them to json messages and stick them in the queue.



	$feed_template = get_markup_template('atom_feed.tpl');
	$mail_template = get_markup_template('atom_mail.tpl');

	$atom = '';
	$slaps = array();

	$hubxml = feed_hublinks();

	$birthday = feed_birthday($owner['uid'],$owner['timezone']);

	if(strlen($birthday))
		$birthday = '<dfrn:birthday>' . xmlify($birthday) . '</dfrn:birthday>';

	$atom .= replace_macros($feed_template, array(
			'$version'      => xmlify(FRIENDICA_VERSION),
			'$feed_id'      => xmlify($a->get_baseurl() . '/channel/' . $owner['nickname'] ),
			'$feed_title'   => xmlify($owner['name']),
			'$feed_updated' => xmlify(datetime_convert('UTC', 'UTC', $updated . '+00:00' , ATOM_TIME)) ,
			'$hub'          => $hubxml,
			'$salmon'       => '',  // private feed, we don't use salmon here
			'$name'         => xmlify($owner['name']),
			'$profile_page' => xmlify($owner['url']),
			'$photo'        => xmlify($owner['photo']),
			'$thumb'        => xmlify($owner['thumb']),
			'$picdate'      => xmlify(datetime_convert('UTC','UTC',$owner['avatar_date'] . '+00:00' , ATOM_TIME)) ,
			'$uridate'      => xmlify(datetime_convert('UTC','UTC',$owner['uri_date']    . '+00:00' , ATOM_TIME)) ,
			'$namdate'      => xmlify(datetime_convert('UTC','UTC',$owner['name_date']   . '+00:00' , ATOM_TIME)) ,
			'$birthday'     => $birthday,
			'$community'    => (($owner['page-flags'] == PAGE_COMMUNITY) ? '<dfrn:community>1</dfrn:community>' : '')

	));

	if($mail) {
		$public_message = false;  // mail is  not public

		$body = fix_private_photos($item['body'],$owner['uid'],null,$message[0]['contact-id']);

		$atom .= replace_macros($mail_template, array(
			'$name'         => xmlify($owner['name']),
			'$profile_page' => xmlify($owner['url']),
			'$thumb'        => xmlify($owner['thumb']),
			'$item_id'      => xmlify($item['uri']),
			'$subject'      => xmlify($item['title']),
			'$created'      => xmlify(datetime_convert('UTC', 'UTC', $item['created'] . '+00:00' , ATOM_TIME)),
			'$content'      => xmlify($body),
			'$parent_id'    => xmlify($item['parent_uri'])
		));
	}
	elseif($fsuggest) {
		$public_message = false;  // suggestions are not public

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
					$slap  = atom_entry($item,'html',null,$owner,false);
					$atom .= atom_entry($item,'text',null,$owner,false);
				}
			}
		}
		else {
			foreach($items as $item) {

				if(! $item['parent'])
					continue;

				// private emails may be in included in public conversations. Filter them.

				if(($public_message) && $item['private'] == 1)
					continue;


				$contact = get_item_contact($item,$contacts);

				if(! $contact)
					continue;

				if($normal_mode) {

					// we only need the current item, but include the parent because without it
					// older sites without a corresponding dfrn_notify change may do the wrong thing.

				    if($item_id == $item['id'] || $item['id'] == $item['parent'])
						$atom .= atom_entry($item,'text',null,$owner,true);
				}
				else
					$atom .= atom_entry($item,'text',null,$owner,true);

				if(($top_level) && ($public_message) && ($item['author-link'] === $item['owner-link']) && (! $expire)) 
					$slaps[] = atom_entry($item,'html',null,$owner,true);
			}
		}
	}
	$atom .= '</feed>' . "\r\n";

	logger('notifier: ' . $atom, LOGGER_DATA);

	logger('notifier: slaps: ' . print_r($slaps,true), LOGGER_DATA);

	stringify_array_elms($recipients);

	if($followup)
		$recip_str = $parent['contact-id'];
	else
		$recip_str = implode(', ', $recipients);

	$r = q("SELECT * FROM `contact` WHERE `id` IN ( %s ) AND `blocked` = 0 AND `pending` = 0 ",
		dbesc($recip_str)
	);


	$interval = ((get_config('system','delivery_interval') === false) ? 2 : intval(get_config('system','delivery_interval')));

	// delivery loop

	if(count($r)) {

		foreach($r as $contact) {
			if((! $mail) && (! $fsuggest) && (! $followup) && (! $contact['self'])) {
				q("insert into deliverq ( `cmd`,`item`,`contact` ) values ('%s', %d, %d )",
					dbesc($cmd),
					intval($item_id),
					intval($contact['id'])
				);
			}
		}


		// This controls the number of deliveries to execute with each separate delivery process.
		// By default we'll perform one delivery per process. Assuming a hostile shared hosting
		// provider, this provides the greatest chance of deliveries if processes start getting 
		// killed. We can also space them out with the delivery_interval to also help avoid them 
		// getting whacked.

		// If $deliveries_per_process > 1, we will chain this number of multiple deliveries 
		// together into a single process. This will reduce the overall number of processes 
		// spawned for each delivery, but they will run longer. 

		$deliveries_per_process = intval(get_config('system','delivery_batch_count'));
		if($deliveries_per_process <= 0)
			$deliveries_per_process = 1;

		$this_batch = array();

		for($x = 0; $x < count($r); $x ++) {
			$contact = $r[$x];

			if($contact['self'])
				continue;

			// potentially more than one recipient. Start a new process and space them out a bit.
			// we will deliver single recipient types of message and email recipients here. 
		
			if((! $mail) && (! $fsuggest) && (! $followup)) {

				$this_batch[] = $contact['id'];

				if(count($this_batch) == $deliveries_per_process) {
					proc_run('php','include/delivery.php',$cmd,$item_id,$this_batch);
					$this_batch = array();
					if($interval)
						@time_sleep_until(microtime(true) + (float) $interval);
				}
				continue;
			}

			$deliver_status = 0;

			logger("main delivery by notifier: followup=$followup mail=$mail fsuggest=$fsuggest");

			switch($contact['network']) {
				case NETWORK_DFRN:

					// perform local delivery if we are on the same site

					$basepath =  implode('/', array_slice(explode('/',$contact['url']),0,3));

					if(link_compare($basepath,$a->get_baseurl())) {

						$nickname = basename($contact['url']);
						if($contact['issued_id'])
							$sql_extra = sprintf(" AND `dfrn_id` = '%s' ", dbesc($contact['issued_id']));
						else
							$sql_extra = sprintf(" AND `issued_id` = '%s' ", dbesc($contact['dfrn_id']));

						$x = q("SELECT	`contact`.*, `contact`.`uid` AS `importer_uid`, 
							`contact`.`pubkey` AS `cpubkey`, 
							`contact`.`prvkey` AS `cprvkey`, 
							`contact`.`thumb` AS `thumb`, 
							`contact`.`url` as `url`,
							`contact`.`name` as `senderName`,
							`user`.* 
							FROM `contact` 
							LEFT JOIN `user` ON `contact`.`uid` = `user`.`uid` 
							WHERE `contact`.`blocked` = 0 AND `contact`.`archive` = 0
							AND `contact`.`pending` = 0
							AND `contact`.`network` = '%s' AND `user`.`nickname` = '%s'
							$sql_extra
							AND `user`.`account_expired` = 0 LIMIT 1",
							dbesc(NETWORK_DFRN),
							dbesc($nickname)
						);

						if($x && count($x)) {
							$write_flag = (($x[0]['rel'] == CONTACT_IS_FOLLOWER || $x[0]['rel'] == CONTACT_IS_FRIEND) ? true : false);
							if((($owner['page-flags'] == PAGE_COMMUNITY) || ($write_flag)) && (! $x[0]['writable'])) {
								q("update contact set writable = 1 where id = %d limit 1",
									intval($x[0]['id'])
								);
								$x[0]['writable'] = 1;
							}

							// if contact's ssl policy changed, which we just determined
							// is on our own server, update our contact links
							
							$ssl_policy = get_config('system','ssl_policy');
							fix_contact_ssl_policy($x[0],$ssl_policy);

							// If we are setup as a soapbox we aren't accepting input from this person

							if($x[0]['page-flags'] == PAGE_SOAPBOX)
								break;

							require_once('library/simplepie/simplepie.inc');
							logger('mod-delivery: local delivery');
							local_delivery($x[0],$atom);
							break;					
						}
					}



					logger('notifier: dfrndelivery: ' . $contact['name']);
					$deliver_status = dfrn_deliver($owner,$contact,$atom);

					logger('notifier: dfrn_delivery returns ' . $deliver_status);
	
					if($deliver_status == (-1)) {
						logger('notifier: delivery failed: queuing message');
						// queue message for redelivery
						add_to_queue($contact['id'],NETWORK_DFRN,$atom);
					}
					break;

				default:
					break;
			}
		}
	}
		

	if($public_message) {

		$r = q("SELECT `id`, `name`,`network` FROM `contact` 
			WHERE `network` in ( '%s', '%s')  AND `uid` = %d AND `blocked` = 0 AND `pending` = 0 AND `archive` = 0
			AND `rel` != %d order by rand() ",
			dbesc(NETWORK_DFRN),
			dbesc(NETWORK_MAIL2),
			intval($owner['uid']),
			intval(CONTACT_IS_SHARING)
		);


		if(count($r)) {
			logger('pubdeliver: ' . print_r($r,true), LOGGER_DEBUG);

			// throw everything into the queue in case we get killed

			foreach($r as $rr) {
				if((! $mail) && (! $fsuggest) && (! $followup)) {
					q("insert into deliverq ( `cmd`,`item`,`contact` ) values ('%s', %d, %d )",
						dbesc($cmd),
						intval($item_id),
						intval($rr['id'])
					);
				}
			}

			foreach($r as $rr) {

				if(in_array($rr['id'],$conversants)) {
					logger('notifier: already delivered id=' . $rr['id']);
					continue;
				}

				if((! $mail) && (! $fsuggest) && (! $followup)) {
					logger('notifier: delivery agent: ' . $rr['name'] . ' ' . $rr['id']); 
					proc_run('php','include/delivery.php',$cmd,$item_id,$rr['id']);
					if($interval)
						@time_sleep_until(microtime(true) + (float) $interval);
				}
			}
		}


		if(strlen($hub)) {
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

	}

	if($normal_mode)
		call_hooks('notifier_normal',$target_item);

	call_hooks('notifier_end',$target_item);

	return;
}

if (array_search(__file__,get_included_files())===0){
  notifier_run($argv,$argc);
  killme();
}
