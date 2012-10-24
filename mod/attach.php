<?php

require_once('include/security.php');

function attach_init(&$a) {

	if(argc() != 2) {
		notice( t('Item not available.') . EOL);
		return;
	}

	$hash = argv(1);

	// Check for existence, which will also provide us the owner uid

	$r = q("SELECT * FROM `attach` WHERE `hash` = '%s' LIMIT 1",
		dbesc($hash)
	);
	if(! count($r)) {
		notice( t('Item was not found.'). EOL);
		return;
	}

	$sql_extra = permissions_sql($r[0]['uid']);

	// Now we'll see if we can access the attachment

	$r = q("SELECT * FROM `attach` WHERE hash = '%s' $sql_extra LIMIT 1",
		dbesc($hash)
	);

	if(! count($r)) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	header('Content-type: ' . $r[0]['filetype']);
	header('Content-disposition: attachment; filename=' . $r[0]['filename']);
	echo $r[0]['data'];
	killme();
	// NOTREACHED
}