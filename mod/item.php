<?php

function sanitise_acl(&$item) {
	$item = '<' . intval(notags(trim($item))) . '>';
}

function item_post(&$a) {
dbg(3);
	if((! local_user()) && (! remote_user()))
		return;

	require_once('include/security.php');

	$uid = $_SESSION['uid'];
	$parent = ((x($_POST,'parent')) ? intval($_POST['parent']) : 0);

	$parent_item = null;

	if($parent) {
		$r = q("SELECT * FROM `item` WHERE `id` = %d LIMIT 1",
			intval($parent)
		);
		if(! count($r)) {
			notice("Unable to locate original post." . EOL);
			goaway($a->get_baseurl() . "/" . $_POST['return'] );
		}
		$parent_item = $r[0];
	}

	$profile_uid = ((x($_POST,'profile_uid')) ? intval($_POST['profile_uid']) : 0);

	if(! can_write_wall($a,$profile_uid)) {
		notice("Permission denied." . EOL) ;
		return;
	}
	
	$str_group_allow = '';
	$group_allow = $_POST['group_allow'];
	if(is_array($group_allow)) {
		array_walk($group_allow,'sanitise_acl');
		$str_group_allow = implode('',$group_allow);
	}

	$str_contact_allow = '';
	$contact_allow = $_POST['contact_allow'];
	if(is_array($contact_allow)) {
		array_walk($contact_allow,'sanitise_acl');
		$str_contact_allow = implode('',$contact_allow);
	}

	$str_group_deny = '';
	$group_deny = $_POST['group_deny'];
	if(is_array($group_deny)) {
		array_walk($group_deny,'sanitise_acl');
		$str_group_deny = implode('',$group_deny);
	}

	$str_contact_deny = '';
	$contact_deny = $_POST['contact_deny'];
	if(is_array($contact_deny)) {
		array_walk($contact_deny,'sanitise_acl');
		$str_contact_deny = implode('',$contact_deny);
	}


	$body = escape_tags(trim($_POST['body']));

	if(! strlen($body)) {
		notice("Empty post discarded." . EOL );
		goaway($a->get_baseurl() . "/" . $_POST['return'] );

	}

	// get contact info for poster

	if((x($_SESSION,'visitor_id')) && (intval($_SESSION['visitor_id']))) {
		$contact_id = $_SESSION['visitor_id'];
	}
	else {
		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 1 LIMIT 1",
			intval($_SESSION['uid']));
		if(count($r))
			$contact_id = $r[0]['id'];
	}

	// get contact info for owner
	
	$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 1 LIMIT 1",
		intval($profile_uid)
	);
	if(count($r))
		$contact_record = $r[0];


	$notify_type = (($parent) ? 'comment-new' : 'wall-new' );

	if(($_POST['type'] == 'wall') || ($_POST['type'] == 'wall-comment')) {

		do {
			$dups = false;
			$hash = random_string();

			$uri = "urn:X-dfrn:" . $a->get_hostname() . ':' . $profile_uid . ':' . $hash;

			$r = q("SELECT `id` FROM `item` WHERE `uri` = '%s' LIMIT 1",
			dbesc($uri));
			if(count($r))
				$dups = true;
		} while($dups == true);


		$r = q("INSERT INTO `item` (`uid`,`type`,`contact-id`,`owner-name`,`owner-link`,`owner-avatar`, `created`,
			`edited`, `uri`, `body`, `allow_cid`, `allow_gid`, `deny_cid`, `deny_gid`)
			VALUES( %d, '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )",
			intval($profile_uid),
			dbesc($_POST['type']),
			intval($contact_id),
			dbesc($contact_record['name']),
			dbesc($contact_record['url']),
			dbesc($contact_record['thumb']),
			datetime_convert(),
			datetime_convert(),
			dbesc($uri),
			dbesc(escape_tags(trim($_POST['body']))),
			dbesc($str_contact_allow),
			dbesc($str_group_allow),
			dbesc($str_contact_deny),
			dbesc($str_group_deny)

		);
		$r = q("SELECT `id` FROM `item` WHERE `uri` = '%s' LIMIT 1",
			dbesc($uri));
		if(count($r)) {
			$post_id = $r[0]['id'];

			if($parent) {

				// This item is the last leaf and gets the comment box, clear any ancestors
				$r = q("UPDATE `item` SET `last-child` = 0 WHERE `parent` = %d ",
					intval($parent)
				);

				// Inherit ACL's from the parent item.
				// TODO merge with subsequent UPDATE operation and save a db write 

				$r = q("UPDATE `item` SET `allow_cid` = '%s', `allow_gid` = '%s', `deny_cid` = '%s', `deny_gid` = '%s'
					WHERE `id` = %d LIMIT 1",
					dbesc($parent_item['allow_cid']),
					dbesc($parent_item['allow_gid']),
					dbesc($parent_item['deny_cid']),
					dbesc($parent_item['deny_gid']),
					intval($post_id)
				);
			}
			else {
				$parent = $post_id;
			}

			$r = q("UPDATE `item` SET `parent` = %d, `parent-uri` = '%s', `last-child` = 1, `visible` = 1
				WHERE `id` = %d LIMIT 1",
				intval($parent),
				dbesc(($parent == $post_id) ? $uri : $parent_item['uri']),
				intval($post_id)
			);
		}

		$url = $a->get_baseurl();

		proc_close(proc_open("php include/notifier.php \"$url\" \"$notify_type\" \"$post_id\" > notify.log &",
			array(),$foo));

	}
//	goaway($a->get_baseurl() . "/" . $_POST['return'] );
	return; // NOTREACHED
}