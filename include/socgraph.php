<?php

require_once('include/zot.php');

/*
 * poco_load
 *
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
 



function poco_load($xchan = null,$url = null) {
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


	$url = $url . '?f=&fields=displayName,hash,urls,photos' ;

	logger('poco_load: ' . $url, LOGGER_DEBUG);

	$s = z_fetch_url($url);


	if(! $s['success']) {
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

		$total ++;
		$profile_url = '';
		$profile_photo = '';
		$address = '';
		$name = '';
		$hash = '';

		$name = $entry['displayName'];
		$hash = $entry['hash'];

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
			}
		}
	

		if($xchan) {
			$r = q("select * from xlink where xlink_xchan = '%s' and xlink_link = '%s' limit 1",
				dbesc($xchan),
				dbesc($hash)
			);
			if(! $r) {
				q("insert into xlink ( xlink_xchan, xlink_link, xlink_updated ) values ( '%s', '%s', '%s' ) ",
					dbesc($xchan),
					dbesc($hash),
					dbesc(datetime_convert())
				);
			}
			else {
				q("update xlink set xlink_updated = '%s' where xlink_id = %d limit 1",
					dbesc(datetime_convert()),
					intval($r[0]['xlink_id'])
				);
			}
		}
	}
	logger("poco_load: loaded $total entries",LOGGER_DEBUG);

	q("delete from xlink where xlink_xchan = '%s' and xlink_updated` < UTC_TIMESTAMP() - INTERVAL 2 DAY",
		dbesc($xchan)
	);
}


function count_common_friends($uid,$xchan) {

	$r = q("SELECT count(xlink_id) as total from xlink where xlink_xchan = '%s' and xlink_link in
		(select abook_chan from abook where abook_xchan != '%s' and abook_channel = %d and abook_flags = 0 )",
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

	$r = q("SELECT * from xchan left join xlink on xlink_xchan = xchan_hash where xlink_xchan = '%s' and xlink_link in
		(select abook_chan from abook where abook_xchan != '%s' and abook_channel = %d and abook_flags = 0 ) $sql_extra limit %d, %d",
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



function suggestion_query($uid, $start = 0, $limit = 80) {

	if(! $uid)
		return array();

	$r = q("SELECT count(glink.gcid) as `total`, gcontact.* from gcontact 
		left join glink on glink.gcid = gcontact.id 
		where uid = %d and not gcontact.nurl in ( select nurl from contact where uid = %d )
		and not gcontact.name in ( select name from contact where uid = %d )
		and not gcontact.id in ( select gcid from gcign where uid = %d )
		group by glink.gcid order by total desc limit %d, %d ",
		intval($uid),
		intval($uid),
		intval($uid),
		intval($uid),
		intval($start),
		intval($limit)
	);

	if(count($r) && count($r) >= ($limit -1))
		return $r;

	$r2 = q("SELECT gcontact.* from gcontact 
		left join glink on glink.gcid = gcontact.id 
		where glink.uid = 0 and glink.cid = 0 and glink.zcid = 0 and not gcontact.nurl in ( select nurl from contact where uid = %d )
		and not gcontact.name in ( select name from contact where uid = %d )
		and not gcontact.id in ( select gcid from gcign where uid = %d )
		order by rand() limit %d, %d ",
		intval($uid),
		intval($uid),
		intval($uid),
		intval($start),
		intval($limit)
	);


	return array_merge($r,$r2);

}

function update_suggestions() {

// FIXME
return;
	$a = get_app();

	$done = array();

	// fix this to get a json list from an upstream directory
//	poco_load(0,0,0,$a->get_baseurl() . '/poco');

//	$done[] = $a->get_baseurl() . '/poco';

//	if(strlen(get_config('system','directory_submit_url'))) {
//		$x = fetch_url('http://dir.friendica.com/pubsites');
//		if($x) {
//			$j = json_decode($x);
//			if($j->entries) {
//				foreach($j->entries as $entry) {
//					$url = $entry->url . '/poco';
//					if(! in_array($url,$done))
//						poco_load(0,0,0,$entry->url . '/poco');
//				}
//			}
//		}
//	}

	$r = q("select distinct(xchan_connurl) as poco from xchan where xchan_network = 'zot'");

	if($r) {
		foreach($r as $rr) {
			$base = substr($rr['poco'],0,strrpos($rr['poco'],'/'));
			if(! in_array($base,$done))
				poco_load('',$base);
		}
	}
}
