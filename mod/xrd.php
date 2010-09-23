<?php


function xrd_content(&$a) {

	$uri = urldecode(notags(trim($_GET['uri'])));
	$local = str_replace('acct:', '', $uri);
	if(substr($local,0,2) == '//')
		$local = substr($local,2);

	$name = substr($local,0,strpos($local,'@'));

	$r = q("SELECT * FROM `user` WHERE `nickname` = '%s' LIMIT 1",
		dbesc($name)
	);
	if(! count($r))
		killme();

	$tpl = load_view_file('view/xrd_person.tpl');

	$o = replace_macros($tpl, array(
		'$accturi' => $uri,
		'$profile_url' => $a->get_baseurl() . '/profile/' . $r[0]['nickname'],
		'$photo' => $a->get_baseurl() . '/photo/profile/' . $r[0]['uid']
	));

	echo $o;
	killme();

}