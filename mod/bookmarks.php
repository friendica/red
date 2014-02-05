<?php

function bookmarks_content(&$a) {
	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}


	require_once('include/menu.php');

	$o = '<h3>' . t('My Bookmarks') . '</h3>';

	$x = menu_list(local_user(),'',MENU_BOOKMARK);

	if($x) {
		foreach($x as $xx) {
			$y = menu_fetch($xx['menu_name'],local_user(),get_observer_hash());
			$o .= menu_render($y);
		}
	}

	$o .= '<h3>' . t('My Connections Bookmarks') . '</h3>';


	$x = menu_list(local_user(),'',MENU_SYSTEM|MENU_BOOKMARK);

	if($x) {
		foreach($x as $xx) {
			$y = menu_fetch($xx['menu_name'],local_user(),get_observer_hash());
			$o .= menu_render($y);
		}
	}



	return $o;

}

