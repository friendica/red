<?php /** @file */

require_once('include/security.php');

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
	

function menu_render($menu) {
	if(! $menu)
		return '';
	for($x = 0; $x < count($menu['items']); $x ++)
		if($menu['items']['mitem_flags'] & MENU_ITEM_ZID)
			$menu['items']['link'] = zid($menu['items']['link']);

	return replace_macros(get_markup_template('usermenu.tpl'),array(
		'$menu' => $menu['menu'],
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

	if(! $menu_desc)
		$menu_desc = $menu_name;

	if(! $menu_name)
		return false;


	$menu_channel_id = intval($arr['menu_channel_id']);

	$r = q("select * from menu where menu_name = '%s' and menu_channel_id = %d limit 1",
		dbesc($menu_name),
		intval($menu_channel_id)
	);

	if($r)
		return false;

	$r = q("insert into menu ( menu_name, menu_desc, menu_channel_id ) 
		values( '%s', '%s', %d )",
 		dbesc($menu_name),
		dbesc($menu_desc),
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

function menu_list($channel_id) {
	$r = q("select * from menu where menu_channel_id = %d order by menu_name",
		intval($channel_id)
	);
	return $r;
}



function menu_edit($arr) {

	$menu_id   = intval($arr['menu_id']);

	$menu_name = trim(escape_tags($arr['menu_name']));
	$menu_desc = trim(escape_tags($arr['menu_desc']));

	if(! $menu_desc)
		$menu_desc = $menu_name;

	if(! $menu_name)
		return false;


	$r = q("select menu_id from menu where menu_name = '%s' and menu_channel_id = %d limit 1",
		dbesc($menu_name),
		intval($menu_channel_id)
	);
	if(($r) && ($r[0]['menu_id'] != $menu_id)) {
		logger('menu_edit: duplicate menu name for channel ' . $menu_channel_id);
		return false;
	}



	$menu_channel_id = intval($arr['menu_channel_id']);

	$r = q("select * from menu where menu_id = %d and menu_channel_id = %d limit 1",
		intval($menu_id),
		intval($menu_channel_id)
	);
	if(! $r) {
		logger('menu_edit: not found: ' . print_r($arr,true));
		return false;
	}


	$r = q("select * from menu where menu_name = '%s' and menu_channel_id = %d limit 1",
		dbesc($menu_name),
		intval($menu_channel_id)
	);

	if($r)
		return false;

	return q("update menu set menu_name = '%s', menu_desc = '%s' 
		where menu_id = %d and menu_channel_id = %d limit 1", 
 		dbesc($menu_name),
		dbesc($menu_desc),
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
	$allow_cid = perms2str($arr['allow_cid']);
	$allow_gid = perms2str($arr['allow_gid']);
	$deny_cid = perms2str($arr['deny_cid']);
	$deny_gid = perms2str($arr['deny_gid']);

	$r = q("insert into menu_item ( mitem_link, mitem_desc, mitem_flags, allow_cid, allow_gid, deny_cid, deny_gid, mitem_channel_id, mitem_menu_id, mitem_order ) values ( '%s', '%s', %d, '%s', '%s', '%s', '%s', %d, %d, %d ) ",
		dbesc($mitem_link),
		dbesc($mitem_desc),
		intval($mitem_flags),
		dbesc($allow_cid),
		dbesc($allow_gid),
		dbesc($deny_cid),
		dbesc($deny_gid),
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
	$allow_cid = perms2str($arr['allow_cid']);
	$allow_gid = perms2str($arr['allow_gid']);
	$deny_cid = perms2str($arr['deny_cid']);
	$deny_gid = perms2str($arr['deny_gid']);

	$r = q("update menu_item set mitem_link = '%s', mitem_desc = '%s', mitem_flags = %d, allow_cid = '%s', allow_gid = '%s', deny_cid = '%s', deny_gid = '%s', mitem_order = %d  where mitem_channel_id = %d and mitem_menu_id = %d and mitem_id = %d limit 1",
		dbesc($mitem_link),
		dbesc($mitem_desc),
		intval($mitem_flags),
		dbesc($allow_cid),
		dbesc($allow_gid),
		dbesc($deny_cid),
		dbesc($deny_gid),
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

