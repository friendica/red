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
