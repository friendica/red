<?php
 /**
  * Friendika admin
  */
  
 
function admin_init(&$a) {
	if(!is_site_admin()) {
		notice( t('Permission denied.') . EOL);
		return;
	}
}


function admin_content(&$a) {

	if(!is_site_admin()) {
		return login(false);
	}

	/**
	 * Side bar links
	 */

	// array( url, name, extra css classes )
	$aside = Array(
		'site'	 =>	Array($a->get_baseurl()."/admin/site/", t("Site") , "site"),
		'users'	 =>	Array($a->get_baseurl()."/admin/users/", t("Users") , "users"),
		'plugins'=>	Array($a->get_baseurl()."/admin/plugins/", t("Plugins") , "plugins")
	);
	
	/* get plugins admin page */
	
	$r = q("SELECT * FROM `hook` WHERE `hook`='plugin_admin'");
	$aside['plugins_admin']=Array();
	foreach ($r as $h){
		$plugin = explode("/",$h['file']); $plugin = $plugin[1];
		$aside['plugins_admin'][] = Array($a->get_baseurl()."/admin/plugins/".$plugin, $plugin, "plugin");
	}
		
	$aside['logs'] = Array($a->get_baseurl()."/admin/logs/", t("Logs"), "logs");

	$t = get_markup_template("admin_aside.tpl");
	$a->page['aside'] = replace_macros( $t, array('$admin' => $aside) );



	/**
	 * Page content
	 */
	$o = '';
	
	// urls
	if ($a->argc > 1){
		switch ($a->argv[1]){
			case 'site': {
				$o = admin_page_site($a);
				break;
			}
			default:
				notice( t("Item not found.") );
		}
	} else {
		$o = admin_page_summary($a);
	}
	return $o;
} 


/**
 * Admin Summary Page
 */
function admin_page_summary(&$a) {
	$r = q("SELECT `page-flags`, COUNT(uid) as `count` FROM `user` GROUP BY `page-flags`");
	$accounts = Array(
		Array( t('Normal Account'), 0),
		Array( t('Soapbox Account'), 0),
		Array( t('Community/Celebrity Account'), 0),
		Array( t('Automatic Friend Account'), 0)
	);
	$users=0;
	foreach ($r as $u){ $accounts[$u['page-flags']][1] = $u['count']; $users+=$u['count']; }

	//echo "<pre>"; var_dump($a->plugins); die("</pre>");

	
	$r = q("SELECT COUNT(id) as `count` FROM `register`");
	$pending = $r[0]['count'];
	
	
	
	
	
	$t = get_markup_template("admin_summary.tpl");
	return replace_macros($t, array(
		'$title' => t('Administration'),
		'$page' => t('Summary'),
		'$users' => Array( t('Registered users'), $users),
		'$accounts' => $accounts,
		'$pending' => Array( t('Pending registrations'), $pending),
		'$version' => Array( t('Version'), FRIENDIKA_VERSION),
		'$build' =>  get_config('system','build'),
		'$plugins' => Array( t('Active plugins'), $a->plugins )
	));
}
