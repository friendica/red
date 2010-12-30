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

	if((local_user()) && (local_user() == $b['uid']) && (! $b['private'])) {

		$appid  = get_config('system', 'facebook_appid'  );
		$secret = get_config('system', 'facebook_secret' );

		if($appid && $secret) {

			$fb_post = get_pconfig($local_user(),'facebook','post');

			if($fb_post) {
				require_once('library/facebook.php');
				require_once('include/bbcode.php');	

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