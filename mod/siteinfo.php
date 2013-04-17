<?php

function siteinfo_init(&$a) {

	if ($a->argv[1]=="json"){
		$register_policy = Array('REGISTER_CLOSED', 'REGISTER_APPROVE', 'REGISTER_OPEN');

		$sql_extra = '';
		if(x($a->config,'admin_nickname')) {
			$sql_extra = sprintf(" AND nickname = '%s' ",dbesc($a->config['admin_nickname']));
		}
		if (isset($a->config['admin_email']) && $a->config['admin_email']!=''){
			$r = q("SELECT username, nickname FROM user WHERE email='%s' $sql_extra", dbesc($a->config['admin_email']));
			$admin = array(
				'name' => $r[0]['username'],
				'profile'=> $a->get_baseurl().'/channel/'.$r[0]['nickname'],
			);
		} else {
			$admin = false;
		}

		$visible_plugins = array();
		if(is_array($a->plugins) && count($a->plugins)) {
			$r = q("select * from addon where hidden = 0");
			if(count($r))
				foreach($r as $rr)
					$visible_plugins[] = $rr['name'];
		}

		$data = Array(
			'version' => RED_VERSION,
			'url' => z_root(),
			'plugins' => $visible_plugins,
			'register_policy' =>  $register_policy[$a->config['system']['register_policy']],
			'admin' => $admin,
			'site_name' => $a->config['sitename'],
			'platform' => RED_PLATFORM,
			'info' => ((x($a->config,'info')) ? $a->config['info'] : '')			
		);

		echo json_encode($data);
		killme();
	}
}



function siteinfo_content(&$a) {

	if(! get_config('system','hidden_version_siteinfo'))
		$version = sprintf( t('Version %s'), RED_VERSION );
	else
	        $version = "";

	$visible_plugins = array();
	if(is_array($a->plugins) && count($a->plugins)) {
		$r = q("select * from addon where hidden = 0");
		if(count($r))
			foreach($r as $rr)
				$visible_plugins[] = $rr['name'];
	}

	$plugins_list = '';
	if(count($visible_plugins)) {
	        $plugins_text = t('Installed plugins/addons/apps:');
		$sorted = $visible_plugins;
		$s = '';
		sort($sorted);
		foreach($sorted as $p) {
			if(strlen($p)) {
				if(strlen($s)) $s .= ', ';
				$s .= $p;
			}
		}
		$plugins_list .= $s;
	}
	else
		$plugins_text = t('No installed plugins/addons/apps');

	$o = replace_macros(get_markup_template('siteinfo.tpl'), array(
                '$title' => t('Red'),
		'$description' => t('This is Red - another decentralized, distributed communications project by the folks at Friendica.'),
		'$version' => $version,
		'$web_location' => t('Running at web location') . ' ' . z_root(),
		'$visit' => t('Please visit <a href="http://friendica.com">Friendica.com</a> to learn more about the Friendica and/or Red project.'),
		'$bug_text' => t('Bug reports and issues: please visit'),
		'$bug_link_url' => 'http://bugs.friendica.com',
		'$bug_link_text' => 'Bugs.Friendica.com',
		'$contact' => t('Suggestions, praise, donations, etc. - please email "Info" at Friendica - dot com'),
		'$plugins_text' => $plugins_text,
		'$plugins_list' => $plugins_list
        ));

	call_hooks('about_hook', $o); 	

	return $o;

}
