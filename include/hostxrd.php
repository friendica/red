<?php

function hostxrd($baseurl) {

	header('Access-Control-Allow-Origin: *');
	header("Content-type: text/xml");
	$tpl = file_get_contents('view/xrd_host.tpl');
	echo str_replace('$domain',$baseurl,$tpl);
	session_write_close();
	exit();

}