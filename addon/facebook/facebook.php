<?php
/**
 * Name: Facebook Connector
 * Version: 1.0
 * Author: Mike Macgirvin <http://macgirvin.com/profile/mike>
 */

/**
 * Installing the Friendika/Facebook connector
 *
 * 1. register an API key for your site from developer.facebook.com
 *   a. We'd be very happy if you include "Friendika" in the application name
 *      to increase name recognition. The Friendika icons are also present
 *      in the images directory and may be uploaded as a Facebook app icon.
 *      Use images/friendika-16.jpg for the Icon and images/friendika-128.jpg for the Logo.
 *   b. The url should be your site URL with a trailing slash.
 *      You may use http://portal.friendika.com/privacy as the privacy policy
 *      URL unless your site has different requirements, and 
 *      http://portal.friendika.com as the Terms of Service URL unless
 *      you have different requirements. (Friendika is a software application
 *      and does not require Terms of Service, though your installation of it might).
 *   c. Set the following values in your .htconfig.php file
 *         $a->config['facebook']['appid'] = 'xxxxxxxxxxx';
 *         $a->config['facebook']['appsecret'] = 'xxxxxxxxxxxxxxx';
 *      Replace with the settings Facebook gives you.
 *   d. Navigate to Set Web->Site URL & Domain -> Website Settings.  Set 
 *      Site URL to yoursubdomain.yourdomain.com. Set Site Domain to your 
 *      yourdomain.com.
 * 2. Enable the facebook plugin by including it in .htconfig.php - e.g. 
 *     $a->config['system']['addon'] = 'plugin1,plugin2,facebook';
 * 3. Visit the Facebook Settings section of the "Settings->Plugin Settings" page.
 *    and click 'Install Facebook Connector'.
 * 4. This will ask you to login to Facebook and grant permission to the 
 *    plugin to do its stuff. Allow it to do so. 
 * 5. You're done. To turn it off visit the Plugin Settings page again and
 *    'Remove Facebook posting'.
 *
 * Vidoes and embeds will not be posted if there is no other content. Links 
 * and images will be converted to a format suitable for the Facebook API and 
 * long posts truncated - with a link to view the full post. 
 *
 * Facebook contacts will not be able to view private photos, as they are not able to
 * authenticate to your site to establish identity. We will address this 
 * in a future release.
 */

define('FACEBOOK_MAXPOSTLEN', 420);


function facebook_install() {
	register_hook('post_local_end',   'addon/facebook/facebook.php', 'facebook_post_hook');
	register_hook('jot_networks',     'addon/facebook/facebook.php', 'facebook_jot_nets');
	register_hook('plugin_settings',  'addon/facebook/facebook.php', 'facebook_plugin_settings');
	register_hook('cron',             'addon/facebook/facebook.php', 'facebook_cron');
	register_hook('queue_predeliver', 'addon/facebook/facebook.php', 'fb_queue_hook');
}


function facebook_uninstall() {
	unregister_hook('post_local_end',   'addon/facebook/facebook.php', 'facebook_post_hook');
	unregister_hook('jot_networks',     'addon/facebook/facebook.php', 'facebook_jot_nets');
	unregister_hook('plugin_settings',  'addon/facebook/facebook.php', 'facebook_plugin_settings');
	unregister_hook('cron',             'addon/facebook/facebook.php', 'facebook_cron');
	unregister_hook('queue_predeliver', 'addon/facebook/facebook.php', 'fb_queue_hook');
}


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
			if(get_pconfig($uid,'facebook','no_linking') === false)
				set_pconfig($uid,'facebook','no_linking',1);
			fb_get_self($uid);
			fb_get_friends($uid);
			fb_consume_all($uid);

		}

	}

}


function fb_get_self($uid) {
	$access_token = get_pconfig($uid,'facebook','access_token');
	if(! $access_token)
		return;
	$s = fetch_url('https://graph.facebook.com/me/?access_token=' . $access_token);
	if($s) {
		$j = json_decode($s);
		set_pconfig($uid,'facebook','self_id',(string) $j->id);
	}
}



