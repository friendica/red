<?php


require_once('library/openid/openid.php');
require_once('include/auth.php');

function openid_content(&$a) {

	$noid = get_config('system','disable_openid');
	if($noid)
		goaway(z_root());

	logger('mod_openid ' . print_r($_REQUEST,true), LOGGER_DATA);

	if(x($_REQUEST,'openid_mode')) {

		$openid = new LightOpenID(z_root());

		if($openid->validate()) {

			logger('openid: validate');

			$authid = normalise_openid($_REQUEST['openid_identity']);

			if(! strlen($authid)) {
				logger( t('OpenID protocol error. No ID returned.') . EOL);
				goaway(z_root());
			}
			
			$x = match_openid($authid);
			if($x) {	

				$r = q("select * from channel where channel_id = %d limit 1",
					intval($x)
				);
				if($r) {
					$y = q("select * from account where account_id = %d limit 1",
						intval($r[0]['channel_account_id'])
					);
					if($y) {
					    foreach($y as $record) {
					        if(($record['account_flags'] == ACCOUNT_OK) || ($record['account_flags'] == ACCOUNT_UNVERIFIED)) {
			            		logger('mod_openid: openid success for ' . $x[0]['channel_name']);
								$_SESSION['uid'] = $r[0]['channel_id'];
								$_SESSION['account_id'] = $r[0]['channel_account_id'];
								$_SESSION['authenticated'] = true;
								authenticate_success($record,true,true,true,true);
								goaway(z_root());
							}
						}
					}
				}
			}

			// Successful OpenID login - but we can't match it to an existing account.
			// See if they've got an xchan

			$r = q("select * from xconfig left join xchan on xchan_hash = xconfig.xchan where cat = 'system' and k = 'openid' and v = '%s' limit 1",
				dbesc($authid)
			);				

			if($r) {
				$_SESSION['authenticated'] = 1;
				$_SESSION['visitor_id'] = $r[0]['xchan_hash'];
				$_SESSION['my_address'] = $r[0]['xchan_addr'];
				$arr = array('xchan' => $r[0], 'session' => $_SESSION);
				call_hooks('magic_auth_openid_success',$arr);
				$a->set_observer($r[0]);
				require_once('include/security.php');
				$a->set_groups(init_groups_visitor($_SESSION['visitor_id']));
				info(sprintf( t('Welcome %s. Remote authentication successful.'),$r[0]['xchan_name']));
				logger('mod_openid: remote auth success from ' . $r[0]['xchan_addr']); 
				if($_SESSION['return_url'])
					goaway($_SESSION['return_url']);
				goaway(z_root());
			}

			// no xchan...
			// create one.
			// We should probably probe the openid url and figure out if they have any kind of social presence we might be able to 
			// scrape some identifying info from. 

			$name = $authid;
			$url = trim($_REQUEST['openid_identity'],'/');
			if(strpos($url,'http') === false)
				$url = 'https://' . $url;
			$pphoto = get_default_profile_photo();
			$parsed = @parse_url($url);
			if($parsed) {
				$host = $parsed['host'];
			}

			$attr = $openid->getAttributes();

			if(is_array($attr) && count($attr)) {
				foreach($attr as $k => $v) {
					if($k === 'namePerson/friendly')
						$nick = notags(trim($v));
					if($k === 'namePerson/first')
						$first = notags(trim($v));
					if($k === 'namePerson')
						$name = notags(trim($v));
					if($k === 'contact/email')
						$addr = notags(trim($v));
					if($k === 'media/image/aspect11')
						$photosq = trim($v);
					if($k === 'media/image/default')
						$photo_other = trim($v);
				}
			}
			if(! $nick) {
				if($first)
					$nick = $first;
				else
					$nick = $name;
			}

			require_once('library/urlify/URLify.php');
			$x = strtolower(URLify::transliterate($nick));
			if($nick & $host)
				$addr = $nick . '@' . $host;
			$network = 'unknown';
			
			if($photosq)
				$pphoto = $photosq;
			elseif($photo_other)
				$pphoto = $photo_other;

	        $x = q("insert into xchan ( xchan_hash, xchan_guid, xchan_guid_sig, xchan_pubkey, xchan_photo_mimetype,
                xchan_photo_l, xchan_addr, xchan_url, xchan_connurl, xchan_follow, xchan_connpage, xchan_name, xchan_network, xchan_photo_date, 
				xchan_name_date, xchan_flags)
                values ( '%s', '%s', '%s', '%s' , '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d) ",
	            dbesc($url),
    	        dbesc(''),
        	    dbesc(''),
            	dbesc(''),
	            dbesc('image/jpeg'),
    	        dbesc($pphoto),
        	    dbesc($addr),
            	dbesc($url),
	            dbesc(''),
    	        dbesc(''),
        	    dbesc(''),
            	dbesc($name),
	            dbesc($network),
    	        dbesc(datetime_convert()),
        	    dbesc(datetime_convert()),
            	intval(XCHAN_FLAGS_HIDDEN)
        	);
			if($x) {
				$r = q("select * from xchan where xchan_hash = '%s' limit 1",
					dbesc($url)
				);
				if($r) {

					$photos = import_profile_photo($pphoto,$url);
					if($photos) {
						$z = q("update xchan set xchan_photo_date = '%s', xchan_photo_l = '%s', xchan_photo_m = '%s', 
							xchan_photo_s = '%s', xchan_photo_mimetype = '%s' where xchan_hash = '%s' limit 1",
							dbesc(datetime_convert()),
							dbesc($photos[0]),
							dbesc($photos[1]),
							dbesc($photos[2]),
							dbesc($photos[3]),
							dbesc($url)
	            		);
					}

					set_xconfig($url,'system','openid',$authid);
					$_SESSION['authenticated'] = 1;
					$_SESSION['visitor_id'] = $r[0]['xchan_hash'];
					$_SESSION['my_address'] = $r[0]['xchan_addr'];
					$arr = array('xchan' => $r[0], 'session' => $_SESSION);
					call_hooks('magic_auth_openid_success',$arr);
					$a->set_observer($r[0]);
					info(sprintf( t('Welcome %s. Remote authentication successful.'),$r[0]['xchan_name']));
					logger('mod_openid: remote auth success from ' . $r[0]['xchan_addr']); 
					if($_SESSION['return_url'])
						goaway($_SESSION['return_url']);
					goaway(z_root());
				}
			}

		}
	}
	notice( t('Login failed.') . EOL);
	goaway(z_root());
	// NOTREACHED
}
