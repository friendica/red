<?php

require_once('include/apps.php');

function apps_content(&$a) {


	if(argc() == 1 || (! local_user())) {

		$apps = get_system_apps();

		//	$o .= print_r($apps,true);

		//	return $o;

		return replace_macros(get_markup_template('apps.tpl'), array(
			'$title' => t('Apps'),
			'$apps' => $apps,
		));
	}

	if(argc() == 3 && argv(2) == 'edit')
		$mode = 'edit';
	else
		$mode = 'list';

	$apps = array();
	$list = app_list(local_user());
	if($list) {
		foreach($list as $app) {
			$apps[] = app_render(app_encode($app),$mode);
		}
	}

	return replace_macros(get_markup_template('myapps.tpl'), array(
		'$title' => t('Apps'),
		'$apps' => $apps,
	));

}
