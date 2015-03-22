<?php /** @file */

require_once('include/menu.php');

function bookmark_add($channel,$sender,$taxonomy,$private,$opts = null) {

	$menu_id = 0;
	$menu_name = '';
	$ischat = false;

	if(is_array($opts)) {
		$menu_id = ((x($opts,'menu_id')) ? intval($opts['menu_id']) : 0);
		$menu_name = ((x($opts,'menu_name')) ? escape_tags($opts['menu_name']) : '');
		$ischat = ((x($opts,'ischat')) ? intval($opts['ischat']) : 0);
	}

	$iarr = array();
	$channel_id = $channel['channel_id'];

	if($private)
		$iarr['contact_allow'] = array($channel['channel_hash']); 
	$iarr['mitem_link'] = $taxonomy['url'];
	$iarr['mitem_desc'] = $taxonomy['term'];
	$iarr['mitem_flags'] = (($ischat) ? MENU_ITEM_CHATROOM : 0);

	require_once('include/hubloc.php');
	$zrl = is_matrix_url($taxonomy['url']);

	if($zrl)
		$iarr['mitem_flags'] |= MENU_ITEM_ZID;

	$arr = array();
	if(! $menu_name) {
		$arr['menu_name'] = substr($sender['xchan_hash'],0,16) . ' ' . $sender['xchan_name'];
		$arr['menu_desc'] = sprintf( t('%1$s\'s bookmarks'), $sender['xchan_name']);
	}
	else {
		$arr['menu_name'] = $arr['menu_desc'] = $menu_name;
	}
	$arr['menu_flags'] = (($sender['xchan_hash'] === $channel['channel_hash']) ? MENU_BOOKMARK : MENU_SYSTEM|MENU_BOOKMARK);
	$arr['menu_channel_id'] = $channel_id;

	if(! $menu_id) {
		$x = menu_list($arr['menu_channel_id'],$arr['menu_name'],$arr['menu_flags']);
		if($x) 
			$menu_id = $x[0]['menu_id'];
		else 
			$menu_id = menu_create($arr);
	}

	if(! $menu_id) {
		logger('bookmark_add: unable to create menu ' . $arr['menu_name']);
		return; 
	}
	logger('add_bookmark: menu_id ' . $menu_id);
	$r = q("select * from menu_item where mitem_link = '%s' and mitem_menu_id = %d and mitem_channel_id = %d limit 1",
		dbesc($iarr['mitem_link']),
		intval($menu_id),
		intval($channel_id) 
	);
	if($r)
		logger('add_bookmark: duplicate menu entry', LOGGER_DEBUG);
	if(! $r)
		$r = menu_add_item($menu_id,$channel_id,$iarr);

	return $r;
}

function get_bookmark_link($observer) {

	if((! $observer) || ($observer['xchan_network'] !== 'zot'))
		return '';

	$h = @parse_url($observer['xchan_url']);
	if($h)
		return $h['scheme'] . '://' . $h['host'] . (($h['port']) ? ':' . $h['port'] : '') . '/rbmark?f=';
	return '';
}
