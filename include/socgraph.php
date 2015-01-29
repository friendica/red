<?php /** @file */

require_once('include/dir_fns.php');
require_once('include/zot.php');

/*
 * poco_load
 *
 * xchan is your connection
 * We will load their friend list, and store in xlink_xchan your connection hash and xlink_link the hash for each connection
 * If xchan isn't provided we will load the list of people from url who have indicated they are willing to be friends with
 * new folks and add them to xlink with no xlink_xchan.
 *
 * Old behaviour: (documentation only):
 * Given a contact-id (minimum), load the PortableContacts friend list for that contact,
 * and add the entries to the gcontact (Global Contact) table, or update existing entries
 * if anything (name or photo) has changed.
 * We use normalised urls for comparison which ignore http vs https and www.domain vs domain
 *
 * Once the global contact is stored add (if necessary) the contact linkage which associates
 * the given uid, cid to the global contact entry. There can be many uid/cid combinations
 * pointing to the same global contact id. 
 *
 */
 



function poco_load($xchan = '',$url = null) {
	$a = get_app();

	if($xchan && ! $url) {
		$r = q("select xchan_connurl from xchan where xchan_hash = '%s' limit 1",
			dbesc($xchan)
		);
		if($r) {
			$url = $r[0]['xchan_connurl'];
		}
	}

	if(! $url) {
		logger('poco_load: no url');
		return;
	}


	$url = $url . '?f=&fields=displayName,hash,urls,photos,rating' ;

	logger('poco_load: ' . $url, LOGGER_DEBUG);

	$s = z_fetch_url($url);

	if(! $s['success']) {
		if($s['return_code'] == 401)
			logger('poco_load: protected');
		elseif($s['return_code'] == 404)
			logger('poco_load: nothing found');
		else
			logger('poco_load: returns ' . print_r($s,true));
		return;
	}

	$j = json_decode($s['body'],true);

	if(! $j) {
		logger('poco_load: unable to json_decode returned data.');
		return;
	}

	logger('poco_load: ' . print_r($j,true),LOGGER_DATA);

	if($xchan) {
		if(array_key_exists('chatrooms',$j) && is_array($j['chatrooms'])) {
			foreach($j['chatrooms'] as $room) {
				if((! $room['url']) || (! $room['desc']))
					continue;

				$r = q("select * from xchat where xchat_url = '%s' and xchat_xchan = '%s' limit 1",
					dbesc($room['url']),
					dbesc($xchan)
				);
				if($r) {
					q("update xchat set xchat_edited = '%s' where xchat_id = %d",
						dbesc(datetime_convert()),
						intval($r[0]['xchat_id'])
					);
				}
				else {
					$x = q("insert into xchat ( xchat_url, xchat_desc, xchat_xchan, xchat_edited )
						values ( '%s', '%s', '%s', '%s' ) ",
						dbesc(escape_tags($room['url'])),
						dbesc(escape_tags($room['desc'])),
						dbesc($xchan),
						dbesc(datetime_convert())
					);
				}
			}
		}
		q("delete from xchat where xchat_edited < %s - INTERVAL %s and xchat_xchan = '%s' ",
			db_utcnow(), db_quoteinterval('7 DAY'),
			dbesc($xchan)
		);
	}

	if(! ((x($j,'entry')) && (is_array($j['entry'])))) {
		logger('poco_load: no entries');
		return;
	}

	$total = 0;
	foreach($j['entry'] as $entry) {

		$profile_url = '';
		$profile_photo = '';
		$address = '';
		$name = '';
		$hash = '';
		$rating = 0;

		$name   = $entry['displayName'];
		$hash   = $entry['hash'];
		$rating = ((array_key_exists('rating',$entry) && (! is_array($entry['rating']))) ? intval($entry['rating']) : 0);
		$rating_text = ((array_key_exists('rating_text',$entry)) ? escape_tags($entry['rating_text']) :'');

		if(x($entry,'urls') && is_array($entry['urls'])) {
			foreach($entry['urls'] as $url) {
				if($url['type'] == 'profile') {
					$profile_url = $url['value'];
					continue;
				}
				if($url['type'] == 'zot' || $url['type'] == 'diaspora' || $url['type'] == 'friendica') {
					$network = $url['type'];
					$address = str_replace('acct:' , '', $url['value']);
					continue;
				}
			}
		}
		if(x($entry,'photos') && is_array($entry['photos'])) { 
			foreach($entry['photos'] as $photo) {
				if($photo['type'] == 'profile') {
					$profile_photo = $photo['value'];
					continue;
				}
			}
		}

		if((! $name) || (! $profile_url) || (! $profile_photo) || (! $hash) || (! $address)) {
			logger('poco_load: missing data');
			continue; 
		}
		 
		$x = q("select xchan_hash from xchan where xchan_hash = '%s' limit 1",
			dbesc($hash)
		);

		// We've never seen this person before. Import them.

		if(($x !== false) && (! count($x))) {
			if($address) {
				if($network === 'zot') {
					$z = zot_finger($address,null);
					if($z['success']) {
						$j = json_decode($z['body'],true);
						if($j)
							import_xchan($j);
					}
					$x = q("select xchan_hash from xchan where xchan_hash = '%s' limit 1",
						dbesc($hash)
					);
					if(! $x) {
						continue;
					}
				}
				else {
					$x = import_author_diaspora(array('address' => $address));
					if(! $x) {
						continue;
					}
				}
			}
			else {
				continue;
			}
		}
	
		$total ++;


		$r = q("select * from xlink where xlink_xchan = '%s' and xlink_link = '%s' and xlink_static = 0 limit 1",
			dbesc($xchan),
			dbesc($hash)
		);

		if(! $r) {
			q("insert into xlink ( xlink_xchan, xlink_link, xlink_rating, xlink_rating_text, xlink_updated, xlink_static ) values ( '%s', '%s', %d, '%s', '%s', 0 ) ",
				dbesc($xchan),
				dbesc($hash),
				intval($rating),
				dbesc($rating_text),
				dbesc(datetime_convert())
			);
		}
		else {
			q("update xlink set xlink_updated = '%s', xlink_rating = %d, xlink_rating_text = '%s' where xlink_id = %d",
				dbesc(datetime_convert()),
				intval($rating),
				dbesc($rating_text),
				intval($r[0]['xlink_id'])
			);
		}
	}
	logger("poco_load: loaded $total entries",LOGGER_DEBUG);

	q("delete from xlink where xlink_xchan = '%s' and xlink_updated < %s - INTERVAL %s and xlink_static = 0",
		dbesc($xchan),
		db_utcnow(), db_quoteinterval('2 DAY')
	);


}


