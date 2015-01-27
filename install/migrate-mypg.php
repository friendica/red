<?php
function ask_question($menu, $options, $default) {
	$r=null;
	while(!$r) {
		echo $menu;
		$str = substr(strtolower(fgets(STDIN)),0,-1);
		if($str == '')
			$r = $default;
		else if(in_array($str, $options))
			$r = $str;
	}
	return $r;
}

function get_data($prompt, $regex) {
	do {
		echo $prompt;
		$r = substr(fgets(STDIN), 0, -1);
		if(!preg_match($regex, $prompt))
			$r = '';
	} while($r === '');
	return $r;
}

function parse_htconfig($file) {
	if(!file_exists($file))
		return array();
	$conf = file_get_contents($file);
	preg_match_all('/\$db\_(host|port|user|pass|data|type)\s*=\s*\'([[:print:]]+)\'/', $conf, $matches);
	return array_combine($matches[1], $matches[2]);
}

function get_configtype(array $data) {
	if(!isset($data['host'], $data['user'], $data['pass'], $data['data']))
		return 'none';
	if(@$data['type'] == 1)
		return 'pgsql';
	return 'mysql';
}

function phpquote($str) {
	for($r = '', $x=0, $l=strlen($str); $x < $l; $x++)
		if($str{$x} == '\'' || $str{$x} == '\\')
			$r .= '\\' . $str{$x};
		else
			$r .= $str{$x};
	return $r;
}

function run_sql($file, $db, &$err, &$n) {
	$sql = file_get_contents($file);
	$sql = explode(';', $sql);
	$err = 0; $n = 0;
	$c = count($sql);
	if(!$c) {
		echo "Unknown error.\n";
		exit();
	}
	foreach($sql as $stmt) {
		if($stmt == '' || $stmt == "\n" || $stmt == "\n\n") {
			$c--;
			continue;
		}
		$r = $db->exec($stmt);
		if($r===false) {
			echo "\nError executing $stmt: ".var_export($db->errorInfo(), true)."\n";
			$err++;
		} else {
			$n++;
		}
		if($n % 5 == 0)
			echo "\033[255DExecuting: $file, $n/$c\033[K";
	}
	echo "\n";
}
	
$drivers=true;
if(!class_exists('PDO'))
	$drivers=false;
if($drivers) {
	$drivers = PDO::getAvailableDrivers();
	if(!in_array('pgsql', $drivers) || !in_array('mysql', $drivers))
		$drivers = false;
}
if(!$drivers) {
	echo "Sorry. This migration tool requires both mysql and pgsql PDO drivers.\n";
	$r = ask_question("If you are on dreamhost you can enable them. This might work on other shared hosts too. Type 'n' to do it yourself.\nWould you like to try (Y/n)? ", array('y', 'n'), 'y');
	if($r=='y') {
		$path = $_SERVER['HOME'] . '/.php/5.4';
		if(!file_exists($path))
			mkdir($path, 0770, true);
		
		$rcfile = $path . '/phprc';

		$str = '';
		$mods = get_loaded_extensions();
		foreach(array('pdo_mysql','pdo_pgsql','pgsql') as $ext)
			if(!in_array($ext, $mods))
				$str .= 'extension=' . $ext . ".so\n";
				
		file_put_contents($rcfile, $str, FILE_APPEND );
		echo "drivers enabled.\nNow type: \033[1m/usr/local/bin/php-5.4 install/".basename($argv[0])."\033[0m\n";
	}
	exit();
}

foreach(array('install','include','mod','view') as $dir) {
	if(!file_exists($dir)) {
		echo "You must execute from inside the webroot like the cron\n";
		exit();
	}
}

$cfgfile = '.htconfig.php';
if($argc >= 2 && $argv[1] == '--resume') {
	if($argc < 4) {
		echo "Resume usage {$argv[0]} --resume <table> <row>\n";
		exit();
	}
	$starttable = $argv[2];
	$startrow = $argv[3];
	$cfgfile = '.htconfig.php-mysql';
}

$cfg = parse_htconfig($cfgfile);
$type = get_configtype($cfg);
if($type != 'mysql') {
	echo "Error. Must start with standard mysql installation in .htconfig.php.\n";
	exit();
}

if(!$cfg['port'])
	$cfg['port'] = 3306;
try {
	$mydb = new PDO("mysql:host={$cfg['host']};dbname={$cfg['data']};port={$cfg['port']}", $cfg['user'], $cfg['pass']);
} catch (PDOException $e) {
	echo "Error connecting to mysql DB: " . $e->getMessage() . "\n";
	exit();
}

// mysql insists on buffering even when you use fetch() instead of fetchAll() for some stupid reason
// http://stackoverflow.com/questions/6895098/pdo-mysql-memory-consumption-with-large-result-set
$mydb->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false); 

