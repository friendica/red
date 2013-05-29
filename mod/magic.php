<?php

@require_once('include/zot.php');

function magic_init(&$a) {

	$addr = ((x($_REQUEST,'addr')) ? $_REQUEST['addr'] : '');
	$hash = ((x($_REQUEST,'hash')) ? $_REQUEST['hash'] : '');
	$dest = ((x($_REQUEST,'dest')) ? $_REQUEST['dest'] : '');

	if($hash) {
		$x = q("select xchan.xchan_url, hubloc.* from xchan left join hubloc on xchan_hash = hubloc_hash
			where hubloc_hash = '%s' and (hubloc_flags & %d) order by hubloc_id desc limit 1",
			dbesc($hash),
			intval(HUBLOC_FLAGS_PRIMARY)
		);
	}
	elseif($addr) {
		$x = q("select hubloc.* from xchan left join hubloc on xchan_hash = hubloc_hash 
			where xchan_addr = '%s' and (hubloc_flags & %d) order by hubloc_id desc limit 1",
			dbesc($addr),
			intval(HUBLOC_FLAGS_PRIMARY)
		);
	}
	else {
		// See if we know anybody at the dest site that will unlock the door for us
		$b = explode('/',$dest);
		if(count($b) >= 2)
		$u = $b[0] . '//' . $b[2];
		logger('mod_magic: fallback: ' . $b . ' -> ' . $u);

		$x = q("select xchan.xchan_url, hubloc.* from xchan left join hubloc on xchan_hash = hubloc_hash
			where hubloc_url = '%s' order by hubloc_id desc limit 1",
			dbesc($u)
		);

	}

	if(! $x) {

		// Finger them if they've never been seen here before

		if($addr) {
			$ret = zot_finger($addr,null);
			if($ret['success']) {
				$j = json_decode($ret['body'],true);
				if($j)
					import_xchan($j);
				$x = q("select hubloc.* from xchan left join hubloc on xchan_hash = hubloc_hash 
					where xchan_addr = '%s' and (hubloc_flags & %d) order by hubloc_id desc limit 1",
					dbesc($addr),
					intval(HUBLOC_FLAGS_PRIMARY)
				);
			}
		}
	}

	if(! $x) {
		logger('mod_magic: channel not found.' . print_r($_REQUEST,true));
		notice( t('Channel not found.') . EOL);
		return;
	}

	// This is ready-made for a plugin that provides a blacklist or "ask me" before blindly authenticating. 
	// By default, we'll proceed without asking.

	$arr = array(
		'channel_id' => local_user(),
		'xchan' => $x[0],
		'destination' => $dest, 
		'proceed' => true
	);

	call_hooks('magic_auth',$arr);
	$dest = $arr['destination'];
	if(! $arr['proceed'])
		goaway($dest);

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

	if(local_user()) {
		$channel = $a->get_channel();

		$token = random_string();
		$token_sig = base64url_encode(rsa_sign($token,$channel['channel_prvkey']));
 
		$channel['token'] = $token;
		$channel['token_sig'] = $token_sig;


		$recip = array(array('guid' => $x[0]['hubloc_guid'],'guid_sig' => $x[0]['hubloc_guid_sig']));

		$hash = random_string();

		$r = q("insert into verify ( type, channel, token, meta, created) values ('%s','%d','%s','%s','%s')",
			dbesc('auth'),
			intval($channel['channel_id']),
			dbesc($token),
			dbesc($x[0]['hubloc_hash']),
			dbesc(datetime_convert())
		);

		goaway($x[0]['hubloc_callback'] . '/' . substr($x[0]['hubloc_addr'],0,strpos($x[0]['hubloc_addr'],'@'))
			. '/?f=&auth=' . $channel['channel_address'] . '@' . $a->get_hostname()
			. '&sec=' . $token . '&dest=' . urlencode($dest) . '&version=' . ZOT_REVISION);
	}

	if(strpos($dest,'/'))
		goaway($dest);
	goaway(z_root());
}
