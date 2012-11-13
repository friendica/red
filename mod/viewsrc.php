<?php


function viewsrc_content(&$a) {

	$o = '';

	$item_id = ((argc() > 1) ? intval(argv(1)) : 0);
	$raw_output = ((argc() > 2 && argv[2] === 'raw') ? true : false);

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
	}


	if(! $item_id) {
		$a->error = 404;
		notice( t('Item not found.') . EOL);
	}

	if(local_user() && $item_id) {
		$r = q("select body from item where item_restrict = 0 and uid = %d and id = %d limit 1",
			intval(local_user()),
			intval($item_id)
		);

		if($r)
			$o = (($raw_output) ? $r[0]['body'] : str_replace("\n",'<br />',$r[0]['body']));
	}

	if(is_ajax()) {
		echo $o;
		killme();
	} 

	return $o;
}

