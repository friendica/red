<?php /** @file */


function abook_connections($channel_id, $sql_conditions = '') {
	$r = q("select * from abook left join xchan on abook_xchan = xchan_hash where abook_channel = %d
		and not ( abook_flags & %d ) $sql_conditions",
		intval($channel_id),
		intval(ABOOK_FLAG_SELF)
	);
	return(($r) ? $r : array());
}	

function abook_self($channel_id) {
	$r = q("select * from abook left join xchan on abook_xchan = xchan_hash where abook_channel = %d
		and ( abook_flags & %d ) limit 1",
		intval($channel_id),
		intval(ABOOK_FLAG_SELF)
	);
	return(($r) ? $r[0] : array());
}	

function channelx_by_nick($nick) {
	return q("SELECT * FROM channel left join xchan on channel_hash = xchan_hash WHERE channel_address = '%s'  and not ( channel_pageflags & %d ) LIMIT 1",
		dbesc($nick),
		intval(PAGE_REMOVED)
	);
}

function channelx_by_hash($hash) {
	return q("SELECT * FROM channel left join xchan on channel_hash = xchan_hash WHERE channel_hash = '%s'  and not ( channel_pageflags & %d ) LIMIT 1",
		dbesc($hash),
		intval(PAGE_REMOVED)
	);
}

function channelx_by_n($id) {
	return q("SELECT * FROM channel left join xchan on channel_hash = xchan_hash WHERE channel_id = %d  and not ( channel_pageflags & %d ) LIMIT 1",
		dbesc($id),
		intval(PAGE_REMOVED)
	);
}


function vcard_from_xchan($xchan, $observer = null, $mode = '') {

	$connect = false;
	if(local_user()) {
		$r = q("select * from abook where abook_xchan = '%s' and abook_channel = %d limit 1",
			dbesc($xchan['xchan_hash']),
			intval(local_user())
		);
		if(! $r)
			$connect = t('Connect');
	}

	$url = (($observer) 
		? z_root() . '/magic?f=&dest=' . $xchan['xchan_url'] . '&addr=' . $xchan['xchan_addr'] 
		: $xchan['xchan_url']
	);
					
	return replace_macros(get_markup_template('xchan_vcard.tpl'),array(
		'$name'    => $xchan['xchan_name'],
		'$photo'   => $xchan['xchan_photo_l'],
		'$follow'  => $xchan['xchan_addr'],
		'$connect' => $connect,
		'$newwin'  => (($mode === 'chanview') ? t('New window') : ''),
		'$newtit'  => t('Open the selected location in a different window or browser tab'),
		'$url'     => $url,
	));
}

function abook_toggle_flag($abook,$flag) {

	$r = q("UPDATE abook set abook_flags = (abook_flags ^ %d) where abook_id = %d and abook_channel = %d limit 1",
		intval($flag),
		intval($abook['abook_id']),
		intval($abook['abook_channel'])
	);
	return $r;

}




















// Included here for completeness, but this is a very dangerous operation.
// It is the caller's responsibility to confirm the requestor's intent and
// authorisation to do this.

function user_remove($uid) {

}


function channel_remove($channel_id) {

	if(! $channel_id)
		return;
	$a = get_app();
	logger('Removing channel: ' . $channel_id);

	$r = q("select * from channel where channel_id = %d limit 1", intval($channel_id));

	call_hooks('channel_remove',$r[0]);

	// FIXME notify the directory
	
	// FIXME notify all contacts


	q("DELETE FROM `group` WHERE `uid` = %d", intval($channel_id));
	q("DELETE FROM `group_member` WHERE `uid` = %d", intval($channel_id));
	q("DELETE FROM `event` WHERE `uid` = %d", intval($channel_id));
	q("DELETE FROM `item` WHERE `uid` = %d", intval($channel_id));
	q("DELETE FROM `item_id` WHERE `uid` = %d", intval($channel_id));
	q("DELETE FROM `mail` WHERE `uid` = %d", intval($channel_id));
	q("DELETE FROM `notify` WHERE `uid` = %d", intval($channel_id));
	q("DELETE FROM `photo` WHERE `uid` = %d", intval($channel_id));
	q("DELETE FROM `attach` WHERE `uid` = %d", intval($channel_id));
	q("DELETE FROM `profile` WHERE `uid` = %d", intval($channel_id));
	q("DELETE FROM `pconfig` WHERE `uid` = %d", intval($channel_id));
	q("DELETE FROM `spam` WHERE `uid` = %d", intval($channel_id));

	// We also need a timestamp in the channel DB so we know when to remove the entry.

	$r = q("update channel set channel_pageflags = (channel_pageflags | %d) where channel_id = %d limit 1",
		intval(PAGE_REMOVED),
		intval($channel_id)
	);


	if($channel_id == local_user()) {
		unset($_SESSION['authenticated']);
		unset($_SESSION['uid']);
		goaway($a->get_baseurl());
	}

}

