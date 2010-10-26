<?php

require_once('Scrape.php');

function follow_post(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	$url = notags(trim($_POST['url']));

	if($url) {
		$links = lrdd($url);
		if(count($links)) {
			foreach($links as $link) {
				if($link['@attributes']['rel'] === NAMESPACE_DFRN)
					$dfrn = $link['@attributes']['href'];
				if($link['@attributes']['rel'] === 'salmon')
					$notify = $link['@attributes']['href'];
				if($link['@attributes']['rel'] === NAMESPACE_FEED)
					$poll = $link['@attributes']['href'];
				if($link['@attributes']['rel'] === 'http://microformats.org/profile/hcard')
					$hcard = $link['@attributes']['href'];
				if($link['@attributes']['rel'] === 'http://webfinger.net/rel/profile-page')
					$profile = $link['@attributes']['href'];

			}
		}
	}	

	// If we find a DFRN site, send our subscriber to the other person's
	// dfrn_request page and all the other details will get sorted.

	if(strlen($dfrn)) {
		$ret = scrape_dfrn($dfrn);
		if(is_array($ret) && x($ret,'dfrn-request')) {
			if(strlen($a->path))
				$myaddr = urlencode($a->get_baseurl() . '/profile/' . $a->user['nickname']);
			else
				$myaddr = urlencode($a->user['nickname'] . '@' . $a->get_hostname());
 
			goaway($ret['dfrn-request'] . "&address=$myaddr");
		
			// NOTREACHED
		}
	}

	if($hcard) {
		$vcard = scrape_vcard($hcard);
	}

	if(! $profile)
		$profile = $url;

	// do we have enough information?

	if(! x($vcard,'fn'))
		if(x($vcard,'nick'))
			$vcard['fn'] = $vcard['nick'];

	if(! ((x($vcard['fn'])) && ($poll) && ($notify) && ($profile))) {
		notice( t('The profile address specified does not provide adequate information.') . EOL);
		goaway($_SESSION['return_url']);
	} 

	if(! x($vcard,'photo'))
		$vcard['photo'] = $a->get_baseurl() . '/images/default-profile.jpg' ; 

	// check if we already have a contact

	$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `poll` = '%s' LIMIT 1",
		intval(local_user()),
		dbesc($poll)
	);			
	if($r) {
		// update contact
		if($r[0]['rel'] == REL_VIP) {
			q("UPDATE `contact` SET `rel` = %d WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval(REL_BUD),
				intval($r[0]['id']),
				intval(local_user())
			);
		}
	}
	else {
		// create contact record 
		$r = q("INSERT INTO `contact` ( `uid`, `created`, `url`, `notify`, `poll`, `name`, `nick`, `photo`, `network`, `rel`, 
			`blocked`, `readonly`, `pending` )
			VALUES ( %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, 0, 0, 0 ) ",
			intval(local_user()),
			dbesc(datetime_convert()),
			dbesc($profile),
			dbesc($notify),
			dbesc($poll),
			dbesc($vcard['fn']),
			dbesc($vcard['nick']),
			dbesc($vcard['photo']),
			dbesc('stat'),
			intval(REL_FAN)
		);
	}
	$r = q("SELECT * FROM `contact` WHERE `url` = '%s' AND `uid` = %d LIMIT 1",
		dbesc($profile),
		intval(local_user())
	);
	if(! count($r)) {
		notice( t('Unable to retrieve contact information.') . EOL);
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	$contact = $r[0];
	$contact_id  = $r[0]['id'];

	require_once("Photo.php");

	$photo_failure = false;

	$filename = basename($vcard['photo']);
	$img_str = fetch_url($vcard['photo'],true);
	$img = new Photo($img_str);
	if($img->is_valid()) {

		$img->scaleImageSquare(175);
					
		$hash = photo_new_resource();

		$r = $img->store(local_user(), $contact_id, $hash, $filename, t('Contact Photos'), 4 );

		if($r === false)
			$photo_failure = true;

		$img->scaleImage(80);

		$r = $img->store(local_user(), $contact_id, $hash, $filename, t('Contact Photos'), 5 );

		if($r === false)
			$photo_failure = true;

		$photo = $a->get_baseurl() . '/photo/' . $hash . '-4.jpg';
		$thumb = $a->get_baseurl() . '/photo/' . $hash . '-5.jpg';
	}
	else
		$photo_failure = true;

	if($photo_failure) {
		$photo = $a->get_baseurl() . '/images/default-profile.jpg';
		$thumb = $a->get_baseurl() . '/images/default-profile-sm.jpg';
	}

	$r = q("UPDATE `contact` SET `photo` = '%s', 
			`thumb` = '%s', 
			`name-date` = '%s', 
			`uri-date` = '%s', 
			`avatar-date` = '%s'
			WHERE `id` = %d LIMIT 1
		",
			dbesc($photo),
			dbesc($thumb),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			intval($contact_id)
		);			


	// pull feed and consume it, which should subscribe to the hub.


	// create a follow slap

	$tpl = load_view_file('view/follow_slap.tpl');
	$slap = replace_macros($tpl, array(
		'$name' => $a->user['username'],
		'$profile_page' => $a->get_baseurl() . '/profile/' . $a->user['nickname'],
		'$photo' => $a->contact['photo'],
		'$thumb' => $a->contact['thumb'],
		'$published' => datetime_convert('UTC','UTC', 'now', ATOM_TIME),
		'$item_id' => 'urn:X-dfrn:' . $a->get_hostname() . ':follow:' . random_string(),
		'$title' => '',
		'$type' => 'text',
		'$content' => t('following'),
		'$nick' => $a->user['nickname'],
		'$verb' => ACTIVITY_FOLLOW
	));

	$r = q("SELECT `contact`.*, `user`.* FROM `contact` LEFT JOIN `user` ON `contact`.`uid` = `user`.`uid` 
			WHERE `user`.`uid` = %d AND `contact`.`self` = 1 LIMIT 1",
			intval(local_user())
	);

	require_once('include/salmon.php');
	slapper($r[0],$contact,$slap);

	goaway($_SESSION['return_url']);
	// NOTREACHED
}
