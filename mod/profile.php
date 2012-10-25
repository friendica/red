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

	$cat = ((x($_REQUEST,'cat')) ? htmlspecialchars($_REQUEST['cat']) : '');

	profile_load($a,$which,$profile);

	$a->set_widget('archive',posted_date_widget($a->get_baseurl(true) . '/profile/' . $a->profile['nickname'],$a->profile['profile_uid'],true));	
	$a->set_widget('categories',categories_widget($a->get_baseurl(true) . '/profile/' . $a->profile['nickname'],$cat));

}


function profile_content(&$a, $update = 0) {

	$category = $datequery = $datequery2 = '';

	if(argc() > 2) {
		for($x = 2; $x < argc(); $x ++) {
			if(is_a_date_arg(argv($x))) {
				if($datequery)
					$datequery2 = escape_tags(argv($x));
				else
					$datequery = escape_tags(argv($x));
			}
		}
	}

	if(get_config('system','block_public') && (! get_account_id()) && (! remote_user())) {
			return login();
	}

	$channel = $a->get_channel();

	require_once("include/bbcode.php");
	require_once('include/security.php');
	require_once('include/conversation.php');
	require_once('include/acl_selectors.php');
	require_once('include/items.php');

	$groups = array();

	$tab = 'posts';
	$o = '';

	if($update) {
		// Ensure we've got a profile owner if updating.
		$a->profile['profile_uid'] = $update;
	}
	else {
		if($a->profile['profile_uid'] == local_user()) {
			nav_set_selected('home');
		}
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

	if(! $update) {


		if(x($_GET,'tab'))
			$tab = notags(trim($_GET['tab']));

//		$o.=profile_tabs($a, $is_owner, $a->profile['nickname']);


		if($tab === 'profile') {
			require_once('include/profile_advanced.php');
			$o .= advanced_profile($a);
			call_hooks('profile_advanced',$o);
			return $o;
		}


		$o .= common_friends_visitor_widget($a->profile['profile_uid']);


		$commpage = (($a->profile['page-flags'] == PAGE_COMMUNITY) ? true : false);
		$commvisitor = (($commpage && $remote_contact == true) ? true : false);

		$celeb = ((($a->profile['page-flags'] == PAGE_SOAPBOX) || ($a->profile['page-flags'] == PAGE_COMMUNITY)) ? true : false);

		if(can_write_wall($a,$a->profile['profile_uid'])) {

			$x = array(
				'is_owner' => $is_owner,
            	'allow_location' => ((($is_owner || $commvisitor) && $a->profile['allow_location']) ? true : false),
	            'default_location' => (($is_owner) ? $a->user['default-location'] : ''),
    	        'nickname' => $channel['channel_address'],
        	    'lockstate' => (((strlen($channel['channel_allow_cid'])) || (strlen($channel['channel_allow_gid'])) || (strlen($channel['channel_deny_cid'])) || (strlen($channel['channel_deny_gid']))) ? 'lock' : 'unlock'),
            	'acl' => (($is_owner) ? populate_acl($channel, $celeb) : ''),
	            'bang' => '',
    	        'visitor' => (($is_owner || $commvisitor) ? 'block' : 'none'),
        	    'profile_uid' => $a->profile['profile_uid']
        	);

        	$o .= status_editor($a,$x);
		}

	}


	/**
	 * Get permissions SQL - if $remote_contact is true, our remote user has been pre-verified and we already have fetched his/her groups
	 */

	$sql_extra = item_permissions_sql($a->profile['profile_uid'],$remote_contact,$groups);


	if($update) {

		$r = q("SELECT distinct(parent) AS `item_id` from item
			WHERE uid = %d AND item_restrict = 0
			AND item_flags &  %d
			$sql_extra
			ORDER BY created DESC",
			intval($a->profile['profile_uid']),
			intval(ITEM_WALL)
		);

	}
	else {

		if(x($category)) {
			$sql_extra .= protect_sprintf(file_tag_file_query('item',$category,'category'));
		}

		if($datequery) {
			$sql_extra2 .= protect_sprintf(sprintf(" AND item.created <= '%s' ", dbesc(datetime_convert(date_default_timezone_get(),'',$datequery))));
		}
		if($datequery2) {
			$sql_extra2 .= protect_sprintf(sprintf(" AND item.created >= '%s' ", dbesc(datetime_convert(date_default_timezone_get(),'',$datequery2))));
		}


		$a->set_pager_itemspage(40);

		$pager_sql = sprintf(" LIMIT %d, %d ",intval($a->pager['start']), intval($a->pager['itemspage']));

		$r = q("SELECT id AS item_id FROM item 
			WHERE uid = %d AND item_restrict = 0
			AND item_flags & %d
			$sql_extra $sql_extra2
			ORDER BY created DESC $pager_sql ",
			intval($a->profile['profile_uid']),
			intval(ITEM_WALL|ITEM_THREAD_TOP)

		);

	}

	if($r && count($r)) {

		$parents_str = ids_to_querystr($r,'item_id');
 
		$items = q("SELECT `item`.*, `item`.`id` AS `item_id` 
			FROM `item`
			WHERE `item`.`uid` = %d AND `item`.`item_restrict` = 0
			AND `item`.`parent` IN ( %s )
			$sql_extra ",
			intval($a->profile['profile_uid']),
			dbesc($parents_str)
		);

		xchan_query($items);
		$items = fetch_post_tags($items);
		$items = conv_sort($items,'created');

	} else {
		$items = array();
	}


	if((! $update) && ($tab === 'posts')) {

		// This is ugly, but we can't pass the profile_uid through the session to the ajax updater,
		// because browser prefetching might change it on us. We have to deliver it with the page.

		$o .= '<div id="live-profile"></div>' . "\r\n";
		$o .= "<script> var profile_uid = " . $a->profile['profile_uid'] 
			. "; var netargs = '?f='; var profile_page = " . $a->pager['page'] . "; </script>\r\n";
	}


	if($is_owner) {
		$r = q("UPDATE `item` SET `item_flags` = item_flags - %d
			WHERE item_flags & %d AND `uid` = %d",
			intval(ITEM_UNSEEN),
			intval(ITEM_UNSEEN|ITEM_WALL),
			intval(local_user())
		);
	}

	$o .= conversation($a,$items,'profile',$update);

	if(! $update)
		$o .= alt_pager($a,count($items));

	return $o;
}
