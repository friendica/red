<?php /** @file */

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

	logger('poco_load: ' . print_r($j,true),LOGGER_DATA);

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
		$rating = ((array_key_exists('rating',$entry)) ? intval($entry['rating']) : 0);

		if(x($entry,'urls') && is_array($entry['urls'])) {
			foreach($entry['urls'] as $url) {
				if($url['type'] == 'profile') {
					$profile_url = $url['value'];
					continue;
				}
				if($url['type'] == 'zot') {
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
				continue;
			}
		}
	
		$total ++;


		$r = q("select * from xlink where xlink_xchan = '%s' and xlink_link = '%s' limit 1",
			dbesc($xchan),
			dbesc($hash)
		);

		if(! $r) {
			q("insert into xlink ( xlink_xchan, xlink_link, xlink_rating, xlink_updated ) values ( '%s', '%s', %d, '%s' ) ",
				dbesc($xchan),
				dbesc($hash),
				intval($rating),
				dbesc(datetime_convert())
			);
		}
		else {
			q("update xlink set xlink_updated = '%s', xlink_rating = %d where xlink_id = %d limit 1",
				dbesc(datetime_convert()),
				intval($rating),
				intval($r[0]['xlink_id'])
			);
		}
	}
	logger("poco_load: loaded $total entries",LOGGER_DEBUG);

	q("delete from xlink where xlink_xchan = '%s' and xlink_updated < UTC_TIMESTAMP() - INTERVAL 2 DAY",
		dbesc($xchan)
	);
}


function count_common_friends($uid,$xchan) {

	$r = q("SELECT count(xlink_id) as total from xlink where xlink_xchan = '%s' and xlink_link in
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

	if($shuffle)
		$sql_extra = " order by rand() ";
	else
		$sql_extra = " order by xchan_name asc "; 

	$r = q("SELECT * from xchan left join xlink on xlink_link = xchan_hash where xlink_xchan = '%s' and xlink_link in
		(select abook_xchan from abook where abook_xchan != '%s' and abook_channel = %d and abook_flags = 0 ) $sql_extra limit %d, %d",
		dbesc($xchan),
		dbesc($xchan),
		intval($uid),
		intval($start),
		intval($limit)
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
		$sql_extra limit %d, %d",
		intval($zcid),
		intval($uid),
		intval($start),
		intval($limit)
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
		order by `gcontact`.`name` asc LIMIT %d, %d ",
		intval($cid),
		intval($uid),
		intval($start),
		intval($limit)
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
		and not ( xchan_flags & %d )
		group by xchan_hash order by total desc limit %d, %d ",
		intval($uid),
		intval($uid),
		intval($uid),
		intval(XCHAN_FLAGS_HIDDEN),
		intval($start),
		intval($limit)
	);

	if($r && count($r) >= ($limit -1))
		return $r;

	$r2 = q("SELECT count(xlink_link) as `total`, xchan.* from xchan
		left join xlink on xlink_link = xchan_hash
		where xlink_xchan = ''
		and not xlink_link in ( select abook_xchan from abook where abook_channel = %d )
		and not xlink_link in ( select xchan from xign where uid = %d )
		and not ( xchan_flags & %d )
		group by xchan_hash order by total desc limit %d, %d ",
		intval($uid),
		intval($uid),
		intval(XCHAN_FLAGS_HIDDEN),
		intval($start),
		intval($limit)
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

		if($directory) {
			$url = $directory['url'] . '/sitelist';
		}
		else {
			$url = DIRECTORY_FALLBACK_MASTER . '/sitelist';
		}
	}
	if(! $url)
		return;



	$ret = z_fetch_url($url);

	if($ret['success']) {

		// We will grab fresh data once a day via the poller. Remove anything over a week old because
		// the targets may have changed their preferences and don't want to be suggested - and they 
		// may have simply gone away. 

		$r = q("delete from xlink where xlink_xchan = '' and xlink_updated < UTC_TIMESTAMP() - INTERVAL 7 DAY");


		$j = json_decode($ret['body'],true);
		if($j && $j['success']) {
			foreach($j['entries'] as $host) {
				poco_load('',$host['url'] . '/poco');
			}
		}
	}
}
