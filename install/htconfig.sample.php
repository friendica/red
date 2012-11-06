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
$db_user = 'mysqlusername';
$db_pass = 'mysqlpassword';
$db_data = 'mysqldatabasename';

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

// You shouldn't need to change anything else.
// Location of global directory submission page. 

$a->config['system']['directory_submit_url'] = 'http://dir.friendica.com/submit';
$a->config['system']['directory_search_url'] = 'http://dir.friendica.com/directory?search=';

// PuSH - aka pubsubhubbub URL. This makes delivery of public posts as fast as private posts

$a->config['system']['huburl'] = 'http://pubsubhubbub.appspot.com';

// allowed themes (change this from admin panel after installation)

$a->config['system']['allowed_themes'] = 'duepuntozero';

// default system theme

$a->config['system']['theme'] = 'duepuntozero';



