<?php

function notifications_post(&$a) {

	if(! local_user()) {
		goaway($a->get_baseurl());
	}
	
	$request_id = (($a->argc > 1) ? $a->argv[1] : 0);
	
	if($request_id === "all")
		return;

	if($request_id) {

		$r = q("SELECT * FROM `intro` 
			WHERE `id` = %d 
			AND `uid` = %d LIMIT 1",
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
		if($_POST['submit'] == t('Discard')) {
			$r = q("DELETE FROM `intro` WHERE `id` = %d LIMIT 1", 
				intval($intro_id)
			);	
			$r = q("DELETE FROM `contact` WHERE `id` = %d AND `uid` = %d AND `self` = 0 LIMIT 1", 
				intval($contact_id),
				intval(local_user())
			);
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
		goaway($a->get_baseurl());
	}

	$o = '';
	$o .= '<script>	$(document).ready(function() { $(\'#nav-notify-link\').addClass(\'nav-selected\'); });</script>';

	if(($a->argc > 1) && ($a->argv[1] == 'all'))
		$sql_extra = '';
	else
		$sql_extra = " AND `ignore` = 0 ";


	$tpl = load_view_file('view/intros-top.tpl');
	$o .= replace_macros($tpl,array(
		'$hide_url' => ((strlen($sql_extra)) ? 'notifications/all' : 'notifications' ),
		'$hide_text' => ((strlen($sql_extra)) ? t('Show Ignored Requests') : t('Hide Ignored Requests'))
	)); 

	$r = q("SELECT `intro`.`id` AS `intro_id`, `intro`.*, `contact`.* 
		FROM `intro` LEFT JOIN `contact` ON `intro`.`contact-id` = `contact`.`id`
		WHERE `intro`.`uid` = %d $sql_extra AND `intro`.`blocked` = 0 ",
			intval($_SESSION['uid']));

	if(($r !== false) && (count($r))) {


		$tpl = load_view_file("view/intros.tpl");

		foreach($r as $rr) {

			$friend_selected = (($rr['network'] !== 'stat') ? ' checked="checked" ' : ' disabled ');
			$fan_selected = (($rr['network'] === 'stat') ? ' checked="checked" disabled ' : '');
			$dfrn_tpl = load_view_file('view/netfriend.tpl');

			$knowyou   = '';
			$dfrn_text = '';
						
			if($rr['network'] !== 'stat') {
				$knowyou = t('Claims to be known to you: ') . (($rr['knowyou']) ? t('yes') : t('no'));

				$dfrn_text = replace_macros($dfrn_tpl,array(
					'$intro_id' => $rr['intro_id'],
					'$friend_selected' => $friend_selected,
					'$fan_selected' => $fan_selected,
				));
			}			



			$o .= replace_macros($tpl,array(
				'$str_notifytype' => t('Notification type: '),
				'$notify_type' => (($rr['network'] !== 'stat') ? t('Friend/Connect Request') : t('New Follower')),
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
		notice( t('No notifications.') . EOL);

	return $o;
}