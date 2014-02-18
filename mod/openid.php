<?php


require_once('library/openid/openid.php');
require_once('include/auth.php');

function openid_content(&$a) {

	$noid = get_config('system','no_openid');
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
								$_SESSION['authenticated'] = true;
								authenticate_success($record,true,true,true,true);
								goaway(z_root());
							}
						}
					}
				}
			}

			// Successful OpenID login - but we can't match it to an existing account.

			$r = q("select * from xchan where xchan_hash = '%s' limit 1",
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

			$attr = $openid->getAttributes();
			if(is_array($attr) && count($attr)) {
				foreach($attr as $k => $v) {
					if($k === 'namePerson/friendly')
						$nick = notags(trim($v));
					if($k === 'namePerson/first')
						$first = notags(trim($v));
					if($k === 'namePerson')
						$args .= '&username=' . notags(trim($v));
					if($k === 'contact/email')
						$args .= '&email=' . notags(trim($v));
					if($k === 'media/image/aspect11')
						$photosq = bin2hex(trim($v));
					if($k === 'media/image/default')
						$photo = bin2hex(trim($v));
				}
			}
			if($nick)
				$args .= '&nickname=' . $nick;
			elseif($first)
				$args .= '&nickname=' . $first;

			if($photosq)
				$args .= '&photo=' . $photosq;
			elseif($photo)
				$args .= '&photo=' . $photo;

			$args .= '&openid_url=' . notags(trim($authid));

			goaway($a->get_baseurl() . '/register' . $args);

			// NOTREACHED
		}
	}
	notice( t('Login failed.') . EOL);
	goaway(z_root());
	// NOTREACHED
}
