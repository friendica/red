<?php

 /**
  * Red admin
  */


/**
 * @param App $a
 */
function admin_post(&$a){
	logger('admin_post', LOGGER_DEBUG);

	if(!is_site_admin()) {
		return;
	}

	// urls
	if (argc() > 1){
		switch (argv(1)){
			case 'site':
				admin_page_site_post($a);
				break;
			case 'users':
				admin_page_users_post($a);
				break;
			case 'plugins':
				if (argc() > 2 && 
					is_file("addon/" . argv(2) . "/" . argv(2) . ".php")){
						@include_once("addon/" . argv(2) . "/" . argv(2) . ".php");
						if(function_exists(argv(2).'_plugin_admin_post')) {
							$func = argv(2) . '_plugin_admin_post';
							$func($a);
						}
				}
				goaway($a->get_baseurl(true) . '/admin/plugins/' . argv(2) );
				return; // NOTREACHED
				break;
			case 'themes':
				$theme = argv(2);
				if (is_file("view/theme/$theme/php/config.php")){
					require_once("view/theme/$theme/php/config.php");
// fixme add parent theme if derived
					if (function_exists("theme_admin_post")){
						theme_admin_post($a);
					}
				}
				info(t('Theme settings updated.'));
				if(is_ajax()) return;
				
				goaway($a->get_baseurl(true) . '/admin/themes/' . $theme );

				return;
				break;
			case 'logs':
				admin_page_logs_post($a);
				break;
			case 'hubloc':
				admin_page_hubloc_post($a);
				break;
			case 'dbsync':
				admin_page_dbsync_post($a);
				break;
		}
	}

	goaway($a->get_baseurl(true) . '/admin' );
	return; // NOTREACHED	
}

/**
 * @param App $a
 * @return string
 */
function admin_content(&$a) {

	logger('admin_content', LOGGER_DEBUG);
	if(!is_site_admin()) {
		return login(false);
	}

	/**
	 * Side bar links
	 */

	// array( url, name, extra css classes )
	$aside = Array(
		'site'	 =>	Array($a->get_baseurl(true)."/admin/site/", t("Site") , "site"),
		'users'	 =>	Array($a->get_baseurl(true)."/admin/users/", t("Users") , "users"),
		'plugins'=>	Array($a->get_baseurl(true)."/admin/plugins/", t("Plugins") , "plugins"),
		'themes' =>	Array($a->get_baseurl(true)."/admin/themes/", t("Themes") , "themes"),
		'hubloc' =>	Array($a->get_baseurl(true)."/admin/hubloc/", t("Server") , "server"),
		'dbsync' => Array($a->get_baseurl(true)."/admin/dbsync/", t('DB updates'), "dbsync")
	);
	
	/* get plugins admin page */
	
	$r = q("SELECT * FROM `addon` WHERE `plugin_admin`=1");
	$aside['plugins_admin']=Array();
	foreach ($r as $h){
		$plugin =$h['name'];
		$aside['plugins_admin'][] = Array($a->get_baseurl(true)."/admin/plugins/".$plugin, $plugin, "plugin");
		// temp plugins with admin
		$a->plugins_admin[] = $plugin;
	}
		
	$aside['logs'] = Array($a->get_baseurl(true)."/admin/logs/", t("Logs"), "logs");

	$t = get_markup_template("admin_aside.tpl");
	$a->page['aside'] .= replace_macros( $t, array(
			'$admin' => $aside, 
			'$admtxt' => t('Admin'),
			'$plugadmtxt' => t('Plugin Features'),
			'$logtxt' => t('Logs'),
			'$h_pending' => t('User registrations waiting for confirmation'),
			'$admurl'=> $a->get_baseurl(true)."/admin/"
	));



	/**
	 * Page content
	 */
	$o = '';
	
	// urls
	if (argc() > 1){
		switch (argv(1)) {
			case 'site':
				$o = admin_page_site($a);
				break;
			case 'users':
				$o = admin_page_users($a);
				break;
			case 'plugins':
				$o = admin_page_plugins($a);
				break;
			case 'themes':
				$o = admin_page_themes($a);
				break;
			case 'hubloc':
				$o = admin_page_hubloc($a);
				break;
			case 'logs':
				$o = admin_page_logs($a);
				break;
			case 'dbsync':
				$o = admin_page_dbsync($a);
				break;
			default:
				notice( t("Item not found.") );
		}
	} else {
		$o = admin_page_summary($a);
	}
	
	if(is_ajax()) {
		echo $o; 
		killme();
		return '';
	} else {
		return $o;
	}
} 


/**
 * Admin Summary Page
 * @param App $a
 * @return string
 */
function admin_page_summary(&$a) {


	// list total user accounts, expirations etc.


	$r = q("SELECT COUNT(account_id) as total FROM `account`");
	$users = $r[0]['total'];
	
	$r = q("SELECT COUNT(id) as `count` FROM `register`");
	$pending = $r[0]['count'];

	$r = q("select count(*) as total from outq");
	$queue = (($r) ? $r[0]['total'] : 0);

	// We can do better, but this is a quick queue status
	
	$queues = array( 'label' => t('Message queues'), 'queue' => $queue );


	$t = get_markup_template("admin_summary.tpl");
	return replace_macros($t, array(
		'$title' => t('Administration'),
		'$page' => t('Summary'),
		'$queues' => $queues,
		'$users' => Array( t('Registered users'), $users),
		'$accounts' => $accounts,
		'$pending' => Array( t('Pending registrations'), $pending),
		'$version' => Array( t('Version'), RED_VERSION),
		'$build' =>  get_config('system','db_version'),
		'$plugins' => Array( t('Active plugins'), $a->plugins )
	));
}


