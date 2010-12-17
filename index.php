<?php

/**
 *
 * Friendika
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
 * Ignore errors. If the file doesn't exist, we are running in installation mode.
 *
 */

$install = ((file_exists('.htconfig.php')) ? false : true);

@include(".htconfig.php");

/**
 *
 * Get the language setting directly from system variables, bypassing get_config()
 * as database may not yet be configured.
 *
 */

$lang = ((isset($a->config['system']['language'])) ? $a->config['system']['language'] : 'en');
	
load_translation_table($lang);

/**
 *
 * Try to open the database;
 *
 */

require_once("dba.php");
$db = new dba($db_host, $db_user, $db_pass, $db_data, $install);
        unset($db_host, $db_user, $db_pass, $db_data);


/**
 *
 * Important stuff we always need to do.
 * Initialise authentication and  date and time. 
 * Create the HTML head for the page, even if we may not use it (xml, etc.)
 * The order of these may be important so use caution if you think they're all
 * intertwingled with no logical order and decide to sort it out. Some of the
 * dependencies have changed, but at least at one time in the recent past - the 
 * order was critical to everything working properly
 *
 */

if(! $install)
	require_once("session.php");

require_once("datetime.php");

date_default_timezone_set(($default_timezone) ? $default_timezone : 'UTC');

$a->init_pagehead();

session_start();

/**
 *
 * For Mozilla auth manager - still needs sorting, and this might conflict with LRDD header.
 * Apache/PHP lumps the Link: headers into one - and other services might not be able to parse it
 * this way. There's a PHP flag to link the headers because by default this will over-write any other 
 * link header. 
 *
 * What we really need to do is output the raw headers ourselves so we can keep them separate.
 *
 */
 
// header('Link: <' . $a->get_baseurl() . '/amcd>; rel="acct-mgmt";');

if((x($_SESSION,'authenticated')) || (x($_POST,'auth-params')) || ($a->module === 'login'))
	require("auth.php");

if(! x($_SESSION,'authenticated'))
	header('X-Account-Management-Status: none');

if(! x($_SESSION,'sysmsg'))
	$_SESSION['sysmsg'] = '';

/*
 * check_config() is responible for running update scripts. These automatically 
 * update the DB schema whenever  we push a new one out. 
 */


if($install)
	$a->module = 'install';
else
	check_config($a);


/**
 *
 * We have already parsed the server path into $->argc and $a->argv
 *
 * $a->argv[0] is our module name. We will load the file mod/{$a->argv[0]}.php
 * and use it for handling our URL request.
 * The module file contains a few functions that we call in various circumstances
 * and in the following order:
 * 
 * "module"_init
 * "module"_post (only if there are $_POST variables)
 * "module"_afterpost
 * "module"_content - the string return of this function contains our page body
 *
 * Modules which emit other serialisations besides HTML (XML,JSON, etc.) should do 
 * so within the module init and/or post functions and then invoke killme() to terminate
 * further processing.
 */

if(strlen($a->module)) {
	if(file_exists("mod/{$a->module}.php")) {
		include("mod/{$a->module}.php");
		$a->module_loaded = true;
	}
	else {
		if((x($_SERVER,'QUERY_STRING')) && ($_SERVER['QUERY_STRING'] === 'q=internal_error.html') && isset($dreamhost_error_hack)) {
			logger('index.php: dreamhost_error_hack invoked');
			goaway($a->get_baseurl() . $_SERVER['REQUEST_URI']);
		}

		logger('index.php: page not found: ' . $_SERVER['REQUEST_URI'] . ' QUERY: ' . $_SERVER['QUERY_STRING'], LOGGER_DEBUG);
		header($_SERVER["SERVER_PROTOCOL"] . ' 404 ' . t('Not Found'));
		notice( t('Page not found.' ) . EOL);
	}
}

if($a->module_loaded) {
	$a->page['page_title'] = $a->module;
	if(function_exists($a->module . '_init')) {
		$func = $a->module . '_init';
		$func($a);
	}

	if(($_SERVER['REQUEST_METHOD'] === 'POST') && (! $a->error)
		&& (function_exists($a->module . '_post'))
		&& (! x($_POST,'auth-params'))) {
		$func = $a->module . '_post';
		$func($a);
	}

	if((! $a->error) && (function_exists($a->module . '_afterpost'))) {
		$func = $a->module . '_afterpost';
		$func($a);
	}

	if((! $a->error) && (function_exists($a->module . '_content'))) {
		$func = $a->module . '_content';
		if(! x($a->page,'content'))
			$a->page['content'] = '';
		$a->page['content'] .= $func($a);
	}

}

if(stristr($_SESSION['sysmsg'], t('Permission denied'))) {
	header($_SERVER["SERVER_PROTOCOL"] . ' 403 ' . t('Permission denied.'));
}

/**
 *
 * Report anything which needs to be communicated in the notification area (before the main body)
 *
 */
	
if(x($_SESSION,'sysmsg')) {
	$a->page['content'] = "<div id=\"sysmsg\" class=\"error-message\">{$_SESSION['sysmsg']}</div>\r\n"
		. ((x($a->page,'content')) ? $a->page['content'] : '');
	unset($_SESSION['sysmsg']);
}

/**
 *
 * Add a place for the pause/resume Ajax indicator
 *
 */

$a->page['content'] .=  '<div id="pause"></div>';


/**
 *
 * Add the navigation (menu) template
 *
 */

if($a->module != 'install')
	require_once('nav.php');

/**
 *
 * Build the page - now that we have all the components
 * Make sure the desired theme exists, though if the default theme doesn't exist we're stuffed.
 *
 */

if((x($_SESSION,'theme')) && (! file_exists('view/theme/' . $_SESSION['theme'] . '/style.css')))
	unset($_SESSION['theme']);

$a->page['htmlhead'] = replace_macros($a->page['htmlhead'], array(
	'$stylesheet' => $a->get_baseurl() . '/view/theme/'
		. ((x($_SESSION,'theme')) ? $_SESSION['theme'] : 'default')
		. '/style.css'
	));

$page    = $a->page;
$profile = $a->profile;

header("Content-type: text/html; charset=utf-8");

$template = 'view/' . $lang . '/' 
	. ((x($a->page,'template')) ? $a->page['template'] : 'default' ) . '.php';

if(file_exists($template))
	require_once($template);
else
	require_once(str_replace($lang . '/', '', $template));

session_write_close();
exit;
