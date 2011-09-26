<?php

function hostxrd_init(&$a) {
	header('Access-Control-Allow-Origin: *');
	header("Content-type: text/xml");
	$tpl = file_get_contents('view/xrd_host.tpl');
	echo str_replace(array(
		'$zroot','$domain','$zot_post'),array(z_root(),z_path(),z_root() . '/post'),$tpl);
	session_write_close();
	exit();

}