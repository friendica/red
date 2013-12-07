<?php

require_once('include/contact_widgets.php');
require_once('include/items.php');
require_once("include/bbcode.php");
require_once('include/security.php');
require_once('include/conversation.php');
require_once('include/acl_selectors.php');
require_once('include/permissions.php');


function channel_init(&$a) {

	$which = null;
	if(argc() > 1)
		$which = argv(1);
	if(! $which) {
		if(local_user()) {
			$channel = $a->get_channel();
			if($channel && $channel['channel_address'])
			$which = $channel['channel_address'];
		}
	}
	if(! $which) {
		notice( t('You must be logged in to see this page.') . EOL );
		return;
	}

	$profile = 0;
	$channel = $a->get_channel();

	if((local_user()) && (argc() > 2) && (argv(2) === 'view')) {
		$which = $channel['channel_address'];
		$profile = argv(1);		
	}

	$a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . $a->get_baseurl() . '/feed/' . $which .'" />' . "\r\n" ;

	// Run profile_load() here to make sure the theme is set before
	// we start loading content

	profile_load($a,$which,$profile);

}


function channel_aside(&$a) {


	if(! $a->profile['profile_uid'])
		return;

	$channel_display = get_pconfig($a->profile['profile_uid'],'system','channel_format');
	if(! $channel_display)
		profile_create_sidebar($a);

	if($channel_display === 'full')
		$a->page['template'] = 'full';
	else {
		$cat = ((x($_REQUEST,'cat')) ? htmlspecialchars($_REQUEST['cat']) : '');
		$a->set_widget('archive',posted_date_widget($a->get_baseurl(true) . '/channel/' . $a->profile['channel_address'],$a->profile['profile_uid'],true));  
		$a->set_widget('categories',categories_widget($a->get_baseurl(true) . '/channel/' . $a->profile['channel_address'],$cat));
	}
	if(feature_enabled($a->profile['profile_uid'],'tagadelic'))
		$a->set_widget('tagcloud',tagblock('search',$a->profile['profile_uid'],50,$a->profile['channel_hash'],ITEM_WALL));

}


