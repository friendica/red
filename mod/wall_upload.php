<?php

require_once('include/photo/photo_driver.php');
require_once('include/identity.php');
require_once('include/photos.php');



function wall_upload_post(&$a) {

	$using_api = ((x($_FILES,'media')) ? true : false); 

	if($using_api) {
		require_once('include/api.php');
		$user_info = api_get_user($a);
		$nick = $user_info['screen_name'];
	}
	else {
		if(argc() > 1)
			$nick = argv(1);
	}

	$channel = (($nick) ? get_channel_by_nick($nick) : false);

	if(! $channel) {
		if($using_api)
			return;
		notice( t('Channel not found.') . EOL);
		killme();
	}

	$observer = $a->get_observer();

	$args = array( 'source' => 'editor', 'album' => t('Wall Photos'), 
			'not_visible' => 1, 'contact_allow' => array($channel['channel_hash']));

 	$ret = photo_upload($channel,$observer,$args);

	if(! $ret['success']) {
		if($using_api)
			return;
		notice($ret['message']);
		killme();
	}

	$m = $ret['body'];

	// This might make Friendica for Android uploads work again, as it won't have any knowledge of zrl and zmg tags
	// and these tags probably aren't useful with other client apps. 

	if($using_api)
		return("\n\n" . $ret['body'] . "\n\n");
	else
		echo  "\n\n" . $ret['body'] . "\n\n";
	killme();
}
