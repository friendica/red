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
			// New registration?

//			if($a->config['register_policy'] == REGISTER_CLOSED) {
				notice( t('Account not found and OpenID registration is not permitted on this site.') . EOL);
				goaway(z_root());
//			}

			unset($_SESSION['register']);
			$args = '';
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
