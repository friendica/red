<?php


function install_post(&$a) {

	global $db;

	$dbhost = notags(trim($_POST['dbhost']));
	$dbuser = notags(trim($_POST['dbuser']));
	$dbpass = notags(trim($_POST['dbpass']));
	$dbdata = notags(trim($_POST['dbdata']));
	$timezone = notags(trim($_POST['timezone']));
	$phpath = notags(trim($_POST['phpath']));

	require_once("dba.php");

	$db = new dba($dbhost, $dbuser, $dbpass, $dbdata, $true);

	if(! $db->getdb()) {
		notice( t('Could not connect to database.') . EOL);
		return;
	}
	else
		notice( t('Connected to database.') . EOL);

	$tpl = file_get_contents('view/htconfig.tpl');
	$txt = replace_macros($tpl,array(
		'$dbhost' => $dbhost,
		'$dbuser' => $dbuser,
		'$dbpass' => $dbpass,
		'$dbdata' => $dbdata,
		'$timezone' => $timezone,
		'$phpath' => $phpath
	));
	$result = file_put_contents('.htconfig.php', $txt);
	if(! $result) {
		$a->data = $txt;
	}

	$errors = load_database($db);
	if(! $errors) {
		// Our sessions normally are stored in the database. But as we have only managed 
		// to get it bootstrapped milliseconds ago, we have to apply a bit of trickery so 
		// that you'll see the following important notice (which is stored in the session). 

		session_write_close();
		require_once('session.php');
		session_start();
		$_SESSION['sysmsg'] = '';

		notice( t('Database import succeeded.') . EOL 
			. t('IMPORTANT: You will need to (manually) setup a scheduled task for the poller.') . EOL 
			. t('Please see the file INSTALL.') . EOL );
		goaway($a->get_baseurl());
	}
	else {
		$db = null; // start fresh
		notice( t('Database import failed.') . EOL
			. t('You may need to import the file "database.sql" manually using phpmyadmin or mysql.') . EOL
			. t('Please see the file INSTALL.') . EOL );
	}
}


function install_content(&$a) {

	notice( t('Welcome to the Mistpark Social Network.') . EOL);

	$o .= check_htconfig();
	if(strlen($o))
		return $o;

	if(strlen($a->data)) {
		$o .= manual_config($a);
		return;
	}

	$o .= check_php($phpath);

	require_once('datetime.php');

	$tpl = file_get_contents('view/install_db.tpl');
	$o .= replace_macros($tpl, array(
		'$tzselect' => ((x($_POST,'timezone')) ? select_timezone($_POST['timezone']) : select_timezone()),
		'$submit' => t('Submit'),
		'$dbhost' => ((x($_POST,'dbhost')) ? notags(trim($_POST['dbhost'])) : 'localhost'),
		'$dbuser' => notags(trim($_POST['dbuser'])),
		'$dbpass' => notags(trim($_POST['dbpass'])),
		'$dbdata' => notags(trim($_POST['dbdata'])),
		'$phpath' => $phpath
	));

	return $o;
}

function check_php(&$phpath) {
	$phpath = trim(shell_exec('which php'));
	if(! strlen($phpath)) {
		$o .= <<< EOT
Could not find a command line version of PHP in the web server PATH. This is required. Please adjust the configuration file .htconfig.php accordingly.

EOT;
	}
	return $o;
}

function check_htconfig() {

	if(((file_exists('.htconfig.php')) && (! is_writable('.htconfig.php')))
		|| (! is_writable('.'))) {

$o .= <<< EOT

The web installer needs to be able to create a file called ".htconfig.php" in the top folder of 
your web server. It is unable to do so. This is most often a permission setting, as the web server 
may not be able to write files in your folder (even if you can).

Please check with your site documentation or support people to see if this situation can be corrected. 
If not, you may be required to perform a manual installation. Please see the file "INSTALL" for instructions. 

EOT;
	}

return $o;
}

	
function manual_config(&$a) {
$o .= <<< EOT
The database configuration file ".htconfig.php" could not be written. Please use the enclosed text to create a configuration file in your web server root.

<textarea rows="24" cols="80" >{$a->data}</textarea>
EOT;
return $o;
}


function load_database($db) {

	$str = file_get_contents('database.sql');
	$arr = explode(';',$str);
	$errors = 0;
	foreach($arr as $a) {
		if(strlen(trim($a))) {	
			$r = @$db->q(trim($a));
			if(! $r) {
				notice( t('Errors encountered creating database tables.') . $a . EOL);
				$errors ++;
			}
		}
        }
	return $errors;
}