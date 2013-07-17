<?php

require_once('include/items.php');
require_once('include/conversation.php');
function page_init(&$a) {
	// We need this to make sure the channel theme is always loaded.
        $which = argv(1);
        $profile = 0;
        $channel = $a->get_channel();

        if((local_user()) && (argc() > 2) && (argv(2) === 'view')) {
                $which = $channel['channel_address'];
                $profile = argv(1);
        }

        profile_load($a,$which,$profile);

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

	$r = q("select item.* from item left join item_id on item.id = item_id.iid
		where item.uid = %d and sid = '%s' and service = 'WEBPAGE' and 
		item_restrict = %d limit 1",
		intval($u[0]['channel_id']),
		dbesc($page_id),
		intval(ITEM_WEBPAGE)
	);

	if(! $r) {
		notice( t('Item not found.') . EOL);
		return;
	}

	xchan_query($r);
	$r = fetch_post_tags($r,true);
	$a->profile = array('profile_uid' => $u[0]['channel_id']);
	$o .= prepare_page($r[0]);
	return $o;

}