function fb_get_friends($uid) {

	$r = q("SELECT `id` FROM `user` WHERE `uid` = %d AND `account_expired` = 0 LIMIT 1",
		intval($uid)
	);
	if(! count($r))
		return;

	$access_token = get_pconfig($uid,'facebook','access_token');

	$no_linking = get_pconfig($uid,'facebook','no_linking');
	if($no_linking)
		return;

	if(! $access_token)
		return;
	$s = fetch_url('https://graph.facebook.com/me/friends?access_token=' . $access_token);
	if($s) {
		logger('facebook: fb_get_friends: ' . $s, LOGGER_DATA);
		$j = json_decode($s);
		logger('facebook: fb_get_friends: json: ' . print_r($j,true), LOGGER_DATA);
		if(! $j->data)
			return;
		foreach($j->data as $person) {
			$s = fetch_url('https://graph.facebook.com/' . $person->id . '?access_token=' . $access_token);
			if($s) {
				$jp = json_decode($s);
				logger('fb_get_friends: info: ' . print_r($jp,true), LOGGER_DATA);

				// always use numeric link for consistency

				$jp->link = 'http://facebook.com/profile.php?id=' . $person->id;

				// check if we already have a contact

				$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `url` = '%s' LIMIT 1",
					intval($uid),
					dbesc($jp->link)
				);			

				if(count($r)) {

					// check that we have all the photos, this has been known to fail on occasion

					if((! $r[0]['photo']) || (! $r[0]['thumb']) || (! $r[0]['micro'])) {  
						require_once("Photo.php");

						$photos = import_profile_photo('https://graph.facebook.com/' . $jp->id . '/picture', $uid, $r[0]['id']);

						$r = q("UPDATE `contact` SET `photo` = '%s', 
							`thumb` = '%s',
							`micro` = '%s', 
							`name-date` = '%s', 
							`uri-date` = '%s', 
							`avatar-date` = '%s'
							WHERE `id` = %d LIMIT 1
						",
							dbesc($photos[0]),
							dbesc($photos[1]),
							dbesc($photos[2]),
							dbesc(datetime_convert()),
							dbesc(datetime_convert()),
							dbesc(datetime_convert()),
							intval($r[0]['id'])
						);			
					}	
					continue;
				}
				else {

					// create contact record 
					$r = q("INSERT INTO `contact` ( `uid`, `created`, `url`, `addr`, `alias`, `notify`, `poll`, 
						`name`, `nick`, `photo`, `network`, `rel`, `priority`,
						`writable`, `blocked`, `readonly`, `pending` )
						VALUES ( %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, %d, 0, 0, 0 ) ",
						intval($uid),
						dbesc(datetime_convert()),
						dbesc($jp->link),
						dbesc(''),
						dbesc(''),
						dbesc($jp->id),
						dbesc('facebook ' . $jp->id),
						dbesc($jp->name),
						dbesc(($jp->nickname) ? $jp->nickname : strtolower($jp->first_name)),
						dbesc('https://graph.facebook.com/' . $jp->id . '/picture'),
						dbesc(NETWORK_FACEBOOK),
						intval(CONTACT_IS_FRIEND),
						intval(1),
						intval(1)
					);
				}

				$r = q("SELECT * FROM `contact` WHERE `url` = '%s' AND `uid` = %d LIMIT 1",
					dbesc($jp->link),
					intval($uid)
				);

				if(! count($r)) {
					continue;
				}

				$contact = $r[0];
				$contact_id  = $r[0]['id'];

				require_once("Photo.php");

				$photos = import_profile_photo($r[0]['photo'],$uid,$contact_id);

				$r = q("UPDATE `contact` SET `photo` = '%s', 
					`thumb` = '%s',
					`micro` = '%s', 
					`name-date` = '%s', 
					`uri-date` = '%s', 
					`avatar-date` = '%s'
					WHERE `id` = %d LIMIT 1
				",
					dbesc($photos[0]),
					dbesc($photos[1]),
					dbesc($photos[2]),
					dbesc(datetime_convert()),
					dbesc(datetime_convert()),
					dbesc(datetime_convert()),
					intval($contact_id)
				);			

			}
		}
	}
}

// This is the POST method to the facebook settings page
// Content is posted to Facebook in the function facebook_post_hook() 

function facebook_post(&$a) {

	$uid = local_user();
	if($uid){

		$value = ((x($_POST,'post_by_default')) ? intval($_POST['post_by_default']) : 0);
		set_pconfig($uid,'facebook','post_by_default', $value);

		$no_linking = get_pconfig($uid,'facebook','no_linking');

		$no_wall = ((x($_POST,'facebook_no_wall')) ? intval($_POST['facebook_no_wall']) : 0);
		set_pconfig($uid,'facebook','no_wall',$no_wall);

		$private_wall = ((x($_POST,'facebook_private_wall')) ? intval($_POST['facebook_private_wall']) : 0);
		set_pconfig($uid,'facebook','private_wall',$private_wall);
	

		$linkvalue = ((x($_POST,'facebook_linking')) ? intval($_POST['facebook_linking']) : 0);
		set_pconfig($uid,'facebook','no_linking', (($linkvalue) ? 0 : 1));

		// FB linkage was allowed but has just been turned off - remove all FB contacts and posts

		if((! intval($no_linking)) && (! intval($linkvalue))) {
			$r = q("SELECT `id` FROM `contact` WHERE `uid` = %d AND `network` = '%s' ",
				intval($uid),
				dbesc(NETWORK_FACEBOOK)
			);
			if(count($r)) {
				require_once('include/Contact.php');
				foreach($r as $rr)
					contact_remove($rr['id']);
			}
		}
		elseif(intval($no_linking) && intval($linkvalue)) {
			// FB linkage is now allowed - import stuff.
			fb_get_self($uid);
			fb_get_friends($uid);
			fb_consume_all($uid);
		}

		info( t('Settings updated.') . EOL);
	} 

	return;		
}

