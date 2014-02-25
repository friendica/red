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

		if(@is_dir('.git') && function_exists('shell_exec'))
			$commit = @shell_exec('git log -1 --format="%h"');
		if(! isset($commit) || strlen($commit) > 16)
			$commit = '';

		$data = Array(
			'version' => RED_VERSION,
			'commit' => $commit,
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

	if(! get_config('system','hidden_version_siteinfo')) {
		$version = sprintf( t('Version %s'), RED_VERSION );
		if(@is_dir('.git') && function_exists('shell_exec'))
			$commit = @shell_exec('git log -1 --format="%h"');
		if(! isset($commit) || strlen($commit) > 16)
			$commit = '';
	}
	else {
	        $version = $commit = '';
	}
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

	$admininfo = bbcode(get_config('system','admininfo'));

	$project_donate = t('Project Donations');
	$donate_text = t('<p>The Red Matrix is provided for you by volunteers working in their spare time. Your support will help us to build a better, freer, and privacy respecting web. Select the following option for a one-time donation of your choosing</p>');
	$alternatively = t('<p>or</p>');
	$recurring = t('Recurring Donation Options');
	
	$donate = <<< EOT
<h3>{$project_donate}</h3>
$donate_text
<form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_donations" /><input type="hidden" name="business" value="mike@macgirvin.com" /><input type="hidden" name="lc" value="US" /><input type="hidden" name="item_name" value="Distributed Social Network Support Donation" /><input type="hidden" name="no_note" value="0" /><input type="hidden" name="currency_code" value="USD" /><input type="hidden" name="bn" value="PP-DonationsBF:btn_donate_LG.gif:NonHostedGuest" /><input style="border: none;" type="image" name="submit" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" alt="Donations gladly accepted to support our work" /></form><br />
<strong>$alternatively</strong>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_s-xclick" /><input type="hidden" name="hosted_button_id" value="FHV36KE28CYM8" /><br />
<table><tbody><tr><td><input type="hidden" name="on0" value="$recurring" />$recurring</td>
</tr><tr><td>
<select name="os0"><option value="Option 1">Option 1 : $3.00USD - monthly</option><option value="Option 2">Option 2 : $5.00USD - monthly</option><option value="Option 3">Option 3 : $10.00USD - monthly</option><option value="Option 4">Option 4 : $20.00USD - monthly</option></select></td>
</tr></tbody></table><p><input type="hidden" name="currency_code" value="USD" /><input type="image" style="border: none;" border="0" name="submit" src="https://www.paypalobjects.com/en_US/i/btn/btn_subscribeCC_LG.gif" alt="PayPal - The safer, easier way to pay online!" /><img src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" alt="" width="1" height="1" border="0" /></p></form>
<p></p>
EOT;

	if(file_exists('doc/site_donate.html'))
		$donate .= file_get_contents('doc/site_donate.html');

	$o = replace_macros(get_markup_template('siteinfo.tpl'), array(
                '$title' => t('Red'),
		'$description' => t('This is a hub of the Red Matrix - a global cooperative network of decentralised privacy enhanced websites.'),
		'$version' => $version,
		'$commit' => $commit,
		'$web_location' => t('Running at web location') . ' ' . z_root(),
		'$visit' => t('Please visit <a href="http://getzot.com">GetZot.com</a> to learn more about the Red Matrix.'),
		'$bug_text' => t('Bug reports and issues: please visit'),
		'$bug_link_url' => 'https://github.com/friendica/red/issues',
		'$bug_link_text' => 'redmatrix issues',
		'$contact' => t('Suggestions, praise, etc. - please email "redmatrix" at librelist - dot com'),
		'$donate' => $donate,
		'$adminlabel' => t('Site Administrators'),
		'$admininfo' => $admininfo,
		'$plugins_text' => $plugins_text,
		'$plugins_list' => $plugins_list
        ));

	call_hooks('about_hook', $o); 	

	return $o;

}
