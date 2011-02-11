<?php

/**
 * This module needs a lot of work.
 *
 * - setting/storing preferences
 * - documentation on how to obtain FB API keys for your site 
 * - ensuring a valid FB login session
 * - requesting permissions within the FB login session to post on your behalf until permission revoked.
 *
 */

define('FACEBOOK_MAXPOSTLEN', 420);

/* declare the facebook_module function so that /facebook url requests will land here */

function facebook_module() {}



/* Callback from Facebook oauth requests. */

function facebook_init(&$a) {

	if($a->argc != 2)
		return;
	$nick = $a->argv[1];
	if(strlen($nick))
		$r = q("SELECT `uid` FROM `user` WHERE `nickname` = '%s' LIMIT 1",
				dbesc($nick)
		);
	if(! count($r))
		return;

	$uid           = $r[0]['uid'];
	$auth_code     = (($_GET['code']) ? $_GET['code'] : '');
	$error         = (($_GET['error_description']) ? $_GET['error_description'] : '');


	if($auth_code && $uid) {

		$appid = get_config('facebook','appid');
		$appsecret = get_config('facebook', 'appsecret');

		$x = fetch_url('https://graph.facebook.com/oauth/access_token?client_id='
			. $appid . '&client_secret=' . $appsecret . '&redirect_uri='
			. urlencode($a->get_baseurl() . '/facebook/' . $nick) 
			. '&code=' . $auth_code);
		if(strpos($x,'access_token=') !== false) {
			$token = str_replace('access_token=', '', $x);
 			if(strpos($token,'&') !== false)
				$token = substr('$token,0,strpos($token,'&'));
			set_pconfig($uid,'facebook','access_token',$token);
		}

		// todo: is this a browser session or a server session? where do we go? 
	}

}

function facebook_content(&$a) {
	$o = "facebook module loaded";
	return $o;
}

function facebook_install() {
	register_hook('post_local_end', 'addon/facebook/facebook.php', 'facebook_post_hook');
}


function facebook_uninstall() {
	unregister_hook('post_local_end', 'addon/facebook/facebook.php', 'facebook_post_hook');
}


function facebook_post_hook(&$a,&$b) {

	/**
	 * Post to Facebook stream
	 */

	if((local_user()) && (local_user() == $b['uid']) && (! $b['private']) && (! $b['parent'])) {

		$appid  = get_config('facebook', 'appid'  );
		$secret = get_config('facebook', 'appsecret' );

		if($appid && $secret) {

			$fb_post  = get_pconfig(local_user(),'facebook','post');
			$fb_token = get_pconfig(local_user(),'facebook','access_token');

			if($fb_post && $fb_token) {
				require_once('library/facebook.php');
				require_once('include/bbcode.php');	


				// make links readable before we strip the code

				$msg = preg_replace('\[url\=(.?*)\](.?*)\[\/url\]/is','$2 ($1)',$msg);

				$msg = preg_replace('\[img\](.?*)\[\/img\]/is', t('Image: ') . '$1',$msg);

				$msg = trim(strip_tags(bbcode($b['body'])));
				if (strlen($msg) > FACEBOOK_MAXPOSTLEN) {
					$shortlink = "";
					require_once('addon/twitter/slinky.php');

					$display_url = $a->get_baseurl() . '/display/' . $a->user['nickname'] . '/' . $b['id'];
					$slinky = new Slinky( $posturl );
					// setup a cascade of shortening services
					// try to get a short link from these services
					// in the order ur1.ca, trim, id.gd, tinyurl
					$slinky->set_cascade( array( new Slinky_UR1ca(), new Slinky_Trim(), new Slinky_IsGd(), new Slinky_TinyURL() ) );
					$shortlink = $slinky->short();
					// the new message will be shortened such that "... $shortlink"
					// will fit into the character limit
					$msg = substr($msg, 0, FACEBOOK_MAXPOSTLEN - strlen($shortlink) - 4);
					$msg .= '... ' . $shortlink;
				}
				if(! strlen($msg))
					return;




				$facebook = new Facebook(array(
					'appId'  => $appid,
					'secret' => $secret,
					'cookie' => true
				));			
				try {
					$statusUpdate = $facebook->api('/me/feed', 'post', array('message'=> bbcode($b['body']), 'cb' => ''));
				} 
				catch (FacebookApiException $e) {
					notice( t('Facebook status update failed.') . EOL);
				}
			}
		}
	}
}

