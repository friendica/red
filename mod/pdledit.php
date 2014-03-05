<?php

function pdledit_post(&$a) {
	if(! local_user())
		return;
	if(! $_REQUEST['module'])
		return;
	if(! trim($_REQUEST['content'])) {
		del_pconfig(local_user(),'system','mod_' . $_REQUEST['module'] . '.pdl');
		goaway(z_root() . '/pdledit/' . $_REQUEST['module']);
	}
	set_pconfig(local_user(),'system','mod_' . $_REQUEST['module'] . '.pdl',escape_tags($_REQUEST['content']));
	info( t('Layout updated.') . EOL);
	goaway(z_root() . '/pdledit/' . $_REQUEST['module']);
}


function pdledit_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	if(argc() > 1)
		$module = 'mod_' . argv(1) . '.pdl';
	else {
		$o .= '<h1>' . t('Edit System Page Description') . '</h1>';
		$files = glob('mod/*');
		if($files) {
			foreach($files as $f) {
				$name = basename($f,'.php');
				$x = theme_include('mod_' . $name . '.pdl');
				if($x) {
					$o .= '<a href="pdledit/' . $name . '" >' . $name . '</a><br />';
				}
			}
		}

		// list module pdl files
		return $o;
	}

	$t = get_pconfig(local_user(),'system',$module);
	if(! $t)
		$t = file_get_contents(theme_include($module));
	if(! $t) {
		notice( t('Layout not found.') . EOL);
		return '';
	}

	$o = replace_macros(get_markup_template('pdledit.tpl'),array(
		'$header' => t('Edit System Page Description'),
		'$mname' => t('Module Name:'),
		'$help' => t('Layout Help'),
		'$module' => argv(1),
		'$content' => htmlspecialchars($t,ENT_COMPAT,'UTF-8'),
		'$submit' => t('Submit')
	));
    
	return $o;
}
