<?php


function display_content(&$a) {

	if(intval(get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		notice( t('Public access denied.') . EOL);
		return;
	}

	require_once("include/bbcode.php");
	require_once('include/security.php');
	require_once('include/conversation.php');
	require_once('include/acl_selectors.php');
	require_once('include/items.php');

	$o = '<div id="live-display"></div>' . "\r\n";

	$a->page['htmlhead'] .= replace_macros(get_markup_template('display-head.tpl'), array());


	if(argc() > 1)
		$item_hash = argv(1);

	if(! $item_hash) {
		$a->error = 404;
		notice( t('Item not found.') . EOL);
		return;
	}

	$observer_is_owner = false;

	// This page can be viewed by anybody so the query could be complicated
	// First we'll see if there is a copy of the item which is owned by us - if we're logged in locally.
	// If that fails (or we aren't logged in locally), 
	// query an item in which the observer (if logged in remotely) has cid or gid rights
	// and if that fails, look for a copy of the post that has no privacy restrictions.  
	// If we find the post, but we don't find a copy that we're allowed to look at, this fact needs to be reported.

// FIXME - on the short term, we'll only do the first query.

	$target_item = null;

	if(local_user()) {
		$r = q("select * from item where uri = '%s' and uid = %d limit 1",
			dbesc($item_hash),
			intval(local_user())
		);
		if($r) {
			$owner = local_user();
			$observer_is_owner = true;		
			$target_item = $r[0];
		}
	}


	// Checking for visitors is a bit harder, we'll look for this item from any of their friends that they've auth'd
	// against and see if any of them are writeable.
	// This will be messy.

//	$nick = (($a->argc > 1) ? $a->argv[1] : '');
//	profile_load($a,$nick);
//	profile_aside($a);

//	$item_id = (($a->argc > 2) ? intval($a->argv[2]) : 0);

//	if(! $item_id) {
//		$a->error = 404;
//		notice( t('Item not found.') . EOL);
//		return;
//	}

//	$groups = array();

//	$contact = null;
//	$remote_contact = false;

//	$contact_id = 0;

//	if(is_array($_SESSION['remote'])) {
//		foreach($_SESSION['remote'] as $v) {
//			if($v['uid'] == $a->profile['uid']) {
//				$contact_id = $v['cid'];
//				break;
//			}
//		}
//	}

//	if($contact_id) {
//		$groups = init_groups_visitor($contact_id);
//		$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
//			intval($contact_id),
//			intval($a->profile['uid'])
//		);
//		if(count($r)) {
//			$contact = $r[0];
//			$remote_contact = true;
//		}
//	}

//	if(! $remote_contact) {

//		if(local_user()) {
//			$contact_id = $_SESSION['cid'];
//			$contact = $a->contact;
//		}
//	}

//	$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 1 LIMIT 1",
//		intval($a->profile['uid'])
//	);

//	$is_owner = ((local_user()) && (local_user() == $a->profile['profile_uid']) ? true : false);

	if($a->profile['hidewall'] && (! $is_owner) && (! $remote_contact)) {
		notice( t('Access to this profile has been restricted.') . EOL);
		return;
	}
	
//	if ($is_owner)
//		$celeb = ((($a->user['page-flags'] == PAGE_SOAPBOX) || ($a->user['page-flags'] == PAGE_COMMUNITY)) ? true : false);

//		$x = array(
//			'is_owner' => true,
//			'allow_location' => $a->user['allow_location'],
//			'default_location' => $a->user['default-location'],
//			'nickname' => $a->user['nickname'],
//			'lockstate' => ( (is_array($a->user)) && ((strlen($a->user['allow_cid'])) || (strlen($a->user['allow_gid'])) || (strlen($a->user['deny_cid'])) || (strlen($a->user['deny_gid']))) ? 'lock' : 'unlock'),
//			'acl' => populate_acl($a->user, $celeb),
//			'bang' => '',
//			'visitor' => 'block',
//			'profile_uid' => local_user()
//		);	
//		$o .= status_editor($a,$x,true);


// FIXME
//	$sql_extra = item_permissions_sql($a->profile['uid']);

	if($target_item) {
		$r = q("SELECT * from item where parent = %d",
			intval($target_item['parent'])
		);
	}


	if($r) {

		if((local_user()) && (local_user() == $owner)) {
//			q("UPDATE `item` SET `unseen` = 0 
//				WHERE `parent` = %d AND `unseen` = 1",
//				intval($r[0]['parent'])
//			);
		}

		xchan_query($r);
		$r = fetch_post_tags($r);

		$o .= conversation($a,$r,'display', false);

	}
	else {
		$r = q("SELECT `id`,`deleted` FROM `item` WHERE `id` = '%s' OR `uri` = '%s' LIMIT 1",
			dbesc($item_id),
			dbesc($item_id)
		);
		if(count($r)) {
			if($r[0]['deleted']) {
				notice( t('Item has been removed.') . EOL );
			}
			else {	
				notice( t('Permission denied.') . EOL ); 
			}
		}
		else {
			notice( t('Item not found.') . EOL );
		}

	}

	return $o;
}

