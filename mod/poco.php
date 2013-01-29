<?php

function poco_init(&$a) {

	$system_mode = false;

	if(intval(get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		logger('mod_poco: block_public');
		http_status_exit(401);
	}

	$observer = $a->get_observer();

	if(argc() > 1) {
		$user = notags(trim(argv(1)));
	}
	if(! x($user)) {
		$c = q("select * from pconfig where cat = 'system' and k = 'suggestme' and v = 1");
		if(! $c) {
			logger('mod_poco: system mode. No candidates.', LOGGER_DEBUG);
			http_status_exit(401);
		}
		$system_mode = true;
	}


	$format = (($_REQUEST['format']) ? $_REQUEST['format'] : 'json');

	$justme = false;

	if(argc() > 2 && argv(2) === '@me')
		$justme = true;
	if(argc() > 3) {
		if(argv(3) === '@all')
			$justme = false;
		elseif(argv(3) === '@self')
			$justme = true;
	}
	if(argc() > 4 && intval(argv(4)) && $justme == false)
		$cid = intval(argv(4));
 		
	if(! $system_mode) {

		$r = q("SELECT channel_id from channel where channel_address = '%s' limit 1",
			dbesc($user)
		);
		if(! $r) {
			logger('mod_poco: user mode. Account not found. ' . $user);
			http_status_exit(404);
		}

		$channel_id = $r[0]['channel_id'];
		$ohash = (($observer) ? $observer['xchan_hash'] : '');

		if(! perm_is_allowed($channel_id,$ohash,'view_contacts')) {
			logger('mod_poco: user mode. Permission denied for ' . $ohash . ' user: ' . $user);
			http_status_exit(401);
		}

	}

	if($justme)
		$sql_extra = " and ( abook_flags & " . ABOOK_FLAG_SELF . " ) ";
	else
		$sql_extra = " and abook_flags = 0 ";

	if($cid)
		$sql_extra = sprintf(" and abook_id = %d ",intval($cid));

	if($system_mode) {
		$r = q("SELECT count(*) as `total` from abook where ( abook_flags & " . ABOOK_FLAG_SELF . 
			" ) and abook_channel in (select uid from pconfig where cat = 'system' and k = 'suggestme' and v = 1) ");
	}
	else {
		$r = q("SELECT count(*) as `total` from abook where abook_channel = %d 
			$sql_extra ",
			intval($channel_id)
		);
	}
	if($r)
		$totalResults = intval($r[0]['total']);
	else
		$totalResults = 0;

	$startIndex = intval($_GET['startIndex']);
	if(! $startIndex)
		$startIndex = 0;
	$itemsPerPage = ((x($_GET,'count') && intval($_GET['count'])) ? intval($_GET['count']) : $totalResults);


	if($system_mode) {
		$r = q("SELECT abook.*, xchan.* from abook left join xchan on abook_xchan = xchan_hash where ( abook_flags & " . ABOOK_FLAG_SELF . 
			" ) and abook_channel in (select uid from pconfig where cat = 'system' and k = 'suggestme' and v = 1) limit %d, %d ",
			intval($startIndex),
			intval($itemsPerPage)
		);
	}
	else {
		$r = q("SELECT abook.*, xchan.* from abook left join xchan on abook_xchan = xchan_hash where abook_channel = %d 
			$sql_extra LIMIT %d, %d",
			intval($channel_id),
			intval($startIndex),
			intval($itemsPerPage)
		);
	}

	$ret = array();
	if(x($_GET,'sorted'))
		$ret['sorted'] = 'false';
	if(x($_GET,'filtered'))
		$ret['filtered'] = 'false';
	if(x($_GET,'updatedSince'))
		$ret['updateSince'] = 'false';

	$ret['startIndex']   = (string) $startIndex;
	$ret['itemsPerPage'] = (string) $itemsPerPage;
	$ret['totalResults'] = (string) $totalResults;
	$ret['entry']        = array();


	$fields_ret = array(
		'id' => false,
		'guid' => false,
		'guid_sig' => false,
		'hash' => false,
		'displayName' => false,
		'urls' => false,
		'preferredUsername' => false,
		'photos' => false



	);

	if((! x($_GET,'fields')) || ($_GET['fields'] === '@all'))
		foreach($fields_ret as $k => $v)
			$fields_ret[$k] = true;
	else {
		$fields_req = explode(',',$_GET['fields']);
		foreach($fields_req as $f)
			$fields_ret[trim($f)] = true;
	}

	if(is_array($r)) {
		if(count($r)) {
			foreach($r as $rr) {
				$entry = array();
				if($fields_ret['id'])
					$entry['id'] = $rr['abook_id'];
				if($fields_ret['guid'])
					$entry['guid'] = $rr['xchan_guid'];
				if($fields_ret['guid_sig'])
					$entry['guid_sig'] = $rr['xchan_guid_sig'];
				if($fields_ret['hash'])
					$entry['hash'] = $rr['xchan_hash'];

				if($fields_ret['displayName'])
					$entry['displayName'] = $rr['xchan_name'];
				if($fields_ret['urls']) {
					$entry['urls'] = array(array('value' => $rr['xchan_url'], 'type' => 'profile'));
					if($rr['xchan_addr'])
						$entry['urls'][] = array('value' => 'acct:' . $rr['xchan_addr'], 'type' => 'zot');  
				}
				if($fields_ret['preferredUsername'])
					$entry['preferredUsername'] = substr($rr['xchan_addr'],0,strpos($rr['xchan_addr'],'@'));
				if($fields_ret['photos'])
					$entry['photos'] = array(array('value' => $rr['xchan_photo_l'], 'mimetype' => $rr['xchan_photo_mimetype'], 'type' => 'profile'));
				$ret['entry'][] = $entry;
			}
		}
		else
			$ret['entry'][] = array();
	}
	else
		http_status_exit(500);

	if($format === 'xml') {
		header('Content-type: text/xml');
		echo replace_macros(get_markup_template('poco_xml.tpl'),array_xmlify(array('$response' => $ret)));
		http_status_exit(500);
	}
	if($format === 'json') {
		header('Content-type: application/json');
		echo json_encode($ret);
		killme();	
	}
	else
		http_status_exit(500);


}