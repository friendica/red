<?php

function bookmarks_init(&$a) {
	if(! local_user())
		return;
	$item_id = intval($_REQUEST['item']);
	if(! $item_id)
		return;

	$u = $a->get_channel();
		
	$i = q("select * from item where id = %d and uid = %d limit 1",
		intval($item_id),
		intval(local_user())
	);
	if(! $i)
		return;

	$i = fetch_post_tags($i);

	$item = $i[0];

	$terms = get_terms_oftype($item['term'],TERM_BOOKMARK);

	if($terms && (! $i[0]['item_restrict'])) {
		require_once('include/bookmarks.php');
		require_once('include/Contact.php');
		$s = channelx_by_hash($i[0]['author_xchan']);
		foreach($terms as $t) {
			bookmark_add($u,$s[0],$t,$i[0]['item_private']);
			notice( t('Bookmark(s) added') . EOL);
		}
	}
	killme();
}

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

