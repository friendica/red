<?php

require_once('include/apps.php');

function apps_content(&$a) {



	$apps = get_system_apps();

//	$o .= print_r($apps,true);

//	return $o;


	return replace_macros(get_markup_template('apps.tpl'), array(
		'$title' => t('Applications'),
		'$apps' => $apps,
	));

}
