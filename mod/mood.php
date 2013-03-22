<?php

require_once('include/security.php');
require_once('include/bbcode.php');
require_once('include/items.php');


function mood_init(&$a) {

	if(! local_user())
		return;

	$uid = local_user();
	$verb = notags(trim($_GET['verb']));
	
	if(! $verb) 
		return;

	$verbs = get_mood_verbs();

	if(! in_array($verb,$verbs))
		return;

	$activity = ACTIVITY_MOOD . '#' . urlencode($verb);

	$parent = ((x($_GET,'parent')) ? intval($_GET['parent']) : 0);


	logger('mood: verb ' . $verb, LOGGER_DEBUG);


	if($parent) {
		$r = q("select mid, owner_xchan, private, allow_cid, allow_gid, deny_cid, deny_gid 
			from item where id = %d and parent = %d and uid = %d limit 1",
			intval($parent),
			intval($parent),
			intval($uid)
		);
		if(count($r)) {
			$parent_mid = $r[0]['mid'];
			$private    = $r[0]['private'];
			$allow_cid  = $r[0]['allow_cid'];
			$allow_gid  = $r[0]['allow_gid'];
			$deny_cid   = $r[0]['deny_cid'];
			$deny_gid   = $r[0]['deny_gid'];
		}
	}
	else {

		$private       = 0;
		$channel       = $a->get_channel();

		$allow_cid     =  $channel['channel_allow_cid'];
		$allow_gid     =  $channel['channel_allow_gid'];
		$deny_cid      =  $channel['channel_deny_cid'];
		$deny_gid      =  $channel['channel_deny_gid'];
	}

	$poster = $a->get_observer();

	$mid = item_message_id();

	$action = sprintf( t('%1$s is currently %2$s'), '[url=' . $poster['xchan_url'] . ']' . $poster['xchan_name'] . '[/url]' , $verbs[$verb]); 
	$item_flags = ITEM_WALL|ITEM_ORIGIN|ITEM_UNSEEN;
	if(! $parent_mid)
		$item_flags |= ITEM_THREAD_TOP;


	$arr = array();

	$arr['aid']           = get_account_id();
	$arr['uid']           = $uid;
	$arr['mid']           = $mid;
	$arr['parent_mid']    = (($parent_mid) ? $parent_mid : $mid);
	$arr['item_flags']    = $item_flags;
	$arr['author_xchan']  = $poster['xchan_hash'];
	$arr['owner_xchan']   = (($parent_mid) ? $r[0]['owner_xchan'] : $poster['xchan_hash']);
	$arr['title']         = '';
	$arr['allow_cid']     = $allow_cid;
	$arr['allow_gid']     = $allow_gid;
	$arr['deny_cid']      = $deny_cid;
	$arr['deny_gid']      = $deny_gid;
	$arr['verb']          = $activity;
	$arr['body']          = $action;

	$item_id = item_store($arr);
	if($item_id) {
//		q("UPDATE `item` SET `plink` = '%s' WHERE `uid` = %d AND `id` = %d LIMIT 1",
//			dbesc($a->get_baseurl() . '/display/' . $poster['nickname'] . '/' . $item_id),
//			intval($uid),
//			intval($item_id)
//		);

		proc_run('php',"include/notifier.php","activity", $item_id);

	}

	call_hooks('post_local_end', $arr);

	if($_SESSION['return_url'])
		goaway(z_root() . '/' . $_SESSION['return_url']);

	return;
}



function mood_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$parent = ((x($_GET,'parent')) ? intval($_GET['parent']) : '0');



	$verbs = get_mood_verbs();

	$shortlist = array();
	foreach($verbs as $k => $v)
		if($v !== 'NOTRANSLATION')
			$shortlist[] = array($k,$v);


	$tpl = get_markup_template('mood_content.tpl');

	$o = replace_macros($tpl,array(
		'$title' => t('Mood'),
		'$desc' => t('Set your current mood and tell your friends'),
		'$verbs' => $shortlist,
		'$parent' => $parent,
		'$submit' => t('Submit'),
	));

	return $o;

}