if(!file_exists('.htconfig.php-pgsql')) {
	echo "Enter postgres server info:\n";
	$p['host'] = get_data("Hostname: ", '/[\w.]+/');
	$p['port'] = get_data("Enter port (0 for default): ", '/\d+/');
	$p['user'] = get_data("Username: ", '/\w+/');
	$p['pass'] = get_data("Password: ", '/[[:print:]]+/');
	$p['data'] = get_data("Database name: ", '/\w+/');
	$old = file_get_contents('.htconfig.php');
	$new = preg_replace(
		array(
			'/^(\$db_host\s*=\s*\')([\w.]+)(\';)$/m',
			'/^(\$db_port\s*=\s*\')(\d+)(\';)$/m',
			'/^(\$db_user\s*=\s*\')(\w+)(\';)$/m',
			'/^(\$db_pass\s*=\s*\')([[:print:]]+)(\';)/m',
			'/^(\$db_data\s*=\s*\')(\w+)(\';)$/m',
			'/^(\$db_type\s*=\s*\')(\d)(\';)$/m' // in case they already have it
		), array(
			"$1{$p['host']}$3",
			"\${1}{$p['port']}$3",
			"$1{$p['user']}$3",
			"$1{$p['pass']}$3",
			"$1{$p['data']}$3\n\$db_type = '1';\n", // they probably don't
			"\${1}1$3"
		),
		$old,
		1,
		$repl
	);
	if($new === false || $repl < 5) {
		echo "Failed. Please make a postgres config file named .htconfig.php-pgsql - Be sure to add \"\$db_type = '1';\" to your config.\n";
		exit();
	}
	file_put_contents('.htconfig.php-pgsql', $new);
}

$pcfg = parse_htconfig('.htconfig.php-pgsql');
$ptype = get_configtype($pcfg);
if($ptype != 'pgsql') {
	echo "Error. Must have a valid pgsql config named .htconfig.php-pgsql. Be sure to add \"\$db_type = '1';\" to your config.\n";
	exit();
}

if(!$pcfg['port'])
	$pcfg['port'] = 5432;
try {
	$pgdb = new PDO("pgsql:host={$pcfg['host']};dbname={$pcfg['data']};port={$pcfg['port']}", $pcfg['user'], $pcfg['pass']);
} catch (PDOException $e) {
	echo "Error connecting to pgsql DB: " . $e->getMessage() . "\n";
	echo "cfg string: " . "pgsql:host={$pcfg['host']};dbname={$pcfg['data']};port={$pcfg['port']}\n";
	exit();
}
$B = "\033[0;34m";
$H = "\033[0;35m";
$W = "\033[1;37m";
$M = "\033[1;31m";
$N = "\033[0m";

if(isset($starttable)) {
	$r = ask_question("Ready to migrate {$W}Red{$M}(#){$W}Matrix$N from mysql db @$B{$cfg['host']}$N/$B{$cfg['data']}$N to postgres db @$B{$pcfg['host']}$N/$B{$pcfg['data']}$N.

Resuming failed migration ({$M}experimental$N) starting at table '$starttable' row $startrow. 
Are you ready to begin (N/y)? ", 
		array('y', 'n'), 
		'n'
	);
	if($r == 'n')
		exit();
} else {
	$r = ask_question("Ready to migrate {$W}Red{$M}(#){$W}Matrix$N from mysql db @$B{$cfg['host']}$N/$B{$cfg['data']}$N to postgres db @$B{$pcfg['host']}$N/$B{$pcfg['data']}$N.
The site will be disabled during the migration by moving the $H.htconfig.php$N file to $H.htconfig.php-mysql$N. 
If for any reason the migration fails, you will need to move the config file back into place manually before trying again.

Are you ready to begin (N/y)? ", array('y','n'), 'n'
	);

	if($r == 'n')
		exit();

	rename('.htconfig.php', '.htconfig.php-mysql');

	run_sql('install/schema_postgres.sql', $pgdb, $err, $n);
	if($err) {
		echo "There were $err errors creating the pgsql schema. Unable to continue.\n";
		exit();	
	}

	echo "pgsql schema created. $n queries executed successfully.\n";
}

$res = $pgdb->query("select relname, attname, pg_type.typname from ((pg_attribute inner join pg_class on attrelid=pg_class.oid) inner join pg_type on atttypid=pg_type.oid) inner join pg_namespace on relnamespace=pg_namespace.oid  where nspname='public' and atttypid not in (26,27,28,29) and relkind='r' and attname <> 'item_search_vector';");
if($res === false) {
	echo "Error reading back schema. Unable to continue.\n";
	var_export($pgdb->errorInfo());
	exit();
}
$schema = array();
while(($row = $res->fetch()) !== false)
	$schema[$row[0]][$row[1]] = $row[2];
	
