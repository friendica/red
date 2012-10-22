<?php

function apps_content(&$a) {

	$apps = $a->get_apps();

	if(count($apps) == 0)
		notice( t('No installed applications.') . EOL);


	$tpl = get_markup_template("apps.tpl");
	return replace_macros($tpl, array(
		'$title' => t('Applications'),
		'$apps' => $apps,
	));

}
