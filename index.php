<?php /** @file */

/**
 *
 * Red Matrix
 *
 */

/**
 *
 * bootstrap the application
 *
 */

require_once('boot.php');

$a = new App;

/**
 *
 * Load the configuration file which contains our DB credentials.
 * Ignore errors. If the file doesn't exist or is empty, we are running in installation mode.
 *
 */

$install = ((file_exists('.htconfig.php') && filesize('.htconfig.php')) ? false : true);

@include(".htconfig.php");

$a->language = get_best_language();
	

/**
 *
 * Try to open the database;
 *
 */

require_once("include/dba/dba_driver.php");

if(! $install) {
	$db = dba_factory($db_host, $db_port, $db_user, $db_pass, $db_data, $install);
    	    unset($db_host, $db_port, $db_user, $db_pass, $db_data);

	/**
	 * Load configs from db. Overwrite configs from .htconfig.php
	 */

	load_config('config');
	load_config('system');
	load_config('feature');

	require_once("include/session.php");
	load_hooks();
	call_hooks('init_1');
	
	load_translation_table($a->language);
}
else {
	// load translations but do not check plugins as we have no database
	load_translation_table($a->language,true);
}


/**
 *
 * Important stuff we always need to do.
 *
 * The order of these may be important so use caution if you think they're all
 * intertwingled with no logical order and decide to sort it out. Some of the
 * dependencies have changed, but at least at one time in the recent past - the 
 * order was critical to everything working properly
 *
 */

session_start();

/**
 * Language was set earlier, but we can over-ride it in the session.
 * We have to do it here because the session was just now opened.
 */

if(array_key_exists('system_language',$_POST)) {
	if(strlen($_POST['system_language']))
		$_SESSION['language'] = $_POST['system_language'];
	else
		unset($_SESSION['language']);
}
if((x($_SESSION,'language')) && ($_SESSION['language'] !== $lang)) {
	$a->language = $_SESSION['language'];
	load_translation_table($a->language);
}

if((x($_GET,'zid')) && (! $install)) {
	$a->query_string = preg_replace('/[\?&]zid=(.*?)([\?&]|$)/is','',$a->query_string);
	if(! local_user()) {
		$_SESSION['my_address'] = $_GET['zid'];
		zid_init($a);
	}
}

if((x($_SESSION,'authenticated')) || (x($_POST,'auth-params')) || ($a->module === 'login'))
	require("include/auth.php");


if(! x($_SESSION,'sysmsg'))
	$_SESSION['sysmsg'] = array();

if(! x($_SESSION,'sysmsg_info'))
	$_SESSION['sysmsg_info'] = array();

/*
 * check_config() is responsible for running update scripts. These automatically 
 * update the DB schema whenever we push a new one out. It also checks to see if
 * any plugins have been added or removed and reacts accordingly. 
 */


if($install) {
	/* Allow an exception for the view module so that pcss will be interpreted during installation */
	if($a->module != 'view')
		$a->module = 'setup';
}
else
	check_config($a);

nav_set_selected('nothing');

$arr = array('app_menu' => $a->get_apps());

call_hooks('app_menu', $arr);

$a->set_apps($arr['app_menu']);

/**
 *
 * We have already parsed the server path into $a->argc and $a->argv
 *
 * $a->argv[0] is our module name. We will load the file mod/{$a->argv[0]}.php
 * and use it for handling our URL request.
 * The module file contains a few functions that we call in various circumstances
 * and in the following order:
 * 
 * "module"_init
 * "module"_post (only called if there are $_POST variables)
 * "module"_aside
 *       $theme_$module_aside (and $extends_$module_aside) are run first if either exist
 *       if either of these return false, module_aside is not called
 *           This allows a theme to over-ride the sidebar layout completely. 
 * "module"_content - the string return of this function contains our page body
 *
 * Modules which emit other serialisations besides HTML (XML,JSON, etc.) should do 
 * so within the module init and/or post functions and then invoke killme() to terminate
 * further processing.
 */

if(strlen($a->module)) {


	/**
	 *
	 * We will always have a module name.
	 * First see if we have a plugin which is masquerading as a module.
	 *
	 */

	if(is_array($a->plugins) && in_array($a->module,$a->plugins) && file_exists("addon/{$a->module}/{$a->module}.php")) {
		include_once("addon/{$a->module}/{$a->module}.php");
		if(function_exists($a->module . '_module'))
			$a->module_loaded = true;
	}


	if((strpos($a->module,'admin') === 0) && (! is_site_admin())) {
		$a->module_loaded = false;
		notice( t('Permission denied.') . EOL);
		goaway(z_root());
	}

	/**
	 * If the site has a custom module to over-ride the standard module, use it.
	 * Otherwise, look for the standard program module in the 'mod' directory
	 */


	if(! $a->module_loaded) {
		if(file_exists("custom/{$a->module}.php")) {
			include_once("custom/{$a->module}.php");
			$a->module_loaded = true;
		}
		elseif(file_exists("mod/{$a->module}.php")) {
			include_once("mod/{$a->module}.php");
			$a->module_loaded = true;
		}
	}


	/**
	 *
	 * The URL provided does not resolve to a valid module.
	 *
	 * On Dreamhost sites, quite often things go wrong for no apparent reason and they send us to '/internal_error.html'. 
	 * We don't like doing this, but as it occasionally accounts for 10-20% or more of all site traffic - 
	 * we are going to trap this and redirect back to the requested page. As long as you don't have a critical error on your page
	 * this will often succeed and eventually do the right thing.
	 *
	 * Otherwise we are going to emit a 404 not found.
	 *
	 */

	if(! $a->module_loaded) {

		// Stupid browser tried to pre-fetch our Javascript img template. Don't log the event or return anything - just quietly exit.
		if((x($_SERVER,'QUERY_STRING')) && preg_match('/{[0-9]}/',$_SERVER['QUERY_STRING']) !== 0) {
			killme();
		}

		if((x($_SERVER,'QUERY_STRING')) && ($_SERVER['QUERY_STRING'] === 'q=internal_error.html') && isset($dreamhost_error_hack)) {
			logger('index.php: dreamhost_error_hack invoked. Original URI =' . $_SERVER['REQUEST_URI']);
			goaway($a->get_baseurl() . $_SERVER['REQUEST_URI']);
		}

		logger('index.php: page not found: ' . $_SERVER['REQUEST_URI'] . ' ADDRESS: ' . $_SERVER['REMOTE_ADDR'] . ' QUERY: ' . $_SERVER['QUERY_STRING'], LOGGER_DEBUG);
		header($_SERVER["SERVER_PROTOCOL"] . ' 404 ' . t('Not Found'));
		$tpl = get_markup_template("404.tpl");
		$a->page['content'] = replace_macros($tpl, array(
			'$message' =>  t('Page not found.' )
		));
	}
}

