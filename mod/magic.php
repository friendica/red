<?php

function magic_init(&$a) {

	$url = ((x($_REQUEST,'url')) ? $_REQUEST['url'] : '');


	if(local_user() && $argc() > 1 && intval(argv(1))) {

		$cid = $argv(1);

		$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($cid),
			intval(local_user())
		);

		if(! ($r && count($r)))
			goaway(z_root());


		$sec = random_string();

		// Here's how it works in zot... still a fair bit of code to write
		// Originator (us) posts our id/sig/location/location_sig with a random tracking code.
		// The other site will call us back asynchronously and do the verification dance.
		// Once that has happened, we will be issued an encrypted token
		// We'll redirect to the site with the decrypted token (which is good for one use).  




		q("INSERT INTO `profile_check` ( `uid`, `cid`, `dfrn_id`, `sec`, `expire`)
			VALUES( %d, %s, '%s', '%s', %d )",
			intval(local_user()),
			intval($cid),
			dbesc($dfrn_id),
			dbesc($sec),
			intval(time() + 45)
		);



		$postvars = array();

		$postvars['tracking'] = $sec;
		

		$ret = $z_post_url($hubloc['hubloc_connect'],$postvars);
		if($ret['success']) {
			$j = json_decode($ret['body']);
			if($j->result && $j->token) {
				$token = openssl_private_decrypt($j->token,$channel['prvkey']);





				logger('mod_magic: ' . $r[0]['name'] . ' ' . $sec, LOGGER_DEBUG); 
				$dest = (($url) ? '&destination_url=' . $url : '');
				goaway ($hubloc['hubloc_connect'] . "?f=" . $dest . "&token=" . $token);
			}

		}
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
