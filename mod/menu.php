<?php

require_once('include/menu.php');

function menu_post(&$a) {

	if(! local_user())
		return;

	$channel = $a->get_channel();
	$menu_id = ((argc() > 1) ? intval(argv(1)) : 0);

	$menu_name   = (($_REQUEST['menu_name']) ? $_REQUEST['menu_name'] : '');
	$menu_desc   = (($_REQUEST['menu_desc']) ? $_REQUEST['menu_desc'] : '');


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
				'$hintedit' => t('Edit this menu')
				));
		}
		return $o;





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
