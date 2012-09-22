<?php

function magic_init(&$a) {

	$url = ((x($_REQUEST,'url')) ? $_REQUEST['url'] : '');


	if(local_user() && argc() > 1 && intval(argv(1))) {

		$cid = $argv(1);

		$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($cid),
			intval(local_user())
		);

		if(! ($r && count($r)))
			goaway(z_root());


		$sec = random_string();

		// Here's how it works in zot... still a fair bit of code to write
		// Create a random tracking code and store it
		// Originator (us) redirects to remote connect url with callback URL and tracking code.
		// Remote calls us back asynchronously to verify we sent the tracking code.
		// Reply with a json document providing the identity details
		// Remote verifies these match a known identity and the site matches a known location
		// (especially including the current location)
		// Once that has happened, the original redirect will be given an authenticated session 
		// and redirected to the chosen page.



		q("INSERT INTO `profile_check` ( `uid`, `cid`, `dfrn_id`, `sec`, `expire`)
			VALUES( %d, %s, '%s', '%s', %d )",
			intval(local_user()),
			intval($cid),
			dbesc($dfrn_id),
			dbesc($sec),
			intval(time() + 45)
		);

		$local_callback = z_root() . '/auth';

		logger('mod_magic: ' . $r[0]['name'] . ' ' . $sec, LOGGER_DEBUG); 
		$dest = (($url) ? '&url=' . urlencode($url) : '');
		goaway ($hubloc['hubloc_connect'] . "?f=&cb=" . urlencode($local_callback) . $dest . "&token=" . $token);

	}


	if(local_user())
		$handle = $a->user['nickname'] . '@' . substr($a->get_baseurl(),strpos($a->get_baseurl(),'://')+3);
	if(remote_user())
		$handle = $_SESSION['handle'];

	if($url) {
		$url = str_replace('{zid}','&zid=' . $handle,$url);
		goaway($url);
	}

	goaway(z_root());
}