// Facebook settings form

function facebook_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return '';
	}

	if($a->argc > 1 && $a->argv[1] === 'remove') {
		del_pconfig(local_user(),'facebook','post');
		info( t('Facebook disabled') . EOL);
	}

	if($a->argc > 1 && $a->argv[1] === 'friends') {
		fb_get_friends(local_user());
		info( t('Updating contacts') . EOL);
	}


	$fb_installed = get_pconfig(local_user(),'facebook','post');

	$appid = get_config('facebook','appid');

	if(! $appid) {
		notice( t('Facebook API key is missing.') . EOL);
		return '';
	}

	$a->page['htmlhead'] .= '<link rel="stylesheet" type="text/css" href="' 
		. $a->get_baseurl() . '/addon/facebook/facebook.css' . '" media="all" />' . "\r\n";

	$o .= '<h3>' . t('Facebook Connect') . '</h3>';

	if(! $fb_installed) { 
		$o .= '<div id="facebook-enable-wrapper">';

		$o .= '<a href="https://www.facebook.com/dialog/oauth?client_id=' . $appid . '&redirect_uri=' 
			. $a->get_baseurl() . '/facebook/' . $a->user['nickname'] . '&scope=publish_stream,read_stream,offline_access">' . t('Install Facebook connector for this account.') . '</a>';
		$o .= '</div>';
	}

	if($fb_installed) {
		$o .= '<div id="facebook-disable-wrapper">';

		$o .= '<a href="' . $a->get_baseurl() . '/facebook/remove' . '">' . t('Remove Facebook connector') . '</a></div>';

		$o .= '<div id="facebook-enable-wrapper">';

		$o .= '<a href="https://www.facebook.com/dialog/oauth?client_id=' . $appid . '&redirect_uri=' 
			. $a->get_baseurl() . '/facebook/' . $a->user['nickname'] . '&scope=publish_stream,read_stream,offline_access">' . t('Re-authenticate [This is necessary whenever your Facebook password is changed.]') . '</a>';
		$o .= '</div>';
	
		$o .= '<div id="facebook-post-default-form">';
		$o .= '<form action="facebook" method="post" >';
		$post_by_default = get_pconfig(local_user(),'facebook','post_by_default');
		$checked = (($post_by_default) ? ' checked="checked" ' : '');
		$o .= '<input type="checkbox" name="post_by_default" value="1"' . $checked . '/>' . ' ' . t('Post to Facebook by default') . EOL;

		$no_linking = get_pconfig(local_user(),'facebook','no_linking');
		$checked = (($no_linking) ? '' : ' checked="checked" ');
		$o .= '<input type="checkbox" name="facebook_linking" value="1"' . $checked . '/>' . ' ' . t('Link all your Facebook friends and conversations on this website') . EOL ;

		$o .= '<p>' . t('Facebook conversations consist of your <em>profile wall</em> and your friend <em>stream</em>.');
		$o .= ' ' . t('On this website, your Facebook friend stream is only visible to you.');
		$o .= ' ' . t('The following settings determine the privacy of your Facebook profile wall on this website.') . '</p>';

		$private_wall = get_pconfig(local_user(),'facebook','private_wall');
		$checked = (($private_wall) ? ' checked="checked" ' : '');
		$o .= '<input type="checkbox" name="facebook_private_wall" value="1"' . $checked . '/>' . ' ' . t('On this website your Facebook profile wall conversations will only be visible to you') . EOL ;


		$no_wall = get_pconfig(local_user(),'facebook','no_wall');
		$checked = (($no_wall) ? ' checked="checked" ' : '');
		$o .= '<input type="checkbox" name="facebook_no_wall" value="1"' . $checked . '/>' . ' ' . t('Do not import your Facebook profile wall conversations') . EOL ;

		$o .= '<p>' . t('If you choose to link conversations and leave both of these boxes unchecked, your Facebook profile wall will be merged with your profile wall on this website and your privacy settings on this website will be used to determine who may see the conversations.') . '</p>';

		$o .= '<input type="submit" name="submit" value="' . t('Submit') . '" /></form></div>';
	}

	return $o;
}



