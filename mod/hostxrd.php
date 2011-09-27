<?php

require_once('include/crypto.php');

function hostxrd_init(&$a) {
	header('Access-Control-Allow-Origin: *');
	header("Content-type: text/xml");
	$tpl = file_get_contents('view/xrd_host.tpl');
	echo str_replace(array(
		'$zroot','$domain','$zot_post','$bigkey'),array(z_root(),z_path(),z_root() . '/post', salmon_key(get_config('system','site_pubkey'))),$tpl);
	session_write_close();
	exit();

}