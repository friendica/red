<?php

// If automatic system installation fails: 

// Copy or rename this file to .htconfig.php in the top level 
// Friendica directory

// Why .htconfig.php? Because it contains sensitive information which could
// give somebody complete control of your database. Apache's default 
// configuration denies access to and refuses to serve any file beginning 
// with .ht

// Then set the following for your MySQL installation

$db_host = 'your.mysqlhost.com';
$db_port = null; #leave null for default or set your port
$db_user = 'mysqlusername';
$db_pass = 'mysqlpassword';
$db_data = 'mysqldatabasename';

// smarty3 compile dir. make sure is writable by webserver
$a->config['system']['smarty3_folder'] = "view/tpl/smart3";

// Choose a legal default timezone. If you are unsure, use "America/Los_Angeles".
// It can be changed later and only applies to timestamps for anonymous viewers.

$default_timezone = 'America/Los_Angeles';

// What is your site name?

$a->config['sitename'] = "Friendica Social Network";

// Your choices are REGISTER_OPEN, REGISTER_APPROVE, or REGISTER_CLOSED.
// Be certain to create your own personal account before setting 
// REGISTER_CLOSED. 'register_text' (if set) will be displayed prominently on 
// the registration page. REGISTER_APPROVE requires you set 'admin_email'
// to the email address of an already registered person who can authorise
// and/or approve/deny the request. 

// In order to perform system administration via the admin panel, admin_email
// must precisely match the email address of the person logged in.

$a->config['system']['register_policy'] = REGISTER_OPEN;
$a->config['register_text'] = '';
$a->config['system']['admin_email'] = '';

// Maximum size of an imported message, 0 is unlimited

$a->config['max_import_size'] = 200000;

// maximum size of uploaded photos

$a->config['system']['maximagesize'] = 800000;

// Location of PHP command line processor

$a->config['php_path'] = 'php';


// Configure how we communicate with directory servers.
// DIRECTORY_MODE_NORMAL     = directory client, we will find a directory
// DIRECTORY_MODE_SECONDARY  = caching directory or mirror
// DIRECTORY_MODE_PRIMARY    = main directory server
// DIRECTORY_MODE_STANDALONE = "off the grid" or private directory services

$a->config['system']['directory_mode']  = DIRECTORY_MODE_NORMAL;


// PuSH - aka pubsubhubbub URL. This makes delivery of public posts as fast as private posts

$a->config['system']['huburl'] = 'http://pubsubhubbub.appspot.com';

// allowed themes (change this from admin panel after installation)

$a->config['system']['allowed_themes'] = 'redbasic';

// default system theme

$a->config['system']['theme'] = 'redbasic';



