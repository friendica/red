<?php /** @file */

require_once('include/security.php');
require_once('include/bbcode.php');

function menu_fetch($name,$uid,$observer_xchan) {

	$sql_options = permissions_sql($uid);

	$r = q("select * from menu where menu_channel_id = %d and menu_name = '%s' limit 1",
		intval($uid),
		dbesc($name)
	);
	if($r) {
		$x = q("select * from menu_item where mitem_menu_id = %d and mitem_channel_id = %d
			$sql_options 
			order by mitem_order asc, mitem_desc asc",
			intval($r[0]['menu_id']),
			intval($uid)
		);
		return array('menu' => $r[0], 'items' => $x );
	}

	return null;
}
	
function menu_render($menu, $edit = false) {
	if(! $menu)
		return '';

	for($x = 0; $x < count($menu['items']); $x ++) {
		if($menu['items'][$x]['mitem_flags'] & MENU_ITEM_ZID)
			$menu['items'][$x]['mitem_link'] = zid($menu['items'][$x]['mitem_link']);
		if($menu['items'][$x]['mitem_flags'] & MENU_ITEM_NEWWIN)
			$menu['items'][$x]['newwin'] = '1';
		$menu['items'][$x]['mitem_desc'] = bbcode($menu['items'][$x]['mitem_desc']);
	}

	return replace_macros(get_markup_template('usermenu.tpl'),array(
		'$menu' => $menu['menu'],
		'$edit' => (($edit) ? t("Edit") : ''),
		'$items' => $menu['items']
	));
}



function menu_fetch_id($menu_id,$channel_id) {

	$r = q("select * from menu where menu_id = %d and menu_channel_id = %d limit 1",
		intval($menu_id),
		intval($channel_id)
	);

	return (($r) ? $r[0] : false);
}



function menu_create($arr) {


	$menu_name = trim(escape_tags($arr['menu_name']));
	$menu_desc = trim(escape_tags($arr['menu_desc']));
	$menu_flags = intval($arr['menu_flags']);


	if(! $menu_desc)
		$menu_desc = $menu_name;

	if(! $menu_name)
		return false;

	if(! $menu_flags)
		$menu_flags = 0;


	$menu_channel_id = intval($arr['menu_channel_id']);

	$r = q("select * from menu where menu_name = '%s' and menu_channel_id = %d limit 1",
		dbesc($menu_name),
		intval($menu_channel_id)
	);

	if($r)
		return false;

	$r = q("insert into menu ( menu_name, menu_desc, menu_flags, menu_channel_id ) 
		values( '%s', '%s', %d, %d )",
 		dbesc($menu_name),
		dbesc($menu_desc),
		intval($menu_flags),
		intval($menu_channel_id)
	);
	if(! $r)
		return false;

	$r = q("select menu_id from menu where menu_name = '%s' and menu_channel_id = %d limit 1",
		dbesc($menu_name),
		intval($menu_channel_id)
	);
	if($r)
		return $r[0]['menu_id'];
	return false;

}

/**
 * If $flags is present, check that all the bits in $flags are set
 * so that MENU_SYSTEM|MENU_BOOKMARK will return entries with both
 * bits set. We will use this to find system generated bookmarks.
 */

function menu_list($channel_id, $name = '', $flags = 0) {

	$sel_options = '';
	$sel_options .= (($name) ? " and menu_name = '" . protect_sprintf(dbesc($name)) . "' " : '');
	$sel_options .= (($flags) ? " and menu_flags = " . intval($flags) . " " : '');

	$r = q("select * from menu where menu_channel_id = %d $sel_options order by menu_desc",
		intval($channel_id)
	);
	return $r;
}



function menu_edit($arr) {

	$menu_id   = intval($arr['menu_id']);

	$menu_name = trim(escape_tags($arr['menu_name']));
	$menu_desc = trim(escape_tags($arr['menu_desc']));
	$menu_flags = intval($arr['menu_flags']);

	if(! $menu_desc)
		$menu_desc = $menu_name;

	if(! $menu_name)
		return false;

	if(! $menu_flags)
		$menu_flags = 0;


	$menu_channel_id = intval($arr['menu_channel_id']);

	$r = q("select menu_id from menu where menu_name = '%s' and menu_channel_id = %d limit 1",
		dbesc($menu_name),
		intval($menu_channel_id)
	);
	if(($r) && ($r[0]['menu_id'] != $menu_id)) {
		logger('menu_edit: duplicate menu name for channel ' . $menu_channel_id);
		return false;
	}


	$r = q("select * from menu where menu_id = %d and menu_channel_id = %d limit 1",
		intval($menu_id),
		intval($menu_channel_id)
	);
	if(! $r) {
		logger('menu_edit: not found: ' . print_r($arr,true));
		return false;
	}

	return q("update menu set menu_name = '%s', menu_desc = '%s', menu_flags = %d
		where menu_id = %d and menu_channel_id = %d limit 1", 
 		dbesc($menu_name),
		dbesc($menu_desc),
		intval($menu_flags),
		intval($menu_id),
		intval($menu_channel_id)
	);
}