$res = $pgdb->query("select relname, attname from pg_attribute inner join pg_class on attrelid=pg_class.oid inner join pg_constraint on conrelid=pg_class.oid and pg_attribute.attnum = any (conkey) where contype='p';");
if($res === false) {
	echo "Error reading back primary keys. Unable to continue.\n";
	var_export($pgdb->errorInfo());
	exit();
}
$pkeys = array();
while(($row = $res->fetch()) !== false)
	$pkeys[$row[0]] = $row[1];

$err = 0; $n = 0;
$reserved = array('ignore','key','with');
foreach($schema as $table=>$fields) {
	if(isset($starttable) && !$n && $table != $starttable) {
		echo "Skipping table $table\n";
		continue;
	}
	$fnames = array_keys($fields);
	$pfnames = array_keys($fields);
	
	foreach($fnames as &$fname)
		if(in_array($fname, $reserved))
			$fname = '`' . $fname . '`';
	$fstr = implode(',', $fnames);
	
	foreach($pfnames as &$pfname)
		if(in_array($pfname, $reserved))
			$pfname = '"' . $pfname . '"';
	$pfstr = implode(',', $pfnames);
	
	$cres = $mydb->query("SELECT count(*) FROM $table;");
	if($cres === false) {
		echo "Fatal error counting table $table: ".var_export($mydb->errorInfo(), true)."\n";
		exit();
	}
	$nrows = $cres->fetchColumn(0);
	$cres->closeCursor();
	
	if(!$nrows) {
		echo "TABLE $table has 0 rows in mysql db.\n";
		continue;
	}
	
	$pstr = '';
	for($x=0, $c=count($fields); $x < $c; $x++)
		$pstr .= ($x ? ',?' : '?');

	if(isset($starttable) && $table == $starttable) {
		$selectsql = "SELECT $fstr FROM $table ORDER BY {$pkeys[$table]} LIMIT $nrows OFFSET $startrow;";
		$crow = $startrow;
	} else {
		$selectsql = "SELECT $fstr FROM $table ORDER BY {$pkeys[$table]};";
		$crow = 0;
	}

	echo "\033[255DTABLE: $table [$c fields]  $crow/$nrows    (".number_format(($crow/$nrows)*100,2)."%)\033[K";
		
	$res = $mydb->query($selectsql);
	if($res === false) {
		echo "Fatal Error importing table $table: ".var_export($mydb->errorInfo(), true)."\n";
		exit();
	}

	$istmt = $pgdb->prepare("INSERT INTO $table ($pfstr) VALUES ($pstr);");
	if($istmt === false) {
		echo "Fatal error preparing query. Aborting.\n";
		var_export($pgdb->errorInfo());
		exit();
	}

	while(($row = $res->fetch(PDO::FETCH_NUM)) !== false) {
		foreach($row as $idx => &$val) 
			if(array_slice(array_values($fields),$idx,1)[0] == 'timestamp' && $val == '0000-00-00 00:00:00')
				$istmt->bindParam($idx+1, ($nulldate='0001-01-01 00:00:00'));
			else if(array_slice(array_values($fields),$idx,1)[0] == 'bytea')
				$istmt->bindParam($idx+1, $val, PDO::PARAM_LOB);
			else
				$istmt->bindParam($idx+1, $val);
		$r = $istmt->execute();
		if($r === false) {
			$err++;
			echo "Insert error: ".var_export(array($pgdb->errorInfo(), $table, $fields, $row), true)."\nResume with {$argv[0]} --resume $table $crow\n";
			exit();
		} else
			$n++;
		$crow++;
		if(($crow % 10) == 0 || $crow == $nrows)
			echo "\033[255DTABLE: $table [$c fields]  $crow/$nrows    (".number_format(($crow/$nrows)*100,2)."%)\033[K";
	}
	$res->closeCursor();
	echo "\n";
}

echo "Done with $err errors and $n inserts.\n";
if($err) {
	echo "Migration had errors. Aborting.\n";
	exit();
}

run_sql('install/migrate_mypg_fixseq.sql', $pgdb, $err, $n);
echo "Sequences updated with $err errors and $n inserts.\n";
if($err)
	exit();
	
$r = ask_question("Everything successful. Once you connect up the pg database there is no going back. Do you want to make it live (N,y)?", array('y', 'n'), 'n');
if($r == 'n') {
	echo "You can make active by renaming .htconfig.php-pgsql to .htconfig.php, or start over by renaming .htconfig.php-mysql to .htconfig.php\n";
	exit();
} 

rename('.htconfig.php-pgsql', '.htconfig.php');
echo "Done. {$W}Red{$M}(#){$W}Matrix$N now running on postgres.\n";
	

