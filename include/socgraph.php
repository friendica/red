<?php

require_once('include/datetime.php');


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
 



function poco_load($cid,$uid = 0,$url = null) {
	$a = get_app();
	if((! $url) || (! $uid)) {
		$r = q("select `poco`, `uid` from `contact` where `id` = %d limit 1",
			intval($cid)
		);
		if(count($r)) {
			$url = $r[0]['poco'];
			$uid = $r[0]['uid'];
		}
	}
	if((! $url) || (! $uid))
		return;
	$s = fetch_url($url . '/@me/@all?fields=displayName,urls,photos');

	if(($a->get_curl_code() > 299) || (! $s))
		return;
	$j = json_decode($s);
	foreach($j->entry as $entry) {

		$profile_url = '';
		$profile_photo = '';
		$name = '';

		$name = $entry->displayName;

		foreach($entry->urls as $url) {
			if($url->type == 'profile') {
				$profile_url = $url->value;
				break;
			}
		} 
		foreach($entry->photos as $photo) {
			if($photo->type == 'profile') {
				$profile_photo = $photo->value;
				break;
			}
		}

		if((! $name) || (! $profile_url) || (! $profile_photo))
			continue; 
		 
		$x = q("select * from `gcontact` where `nurl` = '%s' limit 1",
			dbesc(normalise_link($profile_url))
		);
		if(count($x)) {
			$gcid = $x[0]['id'];

			if($x[0]['name'] != $name || $x[0]['photo'] != $profile_photo) {
				q("update gcontact set `name` = '%s', `photo` = '%s' where
					`nurl` = '%s' limit 1",
					dbesc($name),
					dbesc($profile_photo),
					dbesc(normalise_link($profile_url))
				);
			}
		}
		else {
			q("insert into `gcontact` (`name`,`url`,`nurl`,`photo`)
				values ( '%s', '%s', '%s', '%s') ",
				dbesc($name),
				dbesc($profile_url),
				dbesc(normalise_link($profile_url)),
				dbesc($profile_photo)
			);
			$x = q("select * from `gcontact` where `nurl` = '%s' limit 1",
				dbesc(normalise_link($profile_url))
			);
			if(count($x))
				$gcid = $x[0]['id'];
		}
		if(! $gcid)
			return;

		$r = q("select * from glink where `cid` = %d and `uid` = %d and `gcid` = %d limit 1",
			intval($cid),
			intval($uid),
			intval($gcid)
		);
		if(! count($r)) {
			q("insert into glink ( `cid`,`uid`,`gcid`,`updated`) values (%d,%d,%d,'%s') ",
				intval($cid),
				intval($uid),
				intval($gcid),
				dbesc(datetime_convert())
			);
		}
		else {
			q("update glink set updated = '%s' where `cid` = %d and `uid` = %d and `gcid` = %d limit 1",
				dbesc(datetime_convert()),
				intval($cid),
				intval($uid),
				intval($gcid)
			);
		}

	}

	q("delete from gcid where `cid` = %d and `uid` = %d and `updated` < UTC_TIMESTAMP - INTERVAL 2 DAY",
		intval($cid),
		intval($uid)
	);

}