<?php


require_once('include/bbcode.php');
require_once('include/notify.php');

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

	$result['invalid'] = ((intval($_GET['uid'])) && (intval($_GET['uid']) != local_user()) ? 1 : 0);

	if(x($_SESSION,'sysmsg')){
		foreach ($_SESSION['sysmsg'] as $m){
			$result['notice'][] = array('message' => $m);
		}
		unset($_SESSION['sysmsg']);
	}
	if(x($_SESSION,'sysmsg_info')){
		foreach ($_SESSION['sysmsg_info'] as $m){
			$result['info'][] = array('message' => $m);
		}
		unset($_SESSION['sysmsg_info']);
	}

	if((! local_user()) || ($result['invalid'])) {
		echo json_encode($result);
		killme();
	}

	if(x($_REQUEST,'markRead') && local_user()) {

		switch($_REQUEST['markRead']) {
			case 'network':
				$r = q("update item set item_flags = ( item_flags ^ %d ) where (item_flags & %d) and uid = %d", 
					intval(ITEM_UNSEEN),
					intval(ITEM_UNSEEN),
					intval(local_user())
				);
				break;

			case 'home':
				$r = q("update item set item_flags = ( item_flags ^ %d ) where (item_flags & %d) and (item_flags & %d) and uid = %d", 
					intval(ITEM_UNSEEN),
					intval(ITEM_UNSEEN),
					intval(ITEM_WALL),
					intval(local_user())
				);
				break;
			case 'messages':
				$r = q("update mail set mail_flags = ( mail_flags ^ %d ) where channel_id = %d and not (mail_flags & %d)",
					intval(MAIL_SEEN),
					intval(local_user()),
					intval(MAIL_SEEN)
				);
				break;
			case 'all_events':
				$r = q("update event set ignore = 1 where ignore = 0 and uid = %d", 
					intval(local_user())
				);
				break;

			case 'notify':
				$r = q("update notify set seen = 1 where uid = %d",
					intval(local_user())
				);
				break;

			default:
				break;
		}
	}


	if(argc() > 1 && argv(1) === 'notify') {
		$t = q("select count(*) as total from notify where uid = %d and seen = 0",
			intval(local_user())
		);
		if($t && intval($t[0]['total']) > 49) {
			$z = q("select * from notify where uid = %d
				and seen = 0 order by date desc limit 0, 50",
				intval(local_user())
			);
		}
		else {
			$z1 = q("select * from notify where uid = %d
				and seen = 0 order by date desc limit 0, 50",
				intval(local_user())
			);
			$z2 = q("select * from notify where uid = %d
				and seen = 1 order by date desc limit 0, %d",
				intval(local_user()),
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
					'class' => (($zz['seen']) ? 'notify-seen' : 'notify-unseen'), 
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
			where channel_id = %d and not ( mail_flags & %d ) and not (mail_flags & %d ) 
			and from_xchan != '%s' order by created desc limit 0,50",
			intval(local_user()),
			intval(MAIL_SEEN),
			intval(MAIL_DELETED),
			dbesc($channel['channel_hash'])
		);

		if($t) {
			foreach($t as $zz) {
//				$msg = sprintf( t('sent you a private message.'), $zz['xchan_name']);
				$notifs[] = array(
					'notify_link' => $a->get_baseurl() . '/message/' . $zz['id'], 
					'name' => $zz['xchan_name'],
					'url' => $zz['xchan_url'],
					'photo' => $zz['xchan_photo_s'],
					'when' => relative_date($zz['created']), 
					'class' => (($zz['mail_flags'] & MAIL_SEEN) ? 'notify-seen' : 'notify-unseen'), 
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
			WHERE item_restrict = %d and ( item_flags & %d ) and uid = %d",
			intval(ITEM_VISIBLE),
			intval(ITEM_UNSEEN),
			intval(local_user())
		);

		if($r) {
			xchan_query($r);
			foreach($r as $item) {
				if((argv(1) === 'home') && (! ($item['item_flags'] & ITEM_WALL)))
					continue;
				$result[] = format_notification($item);
			}
		}			
		logger('ping: ' . print_r($result,true));
		echo json_encode(array('notify' => $result));
		killme();

	}

	if(argc() > 1 && (argv(1) === 'intros')) {

		$result = array();

		$r = q("SELECT abook.*, xchan.* FROM abook left join xchan on abook_xchan = xchan_hash
			WHERE abook_channel = %d and (abook_flags & %d) and not (abook_flags & %d)",
			intval(local_user()),
			intval(ABOOK_FLAG_PENDING),
			intval(ABOOK_FLAG_SELF)
		);

		if($r) {
			foreach($r as $rr) {
				$result[] = array(
					'notify_link' => $a->get_baseurl() . '/intro/' . $rr['abook_id'],
					'name' => $rr['xchan_name'],
					'url' => $rr['xchan_url'],
					'photo' => $rr['xchan_photo_s'],
					'when' => relative_date($rr['abook_created']), 
					'class' => ('notify-unseen'), 
					'message' => t('added your channel')
				);
			}
		}			
		logger('ping: ' . print_r($result,true));
		echo json_encode(array('notify' => $result));
		killme();

	}

	if(argc() > 1 && (argv(1) === 'all_events')) {

		$bd_format = t('g A l F d') ; // 8 AM Friday January 18

		$result = array();

		$r = q("SELECT * FROM event left join xchan on event_xchan = xchan_hash
			WHERE `event`.`uid` = %d AND start < '%s' AND start > '%s' and `ignore` = 0
			ORDER BY `start` DESC ",
			intval(local_user()),
			dbesc(datetime_convert('UTC',date_default_timezone_get(),'now + 7 days')),
			dbesc(datetime_convert('UTC',date_default_timezone_get(),'now - 1 days'))
		);

		if($r) {
			foreach($r as $rr) {
				if($rr['adjust'])
					$md = datetime_convert('UTC',date_default_timezone_get(),$rr['start'],'Y/m');
				else
					$md = datetime_convert('UTC','UTC',$rr['start'],'Y/m');

				$strt = datetime_convert('UTC',$rr['convert'] ? date_default_timezone_get() : 'UTC',$rr['start']);
				$today = ((substr($strt,0,10) === datetime_convert('UTC',date_default_timezone_get(),'now','Y-m-d')) ? true : false);
				
				$when = day_translate(datetime_convert('UTC', $rr['adjust'] ? date_default_timezone_get() : 'UTC', $rr['start'], $bd_format)) . (($today) ?  ' ' . t('[today]') : '');


				$result[] = array(
					'notify_link' => $a->get_baseurl() . '/events/event/' . $rr['event_hash'],
					'name'        => $rr['xchan_name'],
					'url'         => $rr['xchan_url'],
					'photo'       => $rr['xchan_photo_s'],
					'when'        => $when,
					'class'       => ('notify-unseen'), 
					'message'     => t('posted an event')
				);
			}
		}			
		logger('ping: ' . print_r($result,true));
		echo json_encode(array('notify' => $result));
		killme();

	}


	// Normal ping - just the counts

	$t = q("select count(*) as total from notify where uid = %d and seen = 0",
		intval(local_user())
	);
	if($t)
		$result['notify'] = intval($t[0]['total']);


	$t1 = dba_timer();

	$r = q("SELECT id, item_restrict, item_flags FROM item
		WHERE (item_restrict = %d) and ( item_flags & %d ) and uid = %d",
		intval(ITEM_VISIBLE),
		intval(ITEM_UNSEEN),
		intval(local_user())
	);

	if(count($r)) {	

		$arr = array('items' => $r);
		call_hooks('network_ping', $arr);
	
		foreach ($r as $it) {
			if($it['item_flags'] & ITEM_WALL)
				$result['home'] ++;
			else
				$result['network'] ++;
		}
	}


	$t2 = dba_timer();

	$intr = q("select count(abook_id) as total from abook where (abook_flags & %d) and abook_channel = %d",
		intval(ABOOK_FLAG_PENDING),
		intval(local_user())
	);

	$t3 = dba_timer();

	if($intr)
		$result['intros'] = intval($intr[0]['total']);

	$t4 = dba_timer();
	$channel = get_app()->get_channel();

	$mails = q("SELECT count(id) as total from mail
		WHERE channel_id = %d AND not (mail_flags & %d) and from_xchan != '%s' ",
		intval(local_user()),
		intval(MAIL_SEEN),		
		dbesc($channel['channel_hash'])
	);
	if($mails)
		$result['mail'] = intval($mails[0]['total']);
		
	if ($a->config['system']['register_policy'] == REGISTER_APPROVE && is_site_admin()){
		$regs = q("SELECT `contact`.`name`, `contact`.`url`, `contact`.`micro`, `register`.`created`, COUNT(*) as `total` FROM `contact` RIGHT JOIN `register` ON `register`.`uid`=`contact`.`uid` WHERE `contact`.`self`=1");
		if($regs)
			$result['register'] = intval($regs[0]['total']);
	} 

	$t5 = dba_timer();

	$events = q("SELECT type, start, adjust FROM `event`
		WHERE `event`.`uid` = %d AND start < '%s' AND start > '%s' and `ignore` = 0
		ORDER BY `start` ASC ",
			intval(local_user()),
			dbesc(datetime_convert('UTC',date_default_timezone_get(),'now + 7 days')),
			dbesc(datetime_convert('UTC',date_default_timezone_get(),'now - 1 days'))
	);

	if($events) {
		$result['all_events'] = count($events);

		if($result['all_events']) {
			$str_now = datetime_convert('UTC',$a->timezone,'now','Y-m-d');
			foreach($events as $x) {
				$bd = false;
				if($x['type'] === 'birthday') {
					$result['birthdays'] ++;
					$bd = true;
				}
				else {
					$result['events'] ++;
				}
				if(datetime_convert('UTC',((intval($x['adjust'])) ? $a->timezone : 'UTC'), $x['start'],'Y-m-d') === $str_now) {
					$result['all_events_today'] ++;
					if($bd)
						$result['birthdays_today'] ++;
					else
						$result['events_today'] ++;
				}
			}
		}
	}

	$x = json_encode($result);
	
	$t6 = dba_timer();

//	logger('ping timer: ' . sprintf('%01.4f %01.4f %01.4f %01.4f %01.4f %01.4f',$t6 - $t5, $t5 - $t4, $t4 - $t3, $t3 - $t2, $t2 - $t1, $t1 - $t0));

	echo $x;
	killme();

}

