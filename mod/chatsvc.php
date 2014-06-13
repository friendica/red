<?php /** @file */

require_once('include/security.php');

function chatsvc_init(&$a) {

//logger('chatsvc');

	$ret = array('success' => false);

	$a->data['chat']['room_id'] = intval($_REQUEST['room_id']);
	$x = q("select cr_uid from chatroom where cr_id = %d and cr_id != 0 limit 1",
		intval($a->data['chat']['room_id'])
	);
	if(! $x)
		json_return_and_die($ret);

	$a->data['chat']['uid'] = $x[0]['cr_uid'];

	if(! perm_is_allowed($a->data['chat']['uid'],get_observer_hash(),'chat')) {
        json_return_and_die($ret);
    }

}

function chatsvc_post(&$a) {

	$ret = array('success' => false);

	$room_id = $a->data['chat']['room_id'];
	$text = escape_tags($_REQUEST['chat_text']);
	if(! $text)
		return;

	$sql_extra = permissions_sql($a->data['chat']['uid']);

	$r = q("select * from chatroom where cr_uid = %d and cr_id = %d $sql_extra",
		intval($a->data['chat']['uid']),
		intval($a->data['chat']['room_id'])
	);
	if(! $r)
		json_return_and_die($ret);

	$arr = array(
		'chat_room' => $a->data['chat']['room_id'],
		'chat_xchan' => get_observer_hash(),
		'chat_text' => $text
	);

	call_hooks('chat_post',$arr);

	$x = q("insert into chat ( chat_room, chat_xchan, created, chat_text )
		values( %d, '%s', '%s', '%s' )",
		intval($a->data['chat']['room_id']),
		dbesc(get_observer_hash()),
		dbesc(datetime_convert()),
		dbesc($arr['chat_text'])		
	);

	$ret['success'] = true;
	json_return_and_die($ret);
}

function chatsvc_content(&$a) {

	$status = strip_tags($_REQUEST['status']);
	$room_id = intval($a->data['chat']['room_id']);
	$stopped = ((x($_REQUEST,'stopped') && intval($_REQUEST['stopped'])) ? true : false);

	if($status && $room_id) {

		$x = q("select channel_address from channel where channel_id = %d limit 1",
			intval($a->data['chat']['uid'])
		);			

		$r = q("update chatpresence set cp_status = '%s', cp_last = '%s' where cp_room = %d and cp_xchan = '%s' and cp_client = '%s' limit 1",
			dbesc($status),
			dbesc(datetime_convert()),
			intval($room_id),
			dbesc(get_observer_hash()),
			dbesc($_SERVER['REMOTE_ADDR'])
		);

		goaway(z_root() . '/chat/' . $x[0]['channel_address'] . '/' . $room_id);		
	}

	if(! $stopped) {

		$lastseen = intval($_REQUEST['last']);

		$ret = array('success' => false);

		$sql_extra = permissions_sql($a->data['chat']['uid']);

		$r = q("select * from chatroom where cr_uid = %d and cr_id = %d $sql_extra",
			intval($a->data['chat']['uid']),
			intval($a->data['chat']['room_id'])
		);
		if(! $r)
			json_return_and_die($ret);

		$inroom = array();

		$r = q("select * from chatpresence left join xchan on xchan_hash = cp_xchan where cp_room = %d order by xchan_name",
			intval($a->data['chat']['room_id'])
		);
		if($r) {
			foreach($r as $rr) {
				switch($rr['cp_status']) {
					case 'away':
						$status = t('Away');
						break;
					case 'online':
					default:
						$status = t('Online');
						break;
				}
	
				$inroom[] = array('img' => zid($rr['xchan_photo_m']), 'img_type' => $rr['xchan_photo_mimetype'],'name' => $rr['xchan_name'], status => $status);	
			}
		}

		$chats = array();

		$r = q("select * from chat left join xchan on chat_xchan = xchan_hash where chat_room = %d and chat_id > %d order by created",
			intval($a->data['chat']['room_id']),
			intval($lastseen)
		);
		if($r) {
			foreach($r as $rr) {
				$chats[] = array(
					'id' => $rr['chat_id'],
					'img' => zid($rr['xchan_photo_m']), 
					'img_type' => $rr['xchan_photo_mimetype'],
					'name' => $rr['xchan_name'],
					'isotime' => datetime_convert('UTC', date_default_timezone_get(), $rr['created'], 'c'),
					'localtime' => datetime_convert('UTC', date_default_timezone_get(), $rr['created'], 'r'),
					'text' => smilies(bbcode($rr['chat_text']))
				);
			}
		}
	}

	$r = q("update chatpresence set cp_last = '%s' where cp_room = %d and cp_xchan = '%s' and cp_client = '%s' limit 1",
		dbesc(datetime_convert()),
		intval($a->data['chat']['room_id']),
		dbesc(get_observer_hash()),
		dbesc($_SERVER['REMOTE_ADDR'])
	);

	$ret['success'] = true;
	if(! $stopped) {
		$ret['inroom'] = $inroom;
		$ret['chats'] = $chats;
	}
	json_return_and_die($ret);

}
		 
