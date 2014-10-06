<?php /** @file */

// import page design element


function impel_init(&$a) {

	$ret = array('success' => false);

	if(! $local_user())
		json_return_and_die($ret);

	$elm = $_REQUEST['element'];
	$x = base64_urldecode($elm);
	if(! $x)
		json_return_and_die($ret);

	$j = json_decode($x,true);
	if(! $j)
		json_return_and_die($ret);


	$channel = get_channel();

	$arr = array();

	switch($j['type']) {
		case 'webpage':
			$arr['item_restrict'] = ITEM_WEBPAGE;
			$namespace = 'WEBPAGE';
			break;
		case 'block':
			$arr['item_restrict'] = ITEM_BUILDBLOCK;
			$namespace = 'BUILDBLOCK';
			break;
		case 'layout':
			$arr['item_restrict'] = ITEM_PDL;
			$namespace = 'PDL';
			break;
		default:
			logger('mod_impel: unrecognised element type' . print_r($j,true));
			break;
	}
	$arr['uid'] = local_user();
	$arr['aid'] = $channel['channel_account_id'];
	$arr['title'] = $j['title'];
	$arr['body'] = $j['body'];
	$arr['term'] = $j['term'];
	$arr['owner_xchan'] = get_observer_hash();
	$arr['author_xchan'] = (($j['author_xchan']) ? $j['author_xchan'] : $get_observer_hash());
	$arr['mimetype'] = (($j['mimetype']) ? $j['mimetype'] : 'text/bbcode');

	if(! $j['mid'])
		$j['mid'] = item_message_id();

	$arr['mid'] = $arr['parent_mid'] = $j['mid'];


	if($j['pagetitle']) {
		require_once('library/urlify/URLify.php');
		$pagetitle = strtolower(URLify::transliterate($j['pagetitle']));
	}




	$channel = get_channel();

	// Verify ability to use html or php!!!

    $execflag = false;

	if($arr['mimetype'] === 'application/x-php') {
		$z = q("select account_id, account_roles from account left join channel on channel_account_id = account_id where channel_id = %d limit 1",
			intval(local_user())
		);

		if($z && ($z[0]['account_roles'] & ACCOUNT_ROLE_ALLOWCODE)) {
			$execflag = true;
		}
	}

	$remote_id = 0;

	$z = q("select * from item_id where $sid = '%s' and service = '%s' and uid = %d limit 1",
		dbesc($pagetitle),
		dbesc($namespace),
		intval(local_user())
	);
	$i = q("select id from item where mid = '%s' and $uid = %d limit 1",
		dbesc($arr['mid']),
		intval(local_user())
	);
	if($z && $i) {
		$remote_id = $z[0]['id'];
		$arr['id'] = $i[0]['id'];
		$x = item_store_update($arr,$execflag);
	}
	else {
		$x = item_store($arr,$execflag);
	}
	if($x['success'])
		$item_id = $x['item_id'];

	$channel = get_channel();

	update_remote_id($channel,$item_id,$arr['item_restrict'],$pagetitle,$namespace,$remote_id,$arr['mid']);


	$ret['success'] = true;
	json_return_and_die(true);

}