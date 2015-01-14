<?php /** @file */

// import page design element


function impel_init(&$a) {

	$ret = array('success' => false);

	if(! local_user())
		json_return_and_die($ret);

	logger('impel: ' . print_r($_REQUEST,true), LOGGER_DATA);

	$elm = $_REQUEST['element'];
	$x = base64url_decode($elm);
	if(! $x)
		json_return_and_die($ret);

	$j = json_decode($x,true);
	if(! $j)
		json_return_and_die($ret);


	$channel = $a->get_channel();

	$arr = array();

	switch($j['type']) {
		case 'webpage':
			$arr['item_restrict'] = ITEM_WEBPAGE;
			$namespace = 'WEBPAGE';
			$installed_type = t('webpage');
			break;
		case 'block':
			$arr['item_restrict'] = ITEM_BUILDBLOCK;
			$namespace = 'BUILDBLOCK';
			$installed_type = t('block');
			break;
		case 'layout':
			$arr['item_restrict'] = ITEM_PDL;
			$namespace = 'PDL';
			$installed_type = t('layout');
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
	$arr['created'] = datetime_convert('UTC','UTC', $j['created']);
	$arr['edited'] = datetime_convert('UTC','UTC',$j['edited']);
	$arr['owner_xchan'] = get_observer_hash();
	$arr['author_xchan'] = (($j['author_xchan']) ? $j['author_xchan'] : get_observer_hash());
	$arr['mimetype'] = (($j['mimetype']) ? $j['mimetype'] : 'text/bbcode');

	if(! $j['mid'])
		$j['mid'] = item_message_id();

	$arr['mid'] = $arr['parent_mid'] = $j['mid'];


	if($j['pagetitle']) {
		require_once('library/urlify/URLify.php');
		$pagetitle = strtolower(URLify::transliterate($j['pagetitle']));
	}



	// Verify ability to use html or php!!!

    $execflag = false;

	if($arr['mimetype'] === 'application/x-php') {
		$z = q("select account_id, account_roles, channel_pageflags from account left join channel on channel_account_id = account_id where channel_id = %d limit 1",
			intval(local_user())
		);

		if($z && (($z[0]['account_roles'] & ACCOUNT_ROLE_ALLOWCODE) || ($z[0]['channel_pageflags'] & PAGE_ALLOWCODE))) {
			$execflag = true;
		}
	}

	$remote_id = 0;

	$z = q("select * from item_id where sid = '%s' and service = '%s' and uid = %d limit 1",
		dbesc($pagetitle),
		dbesc($namespace),
		intval(local_user())
	);
	$i = q("select id from item where mid = '%s' and uid = %d limit 1",
		dbesc($arr['mid']),
		intval(local_user())
	);
	if($z && $i) {
		$remote_id = $z[0]['id'];
		$arr['id'] = $i[0]['id'];
		// don't update if it has the same timestamp as the original
		if($arr['edited'] > $i[0]['edited'])
			$x = item_store_update($arr,$execflag);
	}
	else {
		$x = item_store($arr,$execflag);
	}
	if($x['success'])
		$item_id = $x['item_id'];


	update_remote_id($channel,$item_id,$arr['item_restrict'],$pagetitle,$namespace,$remote_id,$arr['mid']);


	$ret['success'] = true;

	info( sprintf( t('%s element installed'), $installed_type)); 

	json_return_and_die(true);

}