function facebook_cron($a,$b) {

	$last = get_config('facebook','last_poll');
	
	$poll_interval = intval(get_config('facebook','poll_interval'));
	if(! $poll_interval)
		$poll_interval = 3600;

	if($last) {
		$next = $last + $poll_interval;
		if($next > time()) 
			return;
	}

	logger('facebook_cron');


	// Find the FB users on this site and randomize in case one of them
	// uses an obscene amount of memory. It may kill this queue run
	// but hopefully we'll get a few others through on each run. 

	$r = q("SELECT * FROM `pconfig` WHERE `cat` = 'facebook' AND `k` = 'post' AND `v` = '1' ORDER BY RAND() ");
	if(count($r)) {
		foreach($r as $rr) {
			if(get_pconfig($rr['uid'],'facebook','no_linking'))
				continue;
			// check for new friends once a day
			$last_friend_check = get_pconfig($rr['uid'],'facebook','friend_check');
			if($last_friend_check) 
				$next_friend_check = $last_friend_check + 86400;
			if($next_friend_check <= time()) {
				fb_get_friends($rr['uid']);
				set_pconfig($rr['uid'],'facebook','friend_check',time());
			}
			fb_consume_all($rr['uid']);
		}
	}	

	set_config('facebook','last_poll', time());

}



function facebook_plugin_settings(&$a,&$b) {

	$b .= '<div class="settings-block">';
	$b .= '<h3>' . t('Facebook') . '</h3>';
	$b .= '<a href="facebook">' . t('Facebook Connector Settings') . '</a><br />';
	$b .= '</div>';

}

function facebook_jot_nets(&$a,&$b) {
	if(! local_user())
		return;

	$fb_post = get_pconfig(local_user(),'facebook','post');
	if(intval($fb_post) == 1) {
		$fb_defpost = get_pconfig(local_user(),'facebook','post_by_default');
		$selected = ((intval($fb_defpost) == 1) ? ' checked="checked" ' : '');
		$b .= '<div class="profile-jot-net"><input type="checkbox" name="facebook_enable"' . $selected . ' value="1" /> ' 
			. t('Post to Facebook') . '</div>';	
	}
}