/**
 * Admin Site Page
 *  @param App $a
 */
function admin_page_site_post(&$a){
	if (!x($_POST,"page_site")){
		return;
	}

    check_form_security_token_redirectOnErr('/admin/site', 'admin_site');

	$sitename 			=	((x($_POST,'sitename'))			? notags(trim($_POST['sitename']))			: '');
	$banner				=	((x($_POST,'banner'))      		? trim($_POST['banner'])				: false);
	$admininfo			=	((x($_POST,'admininfo'))		? trim($_POST['admininfo'])				: false);
	$language			=	((x($_POST,'language'))			? notags(trim($_POST['language']))			: '');
	$theme				=	((x($_POST,'theme'))			? notags(trim($_POST['theme']))				: '');
	$theme_mobile			=	((x($_POST,'theme_mobile'))		? notags(trim($_POST['theme_mobile']))			: '');
	$theme_accessibility		=	((x($_POST,'theme_accessibility'))	? notags(trim($_POST['theme_accessibility']))		: '');
	$site_channel			=	((x($_POST,'site_channel'))	? notags(trim($_POST['site_channel']))				: '');
	$maximagesize		=	((x($_POST,'maximagesize'))		? intval(trim($_POST['maximagesize']))				:  0);
	
	
	$register_policy	=	((x($_POST,'register_policy'))	? intval(trim($_POST['register_policy']))	:  0);
	$access_policy	=	((x($_POST,'access_policy'))	? intval(trim($_POST['access_policy']))	:  0);
	$abandon_days	    =	((x($_POST,'abandon_days'))	    ? intval(trim($_POST['abandon_days']))	    :  0);

	$register_text		=	((x($_POST,'register_text'))	? notags(trim($_POST['register_text']))		: '');	
	
	$allowed_sites		=	((x($_POST,'allowed_sites'))	? notags(trim($_POST['allowed_sites']))		: '');
	$allowed_email		=	((x($_POST,'allowed_email'))	? notags(trim($_POST['allowed_email']))		: '');
	$block_public		=	((x($_POST,'block_public'))		? True	:	False);
	$force_publish		=	((x($_POST,'publish_all'))		? True	:	False);
	$no_login_on_homepage	=	((x($_POST,'no_login_on_homepage'))		? True	:	False);
	$global_directory	=	((x($_POST,'directory_submit_url'))	? notags(trim($_POST['directory_submit_url']))	: '');
	$no_community_page	=	!((x($_POST,'no_community_page'))	? True	:	False);

	$verifyssl			=	((x($_POST,'verifyssl'))		? True	:	False);
	$proxyuser			=	((x($_POST,'proxyuser'))		? notags(trim($_POST['proxyuser']))	: '');
	$proxy				=	((x($_POST,'proxy'))			? notags(trim($_POST['proxy']))	: '');
	$timeout			=	((x($_POST,'timeout'))			? intval(trim($_POST['timeout']))		: 60);
	$delivery_interval	=	((x($_POST,'delivery_interval'))? intval(trim($_POST['delivery_interval']))		: 0);
	$poll_interval	=	((x($_POST,'poll_interval'))? intval(trim($_POST['poll_interval']))		: 0);
//	$ssl_policy         =   ((x($_POST,'ssl_policy')) ? intval($_POST['ssl_policy']) : 0);
/*
	if($ssl_policy != intval(get_config('system','ssl_policy'))) {
		if($ssl_policy == SSL_POLICY_FULL) {
			q("update `contact` set 
				`url`     = replace(`url`    , 'http:' , 'https:'),
				`photo`   = replace(`photo`  , 'http:' , 'https:'),
				`thumb`   = replace(`thumb`  , 'http:' , 'https:'),
				`micro`   = replace(`micro`  , 'http:' , 'https:'),
				`request` = replace(`request`, 'http:' , 'https:'),
				`notify`  = replace(`notify` , 'http:' , 'https:'),
				`poll`    = replace(`poll`   , 'http:' , 'https:'),
				`confirm` = replace(`confirm`, 'http:' , 'https:'),
				`poco`    = replace(`poco`   , 'http:' , 'https:')
				where `self` = 1"
			);
			q("update `profile` set 
				`photo`   = replace(`photo`  , 'http:' , 'https:'),
				`thumb`   = replace(`thumb`  , 'http:' , 'https:')
				where 1 "
			);
		}
		elseif($ssl_policy == SSL_POLICY_SELFSIGN) {
			q("update `contact` set 
				`url`     = replace(`url`    , 'https:' , 'http:'),
				`photo`   = replace(`photo`  , 'https:' , 'http:'),
				`thumb`   = replace(`thumb`  , 'https:' , 'http:'),
				`micro`   = replace(`micro`  , 'https:' , 'http:'),
				`request` = replace(`request`, 'https:' , 'http:'),
				`notify`  = replace(`notify` , 'https:' , 'http:'),
				`poll`    = replace(`poll`   , 'https:' , 'http:'),
				`confirm` = replace(`confirm`, 'https:' , 'http:'),
				`poco`    = replace(`poco`   , 'https:' , 'http:')
				where `self` = 1"
			);
			q("update `profile` set 
				`photo`   = replace(`photo`  , 'https:' , 'http:'),
				`thumb`   = replace(`thumb`  , 'https:' , 'http:')
				where 1 "
			);
		}
	}
*/
//	set_config('system','ssl_policy',$ssl_policy);
	set_config('system','delivery_interval',$delivery_interval);
	set_config('system','poll_interval',$poll_interval);
	set_config('system','maxloadavg',$maxloadavg);
	set_config('system','sitename',$sitename);
	set_config('system','no_login_on_homepage',$no_login_on_homepage);

	if ($banner=="") {
		del_config('system','banner');
	} 
	else {
		set_config('system','banner', $banner);
	}

	if ($admininfo==''){
		del_config('system','admininfo');
	}
	else {
		set_config('system','admininfo', $admininfo);
	}
	set_config('system','language', $language);
	set_config('system','theme', $theme);
	if ( $theme_mobile === '---' ) {
		del_config('system','mobile_theme');
	} else {
		set_config('system','mobile_theme', $theme_mobile);
        }
        if ( $theme_accessibility === '---' ) {
		del_config('system','accessibility_theme');
	} else {
		set_config('system','accessibility_theme', $theme_accessibility);
        }
      
	set_config('system','site_channel', $site_channel);
	set_config('system','maximagesize', $maximagesize);
	
	set_config('system','register_policy', $register_policy);
	set_config('system','access_policy', $access_policy);
	set_config('system','account_abandon_days', $abandon_days);
	set_config('system','register_text', $register_text);
	set_config('system','allowed_sites', $allowed_sites);
	set_config('system','allowed_email', $allowed_email);
	set_config('system','block_public', $block_public);
	set_config('system','publish_all', $force_publish);
	if($global_directory=="") {
		del_config('system','directory_submit_url');
	} 
	else {
		set_config('system','directory_submit_url', $global_directory);
	}

	set_config('system','no_community_page', $no_community_page);
	set_config('system','no_utf', $no_utf);
	set_config('system','verifyssl', $verifyssl);
	set_config('system','proxyuser', $proxyuser);
	set_config('system','proxy', $proxy);
	set_config('system','curl_timeout', $timeout);

	info( t('Site settings updated.') . EOL);
	goaway($a->get_baseurl(true) . '/admin/site' );
	return; // NOTREACHED	
	
}

