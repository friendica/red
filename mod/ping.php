<?php


require_once('include/bbcode.php');

function ping_init(&$a) {

	$result = array();
	$notifs = array();

	header("content-type: application/json");

	if((! local_user()) || ((intval($_GET['uid'])) && (intval($_GET['uid']) != local_user()))) { 
		$result[] = array('success' => false, 'message' => 'Authentication error');
		echo json_encode($result);
		killme();
	}

	if($a->argc > 1 && $a->argv[1] === 'notify') {
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
					'classs' => (($zz['seen']) ? 'notify-seen' : 'notify-unseen'), 
					'message' => strip_tags(bbcode($zz['msg']))
				);
			}
		}

		echo json_encode(array('notify' => $notifs));
		killme();

	}
	
	$result['notify'] = 0;
	$result['home'] = 0;
	$result['network'] = 0;
	$result['intros'] = 0;
	$result['mail'] = 0;
	$result['register'] = 0;
	$result['notice'] = array();
	$result['info'] = array();



	$t = q("select count(*) as total from notify where uid = %d and seen = 0",
		intval(local_user())
	);
	if($t)
		$result['notify'] = intval($t[0]['total']);

	$r = q("SELECT `item`.`id`,`item`.`parent`, `item`.`verb`, `item`.`wall`, `item`.`author-name`, 
		`item`.`author-link`, `item`.`author-avatar`, `item`.`created`, `item`.`object`, 
		`pitem`.`author-name` as `pname`, `pitem`.`author-link` as `plink` 
		FROM `item` INNER JOIN `item` as `pitem` ON  `pitem`.`id`=`item`.`parent`
		WHERE `item`.`unseen` = 1 AND `item`.`visible` = 1 AND
		 `item`.`deleted` = 0 AND `item`.`uid` = %d 
		ORDER BY `item`.`created` DESC",
		intval(local_user())
	);

	if(count($r)) {		
		foreach ($r as $it) {
			if($it['wall'])
				$result['home'] ++;
			else
				$result['network'] ++;
		}
	}

	$intros1 = q("SELECT  `intro`.`id`, `intro`.`datetime`, 
		`fcontact`.`name`, `fcontact`.`url`, `fcontact`.`photo` 
		FROM `intro` LEFT JOIN `fcontact` ON `intro`.`fid` = `fcontact`.`id`
		WHERE `intro`.`uid` = %d  AND `intro`.`blocked` = 0 AND `intro`.`ignore` = 0 AND `intro`.`fid`!=0",
		intval(local_user())
	);
	$intros2 = q("SELECT `intro`.`id`, `intro`.`datetime`, 
		`contact`.`name`, `contact`.`url`, `contact`.`photo` 
		FROM `intro` LEFT JOIN `contact` ON `intro`.`contact-id` = `contact`.`id`
		WHERE `intro`.`uid` = %d  AND `intro`.`blocked` = 0 AND `intro`.`ignore` = 0 AND `intro`.`contact-id`!=0",
		intval(local_user())
	);
		
	$intro = count($intros1) + count($intros2);
	$result['intros'] = intval($intros);

	$myurl = $a->get_baseurl() . '/profile/' . $a->user['nickname'] ;
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
		
	if(x($_SESSION,'sysmsg')){
		foreach ($_SESSION['sysmsg'] as $m){
			$result['notice'][] = $m;
		}
		unset($_SESSION['sysmsg']);
	}
	if(x($_SESSION,'sysmsg_info')){
		foreach ($_SESSION['sysmsg_info'] as $m){
			$result['info'][] = $m;
		}
		unset($_SESSION['sysmsg_info']);
	}
	
	echo json_encode($result);
	killme();

}

