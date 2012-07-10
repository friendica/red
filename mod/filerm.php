<?php

function filerm_content(&$a) {

	if(! local_user()) {
		killme();
	}

	$term = trim($_GET['term']);
	$cat  = trim($_GET['cat']);

	$category = (($cat) ? true : false);
	if($category)
		$term = $cat;

	$item_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);

	logger('filerm: tag ' . $term . ' item ' . $item_id);

	if($item_id && strlen($term)) {
		$r = q("delete from term where uid = %d and type = %d and oid = %d and $term = '%s' limit 1",
			intval(local_user()),
			intval(($category) ? FILE_CATEGORY : FILE_HASHTAG),
			intval($item_id),
			dbesc($term)
		);
	}

	if(x($_SESSION,'return_url'))
		goaway($a->get_baseurl() . '/' . $_SESSION['return_url']);
	
	killme();
}
