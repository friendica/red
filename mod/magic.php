<?php

function magic_init(&$a) {

	$url = ((x($_REQUEST,'url')) ? $_REQUEST['url'] : '');
	$addr = ((x($_REQUEST,'addr')) ? $_REQUEST['addr'] : '');
	$hash = ((x($_REQUEST,'hash')) ? $_REQUEST['hash'] : '');
	$dest = ((x($_REQUEST,'dest')) ? $_REQUEST['dest'] : '');


	if(local_user()) { 

		if($hash) {
			$x = q("select xchan.xchan_url, hubloc.* from xchan left join hubloc on xchan_hash = hubloc_hash
				where hublock_hash = '%s' and (hubloc_flags & %d) limit 1",
				intval(HUBLOC_FLAGS_PRIMARY)
			);
		}
		elseif($addr) {
			$x = q("select hubloc.* from xchan left join hubloc on xchan_hash = hubloc_hash 
				where xchan_addr = '%s' and (hubloc_flags & %d) limit 1",
				dbesc($addr),
				intval(HUBLOC_FLAGS_PRIMARY)
			);
		}

		if(! $x) {
			notice( t('Channel not found.') . EOL);
			return;
		}

		if($x[0]['hubloc_url'] === z_root()) {
			$webbie = substr($x[0]['hubloc_addr'],0,strpos('@',$x[0]['hubloc_addr']));
			switch($dest) {
				case 'channel':
					$desturl = z_root() . '/channel/' . $webbie;
					break;
				case 'photos':
					$desturl = z_root() . '/photos/' . $webbie;
					break;
				case 'profile':
					$desturl = z_root() . '/profile/' . $webbie;
					break;
				default:
					$desturl = $dest;
					break;
			}
			// We are already authenticated on this site and a registered observer.
			// Just redirect.
			goaway($desturl);
		}

 

				
	

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
