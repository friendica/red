<?php

// Set the following for your MySQL installation
// Copy or rename this file to .htconfig.php

$db_host = 'your.mysqlhost.com';
$db_user = 'mysqlusername';
$db_pass = 'mysqlpassword';
$db_data = 'mysqldatabasename';

// Choose a legal default timezone. If you are unsure, use "America/Los_Angeles".
// It can be changed later and only applies to timestamps for anonymous viewers.

$default_timezone = 'Australia/Sydney';

// What is your site name?

$a->config['sitename'] = "DFRN developer";

// At the present time you can have REGISTER_OPEN
// or REGISTER_CLOSED. But register your personal account 
// first before you close it.

$a->config['register_policy'] = REGISTER_OPEN;


// Maximum size of an imported message, 0 is unlimited.

$a->config['max_import_size'] = 10000;