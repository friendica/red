<?php
/**
 * Name: Twitter Connector
 * Version: 1.0.1
 * Author: Tobias Diekershoff <https://diekershoff.homeunix.net/friendika/profile/tobias>
 */


/*   Twitter Plugin for Friendika
 *
 *   Author: Tobias Diekershoff
 *           tobias.diekershoff@gmx.net
 *
 *   License:3-clause BSD license
 *
 *   Configuration:
 *     To use this plugin you need a OAuth Consumer key pair (key & secret)
 *     you can get it from Twitter at https://twitter.com/apps
 *
 *     Register your Friendika site as "Client" application with "Read & Write" access
 *     we do not need "Twitter as login". When you've registered the app you get the
 *     OAuth Consumer key and secret pair for your application/site.
 *
 *     Add this key pair to your global .htconfig.php
 *
 *     $a->config['twitter']['consumerkey'] = 'your consumer_key here';
 *     $a->config['twitter']['consumersecret'] = 'your consumer_secret here';
 *
 *     To activate the plugin itself add it to the $a->config['system']['addon']
 *     setting. After this, your user can configure their Twitter account settings
 *     from "Settings -> Plugin Settings".
 *
 *     Requirements: PHP5, curl [Slinky library]
 *
 *     Documentation: http://diekershoff.homeunix.net/redmine/wiki/friendikaplugin/Twitter_Plugin
 */

/*   __TODO__
 *
 *   - what about multimedia content?
 *     so far we just strip HTML tags from the message
 */

function twitter_install() {
	//  we need some hooks, for the configuration and for sending tweets
	register_hook('plugin_settings', 'addon/twitter/twitter.php', 'twitter_settings'); 
	register_hook('plugin_settings_post', 'addon/twitter/twitter.php', 'twitter_settings_post');
	register_hook('post_local_end', 'addon/twitter/twitter.php', 'twitter_post_hook');
	register_hook('jot_networks', 'addon/twitter/twitter.php', 'twitter_jot_nets');
	logger("installed twitter");
}


function twitter_uninstall() {
	unregister_hook('plugin_settings', 'addon/twitter/twitter.php', 'twitter_settings'); 
	unregister_hook('plugin_settings_post', 'addon/twitter/twitter.php', 'twitter_settings_post');
	unregister_hook('post_local_end', 'addon/twitter/twitter.php', 'twitter_post_hook');
	unregister_hook('jot_networks', 'addon/twitter/twitter.php', 'twitter_jot_nets');
}

function twitter_jot_nets(&$a,&$b) {
	if(! local_user())
		return;

	$tw_post = get_pconfig(local_user(),'twitter','post');
	if(intval($tw_post) == 1) {
		$tw_defpost = get_pconfig(local_user(),'twitter','post_by_default');
		$selected = ((intval($tw_defpost) == 1) ? ' checked="checked" ' : '');
		$b .= '<div class="profile-jot-net"><input type="checkbox" name="twitter_enable"' . $selected . ' value="1" /> ' 
			. t('Post to Twitter') . '</div>';	
	}


}