/**
 * @param  App $a
 * @return string
 */
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
	$theme_choices_mobile = array();
	$theme_choices_mobile["---"] = t("No special theme for mobile devices");
	$theme_choices_accessibility = array();
	$theme_choices_accessibility["---"] =t("No special theme for accessibility");
	$files = glob('view/theme/*');
	if($files) {
		foreach($files as $file) {
			$f = basename($file);
			$theme_name = ((file_exists($file . '/experimental')) ?  sprintf("%s - Experimental", $f) : $f);
		if (file_exists($file . '/mobile')) {
			$theme_choices_mobile[$f] = $theme_name;
            }
		if (file_exists($file . '/accessibility')) {
                $theme_choices_accessibility[$f] = $theme_name;
            }
			$theme_choices[$f] = $theme_name;
		}
	}
	
	
	/* Banner */
	$banner = get_config('system','banner');
	if($banner == false) 
		$banner = 'red';
	$banner = htmlspecialchars($banner);
	
	/* Admin Info */
	$admininfo = get_config('system','admininfo');

	/* Register policy */
	$register_choices = Array(
		REGISTER_CLOSED  => t("Closed"),
		REGISTER_APPROVE => t("Requires approval"),
		REGISTER_OPEN    => t("Open")
	); 

	/* Acess policy */
	$access_choices = Array(
		ACCESS_PRIVATE => t("Private"),
		ACCESS_PAID => t("Paid Access"),
		ACCESS_FREE => t("Free Access"),
		ACCESS_TIERED => t("Tiered Access")
	);
	
