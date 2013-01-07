<?php

require_once('include/crypto.php');

function xrd_init(&$a) {

	$uri = urldecode(notags(trim($_GET['uri'])));

	if(substr($uri,0,4) === 'http')
		$name = basename($uri);
	else {
		$local = str_replace('acct:', '', $uri);
		if(substr($local,0,2) == '//')
			$local = substr($local,2);

		$name = substr($local,0,strpos($local,'@'));
	}

	$r = q("SELECT * FROM `user` WHERE `nickname` = '%s' LIMIT 1",
		dbesc($name)
	);
	if(! count($r))
		killme();

	$salmon_key = salmon_key($r[0]['pubkey']);

	header('Access-Control-Allow-Origin: *');
	header("Content-type: text/xml");

	$dspr = '';

	$tpl = get_markup_template('view/xrd_person.tpl');

	$o = replace_macros($tpl, array(
		'$nick'        => $r[0]['nickname'],
		'$accturi'     => $uri,
		'$profile_url' => $a->get_baseurl() . '/channel/'       . $r[0]['nickname'],
		'$hcard_url'   => $a->get_baseurl() . '/hcard/'         . $r[0]['nickname'],
		'$atom'        => $a->get_baseurl() . '/dfrn_poll/'     . $r[0]['nickname'],
		'$zot_post'    => $a->get_baseurl() . '/post/'          . $r[0]['nickname'],
		'$poco_url'    => $a->get_baseurl() . '/poco/'          . $r[0]['nickname'],
		'$photo'       => $a->get_baseurl() . '/photo/profile/' . $r[0]['uid'],
		'$dspr'        => $dspr,
		'$salmon'      => $a->get_baseurl() . '/salmon/'        . $r[0]['nickname'],
		'$salmen'      => $a->get_baseurl() . '/salmon/'        . $r[0]['nickname'] . '/mention',
		'$modexp'      => 'data:application/magic-public-key,'  . $salmon_key,
		'$bigkey'      =>  salmon_key($r[0]['pubkey'])
	));


	$arr = array('user' => $r[0], 'xml' => $o);
	call_hooks('personal_xrd', $arr);

	echo $arr['xml'];
	killme();

}
