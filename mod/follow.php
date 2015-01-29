<?php


require_once('include/follow.php');

function follow_init(&$a) {

	if(! local_channel()) {
		return;
	}

	$uid = local_channel();
	$url = notags(trim($_REQUEST['url']));
	$return_url = $_SESSION['return_url'];
	$confirm = intval($_REQUEST['confirm']);

	$result = new_contact($uid,$url,$a->get_channel(),true,$confirm);
	
	if($result['success'] == false) {
		if($result['message'])
			notice($result['message']);
		goaway($return_url);
	}

	info( t('Channel added.') . EOL);

	// If we can view their stream, pull in some posts

	if(($result['abook']['abook_their_perms'] & PERMS_R_STREAM) || ($result['abook']['xchan_network'] === 'rss'))
		proc_run('php','include/onepoll.php',$result['abook']['abook_id']);

	goaway(z_root() . '/connedit/' . $result['abook']['abook_id'] . '?f=&follow=1');

}

function follow_content(&$a) {

	if(! local_channel()) {
		return login();
	}
}