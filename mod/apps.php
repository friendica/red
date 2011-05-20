<?php


function apps_content(&$a) {

	$o .= '<h3>' . t('Applications') . '</h3>';

	$o .= '<div class="app-title"><a href="notes">' . t('Private Notes') . '</a></div>';

	if($a->apps)
		$o .= $a->apps;

	return $o;

}