function remove_all_xchan_resources($xchan, $channel_id = 0) {

	if(intval($channel_id)) {



	}
	else {

		// this is somewhat destructive
// FIXME
		// We don't want to be quite as destructive on directories, which will need to mirror the action 
		// and we also don't want to completely destroy an xchan that has moved to a new primary location

		$r = q("delete from photo where xchan = '%s'",
			dbesc($xchan)
		);
		$r = q("select resource_id, resource_type, uid, id from item where ( author_xchan = '%s' or owner_xchan = '%s' ) ",
			dbesc($xchan),
			dbesc($xchan)
		);
		if($r) {
			foreach($r as $rr) {
				drop_item($rr,false);
			}
		}
		$r = q("delete from event where event_xchan = '%s'",
			dbesc($xchan)
		);
		$r = q("delete from group_member where xchan = '%s'",
			dbesc($xchan)
		);
		$r = q("delete from mail where ( from_xchan = '%s' or to_xchan = '%s' )",
			dbesc($xchan),
			dbesc($xchan)
		);
		$r = q("delete from xlink where ( xlink_xchan = '%s' or xlink_link = '%s' )",
			dbesc($xchan),
			dbesc($xchan)
		);


		$r = q("delete from xchan where xchan_hash = '%s' limit 1",
			dbesc($xchan)
		);
		$r = q("delete from hubloc where hubloc_hash = '%s'",
			dbesc($xchan)
		);
		$r = q("delete from abook where abook_xchan = '%s'",
			dbesc($xchan)
		);
		$r = q("delete from xtag where xtag_hash = '%s'",
			dbesc($xchan)
		);

	}
}




function contact_remove($channel_id, $abook_id) {

	if((! $channel_id) || (! $abook_id))
		return false;

	$archive = get_pconfig($channel_id, 'system','archive_removed_contacts');
	if($archive) {
		q("update abook set abook_flags = abook_flags | %d where abook_id = %d and abook_channel = %d limit 1",
			intval(ABOOK_FLAG_ARCHIVE),
			intval($abook_id),
			intval($channel_id)
		);
		return true;
	}

	$r = q("select * from abook where abook_id = %d and abook_channel = %d limit 1",
		intval($abook_id),
		intval($channel_id)
	);

	if(! $r)
		return false;

	$abook = $r[0];

	if($abook['abook_flags'] & ABOOK_FLAG_SELF)
		return false;

	q("delete from item where author_xchan = '%s' and uid = %d",
		dbesc($abook['abook_xchan']),
		intval($channel_id)
	);
	
	q("delete from abook where abook_id = %d and channel_id = %d limit 1",
		intval($abook['abook_id']),
		intval($channel_id)
	);

/*
// FIXME
	q("DELETE FROM `photo` WHERE `contact-id` = %d ",
		intval($id)
	);
	q("DELETE FROM `mail` WHERE `contact-id` = %d ",
		intval($id)
	);
	q("DELETE FROM `event` WHERE `cid` = %d ",
		intval($id)
	);
	q("DELETE FROM `queue` WHERE `cid` = %d ",
		intval($id)
	);
*/

	return true;
}


// sends an unfriend message. Does not remove the contact

function terminate_friendship($user,$self,$contact) {


	$a = get_app();

	require_once('include/datetime.php');

	if($contact['network'] === NETWORK_DFRN) {
		require_once('include/items.php');
		dfrn_deliver($user,$contact,'placeholder', 1);
	}

}