//	$ssl_choices = array(
//		SSL_POLICY_NONE     => t("No SSL policy, links will track page SSL state"),
//		SSL_POLICY_FULL     => t("Force all links to use SSL")
//	);

	$t = get_markup_template("admin_site.tpl");
	return replace_macros($t, array(
		'$title' => t('Administration'),
		'$page' => t('Site'),
		'$submit' => t('Submit'),
		'$registration' => t('Registration'),
		'$upload' => t('File upload'),
		'$corporate' => t('Policies'),
		'$advanced' => t('Advanced'),
		
		'$baseurl' => $a->get_baseurl(true),
									// name, label, value, help string, extra data...
		'$sitename' 		=> array('sitename', t("Site name"), htmlspecialchars(get_config('system','sitename'), ENT_QUOTES, 'UTF-8'),''),
		'$banner'			=> array('banner', t("Banner/Logo"), $banner, ""),
		'$admininfo'		=> array('admininfo', t("Administrator Information"), $admininfo, t("Contact information for site administrators.  Displayed on siteinfo page.  BBCode can be used here")),
		'$language' 		=> array('language', t("System language"), get_config('system','language'), "", $lang_choices),
		'$theme' 			=> array('theme', t("System theme"), get_config('system','theme'), t("Default system theme - may be over-ridden by user profiles - <a href='#' id='cnftheme'>change theme settings</a>"), $theme_choices),
		'$theme_mobile' 	=> array('theme_mobile', t("Mobile system theme"), get_config('system','mobile_theme'), t("Theme for mobile devices"), $theme_choices_mobile),
		'$theme_accessibility' 	=> array('theme_accessibility', t("Accessibility system theme"), get_config('system','accessibility_theme'), t("Accessibility theme"), $theme_choices_accessibility),
		'$site_channel' 	=> array('site_channel', t("Channel to use for this website's static pages"), get_config('system','site_channel'), t("Site Channel")),
//		'$ssl_policy'       => array('ssl_policy', t("SSL link policy"), (string) intval(get_config('system','ssl_policy')), t("Determines whether generated links should be forced to use SSL"), $ssl_choices),
		'$maximagesize'		=> array('maximagesize', t("Maximum image size"), get_config('system','maximagesize'), t("Maximum size in bytes of uploaded images. Default is 0, which means no limits.")),
		'$register_policy'	=> array('register_policy', t("Register policy"), get_config('system','register_policy'), "", $register_choices),
		'$access_policy'	=> array('access_policy', t("Access policy"), get_config('system','access_policy'), "", $access_choices),
		'$register_text'	=> array('register_text', t("Register text"), htmlspecialchars(get_config('system','register_text'), ENT_QUOTES, 'UTF-8'), t("Will be displayed prominently on the registration page.")),
		'$abandon_days'     => array('abandon_days', t('Accounts abandoned after x days'), get_config('system','account_abandon_days'), t('Will not waste system resources polling external sites for abandonded accounts. Enter 0 for no time limit.')),
		'$allowed_sites'	=> array('allowed_sites', t("Allowed friend domains"), get_config('system','allowed_sites'), t("Comma separated list of domains which are allowed to establish friendships with this site. Wildcards are accepted. Empty to allow any domains")),
		'$allowed_email'	=> array('allowed_email', t("Allowed email domains"), get_config('system','allowed_email'), t("Comma separated list of domains which are allowed in email addresses for registrations to this site. Wildcards are accepted. Empty to allow any domains")),
		'$block_public'		=> array('block_public', t("Block public"), get_config('system','block_public'), t("Check to block public access to all otherwise public personal pages on this site unless you are currently logged in.")),
		'$force_publish'	=> array('publish_all', t("Force publish"), get_config('system','publish_all'), t("Check to force all profiles on this site to be listed in the site directory.")),
		'$no_login_on_homepage'	=> array('no_login_on_homepage', t("No login on Homepage"), get_config('system','no_login_on_homepage'), t("Check to hide the login form from your sites homepage when visitors arrive who are not logged in (e.g. when you put the content of the homepage in via the site channel).")),
			
		'$proxyuser'		=> array('proxyuser', t("Proxy user"), get_config('system','proxyuser'), ""),
		'$proxy'			=> array('proxy', t("Proxy URL"), get_config('system','proxy'), ""),
		'$timeout'			=> array('timeout', t("Network timeout"), (x(get_config('system','curl_timeout'))?get_config('system','curl_timeout'):60), t("Value is in seconds. Set to 0 for unlimited (not recommended).")),
		'$delivery_interval'			=> array('delivery_interval', t("Delivery interval"), (x(get_config('system','delivery_interval'))?get_config('system','delivery_interval'):2), t("Delay background delivery processes by this many seconds to reduce system load. Recommend: 4-5 for shared hosts, 2-3 for virtual private servers. 0-1 for large dedicated servers.")),
		'$poll_interval'			=> array('poll_interval', t("Poll interval"), (x(get_config('system','poll_interval'))?get_config('system','poll_interval'):2), t("Delay background polling processes by this many seconds to reduce system load. If 0, use delivery interval.")),
		'$maxloadavg'			=> array('maxloadavg', t("Maximum Load Average"), ((intval(get_config('system','maxloadavg')) > 0)?get_config('system','maxloadavg'):50), t("Maximum system load before delivery and poll processes are deferred - default 50.")),
        '$form_security_token' => get_form_security_token("admin_site"),
			
	));

}
function admin_page_hubloc_post(&$a){
	check_form_security_token_redirectOnErr('/admin/hubloc', 'admin_hubloc');
	require_once('include/zot.php');

	//prepare for ping

	if ( $_POST['hublocid']) {
		$hublocid = $_POST['hublocid'];
		$arrhublocurl = q("SELECT hubloc_url FROM hubloc WHERE hubloc_id = %d ",
			intval($hublocid)
		);
		$hublocurl = $arrhublocurl[0]['hubloc_url'] . '/post';
		$hublocurl = "http://fred-dev.michameer.dyndns.org/post";
		
		//perform ping
		$m = zot_build_packet($a->get_channel(),'ping');
		logger('ping message : ' . print_r($m,true), LOGGER_DEBUG);
		logger('ping  _REQUEST ' . print_r($_REQUEST,true), LOGGER_DEBUG);
	        $r = zot_zot($hublocurl,$m);
        	logger('ping answer: ' . print_r($r,true), LOGGER_DEBUG);
		
		//unfotunatly zping wont work, I guess return format is not correct
		 //require_once('mod/zping.php');
		 //$r = zping_content($hublocurl);
        	 //logger('zping answer: ' . $r, LOGGER_DEBUG);
		
		//handle results and set the hubloc flags in db to make results visible

		//in case of repair store new pub key for tested hubloc (all channel with this hubloc) in db
		//after repair set hubloc flags to 0

	}


	goaway($a->get_baseurl(true) . '/admin/hubloc' );
	return;
}