function twitter_settings_post ($a,$post) {
	if(! local_user())
		return;
	// don't check twitter settings if twitter submit button is not clicked	
	if (!x($_POST,'twitter-submit')) return;
	
	if (isset($_POST['twitter-disconnect'])) {
		/***
		 * if the twitter-disconnect checkbox is set, clear the OAuth key/secret pair
		 * from the user configuration
		 * TODO can we revoke the access tokens at Twitter and do we need to do so?
		 */
		del_pconfig( local_user(), 'twitter', 'consumerkey'  );
		del_pconfig( local_user(), 'twitter', 'consumersecret' );
                del_pconfig( local_user(), 'twitter', 'post' );
                del_pconfig( local_user(), 'twitter', 'post_by_default' );
	} else {
	if (isset($_POST['twitter-pin'])) {
		//  if the user supplied us with a PIN from Twitter, let the magic of OAuth happen
		logger('got a Twitter PIN');
		require_once('library/twitteroauth.php');
		$ckey    = get_config('twitter', 'consumerkey'  );
		$csecret = get_config('twitter', 'consumersecret' );
		//  the token and secret for which the PIN was generated were hidden in the settings
		//  form as token and token2, we need a new connection to Twitter using these token
		//  and secret to request a Access Token with the PIN
		$connection = new TwitterOAuth($ckey, $csecret, $_POST['twitter-token'], $_POST['twitter-token2']);
		$token   = $connection->getAccessToken( $_POST['twitter-pin'] );
		//  ok, now that we have the Access Token, save them in the user config
 		set_pconfig(local_user(),'twitter', 'oauthtoken',  $token['oauth_token']);
		set_pconfig(local_user(),'twitter', 'oauthsecret', $token['oauth_token_secret']);
                set_pconfig(local_user(),'twitter', 'post', 1);
                //  reload the Addon Settings page, if we don't do it see Bug #42
                goaway($a->get_baseurl().'/settings/addon');
	} else {
		//  if no PIN is supplied in the POST variables, the user has changed the setting
		//  to post a tweet for every new __public__ posting to the wall
		set_pconfig(local_user(),'twitter','post',intval($_POST['twitter-enable']));
                set_pconfig(local_user(),'twitter','post_by_default',intval($_POST['twitter-default']));
                info( t('Twitter settings updated.') . EOL);
	}}
}
function twitter_settings(&$a,&$s) {
        if(! local_user())
                return;
        $a->page['htmlhead'] .= '<link rel="stylesheet"  type="text/css" href="' . $a->get_baseurl() . '/addon/twitter/twitter.css' . '" media="all" />' . "\r\n";
	/***
	 * 1) Check that we have global consumer key & secret
	 * 2) If no OAuthtoken & stuff is present, generate button to get some
	 * 3) Checkbox for "Send public notices (140 chars only)
	 */
	$ckey    = get_config('twitter', 'consumerkey' );
	$csecret = get_config('twitter', 'consumersecret' );
	$otoken  = get_pconfig(local_user(), 'twitter', 'oauthtoken'  );
	$osecret = get_pconfig(local_user(), 'twitter', 'oauthsecret' );
        $enabled = get_pconfig(local_user(), 'twitter', 'post');
	$checked = (($enabled) ? ' checked="checked" ' : '');
        $defenabled = get_pconfig(local_user(),'twitter','post_by_default');
	$defchecked = (($defenabled) ? ' checked="checked" ' : '');

	$s .= '<div class="settings-block">';
	$s .= '<h3>'. t('Twitter Posting Settings') .'</h3>';

	if ( (!$ckey) && (!$csecret) ) {
		/***
		 * no global consumer keys
		 * display warning and skip personal config
		 */
		$s .= '<p>'. t('No consumer key pair for Twitter found. Please contact your site administrator.') .'</p>';
	} else {
		/***
		 * ok we have a consumer key pair now look into the OAuth stuff
		 */
		if ( (!$otoken) && (!$osecret) ) {
			/***
			 * the user has not yet connected the account to twitter...
			 * get a temporary OAuth key/secret pair and display a button with
			 * which the user can request a PIN to connect the account to a
			 * account at Twitter.
			 */
		        require_once('library/twitteroauth.php');
			$connection = new TwitterOAuth($ckey, $csecret);
			$request_token = $connection->getRequestToken();
			$token = $request_token['oauth_token'];
			/***
			 *  make some nice form
			 */
			$s .= '<p>'. t('At this Friendika instance the Twitter plugin was enabled but you have not yet connected your account to your Twitter account. To do so click the button below to get a PIN from Twitter which you have to copy into the input box below and submit the form. Only your <strong>public</strong> posts will be posted to Twitter.') .'</p>';
			$s .= '<a href="'.$connection->getAuthorizeURL($token).'" target="_twitter"><img src="addon/twitter/lighter.png" alt="'.t('Log in with Twitter').'"></a>';
			$s .= '<div id="twitter-pin-wrapper">';
			$s .= '<label id="twitter-pin-label" for="twitter-pin">'. t('Copy the PIN from Twitter here') .'</label>';
			$s .= '<input id="twitter-pin" type="text" name="twitter-pin" />';
			$s .= '<input id="twitter-token" type="hidden" name="twitter-token" value="'.$token.'" />';
			$s .= '<input id="twitter-token2" type="hidden" name="twitter-token2" value="'.$request_token['oauth_token_secret'].'" />';
            $s .= '</div><div class="clear"></div>';
            $s .= '<div class="settings-submit-wrapper" ><input type="submit" name="twitter-submit" class="settings-submit" value="' . t('Submit') . '" /></div>';
		} else {
			/***
			 *  we have an OAuth key / secret pair for the user
			 *  so let's give a chance to disable the postings to Twitter
			 */
                        require_once('library/twitteroauth.php');
			$connection = new TwitterOAuth($ckey,$csecret,$otoken,$osecret);
			$details = $connection->get('account/verify_credentials');
			$s .= '<div id="twitter-info" ><img id="twitter-avatar" src="'.$details->profile_image_url.'" /><p id="twitter-info-block">'. t('Currently connected to: ') .'<a href="https://twitter.com/'.$details->screen_name.'" target="_twitter">'.$details->screen_name.'</a><br /><em>'.$details->description.'</em></p></div>';
			$s .= '<p>'. t('If enabled all your <strong>public</strong> postings can be posted to the associated Twitter account. You can choose to do so by default (here) or for every posting separately in the posting options when writing the entry.') .'</p>';
			$s .= '<div id="twitter-enable-wrapper">';
			$s .= '<label id="twitter-enable-label" for="twitter-checkbox">'. t('Allow posting to Twitter'). '</label>';
			$s .= '<input id="twitter-checkbox" type="checkbox" name="twitter-enable" value="1" ' . $checked . '/>';
                        $s .= '<div class="clear"></div>';
                        $s .= '<label id="twitter-default-label" for="twitter-default">'. t('Send public postings to Twitter by default') .'</label>';
                        $s .= '<input id="twitter-default" type="checkbox" name="twitter-default" value="1" ' . $defchecked . '/>';
			$s .= '</div><div class="clear"></div>';

			$s .= '<div id="twitter-disconnect-wrapper">';
                        $s .= '<label id="twitter-disconnect-label" for="twitter-disconnect">'. t('Clear OAuth configuration') .'</label>';
                        $s .= '<input id="twitter-disconnect" type="checkbox" name="twitter-disconnect" value="1" />';
			$s .= '</div><div class="clear"></div>';
			$s .= '<div class="settings-submit-wrapper" ><input type="submit" name="twitter-submit" class="settings-submit" value="' . t('Submit') . '" /></div>'; 
		}
	}
        $s .= '</div><div class="clear"></div></div>';
}


