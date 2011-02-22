<?php

function hostxrd($hostname) {

	header("Content-type: text/xml");
	$tpl = file_get_contents('view/xrd_host.tpl');
	echo str_replace('$domain',$hostname,$tpl);
	session_write_close();
	exit();

}