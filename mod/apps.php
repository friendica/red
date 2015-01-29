<?php

require_once('include/apps.php');

function apps_content(&$a) {

	if(argc() == 2 && argv(1) == 'edit')
		$mode = 'edit';
	else
		$mode = 'list';

	$apps = array();

	$syslist = get_system_apps();

	if(local_channel()) {
		$list = app_list(local_channel());
		if($list) {
			foreach($list as $x) {
				$syslist[] = app_encode($x);
			}
		}
	}
	usort($syslist,'app_name_compare');

//	logger('apps: ' . print_r($syslist,true));

	foreach($syslist as $app) {
		$apps[] = app_render($app,$mode);
	}

	return replace_macros(get_markup_template('myapps.tpl'), array(
		'$sitename' => get_config('system','sitename'),
		'$title' => t('Apps'),
		'$apps' => $apps,
	));

}
