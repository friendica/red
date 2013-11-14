<?php

require_once('include/items.php');
require_once('include/conversation.php');
require_once('include/page_widgets.php');

function page_init(&$a) {
	// We need this to make sure the channel theme is always loaded.

	$which = argv(1);
	$profile = 0;
	profile_load($a,$which,$profile);

	if($a->profile['profile_uid'])
		head_set_icon($a->profile['thumb']);

}




function page_content(&$a) {

	$observer = $a->get_observer();
	$ob_hash = (($observer) ? $observer['xchan_hash'] : '');

	$perms = get_all_perms($a->profile['profile_uid'],$ob_hash);

	if(! $perms['view_pages']) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	if(argc() < 3) {
		notice( t('Invalid item.') . EOL);
		return;
	}

	$channel_address = argv(1);
	$page_id = argv(2);

	$u = q("select channel_id from channel where channel_address = '%s' limit 1",
		dbesc($channel_address)
	);

	if(! $u) {
		notice( t('Channel not found.') . EOL);
		return;
	}

	if($_REQUEST['rev'])
		$revision = " and revision = " . intval($_REQUEST['rev']) . " ";
	else
		$revision = " order by revision desc ";

	require_once('include/security.php');
	$sql_options = item_permissions_sql($u[0]['channel_id']);

	$r = q("select item.* from item left join item_id on item.id = item_id.iid
		where item.uid = %d and sid = '%s' and service = 'WEBPAGE' and 
		item_restrict = %d $sql_options $revision limit 1",
		intval($u[0]['channel_id']),
		dbesc($page_id),
		intval(ITEM_WEBPAGE)
	);

	if(! $r) {

		// Check again with no permissions clause to see if it is a permissions issue

		$x = q("select item.* from item left join item_id on item.id = item_id.iid
		where item.uid = %d and sid = '%s' and service = 'WEBPAGE' and 
		item_restrict = %d $revision limit 1",
			intval($u[0]['channel_id']),
			dbesc($page_id),
			intval(ITEM_WEBPAGE)
		);
		if($x) {
			// Yes, it's there. You just aren't allowed to see it.
			notice( t('Permission denied.') . EOL);
		}
		else {
			notice( t('Page not found.') . EOL);
		}
		return;
	}

	if($r[0]['layout_mid']) {
		$l = q("select body from item where mid = '%s' and uid = %d limit 1",
			dbesc($r[0]['layout_mid']),
			intval($u[0]['channel_id'])
		);

		if($l) {
			require_once('include/comanche.php');
			comanche_parser(get_app(),$l[0]['body']);
		}
	}


	// logger('layout: ' . print_r($a->layout,true));

	// Use of widgets should be determined by Comanche, but we don't have it on system pages yet, so...

	if ($perms['write_pages']) {
		$chan = $a->channel['channel_id'];
		$who = $channel_address;
		$which = $r[0]['id'];
		$o .= writepages_widget($who,$which);
	}

	xchan_query($r);
	$r = fetch_post_tags($r,true);

	$o .= prepare_body($r[0],true);
	return $o;

}
