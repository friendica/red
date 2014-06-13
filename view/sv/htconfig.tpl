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

// Maximum size of an imported message, 0 is unlimited

$a->config['system']['max_import_size'] = 200000;

// maximum size of uploaded photos

$a->config['system']['maximagesize'] = 800000;

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

