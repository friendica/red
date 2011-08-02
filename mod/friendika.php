<?php

function friendika_init(&$a) {
	if ($a->argv[1]=="json"){
		$register_policy = Array('REGISTER_CLOSED', 'REGISTER_APPROVE', 'REGISTER_OPEN');

		if (isset($a->config['admin_email']) && $a->config['admin_email']!=''){
			$r = q("SELECT username, nickname FROM user WHERE email='%s'", $a->config['admin_email']);
			$admin = array(
				'name' => $r[0]['username'],
				'profile'=> $a->get_baseurl().'/profile/'.$r[0]['nickname'],
			);
		} else {
			$admin = false;
		}

		$data = Array(
			'version' => FRIENDIKA_VERSION,
			'url' => z_root(),
			'plugins' => $a->plugins,
			'register_policy' =>  $register_policy[$a->config['register_policy']],
			'admin' => $admin,
			'site_name' => $a->config['sitename'],
			'info' => ((x($a->config,'info')) ? $a->config['info'] : '')			
		);

		echo json_encode($data);
		killme();
	}
}



function friendika_content(&$a) {

	$o = '';
	$o .= '<h3>Friendika</h3>';


	$o .= '<p></p><p>';

	$o .= t('This is Friendika version') . ' ' . FRIENDIKA_VERSION . ' ';
	$o .= t('running at web location') . ' ' . z_root() . '</p><p>';

	$o .= t('Shared content within the Friendika network is provided under the <a href="http://creativecommons.org/licenses/by/3.0/">Creative Commons Attribution 3.0 license</a>') . '</p><p>';

	$o .= t('Please visit <a href="http://project.friendika.com">Project.Friendika.com</a> to learn more about the Friendika project.') . '</p><p>';	

	$o .= t('Bug reports and issues: please visit') . ' ' . '<a href="http://bugs.friendika.com">Bugs.Friendika.com</a></p><p>';
	$o .= t('Suggestions, praise, donations, etc. - please email "Info" at Friendika - dot com') . '</p>';

	$o .= '<p></p>';

	if(count($a->plugins)) {
		$o .= '<p>' . t('Installed plugins/addons/apps') . '</p>';
		$o .= '<ul>';
		foreach($a->plugins as $p)
			if(strlen($p))
				$o .= '<li>' . $p . '</li>';
		$o .= '</ul>';
	}
	else
		$o .= '<p>' . t('No installed plugins/addons/apps');

	call_hooks('about_hook', $o); 	

	return $o;

}
