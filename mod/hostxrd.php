<?php

function hostxrd_init(&$a) {
	header('Access-Control-Allow-Origin: *');
	header("Content-type: text/xml");

	$tpl = get_markup_template('xrd_host.tpl');
	$x = replace_macros(get_markup_template('xrd_host.tpl'), array(
		'$zhost' => $a->get_hostname(),
		'$zroot' => z_root()
	));
	$arr = array('xrd' => $x);
	call_hooks('hostxrd',$arr);
	echo $arr['xrd'];
	killme();
}
