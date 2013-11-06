<?php

// Set the following for your MySQL installation
// Copy or rename this file to .htconfig.php

$db_host = '{{$dbhost}}';
$db_port = '{{$dbport}}';
$db_user = '{{$dbuser}}';
$db_pass = '{{$dbpass}}';
$db_data = '{{$dbdata}}';

/*
 * Notice: Many of the following settings will be available in the admin panel 
 * after a successful site install. Once they are set in the admin panel, they
 * are stored in the DB - and the DB setting will over-ride any corresponding
 * setting in this file
 *
 * The command-line tool util/config is able to query and set the DB items 
 * directly if for some reason the admin panel is not available and a system
 * setting requires modification. 
 *
 */ 


// Choose a legal default timezone. If you are unsure, use "America/Los_Angeles".
// It can be changed later and only applies to timestamps for anonymous viewers.

$default_timezone = '{{$timezone}}';

// What is your site name?

$a->config['system']['baseurl'] = '{{$siteurl}}';
$a->config['system']['sitename'] = "Red Matrix";
$a->config['system']['location_hash'] = '{{$site_id}}';


// Your choices are REGISTER_OPEN, REGISTER_APPROVE, or REGISTER_CLOSED.
// Be certain to create your own personal account before setting 
// REGISTER_CLOSED. 'register_text' (if set) will be displayed prominently on 
// the registration page. REGISTER_APPROVE requires you set 'admin_email'
// to the email address of an already registered person who can authorise
// and/or approve/deny the request.

$a->config['system']['register_policy'] = REGISTER_OPEN;
$a->config['system']['register_text'] = '';
$a->config['system']['admin_email'] = '{{$adminmail}}';


// Site access restrictions. By default we will create private sites.
// Your choices are ACCESS_PRIVATE, ACCESS_PAID, ACCESS_TIERED, and ACCESS_FREE.
// If you leave REGISTER_OPEN above, anybody may register on your
// site, however your site will not be listed anywhere as an open
// registration  hub. We will use the system access policy (below) 
// to determine whether or not to list your site in the directory 
// as an open hub where anybody may create accounts. Your choice of 
// paid, tiered, or free determines how these listings will be presented.  


$a->config['system']['access_policy'] = ACCESS_PRIVATE;

// If you operate a public site, you might wish that people are directed
// to a "sellpage" where you can describe for features or policies or service plans in depth.
// This must be an absolute URL beginning with http:// or https:// .

$a->config['system']['sellpage'] = '';

// Maximum size of an imported message, 0 is unlimited

$a->config['system']['max_import_size'] = 200000;

// maximum size of uploaded photos

$a->config['system']['maximagesize'] = 3000000;

// Location of PHP command line processor

$a->config['system']['php_path'] = '{{$phpath}}';

// Configure how we communicate with directory servers.
// DIRECTORY_MODE_NORMAL     = directory client, we will find a directory
// DIRECTORY_MODE_SECONDARY  = caching directory or mirror
// DIRECTORY_MODE_PRIMARY    = main directory server
// DIRECTORY_MODE_STANDALONE = "off the grid" or private directory services

$a->config['system']['directory_mode']  = DIRECTORY_MODE_NORMAL;

// default system theme

$a->config['system']['theme'] = 'redbasic';


// PHP error logging setup
// Before doing this ensure that the webserver has permission
// to create and write to php.out in the top level Red directory,
// or change the name (below) to a file/path where this is allowed.

// Uncomment the following 4 lines to turn on PHP error logging.
//error_reporting(E_ERROR | E_WARNING | E_PARSE ); 
//ini_set('error_log','php.out'); 
//ini_set('log_errors','1'); 
//ini_set('display_errors', '0');
