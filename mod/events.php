<?php

require_once('include/datetime.php');
require_once('include/event.php');

function events_post(&$a) {

	if(! local_user())
		return;

	$event_id = ((x($_POST,'event_id')) ? intval($_POST['event_id']) : 0);
	$uid      = local_user();
	$start    = strip_tags($_POST['start']);
	$finish   = strip_tags($_POST['finish']);
	$desc     = escape_tags($_POST['desc']);
	$location = escape_tags($_POST['location']);
	$type     = 'event';
	$adjust   = intval($_POST['adjust']);

	$str_group_allow   = perms2str($_POST['group_allow']);
	$str_contact_allow = perms2str($_POST['contact_allow']);
	$str_group_deny    = perms2str($_POST['group_deny']);
	$str_contact_deny  = perms2str($_POST['contact_deny']);


	if($event_id) {
		$r = q("UPDATE `event` SET
			`edited` = '%s',
			`start` = '%s',
			`finish` = '%s',
			`desc` = '%s',
			`location` = '%s',
			`type` = '%s',
			`adjust` = %d,
			`allow_cid` = '%s',
			`allow_gid` = '%s',
			`deny_cid` = '%s',
			`deny_gid` = '%s'
			WHERE `id` = %d AND `uid` = %d LIMIT 1",

			dbesc(datetime_convert()),
			dbesc($start),
			dbesc($finish),
			dbesc($desc),
			dbesc($location),
			dbesc($type),
			intval($adjust),
			dbesc($str_contact_allow),
			dbesc($str_group_allow),
			dbesc($str_contact_deny),
			dbesc($str_group_deny),
			intval($event_id),
			intval($local_user())
		);

	}
	else {

		$uri = item_new_uri($a->get_hostname(),local_user());

		$r = q("INSERT INTO `event` ( `uid`,`uri`,`created`,`edited`,`start`,`finish`,`desc`,`location`,`type`,
			`adjust`,`allow_cid`,`allow_gid`,`deny_cid`,`deny_gid`)
			VALUES ( %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s' ) ",
			intval(local_user()),

			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			dbesc($start),
			dbesc($finish),
			dbesc($desc),
			dbesc($location),
			dbesc($type),
			intval($adjust),
			dbesc($str_contact_allow),
			dbesc($str_group_allow),
			dbesc($str_contact_deny),
			dbesc($str_group_deny)

		);
	}

}


