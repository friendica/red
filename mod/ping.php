<?php
/**
 * @file mod/ping.php
 *
 */

require_once('include/bbcode.php');
require_once('include/notify.php');

/**
 * @brief do several updates when pinged.
 *
 * This function does several tasks. Whenever called it checks for new messages,
 * introductions, notifications, etc. and returns a json with the results.
 *
 * @param App &$a
 * @result JSON
 */

function ping_init(&$a) {

	$result = array();
	$notifs = array();

	$result['notify'] = 0;
	$result['home'] = 0;
	$result['network'] = 0;
	$result['intros'] = 0;
	$result['mail'] = 0;
	$result['register'] = 0;
	$result['events'] = 0;
	$result['events_today'] = 0;
	$result['birthdays'] = 0;
	$result['birthdays_today'] = 0;
	$result['all_events'] = 0;
	$result['all_events_today'] = 0;
	$result['notice'] = array();
	$result['info'] = array();

	$t0 = dba_timer();

	header("content-type: application/json");

	$vnotify = false;

	if(local_channel())  {
		$vnotify = get_pconfig(local_channel(),'system','vnotify');
		$evdays = intval(get_pconfig(local_channel(),'system','evdays'));
		$ob_hash = get_observer_hash();
	}

	// if unset show all visual notification types
	if($vnotify === false)
		$vnotify = (-1);
	if($evdays < 1)
		$evdays = 3;

	/**
	 * If you have several windows open to this site and switch to a different channel
	 * in one of them, the others may get into a confused state showing you a page or options 
	 * on that page which were only valid under the old identity. You session has changed.
	 * Therefore we send a notification of this fact back to the browser where it is picked up
	 * in javascript and which reloads the page it is on so that it is valid under the context
	 * of the now current channel. 
	 */

	$result['invalid'] = ((intval($_GET['uid'])) && (intval($_GET['uid']) != local_channel()) ? 1 : 0);

	/**
	 * Send all system messages (alerts) to the browser.
	 * Some are marked as informational and some represent
	 * errors or serious notifications. These typically
	 * will popup on the current page (no matter what page it is)
	 */

	if(x($_SESSION, 'sysmsg')){
		foreach ($_SESSION['sysmsg'] as $m){
			$result['notice'][] = array('message' => $m);
		}
		unset($_SESSION['sysmsg']);
	}
	if(x($_SESSION, 'sysmsg_info')){
		foreach ($_SESSION['sysmsg_info'] as $m){
			$result['info'][] = array('message' => $m);
		}
		unset($_SESSION['sysmsg_info']);
	}
	if(! ($vnotify & VNOTIFY_INFO))
		$result['info'] = array();
	if(! ($vnotify & VNOTIFY_ALERT))
		$result['notice'] = array();


	if($a->install) {
		echo json_encode($result);
		killme();
	}

	/**
	 * Update chat presence indication (if applicable)
	 */

	if(get_observer_hash() && (! $result['invalid'])) {
		$r = q("select cp_id, cp_room from chatpresence where cp_xchan = '%s' and cp_client = '%s' and cp_room = 0 limit 1",
			dbesc(get_observer_hash()),
			dbesc($_SERVER['REMOTE_ADDR'])
		);
		$basic_presence = false;
		if($r) {
			$basic_presence = true;	
			q("update chatpresence set cp_last = '%s' where cp_id = %d",
				dbesc(datetime_convert()),
				intval($r[0]['cp_id'])
			);
		}
		if(! $basic_presence) {
			q("insert into chatpresence ( cp_xchan, cp_last, cp_status, cp_client)
				values( '%s', '%s', '%s', '%s' ) ",
				dbesc(get_observer_hash()),
				dbesc(datetime_convert()),
				dbesc('online'),
				dbesc($_SERVER['REMOTE_ADDR'])
			);
		}
	}

	/**
	 * Chatpresence continued... if somebody hasn't pinged recently, they've most likely left the page
	 * and shouldn't count as online anymore. We allow an expection for bots.
	 */

	q("delete from chatpresence where cp_last < %s - INTERVAL %s and cp_client != 'auto' ",
		db_utcnow(), db_quoteinterval('3 MINUTE')
	); 

	if((! local_channel()) || ($result['invalid'])) {
		echo json_encode($result);
		killme();
	}

	/**
	 * Everything following is only permitted under the context of a locally authenticated site member.
	 */


	/**
	 * Handle "mark all xyz notifications read" requests.
	 */

	// mark all items read
	if(x($_REQUEST, 'markRead') && local_channel()) {
		switch($_REQUEST['markRead']) {
			case 'network':
				$r = q("update item set item_flags = ( item_flags & ~%d ) where (item_flags & %d) > 0 and uid = %d", 
					intval(ITEM_UNSEEN),
					intval(ITEM_UNSEEN),
					intval(local_channel())
				);
				break;
			case 'home':
				$r = q("update item set item_flags = ( item_flags & ~%d ) where (item_flags & %d) > 0 and (item_flags & %d) > 0  and uid = %d", 
					intval(ITEM_UNSEEN),
					intval(ITEM_UNSEEN),
					intval(ITEM_WALL),
					intval(local_channel())
				);
				break;
			case 'messages':
				$r = q("update mail set mail_flags = ( mail_flags | %d ) where channel_id = %d and not (mail_flags & %d) > 0",
					intval(MAIL_SEEN),
					intval(local_channel()),
					intval(MAIL_SEEN)
				);
				break;
			case 'all_events':
				$r = q("update event set `ignore` = 1 where `ignore` = 0 and uid = %d AND start < '%s' AND start > '%s' ",
					intval(local_channel()),
					dbesc(datetime_convert('UTC', date_default_timezone_get(), 'now + ' . intval($evdays) . ' days')),
					dbesc(datetime_convert('UTC', date_default_timezone_get(), 'now - 1 days'))
				);
				break;
			case 'notify':
				$r = q("update notify set seen = 1 where uid = %d",
					intval(local_channel())
				);
				break;
			default:
				break;
		}
	}

	if(x($_REQUEST, 'markItemRead') && local_channel()) {
		$r = q("update item set item_flags = ( item_flags & ~%d ) where parent = %d and uid = %d", 
			intval(ITEM_UNSEEN),
			intval($_REQUEST['markItemRead']),
			intval(local_channel())
		);
	}



	/**
	 * URL ping/something will return detail for "something", e.g. a json list with which to populate a notification
	 * dropdown menu.
	 */

	if(argc() > 1 && argv(1) === 'notify') {
		$t = q("select count(*) as total from notify where uid = %d and seen = 0",
			intval(local_channel())
		);
		if($t && intval($t[0]['total']) > 49) {
			$z = q("select * from notify where uid = %d
				and seen = 0 order by date desc limit 50",
				intval(local_channel())
			);
		}
		else {
			$z1 = q("select * from notify where uid = %d
				and seen = 0 order by date desc limit 50",
				intval(local_channel())
			);
			$z2 = q("select * from notify where uid = %d
				and seen = 1 order by date desc limit %d",
				intval(local_channel()),
				intval(50 - intval($t[0]['total']))
			);
			$z = array_merge($z1,$z2);
		}

		if(count($z)) {
			foreach($z as $zz) {
				$notifs[] = array(
					'notify_link' => $a->get_baseurl() . '/notify/view/' . $zz['id'], 
					'name' => $zz['name'],
					'url' => $zz['url'],
					'photo' => $zz['photo'],
					'when' => relative_date($zz['date']), 
					'hclass' => (($zz['seen']) ? 'notify-seen' : 'notify-unseen'), 
					'message' => strip_tags(bbcode($zz['msg']))
				);
			}
		}

		echo json_encode(array('notify' => $notifs));
		killme();
	}

	if(argc() > 1 && argv(1) === 'messages') {
		$channel = $a->get_channel();
		$t = q("select mail.*, xchan.* from mail left join xchan on xchan_hash = from_xchan 
			where channel_id = %d and not ( mail_flags & %d ) > 0 and not (mail_flags & %d ) > 0 
			and from_xchan != '%s' order by created desc limit 50",
			intval(local_channel()),
			intval(MAIL_SEEN),
			intval(MAIL_DELETED),
			dbesc($channel['channel_hash'])
		);

		if($t) {
			foreach($t as $zz) {
				$notifs[] = array(
					'notify_link' => $a->get_baseurl() . '/mail/' . $zz['id'], 
					'name' => $zz['xchan_name'],
					'url' => $zz['xchan_url'],
					'photo' => $zz['xchan_photo_s'],
					'when' => relative_date($zz['created']), 
					'hclass' => (($zz['mail_flags'] & MAIL_SEEN) ? 'notify-seen' : 'notify-unseen'), 
					'message' => t('sent you a private message'),
				);
			}
		}

		echo json_encode(array('notify' => $notifs));
		killme();
	}

	if(argc() > 1 && (argv(1) === 'network' || argv(1) === 'home')) {
		$result = array();

		$r = q("SELECT * FROM item
			WHERE item_restrict = %d and ( item_flags & %d ) > 0 and uid = %d
			and author_xchan != '%s' ORDER BY created DESC",
			intval(ITEM_VISIBLE),
			intval(ITEM_UNSEEN),
			intval(local_channel()),
			dbesc($ob_hash)
		);

		if($r) {
			xchan_query($r);
			foreach($r as $item) {
				if((argv(1) === 'home') && (! ($item['item_flags'] & ITEM_WALL)))
					continue;
				$result[] = format_notification($item);
			}
		}
		logger('ping (network||home): ' . print_r($result, true), LOGGER_DATA);
		echo json_encode(array('notify' => $result));
		killme();
	}

	if(argc() > 1 && (argv(1) === 'intros')) {
		$result = array();

		$r = q("SELECT * FROM abook left join xchan on abook.abook_xchan = xchan.xchan_hash where abook_channel = %d and (abook_flags & %d) > 0 and not ((abook_flags & %d) > 0 or (xchan_flags & %d) > 0) ORDER BY abook_created DESC",
			intval(local_channel()),
			intval(ABOOK_FLAG_PENDING),
			intval(ABOOK_FLAG_SELF|ABOOK_FLAG_IGNORED),
			intval(XCHAN_FLAGS_DELETED|XCHAN_FLAGS_ORPHAN)
		);

		if($r) {
			foreach($r as $rr) {
				$result[] = array(
					'notify_link' => $a->get_baseurl() . '/connedit/' . $rr['abook_id'],
					'name' => $rr['xchan_name'],
					'url' => $rr['xchan_url'],
					'photo' => $rr['xchan_photo_s'],
					'when' => relative_date($rr['abook_created']), 
					'hclass' => ('notify-unseen'), 
					'message' => t('added your channel')
				);
			}
		}
		logger('ping (intros): ' . print_r($result, true), LOGGER_DATA);
		echo json_encode(array('notify' => $result));
		killme();
	}

	if(argc() > 1 && (argv(1) === 'all_events')) {
		$bd_format = t('g A l F d') ; // 8 AM Friday January 18

		$result = array();

		$r = q("SELECT * FROM event left join xchan on event_xchan = xchan_hash
			WHERE `event`.`uid` = %d AND start < '%s' AND start > '%s' and `ignore` = 0
			ORDER BY `start` DESC ",
			intval(local_channel()),
			dbesc(datetime_convert('UTC', date_default_timezone_get(), 'now + ' . intval($evdays) . ' days')),
			dbesc(datetime_convert('UTC', date_default_timezone_get(), 'now - 1 days'))
		);

		if($r) {
			foreach($r as $rr) {
				if($rr['adjust'])
					$md = datetime_convert('UTC', date_default_timezone_get(), $rr['start'], 'Y/m');
				else
					$md = datetime_convert('UTC', 'UTC', $rr['start'], 'Y/m');

				$strt = datetime_convert('UTC', (($rr['adjust']) ? date_default_timezone_get() : 'UTC'), $rr['start']);
				$today = ((substr($strt, 0, 10) === datetime_convert('UTC', date_default_timezone_get(), 'now', 'Y-m-d')) ? true : false);
				
				$when = day_translate(datetime_convert('UTC', (($rr['adjust']) ? date_default_timezone_get() : 'UTC'), $rr['start'], $bd_format)) . (($today) ?  ' ' . t('[today]') : '');

				$result[] = array(
					'notify_link' => $a->get_baseurl() . '/events', // FIXME this takes you to an edit page and it may not be yours, we really want to just view the single event  --> '/events/event/' . $rr['event_hash'],
					'name'        => $rr['xchan_name'],
					'url'         => $rr['xchan_url'],
					'photo'       => $rr['xchan_photo_s'],
					'when'        => $when,
					'hclass'       => ('notify-unseen'), 
					'message'     => t('posted an event')
				);
			}
		}
		logger('ping (all_events): ' . print_r($result, true), LOGGER_DATA);
		echo json_encode(array('notify' => $result));
		killme();
	}



	/**
	 * Normal ping - just the counts, no detail
	 */

	if($vnotify & VNOTIFY_SYSTEM) {
		$t = q("select count(*) as total from notify where uid = %d and seen = 0",
			intval(local_channel())
		);
		if($t)
			$result['notify'] = intval($t[0]['total']);
	}

	$t1 = dba_timer();

	if($vnotify & (VNOTIFY_NETWORK|VNOTIFY_CHANNEL)) {
		$r = q("SELECT id, item_restrict, item_flags FROM item
			WHERE (item_restrict = %d) and ( item_flags & %d ) > 0 and uid = %d
			and author_xchan != '%s'",
			intval(ITEM_VISIBLE),
			intval(ITEM_UNSEEN),
			intval(local_channel()),
			dbesc($ob_hash)
		);

		if($r) {	
			$arr = array('items' => $r);
			call_hooks('network_ping', $arr);
	
			foreach ($r as $it) {
				if($it['item_flags'] & ITEM_WALL)
					$result['home'] ++;
				else
					$result['network'] ++;
			}
		}
	}
	if(! ($vnotify & VNOTIFY_NETWORK))
		$result['network'] = 0;
	if(! ($vnotify & VNOTIFY_CHANNEL))
		$result['home'] = 0;


	$t2 = dba_timer();

	if($vnotify & VNOTIFY_INTRO) {
		$intr = q("SELECT COUNT(abook.abook_id) AS total FROM abook left join xchan on abook.abook_xchan = xchan.xchan_hash where abook_channel = %d and (abook_flags & %d) > 0 and not ((abook_flags & %d) > 0 or (xchan_flags & %d) > 0)",
			intval(local_channel()),
			intval(ABOOK_FLAG_PENDING),
			intval(ABOOK_FLAG_SELF|ABOOK_FLAG_IGNORED),
			intval(XCHAN_FLAGS_DELETED|XCHAN_FLAGS_ORPHAN)
		);

		$t3 = dba_timer();

		if($intr)
			$result['intros'] = intval($intr[0]['total']);
	}

	$t4 = dba_timer();
	$channel = get_app()->get_channel();

	if($vnotify & VNOTIFY_MAIL) {
		$mails = q("SELECT count(id) as total from mail
			WHERE channel_id = %d AND not (mail_flags & %d) > 0 and from_xchan != '%s' ",
			intval(local_channel()),
			intval(MAIL_SEEN),		
			dbesc($channel['channel_hash'])
		);
		if($mails)
			$result['mail'] = intval($mails[0]['total']);
	}
	
	if($vnotify & VNOTIFY_REGISTER) {
		if ($a->config['system']['register_policy'] == REGISTER_APPROVE && is_site_admin()) {
			$regs = q("SELECT count(account_id) as total from account where (account_flags & %d) > 0",
				intval(ACCOUNT_PENDING)
			);
			if($regs)
				$result['register'] = intval($regs[0]['total']);
		}
	} 

	$t5 = dba_timer();

	if($vnotify & (VNOTIFY_EVENT|VNOTIFY_EVENTTODAY|VNOTIFY_BIRTHDAY)) {
		$events = q("SELECT type, start, adjust FROM `event`
			WHERE `event`.`uid` = %d AND start < '%s' AND start > '%s' and `ignore` = 0
			ORDER BY `start` ASC ",
				intval(local_channel()),
				dbesc(datetime_convert('UTC', date_default_timezone_get(), 'now + ' . intval($evdays) . ' days')),
				dbesc(datetime_convert('UTC', date_default_timezone_get(), 'now - 1 days'))
		);

		if($events) {
			$result['all_events'] = count($events);

			if($result['all_events']) {
				$str_now = datetime_convert('UTC', date_default_timezone_get(), 'now', 'Y-m-d');
				foreach($events as $x) {
					$bd = false;
					if($x['type'] === 'birthday') {
						$result['birthdays'] ++;
						$bd = true;
					}
					else {
						$result['events'] ++;
					}
					if(datetime_convert('UTC', ((intval($x['adjust'])) ? date_default_timezone_get() : 'UTC'), $x['start'], 'Y-m-d') === $str_now) {
						$result['all_events_today'] ++;
						if($bd)
							$result['birthdays_today'] ++;
						else
							$result['events_today'] ++;
					}
				}
			}
		}
	}
	if(! ($vnotify & VNOTIFY_EVENT))
		$result['all_events'] = $result['events'] = 0;
	if(! ($vnotify & VNOTIFY_EVENTTODAY))
		$result['all_events_today'] = $result['events_today'] = 0;
	if(! ($vnotify & VNOTIFY_BIRTHDAY))
		$result['birthdays'] = 0;


	$x = json_encode($result);
	
	$t6 = dba_timer();

//	logger('ping timer: ' . sprintf('%01.4f %01.4f %01.4f %01.4f %01.4f %01.4f',$t6 - $t5, $t5 - $t4, $t4 - $t3, $t3 - $t2, $t2 - $t1, $t1 - $t0));

	echo $x;
	killme();
}
