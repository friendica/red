<?php

/**
 * This module still needs a lot of work, but is functional today.
 * Please review this section if you upgrade because things will change.
 * If you have issues upgrading, remove facebook from the addon list, 
 * view a page on your site, then add it back to the list. This will reset
 * all of the plugin 'hooks'. 
 *
 * 1. register an API key from developer.facebook.com
 *   a. We'd be very happy if you include "Friendika" in the application name
 *      to increase name recognition.
 *   b. The url should be your site URL with a trailing slash
 *   c. Set the following values in your .htconfig.php file
 *         $a->config['facebook']['appid'] = 'xxxxxxxxxxx';
 *         $a->config['facebook']['appsecret'] = 'xxxxxxxxxxxxxxx';
 *      Replace with the settings Facebook gives you.
 * 2. Enable the facebook plugin by including it in .htconfig.php - e.g. 
 *     $a->config['system']['addon'] = 'plugin1,plugin2,facebook';
 * 3. Visit your site url + '/facebook' (e.g. http://example.com/facebook)
 *    and click 'Install Facebook posting'.
 * 4. This will ask you to login to Facebook and grant permission to the 
 *    plugin to do its stuff. Allow it to do so. 
 * 5. You're done. To turn it off visit your site's /facebook page again and
 *    'Remove Facebook posting'.
 *
 * Turn logging on (see the github Friendika wiki page 'Settings') and 
 * repeat these steps if you have trouble.
 * Vidoes and embeds will not be posted if there is no other content. Links 
 * and images will be converted to text and long posts truncated - with a link
 * to view the full post. Posts with permission settings and comments will
 * not be posted to Facebook. 
 *
 */

define('FACEBOOK_MAXPOSTLEN', 420);

/* declare the facebook_module function so that /facebook url requests will land here */

function facebook_module() {}



/* If a->argv[1] is a nickname, this is a callback from Facebook oauth requests. */

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


	if($error)
		logger('facebook_init: Error: ' . $error);

	if($auth_code && $uid) {

		$appid = get_config('facebook','appid');
		$appsecret = get_config('facebook', 'appsecret');

		$x = fetch_url('https://graph.facebook.com/oauth/access_token?client_id='
			. $appid . '&client_secret=' . $appsecret . '&redirect_uri='
			. urlencode($a->get_baseurl() . '/facebook/' . $nick) 
			. '&code=' . $auth_code);

		logger('facebook_init: returned access token: ' . $x, LOGGER_DATA);

		if(strpos($x,'access_token=') !== false) {
			$token = str_replace('access_token=', '', $x);
 			if(strpos($token,'&') !== false)
				$token = substr($token,0,strpos($token,'&'));
			set_pconfig($uid,'facebook','access_token',$token);
			set_pconfig($uid,'facebook','post','1');
		}

		// todo: is this a browser session or a server session? where do we go? 
	}

}

function facebook_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return '';
	}

	if($a->argc > 1 && $a->argv[1] === 'remove') {
		del_pconfig(local_user(),'facebook','post');
		notice( t('Facebook disabled') . EOL);
	}

	$appid = get_config('facebook','appid');

	if(! $appid) {
		notify( t('Facebook API key is missing.') . EOL);
		return '';
	}

	$o .= '<h3>' . t('Facebook Connect') . '</h3>';

	$o .= '<br />';

	$o .= '<a href="https://www.facebook.com/dialog/oauth?client_id=' . $appid . '&redirect_uri=' 
		. $a->get_baseurl() . '/facebook/' . $a->user['nickname'] . '&scope=publish_stream,read_stream,offline_access">' . t('Install Facebook post connector') . '</a><br /><br />';

	$o .= '<a href="' . $a->get_baseurl() . '/facebook/remove' . '">' . t('Remove Facebook post connector') . '</a><br />';


	return $o;
}

function facebook_install() {
	register_hook('post_local_end',  'addon/facebook/facebook.php', 'facebook_post_hook');
	register_hook('jot_networks',    'addon/facebook/facebook.php', 'facebook_jot_nets');
	register_hook('plugin_settings', 'addon/facebook/facebook.php', 'facebook_plugin_settings');
}


function facebook_uninstall() {
	unregister_hook('post_local_end',  'addon/facebook/facebook.php', 'facebook_post_hook');
	unregister_hook('jot_networks',    'addon/facebook/facebook.php', 'facebook_jot_nets');
	unregister_hook('plugin_settings', 'addon/facebook/facebook.php', 'facebook_plugin_settings');
}


function facebook_plugin_settings(&$a,&$b) {

	$b .= '<h3>' . t('Facebook') . '</h3>';
	$b .= '<a href="facebook">' . t('Facebook Connector Settings') . '</a><br />';

}

function facebook_jot_nets(&$a,&$b) {
	if(! local_user())
		return;

	$fb_post = get_pconfig(local_user(),'facebook','post');
	if(intval($fb_post) == 1) {
		$fb_defpost = get_pconfig(local_user(),'facebook','post_by_default');
		$selected = ((intval($fb_defpost == 1)) ? ' selected="selected" ' : '');
		$b .= '<div class="profile-jot-net"><input type="checkbox" name="facebook_enable"' . $selected . 'value="1" /> ' 
			. t('Post to Facebook') . '</div>';	
	}
}


function facebook_post_hook(&$a,&$b) {

	/**
	 * Post to Facebook stream
	 */

	logger('Facebook post');

	if((local_user()) && (local_user() == $b['uid']) && (! $b['private']) && (! $b['parent'])) {


		$appid  = get_config('facebook', 'appid'  );
		$secret = get_config('facebook', 'appsecret' );

		if($appid && $secret) {

			logger('facebook: have appid+secret');

			$fb_post   = intval(get_pconfig(local_user(),'facebook','post'));
			$fb_enable = (($fb_post && x($_POST,'facebook_enable')) ? intval($_POST['facebook_enable']) : 0);
			$fb_token  = get_pconfig(local_user(),'facebook','access_token');

			logger('facebook: $fb_post: ' . $fb_post . ' $fb_enable: ' . $fb_enable . ' $fb_token: ' . $fb_token,LOGGER_DEBUG); 
			if($fb_post && $fb_token && $fb_enable) {
				logger('facebook: able to post');
				require_once('library/facebook.php');
				require_once('include/bbcode.php');	

				$msg = $b['body'];

				logger('Facebook post: original msg=' . $msg, LOGGER_DATA);

				// make links readable before we strip the code

				$msg = preg_replace("/\[url=(.+?)\](.+?)\[\/url\]/is",'$2 ($1)',$msg);

				$msg = preg_replace("/\[img\](.+?)\[\/img\]/is", t('Image: ') . '$1',$msg);

				$msg = trim(strip_tags(bbcode($msg)));
				$msg = html_entity_decode($msg,ENT_QUOTES,'UTF-8');

				if (strlen($msg) > FACEBOOK_MAXPOSTLEN) {
					$shortlink = "";
					require_once('library/slinky.php');

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

				logger('Facebook post: msg=' . $msg, LOGGER_DATA);

				$postvars = array('access_token' => $fb_token, 'message' => $msg);

				$x = post_url('https://graph.facebook.com/me/feed', $postvars);
				
				logger('Facebook post returns: ' . $x, LOGGER_DEBUG);

			}
		}
	}
}

