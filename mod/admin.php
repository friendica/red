<?php
/**
 * @file mod/admin.php
 * @brief RedMatrix's admin controller.
 *
 * Controller for the /admin/ area.
 */


/**
 * @param App &$a
 */
function admin_post(&$a){
	logger('admin_post', LOGGER_DEBUG);

	if(!is_site_admin()) {
		return;
	}

	// urls
	if (argc() > 1) {
		switch (argv(1)) {
			case 'site':
				admin_page_site_post($a);
				break;
			case 'users':
				admin_page_users_post($a);
				break;
			case 'channels':
				admin_page_channels_post($a);
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
			case 'profs':
				admin_page_profs_post($a);
				break;
		}
	}

	goaway($a->get_baseurl(true) . '/admin' );
}

/**
 * @param App $$a
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
	$aside = array(
		'site'      => array($a->get_baseurl(true)."/admin/site/", t("Site") , "site"),
		'users'     => array($a->get_baseurl(true)."/admin/users/", t("Accounts") , "users"),
		'channels'  => array($a->get_baseurl(true)."/admin/channels/", t("Channels") , "channels"),
		'plugins'   => array($a->get_baseurl(true)."/admin/plugins/", t("Plugins") , "plugins"),
		'themes'    => array($a->get_baseurl(true)."/admin/themes/", t("Themes") , "themes"),
		'queue'     => array(z_root() . '/admin/queue', t('Inspect queue'), 'queue'),
//		'hubloc'    => array($a->get_baseurl(true)."/admin/hubloc/", t("Server") , "server"),
		'profs'     => array(z_root() . '/admin/profs', t('Profile Config'), 'profs'),
		'dbsync'    => array($a->get_baseurl(true)."/admin/dbsync/", t('DB updates'), "dbsync")
	);

	/* get plugins admin page */

	$r = q("SELECT * FROM addon WHERE plugin_admin = 1");
	$aside['plugins_admin'] = array();
	foreach ($r as $h){
		$plugin = $h['name'];
		$aside['plugins_admin'][] = array($a->get_baseurl(true) . '/admin/plugins/' . $plugin, $plugin, 'plugin');
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
			case 'channels':
				$o = admin_page_channels($a);
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
			case 'profs':
				$o = admin_page_profs($a);
				break;
			case 'queue':
				$o = admin_page_queue($a);
				break;
			default:
				notice( t('Item not found.') );
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
 * @brief Returns content for Admin Summary Page.
 *
 * @param App $$a
 * @return string HTML from parsed admin_summary.tpl
 */
function admin_page_summary(&$a) {

	// list total user accounts, expirations etc.
	$accounts = array();
	$r = q("SELECT COUNT(*) AS total, COUNT(IF(account_expires > %s, 1, NULL)) AS expiring, COUNT(IF(account_expires < %s AND account_expires != '%s', 1, NULL)) AS expired, COUNT(IF((account_flags & %d)>0, 1, NULL)) AS blocked FROM account",
		db_utcnow(),
		db_utcnow(),
		dbesc(NULL_DATE),
		intval(ACCOUNT_BLOCKED)
	);
	if ($r) {
		$accounts['total']    = array('label' => t('# Accounts'), 'val' => $r[0]['total']);
		$accounts['blocked']  = array('label' => t('# blocked accounts'), 'val' => $r[0]['blocked']);
		$accounts['expired']  = array('label' => t('# expired accounts'), 'val' => $r[0]['expired']);
		$accounts['expiring'] = array('label' => t('# expiring accounts'), 'val' => $r[0]['expiring']);
	}

	// pending registrations
	$r = q("SELECT COUNT(id) AS `count` FROM register");
	$pending = $r[0]['count'];

	// available channels, primary and clones
	$channels = array();
	$r = q("SELECT COUNT(*) AS total, COUNT(IF(channel_primary = 1, 1, NULL)) AS main, COUNT(IF(channel_primary = 0, 1, NULL)) AS clones FROM channel WHERE NOT (channel_pageflags & %d)>0",
		intval(PAGE_REMOVED)
	);
	if ($r) {
		$channels['total']  = array('label' => t('# Channels'), 'val' => $r[0]['total']);
		$channels['main']   = array('label' => t('# primary'), 'val' => $r[0]['main']);
		$channels['clones'] = array('label' => t('# clones'), 'val' => $r[0]['clones']);
	}

	// We can do better, but this is a quick queue status
	$r = q("SELECT COUNT(outq_delivered) AS total FROM outq WHERE outq_delivered = 0");
	$queue = (($r) ? $r[0]['total'] : 0);
	$queues = array( 'label' => t('Message queues'), 'queue' => $queue );

	// If no plugins active return 0, otherwise list of plugin names
	$plugins = (count($a->plugins) == 0) ? count($a->plugins) : $a->plugins;

	// Could be extended to provide also other alerts to the admin
	$alertmsg = '';
	// annoy admin about upcoming unsupported PHP version
	if (version_compare(PHP_VERSION, '5.4', '<')) {
		$alertmsg = 'Your PHP version ' . PHP_VERSION . ' will not be supported with the next major release of RedMatrix. You are strongly urged to upgrade to a current version.'
			. '<br>PHP 5.3 has reached its <a href="http://php.net/eol.php" class="alert-link">End of Life (EOL)</a> in August 2014.'
			. ' A list about current PHP versions can be found <a href="http://php.net/supported-versions.php" class="alert-link">here</a>.';
	}

	$t = get_markup_template('admin_summary.tpl');
	return replace_macros($t, array(
		'$title' => t('Administration'),
		'$page' => t('Summary'),
		'$adminalertmsg' => $alertmsg,
		'$queues' => $queues,
		'$accounts' => array( t('Registered accounts'), $accounts),
		'$pending' => array( t('Pending registrations'), $pending),
		'$channels' => array( t('Registered channels'), $channels),
		'$plugins' => array( t('Active plugins'), $plugins ),
		'$version' => array( t('Version'), RED_VERSION),
		'$build' => get_config('system', 'db_version')
	));
}


/**
 * Admin Site Page
 *  @param App $a
 */
function admin_page_site_post(&$a){
	if (!x($_POST, 'page_site')){
		return;
	}

	check_form_security_token_redirectOnErr('/admin/site', 'admin_site');

	$sitename 			=	((x($_POST,'sitename'))			? notags(trim($_POST['sitename']))			: '');
	$banner				=	((x($_POST,'banner'))      		? trim($_POST['banner'])				: false);
	$admininfo			=	((x($_POST,'admininfo'))		? trim($_POST['admininfo'])				: false);
	$language			=	((x($_POST,'language'))			? notags(trim($_POST['language']))			: '');
	$theme				=	((x($_POST,'theme'))			? notags(trim($_POST['theme']))				: '');
	$theme_mobile			=	((x($_POST,'theme_mobile'))		? notags(trim($_POST['theme_mobile']))			: '');
//	$site_channel			=	((x($_POST,'site_channel'))	? notags(trim($_POST['site_channel']))				: '');
	$maximagesize		=	((x($_POST,'maximagesize'))		? intval(trim($_POST['maximagesize']))				:  0);

	$register_policy	=	((x($_POST,'register_policy'))	? intval(trim($_POST['register_policy']))	:  0);
	$access_policy	=	((x($_POST,'access_policy'))	? intval(trim($_POST['access_policy']))	:  0);
	$abandon_days	    =	((x($_POST,'abandon_days'))	    ? intval(trim($_POST['abandon_days']))	    :  0);

	$register_text		=	((x($_POST,'register_text'))	? notags(trim($_POST['register_text']))		: '');

	$allowed_sites		=	((x($_POST,'allowed_sites'))	? notags(trim($_POST['allowed_sites']))		: '');
	$allowed_email		=	((x($_POST,'allowed_email'))	? notags(trim($_POST['allowed_email']))		: '');
	$not_allowed_email		=	((x($_POST,'not_allowed_email'))	? notags(trim($_POST['not_allowed_email']))		: '');
	$block_public		=	((x($_POST,'block_public'))		? True	:	False);
	$force_publish		=	((x($_POST,'publish_all'))		? True	:	False);
	$disable_discover_tab		=	((x($_POST,'disable_discover_tab'))		? True	:	False);
	$no_login_on_homepage	=	((x($_POST,'no_login_on_homepage'))		? True	:	False);
	$global_directory	= ((x($_POST,'directory_submit_url'))	? notags(trim($_POST['directory_submit_url']))	: '');
	$no_community_page	= !((x($_POST,'no_community_page'))	? True	:	False);

	$verifyssl         = ((x($_POST,'verifyssl'))        ? True : False);
	$proxyuser         = ((x($_POST,'proxyuser'))        ? notags(trim($_POST['proxyuser']))  : '');
	$proxy             = ((x($_POST,'proxy'))            ? notags(trim($_POST['proxy']))      : '');
	$timeout           = ((x($_POST,'timeout'))          ? intval(trim($_POST['timeout']))    : 60);
	$delivery_interval = ((x($_POST,'delivery_interval'))? intval(trim($_POST['delivery_interval'])) : 0);
	$poll_interval     = ((x($_POST,'poll_interval'))    ? intval(trim($_POST['poll_interval'])) : 0);
	$maxloadavg        = ((x($_POST,'maxloadavg'))       ? intval(trim($_POST['maxloadavg'])) : 50);
	$feed_contacts     = ((x($_POST,'feed_contacts'))    ? intval($_POST['feed_contacts'])    : 0);
	$diaspora_enabled  = ((x($_POST,'diaspora_enabled')) ? intval($_POST['diaspora_enabled']) : 0);
	$verify_email      = ((x($_POST,'verify_email'))     ? 1 : 0);

	set_config('system', 'feed_contacts', $feed_contacts);
	set_config('system', 'diaspora_enabled', $diaspora_enabled);
	set_config('system', 'delivery_interval', $delivery_interval);
	set_config('system', 'poll_interval', $poll_interval);
	set_config('system', 'maxloadavg', $maxloadavg);
	set_config('system', 'sitename', $sitename);
	set_config('system', 'no_login_on_homepage', $no_login_on_homepage);
	set_config('system', 'verify_email', $verify_email);

	if ($banner == '') {
		del_config('system', 'banner');
	} else {
		set_config('system', 'banner', $banner);
	}

	if ($admininfo == ''){
		del_config('system', 'admininfo');
	} else {
		require_once('include/text.php');
		linkify_tags($a, $admininfo, local_channel());
		set_config('system', 'admininfo', $admininfo);
	}
	set_config('system', 'language', $language);
	set_config('system', 'theme', $theme);
	if ( $theme_mobile === '---' ) {
		del_config('system', 'mobile_theme');
	} else {
		set_config('system', 'mobile_theme', $theme_mobile);
	}
//	set_config('system','site_channel', $site_channel);
	set_config('system','maximagesize', $maximagesize);

	set_config('system','register_policy', $register_policy);
	set_config('system','access_policy', $access_policy);
	set_config('system','account_abandon_days', $abandon_days);
	set_config('system','register_text', $register_text);
	set_config('system','allowed_sites', $allowed_sites);
	set_config('system','allowed_email', $allowed_email);
	set_config('system','not_allowed_email', $not_allowed_email);	
	set_config('system','block_public', $block_public);
	set_config('system','publish_all', $force_publish);
	set_config('system','disable_discover_tab', $disable_discover_tab);
	if ($global_directory == '') {
		del_config('system', 'directory_submit_url');
	} else {
		set_config('system', 'directory_submit_url', $global_directory);
	}

	set_config('system','no_community_page', $no_community_page);
	set_config('system','no_utf', $no_utf);
	set_config('system','verifyssl', $verifyssl);
	set_config('system','proxyuser', $proxyuser);
	set_config('system','proxy', $proxy);
	set_config('system','curl_timeout', $timeout);

	info( t('Site settings updated.') . EOL);
	goaway($a->get_baseurl(true) . '/admin/site' );
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
	$theme_choices_mobile["---"] = t("Default");
	$theme_choices = array();
	$files = glob('view/theme/*');
	if($files) {
		foreach($files as $file) {
			$vars = '';
			$f = basename($file);
			if (file_exists($file . '/library'))
				continue;
			if (file_exists($file . '/mobile'))
				$vars = t('mobile');
			if (file_exists($file . '/experimental'))
				$vars .= t('experimental');
			if (file_exists($file . '/unsupported'))
				$vars .= t('unsupported');
			if ($vars) {
				$theme_choices[$f] = $f . ' (' . $vars . ')';
				$theme_choices_mobile[$f] = $f . ' (' . $vars . ')';
			}
			else {
				$theme_choices[$f] = $f;
				$theme_choices_mobile[$f] = $f;
			}
		}
	}

	/* Banner */
	$banner = get_config('system', 'banner');
	if($banner == false) 
		$banner = 'red';

	$banner = htmlspecialchars($banner);

	/* Admin Info */
	$admininfo = get_config('system', 'admininfo');

	/* Register policy */
	$register_choices = Array(
		REGISTER_CLOSED  => t("No"),
		REGISTER_APPROVE => t("Yes - with approval"),
		REGISTER_OPEN    => t("Yes")
	);

	/* Acess policy */
	$access_choices = Array(
		ACCESS_PRIVATE => t("My site is not a public server"),
		ACCESS_PAID => t("My site has paid access only"),
		ACCESS_FREE => t("My site has free access only"),
		ACCESS_TIERED => t("My site offers free accounts with optional paid upgrades")
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
//		'$site_channel' 	=> array('site_channel', t("Channel to use for this website's static pages"), get_config('system','site_channel'), t("Site Channel")),
		'$diaspora_enabled'  => array('diaspora_enabled',t('Enable Diaspora Protocol'), get_config('system','diaspora_enabled'), t('Communicate with Diaspora and Friendica - experimental')),
		'$feed_contacts'    => array('feed_contacts', t('Allow Feeds as Connections'),get_config('system','feed_contacts'),t('(Heavy system resource usage)')), 
		'$maximagesize'		=> array('maximagesize', t("Maximum image size"), intval(get_config('system','maximagesize')), t("Maximum size in bytes of uploaded images. Default is 0, which means no limits.")),
		'$register_policy'	=> array('register_policy', t("Does this site allow new member registration?"), get_config('system','register_policy'), "", $register_choices),
		'$access_policy'	=> array('access_policy', t("Which best describes the types of account offered by this hub?"), get_config('system','access_policy'), "This is displayed on the public server site list.", $access_choices),
		'$register_text'	=> array('register_text', t("Register text"), htmlspecialchars(get_config('system','register_text'), ENT_QUOTES, 'UTF-8'), t("Will be displayed prominently on the registration page.")),
		'$abandon_days'     => array('abandon_days', t('Accounts abandoned after x days'), get_config('system','account_abandon_days'), t('Will not waste system resources polling external sites for abandonded accounts. Enter 0 for no time limit.')),
		'$allowed_sites'	=> array('allowed_sites', t("Allowed friend domains"), get_config('system','allowed_sites'), t("Comma separated list of domains which are allowed to establish friendships with this site. Wildcards are accepted. Empty to allow any domains")),
		'$allowed_email'	=> array('allowed_email', t("Allowed email domains"), get_config('system','allowed_email'), t("Comma separated list of domains which are allowed in email addresses for registrations to this site. Wildcards are accepted. Empty to allow any domains")),
		'$not_allowed_email'	=> array('not_allowed_email', t("Not allowed email domains"), get_config('system','not_allowed_email'), t("Comma separated list of domains which are not allowed in email addresses for registrations to this site. Wildcards are accepted. Empty to allow any domains, unless allowed domains have been defined.")),
		'$block_public'		=> array('block_public', t("Block public"), get_config('system','block_public'), t("Check to block public access to all otherwise public personal pages on this site unless you are currently logged in.")),
		'$verify_email'		=> array('verify_email', t("Verify Email Addresses"), get_config('system','verify_email'), t("Check to verify email addresses used in account registration (recommended).")),
		'$force_publish'	=> array('publish_all', t("Force publish"), get_config('system','publish_all'), t("Check to force all profiles on this site to be listed in the site directory.")),
		'$disable_discover_tab'	=> array('disable_discover_tab', t("Disable discovery tab"), get_config('system','disable_discover_tab'), t("Remove the tab in the network view with public content pulled from sources chosen for this site.")),
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

		//perform ping
		$m = zot_build_packet($a->get_channel(),'ping');
		$r = zot_zot($hublocurl,$m);
		//handle results and set the hubloc flags in db to make results visible
		$r2 = $r['body'];
		$r3 = $r2['success'];
		if ( $r3['success'] == True ){
			//set HUBLOC_OFFLINE to 0
			logger(' success = true ',LOGGER_DEBUG);
		} else {
			//set HUBLOC_OFFLINE to 1 
			logger(' success = false ', LOGGER_DEBUG);
		}

		//unfotunatly zping wont work, I guess return format is not correct
		//require_once('mod/zping.php');
		//$r = zping_content($hublocurl);
		//logger('zping answer: ' . $r, LOGGER_DEBUG);

		//in case of repair store new pub key for tested hubloc (all channel with this hubloc) in db
		//after repair set hubloc flags to 0
	}

	goaway($a->get_baseurl(true) . '/admin/hubloc' );
}

function admin_page_hubloc(&$a) {
	$hubloc = q("SELECT hubloc_id, hubloc_addr, hubloc_host, hubloc_status  FROM hubloc");

	if(! $hubloc){
		notice( t('No server found') . EOL);
		goaway($a->get_baseurl(true) . '/admin/hubloc');
	}

	$t = get_markup_template('admin_hubloc.tpl');
	return replace_macros($t, array(
		'$hubloc' => $hubloc,
		'$th_hubloc' => array(t('ID'), t('for channel'), t('on server'), t('Status')),
		'$title' => t('Administration'),
		'$page' => t('Server'),
		'$queues' => $queues,
		//'$accounts' => $accounts, /*$accounts is empty here*/
		'$pending' => array( t('Pending registrations'), $pending),
		'$plugins' => array( t('Active plugins'), $a->plugins ),
		'$form_security_token' => get_form_security_token('admin_hubloc')
	));
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
				$o .= sprintf( t('Update %s was successfully applied.'), $func);
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
			if($rr['v'] === 'success')
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

function admin_page_queue($a) {
	$o = '';

	$expert = ((array_key_exists('expert',$_REQUEST)) ? intval($_REQUEST['expert']) : 0);

	if($_REQUEST['drophub']) {
		require_once('hubloc.php');
		hubloc_mark_as_down($_REQUEST['drophub']);
	}

	if($_REQUEST['emptyhub']) {
		$r = q("delete from outq where outq_posturl = '%s' ",
			dbesc($_REQUEST['emptyhub'])
		);
	}


	$r = q("select count(outq_posturl) as total, outq_posturl from outq 
		where outq_delivered = 0 group by outq_posturl order by total desc");

	for($x = 0; $x < count($r); $x ++) {
		$r[$x]['eurl'] = urlencode($r[$x]['outq_posturl']);
		$r[$x]['connected'] = datetime_convert('UTC',date_default_timezone_get(),$r[$x]['connected'],'Y-m-d');
	}


	$o = replace_macros(get_markup_template('admin_queue.tpl'), array(
		'$banner' => t('Queue Statistics'),
		'$numentries' => t('Total Entries'),
		'$desturl' => t('Destination URL'),
		'$nukehub' => t('Mark hub permanently offline'),
		'$empty' => t('Empty queue for this hub'),
		'$lastconn' => t('Last known contact'),
		'$hasentries' => ((count($r)) ? true : false),
		'$entries' => $r,
		'$expert' => $expert
	));

	return $o;
}

/**
 * @brief Handle POST actions on users admin page.
 *
 * This function is called when on the admin user/account page the form was
 * submitted to handle multiple operations at once. If one of the icons next
 * to an entry are pressed the function admin_page_users() will handle this.
 *
 * @param App $a
 */
function admin_page_users_post($a) {
	$pending = ( x($_POST, 'pending') ? $_POST['pending'] : array() );
	$users   = ( x($_POST, 'user')    ? $_POST['user']    : array() );
	$blocked = ( x($_POST, 'blocked') ? $_POST['blocked'] : array() );

	check_form_security_token_redirectOnErr('/admin/users', 'admin_users');

	// change to switch structure?
	// account block/unblock button was submitted
	if (x($_POST, 'page_users_block')) {
		for ($i = 0; $i < count($users); $i++) {
			// if account is blocked remove blocked bit-flag, otherwise add blocked bit-flag
			$op = ($blocked[$i]) ? '& ~' : '| ';
			q("UPDATE account SET account_flags = (account_flags $op%d) WHERE account_id = %d",
				intval(ACCOUNT_BLOCKED),
				intval($users[$i])
			);
		}
		notice( sprintf( tt("%s user blocked/unblocked", "%s users blocked/unblocked", count($users)), count($users)) );
	}
	// account delete button was submitted
	if (x($_POST, 'page_users_delete')) {
		require_once('include/Contact.php');
		foreach ($users as $uid){
			account_remove($uid, true, false);
		}
		notice( sprintf( tt("%s user deleted", "%s users deleted", count($users)), count($users)) );
	}
	// registration approved button was submitted
	if (x($_POST, 'page_users_approve')) {
		foreach ($pending as $hash) {
			user_allow($hash);
		}
	}
	// registration deny button was submitted
	if (x($_POST, 'page_users_deny')) {
		foreach ($pending as $hash) {
			user_deny($hash);
		}
	}

	goaway($a->get_baseurl(true) . '/admin/users' );
}

/**
 * @brief Generate users admin page and handle single item operations.
 *
 * This function generates the users/account admin page and handles the actions
 * if an icon next to an entry was clicked. If several items were selected and
 * the form was submitted it is handled by the function admin_page_users_post().
 *
 * @param App &$a
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

		check_form_security_token_redirectOnErr('/admin/users', 'admin_users', 't');

		switch (argv(2)){
			case 'delete':
				// delete user
				require_once('include/Contact.php');
				account_remove($uid,true,false);

				notice( sprintf(t("User '%s' deleted"), $account[0]['account_email']) . EOL);
				break;
			case 'block':
				q("UPDATE account SET account_flags = ( account_flags | %d ) WHERE account_id = %d",
					intval(ACCOUNT_BLOCKED),
					intval($uid)
				);

				notice( sprintf( t("User '%s' blocked") , $account[0]['account_email']) . EOL);
				break;
			case 'unblock':
				q("UPDATE account SET account_flags = ( account_flags & ~%d ) WHERE account_id = %d",
						intval(ACCOUNT_BLOCKED),
						intval($uid)
				);

				notice( sprintf( t("User '%s' unblocked"), $account[0]['account_email']) . EOL);
				break;
		}

		goaway($a->get_baseurl(true) . '/admin/users' );
	}

	/* get pending */
	$pending = q("SELECT account.*, register.hash from account left join register on account_id = register.uid where (account_flags & %d )>0 ",
		intval(ACCOUNT_PENDING)
	);

	/* get users */

	$total = q("SELECT count(*) as total FROM account");
	if (count($total)) {
		$a->set_pager_total($total[0]['total']);
		$a->set_pager_itemspage(100);
	}


//	WEe'll still need to link email addresses to admin/users/channels or some such, but this bit doesn't exist yet.
//	That's where we need to be doing last post/channel flags/etc, not here.


	$serviceclass = (($_REQUEST['class']) ? " and account_service_class = '" . dbesc($_REQUEST['class']) . "' " : '');


	$order = " order by account_email asc ";
	if($_REQUEST['order'] === 'expires')
		$order = " order by account_expires desc ";
	if($_REQUEST['order'] === 'created')
		$order = " order by account_created desc ";

	$users = q("SELECT `account_id` , `account_email`, `account_lastlog`, `account_created`, `account_expires`, " . 			"`account_service_class`, ( account_flags & %d )>0 as `blocked`, " .
			"(SELECT %s FROM channel as ch " .
			"WHERE ch.channel_account_id = ac.account_id and not (ch.channel_pageflags & %d )>0) as `channels` " .
		"FROM account as ac where true $serviceclass $order limit %d offset %d ",
		intval(ACCOUNT_BLOCKED),
		db_concat('ch.channel_address', ' '),
		intval(PAGE_REMOVED),
		intval($a->pager['itemspage']),
		intval($a->pager['start'])
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


	$t = get_markup_template('admin_users.tpl');
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
 * Channels admin page
 *
 * @param App $a
 */
function admin_page_channels_post(&$a) {
	$channels = ( x($_POST, 'channel') ? $_POST['channel'] : Array() );

	check_form_security_token_redirectOnErr('/admin/channels', 'admin_channels');

	if (x($_POST,'page_channels_block')){
		foreach($channels as $uid){
			q("UPDATE channel SET channel_pageflags = ( channel_pageflags & ~%d ) where channel_id = %d",
				intval(PAGE_CENSORED),
				intval( $uid )
			);
			proc_run('php','include/directory.php',$uid,'nopush');
		}
		notice( sprintf( tt("%s channel censored/uncensored", "%s channels censored/uncensored", count($channels)), count($channels)) );
	}
	if (x($_POST,'page_channels_delete')){
		require_once("include/Contact.php");
		foreach($channels as $uid){
			channel_remove($uid,true);
		}
		notice( sprintf( tt("%s channel deleted", "%s channels deleted", count($channels)), count($channels)) );
	}

	goaway($a->get_baseurl(true) . '/admin/channels' );
}

/**
 * @param App $a
 * @return string
 */
function admin_page_channels(&$a){
	if (argc() > 2) {
		$uid = argv(3);
		$channel = q("SELECT * FROM channel WHERE channel_id = %d",
			intval($uid)
		);

		if (! $channel) {
			notice( t('Channel not found') . EOL);
			goaway($a->get_baseurl(true) . '/admin/channels' );
		}

		switch(argv(2)) {
			case "delete":{
				check_form_security_token_redirectOnErr('/admin/channels', 'admin_channels', 't');
				// delete channel
				require_once("include/Contact.php");
				channel_remove($uid,true);
				
				notice( sprintf(t("Channel '%s' deleted"), $channel[0]['channel_name']) . EOL);
			}; break;

			case "block":{
				check_form_security_token_redirectOnErr('/admin/channels', 'admin_channels', 't');
				q("UPDATE channel SET channel_pageflags = ( channel_pageflags & ~%d ) where channel_id = %d",
					intval(PAGE_CENSORED),
					intval( $uid )
				);
				proc_run('php','include/directory.php',$uid,'nopush');

				notice( sprintf( (($channel[0]['channel_pageflags'] & PAGE_CENSORED) ? t("Channel '%s' uncensored"): t("Channel '%s' censored")) , $channel[0]['channel_name'] . ' (' . $channel[0]['channel_address'] . ')' ) . EOL);
			}; break;
		}
		goaway($a->get_baseurl(true) . '/admin/channels' );
	}

	/* get channels */

	$total = q("SELECT count(*) as total FROM channel where not (channel_pageflags & %d)>0",
		intval(PAGE_REMOVED|PAGE_SYSTEM)
	);
	if($total) {
		$a->set_pager_total($total[0]['total']);
		$a->set_pager_itemspage(100);
	}

	$order = " order by channel_name asc ";

	$channels = q("SELECT * from channel where not ( channel_pageflags & %d )>0 $order limit %d offset %d ",
		intval(PAGE_REMOVED|PAGE_SYSTEM),
		intval($a->pager['itemspage']),
		intval($a->pager['start'])
	);

	if($channels) {
		for($x = 0; $x < count($channels); $x ++) {
			if($channels[$x]['channel_pageflags'] & PAGE_CENSORED)
				$channels[$x]['blocked'] = true;
			else
				$channels[$x]['blocked'] = false;
		}
	}

	$t = get_markup_template("admin_channels.tpl");
	$o = replace_macros($t, array(
		// strings //
		'$title' => t('Administration'),
		'$page' => t('Channels'),
		'$submit' => t('Submit'),
		'$select_all' => t('select all'),
		'$delete' => t('Delete'),
		'$block' => t('Censor'),
		'$unblock' => t('Uncensor'),

		'$h_channels' => t('Channel'),
		'$th_channels' => array( t('UID'), t('Name'), t('Address')),

		'$confirm_delete_multi' => t('Selected channels will be deleted!\n\nEverything that was posted in these channels on this site will be permanently deleted!\n\nAre you sure?'),
		'$confirm_delete' => t('The channel {0} will be deleted!\n\nEverything that was posted in this channel on this site will be permanently deleted!\n\nAre you sure?'),

		'$form_security_token' => get_form_security_token("admin_channels"),

		// values //
		'$baseurl' => $a->get_baseurl(true),
		'$channels' => $channels,
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
			check_form_security_token_redirectOnErr('/admin/plugins', 'admin_plugins', 't');

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
		}
		// display plugin details
		require_once('library/markdown.php');

		if (in_array($plugin, $a->plugins)){
			$status = 'on';
			$action = t('Disable');
		} else {
			$status = 'off';
			$action =  t('Enable');
		}

		$readme = null;
		if (is_file("addon/$plugin/README.md")){
			$readme = file_get_contents("addon/$plugin/README.md");
			$readme = Markdown($readme);
		} else if (is_file("addon/$plugin/README")){
			$readme = "<pre>". file_get_contents("addon/$plugin/README") ."</pre>";
		}

		$admin_form = '';
		if (is_array($a->plugins_admin) && in_array($plugin, $a->plugins_admin)){
			@require_once("addon/$plugin/$plugin.php");
			if(function_exists($plugin.'_plugin_admin')) {
				$func = $plugin.'_plugin_admin';
				$func($a, $admin_form);
			}
		}

		$t = get_markup_template('admin_plugins_details.tpl');
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

			'$form_security_token' => get_form_security_token('admin_plugins'),
		));
	}


	/**
	 * List plugins
	 */
	$plugins = array();
	$files = glob('addon/*/');
	if($files) {
		foreach($files as $file) {
			if (is_dir($file)){
				list($tmp, $id) = array_map('trim', explode('/', $file));
				$info = get_plugin_info($id);
				$plugins[] = array( $id, (in_array($id,  $a->plugins)?"on":"off") , $info);
			}
		}
	}

	$t = get_markup_template('admin_plugins.tpl');
	return replace_macros($t, array(
		'$title' => t('Administration'),
		'$page' => t('Plugins'),
		'$submit' => t('Submit'),
		'$baseurl' => $a->get_baseurl(true),
		'$function' => 'plugins',
		'$plugins' => $plugins,
		'$form_security_token' => get_form_security_token('admin_plugins'),
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
function theme_status($themes, $th) {
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

	$allowed_themes_str = get_config('system', 'allowed_themes');
	$allowed_themes_raw = explode(',', $allowed_themes_str);
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

			toggle_theme($themes, $theme, $result);
			$s = rebuild_theme_table($themes);
			if($result)
				info( sprintf('Theme %s enabled.', $theme));
			else
				info( sprintf('Theme %s disabled.', $theme));

			set_config('system', 'allowed_themes', $s);
			goaway($a->get_baseurl(true) . '/admin/themes' );
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

		$admin_form = '';
		if (is_file("view/theme/$theme/php/config.php")){
			require_once("view/theme/$theme/php/config.php");
			if(function_exists("theme_admin")){
				$admin_form = theme_admin($a);
			}
		}

		$screenshot = array( get_theme_screenshot($theme), t('Screenshot'));
		if(! stristr($screenshot[0],$theme))
			$screenshot = null;

		$t = get_markup_template('admin_plugins_details.tpl');
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

			'$form_security_token' => get_form_security_token('admin_themes'),
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

	$t = get_markup_template('admin_plugins.tpl');
	return replace_macros($t, array(
		'$title' => t('Administration'),
		'$page' => t('Themes'),
		'$submit' => t('Submit'),
		'$baseurl' => $a->get_baseurl(true),
		'$function' => 'themes',
		'$plugins' => $xthemes,
		'$experimental' => t('[Experimental]'),
		'$unsupported' => t('[Unsupported]'),
		'$form_security_token' => get_form_security_token('admin_themes'),
	));
}


/**
 * Logs admin page
 *
 * @param App $a
 */
function admin_page_logs_post(&$a) {
	if (x($_POST, 'page_logs')) {
		check_form_security_token_redirectOnErr('/admin/logs', 'admin_logs');

		$logfile   = ((x($_POST,'logfile'))   ? notags(trim($_POST['logfile'])) : '');
		$debugging = ((x($_POST,'debugging')) ? true : false);
		$loglevel  = ((x($_POST,'loglevel'))  ? intval(trim($_POST['loglevel'])) : 0);

		set_config('system','logfile', $logfile);
		set_config('system','debugging',  $debugging);
		set_config('system','loglevel', $loglevel);
	}

	info( t('Log settings updated.') );
	goaway($a->get_baseurl(true) . '/admin/logs' );
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

	$t = get_markup_template('admin_logs.tpl');

	$f = get_config('system', 'logfile');

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
		'$debugging' => array('debugging', t("Debugging"),get_config('system','debugging'), ""),
		'$logfile'   => array('logfile', t("Log file"), get_config('system','logfile'), t("Must be writable by web server. Relative to your Red top-level directory.")),
		'$loglevel'  => array('loglevel', t("Log level"), get_config('system','loglevel'), "", $log_choices),

		'$form_security_token' => get_form_security_token('admin_logs'),
	));
}

function admin_page_profs_post(&$a) {

	if($_REQUEST['id']) {
		$r = q("update profdef set field_name = '%s', field_type = '%s', field_desc = '%s' field_help = '%s', field_inputs = '%s' where id = %d",
			dbesc($_REQUEST['field_name']),
			dbesc($_REQUEST['field_type']),
			dbesc($_REQUEST['field_desc']),
			dbesc($_REQUEST['field_help']),
			dbesc($_REQUEST['field_inputs']),
			intval($_REQUEST['id'])
		);
	}
	else {
		$r = q("insert into profdef ( field_name, field_type, field_desc, field_help, field_inputs ) values ( '%s' , '%s', '%s', '%s', '%s' )",
			dbesc($_REQUEST['field_name']),
			dbesc($_REQUEST['field_type']),
			dbesc($_REQUEST['field_desc']),
			dbesc($_REQUEST['field_help']),
			dbesc($_REQUEST['field_inputs'])
		);
	}

	// add to chosen array basic or advanced

	goaway(z_root() . '/admin/profs');
}

function admin_page_profs(&$a) {

	if((argc() > 3) && argv(2) == 'drop' && intval(argv(3))) {
		$r = q("delete from profdef where id = %d",
			intval(argv(3))
		);
		// remove from allowed fields

		goaway(z_root() . '/admin/profs');	
	}

	if((argc() > 2) && argv(2) === 'new') {
		return replace_macros(get_markup_template('profdef_edit.tpl'),array(
			'$header' => t('New Profile Field'),
			'$field_name' => array('field_name',t('Field nickname'),$_REQUEST['field_name'],t('System name of field')),
			'$field_type' => array('field_type',t('Input type'),(($_REQUEST['field_type']) ? $_REQUEST['field_type'] : 'text'),''),
			'$field_desc' => array('field_desc',t('Field Name'),$_REQUEST['field_desc'],t('Label on profile pages')),
			'$field_help' => array('field_help',t('Help text'),$_REQUEST['field_help'],t('Additional info (optional)')),
			'$submit' => t('Save')
		));
	}

	if((argc() > 2) && intval(argv(2))) {
		$r = q("select * from profdef where id = %d limit 1",
			intval(argv(2))
		);
		if(! $r) {
			notice( t('Field definition not found') . EOL);
			goaway(z_root() . '/admin/profs');
		}

		return replace_macros(get_markup_template('profdef_edit.tpl'),array(
			'$id' => intval($r[0]['id']),
			'$header' => t('Edit Profile Field'),
			'$field_name' => array('field_name',t('Field nickname'),$r[0]['field_name'],t('System name of field')),
			'$field_type' => array('field_type',t('Input type'),$r[0]['field_type'],''),
			'$field_desc' => array('field_desc',t('Field Name'),$r[0]['field_desc'],t('Label on profile pages')),
			'$field_help' => array('field_help',t('Help text'),$r[0]['field_help'],t('Additional info (optional)')),
			'$submit' => t('Save')
		));
	}

}