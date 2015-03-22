<?php

require_once('include/attach.php');
require_once('include/identity.php');
require_once('include/photos.php');

function wall_attach_post(&$a) {

	if(argc() > 1)
		$channel = get_channel_by_nick(argv(1));
	elseif($_FILES['media']) {
		require_once('include/api.php');
		$user_info = api_get_user($a);
		$nick = $user_info['screen_name'];
		$channel = get_channel_by_nick($user_info['screen_name']);
    }

	if(! $channel)
		killme();

	$observer = $a->get_observer();


	if($_FILES['userfile']['tmp_name']) {
		$x = @getimagesize($_FILES['userfile']['tmp_name']);
		if(($x) && ($x[2] === IMG_GIF || $x[2] === IMG_JPG || $x[2] === IMG_JPEG || $x[2] === IMG_PNG)) {
			$args = array( 'source' => 'editor', 'visible' => 0, 'contact_allow' => array($channel['channel_hash']));
			$ret = photo_upload($channel,$observer,$args);
			if($ret['success']) {
				echo  "\n\n" . $ret['body'] . "\n\n";
				killme();
			}
			if($using_api)
				return;
			notice($ret['message']);
			killme();
		}
	}

	$r = attach_store($channel,(($observer) ? $observer['xchan_hash'] : ''));

	if(! $r['success']) {
		notice( $r['message'] . EOL);
		killme();
	}

	echo  "\n\n" . '[attachment]' . $r['data']['hash'] . ',' . $r['data']['revision'] . '[/attachment]' . "\n";
	killme();

}
