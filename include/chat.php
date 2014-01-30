<?php /** @file */


function chatroom_create($channel,$arr) {

	$ret = array('success' => false);

	$name = trim($arr['name']);
	if(! $name) {
		$ret['message'] = t('Missing room name');
		return $ret;
	}

	$r = q("select cr_id from chatroom where cr_uid = %d and cr_name = '%s' limit 1",
		intval($channel['channel_id']),
		dbesc($name)
	);
	if($r) {
		$ret['message'] = t('Duplicate room name');
		return $ret;
	}

	$created = datetime_convert();

	$x = q("insert into chatroom ( cr_aid, cr_uid, cr_name, cr_created, cr_edited, allow_cid, allow_gid, deny_cid, deny_gid )
		values ( %d, %d , '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) ",
		intval($channel['channel_account_id']),
		intval($channel['channel_id']),
		dbesc($name),
		dbesc($created),
		dbesc($created),
		dbesc($arr['allow_cid']),
		dbesc($arr['allow_gid']),
		dbesc($arr['deny_cid']),
		dbesc($arr['deny_gid'])
	);

	if($x)
		$ret['success'] = true;

	return $ret;
}


function chatroom_destroy($channel,$arr) {

	$ret = array('success' => false);
	if(intval($arr['cr_id']))
		$sql_extra = " and cr_id = " . intval($arr['cr_id']) . " ";
	elseif(trim($arr['cr_name']))
		$sql_extra = " and cr_name = '" . protect_sprintf(dbesc(trim($arr['cr_name']))) . "' ";
	else {
		$ret['message'] = t('Invalid room specifier.');
		return $ret;
	}

	$r = q("select * from chatroom where cr_uid = %d $sql_extra limit 1",
		intval($channel['channel_id'])
	);
	if(! $r) {		
		$ret['message'] = t('Invalid room specifier.');
		return $ret;
	}

	q("delete from chatroom where cr_id = %d limit 1",
		intval($r[0]['cr_id'])
	);
	if($r[0]['cr_id']) {
		q("delete from chatpresence where cp_room = %d",
			intval($r[0]['cr_id'])
		);
	}
	$ret['success'] = true;
	return $ret;
}


function chatroom_enter($observer_xchan,$room_id,$status,$client) {

	if(! $room_id || ! $observer_xchan)
		return;

	$r = q("select * from chatroom where cr_id = %d limit 1",
		intval($room_id)
	);
	if(! $r) {
		notice( t('Room not found.') . EOL);
		return false;
	}
	require_once('include/security.php');
	$sql_extra = permissions_sql($r[0]['cr_uid']);

	$x = q("select * from chatroom where cr_id = %d and cr_uid = %d $sql_extra limit 1",
			intval($room_id),
			intval($r[0]['cr_uid'])
	);
	if(! $x) {
		notice( t('Permission denied.') . EOL);
		return false;
	}

	$r = q("select * from chatpresence where cp_xchan = '%s' and cp_room = %d limit 1",
		dbesc($observer_xchan),
		intval($room_id)
	);
	if($r) {
		q("update chatpresence set cp_status = %d and cp_last = '%s' where cp_id = %d limit 1",
			dbesc($status),
			dbesc(datetime_convert()),
			intval($r[0]['cp_id'])
		);
		return true;
	}

	$r = q("insert into chatpresence ( cp_room, cp_xchan, cp_last, cp_status, cp_client )
		values ( %d, '%s', '%s', '%s', '%s' )",
		intval($room_id),
		dbesc($observer_xchan),
		dbesc(datetime_convert()),
		dbesc($status),
		dbesc($client)
	);
	return $r;
}


function chatroom_leave($observer_xchan,$room_id,$client) {
	if(! $room_id || ! $observer_xchan)
		return;
	$r = q("select * from chatpresence where cp_xchan = '%s' and cp_room = %d and cp_client = '%s' limit 1",
		dbesc($observer_xchan),
		intval($room_id),
		dbesc($client)
	);
	if($r) {
		q("delete from chatpresence where cp_id = %d limit 1",
			intval($r[0]['cp_id'])
		);
	}
	return true;
}


function chatroom_list($uid) {

	$r = q("select cr_name, cr_id, count(cp_id) as cr_inroom from chatroom left join chatpresence on cr_id = cp_room where cr_uid = %d  group by cp_id order by cr_name",
		intval($uid)
	);

	return $r;
}