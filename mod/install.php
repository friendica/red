<?php


function install_post(&$a) {

	global $db;

	$urlpath = $a->get_path();
	$dbhost = notags(trim($_POST['dbhost']));
	$dbuser = notags(trim($_POST['dbuser']));
	$dbpass = notags(trim($_POST['dbpass']));
	$dbdata = notags(trim($_POST['dbdata']));
	$timezone = notags(trim($_POST['timezone']));
	$phpath = notags(trim($_POST['phpath']));
	$adminmail = notags(trim($_POST['adminmail']));

	require_once("dba.php");
	unset($db);
	$db = new dba($dbhost, $dbuser, $dbpass, $dbdata, true);

	if(mysqli_connect_errno()) {
		unset($db);
		$db = new dba($dbhost, $dbuser, $dbpass, '', true);
		if(! mysqli_connect_errno()) {
			$r = q("CREATE DATABASE '%s'",
					dbesc($dbdata)
			);
			if($r) {
				unset($db);
				$db = new dba($dbhost, $dbuser, $dbpass, $dbdata, true);
			}
		}
		if(mysqli_connect_errno()) {
			notice( t('Could not create/connect to database.') . EOL);
			return;
		}
	}

	info( t('Connected to database.') . EOL);

	$tpl = get_intltext_template('htconfig.tpl');
	$txt = replace_macros($tpl,array(
		'$dbhost' => $dbhost,
		'$dbuser' => $dbuser,
		'$dbpass' => $dbpass,
		'$dbdata' => $dbdata,
		'$timezone' => $timezone,
		'$urlpath' => $urlpath,
		'$phpath' => $phpath,
		'$adminmail' => $adminmail
	));

	$result = file_put_contents('.htconfig.php', $txt);
	if(! $result) {
		$a->data = $txt;
	}

	$errors = load_database($db);

	if($errors)
		$a->data['db_failed'] = true;
	else
		$a->data['db_installed'] = true;

	return;
}


function install_content(&$a) {

	global $db;
	$o = '';
	
	if(x($a->data,'db_installed')) {
		$o .= '<h2>' . t('Proceed with Installation') . '</h2>';
		$o .= '<p style="font-size: 130%;">';
		$o .= t('Your Friendika site database has been installed.') . EOL;
		$o .= t('IMPORTANT: You will need to [manually] setup a scheduled task for the poller.') . EOL ;
		$o .= t('Please see the file "INSTALL.txt".') . EOL ;
		$o .= '<br />';
		$o .= '<a href="' . $a->get_baseurl() . '/register' . '">' . t('Proceed to registration') . '</a>' ;
		$o .= '</p>';
		return $o;
	}

	if(x($a->data,'db_failed')) {
		$o .= t('Database import failed.') . EOL;
		$o .= t('You may need to import the file "database.sql" manually using phpmyadmin or mysql.') . EOL;
		$o .= t('Please see the file "INSTALL.txt".') . EOL ;
		return $o;
	}

	if($db && $db->connected) {
		$r = q("SELECT COUNT(*) as `total` FROM `user`");
		if($r && count($r) && $r[0]['total']) {
			notice( t('Permission denied.') . EOL);
			return '';
		}
	}

	info( t('Welcome to Friendika.') . EOL);


	check_funcs();

	$o .= check_htconfig();
	if(strlen($o))
		return $o;

	if(strlen($a->data)) {
		$o .= manual_config($a);
		return;
	}

	$o .= check_php($phpath);

	$o .= check_keys();


	require_once('datetime.php');

	$tpl = get_markup_template('install_db.tpl');
	$o .= replace_macros($tpl, array(
		'$lbl_01' => t('Friendika Social Network'),
		'$lbl_02' => t('Installation'),
		'$lbl_03' => t('In order to install Friendika we need to know how to contact your database.'),
		'$lbl_04' => t('Please contact your hosting provider or site administrator if you have questions about these settings.'),
		'$lbl_05' => t('The database you specify below must already exist. If it does not, please create it before continuing.'),
		'$lbl_06' => t('Database Server Name'),
		'$lbl_07' => t('Database Login Name'),
		'$lbl_08' => t('Database Login Password'),
		'$lbl_09' => t('Database Name'),
		'$lbl_10' => t('Please select a default timezone for your website'),
		'$lbl_11' => t('Site administrator email address. Your account email address will need match this.'), 
		'$baseurl' => $a->get_baseurl(),
		'$tzselect' => ((x($_POST,'timezone')) ? select_timezone($_POST['timezone']) : select_timezone()),
		'$submit' => t('Submit'),
		'$dbhost' => ((x($_POST,'dbhost')) ? notags(trim($_POST['dbhost'])) : 'localhost'),
		'$dbuser' => notags(trim($_POST['dbuser'])),
		'$dbpass' => notags(trim($_POST['dbpass'])),
		'$dbdata' => notags(trim($_POST['dbdata'])),
		'$phpath' => $phpath,
		'$adminemail' => notags(trim($_POST['adminemail']))
	));

	return $o;
}

