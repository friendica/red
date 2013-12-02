<?php

@require_once('include/zot.php');

function magic_init(&$a) {

	logger('mod_magic: invoked', LOGGER_DEBUG);

	logger('mod_magic: args: ' . print_r($_REQUEST,true),LOGGER_DATA);

	$addr = ((x($_REQUEST,'addr')) ? $_REQUEST['addr'] : '');
	$hash = ((x($_REQUEST,'hash')) ? $_REQUEST['hash'] : '');
	$dest = ((x($_REQUEST,'dest')) ? $_REQUEST['dest'] : '');
	$rev  = ((x($_REQUEST,'rev'))  ? intval($_REQUEST['rev']) : 0);

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
		// This is the equivalent of buzzing every apartment in an apartment block
		// to get inside the front gate. The thing about magic auth is that we're 
		// authenticating to the other site. Permissions provided by various 
		// channels will still affect what we can do once authenticated.

		$b = explode('/',$dest);

		if(count($b) >= 2) {
			$u = $b[0] . '//' . $b[2];

			if(local_user()) {
				// first look for a connection or anybody who knows us
				$x = q("select xchan.xchan_url, hubloc.* from xchan left join hubloc on xchan_hash = hubloc_hash 
					left join abook on abook_xchan = hubloc_hash
					where abook_channel = %d and hubloc_url = '%s' order by hubloc_id desc limit 5",
					intval(local_user()),
					dbesc($u)
				);
			}
			if(! $x) {
				// no luck - ok anybody will do
				$x = q("select xchan.xchan_url, hubloc.* from xchan left join hubloc on xchan_hash = hubloc_hash
					where hubloc_url = '%s' order by hubloc_id desc limit 5",
					dbesc($u)
				);
			}

			if($x) {
				// They must have a valid hubloc_addr
				while(! strpos($x[0]['hubloc_addr'],'@')) {
					array_shift($x);
				}
			}


		}
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
		if($rev)
			goaway($dest);
		else {
			logger('mod_magic: no channels found for requested hub.' . print_r($_REQUEST,true));
			notice( t('Hub not found.') . EOL);
			return;
		}
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
		// We are already authenticated on this site and a registered observer.
		// Just redirect.
		goaway($dest);
	}

	if(local_user()) {
		$channel = $a->get_channel();

		$token = random_string();
		$token_sig = base64url_encode(rsa_sign($token,$channel['channel_prvkey']));
 
		$channel['token'] = $token;
		$channel['token_sig'] = $token_sig;

		$r = q("insert into verify ( type, channel, token, meta, created) values ('%s','%d','%s','%s','%s')",
			dbesc('auth'),
			intval($channel['channel_id']),
			dbesc($token),
			dbesc($x[0]['hubloc_url']),
			dbesc(datetime_convert())
		);

		$target_url = $x[0]['hubloc_callback'];
		logger('mod_magic: redirecting to: ' . $target_url, LOGGER_DEBUG); 

		goaway($target_url
			. '/?f=&auth=' . urlencode($channel['channel_address'] . '@' . $a->get_hostname())
			. '&sec=' . $token . '&dest=' . urlencode($dest) . '&version=' . ZOT_REVISION);
	}

	if(strpos($dest,'/'))
		goaway($dest);
	goaway(z_root());
}
