<?php

require_once('include/menu.php');

function menu_post(&$a) {

	if(! local_user())
		return;

	$_REQUEST['menu_channel_id'] = local_user();

	$menu_id = ((argc() > 1) ? intval(argv(1)) : 0);
	if($menu_id) {
		$_REQUEST['menu_id'] = intval(argv(1));
		$r = menu_edit($_REQUEST);
		if($r) {
			info( t('Menu updated.') . EOL);
			goaway(z_root() . '/mitem/' . $menu_id); 
		}
		else
			notice( t('Unable to update menu.'). EOL);
	}
	else {
		$r = menu_create($_REQUEST);
		if($r) {
			info( t('Menu created.') . EOL);
			goaway(z_root() . '/mitem/' . $r); 
		}
		else
			notice( t('Unable to create menu.'). EOL);

	}

}


function menu_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return '';
	}


	if(argc() == 1) {
		// list menus
		$x = menu_list(local_user());
		if($x) {
			$o = replace_macros(get_markup_template('menulist.tpl'),array(
				'$title' => t('Manage Menus'),
				'$menus' => $x,
				'$edit' => t('Edit'),
				'$drop' => t('Drop'),
				'$new' => t('New'),
				'$hintnew' => t('Create a new menu'),
				'$hintdrop' => t('Delete this menu'),
				'$hintcontent' => t('Edit menu contents'),
				'$hintedit' => t('Edit this menu')
				));
		}
		return $o;





	}


	if(argc() > 1) {
		if(argv(1) === 'new') {			
			$o = replace_macros(get_markup_template('menuedit.tpl'), array(
				'$header' => t('New Menu'),
				'$menu_name' => array('menu_name', t('Menu name'), '', t('Must be unique, only seen by you'), '*'),
				'$menu_desc' => array('menu_desc', t('Menu title'), '', t('Menu title as seen by others'), ''),
				'$submit' => t('Create')
			));
			return $o;
		}

 		elseif(intval(argv(1))) {
			$m = menu_fetch_id(intval(argv(1)),local_user());
			if(! $m) {
				notice( t('Menu not found.') . EOL);
				return '';
			}
			if(argc() == 3 && argv(2) == 'drop') {
				$r = menu_delete_id(intval(argv(1)),local_user());
				if($r)
					info( t('Menu deleted.') . EOL);
				else
					notice( t('Menu could not be deleted.'). EOL);

				goaway(z_root() . '/menu');
			}
			else {
				$o = replace_macros(get_markup_template('menuedit.tpl'), array(
					'$header' => t('Edit Menu'),
					'$menu_id' => intval(argv(1)),
					'$hintedit' => t('Add or remove entries to this menu'),
					'$editcontents' => t('Edit menu contents'),
					'$menu_name' => array('menu_name', t('Menu name'), $m['menu_name'], t('Must be unique, only seen by you'), '*'),
					'$menu_desc' => array('menu_desc', t('Menu title'), $m['menu_desc'], t('Menu title as seen by others'), ''),
					'$submit' => t('Modify')
				));
				return $o;
			}
		}
		else {
			notice( t('Not found.') . EOL);
			return;
		}
	}

}