function count_common_friends($uid,$xchan) {

	$r = q("SELECT count(xlink_id) as total from xlink where xlink_xchan = '%s' and xlink_static = 0 and xlink_link in
		(select abook_xchan from abook where abook_xchan != '%s' and abook_channel = %d and abook_flags = 0 )",
		dbesc($xchan),
		dbesc($xchan),
		intval($uid)
	);

	if($r)
		return $r[0]['total'];
	return 0;
}


function common_friends($uid,$xchan,$start = 0,$limit=100000000,$shuffle = false) {

	$rand = db_getfunc('rand');
	if($shuffle)
		$sql_extra = " order by $rand ";
	else
		$sql_extra = " order by xchan_name asc "; 

	$r = q("SELECT * from xchan left join xlink on xlink_link = xchan_hash where xlink_xchan = '%s' and xlink_static = 0 and xlink_link in
		(select abook_xchan from abook where abook_xchan != '%s' and abook_channel = %d and abook_flags = 0 ) $sql_extra limit %d offset %d",
		dbesc($xchan),
		dbesc($xchan),
		intval($uid),
		intval($limit),
		intval($start)
	);

	return $r;

}


function count_common_friends_zcid($uid,$zcid) {

	$r = q("SELECT count(*) as `total` 
		FROM `glink` left join `gcontact` on `glink`.`gcid` = `gcontact`.`id`
		where `glink`.`zcid` = %d
		and `gcontact`.`nurl` in (select nurl from contact where uid = %d and self = 0 and blocked = 0 and hidden = 0 ) ",
		intval($zcid),
		intval($uid)
	);

	if(count($r))
		return $r[0]['total'];
	return 0;

}

