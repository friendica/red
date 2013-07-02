<?php /** @file */



function thing_init(&$a) {

	if(! local_user())
		return;

	$account_id = $a->get_account();

	$name = escape_tags($_REQUEST['term']);
	$url = $_REQUEST['link'];
	$photo = $_REQUEST['photo'];

	$hash = random_string();


	if(! $name)
		return;

	$r = q("insert into term ( aid, uid, oid, otype, type, term, url, imgurl, term_hash )
		values( %d, %d, %d, %d, %d, '%s', '%s', '%s', '%s' ) ",
		intval($account_id),
		intval(local_user()),
		0,
		TERM_OBJ_THING,
		TERM_THING,
		dbesc($name),
		dbesc(($url) ? $url : z_root() . '/thing/' . $hash),
		dbesc(($photo) ? $photo : ''),
		dbesc($hash)
	);
	
}


function thing_content(&$a) {




}