function menu_delete($menu_name, $uid) {
	$r = q("select menu_id from menu where menu_name = '%s' and menu_channel_id = %d limit 1",
		dbesc($menu_name),
		intval($uid)
	);

	if($r)
		return menu_delete_id($r[0]['menu_id'],$uid);
	return false;
}

function menu_delete_id($menu_id, $uid) {
	$r = q("select menu_id from menu where menu_id = %d and menu_channel_id = %d limit 1",
		intval($menu_id),
		intval($uid)
	);
	if($r) {
		$x = q("delete from menu_item where mitem_menu_id = %d and mitem_channel_id = %d",
			intval($menu_id),
			intval($uid)
		);
		return q("delete from menu where menu_id = %d and menu_channel_id = %d limit 1",
			intval($menu_id),
			intval($uid)
		);
	}			
	return false;
}


function menu_add_item($menu_id, $uid, $arr) {


	$mitem_link = escape_tags($arr['mitem_link']);
	$mitem_desc = escape_tags($arr['mitem_desc']);
	$mitem_order = intval($arr['mitem_order']);	
	$mitem_flags = intval($arr['mitem_flags']);

	if(local_user() == $uid) {
		$channel = get_app()->get_channel();	
	}

	if (($channel) 
		&& (! $arr['contact_allow'])
		&& (! $arr['group_allow'])
		&& (! $arr['contact_deny'])
		&& (! $arr['group_deny'])) {
		$str_group_allow   = $channel['channel_allow_gid'];
		$str_contact_allow = $channel['channel_allow_cid'];
		$str_group_deny    = $channel['channel_deny_gid'];
		$str_contact_deny  = $channel['channel_deny_cid'];
	}
	else {

		// use the posted permissions

		$str_group_allow   = perms2str($arr['group_allow']);
		$str_contact_allow = perms2str($arr['contact_allow']);
		$str_group_deny    = perms2str($arr['group_deny']);
		$str_contact_deny  = perms2str($arr['contact_deny']);
	}

//  unused
//	$allow_cid = perms2str($arr['allow_cid']);
//	$allow_gid = perms2str($arr['allow_gid']);
//	$deny_cid = perms2str($arr['deny_cid']);
//	$deny_gid = perms2str($arr['deny_gid']);

	$r = q("insert into menu_item ( mitem_link, mitem_desc, mitem_flags, allow_cid, allow_gid, deny_cid, deny_gid, mitem_channel_id, mitem_menu_id, mitem_order ) values ( '%s', '%s', %d, '%s', '%s', '%s', '%s', %d, %d, %d ) ",
		dbesc($mitem_link),
		dbesc($mitem_desc),
		intval($mitem_flags),
		dbesc($str_contact_allow),
		dbesc($str_group_allow),
		dbesc($str_contact_deny),
		dbesc($str_group_deny),
		intval($uid),
		intval($menu_id),
		intval($mitem_order)
	);
	return $r;

}

function menu_edit_item($menu_id, $uid, $arr) {


	$mitem_id = intval($arr['mitem_id']);
	$mitem_link = escape_tags($arr['mitem_link']);
	$mitem_desc = escape_tags($arr['mitem_desc']);
	$mitem_order = intval($arr['mitem_order']);	
	$mitem_flags = intval($arr['mitem_flags']);


	if(local_user() == $uid) {
		$channel = get_app()->get_channel();	
	}

	if ((! $arr['contact_allow'])
		&& (! $arr['group_allow'])
		&& (! $arr['contact_deny'])
		&& (! $arr['group_deny'])) {
		$str_group_allow   = $channel['channel_allow_gid'];
		$str_contact_allow = $channel['channel_allow_cid'];
		$str_group_deny    = $channel['channel_deny_gid'];
		$str_contact_deny  = $channel['channel_deny_cid'];
	}
	else {

		// use the posted permissions

		$str_group_allow   = perms2str($arr['group_allow']);
		$str_contact_allow = perms2str($arr['contact_allow']);
		$str_group_deny    = perms2str($arr['group_deny']);
		$str_contact_deny  = perms2str($arr['contact_deny']);
	}


	$r = q("update menu_item set mitem_link = '%s', mitem_desc = '%s', mitem_flags = %d, allow_cid = '%s', allow_gid = '%s', deny_cid = '%s', deny_gid = '%s', mitem_order = %d  where mitem_channel_id = %d and mitem_menu_id = %d and mitem_id = %d limit 1",
		dbesc($mitem_link),
		dbesc($mitem_desc),
		intval($mitem_flags),
		dbesc($str_contact_allow),
		dbesc($str_group_allow),
		dbesc($str_contact_deny),
		dbesc($str_group_deny),
		intval($mitem_order),
		intval($uid),
		intval($menu_id),
		intval($mitem_id)
	);
	return $r;
}




function menu_del_item($menu_id,$uid,$item_id) {
	$r = q("delete from menu_item where mitem_menu_id = %d and mitem_channel_id = %d and mitem_id = %d limit 1",
		intval($menu_id),
		intval($uid),
		intval($item_id)
	);
	return $r;
}

