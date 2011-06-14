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

function admin_post(&$a){
	if(!is_site_admin()) {
		return login(false);
	}
	
	// urls
	if ($a->argc > 1){
		switch ($a->argv[1]){
			case 'site': {
				admin_page_site_post($a);
				break;
			}
		}
	}

	goaway($a->get_baseurl() . '/admin' );
	return; // NOTREACHED	
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
	$a->page['aside'] = replace_macros( $t, array(
			'$admin' => $aside, 
			'$admurl'=> $a->get_baseurl()."/admin/"
	));



	/**
	 * Page content
	 */
	$o = '';
	
	// urls
	if ($a->argc > 1){
		switch ($a->argv[1]){
			case 'site':
				$o = admin_page_site($a);
				break;
			case 'users':
				$o = admin_page_users($a);
				break;
			case 'plugins':
				$o = admin_page_plugins($a);
				break;
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


/**
 * Admin Site Page
 */
function admin_page_site_post(&$a){
	if (!x($_POST,"page_site")){
		return;
	}

	
	$sitename 			=	((x($_POST,'sitename'))			? notags(trim($_POST['sitename']))			: '');
	$banner				=	((x($_POST,'banner'))      		? trim($_POST['banner'])					: false);
	$language			=	((x($_POST,'language'))			? notags(trim($_POST['language']))			: '');
	$theme				=	((x($_POST,'theme'))			? notags(trim($_POST['theme']))				: '');
	$maximagesize		=	((x($_POST,'maximagesize'))		? intval(trim($_POST['maximagesize']))		:  0);
	$allowed_sites		=	((x($_POST,'allowed_sites'))	? notags(trim($_POST['allowed_sites']))		: '');
	$allowed_email		=	((x($_POST,'allowed_email'))	? notags(trim($_POST['allowed_email']))		: '');
	$block_public		=	((x($_POST,'block_public'))		? True	:	False);
	$force_publish		=	((x($_POST,'publish_all'))		? True	:	False);
	$global_directory	=	((x($_POST,'directory_submit_url'))	? notags(trim($_POST['directory_submit_url']))	: '');
	$global_search_url	=	((x($_POST,'directory_search_url'))? notags(trim($_POST['directory_search_url']))	: '');
	$no_multi_reg		=	((x($_POST,'no_multi_reg'))		? True	:	False);
	$no_openid			=	((x($_POST,'no_openid'))		? True	:	False);
	$no_gravatar		=	((x($_POST,'no_gravatar'))		? True	:	False);
	$no_regfullname		=	((x($_POST,'no_regfullname'))	? True	:	False);
	$no_utf				=	((x($_POST,'no_utf'))			? True	:	False);
	$rino_enc			=	((x($_POST,'rino_enc'))			? True	:	False);
	$verifyssl			=	((x($_POST,'verifyssl'))		? True	:	False);
	$proxyuser			=	((x($_POST,'proxyuser'))		? notags(trim($_POST['global_search_url']))	: '');
	$proxy				=	((x($_POST,'proxy'))			? notags(trim($_POST['global_search_url']))	: '');
	$timeout			=	((x($_POST,'timeout'))			? intval(trim($_POST['timeout']))		: 60);


	$a->config['sitename'] = $sitename;
	if ($banner==""){
		// don't know why, but del_config doesn't work...
		q("DELETE FROM `config` WHERE `cat` = '%s' AND `k` = '%s' LIMIT 1",
			dbesc("system"),
			dbesc("banner")
		);
	} else {
		set_config('system','banner', $banner);
	}
	set_config('system','language', $language);
	set_config('system','theme', $theme);
	set_config('system','maximagesize', $maximagesize);
	set_config('system','allowed_sites', $allowed_sites);
	set_config('system','allowed_email', $allowed_email);
	set_config('system','block_public', $block_public);
	set_config('system','publish_all', $force_publish);
	if ($global_directory==""){
		// don't know why, but del_config doesn't work...
		q("DELETE FROM `config` WHERE `cat` = '%s' AND `k` = '%s' LIMIT 1",
			dbesc("system"),
			dbesc("directory_submit_url")
		);
	} else {
		set_config('system','directory_submit_url', $global_directory);
	}
	set_config('system','directory_search_url', $global_search_url);
	set_config('system','block_extended_register', $no_multi_reg);
	set_config('system','no_openid', $no_openid);
	set_config('system','no_gravatar', $no_gravatar);
	set_config('system','no_regfullname', $no_regfullname);
	set_config('system','proxy', $no_utf);
	set_config('system','rino_encrypt', $rino_enc);
	set_config('system','verifyssl', $verifyssl);
	set_config('system','proxyuser', $proxyuser);
	set_config('system','proxy', $proxy);
	set_config('system','curl_timeout', $timeout);

	$r = q("SELECT * FROM `config` WHERE `cat`='config' AND `k`='sitename'");
	if (count($r)>0){
		q("UPDATE `config` SET `v`='%s' WHERE `cat`='config' AND `k`='sitename'",
			dbesc($a->config['sitename'])
		);
	} else {
		q("INSERT INTO `config`  ( `cat`, `k`, `v` ) VALUES ( 'config', 'sitename', '%s' )",
			dbesc($a->config['sitename'])
		);
	}
	


	goaway($a->get_baseurl() . '/admin/site' );
	return; // NOTREACHED	
	
}
 
function admin_page_site(&$a) {
	
	/* Installed langs */
	$lang_choices = array();
	$langs = glob('view/*/strings.php');
	
	if(is_array($langs) && count($langs)) {
		if(! in_array('view/en/strings.php',$langs))
			$langs[] = 'view/en/';
		asort($langs);
		foreach($langs as $l) {
			$t = explode("/",$l);
			$lang_choices[$t[1]] = $t[1];
		}
	}
	
	/* Installed themes */
	$theme_choices = array();
	$files = glob('view/theme/*');
	if($files) {
		foreach($files as $file) {
			$f = basename($file);
			$theme_name = ((file_exists($file . '/experimental')) ?  sprintf("%s - \x28Experimental\x29", $f) : $f);
			$theme_choices[$f] = $theme_name;
		}
	}
	
	
	/* Banner */
	$banner = get_config('system','banner');
	if($banner == false) 
		$banner = htmlspecialchars('<a href="http://project.friendika.com"><img id="logo-img" src="images/friendika-32.png" alt="logo" /></a><span id="logo-text"><a href="http://project.friendika.com">Friendika</a></span>');
	
	//echo "<pre>"; var_dump($lang_choices); die("</pre>");


	
	$t = get_markup_template("admin_site.tpl");
	return replace_macros($t, array(
		'$title' => t('Administration'),
		'$page' => t('Site'),
		'$submit' => t('Submit'),
		'$baseurl' => $a->get_baseurl(),
						
									// name, label, value, help string, extra data...
		'$sitename' 		=> array('sitename', t("Site name"), $a->config['sitename'], ""),
		'$banner'			=> array('banner', t("Banner/Logo"), $banner, ""),
		'$language' 		=> array('language', t("System language"), get_config('system','language'), "", $lang_choices),
		'$theme' 			=> array('theme', t("System theme"), get_config('system','theme'), "Default system theme (which may be over-ridden by user profiles)", $theme_choices),

		'$maximagesize'		=> array('maximagesize', t("Maximum image size"), get_config('system','maximagesize'), "Maximum size in bytes of uploaded images. Default is 0, which means no limits."),

		'$allowed_sites'	=> array('allowed_sites', t("Allowed friend domains"), get_config('system','allowed_sites'), "Comma separated list of domains which are allowed to establish friendships with this site. Wildcards are accepted. Empty to allow any domains"),
		'$allowed_email'	=> array('allowed_email', t("Allowed email domains"), get_config('system','allowed_email'), "Comma separated list of domains which are allowed in email addresses for registrations to this site. Wildcards are accepted. Empty to allow any domains"),
		'$block_public'		=> array('block_public', t("Block public"), get_config('system','block_public'), "Check to block public access to all otherwise public personal pages on this site unless you are currently logged in."),
		'$force_publish'	=> array('publish_all', t("Force publish"), get_config('system','publish_all'), "Check to force all profiles on this site to be listed in the site directory."),
		'$global_directory'	=> array('directory_submit_url', t("Global directory update URL"), get_config('system','directory_submit_url'), "URL to update the global directory. If this is not set, the global directory is completely unavailable to the application."),
		'$global_search_url'=> array('directory_search_url', t("Global directory search URL"), get_config('system','directory_search_url'), ""),
			
			
		'$no_multi_reg'		=> array('no_multi_reg', t("Block multiple registrations"),  get_config('system','block_extended_register'), "Disallow users to register additional accounts for use as pages."),
		'$no_openid'		=> array('no_openid', t("No OpenID support"), get_config('system','no_openid'), "Disable OpenID support for registration and logins."),
		'$no_gravatar'		=> array('no_gravatar', t("No Gravatar support"), get_config('system','no_gravatar'), ""),
		'$no_regfullname'	=> array('no_regfullname', t("No fullname check"), get_config('system','no_regfullname'), "If unchecked, force users to registrate with a space between his firsname and lastname in Full name, as an antispam measure"),
		'$no_utf'			=> array('no_utf', t("No UTF-8 Regular expressions"), get_config('system','proxy'), "Default is false (meaning UTF8 regular expressions are supported and working)"),
			
		'$rino_enc'			=> array('rino_enc', t("Enable Rino encrypt"), get_config('system','rino_encrypt'),""),
		'$verifyssl' 		=> array('verifyssl', t("Verify SSL"), get_config('system','verifyssl'), "If you wish, you can turn on strict certificate checking. This will mean you cannot connect (at all) to self-signed SSL sites."),
		'$proxyuser'		=> array('proxyuser', t("Proxy user"), get_config('system','proxyuser'), ""),
		'$proxy'			=> array('proxy', t("Proxy URL"), get_config('system','proxy'), ""),
		'$timeout'			=> array('timeout', t("Network timeout"), (x(get_config('system','curl_timeout'))?get_config('system','curl_timeout'):60), "Value is in seconds. Set to 0 for unlimited (not recommended)."),

			
	));

}


/**
 * Users admin page
 */
 
function admin_page_users(&$a){
	return ":)";
}


/*
 * Plugins admin page
 */

function admin_page_plugins(&$a){
	
	/* all plugins */
	$plugins = array();
	$files = glob("addon/*/");
	if($files) {
		foreach($files as $file) {	
			if (is_dir($file)){
				list($tmp, $id)=array_map("trim", explode("/",$file));
				// TODO: plugins info
				$name=$author=$description=$homepage="";
				$plugins[] = array( $id, (in_array($id,  $a->plugins)?"on":"off") , $name, $author, $description, $homepage);
			}
		}
	}
	
	$t = get_markup_template("admin_plugins.tpl");
	return replace_macros($t, array(
		'$title' => t('Administration'),
		'$page' => t('Plugins'),
		'$submit' => t('Submit'),
		'$baseurl' => $a->get_baseurl(),
	
		'$plugins' => $plugins
	));
}

