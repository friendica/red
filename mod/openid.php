<?php


require_once('library/openid.php');


function openid_content(&$a) {

	if((x($_GET,'openid_mode')) && (x($_SESSION,'openid'))) {
		$openid = new LightOpenID;

		if($openid->validate()) {

			if(x($_SESSION,'register')) {
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

				$args .= '&openid_url=' . notags(trim($_SESSION['openid']));
				if($a->config['register_policy'] != REGISTER_CLOSED)
					goaway($a->get_baseurl() . '/register' . $args);
				else
					goaway($a->get_baseurl());

				// NOTREACHED
			} 


			$r = q("SELECT * FROM `user` WHERE `openid` = '%s' AND `blocked` = 0 AND `verified` = 1 LIMIT 1",
				dbesc($_SESSION['openid'])
			);
			if(! count($r)) {
				notice( t('Login failed.') . EOL );
				goaway($a->get_baseurl());
  			}
			unset($_SESSION['openid']);

			$_SESSION['uid'] = $r[0]['uid'];
			$_SESSION['theme'] = $r[0]['theme'];
			$_SESSION['authenticated'] = 1;
			$_SESSION['page_flags'] = $r[0]['page-flags'];
			$_SESSION['my_url'] = $a->get_baseurl() . '/profile/' . $r[0]['nickname'];

			notice( t("Welcome back ") . $r[0]['username'] . EOL);
			$a->user = $r[0];
			if(strlen($a->user['timezone']))
				date_default_timezone_set($a->user['timezone']);

			$r = q("SELECT * FROM `contact` WHERE `uid` = %s AND `self` = 1 LIMIT 1",
				intval($_SESSION['uid']));
			if(count($r)) {
				$a->contact = $r[0];
				$a->cid = $r[0]['id'];
				$_SESSION['cid'] = $a->cid;
			}

			header('X-Account-Management-Status: active; name="' . $a->user['username'] . '"; id="' . $a->user['nickname'] .'"');
			if(($a->module !== 'home') && isset($_SESSION['return_url']))
				goaway($a->get_baseurl() . '/' . $_SESSION['return_url']);
			else
				goaway($a->get_baseurl());
		}
	}
	notice( t('Login failed.') . EOL);
	goaway($a->get_baseurl());
	// NOTREACHED
}