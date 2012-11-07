<?php

// Set the following for your MySQL installation
// Copy or rename this file to .htconfig.php

$db_host = '$dbhost';
$db_user = '$dbuser';
$db_pass = '$dbpass';
$db_data = '$dbdata';

// Choose a legal default timezone. If you are unsure, use "America/Los_Angeles".
// It can be changed later and only applies to timestamps for anonymous viewers.

$default_timezone = '$timezone';

// What is your site name?

$a->config['system']['baseurl'] = '$siteurl';
$a->config['system']['sitename'] = "My Friend Network";
$a->config['system']['location_hash'] = '$site_id';

// Your choices are REGISTER_OPEN, REGISTER_APPROVE, or REGISTER_CLOSED.
// Be certain to create your own personal account before setting 
// REGISTER_CLOSED. 'register_text' (if set) will be displayed prominently on 
// the registration page. REGISTER_APPROVE requires you set 'admin_email'
// to the email address of an already registered person who can authorise
// and/or approve/deny the request.

$a->config['system']['register_policy'] = REGISTER_OPEN;
$a->config['system']['register_text'] = '';
$a->config['system']['admin_email'] = '$adminmail';

// Maximum size of an imported message, 0 is unlimited

$a->config['system']['max_import_size'] = 200000;

// maximum size of uploaded photos

$a->config['system']['maximagesize'] = 800000;

// Location of PHP command line processor

$a->config['system']['php_path'] = '$phpath';

// Location of global directory submission page.

$a->config['system']['directory_submit_url'] = 'http://dir.friendica.com/submit';
$a->config['system']['directory_search_url'] = 'http://dir.friendica.com/directory?search=';

// default system theme

$a->config['system']['theme'] = 'redbasic';

// By default allow pseudonyms

$a->config['system']['no_regfullname'] = true;
