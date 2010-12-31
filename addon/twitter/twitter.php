<?php


function twitter_install() {
	register_hook('post_local_end', 'addon/twitter/twitter.php', 'twitter_post_hook');
}


function twitter_uninstall() {
	unregister_hook('post_local_end', 'addon/twitter/twitter.php', 'twitter_post_hook');
}




function twitter_post_hook(&$a,&$b) {

	/**
	 * Post to Twitter
	 */

	if((local_user()) && (local_user() == $b['uid']) && (! $b['private'])) {

		load_pconfig(local_user(), 'twitter');

		$ckey    = get_pconfig(local_user(), 'twitter', 'consumerkey'  );
		$csecret = get_pconfig(local_user(), 'twitter', 'consumersecret' );
		$otoken  = get_pconfig(local_user(), 'twitter', 'oauthtoken'  );
		$osecret = get_pconfig(local_user(), 'twitter', 'oauthsecret' );

		if($ckey && $csecret && $otoken && $osecret) {

			$twitter_post = get_pconfig(local_user(),'twitter','post');

			if($twitter_post) {
				require_once('addon/twitter/twitteroauth.php');
				require_once('include/bbcode.php');	

				$tweet = new TwitterOAuth($ckey,$csecret,$otoken,$osecret);
				$tweet->post('statuses/update', array('status' => bbcode($b['body'])));
			}
		}
	}
}


