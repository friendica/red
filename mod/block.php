<?php

require_once('include/items.php');
require_once('include/conversation.php');
require_once('include/page_widgets.php');

function block_init(&$a) {

	$which = argv(1);
	$profile = 0;
	profile_load($a,$which,$profile);

	if($a->profile['profile_uid'])
		head_set_icon($a->profile['thumb']);

}


function block_content(&$a) {

	if(! perm_is_allowed($a->profile['profile_uid'],get_observer_hash(),'view_pages')) {
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
		where item.uid = %d and sid = '%s' and service = 'BUILDBLOCK' and 
		item_restrict = %d $sql_options $revision limit 1",
		intval($u[0]['channel_id']),
		dbesc($page_id),
		intval(ITEM_BUILDBLOCK)
	);

	if(! $r) {

		// Check again with no permissions clause to see if it is a permissions issue

		$x = q("select item.* from item left join item_id on item.id = item_id.iid
		where item.uid = %d and sid = '%s' and service = 'BUILDBLOCK' and 
		item_restrict = %d $revision limit 1",
			intval($u[0]['channel_id']),
			dbesc($page_id),
			intval(ITEM_BUILDBLOCK)
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

	xchan_query($r);
	$r = fetch_post_tags($r,true);

	$o .= prepare_page($r[0]);
	return $o;

}
