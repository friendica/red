<?php


//
// Takes a $uid and the channel associated with the uid, and a url/handle and adds a new channel

// Returns an array
//  $return['success'] boolean true if successful
//  $return['abook_id'] Address book ID if successful
//  $return['message'] error text if success is false.

require_once('include/zot.php');

function new_contact($uid,$url,$channel,$interactive = false) {

	$result = array('success' => false,'message' => '');

	$a = get_app();

	if(! allowed_url($url)) {
		$result['message'] = t('Channel is blocked on this site.');
		return $result;
	}

	if(! $url) {
		$result['message'] = t('Channel location missing.');
		return $result;
	}

	$arr = array('url' => $url, 'channel' => array());

	call_hooks('follow', $arr);

	if($arr['channel']['success']) 
		$ret = $arr['channel'];
	else
		$ret = zot_finger($url,$channel,false);

	if($ret['success']) {
		$j = json_decode($ret['body']);

	}

	logger('follow: ' . $url . ' ' . print_r($j,true));
//	killme();


	if(! ($j->success && $j->guid)) {
		$result['message'] = t('Unable to communicate with requested channel.');
		return $result;
	}


	// check service class limits

	$r = q("select count(*) as total from abook where abook_channel = %d and not (abook_flags & %d)",
		intval($uid),
		intval(ABOOK_FLAG_SELF)
	);
	if($r)
		$total_channels = $r[0]['total'];

	if(! service_class_allows($uid,'total_channels',$total_channels)) {
		$result['message'] .= upgrade_message();
		return $result;
	}

	// do we have an xchan and hubloc?
	// If not, create them.	

	$x = import_xchan_from_json($j);

	// Do we already have an abook entry?
	// go directly to the abook edit page.

	$their_perms = 0;

	$global_perms = get_perms();

	foreach($j->permissions as $k => $v) {
		if($v) {
			$their_perms = $their_perms | intval($global_perms[$k][1]);
		}
	}




	// Else create an entry



	// Then send a ping/message to the other side


	

/*

	$r = q("INSERT INTO `contact` ( `uid`, `created`, `url`, `nurl`, `addr`, `alias`, `batch`, `notify`, `poll`, `poco`, `name`, `nick`, `photo`, `network`, `pubkey`, `rel`, `priority`,
			`writable`, `hidden`, `blocked`, `readonly`, `pending` )
			VALUES ( %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, %d, %d, 0, 0, 0) ",
			intval($uid),
			dbesc(datetime_convert()),
			dbesc($ret['url']),
			dbesc(normalise_link($ret['url'])),
			dbesc($ret['addr']),
			dbesc($ret['alias']),
			dbesc($ret['batch']),
			dbesc($ret['notify']),
			dbesc($ret['poll']),
			dbesc($ret['poco']),
			dbesc($ret['name']),
			dbesc($ret['nick']),
			dbesc($ret['photo']),
			dbesc($ret['network']),
			dbesc($ret['pubkey']),
			intval($new_relation),
			intval($ret['priority']),
			intval($writeable),
			intval($hidden)
		);
	}

	$r = q("SELECT * FROM `contact` WHERE `url` = '%s' AND `uid` = %d LIMIT 1",
		dbesc($ret['url']),
		intval($uid)
	);

	if(! count($r)) {
		$result['message'] .=  t('Unable to retrieve contact information.') . EOL;
		return $result;
	}

	$contact = $r[0];
	$contact_id  = $r[0]['id'];


	$g = q("select def_gid from user where uid = %d limit 1",
		intval($uid)
	);
	if($g && intval($g[0]['def_gid'])) {
		require_once('include/group.php');
		group_add_member($uid,'',$contact_id,$g[0]['def_gid']);
	}

*/


	$result['success'] = true;
	return $result;



}