function twitter_post_hook(&$a,&$b) {

	/**
	 * Post to Twitter
	 */

        logger('twitter post invoked');

	if((local_user()) && (local_user() == $b['uid']) && (! $b['private']) && (! $b['parent']) ) {

		// Twitter is not considered a private network
		if($b['prvnets'])
			return;


		load_pconfig(local_user(), 'twitter');

		$ckey    = get_config('twitter', 'consumerkey'  );
		$csecret = get_config('twitter', 'consumersecret' );
		$otoken  = get_pconfig(local_user(), 'twitter', 'oauthtoken'  );
		$osecret = get_pconfig(local_user(), 'twitter', 'oauthsecret' );

		if($ckey && $csecret && $otoken && $osecret) {

			$twitter_post = intval(get_pconfig(local_user(),'twitter','post'));
			$twitter_enable = (($twitter_post && x($_POST,'twitter_enable')) ? intval($_POST['twitter_enable']) : 0);

			// if API is used, default to the chosen settings
			if($_POST['api_source'] && intval(get_pconfig(local_user(),'twitter','post_by_default')))
				$twitter_enable = 1;

			if($twitter_post && $twitter_enable) {
				logger('Posting to Twitter', LOGGER_DEBUG);
				require_once('library/twitteroauth.php');
				require_once('include/bbcode.php');	
				$tweet = new TwitterOAuth($ckey,$csecret,$otoken,$osecret);
				$max_char = 140; // max. length for a tweet
				$msg = strip_tags(bbcode($b['body']));
				if ( strlen($msg) > $max_char) {
					$shortlink = "";
					require_once('library/slinky.php');
					// post url = base url + /display/ + owner + post id
					// we construct this from the Owner link and replace
					// profile by display - this will cause an error when
					// /profile/ is in the owner url twice but I don't
					// think this will be very common...
					$posturl = str_replace('/profile/','/display/',$b['owner-link']).'/'.$b['id'];
					$slinky = new Slinky( $posturl );
					// setup a cascade of shortening services
					// try to get a short link from these services
					// in the order ur1.ca, trim, id.gd, tinyurl
					$slinky->set_cascade( array( new Slinky_UR1ca(), new Slinky_Trim(), new Slinky_IsGd(), new Slinky_TinyURL() ) );
					$shortlink = $slinky->short();
					// the new message will be shortened such that "... $shortlink"
					// will fit into the character limit
					$msg = substr($msg, 0, $max_char-strlen($shortlink)-4);
					$msg .= '... ' . $shortlink;
				}
                // and now tweet it :-)
				if(strlen($msg)) {
					$result = $tweet->post('statuses/update', array('status' => $msg));
					logger('twitter_post returns: ' . $result);
				}

			}
		}
	}
}

function twitter_plugin_admin_post(&$a){
	$consumerkey	=	((x($_POST,'consumerkey'))		? notags(trim($_POST['consumerkey']))	: '');
	$consumersecret	=	((x($_POST,'consumersecret'))	? notags(trim($_POST['consumersecret'])): '');
	set_config('twitter','consumerkey',$consumerkey);
	set_config('twitter','consumersecret',$consumersecret);
	info( t('Settings updated.'). EOL );
}
function twitter_plugin_admin(&$a, &$o){
	$t = file_get_contents( dirname(__file__). "/admin.tpl" );
	$o = replace_macros($t, array(
		'$submit' => t('Submit'),
								// name, label, value, help, [extra values]
		'$consumerkey' => array('consumerkey', t('Consumer key'),  get_config('twitter', 'consumerkey' ), ''),
		'$consumersecret' => array('consumersecret', t('Consumer secret'),  get_config('twitter', 'consumersecret' ), '')
	));
}
