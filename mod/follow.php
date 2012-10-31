<?php

require_once('Scrape.php');
require_once('include/follow.php');

function follow_init(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	$uid = local_user();
	$url = notags(trim($_REQUEST['url']));
	$return_url = $_SESSION['return_url'];


	$result = new_contact($uid,$url,true);

	if($result['success'] == false) {
		if($result['message'])
			notice($result['message']);
		goaway($return_url);
	}

	info( t('Channel added') . EOL);

	if(strstr($return_url,'channel'))
		goaway($a->get_baseurl() . '/channel/' . $result['channel_id']);

	goaway($return_url);
	// NOTREACHED
}
