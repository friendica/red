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
			break;
		case 'block':
			$arr['item_restrict'] = ITEM_BUILDBLOCK;
			break;
		case 'layout':
			$arr['item_restrict'] = ITEM_PDL;
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

	$x = item_store($arr,$execflag);
	$ret['success'] = true;
	json_return_and_die(true);

}