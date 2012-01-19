<?php

require_once('bbcode.php');

function share_init(&$a) {

	$post_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);
	if((! $post_id) || (! local_user()))
		killme();

	$r = q("SELECT * FROM `item` WHERE `id` = %d LIMIT 1",
		intval($post_id)
	);
	if(! count($r) || $r[0]['private'])
		killme();

	$o = '';

	$o .= '&#x2672; <a href="' . $r[0]['author-link'] . '">' . $r[0]['author-name'] . '</a><br />';
	if($r[0]['title'])
		$o .= '<strong>' . $r[0]['title'] . '</strong><br />';
	$o .= bbcode($r[0]['body'], true);
	echo $o . '<br />';
	killme();  
}
