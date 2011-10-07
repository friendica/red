<?php

function notifications_post(&$a) {

	if(! local_user()) {
		goaway(z_root());
	}
	
	$request_id = (($a->argc > 1) ? $a->argv[1] : 0);
	
	if($request_id === "all")
		return;

	if($request_id) {

		$r = q("SELECT * FROM `intro` WHERE `id` = %d  AND `uid` = %d LIMIT 1",
			intval($request_id),
			intval(local_user())
		);
	
		if(count($r)) {
			$intro_id = $r[0]['id'];
			$contact_id = $r[0]['contact-id'];
		}
		else {
			notice( t('Invalid request identifier.') . EOL);
			return;
		}

		// If it is a friend suggestion, the contact is not a new friend but an existing friend
		// that should not be deleted.

		$fid = $r[0]['fid'];

		if($_POST['submit'] == t('Discard')) {
			$r = q("DELETE FROM `intro` WHERE `id` = %d LIMIT 1", 
				intval($intro_id)
			);	
			if(! $fid) {
				$r = q("DELETE FROM `contact` WHERE `id` = %d AND `uid` = %d AND `self` = 0 LIMIT 1", 
					intval($contact_id),
					intval(local_user())
				);
			}
			return;
		}
		if($_POST['submit'] == t('Ignore')) {
			$r = q("UPDATE `intro` SET `ignore` = 1 WHERE `id` = %d LIMIT 1",
				intval($intro_id));
			return;
		}
	}
}