function admin_page_hubloc(&$a) {
	$o = '';
	$hubloc = q("SELECT hubloc_id, hubloc_addr, hubloc_host, hubloc_status  FROM hubloc");

	
	if(! $hubloc){
		notice( t('No server found') . EOL);
		goaway($a->get_baseurl(true) . '/admin/hubloc');
	}

	$t = get_markup_template("admin_hubloc.tpl");
        return replace_macros($t, array(
		'$hubloc' => $hubloc,
		'$th_hubloc' => array(t('ID'), t('for channel'), t('on server'), t('Status')),
                '$title' => t('Administration'),
                '$page' => t('Server'),
                '$queues' => $queues,
                //'$accounts' => $accounts, /*$accounts is empty here*/
                '$pending' => Array( t('Pending registrations'), $pending),
                '$plugins' => Array( t('Active plugins'), $a->plugins ),
		'$form_security_token' => get_form_security_token("admin_hubloc")
        ));
	return $o;
}


function admin_page_dbsync(&$a) {

	$o = '';

	if(argc() > 3 && intval(argv(3)) && argv(2) === 'mark') {
		set_config('database', 'update_r' . intval(argv(3)), 'success');
		if(intval(get_config('system','db_version')) <= intval(argv(3)))
			set_config('system','db_version',intval(argv(3)) + 1);
		info( t('Update has been marked successful') . EOL);
		goaway($a->get_baseurl(true) . '/admin/dbsync');
	}

	if(argc() > 2 && intval(argv(2))) {
		require_once('install/update.php');
		$func = 'update_r' . intval(argv(2));
		if(function_exists($func)) {
			$retval = $func();
			if($retval === UPDATE_FAILED) {
				$o .= sprintf( t('Executing %s failed. Check system logs.'), $func); 
			}
			elseif($retval === UPDATE_SUCCESS) {
				$o .= sprintf( t('Update %s was successfully applied.', $func));
				set_config('database',$func, 'success');
			}
			else
				$o .= sprintf( t('Update %s did not return a status. Unknown if it succeeded.'), $func);
		}
		else
			$o .= sprintf( t('Update function %s could not be found.'), $func);
		return $o;
	}

	$failed = array();
	$r = q("select * from config where `cat` = 'database' ");
	if(count($r)) {
		foreach($r as $rr) {
			$upd = intval(substr($rr['k'],8));
			if($upd < 1139 || $rr['v'] === 'success')
				continue;
			$failed[] = $upd;
		}
	}
	if(! count($failed))
		return '<h3>' . t('No failed updates.') . '</h3>';

	$o = replace_macros(get_markup_template('failed_updates.tpl'),array(
		'$base' => $a->get_baseurl(true),
		'$banner' => t('Failed Updates'),
		'$desc' => '',
		'$mark' => t('Mark success (if update was manually applied)'),
		'$apply' => t('Attempt to execute this update step automatically'),
		'$failed' => $failed
	));	

	return $o;

}

/**
 * Users admin page
 *
 * @param App $a
 */
function admin_page_users_post(&$a){
	$pending = ( x($_POST, 'pending') ? $_POST['pending'] : Array() );
	$users = ( x($_POST, 'user') ? $_POST['user'] : Array() );

    check_form_security_token_redirectOnErr('/admin/users', 'admin_users');

	if (x($_POST,'page_users_block')){
		foreach($users as $uid){
			q("UPDATE account SET account_flags = (account_flags & %d) where account_id = %d limit 1",
				intval(ACCOUNT_BLOCKED),
				intval( $uid )
			);
		}
		notice( sprintf( tt("%s user blocked/unblocked", "%s users blocked/unblocked", count($users)), count($users)) );
	}
	if (x($_POST,'page_users_delete')){
		require_once("include/Contact.php");
		foreach($users as $uid){
			account_remove($uid,true);
		}
		notice( sprintf( tt("%s user deleted", "%s users deleted", count($users)), count($users)) );
	}
	
	if (x($_POST,'page_users_approve')){
		require_once('include/account.php');
		foreach($pending as $hash){
			user_allow($hash);
		}
	}
	if (x($_POST,'page_users_deny')){
		require_once('include/account.php');
		foreach($pending as $hash){
			user_deny($hash);
		}
	}
	goaway($a->get_baseurl(true) . '/admin/users' );
	return; // NOTREACHED	
}

/**
 * @param App $a
 * @return string
 */
