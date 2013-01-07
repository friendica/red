<?php

require_once('include/crypto.php');

function hostxrd_init(&$a) {
	header('Access-Control-Allow-Origin: *');
	header("Content-type: text/xml");
	$pubkey = get_config('system','site_pubkey');

	if(! $pubkey) {
		$res = new_keypair(1024);

		set_config('system','site_prvkey', $res['prvkey']);
		set_config('system','site_pubkey', $res['pubkey']);
	}

	$tpl = get_markup_template('xrd_host.tpl');
	echo replace_macros($tpl, array(
		'$zhost' => $a->get_hostname(),
		'$zroot' => z_root(),
		'$domain' => z_path(),
		'$zot_post' => z_root() . '/post',
		'$bigkey' => salmon_key(get_config('system','site_pubkey')),
	));
	session_write_close();
	exit();

}