// Contact has refused to recognise us as a friend. We will start a countdown.
// If they still don't recognise us in 32 days, the relationship is over,
// and we won't waste any more time trying to communicate with them.
// This provides for the possibility that their database is temporarily messed
// up or some other transient event and that there's a possibility we could recover from it.
 
if(! function_exists('mark_for_death')) {
function mark_for_death($contact) {

	if($contact['archive'])
		return;

	if($contact['term_date'] == '0000-00-00 00:00:00') {
		q("UPDATE `contact` SET `term_date` = '%s' WHERE `id` = %d LIMIT 1",
				dbesc(datetime_convert()),
				intval($contact['id'])
		);
	}
	else {

		// TODO: We really should send a notification to the owner after 2-3 weeks
		// so they won't be surprised when the contact vanishes and can take
		// remedial action if this was a serious mistake or glitch

		$expiry = $contact['term_date'] . ' + 32 days ';
		if(datetime_convert() > datetime_convert('UTC','UTC',$expiry)) {

			// relationship is really truly dead. 
			// archive them rather than delete
			// though if the owner tries to unarchive them we'll start the whole process over again

			q("update contact set `archive` = 1 where id = %d limit 1",
				intval($contact['id'])
			);

			//contact_remove($contact['id']);

		}
	}

}}

if(! function_exists('unmark_for_death')) {
function unmark_for_death($contact) {
	// It's a miracle. Our dead contact has inexplicably come back to life.
	q("UPDATE `contact` SET `term_date` = '%s' WHERE `id` = %d LIMIT 1",
		dbesc('0000-00-00 00:00:00'),
		intval($contact['id'])
	);
}}

if(! function_exists('contact_photo_menu')){
function contact_photo_menu($contact) {

	$a = get_app();
	
	$contact_url="";
	$pm_url="";
	$status_link="";
	$photos_link="";
	$posts_link="";
	$poke_link="";

	$sparkle = false;
	if($contact['xchan_network'] === NETWORK_ZOT) {
		$sparkle = true;
		$profile_link = $a->get_baseurl() . '/magic?f=&id=' . $contact['abook_id'];
	}
	else
		$profile_link = $contact['xchan_url'];

	if($sparkle) {
		$status_link = $profile_link . "&url=status";
		$photos_link = $profile_link . "&url=photos";
		$profile_link = $profile_link . "&url=profile";
		$pm_url = $a->get_baseurl() . '/message/new/' . $contact['xchan_hash'];
	}

	$poke_link = $a->get_baseurl() . '/poke/?f=&c=' . $contact['abook_id'];
	$contact_url = $a->get_baseurl() . '/connections/' . $contact['abook_id'];
	$posts_link = $a->get_baseurl() . '/network/?cid=' . $contact['abook_id'];

	$menu = Array(
		t("Poke") => $poke_link,
		t("View Status") => $status_link,
		t("View Profile") => $profile_link,
		t("View Photos") => $photos_link,		
		t("Network Posts") => $posts_link, 
		t("Edit Contact") => $contact_url,
		t("Send PM") => $pm_url,
	);
	
	
	$args = array('contact' => $contact, 'menu' => &$menu);
	
	call_hooks('contact_photo_menu', $args);
	
	$o = "";
	foreach($menu as $k=>$v){
		if ($v!="") {
			$o .= "<li><a href=\"$v\">$k</a></li>\n";
		}
	}
	return $o;
}}


function random_profile() {
	$r = q("select xchan_url from xchan where xchan_network = 'zot' order by rand() limit 1");
	if($r && count($r))
		return $r[0]['xchan_url'];
	return '';
}


function contacts_not_grouped($uid,$start = 0,$count = 0) {

	if(! $count) {
		$r = q("select count(*) as total from contact where uid = %d and self = 0 and id not in (select distinct(`contact-id`) from group_member where uid = %d) ",
			intval($uid),
			intval($uid)
		);

		return $r;


	}

	$r = q("select * from contact where uid = %d and self = 0 and id not in (select distinct(`contact-id`) from group_member where uid = %d) and blocked = 0 and pending = 0 limit %d, %d",
		intval($uid),
		intval($uid),
		intval($start),
		intval($count)
	);

	return $r;
}