function admin_page_users(&$a){
	if (argc() > 2) {
		$uid = argv(3);
		$account = q("SELECT * FROM account WHERE account_id = %d", 
			intval($uid)
		);

		if (! $account) {
			notice( t('Account not found') . EOL);
			goaway($a->get_baseurl(true) . '/admin/users' );
		}		

		switch(argv(2)){
			case "delete":{
                check_form_security_token_redirectOnErr('/admin/users', 'admin_users', 't');
				// delete user
				require_once("include/Contact.php");
				account_remove($uid,true);
				
				notice( sprintf(t("User '%s' deleted"), $account[0]['account_email']) . EOL);
			}; break;
			case "block":{
                check_form_security_token_redirectOnErr('/admin/users', 'admin_users', 't');
				q("UPDATE account SET account_flags = ( account_flags ^ %d ) where account_id = %d",
					intval(ACCOUNT_BLOCKED),
					intval( $uid )
				);

				notice( sprintf( (($account['account_flags'] & ACCOUNT_BLOCKED) ? t("User '%s' unblocked"):t("User '%s' blocked")) , $account[0]['account_email']) . EOL);
			}; break;
		}
		goaway($a->get_baseurl(true) . '/admin/users' );
		return ''; // NOTREACHED
		
	}
	
	/* get pending */
	$pending = q("SELECT account.*, register.hash from account left join register on account_id = register.uid where (account_flags & %d ) ",
		intval(ACCOUNT_PENDING)
	);	
	
	/* get users */

	$total = q("SELECT count(*) as total FROM account where 1");
	if(count($total)) {
		$a->set_pager_total($total[0]['total']);
		$a->set_pager_itemspage(100);
	}
	

//	WEe'll still need to link email addresses to admin/users/channels or some such, but this bit doesn't exist yet.
//	That's where we need to be doing last post/channel flags/etc, not here.


	$serviceclass = (($_REQUEST['class']) ? " and account_service_class = '" . dbesc($_REQUEST['class']) . "' " : '');


	$order = " order by account_email asc ";
	if($_REQUEST['order'] === 'expires')
		$order = " order by account_expires desc ";

	$users =q("SELECT `account_id` , `account_email`, `account_lastlog`, `account_created`, `account_expires`, " . 			"`account_service_class`, ( account_flags & %d ) > 0 as `blocked`, " .
			"(SELECT GROUP_CONCAT( ch.channel_address SEPARATOR ' ') FROM channel as ch " .
			"WHERE ch.channel_account_id = ac.account_id) as `channels` " .
		"FROM account as ac where true $serviceclass $order limit %d , %d ",
		intval(ACCOUNT_BLOCKED),		
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);
					
//	function _setup_users($e){
//		$accounts = Array(
//			t('Normal Account'), 
//			t('Soapbox Account'),
//			t('Community/Celebrity Account'),
//			t('Automatic Friend Account')
//		);

//		$e['page_flags'] = $accounts[$e['page-flags']];
//		$e['register_date'] = relative_date($e['register_date']);
//		$e['login_date'] = relative_date($e['login_date']);
//		$e['lastitem_date'] = relative_date($e['lastitem_date']);
//		return $e;
//	}
//	$users = array_map("_setup_users", $users);
	
	
	$t = get_markup_template("admin_users.tpl");
	$o = replace_macros($t, array(
		// strings //
		'$title' => t('Administration'),
		'$page' => t('Users'),
		'$submit' => t('Submit'),
		'$select_all' => t('select all'),
		'$h_pending' => t('User registrations waiting for confirm'),
		'$th_pending' => array( t('Request date'), t('Email') ),
		'$no_pending' =>  t('No registrations.'),
		'$approve' => t('Approve'),
		'$deny' => t('Deny'),
		'$delete' => t('Delete'),
		'$block' => t('Block'),
		'$unblock' => t('Unblock'),

		'$h_users' => t('Users'),
		'$th_users' => array( t('ID'), t('Email'), t('All Channels'), t('Register date'), t('Last login'), t('Expires'), t('Service Class')),

		'$confirm_delete_multi' => t('Selected users will be deleted!\n\nEverything these users had posted on this site will be permanently deleted!\n\nAre you sure?'),
		'$confirm_delete' => t('The user {0} will be deleted!\n\nEverything this user has posted on this site will be permanently deleted!\n\nAre you sure?'),

        '$form_security_token' => get_form_security_token("admin_users"),

		// values //
		'$baseurl' => $a->get_baseurl(true),

		'$pending' => $pending,
		'$users' => $users,
	));
	$o .= paginate($a);
	return $o;
}


/**
 * Plugins admin page
 *
 * @param App $a
 * @return string
 */