function facebook_post_hook(&$a,&$b) {

	/**
	 * Post to Facebook stream
	 */

	require_once('include/group.php');

	logger('Facebook post');

	$reply = false;
	$likes = false;

	if((local_user()) && (local_user() == $b['uid'])) {

		// Facebook is not considered a private network
		if($b['prvnets'] && $b['private'])
			return;

		$linking = ((get_pconfig(local_user(),'facebook','no_linking')) ? 0 : 1);

		if(($b['parent']) && ($linking)) {
			$r = q("SELECT * FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($b['parent']),
				intval(local_user())
			);
			if(count($r) && substr($r[0]['uri'],0,4) === 'fb::')
				$reply = substr($r[0]['uri'],4);
			elseif(count($r) && substr($r[0]['extid'],0,4) === 'fb::')
				$reply = substr($r[0]['extid'],4);
			else
				return;
			logger('facebook reply id=' . $reply);
		}

		if($b['private'] && $reply === false) {
			$allow_people = expand_acl($b['allow_cid']);
			$allow_groups = expand_groups(expand_acl($b['allow_gid']));
			$deny_people  = expand_acl($b['deny_cid']);
			$deny_groups  = expand_groups(expand_acl($b['deny_gid']));

			$recipients = array_unique(array_merge($allow_people,$allow_groups));
			$deny = array_unique(array_merge($deny_people,$deny_groups));

			$allow_str = dbesc(implode(', ',$recipients));
			if($allow_str) {
				$r = q("SELECT `notify` FROM `contact` WHERE `id` IN ( $allow_str ) AND `network` = 'face'"); 
				$allow_arr = array();
				if(count($r)) 
					foreach($r as $rr)
						$allow_arr[] = $rr['notify'];
			}

			$deny_str = dbesc(implode(', ',$deny));
			if($deny_str) {
				$r = q("SELECT `notify` FROM `contact` WHERE `id` IN ( $deny_str ) AND `network` = 'face'"); 
				$deny_arr = array();
				if(count($r)) 
					foreach($r as $rr)
						$deny_arr[] = $rr['notify'];
			}

			if(count($deny_arr) && (! count($allow_arr))) {

				// One or more FB folks were denied access but nobody on FB was specifically allowed access.
				// This might cause the post to be open to public on Facebook, but only to selected members
				// on another network. Since this could potentially leak a post to somebody who was denied, 
				// we will skip posting it to Facebook with a slightly vague but relevant message that will 
				// hopefully lead somebody to this code comment for a better explanation of what went wrong.

				notice( t('Post to Facebook cancelled because of multi-network access permission conflict.') . EOL);
				return;
			}


			// if it's a private message but no Facebook members are allowed or denied, skip Facebook post

			if((! count($allow_arr)) && (! count($deny_arr)))
				return;
		}

		if($b['verb'] == ACTIVITY_LIKE)
			$likes = true;				


		$appid  = get_config('facebook', 'appid'  );
		$secret = get_config('facebook', 'appsecret' );

		if($appid && $secret) {

			logger('facebook: have appid+secret');

			$fb_post   = intval(get_pconfig(local_user(),'facebook','post'));
			$fb_enable = (($fb_post && x($_POST,'facebook_enable')) ? intval($_POST['facebook_enable']) : 0);
			$fb_token  = get_pconfig(local_user(),'facebook','access_token');

			// if API is used, default to the chosen settings
			if($_POST['api_source'] && intval(get_pconfig(local_user(),'facebook','post_by_default')))
				$fb_enable = 1;




			logger('facebook: $fb_post: ' . $fb_post . ' $fb_enable: ' . $fb_enable . ' $fb_token: ' . $fb_token,LOGGER_DEBUG); 

			// post to facebook if it's a public post and we've ticked the 'post to Facebook' box, 
			// or it's a private message with facebook participants
			// or it's a reply or likes action to an existing facebook post			

			if($fb_post && $fb_token && ($fb_enable || $b['private'] || $reply)) {
				logger('facebook: able to post');
				require_once('library/facebook.php');
				require_once('include/bbcode.php');	

				$msg = $b['body'];

				logger('Facebook post: original msg=' . $msg, LOGGER_DATA);

				// make links readable before we strip the code

				// unless it's a dislike - just send the text as a comment

				if($b['verb'] == ACTIVITY_DISLIKE)
					$msg = trim(strip_tags(bbcode($msg)));

				$search_str = $a->get_baseurl() . '/search';

				if(preg_match("/\[url=(.*?)\](.*?)\[\/url\]/is",$msg,$matches)) {

					// don't use hashtags for message link

					if(strpos($matches[2],$search_str) === false) {
						$link = $matches[1];
						if(substr($matches[2],0,5) != '[img]')
							$linkname = $matches[2];
					}
				}

				$msg = preg_replace("/\[url=(.*?)\](.*?)\[\/url\]/is",'$2 $1',$msg);

				if(preg_match("/\[img\](.*?)\[\/img\]/is",$msg,$matches))
					$image = $matches[1];

				$msg = preg_replace("/\[img\](.*?)\[\/img\]/is", t('Image: ') . '$1', $msg);

				if((strpos($link,z_root()) !== false) && (! $image))
					$image = $a->get_baseurl() . '/images/friendika-64.jpg';

				$msg = trim(strip_tags(bbcode($msg)));
				$msg = html_entity_decode($msg,ENT_QUOTES,'UTF-8');

				// add any attachments as text urls

			    $arr = explode(',',$b['attach']);

			    if(count($arr)) {
					$msg .= "\n";
        			foreach($arr as $r) {
            			$matches = false;
						$cnt = preg_match('|\[attach\]href=\"(.*?)\" size=\"(.*?)\" type=\"(.*?)\" title=\"(.*?)\"\[\/attach\]|',$r,$matches);
						if($cnt) {
							$msg .= $matches[1];
						}
					}
				}

				if (strlen($msg) > FACEBOOK_MAXPOSTLEN) {
					$shortlink = "";
					require_once('library/slinky.php');

					$display_url = $a->get_baseurl() . '/display/' . $a->user['nickname'] . '/' . $b['id'];
					$slinky = new Slinky( $display_url );
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

				if($likes) { 
					$postvars = array('access_token' => $fb_token);
				}
				else {
					$postvars = array(
						'access_token' => $fb_token, 
						'message' => $msg
					);
					if(isset($image))
						$postvars['picture'] = $image;
					if(isset($link))
						$postvars['link'] = $link;
					if(isset($linkname))
						$postvars['name'] = $linkname;
				}

				if(($b['private']) && (! $b['parent'])) {
					$postvars['privacy'] = '{"value": "CUSTOM", "friends": "SOME_FRIENDS"';
					if(count($allow_arr))
						$postvars['privacy'] .= ',"allow": "' . implode(',',$allow_arr) . '"';
					if(count($deny_arr))
						$postvars['privacy'] .= ',"deny": "' . implode(',',$deny_arr) . '"';
					$postvars['privacy'] .= '}';

				}

				if($reply) {
					$url = 'https://graph.facebook.com/' . $reply . '/' . (($likes) ? 'likes' : 'comments');
				}
				else { 
					$url = 'https://graph.facebook.com/me/feed';
					if($b['plink'])
						$postvars['actions'] = '{"name": "' . t('View on Friendika') . '", "link": "' .  $b['plink'] . '"}';
				}

				logger('facebook: post to ' . $url);
				logger('facebook: postvars: ' . print_r($postvars,true));

				// "test_mode" prevents anything from actually being posted.
				// Otherwise, let's do it. 

				if(! get_config('facebook','test_mode')) {
					$x = post_url($url, $postvars);

					$retj = json_decode($x);
					if($retj->id) {
						q("UPDATE `item` SET `extid` = '%s' WHERE `id` = %d LIMIT 1",
							dbesc('fb::' . $retj->id),
							intval($b['id'])
						);
					}
					else {
						if(! $likes) {
							$s = serialize(array('url' => $url, 'item' => $b['id'], 'post' => $postvars));
							q("INSERT INTO `queue` ( `network`, `cid`, `created`, `last`, `content`)
								VALUES ( '%s', %d, '%s', '%s', '%s') ",
								dbesc(NETWORK_FACEBOOK),
								intval($a->contact),
								dbesc(datetime_convert()),
								dbesc(datetime_convert()),
								dbesc($s)
							);								

							notice( t('Facebook post failed. Queued for retry.') . EOL);
						}
					}
					
					logger('Facebook post returns: ' . $x, LOGGER_DEBUG);
				}
			}
		}
	}
}


function fb_queue_hook(&$a,&$b) {

	$qi = q("SELECT * FROM `queue` WHERE `network` = '%s'",
		dbesc(NETWORK_FACEBOOK)
	);
	if(! count($qi))
		return;

	require_once('include/queue_fn.php');

	foreach($qi as $x) {
		if($x['network'] !== NETWORK_FACEBOOK)
			continue;

		logger('facebook_queue: run');

		$r = q("SELECT `user`.* FROM `user` LEFT JOIN `contact` on `contact`.`uid` = `user`.`uid` 
			WHERE `contact`.`self` = 1 AND `contact`.`id` = %d LIMIT 1",
			intval($x['cid'])
		);
		if(! count($r))
			continue;

		$user = $r[0];

		$appid  = get_config('facebook', 'appid'  );
		$secret = get_config('facebook', 'appsecret' );

		if($appid && $secret) {
			$fb_post   = intval(get_pconfig($user['uid'],'facebook','post'));
			$fb_token  = get_pconfig($user['uid'],'facebook','access_token');

			if($fb_post && $fb_token) {
				logger('facebook_queue: able to post');
				require_once('library/facebook.php');

				$z = unserialize($x['content']);
				$item = $z['item'];
				$j = post_url($z['url'],$z['post']);

				$retj = json_decode($j);
				if($retj->id) {
					q("UPDATE `item` SET `extid` = '%s' WHERE `id` = %d LIMIT 1",
						dbesc('fb::' . $retj->id),
						intval($item)
					);
					logger('facebook_queue: success: ' . $j); 
					remove_queue_item($x['id']);
				}
				else {
					logger('facebook_queue: failed: ' . $j);
					update_queue_time($x['id']);
				}
			}
		}
	}
}

function fb_consume_all($uid) {

	require_once('include/items.php');

	$access_token = get_pconfig($uid,'facebook','access_token');
	if(! $access_token)
		return;
	
	if(! get_pconfig($uid,'facebook','no_wall')) {
		$private_wall = intval(get_pconfig($uid,'facebook','private_wall'));
		$s = fetch_url('https://graph.facebook.com/me/feed?access_token=' . $access_token);
		if($s) {
			$j = json_decode($s);
			logger('fb_consume_stream: wall: ' . print_r($j,true), LOGGER_DATA);
			fb_consume_stream($uid,$j,($private_wall) ? false : true);
		}
	}
	$s = fetch_url('https://graph.facebook.com/me/home?access_token=' . $access_token);
	if($s) {
		$j = json_decode($s);
		logger('fb_consume_stream: feed: ' . print_r($j,true), LOGGER_DATA);
		fb_consume_stream($uid,$j,false);
	}

}

function fb_consume_stream($uid,$j,$wall = false) {

	$a = get_app();


	$user = q("SELECT `nickname`, `blockwall` FROM `user` WHERE `uid` = %d AND `account_expired` = 0 LIMIT 1",
		intval($uid)
	);
	if(! count($user))
		return;

	$my_local_url = $a->get_baseurl() . '/profile/' . $user[0]['nickname'];

	$no_linking = get_pconfig($uid,'facebook','no_linking');
	if($no_linking)
		return;

	$self = q("SELECT * FROM `contact` WHERE `self` = 1 AND `uid` = %d LIMIT 1",
		intval($uid)
	);


	$self_id = get_pconfig($uid,'facebook','self_id');
	if(! count($j->data) || (! strlen($self_id)))
		return;

	foreach($j->data as $entry) {
		logger('fb_consume: entry: ' . print_r($entry,true), LOGGER_DATA);
		$datarray = array();

		$r = q("SELECT * FROM `item` WHERE ( `uri` = '%s' OR `extid` = '%s') AND `uid` = %d LIMIT 1",
				dbesc('fb::' . $entry->id),
				dbesc('fb::' . $entry->id),
				intval($uid)
		);
		if(count($r)) {
			$post_exists = true;
			$orig_post = $r[0];
			$top_item = $r[0]['id'];
		}
		else {
			$post_exists = false;
			$orig_post = null;
		}

		if(! $orig_post) {
			$datarray['gravity'] = 0;
			$datarray['uid'] = $uid;
			$datarray['wall'] = (($wall) ? 1 : 0);
			$datarray['uri'] = $datarray['parent-uri'] = 'fb::' . $entry->id;
			$from = $entry->from;
			if($from->id == $self_id)
				$datarray['contact-id'] = $self[0]['id'];
			else {
				$r = q("SELECT * FROM `contact` WHERE `notify` = '%s' AND `uid` = %d AND `blocked` = 0 AND `readonly` = 0 LIMIT 1",
					dbesc($from->id),
					intval($uid)
				);
				if(count($r))
					$datarray['contact-id'] = $r[0]['id'];
			}

			// don't store post if we don't have a contact

			if(! x($datarray,'contact-id')) {
				logger('no contact: post ignored');
				continue; 
			}

			$datarray['verb'] = ACTIVITY_POST;						
			if($wall) {
				$datarray['owner-name'] = $self[0]['name'];
				$datarray['owner-link'] = $self[0]['url'];
				$datarray['owner-avatar'] = $self[0]['thumb'];
			}
			if(isset($entry->application) && isset($entry->application->name) && strlen($entry->application->name))
				$datarray['app'] = strip_tags($entry->application->name);
			else
				$datarray['app'] = 'facebook';
			$datarray['author-name'] = $from->name;
			$datarray['author-link'] = 'http://facebook.com/profile.php?id=' . $from->id;
			$datarray['author-avatar'] = 'https://graph.facebook.com/' . $from->id . '/picture';
			$datarray['plink'] = $datarray['author-link'] . '&v=wall&story_fbid=' . substr($entry->id,strpos($entry->id,'_') + 1);

			$datarray['body'] = $entry->message;
			if($entry->picture)
				$datarray['body'] .= "\n\n" . '[img]' . $entry->picture . '[/img]';
			if($entry->link)
				$datarray['body'] .= "\n" . linkify($entry->link);
			if($entry->name)
				$datarray['body'] .= "\n" . $entry->name;
			if($entry->caption)
				$datarray['body'] .= "\n" . $entry->caption;
			if($entry->description)
				$datarray['body'] .= "\n" . $entry->description;
			$datarray['created'] = datetime_convert('UTC','UTC',$entry->created_time);
			$datarray['edited'] = datetime_convert('UTC','UTC',$entry->updated_time);

			// If the entry has a privacy policy, we cannot assume who can or cannot see it,
			// as the identities are from a foreign system. Mark it as private to the owner.

			if($entry->privacy && $entry->privacy->value !== 'EVERYONE') {
				$datarray['private'] = 1;
				$datarray['allow_cid'] = '<' . $uid . '>';
			}
			
			$top_item = item_store($datarray);
			$r = q("SELECT * FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($top_item),
				intval($uid)
			);			
			if(count($r)) {
				$orig_post = $r[0];
				logger('fb: new top level item posted');
			}
		}

		if(isset($entry->likes) && isset($entry->likes->data))
			$likers = $entry->likes->data;
		else
			$likers = null;

		if(isset($entry->comments) && isset($entry->comments->data))
			$comments = $entry->comments->data;
		else
			$comments = null;

		if(is_array($likers)) {
			foreach($likers as $likes) {

				if(! $orig_post)
					continue;

				// If we posted the like locally, it will be found with our url, not the FB url.

				$second_url = (($likes->id == $self_id) ? $self[0]['url'] : 'http://facebook.com/profile.php?id=' . $likes->id); 

				$r = q("SELECT * FROM `item` WHERE `parent-uri` = '%s' AND `uid` = %d AND `verb` = '%s' 
					AND ( `author-link` = '%s' OR `author-link` = '%s' ) LIMIT 1",
					dbesc($orig_post['uri']),
					intval($uid),
					dbesc(ACTIVITY_LIKE),
					dbesc('http://facebook.com/profile.php?id=' . $likes->id),
					dbesc($second_url)
				);

				if(count($r))
					continue;
					
				$likedata = array();
				$likedata['parent'] = $top_item;
				$likedata['verb'] = ACTIVITY_LIKE;
				$likedata['gravity'] = 3;
				$likedata['uid'] = $uid;
				$likedata['wall'] = (($wall) ? 1 : 0);
				$likedata['uri'] = item_new_uri($a->get_baseurl(), $uid);
				$likedata['parent-uri'] = $orig_post['uri'];
				if($likes->id == $self_id)
					$likedata['contact-id'] = $self[0]['id'];
				else {
					$r = q("SELECT * FROM `contact` WHERE `notify` = '%s' AND `uid` = %d AND `blocked` = 0 AND `readonly` = 0 LIMIT 1",
						dbesc($likes->id),
						intval($uid)
					);
					if(count($r))
						$likedata['contact-id'] = $r[0]['id'];
				}
				if(! x($likedata,'contact-id'))
					$likedata['contact-id'] = $orig_post['contact-id'];

				$likedata['app'] = 'facebook';
				$likedata['verb'] = ACTIVITY_LIKE;						
				$likedata['author-name'] = $likes->name;
				$likedata['author-link'] = 'http://facebook.com/profile.php?id=' . $likes->id;
				$likedata['author-avatar'] = 'https://graph.facebook.com/' . $likes->id . '/picture';
				
				$author  = '[url=' . $likedata['author-link'] . ']' . $likedata['author-name'] . '[/url]';
				$objauthor =  '[url=' . $orig_post['author-link'] . ']' . $orig_post['author-name'] . '[/url]';
				$post_type = t('status');
        		$plink = '[url=' . $orig_post['plink'] . ']' . $post_type . '[/url]';
				$likedata['object-type'] = ACTIVITY_OBJ_NOTE;

				$likedata['body'] = sprintf( t('%1$s likes %2$s\'s %3$s'), $author, $objauthor, $plink);
				$likedata['object'] = '<object><type>' . ACTIVITY_OBJ_NOTE . '</type><local>1</local>' . 
					'<id>' . $orig_post['uri'] . '</id><link>' . xmlify('<link rel="alternate" type="text/html" href="' . xmlify($orig_post['plink']) . '" />') . '</link><title>' . $orig_post['title'] . '</title><content>' . $orig_post['body'] . '</content></object>';  

				$item = item_store($likedata);			
			}
		}
		if(is_array($comments)) {
			foreach($comments as $cmnt) {

				if(! $orig_post)
					continue;

				$r = q("SELECT * FROM `item` WHERE `uid` = %d AND ( `uri` = '%s' OR `extid` = '%s' ) LIMIT 1",
					intval($uid),
					dbesc('fb::' . $cmnt->id),
					dbesc('fb::' . $cmnt->id)
				);
				if(count($r))
					continue;

				$cmntdata = array();
				$cmntdata['parent'] = $top_item;
				$cmntdata['verb'] = ACTIVITY_POST;
				$cmntdata['gravity'] = 6;
				$cmntdata['uid'] = $uid;
				$cmntdata['wall'] = (($wall) ? 1 : 0);
				$cmntdata['uri'] = 'fb::' . $cmnt->id;
				$cmntdata['parent-uri'] = $orig_post['uri'];
				if($cmnt->from->id == $self_id) {
					$cmntdata['contact-id'] = $self[0]['id'];
				}
				else {
					$r = q("SELECT * FROM `contact` WHERE `notify` = '%s' AND `uid` = %d LIMIT 1",
						dbesc($cmnt->from->id),
						intval($uid)
					);
					if(count($r)) {
						$cmntdata['contact-id'] = $r[0]['id'];
						if($r[0]['blocked'] || $r[0]['readonly'])
							continue;
					}
				}
				if(! x($cmntdata,'contact-id'))
					$cmntdata['contact-id'] = $orig_post['contact-id'];

				$cmntdata['app'] = 'facebook';
				$cmntdata['created'] = datetime_convert('UTC','UTC',$cmnt->created_time);
				$cmntdata['edited']  = datetime_convert('UTC','UTC',$cmnt->created_time);
				$cmntdata['verb'] = ACTIVITY_POST;						
				$cmntdata['author-name'] = $cmnt->from->name;
				$cmntdata['author-link'] = 'http://facebook.com/profile.php?id=' . $cmnt->from->id;
				$cmntdata['author-avatar'] = 'https://graph.facebook.com/' . $cmnt->from->id . '/picture';
				$cmntdata['body'] = $cmnt->message;
				$item = item_store($cmntdata);			
			}
		}
	}
}