function check_php(&$phpath) {
	$o = '';
	$phpath = trim(shell_exec('which php'));
	if(! strlen($phpath)) {
		$o .= t('Could not find a command line version of PHP in the web server PATH.') . EOL;
		$o .= t('This is required. Please adjust the configuration file .htconfig.php accordingly.') . EOL;
	}
	if(strlen($phpath)) {
		$str = autoname(8);
		$cmd = "$phpath testargs.php $str";
		$result = trim(shell_exec($cmd));
		if($result != $str) {
			$o .= t('The command line version of PHP on your system does not have "register_argc_argv" enabled.') . EOL;
			$o .= t('This is required for message delivery to work.') . EOL;
		}
	}
	return $o;

}

function check_keys() {

	$o = '';

	$res = false;

	if(function_exists('openssl_pkey_new')) 
		$res=openssl_pkey_new(array(
		'digest_alg' => 'sha1',
		'private_key_bits' => 4096,
		'encrypt_key' => false ));

	// Get private key

	if(! $res) {
		$o .= t('Error: the "openssl_pkey_new" function on this system is not able to generate encryption keys') . EOL;
		$o .= t('If running under Windows, please see "http://www.php.net/manual/en/openssl.installation.php".') . EOL;
	}
	return $o;

}


function check_funcs() {
	if((function_exists('apache_get_modules')) && (! in_array('mod_rewrite',apache_get_modules())))
		notice( t('Error: Apache webserver mod-rewrite module is required but not installed.') . EOL);
	if(! function_exists('curl_init')) 
		notice( t('Error: libCURL PHP module required but not installed.') . EOL);
	if(! function_exists('imagecreatefromjpeg')) 
		notice( t('Error: GD graphics PHP module with JPEG support required but not installed.') . EOL);
	if(! function_exists('openssl_public_encrypt')) 
		notice( t('Error: openssl PHP module required but not installed.') . EOL);	
	if(! function_exists('mysqli_connect')) 
		notice( t('Error: mysqli PHP module required but not installed.') . EOL);
	if(! function_exists('mb_strlen'))
		notice( t('Error: mb_string PHP module required but not installed.') . EOL);
	
	if((x($_SESSION,'sysmsg')) && strlen($_SESSION['sysmsg']))
		notice( t('Please see the file "INSTALL.txt".') . EOL);
}


function check_htconfig() {

	if(((file_exists('.htconfig.php')) && (! is_writable('.htconfig.php')))
		|| (! is_writable('.'))) {

		$o = t('The web installer needs to be able to create a file called ".htconfig.php" in the top folder of your web server and it is unable to do so.');
		$o .= t('This is most often a permission setting, as the web server may not be able to write files in your folder - even if you can.');
		$o .= t('Please check with your site documentation or support people to see if this situation can be corrected.');
		$o .= t('If not, you may be required to perform a manual installation. Please see the file "INSTALL.txt" for instructions.'); 
	}

	return $o;
}

	
function manual_config(&$a) {
	$data = htmlentities($a->data);
	$o = t('The database configuration file ".htconfig.php" could not be written. Please use the enclosed text to create a configuration file in your web server root.');
	$o .= "<textarea rows=\"24\" cols=\"80\" >$data</textarea>";
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
