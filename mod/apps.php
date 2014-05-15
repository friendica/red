<?php

require_once('include/apps.php');

function apps_content(&$a) {



	$apps = get_system_apps();

	$o .= print_r($apps,true);

	return $o;

//	$tpl = get_markup_template("apps.tpl");
//	return replace_macros($tpl, array(
//		'$title' => t('Applications'),
//		'$apps' => $apps,
//	));

}