function admin_page_plugins(&$a){
	
	/**
	 * Single plugin
	 */
	if ($a->argc == 3){
		$plugin = $a->argv[2];
		if (!is_file("addon/$plugin/$plugin.php")){
			notice( t("Item not found.") );
			return '';
		}
		
		if (x($_GET,"a") && $_GET['a']=="t"){
            check_form_security_token_redirectOnErr('/admin/plugins', 'admin_themes', 't');

			// Toggle plugin status
			$idx = array_search($plugin, $a->plugins);
			if ($idx !== false){
				unset($a->plugins[$idx]);
				uninstall_plugin($plugin);
				info( sprintf( t("Plugin %s disabled."), $plugin ) );
			} else {
				$a->plugins[] = $plugin;
				install_plugin($plugin);
				info( sprintf( t("Plugin %s enabled."), $plugin ) );
			}
			set_config("system","addon", implode(", ",$a->plugins));
			goaway($a->get_baseurl(true) . '/admin/plugins' );
			return ''; // NOTREACHED
		}
		// display plugin details
		require_once('library/markdown.php');

		if (in_array($plugin, $a->plugins)){
			$status="on"; $action= t("Disable");
		} else {
			$status="off"; $action= t("Enable");
		}
		
		$readme=Null;
		if (is_file("addon/$plugin/README.md")){
			$readme = file_get_contents("addon/$plugin/README.md");
			$readme = Markdown($readme);
		} else if (is_file("addon/$plugin/README")){
			$readme = "<pre>". file_get_contents("addon/$plugin/README") ."</pre>";
		} 
		
		$admin_form="";

		if (is_array($a->plugins_admin) && in_array($plugin, $a->plugins_admin)){
			@require_once("addon/$plugin/$plugin.php");
			if(function_exists($plugin.'_plugin_admin')) {
				$func = $plugin.'_plugin_admin';
				$func($a, $admin_form);
			}
		}


		$t = get_markup_template("admin_plugins_details.tpl");
		return replace_macros($t, array(
			'$title' => t('Administration'),
			'$page' => t('Plugins'),
			'$toggle' => t('Toggle'),
			'$settings' => t('Settings'),
			'$baseurl' => $a->get_baseurl(true),
		
			'$plugin' => $plugin,
			'$status' => $status,
			'$action' => $action,
			'$info' => get_plugin_info($plugin),
			'$str_author' => t('Author: '),
			'$str_maintainer' => t('Maintainer: '),			
		
			'$admin_form' => $admin_form,
			'$function' => 'plugins',
			'$screenshot' => '',
			'$readme' => $readme,

            '$form_security_token' => get_form_security_token("admin_themes"),
		));
	} 
	 
	 
	
	/**
	 * List plugins
	 */
	
	$plugins = array();
	$files = glob("addon/*/");
	if($files) {
		foreach($files as $file) {	
			if (is_dir($file)){
				list($tmp, $id)=array_map("trim", explode("/",$file));
				$info = get_plugin_info($id);
				$plugins[] = array( $id, (in_array($id,  $a->plugins)?"on":"off") , $info);
			}
		}
	}
	
	$t = get_markup_template("admin_plugins.tpl");
	return replace_macros($t, array(
		'$title' => t('Administration'),
		'$page' => t('Plugins'),
		'$submit' => t('Submit'),
		'$baseurl' => $a->get_baseurl(true),
		'$function' => 'plugins',	
		'$plugins' => $plugins,
        '$form_security_token' => get_form_security_token("admin_themes"),
	));
}

/**
 * @param array $themes
 * @param string $th
 * @param int $result
 */
function toggle_theme(&$themes,$th,&$result) {
	for($x = 0; $x < count($themes); $x ++) {
		if($themes[$x]['name'] === $th) {
			if($themes[$x]['allowed']) {
				$themes[$x]['allowed'] = 0;
				$result = 0;
			}
			else {
				$themes[$x]['allowed'] = 1;
				$result = 1;
			}
		}
	}
}

/**
 * @param array $themes
 * @param string $th
 * @return int
 */
function theme_status($themes,$th) {
	for($x = 0; $x < count($themes); $x ++) {
		if($themes[$x]['name'] === $th) {
			if($themes[$x]['allowed']) {
				return 1;
			}
			else {
				return 0;
			}
		}
	}
	return 0;
}


/**
 * @param array $themes
 * @return string
 */
function rebuild_theme_table($themes) {
	$o = '';
	if(count($themes)) {
		foreach($themes as $th) {
			if($th['allowed']) {
				if(strlen($o))
					$o .= ',';
				$o .= $th['name'];
			}
		}
	}
	return $o;
}

	
/**
 * Themes admin page
 *
 * @param App $a
 * @return string
 */
