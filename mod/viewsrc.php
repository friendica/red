<?php


function viewsrc_content(&$a) {

	if(! local_user()) {
		notice( t('Access denied.') . EOL);
		return;
	}

	$item_id = ((argc() > 1) ? intval(argv(1)) : 0);

	if(! $item_id) {
		$a->error = 404;
		notice( t('Item not found.') . EOL);
		return;
	}

	$r = q("SELECT `item`.`body` FROM `item` 
		WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
		and `item`.`moderated` = 0
		AND `item`.`id` = %d LIMIT 1",
		intval(local_user()),
		intval($item_id)
	);

	if(count($r)) {
		$o = str_replace("\n",'<br />',$r[0]['body']);
		if(is_ajax()) {
			echo $o;
			killme();
		} 
	}
	return $o;
}

