<?php

/*   StatusNet Plugin for Friendika
 *
 *   Author: Tobias Diekershoff
 *           tobias.diekershoff@gmx.net
 *
 *   License:3-clause BSD license (same as Friendika)
 *
 *   Configuration:
 *     To activate the plugin itself add it to the $a->config['system']['addon']
 *     setting. After this, your user can configure their Twitter account settings
 *     from "Settings -> Plugin Settings".
 *
 *     Requirements: PHP5, curl [Slinky library]
 *
 *     Documentation: http://diekershoff.homeunix.net/redmine/wiki/friendikaplugin/StatusNet_Plugin
 */

/*   __TODO__
 *
 *   - what about multimedia content?
 *     so far we just strip HTML tags from the message
 */


/***
 * We have to alter the TwitterOAuth class a little bit to work with any StatusNet
 * installation abroad. Basically it's only make the API path variable and be happy.
 *
 * Thank you guys for the Twitter compatible API!
 */
require_once('addon/twitter/twitteroauth.php');
class StatusNetOAuth extends TwitterOAuth {
    function get_maxlength() {
        $config = $this->get($this->host . 'statusnet/config.json');
        return $config->site->textlimit;
    }
    function accessTokenURL()  { return $this->host.'oauth/access_token'; }
    function authenticateURL() { return $this->host.'oauth/authenticate'; } 
    function authorizeURL() { return $this->host.'oauth/authorize'; }
    function requestTokenURL() { return $this->host.'oauth/request_token'; }
    function __construct($apipath, $consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {
        parent::__construct($consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret);
        $this->host = $apipath;
    }
}

function statusnet_install() {
	//  we need some hooks, for the configuration and for sending tweets
	register_hook('plugin_settings', 'addon/statusnet/statusnet.php', 'statusnet_settings'); 
	register_hook('plugin_settings_post', 'addon/statusnet/statusnet.php', 'statusnet_settings_post');
	register_hook('post_local_end', 'addon/statusnet/statusnet.php', 'statusnet_post_hook');
	logger("installed statusnet");
}


function statusnet_uninstall() {
	unregister_hook('plugin_settings', 'addon/statusnet/statusnet.php', 'statusnet_settings'); 
	unregister_hook('plugin_settings_post', 'addon/statusnet/statusnet.php', 'statusnet_settings_post');
	unregister_hook('post_local_end', 'addon/statusnet/statusnet.php', 'statusnet_post_hook');
}

function statusnet_settings_post ($a,$post) {
	if(! local_user())
	    return;
	if (isset($_POST['statusnet-disconnect'])) {
	    /***
	     * if the statusnet-disconnect checkbox is set, clear the statusnet configuration
	     * TODO can we revoke the access tokens at Twitter and do we need to do so?
	     */
            del_pconfig( local_user(), 'statusnet', 'consumerkey'  );
	    del_pconfig( local_user(), 'statusnet', 'consumersecret' );
	    del_pconfig( local_user(), 'statusnet', 'post' );
            del_pconfig( local_user(), 'statusnet', 'oauthtoken' );
            del_pconfig( local_user(), 'statusnet', 'oauthsecret' );
            del_pconfig( local_user(), 'statusnet', 'baseapi' );
	} else {
        if (isset($_POST['statusnet-consumersecret'])) {
            set_pconfig(local_user(), 'statusnet', 'consumerkey', $_POST['statusnet-consumerkey']);
            set_pconfig(local_user(), 'statusnet', 'consumersecret', $_POST['statusnet-consumersecret']);
            set_pconfig(local_user(), 'statusnet', 'baseapi', $_POST['statusnet-baseapi']);
            header('Location: '.$a->get_baseurl().'/settings/addon');
        } else {
	if (isset($_POST['statusnet-pin'])) {
	    //  if the user supplied us with a PIN from Twitter, let the magic of OAuth happen
	    logger('got a StatusNet security code');
            $api     = get_pconfig(local_user(), 'statusnet', 'baseapi');
	    $ckey    = get_pconfig(local_user(), 'statusnet', 'consumerkey'  );
	    $csecret = get_pconfig(local_user(), 'statusnet', 'consumersecret' );
	    //  the token and secret for which the PIN was generated were hidden in the settings
	    //  form as token and token2, we need a new connection to Twitter using these token
	    //  and secret to request a Access Token with the PIN
	    $connection = new StatusNetOAuth($api, $ckey, $csecret, $_POST['statusnet-token'], $_POST['statusnet-token2']);
	    $token   = $connection->getAccessToken( $_POST['statusnet-pin'] );
	    //  ok, now that we have the Access Token, save them in the user config
 	    set_pconfig(local_user(),'statusnet', 'oauthtoken',  $token['oauth_token']);
	    set_pconfig(local_user(),'statusnet', 'oauthsecret', $token['oauth_token_secret']);
            set_pconfig(local_user(),'statusnet', 'post', 1);
            //  reload the Addon Settings page, if we don't do it see Bug #42
            header('Location: '.$a->get_baseurl().'/settings/addon');
	} else {
	    //  if no PIN is supplied in the POST variables, the user has changed the setting
	    //  to post a tweet for every new __public__ posting to the wall
	    set_pconfig(local_user(),'statusnet','post',intval($_POST['statusnet-enable']));
	}}}
}
function statusnet_settings(&$a,&$s) {
        if(! local_user())
                return;
        $a->page['htmlhead'] .= '<link rel="stylesheet"  type="text/css" href="' . $a->get_baseurl() . '/addon/statusnet/statusnet.css' . '" media="all" />' . "\r\n";
	/***
	 * 1) Check that we have a base api url and a consumer key & secret
	 * 2) If no OAuthtoken & stuff is present, generate button to get some
	 * 3) Checkbox for "Send public notices (respect size limitation)
	 */
        $api     = get_pconfig(local_user(), 'statusnet', 'baseapi');
	$ckey    = get_pconfig(local_user(), 'statusnet', 'consumerkey' );
	$csecret = get_pconfig(local_user(), 'statusnet', 'consumersecret' );
	$otoken  = get_pconfig(local_user(), 'statusnet', 'oauthtoken'  );
	$osecret = get_pconfig(local_user(), 'statusnet', 'oauthsecret' );
        $enabled = get_pconfig(local_user(), 'statusnet', 'post');
	$checked = (($enabled) ? ' checked="checked" ' : '');
	$s .= '<h3>'.t('StatusNet Posting Settings').'</h3>';

	if ( (!$ckey) && (!$csecret) ) {
		/***
		 * no consumer keys
		 */
		$s .= '<p>'. t('No consumer key pair for StatusNet found. Register your Friendika Account as an desktop client on your StatusNet account, copy the consumer key pair here and enter the API base root.<br />Before you register your own OAuth key pair ask the administrator if there is already a key pair for this Friendika installation at your favorited StatusNet installation.') .'</p>';
                $s .= '<div id="statusnet-consumer-wrapper">';
                $s .= '<label id="statusnet-consumerkey-label" for="statusnet-consumerkey">'. t('OAuth Consumer Key') .'</label>';
		$s .= '<input id="statusnet-consumerkey" type="text" name="statusnet-consumerkey" size="35" /><br />';
                $s .= '<label id="statusnet-consumersecret-label" for="statusnet-consumersecret">'. t('OAuth Consumer Secret') .'</label>';
		$s .= '<input id="statusnet-consumersecret" type="text" name="statusnet-consumersecret" size="35" /><br />';
                $s .= '<label id="statusnet-baseapi-label" for="statusnet-baseapi">'. t('Base API Path (remember the trailing /)') .'</label>';
		$s .= '<input id="statusnet-baseapi" type="text" name="statusnet-baseapi" size="35" /><br  />';
                $s .= '</div><div class="clear"></div>';
                $s .= '<div class="settings-submit-wrapper" ><input type="submit" name="submit" class="settings-submit" value="' . t('Submit') . '" /></div>';
	} else {
		/***
		 * ok we have a consumer key pair now look into the OAuth stuff
		 */
		if ( (!$otoken) && (!$osecret) ) {
			/***
			 * the user has not yet connected the account to statusnet
			 * get a temporary OAuth key/secret pair and display a button with
			 * which the user can request a PIN to connect the account to a
			 * account at statusnet
			 */
			$connection = new StatusNetOAuth($api, $ckey, $csecret);
			$request_token = $connection->getRequestToken('oob');
			$token = $request_token['oauth_token'];
			/***
			 *  make some nice form
			 */
			$s .= '<p>'. t('To connect to your StatusNet account click the button below to get a security code from StatusNet which you have to copy into the input box below and submit the form. Only your <strong>public</strong> posts will be posted to StatusNet.') .'</p>';
			$s .= '<a href="'.$connection->getAuthorizeURL($token,False).'" target="_statusnet"><img src="addon/statusnet/signinwithstatusnet.png" alt="'. t('Log in with StatusNet') .'"></a>';
			$s .= '<div id="statusnet-pin-wrapper">';
			$s .= '<label id="statusnet-pin-label" for="statusnet-pin">'. t('Copy the security code from StatusNet here') .'</label>';
			$s .= '<input id="statusnet-pin" type="text" name="statusnet-pin" />';
			$s .= '<input id="statusnet-token" type="hidden" name="statusnet-token" value="'.$token.'" />';
			$s .= '<input id="statusnet-token2" type="hidden" name="statusnet-token2" value="'.$request_token['oauth_token_secret'].'" />';
                        $s .= '</div><div class="clear"></div>';
                        $s .= '<div class="settings-submit-wrapper" ><input type="submit" name="submit" class="settings-submit" value="' . t('Submit') . '" /></div>';
		} else {
			/***
			 *  we have an OAuth key / secret pair for the user
			 *  so let's give a chance to disable the postings to statusnet
			 */
			$connection = new StatusNetOAuth($api,$ckey,$csecret,$otoken,$osecret);
			$details = $connection->get('account/verify_credentials');
			$s .= '<div id="statusnet-info" ><img id="statusnet-avatar" src="'.$details->profile_image_url.'" /><p id="statusnet-info-block">'. t('Currently connected to: ') .'<a href="'.$details->statusnet_profile_url.'" target="_statusnet">'.$details->screen_name.'</a><br /><em>'.$details->description.'</em></p></div>';
			$s .= '<p>'. t('If enabled all your <strong>public</strong> postings will be posted to the associated StatusNet account as well.') .'</p>';
			$s .= '<div id="statusnet-enable-wrapper">';
			$s .= '<label id="statusnet-enable-label" for="statusnet-checkbox">'. t('Send public postings to StatusNet') .'</label>';
			$s .= '<input id="statusnet-checkbox" type="checkbox" name="statusnet-enable" value="1" ' . $checked . '/>';
			$s .= '</div><div class="clear"></div>';
			$s .= '<div id="statusnet-disconnect-wrapper">';
                        $s .= '<label id="statusnet-disconnect-label" for="statusnet-disconnect">'. t('Clear OAuth configuration') .'</label>';
                        $s .= '<input id="statusnet-disconnect" type="checkbox" name="statusnet-disconnect" value="1" />';
			$s .= '</div><div class="clear"></div>';
			$s .= '<div class="settings-submit-wrapper" ><input type="submit" name="submit" class="settings-submit" value="' . t('Submit') . '" /></div>'; 
		}
	}
        $s .= '</div><div class="clear"></div>';
}


function statusnet_post_hook(&$a,&$b) {

	/**
	 * Post to statusnet
	 */

        logger('StatusNet post invoked');

	if((local_user()) && (local_user() == $b['uid']) && (! $b['private']) && (!$b['parent']) ) {

                load_pconfig(local_user(), 'statusnet');
            
                $api     = get_pconfig(local_user(), 'statusnet', 'baseapi');
                $ckey    = get_pconfig(local_user(), 'statusnet', 'consumerkey'  );
		$csecret = get_pconfig(local_user(), 'statusnet', 'consumersecret' );
		$otoken  = get_pconfig(local_user(), 'statusnet', 'oauthtoken'  );
		$osecret = get_pconfig(local_user(), 'statusnet', 'oauthsecret' );

		if($ckey && $csecret && $otoken && $osecret) {

			$statusnet_post = get_pconfig(local_user(),'statusnet','post');

			if($statusnet_post) {
				require_once('include/bbcode.php');	
				$dent = new StatusNetOAuth($api,$ckey,$csecret,$otoken,$osecret);
				$max_char = $dent->get_maxlength(); // max. length for a dent
				$msg = strip_tags(bbcode($b['body']));
                                if ( strlen($msg) > $max_char) {
                                        $shortlink = "";
                                        require_once('addon/statusnet/slinky.php');
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
				if(strlen($msg))
					$dent->post('statuses/update', array('status' => $msg));
			}
		}
        }
}

