<?php

require_once("boot.php");

$a = new App;

$debug_text = ''; // Debugging functions should never be used on production systems.

// Setup the database.

$install = ((file_exists('.htconfig.php')) ? false : true);

@include(".htconfig.php");
require_once("dba.php");
$db = new dba($db_host, $db_user, $db_pass, $db_data, $install);
	unset($db_host, $db_user, $db_pass, $db_data);

require_once("session.php");
require_once("datetime.php");

date_default_timezone_set(($default_timezone) ? $default_timezone : 'UTC');

$a->init_pagehead();

session_start();

if((x($_SESSION,'authenticated')) || (x($_POST['auth-params'])))
	require("auth.php");

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
		notice( t('Page not found' ) . EOL);
	}
}

// invoke module functions
// Important: Modules normally do not emit content, unless you need it for debugging.
// The module_init, module_post, and module_afterpost functions process URL parameters and POST processing.
// The module_content function returns content text to this file where it is included on the page.
// Modules emitting XML/Atom, etc. should do so idirectly and promptly exit before the HTML page can be rendered.
// "Most" HTML resides in the view directory as text templates with macro substitution. 
// They look like HTML with PHP variables but only a couple pass through the PHP processor - those with .php extensions.
// The macro substitution is defined per page for the .tpl files. 
// Information transfer between functions can be accomplished via the App session '$a' and its related variables.
// x() queries both a variable's existence and that it is "non-zero" or "non-empty" depending on how it is called. 
// q() is the SQL query form. All string (%s) variables MUST be passed through dbesc(). 
// All int values MUST be cast to integer using intval(); 

if($a->module_loaded) {
	$a->page['page_title'] = $a->module;
	if(function_exists($a->module . '_init')) {
		$func = $a->module . '_init';
		$func($a);
    	}

	if(($_SERVER['REQUEST_METHOD'] == 'POST') && (! $a->error)
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
      		$a->page['content'] .= $func($a);
	}

	footer($a);
}

// report anything important happening
	
if(x($_SESSION,'sysmsg')) {
	$a->page['content'] = "<div id=\"sysmsg\" class=\"error-message\">{$_SESSION['sysmsg']}</div>\r\n"
		. $a->page['content'];
	unset($_SESSION['sysmsg']);
}

if(stristr($_SESSION['sysmsg'], t('Permission denied'))) {
	header($_SERVER["SERVER_PROTOCOL"] . ' 403 ' . t('Permission denied.'));
}


// Feel free to comment out this line on production sites.
$a->page['content'] .= $debug_text;

// build page

// Navigation (menu) template
require_once("nav.php");

$page    = $a->page;
$profile = $a->profile;

header("Content-type: text/html; charset=utf-8");
$template = "view/" 
	. ((x($a->page,'theme')) ? $a->page['theme'] . '/' : "" ) 
	. ((x($a->page,'template')) ? $a->page['template'] : 'default' ) 
	. ".php";

require_once($template);

session_write_close();
exit;
