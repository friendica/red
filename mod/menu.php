<?php

require_once('include/menu.php');

// probably should split this into two modules - with this being the CRUD module for menu
// and mod_mitem to do the same for menu_items
// Currently this file has pieces of both

function menu_post(&$a) {

	if(! local_user())
		return;

	$channel = $a->get_channel();
	$menu_id = ((argc() > 1) ? intval(argv(1)) : 0);

	

	$menu_name   = (($_REQUEST['menu_name']) ? $_REQUEST['menu_name'] : '');
	$menu_desc   = (($_REQUEST['menu_desc']) ? $_REQUEST['menu_desc'] : '');


	$mitem_link  = (($_REQUEST['mitem_link']) ? $_REQUEST['menu_link'] : '');
	$mitem_desc  = (($_REQUEST['mitem_desc']) ? $_REQUEST['mitem_desc'] : '');
	$mitem_order = (($_REQUEST['mitem_order']) ? intval($_REQUEST['mitem_order']) : 0);
	$mitem_id    = (($_REQUEST['mitem_id']) ? intval($_REQUEST['mitem_id']) : 0);

	$mitem_flags = (($_REQUEST['mitem_zid']) ? MENU_ITEM_ZID : 0);


	if ((! $_REQUEST['contact_allow'])
		&& (! $_REQUEST['group_allow'])
		&& (! $_REQUEST['contact_deny'])
		&& (! $_REQUEST['group_deny'])) {
		$str_group_allow   = $channel['channel_allow_gid'];
		$str_contact_allow = $channel['channel_allow_cid'];
		$str_group_deny    = $channel['channel_deny_gid'];
		$str_contact_deny  = $channel['channel_deny_cid'];
	}
	else {

		// use the posted permissions

		$str_group_allow   = perms2str($_REQUEST['group_allow']);
		$str_contact_allow = perms2str($_REQUEST['contact_allow']);
		$str_group_deny    = perms2str($_REQUEST['group_deny']);
		$str_contact_deny  = perms2str($_REQUEST['contact_deny']);
	}



}


function menu_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return '';
	}


	if(argc() == 1) {
		// list menus

	}


	if(argc() > 1) {
		if(argv(1) === 'new') {
			// new menu



		}

 		elseif(intval(argv(1))) {
			if(argc() == 3 && argv(2) == 'drop') {
				$r = menu_delete_id(intval(argv(1)),local_user());
				if($r)
					info( t('Menu deleted.') . EOL);
				else
					notice( t('Menu could not be deleted.'). EOL);

				goaway(z_root() . '/menu');
			}
			else {
				// edit menu


			}
		}

	}

}
