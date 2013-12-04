<?php /** @file */

require_once('include/contact_widgets.php');
require_once('include/items.php');
require_once("include/bbcode.php");
require_once('include/security.php');
require_once('include/conversation.php');
require_once('include/acl_selectors.php');


function profile_init(&$a) {

	if(argc() > 1)
		$which = argv(1);
	else {
		notice( t('Requested profile is not available.') . EOL );
		$a->error = 404;
		return;
	}

	$profile = '';
	$channel = $a->get_channel();

	if((local_user()) && (argc() > 2) && (argv(2) === 'view')) {
		$which = $channel['channel_address'];
		$profile = argv(1);		
		$r = q("select profile_guid from profile where id = %d and uid = %d limit 1",
			intval($profile),
			intval(local_user())
		);
		if(! $r)
			$profile = '';
		$profile = $r[0]['profile_guid'];
	}

	$a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . $a->get_baseurl() . '/feed/' . $which .'" />' . "\r\n" ;

	if(! $profile) {
		$x = q("select channel_id as profile_uid from channel where channel_address = '%s' limit 1",
			dbesc(argv(1))
		);
		if($x) {
			$a->profile = $x[0];
		}
	}
//		$channel_display = get_pconfig($a->profile['profile_uid'],'system','channel_format');
//		if(! $channel_display)
	profile_load($a,$which,$profile);


}


function profile_aside(&$a) {


	profile_create_sidebar($a);

}


function profile_content(&$a, $update = 0) {

	if(get_config('system','block_public') && (! get_account_id()) && (! remote_user())) {
			return login();
	}



	$groups = array();

	$tab = 'profile';
	$o = '';


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


	$o .= advanced_profile($a);
	call_hooks('profile_advanced',$o);
	return $o;

}
