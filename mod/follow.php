<?php

require_once('Scrape.php');

function follow_post(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	$url = $orig_url = notags(trim($_POST['url']));
	
	$email_conversant = false;

	if($url) {
		$links = @lrdd($url);
		if(count($links)) {
			foreach($links as $link) {
				if($link['@attributes']['rel'] === NAMESPACE_DFRN)
					$dfrn = unamp($link['@attributes']['href']);
				if($link['@attributes']['rel'] === 'salmon')
					$notify = unamp($link['@attributes']['href']);
				if($link['@attributes']['rel'] === NAMESPACE_FEED)
					$poll = unamp($link['@attributes']['href']);
				if($link['@attributes']['rel'] === 'http://microformats.org/profile/hcard')
					$hcard = unamp($link['@attributes']['href']);
				if($link['@attributes']['rel'] === 'http://webfinger.net/rel/profile-page')
					$profile = unamp($link['@attributes']['href']);

			}

			// Status.Net can have more than one profile URL. We need to match the profile URL
			// to a contact on incoming messages to prevent spam, and we won't know which one
			// to match. So in case of two, one of them is stored as an alias. Only store URL's
			// and not webfinger user@host aliases. If they've got more than two non-email style
			// aliases, let's hope we're lucky and get one that matches the feed author-uri because 
			// otherwise we're screwed.

			foreach($links as $link) {
				if($link['@attributes']['rel'] === 'alias') {
					if(strpos($link['@attributes']['href'],'@') === false) {
						if(isset($profile)) {
							if($link['@attributes']['href'] !== $profile)
								$alias = unamp($link['@attributes']['href']);
						}
						else
							$profile = unamp($link['@attributes']['href']);
					}
				}
			}
		}
		else {
			if((strpos($orig_url,'@')) && validate_email($orig_url)) {
				$email_conversant = true;
			}
		}
	}	

	// If we find a DFRN site, send our subscriber to the other person's
	// dfrn_request page and all the other details will get sorted.

	if(strlen($dfrn)) {
		$ret = scrape_dfrn($dfrn);
		if(is_array($ret) && x($ret,'dfrn-request')) {
			if(strlen($a->path))
				$myaddr = bin2hex($a->get_baseurl() . '/profile/' . $a->user['nickname']);
			else
				$myaddr = bin2hex($a->user['nickname'] . '@' . $a->get_hostname());
 
			goaway($ret['dfrn-request'] . "&addr=$myaddr");
		
			// NOTREACHED
		}
	}

	$network  = 'stat';
	$priority = 0;

	if($hcard) {
		$vcard = scrape_vcard($hcard);

		// Google doesn't use absolute url in profile photos

		if((x($vcard,'photo')) && substr($vcard['photo'],0,1) == '/') {
			$h = parse_url($hcard);
			if($h)
				$vcard['photo'] = $h['scheme'] . '://' . $h['host'] . $vcard['photo'];
		}
	}

	if(! $profile)
		$profile = $url;


	if(! x($vcard,'fn'))
		if(x($vcard,'nick'))
			$vcard['fn'] = $vcard['nick'];

	if((! isset($vcard)) && (! $poll)) {

		$ret = scrape_feed($url);

		if(count($ret) && ($ret['feed_atom'] || $ret['feed_rss'])) {
			$poll = ((x($ret,'feed_atom')) ? unamp($ret['feed_atom']) : unamp($ret['feed_rss']));
			$vcard = array();
			require_once('simplepie/simplepie.inc');
		    $feed = new SimplePie();
			$xml = fetch_url($poll);

    		$feed->set_raw_data($xml);

		    $feed->init();

			$vcard['photo'] = $feed->get_image_url();
			$author = $feed->get_author();
			if($author) {			
				$vcard['fn'] = unxmlify(trim($author->get_name()));
				if(! $vcard['fn'])
					$vcard['fn'] = trim(unxmlify($author->get_email()));
				if(strpos($vcard['fn'],'@') !== false)
					$vcard['fn'] = substr($vcard['fn'],0,strpos($vcard['fn'],'@'));
				$vcard['nick'] = strtolower(notags(unxmlify($vcard['fn'])));
				if(strpos($vcard['nick'],' '))
					$vcard['nick'] = trim(substr($vcard['nick'],0,strpos($vcard['nick'],' ')));
				$email = unxmlify($author->get_email());
			}
			else {
				$item = $feed->get_item(0);
				if($item) {
					$author = $item->get_author();
					if($author) {			
						$vcard['fn'] = trim(unxmlify($author->get_name()));
						if(! $vcard['fn'])
							$vcard['fn'] = trim(unxmlify($author->get_email()));
						if(strpos($vcard['fn'],'@') !== false)
							$vcard['fn'] = substr($vcard['fn'],0,strpos($vcard['fn'],'@'));
						$vcard['nick'] = strtolower(unxmlify($vcard['fn']));
						if(strpos($vcard['nick'],' '))
							$vcard['nick'] = trim(substr($vcard['nick'],0,strpos($vcard['nick'],' ')));
						$email = unxmlify($author->get_email());
					}
					if(! $vcard['photo']) {
						$rawmedia = $item->get_item_tags('http://search.yahoo.com/mrss/','thumbnail');
						if($rawmedia && $rawmedia[0]['attribs']['']['url'])
							$vcard['photo'] = unxmlify($rawmedia[0]['attribs']['']['url']);
					}
				}
			}
			if((! $vcard['photo']) && strlen($email))
				$vcard['photo'] = gravatar_img($email);
			
			$network = 'feed';
			$priority = 2;
		}
	}

	logger('follow: poll=' . $poll . ' notify=' . $notify . ' profile=' . $profile . ' vcard=' . print_r($vcard,true));

	$vcard['fn'] = notags($vcard['fn']);
	$vcard['nick'] = notags($vcard['nick']);

	// do we have enough information?
	
	if(! ((x($vcard['fn'])) && ($poll) && ($profile))) {
		notice( t('The profile address specified does not provide adequate information.') . EOL);
		goaway($_SESSION['return_url']);
	}


	if(! $notify) {
		notice( t('Limited profile. This person will be unable to receive direct/personal notifications from you.') . EOL);
	}

	if(! x($vcard,'photo'))
		$vcard['photo'] = $a->get_baseurl() . '/images/default-profile.jpg' ; 

	// check if we already have a contact
	// the poll url is more reliable than the profile url, as we may have
	// indirect links or webfinger links

	$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `poll` = '%s' LIMIT 1",
		intval(local_user()),
		dbesc($poll)
	);			

	if(count($r)) {
		// update contact
		if($r[0]['rel'] == REL_VIP) {
			q("UPDATE `contact` SET `rel` = %d , `readonly` = 0 WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval(REL_BUD),
				intval($r[0]['id']),
				intval(local_user())
			);
		}
	}
	else {
		// create contact record 
		$r = q("INSERT INTO `contact` ( `uid`, `created`, `url`, `alias`, `notify`, `poll`, `name`, `nick`, `photo`, `network`, `rel`, `priority`,
			`blocked`, `readonly`, `pending` )
			VALUES ( %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, 0, 0, 0 ) ",
			intval(local_user()),
			dbesc(datetime_convert()),
			dbesc($profile),
			dbesc($alias),
			dbesc($notify),
			dbesc($poll),
			dbesc($vcard['fn']),
			dbesc($vcard['nick']),
			dbesc($vcard['photo']),
			dbesc($network),
			intval(REL_FAN),
			intval($priority)
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

	$photos = import_profile_photo($vcard['photo'],local_user(),$contact_id);

	$r = q("UPDATE `contact` SET `photo` = '%s', 
			`thumb` = '%s',
			`micro` = '%s', 
			`name-date` = '%s', 
			`uri-date` = '%s', 
			`avatar-date` = '%s'
			WHERE `id` = %d LIMIT 1
		",
			dbesc($photos[0]),
			dbesc($photos[1]),
			dbesc($photos[2]),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			intval($contact_id)
		);			


	// pull feed and consume it, which should subscribe to the hub.

	$php_path = ((x($a->config,'php_path') && strlen($a->config['php_path'])) ? $a->config['php_path'] : 'php');
	//proc_close(proc_open("\"$php_path\" \"include/poller.php\" \"$contact_id\" &", array(), $foo));
	proc_run($php_path,"include/poller.php","$contact_id");

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
		'$verb' => ACTIVITY_FOLLOW,
		'$ostat_follow' => ''
	));

	$r = q("SELECT `contact`.*, `user`.* FROM `contact` LEFT JOIN `user` ON `contact`.`uid` = `user`.`uid` 
			WHERE `user`.`uid` = %d AND `contact`.`self` = 1 LIMIT 1",
			intval(local_user())
	);


	if((count($r)) && (x($contact,'notify')) && (strlen($contact['notify']))) {
		require_once('include/salmon.php');
		slapper($r[0],$contact['notify'],$slap);
	}

	goaway($_SESSION['return_url']);
	// NOTREACHED
}
