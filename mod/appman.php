<?php /** @file */

require_once('include/apps.php');

function appman_post(&$a) {

	if(! local_user())
		return;

	$papp = app_decode($_POST['papp']);

	if(! is_array($papp)) {
		notice( t('Malformed app.') . EOL);
		return;
	}

	if($_POST['install']) {
		app_install(local_user(),$papp);
	}

	if($_POST['delete']) {
		app_destroy(local_user(),$papp);
	}


}