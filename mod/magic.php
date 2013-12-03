<?php

@require_once('include/zot.php');

function magic_init(&$a) {

	logger('mod_magic: invoked', LOGGER_DEBUG);

	logger('mod_magic: args: ' . print_r($_REQUEST,true),LOGGER_DATA);

	$addr = ((x($_REQUEST,'addr')) ? $_REQUEST['addr'] : '');
	$hash = ((x($_REQUEST,'hash')) ? $_REQUEST['hash'] : '');
	$dest = ((x($_REQUEST,'dest')) ? $_REQUEST['dest'] : '');
	$rev  = ((x($_REQUEST,'rev'))  ? intval($_REQUEST['rev']) : 0);


	$parsed = parse_url($dest);
	if(! $parsed)
		goaway($dest);

	$basepath = $parsed['scheme'] . '://' . $parsed['host'] . (($parsed['port']) ? ':' . $parsed['port'] : ''); 

	$x = q("select * from hubloc where hubloc_url = '%s' order by hubloc_connected desc limit 1"
		dbesc($basepath)
	);
	
	if(! $x) {

		// Somebody new? Finger them if they've never been seen here before

		if($addr) {
			$ret = zot_finger($addr,null);
			if($ret['success']) {
				$j = json_decode($ret['body'],true);
				if($j)
					import_xchan($j);

				// Now try again

				$x = q("select * from hubloc where hubloc_url = '%s' order by hubloc_connected desc limit 1"
					dbesc($basepath)
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

	if((get_observer_hash()) && ($x[0]['hubloc_url'] === z_root())) {
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

	goaway($dest);

}