function common_friends_zcid($uid,$zcid,$start = 0, $limit = 9999,$shuffle = false) {

	if($shuffle)
		$sql_extra = " order by rand() ";
	else
		$sql_extra = " order by `gcontact`.`name` asc "; 

	$r = q("SELECT `gcontact`.* 
		FROM `glink` left join `gcontact` on `glink`.`gcid` = `gcontact`.`id`
		where `glink`.`zcid` = %d
		and `gcontact`.`nurl` in (select nurl from contact where uid = %d and self = 0 and blocked = 0 and hidden = 0 ) 
		$sql_extra limit %d offset %d",
		intval($zcid),
		intval($uid),
		intval($limit),
		intval($start)
	);

	return $r;

}


function count_all_friends($uid,$cid) {

	$r = q("SELECT count(*) as `total`
		FROM `glink` left join `gcontact` on `glink`.`gcid` = `gcontact`.`id`
		where `glink`.`cid` = %d and `glink`.`uid` = %d ",
		intval($cid),
		intval($uid)
	);

	if(count($r))
		return $r[0]['total'];
	return 0;

}


function all_friends($uid,$cid,$start = 0, $limit = 80) {

	$r = q("SELECT `gcontact`.* 
		FROM `glink` left join `gcontact` on `glink`.`gcid` = `gcontact`.`id`
		where `glink`.`cid` = %d and `glink`.`uid` = %d 
		order by `gcontact`.`name` asc LIMIT %d OFFSET %d ",
		intval($cid),
		intval($uid),
		intval($limit),
		intval($start)
	);

	return $r;
}



function suggestion_query($uid, $myxchan, $start = 0, $limit = 80) {

	if((! $uid) || (! $myxchan))
		return array();

	$r = q("SELECT count(xlink_xchan) as `total`, xchan.* from xchan
		left join xlink on xlink_link = xchan_hash
		where xlink_xchan in ( select abook_xchan from abook where abook_channel = %d )
		and not xlink_link in ( select abook_xchan from abook where abook_channel = %d )
		and not xlink_link in ( select xchan from xign where uid = %d )
		and xlink_xchan != ''
		and xlink_static = 0
		and not ( xchan_flags & %d )>0
		and not ( xchan_flags & %d )>0
		group by xchan_hash order by total desc limit %d offset %d ",
		intval($uid),
		intval($uid),
		intval($uid),
		intval(XCHAN_FLAGS_HIDDEN),
		intval(XCHAN_FLAGS_DELETED),
		intval($limit),
		intval($start)
	);

	if($r && count($r) >= ($limit -1))
		return $r;

	$r2 = q("SELECT count(xlink_link) as `total`, xchan.* from xchan
		left join xlink on xlink_link = xchan_hash
		where xlink_xchan = ''
		and not xlink_link in ( select abook_xchan from abook where abook_channel = %d )
		and not xlink_link in ( select xchan from xign where uid = %d )
		and xlink_static = 0
		and not ( xchan_flags & %d )>0
		and not ( xchan_flags & %d )>0
		group by xchan_hash order by total desc limit %d offset %d ",
		intval($uid),
		intval($uid),
		intval(XCHAN_FLAGS_HIDDEN),
		intval(XCHAN_FLAGS_DELETED),
		intval($limit),
		intval($start)
	);

	if(is_array($r) && is_array($r2))
		return array_merge($r,$r2);

	return array();
}

function update_suggestions() {

	$a = get_app();

	$dirmode = get_config('system','directory_mode');
	if($dirmode === false)
		$dirmode = DIRECTORY_MODE_NORMAL;

	if(($dirmode == DIRECTORY_MODE_PRIMARY) || ($dirmode == DIRECTORY_MODE_STANDALONE)) {
		$url = z_root() . '/sitelist';
	}
	else {
		$directory = find_upstream_directory($dirmode);
		$url = $directory['url'] . '/sitelist';
	}
	if(! $url)
		return;



	$ret = z_fetch_url($url);

	if($ret['success']) {

		// We will grab fresh data once a day via the poller. Remove anything over a week old because
		// the targets may have changed their preferences and don't want to be suggested - and they 
		// may have simply gone away. 

		$r = q("delete from xlink where xlink_xchan = '' and xlink_updated < %s - INTERVAL %s and xlink_static = 0",
			db_utcnow(), db_quoteinterval('7 DAY')
		);


		$j = json_decode($ret['body'],true);
		if($j && $j['success']) {
			foreach($j['entries'] as $host) {
				poco_load('',$host['url'] . '/poco');
			}
		}
	}
}


