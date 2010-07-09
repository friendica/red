<?php


function item_post(&$a) {

	if((! local_user()) && (! remote_user()))
		return;

	require_once('include/security.php');

	$uid = $_SESSION['uid'];
	$parent = ((x($_POST,'parent')) ? intval($_POST['parent']) : 0);
	$profile_uid = ((x($_POST,'profile_uid')) ? intval($_POST['profile_uid']) : 0);
	if(! can_write_wall($a,$profile_uid)) {
		notice("Permission denied." . EOL) ;
		return;
	}

	if((x($_SESSION,'visitor_id')) && (intval($_SESSION['visitor_id'])))
		$contact_id = $_SESSION['visitor_id'];
	else {
		$r = q("SELECT `id` FROM `contact` WHERE `uid` = %d AND `self` = 1 LIMIT 1",
			intval($_SESSION['uid']));
		if(count($r))
			$contact_id = $r[0]['id'];
	}	

	$notify_type = (($parent) ? 'comment-new' : 'wall-new' );

	if($_POST['type'] == 'jot') {

		do {
			$dups = false;
			$hash = random_string();
			$r = q("SELECT `id` FROM `item` WHERE `hash` = '%s' LIMIT 1",
			dbesc($hash));
			if(count($r))
				$dups = true;
		} while($dups == true);


		$r = q("INSERT INTO `item` (`uid`,`type`,`contact-id`,`created`,`edited`,`hash`,`body`)
			VALUES( %d, '%s', %d, '%s', '%s', '%s', '%s' )",
			intval($profile_uid),
			"jot",
			intval($contact_id),
			datetime_convert(),
			datetime_convert(),
			dbesc($hash),
			dbesc(escape_tags(trim($_POST['body'])))
		);
		$r = q("SELECT `id` FROM `item` WHERE `hash` = '%s' LIMIT 1",
			dbesc($hash));
		if(count($r)) {
			$post_id = $r[0]['id'];
			if(! $parent)
				$parent = $post_id;
			$r = q("UPDATE `item` SET `parent` = %d, `visible` = 1
				WHERE `id` = %d LIMIT 1",
				intval($parent),
				intval($post_id));
		}

		$url = bin2hex($a->get_baseurl());

		proc_close(proc_open("php include/notifier.php $url $notify_type $post_id > notify.log &",
			array(),$foo));

	}
	goaway($a->get_baseurl() . "/profile/$profile_uid");







}