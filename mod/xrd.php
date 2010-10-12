<?php

require_once('salmon.php');

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

	$salmon_key = salmon_key($r[0]['spubkey']);


	header("Content-type: text/xml");

	$tpl = load_view_file('view/xrd_person.tpl');

	$o = replace_macros($tpl, array(
		'$accturi'     => $uri,
		'$profile_url' => $a->get_baseurl() . '/profile/'       . $r[0]['nickname'],
		'$atom'        => $a->get_baseurl() . '/dfrn_poll/'     . $r[0]['nickname'],
		'$photo'       => $a->get_baseurl() . '/photo/profile/' . $r[0]['uid'],
		'$salmon'      => $a->get_baseurl() . '/salmon/'        . $r[0]['nickname'],
		'$salmen'      => $a->get_baseurl() . '/salmon/'        . $r[0]['nickname'] . '/mention',
		'$modexp'      => 'data:application/magic-public-key,'  . $salmon_key
	));

	echo $o;
	killme();

}