function admin_page_themes(&$a){
	
	$allowed_themes_str = get_config('system','allowed_themes');
	$allowed_themes_raw = explode(',',$allowed_themes_str);
	$allowed_themes = array();
	if(count($allowed_themes_raw))
		foreach($allowed_themes_raw as $x)
			if(strlen(trim($x)))
				$allowed_themes[] = trim($x);

	$themes = array();
    $files = glob('view/theme/*');
    if($files) {
        foreach($files as $file) {
            $f = basename($file);
            $is_experimental = intval(file_exists($file . '/.experimental'));
			$is_supported = 1-(intval(file_exists($file . '/.unsupported'))); // Is not used yet
			$is_allowed = intval(in_array($f,$allowed_themes));
			$themes[] = array('name' => $f, 'experimental' => $is_experimental, 'supported' => $is_supported, 'allowed' => $is_allowed);
        }
    }

	if(! count($themes)) {
		notice( t('No themes found.'));
		return '';
	}

	/**
	 * Single theme
	 */

	if ($a->argc == 3){
		$theme = $a->argv[2];
		if(! is_dir("view/theme/$theme")){
			notice( t("Item not found.") );
			return '';
		}
		
		if (x($_GET,"a") && $_GET['a']=="t"){
            check_form_security_token_redirectOnErr('/admin/themes', 'admin_themes', 't');

			// Toggle theme status

			toggle_theme($themes,$theme,$result);
			$s = rebuild_theme_table($themes);
			if($result)
				info( sprintf('Theme %s enabled.',$theme));
			else
				info( sprintf('Theme %s disabled.',$theme));

			set_config('system','allowed_themes',$s);
			goaway($a->get_baseurl(true) . '/admin/themes' );
			return ''; // NOTREACHED
		}

		// display theme details
		require_once('library/markdown.php');

		if (theme_status($themes,$theme)) {
			$status="on"; $action= t("Disable");
		} else {
			$status="off"; $action= t("Enable");
		}
		
		$readme=Null;
		if (is_file("view/theme/$theme/README.md")){
			$readme = file_get_contents("view/theme/$theme/README.md");
			$readme = Markdown($readme);
		} else if (is_file("view/theme/$theme/README")){
			$readme = "<pre>". file_get_contents("view/theme/$theme/README") ."</pre>";
		} 
		
		$admin_form="";
		if (is_file("view/theme/$theme/php/config.php")){
			require_once("view/theme/$theme/php/config.php");
			if(function_exists("theme_admin")){
				$admin_form = theme_admin($a);
			}
			
		}
		

		$screenshot = array( get_theme_screenshot($theme), t('Screenshot'));
		if(! stristr($screenshot[0],$theme))
			$screenshot = null;		

		$t = get_markup_template("admin_plugins_details.tpl");
		return replace_macros($t, array(
			'$title' => t('Administration'),
			'$page' => t('Themes'),
			'$toggle' => t('Toggle'),
			'$settings' => t('Settings'),
			'$baseurl' => $a->get_baseurl(true),
		
			'$plugin' => $theme,
			'$status' => $status,
			'$action' => $action,
			'$info' => get_theme_info($theme),
			'$function' => 'themes',
			'$admin_form' => $admin_form,
			'$str_author' => t('Author: '),
			'$str_maintainer' => t('Maintainer: '),
			'$screenshot' => $screenshot,
			'$readme' => $readme,

			'$form_security_token' => get_form_security_token("admin_themes"),
		));
	} 
	 
	 
	
	/**
	 * List themes
	 */
	
	$xthemes = array();
	if($themes) {
		foreach($themes as $th) {
			$xthemes[] = array($th['name'],(($th['allowed']) ? "on" : "off"), get_theme_info($th['name']));
		}
	}
	
	$t = get_markup_template("admin_plugins.tpl");
	return replace_macros($t, array(
		'$title' => t('Administration'),
		'$page' => t('Themes'),
		'$submit' => t('Submit'),
		'$baseurl' => $a->get_baseurl(true),
		'$function' => 'themes',
		'$plugins' => $xthemes,
		'$experimental' => t('[Experimental]'),
		'$unsupported' => t('[Unsupported]'),
        '$form_security_token' => get_form_security_token("admin_themes"),
	));
}


/**
 * Logs admin page
 *
 * @param App $a
 */
 
function admin_page_logs_post(&$a) {
	if (x($_POST,"page_logs")) {
        check_form_security_token_redirectOnErr('/admin/logs', 'admin_logs');

		$logfile 		=	((x($_POST,'logfile'))		? notags(trim($_POST['logfile']))	: '');
		$debugging		=	((x($_POST,'debugging'))	? true								: false);
		$loglevel 		=	((x($_POST,'loglevel'))		? intval(trim($_POST['loglevel']))	: 0);

		set_config('system','logfile', $logfile);
		set_config('system','debugging',  $debugging);
		set_config('system','loglevel', $loglevel);

		
	}

	info( t("Log settings updated.") );
	goaway($a->get_baseurl(true) . '/admin/logs' );
	return; // NOTREACHED	
}

/**
 * @param App $a
 * @return string
 */
function admin_page_logs(&$a){
	
	$log_choices = Array(
		LOGGER_NORMAL => 'Normal',
		LOGGER_TRACE => 'Trace',
		LOGGER_DEBUG => 'Debug',
		LOGGER_DATA => 'Data',
		LOGGER_ALL => 'All'
	);
	
	$t = get_markup_template("admin_logs.tpl");

	$f = get_config('system','logfile');

	$data = '';

	if(!file_exists($f)) {
		$data = t("Error trying to open <strong>$f</strong> log file.\r\n<br/>Check to see if file $f exist and is 
readable.");
	}
	else {
		$fp = fopen($f, 'r');
		if(!$fp) {
			$data = t("Couldn't open <strong>$f</strong> log file.\r\n<br/>Check to see if file $f is readable.");
		}
		else {
			$fstat = fstat($fp);
			$size = $fstat['size'];
			if($size != 0)
			{
				if($size > 5000000 || $size < 0)
					$size = 5000000;
				$seek = fseek($fp,0-$size,SEEK_END);
				if($seek === 0) {
					$data = escape_tags(fread($fp,$size));
					while(! feof($fp))
						$data .= escape_tags(fread($fp,4096));
				}
			}
			fclose($fp);
		}
	}			

	return replace_macros($t, array(
		'$title' => t('Administration'),
		'$page' => t('Logs'),
		'$submit' => t('Submit'),
		'$clear' => t('Clear'),
		'$data' => $data,
		'$baseurl' => $a->get_baseurl(true),
		'$logname' =>  get_config('system','logfile'),
		
									// name, label, value, help string, extra data...
		'$debugging' 		=> array('debugging', t("Debugging"),get_config('system','debugging'), ""),
		'$logfile'			=> array('logfile', t("Log file"), get_config('system','logfile'), t("Must be writable by web server. Relative to your Red top-level directory.")),
		'$loglevel' 		=> array('loglevel', t("Log level"), get_config('system','loglevel'), "", $log_choices),

        '$form_security_token' => get_form_security_token("admin_logs"),
	));
}

