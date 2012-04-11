<?php

function filerm_content(&$a) {

	if(! local_user()) {
		killme();
	}

	$term = unxmlify(trim($_GET['term']));
	$item_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);

	logger('filerm: tag ' . $term . ' item ' . $item_id);

	if($item_id && strlen($term))
		file_tag_unsave_file(local_user(),$item_id,$term);

	if(x($_SESSION,'return_url'))
		goaway($a->get_baseurl() . '/' . $_SESSION['return_url']);
	
	killme();
}