function notifications_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$o = '';
	
	if( (($a->argc > 1) && ($a->argv[1] == 'intros')) || (($a->argc == 1))) {
		
		if(($a->argc > 2) && ($a->argv[2] == 'all'))
			$sql_extra = '';
		else
			$sql_extra = " AND `ignore` = 0 ";
		
		$notif_tpl = get_markup_template('notifications.tpl');
		
		$notif_content .= '<a href="' . ((strlen($sql_extra)) ? 'notifications/intros/all' : 'notifications/intros' ) . '" id="notifications-show-hide-link" >'
			. ((strlen($sql_extra)) ? t('Show Ignored Requests') : t('Hide Ignored Requests')) . '</a></div>' . "\r\n";

		$r = q("SELECT COUNT(*)	AS `total` FROM `intro` 
			WHERE `intro`.`uid` = %d $sql_extra AND `intro`.`blocked` = 0 ",
				intval($_SESSION['uid'])
		);
		if($r && count($r)) {
			$a->set_pager_total($r[0]['total']);
			$a->set_pager_itemspage(20);
		}

		$r = q("SELECT `intro`.`id` AS `intro_id`, `intro`.*, `contact`.*, `fcontact`.`name` AS `fname`,`fcontact`.`url` AS `furl`,`fcontact`.`photo` AS `fphoto`,`fcontact`.`request` AS `frequest`
			FROM `intro` LEFT JOIN `contact` ON `contact`.`id` = `intro`.`contact-id` LEFT JOIN `fcontact` ON `intro`.`fid` = `fcontact`.`id`
			WHERE `intro`.`uid` = %d $sql_extra AND `intro`.`blocked` = 0 ",
				intval($_SESSION['uid']));

		if(($r !== false) && (count($r))) {

			$sugg = get_markup_template('suggestions.tpl');
			$tpl = get_markup_template("intros.tpl");

			foreach($r as $rr) {
				if($rr['fid']) {

					$return_addr = bin2hex($a->user['nickname'] . '@' . $a->get_hostname() . (($a->path) ? '/' . $a->path : ''));
					$notif_content .= replace_macros($sugg,array(
						'$str_notifytype' => t('Notification type: '),
						'$notify_type' => t('Friend Suggestion'),
						'$intro_id' => $rr['intro_id'],
						'$madeby' => sprintf( t('suggested by %s'),$rr['name']),
						'$contact_id' => $rr['contact-id'],
						'$photo' => ((x($rr,'fphoto')) ? $rr['fphoto'] : "images/default-profile.jpg"),
						'$fullname' => $rr['fname'],
						'$url' => $rr['furl'],
						'$knowyou' => $knowyou,
						'$approve' => t('Approve'),
						'$note' => $rr['note'],
						'$request' => $rr['frequest'] . '?addr=' . $return_addr,
						'$ignore' => t('Ignore'),
						'$discard' => t('Discard')

					));

					continue;

				}
				$friend_selected = (($rr['network'] !== NETWORK_OSTATUS) ? ' checked="checked" ' : ' disabled ');
				$fan_selected = (($rr['network'] === NETWORK_OSTATUS) ? ' checked="checked" disabled ' : '');
				$dfrn_tpl = get_markup_template('netfriend.tpl');

				$knowyou   = '';
				$dfrn_text = '';

				if($rr['network'] === NETWORK_DFRN || $rr['network'] === NETWORK_DIASPORA) {
					if($rr['network'] === NETWORK_DFRN)
						$knowyou = t('Claims to be known to you: ') . (($rr['knowyou']) ? t('yes') : t('no'));
					else
						$knowyou = '';
					$dfrn_text = replace_macros($dfrn_tpl,array(
						'$intro_id' => $rr['intro_id'],
						'$friend_selected' => $friend_selected,
						'$fan_selected' => $fan_selected,
						'$approve_as' => t('Approve as: '),
						'$as_friend' => t('Friend'),
						'$as_fan' => (($rr['network'] == NETWORK_DIASPORA) ? t('Sharer') : t('Fan/Admirer'))
					));
				}			

				$notif_content .= replace_macros($tpl,array(
					'$str_notifytype' => t('Notification type: '),
					'$notify_type' => (($rr['network'] !== NETWORK_OSTATUS) ? t('Friend/Connect Request') : t('New Follower')),
					'$dfrn_text' => $dfrn_text,	
					'$dfrn_id' => $rr['issued-id'],
					'$uid' => $_SESSION['uid'],
					'$intro_id' => $rr['intro_id'],
					'$contact_id' => $rr['contact-id'],
					'$photo' => ((x($rr,'photo')) ? $rr['photo'] : "images/default-profile.jpg"),
					'$fullname' => $rr['name'],
					'$url' => $rr['url'],
					'$knowyou' => $knowyou,
					'$approve' => t('Approve'),
					'$note' => $rr['note'],
					'$ignore' => t('Ignore'),
					'$discard' => t('Discard')

				));
			}
		}
		else
			info( t('No notifications.') . EOL);
		
		$o .= replace_macros($notif_tpl,array(
			'$notif_content' => $notif_content,
			'$activetab' => 'intros'
		));
		
		$o .= paginate($a);
		return $o;
				
	} else if (($a->argc > 1) && ($a->argv[1] == 'network')) {
		
		$notif_tpl = get_markup_template('notifications.tpl');
		
		$r = q("SELECT `item`.`id`,`item`.`parent`, `item`.`verb`, `item`.`author-name`, 
				`item`.`author-link`, `item`.`author-avatar`, `item`.`created`, `item`.`object`, 
				`pitem`.`author-name` as `pname`, `pitem`.`author-link` as `plink` 
				FROM `item` INNER JOIN `item` as `pitem` ON  `pitem`.`id`=`item`.`parent`
				WHERE `item`.`unseen` = 1 AND `item`.`visible` = 1 AND
				 `item`.`deleted` = 0 AND `item`.`uid` = %d AND `item`.`wall` = 0 ORDER BY `item`.`created` DESC" ,
			intval(local_user())
		);
		
		$tpl_item_likes = get_markup_template('notifications_likes_item.tpl');
		$tpl_item_dislikes = get_markup_template('notifications_dislikes_item.tpl');
		$tpl_item_friends = get_markup_template('notifications_friends_item.tpl');
		$tpl_item_comments = get_markup_template('notifications_comments_item.tpl');
		
		$notif_content = '';
		
		if (count($r) > 0) {
			
			foreach ($r as $it) {
				switch($it['verb']){
					case ACTIVITY_LIKE:
						$notif_content .= replace_macros($tpl_item_likes,array(
							'$item_link' => $a->get_baseurl().'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => sprintf( t("%s liked %s's post"), $it['author-name'], $it['pname']),
							'$item_when' => relative_date($it['created'])
						));

						break;
					case ACTIVITY_DISLIKE:
						$notif_content .= replace_macros($tpl_item_dislikes,array(
							'$item_link' => $a->get_baseurl().'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => sprintf( t("%s liked %s's post"), $it['author-name'], $it['pname']),
							'$item_when' => relative_date($it['created'])
						));

						break;
					case ACTIVITY_FRIEND:
						$notif_content .= replace_macros($tpl_item_friends,array(
							'$item_link' => $a->get_baseurl().'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => sprintf( t("%s is now friend with %s"), $it['author-name'], $it['fname']),
							'$item_when' => relative_date($it['created'])
						));

						break;
					default:
						$notif_content .= replace_macros($tpl_item_comments,array(
							'$item_link' => $a->get_baseurl().'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => sprintf( t("%s commented %s's post"), $it['author-name'], $it['pname']),
							'$item_when' => relative_date($it['created'])
						));
				}
			}
			
		} else {
			
			$notif_content = 'Nothing new!';
		}
		
		$o .= replace_macros($notif_tpl,array(
			'$notif_content' => $notif_content,
			'$activetab' => 'network'
		));
		
	} else if (($a->argc > 1) && ($a->argv[1] == 'home')) {
		
		$notif_tpl = get_markup_template('notifications.tpl');
		
		$r = q("SELECT `item`.`id`,`item`.`parent`, `item`.`verb`, `item`.`author-name`, 
				`item`.`author-link`, `item`.`author-avatar`, `item`.`created`, `item`.`object`, 
				`pitem`.`author-name` as `pname`, `pitem`.`author-link` as `plink` 
				FROM `item` INNER JOIN `item` as `pitem` ON  `pitem`.`id`=`item`.`parent`
				WHERE `item`.`unseen` = 1 AND `item`.`visible` = 1 AND
				 `item`.`deleted` = 0 AND `item`.`uid` = %d AND `item`.`wall` = 1 ORDER BY `item`.`created` DESC",
			intval(local_user())
		);
		
		$tpl_item_likes = get_markup_template('notifications_likes_item.tpl');
		$tpl_item_dislikes = get_markup_template('notifications_dislikes_item.tpl');
		$tpl_item_friends = get_markup_template('notifications_friends_item.tpl');
		$tpl_item_comments = get_markup_template('notifications_comments_item.tpl');
		
		$notif_content = '';
		
		if (count($r) > 0) {
			
			foreach ($r as $it) {
				switch($it['verb']){
					case ACTIVITY_LIKE:
						$notif_content .= replace_macros($tpl_item_likes,array(
							'$item_link' => $a->get_baseurl().'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => sprintf( t("%s liked %s's post"), $it['author-name'], $it['pname']),
							'$item_when' => relative_date($it['created'])
						));

						break;
					case ACTIVITY_DISLIKE:
						$notif_content .= replace_macros($tpl_item_dislikes,array(
							'$item_link' => $a->get_baseurl().'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => sprintf( t("%s liked %s's post"), $it['author-name'], $it['pname']),
							'$item_when' => relative_date($it['created'])
						));

						break;
					case ACTIVITY_FRIEND:
						$notif_content .= replace_macros($tpl_item_friends,array(
							'$item_link' => $a->get_baseurl().'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => sprintf( t("%s is now friend with %s"), $it['author-name'], $it['fname']),
							'$item_when' => relative_date($it['created'])
						));

						break;
					default:
						$notif_content .= replace_macros($tpl_item_comments,array(
							'$item_link' => $a->get_baseurl().'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => sprintf( t("%s commented %s's post"), $it['author-name'], $it['pname']),
							'$item_when' => relative_date($it['created'])
						));
				}
			}
				
		} else {
			$notif_content = 'Nothing new!';
		}
		
		$o .= replace_macros($notif_tpl,array(
			'$notif_content' => $notif_content,
			'$activetab' => 'home'
		));
	}

	$o .= paginate($a);
	return $o;
}