/**
 * load current theme info
 */
$theme_info_file = "view/theme/".current_theme()."/php/theme.php";
if (file_exists($theme_info_file)){
	require_once($theme_info_file);
}


/* initialise content region */

if(! x($a->page,'content'))
	$a->page['content'] = '';

/* set JS cookie */
if($_COOKIE['jsAvailable'] != 1) {
	$a->page['content'] .= '<script>document.cookie="jsAvailable=1; path=/"; var jsMatch = /\&JS=1/; if (!jsMatch.exec(location.href)) { location.href = location.href + "&JS=1"; }</script>';
	/* emulate JS cookie if cookies are not accepted */
	if ($_GET['JS'] == 1) {
		$_COOKIE['jsAvailable'] = 1;
	}
}


if(! $install)
	call_hooks('page_content_top',$a->page['content']);

/**
 * Call module functions
 */

if($a->module_loaded) {
	$a->page['page_title'] = $a->module;
	$placeholder = '';

	if(function_exists($a->module . '_init')) {
		call_hooks($a->module . '_mod_init', $placeholder);
		$func = $a->module . '_init';
		$func($a);
	}

	if(function_exists(str_replace('-','_',current_theme()) . '_init')) {
		$func = str_replace('-','_',current_theme()) . '_init';
		$func($a);
	}
	elseif (x($a->theme_info,"extends") && file_exists("view/theme/".$a->theme_info["extends"]."/php/theme.php")) {
		require_once("view/theme/".$a->theme_info["extends"]."/php/theme.php");
		if(function_exists(str_replace('-','_',$a->theme_info["extends"]) . '_init')) {
			$func = str_replace('-','_',$a->theme_info["extends"]) . '_init';
			$func($a);
		}
	}

	if(($_SERVER['REQUEST_METHOD'] === 'POST') && (! $a->error)
		&& (function_exists($a->module . '_post'))
		&& (! x($_POST,'auth-params'))) {
		call_hooks($a->module . '_mod_post', $_POST);
		$func = $a->module . '_post';
		$func($a);
	}


	if(! $a->error) {
		// If a theme has defined an _aside() function, run that first
		//
		// If the theme function doesn't exist, see if this theme extends another,
		// and see if that other theme has an _aside() function--if it does, run it
		//
		// If $aside_default is not False after the theme _aside() function, run the
		// module's _aside() function too
		//
		// This gives themes more control over how the sidebar looks

		$aside_default = true;
		call_hooks($a->module . '_mod_aside',$placeholder);
		if(function_exists(str_replace('-','_',current_theme()) . '_' . $a->module . '_aside')) {
			$func = str_replace('-','_',current_theme()) . '_' . $a->module . '_aside';
			$aside_default = $func($a);
		}
		elseif($aside_default && x($a->theme_info,"extends") 
			&& (function_exists(str_replace('-','_',$a->theme_info["extends"]) . '_' . $a->module . '_aside'))) {
			$func = str_replace('-','_',$a->theme_info["extends"]) . '_' . $a->module . '_aside';
			$aside_default = $func($a);
		}
		if($aside_default && function_exists($a->module . '_aside')) {
			$func = $a->module . '_aside';
			$func($a);
		}
	}

	if((! $a->error) && (function_exists($a->module . '_content'))) {
		$arr = array('content' => $a->page['content']);
		call_hooks($a->module . '_mod_content', $arr);
		$a->page['content'] = $arr['content'];
		$func = $a->module . '_content';
		$arr = array('content' => $func($a));
		call_hooks($a->module . '_mod_aftercontent', $arr);
		$a->page['content'] .= $arr['content'];
	}

}

// If you're just visiting, let javascript take you home

if(x($_SESSION,'visitor_home'))
	$homebase = $_SESSION['visitor_home'];
elseif(local_user())
	$homebase = $a->get_baseurl() . '/channel/' . $a->channel['channel_address'];

if(isset($homebase))
	$a->page['content'] .= '<script>var homebase="' . $homebase . '" ; </script>';

// now that we've been through the module content, see if the page reported
// a permission problem and if so, a 403 response would seem to be in order.

if(stristr( implode("",$_SESSION['sysmsg']), t('Permission denied'))) {
	header($_SERVER["SERVER_PROTOCOL"] . ' 403 ' . t('Permission denied.'));
}


call_hooks('page_end', $a->page['content']);

construct_page($a);

session_write_close();
exit;
