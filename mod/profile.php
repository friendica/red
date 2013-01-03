<?php

function profile_init(&$a) {

	$a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . $a->get_baseurl() . '/feed/' . $which .'" />' . "\r\n" ;

}


function profile_aside(&$a) {

	require_once('include/contact_widgets.php');
	require_once('include/items.php');

	if(argc() > 1)
		$which = argv(1);
	else {
		notice( t('Requested profile is not available.') . EOL );
		$a->error = 404;
		return;
	}

	$profile = 0;
	$channel = $a->get_channel();

	if((local_user()) && (argc() > 2) && (argv(2) === 'view')) {
		$which = $channel['channel_address'];
		$profile = argv(1);		
	}


	$x = q("select uid as profile_uid from channel where address = '%s' limit 1",
		dbesc(argv(1)
	);
	if($x) {
		$a->profile = $x[0];
		$channel_display = get_pconfig($a->profile['profile_uid'],'system','channel_format');
		if(! $channel_display)
			profile_load($a,$which,$profile);
		if($channel_display === 'full')
			$a->page['template'] = 'full';
		else {
			$a->set_widget('archive',posted_date_widget($a->get_baseurl(true) . '/channel/' . $a->profile['nickname'],$a->profile['profile_uid'],true));	
			$a->set_widget('categories',categories_widget($a->get_baseurl(true) . '/channel/' . $a->profile['nickname'],$cat));
		}
	}
}


function profile_content(&$a, $update = 0) {

	if(get_config('system','block_public') && (! get_account_id()) && (! remote_user())) {
			return login();
	}


	require_once("include/bbcode.php");
	require_once('include/security.php');
	require_once('include/conversation.php');
	require_once('include/acl_selectors.php');
	require_once('include/items.php');

	$groups = array();

	$tab = 'profile';
	$o = '';

	if($a->profile['profile_uid'] == local_user()) {
		nav_set_selected('home');
	}

	$contact = null;
	$remote_contact = false;

	$contact_id = 0;

	if(is_array($_SESSION['remote'])) {
		foreach($_SESSION['remote'] as $v) {
			if($v['uid'] == $a->profile['profile_uid']) {
				$contact_id = $v['cid'];
				break;
			}
		}
	}

	if($contact_id) {
		$groups = init_groups_visitor($contact_id);
		$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($contact_id),
			intval($a->profile['profile_uid'])
		);
		if(count($r)) {
			$contact = $r[0];
			$remote_contact = true;
		}
	}

	if(! $remote_contact) {
		if(local_user()) {
			$contact_id = $_SESSION['cid'];
			$contact = $a->contact;
		}
	}

	$is_owner = ((local_user()) && (local_user() == $a->profile['profile_uid']) ? true : false);

	if($a->profile['hidewall'] && (! $is_owner) && (! $remote_contact)) {
		notice( t('Access to this profile has been restricted.') . EOL);
		return;
	}


	$o .= profile_tabs($a, $is_owner, $a->profile['channel_address']);


	require_once('include/profile_advanced.php');
	$o .= advanced_profile($a);
	call_hooks('profile_advanced',$o);
	return $o;

}