function channel_content(&$a, $update = 0, $load = false) {

	$category = $datequery = $datequery2 = '';

	$datequery = ((x($_GET,'dend') && is_a_date_arg($_GET['dend'])) ? notags($_GET['dend']) : '');
	$datequery2 = ((x($_GET,'dbegin') && is_a_date_arg($_GET['dbegin'])) ? notags($_GET['dbegin']) : '');

	if(get_config('system','block_public') && (! get_account_id()) && (! remote_user())) {
			return login();
	}

	$category = ((x($_REQUEST,'cat')) ? $_REQUEST['cat'] : '');

	$groups = array();

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

	$is_owner = (((local_user()) && ($a->profile['profile_uid'] == local_user())) ? true : false);

	$observer = $a->get_observer();
	$ob_hash = (($observer) ? $observer['xchan_hash'] : '');

	$perms = get_all_perms($a->profile['profile_uid'],$ob_hash);

	if(! $perms['view_stream']) {
			// We may want to make the target of this redirect configurable
			if($perms['view_profile']) {
				notice( t('Insufficient permissions.  Request redirected to profile page.') . EOL);
				goaway (z_root() . "/profile/" . $a->profile['channel_address']);
			}
		notice( t('Permission denied.') . EOL);
		return;
	}


	if(! $update) {

		$o .= profile_tabs($a, $is_owner, $a->profile['channel_address']);

		$o .= common_friends_visitor_widget($a->profile['profile_uid']);

		$channel_acl = array(
			'allow_cid' => $channel['channel_allow_cid'], 
			'allow_gid' => $channel['channel_allow_gid'], 
			'deny_cid' => $channel['channel_deny_cid'], 
			'deny_gid' => $channel['channel_deny_gid']
		); 


		if($perms['post_wall']) {

			$x = array(
				'is_owner' => $is_owner,
            	'allow_location' => ((($is_owner || $observer) && (intval(get_pconfig($a->profile['profile_uid'],'system','use_browser_location')))) ? true : false),
	            'default_location' => (($is_owner) ? $a->profile['channel_location'] : ''),
    	        'nickname' => $a->profile['channel_address'],
        	    'lockstate' => (((strlen($a->profile['channel_allow_cid'])) || (strlen($a->profile['channel_allow_gid'])) || (strlen($a->profile['channel_deny_cid'])) || (strlen($a->profile['channel_deny_gid']))) ? 'lock' : 'unlock'),
            	'acl' => (($is_owner) ? populate_acl($channel_acl) : ''),
				'showacl' => (($is_owner) ? 'yes' : ''),
	            'bang' => '',
    	        'visitor' => (($is_owner || $observer) ? 'block' : 'none'),
        	    'profile_uid' => $a->profile['profile_uid']
        	);

        	$o .= status_editor($a,$x);
		}

	}


	/**
	 * Get permissions SQL - if $remote_contact is true, our remote user has been pre-verified and we already have fetched his/her groups
	 */


	$sql_extra = item_permissions_sql($a->profile['profile_uid'],$remote_contact,$groups);


	if(($update) && (! $load)) {

		$r = q("SELECT distinct parent AS `item_id` from item
			left join abook on item.author_xchan = abook.abook_xchan
			WHERE uid = %d AND item_restrict = 0
			AND (item_flags &  %d) AND ( item_flags & %d ) 
			AND ((abook.abook_flags & %d) = 0 or abook.abook_flags is null)
			$sql_extra
			ORDER BY created DESC",
			intval($a->profile['profile_uid']),
			intval(ITEM_WALL),
			intval(ITEM_UNSEEN),
			intval(ABOOK_FLAG_BLOCKED)
		);

	}
	else {


		if(x($category)) {
		        $sql_extra .= protect_sprintf(term_query('item', $category, TERM_CATEGORY));
		}

		if($datequery) {
			$sql_extra2 .= protect_sprintf(sprintf(" AND item.created <= '%s' ", dbesc(datetime_convert(date_default_timezone_get(),'',$datequery))));
		}
		if($datequery2) {
			$sql_extra2 .= protect_sprintf(sprintf(" AND item.created >= '%s' ", dbesc(datetime_convert(date_default_timezone_get(),'',$datequery2))));
		}

		$itemspage = get_pconfig(local_user(),'system','itemspage');
		$a->set_pager_itemspage(((intval($itemspage)) ? $itemspage : 20));
		$pager_sql = sprintf(" LIMIT %d, %d ",intval($a->pager['start']), intval($a->pager['itemspage']));

		if($load) {
			$r = q("SELECT distinct id AS item_id FROM item 
				left join abook on item.author_xchan = abook.abook_xchan
				WHERE uid = %d AND item_restrict = 0
				AND (item_flags &  %d) and (item_flags & %d)
				AND ((abook.abook_flags & %d) = 0 or abook.abook_flags is null)
				$sql_extra $sql_extra2
				ORDER BY created DESC $pager_sql ",
				intval($a->profile['profile_uid']),
				intval(ITEM_WALL),
				intval(ITEM_THREAD_TOP),
				intval(ABOOK_FLAG_BLOCKED)

			);
		}
		else {
			$r = array();
		}
	}

	if($r) {

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
		$items = fetch_post_tags($items, true);
		$items = conv_sort($items,'created');

	} else {
		$items = array();
	}


	if((! $update) && (! $load)) {

		// This is ugly, but we can't pass the profile_uid through the session to the ajax updater,
		// because browser prefetching might change it on us. We have to deliver it with the page.

		$o .= '<div id="live-channel"></div>' . "\r\n";
		$o .= "<script> var profile_uid = " . $a->profile['profile_uid'] 
			. "; var netargs = '?f='; var profile_page = " . $a->pager['page'] . "; </script>\r\n";

		$a->page['htmlhead'] .= replace_macros(get_markup_template("build_query.tpl"),array(
			'$baseurl' => z_root(),
			'$pgtype' => 'channel',
			'$uid' => (($a->profile['profile_uid']) ? $a->profile['profile_uid'] : '0'),
			'$gid' => '0',
			'$cid' => '0',
			'$cmin' => '0',
			'$cmax' => '0',
			'$star' => '0',
			'$liked' => '0',
			'$conv' => '0',
			'$spam' => '0',
			'$nouveau' => '0',
			'$wall' => '1',
			'$page' => (($a->pager['page'] != 1) ? $a->pager['page'] : 1),
			'$search' => '',
			'$order' => '',
			'$file' => '',
			'$cats' => (($category) ? $category : ''),
			'$mid' => '',
			'$dend' => $datequery,
			'$dbegin' => $datequery2
		));


	}



	if($is_owner) {

		$r = q("UPDATE item SET item_flags = (item_flags ^ %d)
			WHERE (item_flags & %d) AND (item_flags & %d) AND uid = %d ",
			intval(ITEM_UNSEEN),
			intval(ITEM_UNSEEN),
			intval(ITEM_WALL),
			intval(local_user())
		);
	}


	$o .= conversation($a,$items,'channel',$update,'client');

	if(! $update)
		$o .= alt_pager($a,count($items));

	return $o;
}
