<?php

require_once("boot.php");

$a = new App;

$debug_text = ''; // Debugging functions should never be used on production systems.

// Setup the language and database.

$install = ((file_exists('.htconfig.php')) ? false : true);

@include(".htconfig.php");

if(isset($lang) && strlen($lang))
	load_translation_table($lang);

require_once("dba.php");
$db = new dba($db_host, $db_user, $db_pass, $db_data, $install);
        unset($db_host, $db_user, $db_pass, $db_data);

if(! $install)
	require_once("session.php");

require_once("datetime.php");

date_default_timezone_set(($default_timezone) ? $default_timezone : 'UTC');

$a->init_pagehead();

session_start();


if((x($_SESSION,'authenticated')) || (x($_POST,'auth-params')) || ($a->module === 'login'))
	require("auth.php");

if(! x($_SESSION,'authenticated'))
	header('X-Account-Management-Status: none');

if(! x($_SESSION,'sysmsg'))
	$_SESSION['sysmsg'] = '';

if($install)
	$a->module = 'install';
else
	check_config($a);

if(strlen($a->module)) {
	if(file_exists("mod/{$a->module}.php")) {
		include("mod/{$a->module}.php");
		$a->module_loaded = true;
	}
	else {
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

// report anything important happening
	
if(x($_SESSION,'sysmsg')) {
	$a->page['content'] = "<div id=\"sysmsg\" class=\"error-message\">{$_SESSION['sysmsg']}</div>\r\n"
		. ((x($a->page,'content')) ? $a->page['content'] : '');
	unset($_SESSION['sysmsg']);
}


// Feel free to comment out this line on production sites.
$a->page['content'] .= $debug_text;

$a->page['content'] .=  '<div id="pause"></div>';
// build page


// Navigation (menu) template
if($a->module != 'install')
	require_once("nav.php");

// make sure the desired theme exists, though if the default theme doesn't exist we're stuffed.

if((x($_SESSION,'theme')) && (! file_exists('/view/theme/' . $_SESSION['theme'] . '/style.css')))
	unset($_SESSION['theme']);

$a->page['htmlhead'] = replace_macros($a->page['htmlhead'], array(
	'$stylesheet' => $a->get_baseurl() . '/view/theme/'
		. ((x($_SESSION,'theme')) ? $_SESSION['theme'] : 'default')
		. '/style.css'
	));

$page    = $a->page;
$profile = $a->profile;
$lang    = get_config('system','language');

header("Content-type: text/html; charset=utf-8");

$template = "view/" . (($lang) ? $lang . "/" : "") 
	. ((x($a->page,'template')) ? $a->page['template'] : 'default' ) 
	. ".php";

require_once($template);

session_write_close();
exit;
