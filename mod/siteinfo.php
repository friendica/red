<?php

function siteinfo_init(&$a) {
	global $db;
	
	if ($a->argv[1]=="json"){
		$register_policy = Array('REGISTER_CLOSED', 'REGISTER_APPROVE', 'REGISTER_OPEN');
		$directory_mode = Array('DIRECTORY_MODE_NORMAL', 'DIRECTORY_MODE_SECONDARY','DIRECTORY_MODE_PRIMARY', 'DIRECTORY_MODE_STANDALONE');
		
		$sql_extra = '';

		$r = q("select * from channel left join account on account_id = channel_account_id where ( account_roles & 4096 )>0 and account_default_channel = channel_id");


		if($r) {
			$admin = array();
			foreach($r as $rr) {
				if($rr['channel_pageflags'] & PAGE_HUBADMIN)
					$admin[] = array( 'name' => $rr['channel_name'], 'address' => $rr['channel_address'] . '@' . get_app()->get_hostname(), 'channel' => z_root() . '/channel/' . $rr['channel_address']);
			}
			if(! $admin) {
				foreach($r as $rr) {
					$admin[] = array( 'name' => $rr['channel_name'], 'address' => $rr['channel_address'] . '@' . get_app()->get_hostname(), 'channel' => z_root() . '/channel/' . $rr['channel_address']);
				}
			}
		}
		else {
			$admin = false;
		}

		$def_service_class = get_config('system','default_service_class');
		if($def_service_class)
			$service_class = get_config('service_class',$def_service_class);
		else
			$service_class = false;

		$visible_plugins = array();
		if(is_array($a->plugins) && count($a->plugins)) {
			$r = q("select * from addon where hidden = 0");
			if(count($r))
				foreach($r as $rr)
					$visible_plugins[] = $rr['name'];
		}
		sort($visible_plugins);

		if(@is_dir('.git') && function_exists('shell_exec'))
			$commit = trim(@shell_exec('git log -1 --format="%h"'));
		if(! isset($commit) || strlen($commit) > 16)
			$commit = '';

		$site_info = get_config('system','info');
		$site_name = get_config('system','sitename');
		
		//Statistics
		$channels_total_stat = intval(get_config('system','channels_total_stat'));
		$channels_active_halfyear_stat = intval(get_config('system','channels_active_halfyear_stat'));
		$channels_active_monthly_stat = intval(get_config('system','channels_active_monthly_stat'));
		$local_posts_stat = intval(get_config('system','local_posts_stat'));
		$hide_in_statistics = intval(get_config('system','hide_in_statistics'));
		
		$data = Array(
			'version' => RED_VERSION,
			'commit' => $commit,
			'url' => z_root(),
			'plugins' => $visible_plugins,
			'register_policy' =>  $register_policy[$a->config['system']['register_policy']],
			'directory_mode' =>  $directory_mode[$a->config['system']['directory_mode']],
			'language' => get_config('system','language'),
			'diaspora_emulation' => get_config('system','diaspora_enabled'),
			'rss_connections' => get_config('system','feed_contacts'),
			'default_service_restrictions' => $service_class,
			'admin' => $admin,
			'site_name' => (($site_name) ? $site_name : ''),
			'platform' => RED_PLATFORM,
			'dbdriver' => $db->getdriver(),
			'lastpoll' => get_config('system','lastpoll'),
			'info' => (($site_info) ? $site_info : ''),
			'channels_total' => $channels_total_stat,
			'channels_active_halfyear' => $channels_active_halfyear_stat,
			'channels_active_monthly' => $channels_active_monthly_stat,
			'local_posts' => $local_posts_stat,
			'hide_in_statistics' => $hide_in_statistics
		);
		json_return_and_die($data);
	}
}



function siteinfo_content(&$a) {

	if(! get_config('system','hidden_version_siteinfo')) {
		$version = sprintf( t('Version %s'), RED_VERSION );
		if(@is_dir('.git') && function_exists('shell_exec')) {
			$commit = @shell_exec('git log -1 --format="%h"');
			$tag = @shell_exec('git describe --tags --abbrev=0');
		}
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

	$txt = get_config('system','admininfo');
	$admininfo = bbcode($txt);

	if(file_exists('doc/site_donate.html'))
		$donate .= file_get_contents('doc/site_donate.html');

	$o = replace_macros(get_markup_template('siteinfo.tpl'), array(
                '$title' => t('Red'),
		'$description' => t('This is a hub of the Red Matrix - a global cooperative network of decentralized privacy enhanced websites.'),
		'$version' => $version,
		'$tag_txt' => t('Tag: '),
		'$tag' => $tag,
		'$polled' => t('Last background fetch: '),
		'$lastpoll' => get_poller_runtime(),
		'$commit' => $commit,
		'$web_location' => t('Running at web location') . ' ' . z_root(),
		'$visit' => t('Please visit <a href="https://redmatrix.me">RedMatrix.me</a> to learn more about the Red Matrix.'),
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
