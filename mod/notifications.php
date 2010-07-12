<?php

function notifications_post(&$a) {

	if((! x($_SESSION,'authenticated')) || (! (x($_SESSION,'uid')))) {
		goaway($a->get_baseurl());
	}
	
	$request_id = (($a->argc > 1) ? $a->argv[0] : 0);
	
	if($request_id == "all")
		return;

	if($request_id) {

		$r = q("SELECT `id` FROM `intro` 
			WHERE `request-id` = %d 
			AND `uid` = %d LIMIT 1",
				intval($request_id),
				intval($_SESSION['uid'])
		);
	
		if(count($r)) {
			$intro_id = $r[0]['id'];
		}
		else {
			$_SESSION['sysmsg'] .= "Invalid request identifier." . EOL;
			return;
		}
		if($_POST['submit'] == 'Discard') {
			$r = q("DELETE FROM `intro` WHERE `id` = %d LIMIT 1", intval($intro_id));	
			$r = q("DELETE `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1", 
				intval($request_id),
				intval($_SESSION['uid']));
			return;
		}
		if($_POST['submit'] == 'Ignore') {
			$r = q("UPDATE `intro` SET `ignore` = 1 WHERE `id` = %d LIMIT 1",
				intval($intro_id));
			return;
		}
	}
}





function notifications_content(&$a) {

	$o = '';

	if((! x($_SESSION,'authenticated')) || (! (x($_SESSION,'uid')))) {
		goaway($a->get_baseurl());
	}

	if(($a->argc > 1) && ($a->argv[1] == 'all'))
		$sql_extra = '';
	else
		$sql_extra = " AND `ignore` = 0 ";


	$tpl = file_get_contents('view/intros-top.tpl');
	$o .= replace_macros($tpl,array(
		'$hide_url' => ((strlen($sql_extra)) ? 'notifications/all' : 'notifications' ),
		'$hide_text' => ((strlen($sql_extra)) ? 'Show Ignored Requests' : 'Hide Ignored Requests')
	)); 

	$r = q("SELECT `intro`.`id` AS `intro-id`, `intro`.*, `contact`.* 
		FROM `intro` LEFT JOIN `contact` ON `intro`.`contact-id` = `contact`.`id`
		WHERE `intro`.`uid` = %d $sql_extra AND `intro`.`blocked` = 0 ",
			intval($_SESSION['uid']));

	if(($r !== false) && (count($r))) {


		$tpl = file_get_contents("view/intros.tpl");

		foreach($r as $rr) {

			$o .= replace_macros($tpl,array(
				'$intro_id' => $rr['intro-id'],
				'$dfrn-id' => $rr['issued-id'],
				'$uid' => $_SESSION['uid'],
				'$contact-id' => $rr['contact-id'],
				'$photo' => ((x($rr,'photo')) ? $rr['photo'] : "images/default-profile.jpg"),
				'$fullname' => $rr['name'],
				'$knowyou' => (($rr['knowyou']) ? 'yes' : 'no'),
				'$url' => $rr['url'],
				'$note' => $rr['note']
			));
		}
	}
	else
		$_SESSION['sysmsg'] .= "No notifications." . EOL;

	return $o;
}