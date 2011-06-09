<?php


function apps_content(&$a) {

	$o .= '<h3>' . t('Applications') . '</h3>';

	$apps = false;

	if(local_user()) {
		$apps = true;
		$o .= '<div class="app-title"><a href="notes">' . t('Private Notes') . '</a></div>';
	}

	if($a->apps) {
		$apps = true;
		$o .= $a->apps;
	}

	if(! $apps)
		notice( t('No installed applications.') . EOL);

	return $o;

}