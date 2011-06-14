<?php


function apps_content(&$a) {

	$o .= '<h3>' . t('Applications') . '</h3>';

	if($a->apps)
		$o .= $a->apps;
	else
		notice( t('No installed applications.') . EOL);

	return $o;

}