function poco($a,$extended = false) {

	$system_mode = false;

	if(intval(get_config('system','block_public')) && (! local_channel()) && (! remote_channel())) {
		logger('mod_poco: block_public');
		http_status_exit(401);
	}

	$observer = $a->get_observer();

	if(argc() > 1) {
		$user = notags(trim(argv(1)));
	}
	if(! x($user)) {
		$c = q("select * from pconfig where cat = 'system' and k = 'suggestme' and v = '1'");
		if(! $c) {
			logger('mod_poco: system mode. No candidates.', LOGGER_DEBUG);
			http_status_exit(404);
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
		$sql_extra = " and ( abook_flags & " . ABOOK_FLAG_SELF . " )>0 ";
	else
		$sql_extra = " and abook_flags = 0 ";

	if($cid)
		$sql_extra = sprintf(" and abook_id = %d ",intval($cid));

	if($system_mode) {
		$r = q("SELECT count(*) as `total` from abook where ( abook_flags & " . ABOOK_FLAG_SELF . 
			" )>0 and abook_channel in (select uid from pconfig where cat = 'system' and k = 'suggestme' and v = '1') ");
	}
	else {
		$r = q("SELECT count(*) as `total` from abook where abook_channel = %d 
			$sql_extra ",
			intval($channel_id)
		);
		$rooms = q("select * from menu_item where ( mitem_flags & " . intval(MENU_ITEM_CHATROOM) . " )>0 and allow_cid = '' and allow_gid = '' and deny_cid = '' and deny_gid = '' and mitem_channel_id = %d",
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
			" )>0 and abook_channel in (select uid from pconfig where cat = 'system' and k = 'suggestme' and v = '1') limit %d offset %d ",
			intval($itemsPerPage),
			intval($startIndex)
		);
	}
	else {
		$r = q("SELECT abook.*, xchan.* from abook left join xchan on abook_xchan = xchan_hash where abook_channel = %d 
			$sql_extra LIMIT %d OFFSET %d",
			intval($channel_id),
			intval($itemsPerPage),
			intval($startIndex)
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

	if($rooms) {
		$ret['chatrooms'] = array();
		foreach($rooms as $room) {
			$ret['chatrooms'][] = array('url' => $room['mitem_link'], 'desc' => $room['mitem_desc']);
		}
	}

	$ret['entry']        = array();


	$fields_ret = array(
		'id' => false,
		'guid' => false,
		'guid_sig' => false,
		'hash' => false,
		'displayName' => false,
		'urls' => false,
		'preferredUsername' => false,
		'photos' => false,
		'rating' => false
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
					$network = $rr['xchan_network'];
					if(strpos($network,'friendica') !== false)
						$network = 'friendica';
					if($rr['xchan_addr'])
						$entry['urls'][] = array('value' => 'acct:' . $rr['xchan_addr'], 'type' => $network);  
				}
				if($fields_ret['preferredUsername'])
					$entry['preferredUsername'] = substr($rr['xchan_addr'],0,strpos($rr['xchan_addr'],'@'));
				if($fields_ret['photos'])
					$entry['photos'] = array(array('value' => $rr['xchan_photo_l'], 'mimetype' => $rr['xchan_photo_mimetype'], 'type' => 'profile'));
				if($fields_ret['rating']) {
					$entry['rating'] = ((array_key_exists('abook_rating',$rr)) ? intval($rr['abook_rating']) : 0);
					$entry['rating_text'] = ((array_key_exists('abook_rating_text',$rr)) ? $rr['abook_rating_text'] : '');
					// maybe this should be a composite calculated rating in $system_mode
					if($system_mode)
						$entry['rating'] = 0;
				}
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