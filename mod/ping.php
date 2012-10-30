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

	if(argc() > 1 && (argv(1) === 'network' || argv(1) === 'home')) {

		$result = array();

		$r = q("SELECT id, item_restrict, item_flags FROM item
			WHERE item_restrict = %d and item_flags & %d and `item`.`uid` = %d",
			intval(ITEM_VISIBLE),
			intval(ITEM_UNSEEN),
			intval(local_user())
		);

		if($r) {
			foreach($r as $item) {
				if((argv(1) === 'home') && (! ($item['item_flags'] & ITEM_HOME)))
					continue;
				$result[] = format_notification($item);
			}
		}			

		echo json_encode(array( argv(1) => $result));
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
		WHERE item_restrict = %d and ( item_flags & %d ) and uid = %d",
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

	$intros1 = q("SELECT  `intro`.`id`, `intro`.`datetime`, 
		`fcontact`.`name`, `fcontact`.`url`, `fcontact`.`photo` 
		FROM `intro` LEFT JOIN `fcontact` ON `intro`.`fid` = `fcontact`.`id`
		WHERE `intro`.`uid` = %d  AND `intro`.`blocked` = 0 AND `intro`.`ignore` = 0 AND `intro`.`fid`!=0",
		intval(local_user())
	);

	$t3 = dba_timer();

	$intros2 = q("SELECT `intro`.`id`, `intro`.`datetime`, 
		`contact`.`name`, `contact`.`url`, `contact`.`photo` 
		FROM `intro` LEFT JOIN `contact` ON `intro`.`contact-id` = `contact`.`id`
		WHERE `intro`.`uid` = %d  AND `intro`.`blocked` = 0 AND `intro`.`ignore` = 0 AND `intro`.`contact-id`!=0",
		intval(local_user())
	);

	$intros = count($intros1) + count($intros2);
	$result['intros'] = intval($intros);

	$t4 = dba_timer();

	$myurl = $a->get_baseurl() . '/channel/' . $a->user['nickname'] ;
	$mails = q("SELECT *,  COUNT(*) AS `total` FROM `mail`
		WHERE `uid` = %d AND `seen` = 0 AND `from-url` != '%s' ",
		intval(local_user()),
		dbesc($myurl)
	);
	if($mails)
		$result['mail'] = intval($mails[0]['total']);
		
	if ($a->config['register_policy'] == REGISTER_APPROVE && is_site_admin()){
		$regs = q("SELECT `contact`.`name`, `contact`.`url`, `contact`.`micro`, `register`.`created`, COUNT(*) as `total` FROM `contact` RIGHT JOIN `register` ON `register`.`uid`=`contact`.`uid` WHERE `contact`.`self`=1");
		if($regs)
			$result['register'] = intval($regs[0]['total']);
	} 

	$t5 = dba_timer();

	$events = q("SELECT count(`event`.`id`) as total, type, start, adjust FROM `event`
		WHERE `event`.`uid` = %d AND `start` < '%s' AND `finish` > '%s' and `ignore` = 0
		ORDER BY `start` ASC ",
			intval(local_user()),
			dbesc(datetime_convert('UTC','UTC','now + 7 days')),
			dbesc(datetime_convert('UTC','UTC','now'))
	);

	if($events && count($events)) {
		$result['all_events'] = intval($events[0]['total']);

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

