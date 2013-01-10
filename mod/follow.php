<?php


require_once('include/follow.php');

function follow_init(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$uid = local_user();
	$url = notags(trim($_REQUEST['url']));
	$return_url = $_SESSION['return_url'];

	$result = new_contact($uid,$url,$a->get_channel(),true);

	if($result['success'] == false) {
		if($result['message'])
			notice($result['message']);
		goaway($return_url);
	}

	info( t('Channel added') . EOL);

	goaway(z_root() . '/connections/' . $result['abook']['abook_id']);

}
