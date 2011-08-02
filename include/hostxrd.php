<?php

function hostxrd() {
	header('Access-Control-Allow-Origin: *');
	header("Content-type: text/xml");
	$tpl = file_get_contents('view/xrd_host.tpl');
	echo str_replace(array('$zroot','$domain'),array(z_root(),z_path()),$tpl);
	session_write_close();
	exit();

}