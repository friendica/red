<?php

require_once('bbcode.php');

function tagrm_post(&$a) {

	if(! local_user())
		goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);


	if((x($_POST,'submit')) && ($_POST['submit'] === t('Cancel')))
		goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);

	$tag =  ((x($_POST,'tag'))  ? trim($_POST['tag'])       : '');
	$item = ((x($_POST,'item')) ? intval($_POST['item'])    : 0 );

	$r = q("SELECT * FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($item),
		intval(local_user())
	);

	if(! $r)
		goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);

	$r = fetch_post_tags($r,true);

	$item = $r[0];
	$new_tags = array();

	if($item['term']) {
		for($x = 0; $x < count($item['term']); $x ++) {
			if($item['term'][$x]['term'] !== hex2bin($tag))
				$new_tags[] = $item['term'][$x];
		}
	}

	if($new_tags)
		$item['term'] = $new_tags;
	else
		unset($item['term']);

	item_store_update($item);

	info( t('Tag removed') . EOL );
	goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);
	
	// NOTREACHED

}



function tagrm_content(&$a) {

	if(! local_user()) {
		goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);
		// NOTREACHED
	}

	// remove tag on the fly if item and tag are provided
	if((argc() == 4) && (argv(1) === 'drop') && intval(argv(2))) {

		$item = intval(argv(2));
		$tag = argv(3);

		$r = q("SELECT * FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($item),
			intval(local_user())
		);

		if(! $r)
			goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);

		$r = fetch_post_tags($r,true);

		$item = $r[0];

		$new_tags = array();

		if($item['term']) {
			for($x = 0; $x < count($item['term']); $x ++) {
				if($item['term'][$x]['term'] !== hex2bin($tag))
					$new_tags[] = $item['term'][$x];
			}
		}

		if($new_tags)
			$item['term'] = $new_tags;
		else
			unset($item['term']);

		item_store_update($item);

		info( t('Tag removed') . EOL );
		goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);

	}

	//if we got only the item print a list of tags to select
	if((argc() == 3) && (argv(1) === 'drop') && intval(argv(2))) {

		$o = '';

		$item = intval(argv(2));

		$r = q("SELECT * FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($item),
			intval(local_user())
		);

		if(! $r)
			goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);

		$r = fetch_post_tags($r,true);

		if(! count($r[0]['term']))
			goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);

		$o .= '<h3>' . t('Remove Item Tag') . '</h3>';

		$o .= '<p id="tag-remove-desc">' . t('Select a tag to remove: ') . '</p>';

		$o .= '<form id="tagrm" action="tagrm" method="post" >';
		$o .= '<input type="hidden" name="item" value="' . $item . '" />';
		$o .= '<ul>';


		foreach($r[0]['term'] as $x) {
			$o .= '<li><input type="checkbox" name="tag" value="' . bin2hex($x['term']) . '" >' . bbcode($x['term']) . '</input></li>';
		}

		$o .= '</ul>';
		$o .= '<input id="tagrm-submit" type="submit" name="submit" value="' . t('Remove') .'" />';
		$o .= '<input id="tagrm-cancel" type="submit" name="submit" value="' . t('Cancel') .'" />';
		$o .= '</form>';

		return $o;

	}
	
}
