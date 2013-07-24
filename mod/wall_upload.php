<?php

require_once('include/photo/photo_driver.php');
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

	$channel = null;

	if($nick) {		
		$r = q("SELECT channel.* from channel where channel_address = '%s' limit 1",
			dbesc($nick)
		);
		if($r)
			$channel = $r[0];
	}

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
		return(str_replace(array('zrl','zmg'),array('url','img'),$ret['body']));
	else
		echo  "\n\n" . $ret['body'] . "\n\n";
	killme();
}
