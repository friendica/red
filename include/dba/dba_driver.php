<?php /** @file */

function dba_factory($server, $port,$user,$pass,$db,$dbtype,$install = false) {
	$dba = null;

	if($dbtype == 1) {
		require_once('include/dba/dba_postgres.php');
		if(is_null($port)) $port = 5432;
		$dba = new dba_postgres($server, $port, $user, $pass, $db, $install);
	} else {
		if(class_exists('mysqli')) {
			if (is_null($port)) $port = ini_get("mysqli.default_port");
			require_once('include/dba/dba_mysqli.php');
			$dba = new dba_mysqli($server, $port,$user,$pass,$db,$install);
		} else {
			if (is_null($port)) $port = "3306";
			require_once('include/dba/dba_mysql.php');
			$dba = new dba_mysql($server, $port,$user,$pass,$db,$install);
		}
	}
	define('NULL_DATE', $dba->get_null_date());
	define('ACTIVE_DBTYPE', $dbtype);
	return $dba;
}


abstract class dba_driver {
	// legacy behavior
	const INSTALL_SCRIPT='install/schema_mysql.sql';
	const NULL_DATE = '0000-00-00 00:00:00';
	const UTC_NOW = 'UTC_TIMESTAMP()';
	
	protected $debug = 0;
	protected $db;
	public  $connected = false;
	public  $error = false;

	abstract function connect($server, $port, $user,$pass,$db);
	abstract function q($sql);
	abstract function escape($str);
	abstract function close();

	function __construct($server, $port, $user,$pass,$db,$install = false) {
		if(($install) && (! $this->install($server, $port, $user,$pass,$db))) {
			return;
		}
		$this->connect($server, $port, $user,$pass,$db);
	}

	function get_null_date() {
		return static::NULL_DATE;
	}
	
	function get_install_script() {
		return static::INSTALL_SCRIPT;
	}
	
	function utcnow() {
		return static::UTC_NOW;
	}

	function install($server,$user,$pass,$db) {
		if (!(strlen($server) && strlen($user))){
			$this->connected = false;
			$this->db = null;
			return false;
		}

		if(strlen($server) && ($server !== 'localhost') && ($server !== '127.0.0.1')) {
			if(! dns_get_record($server, DNS_A + DNS_CNAME + DNS_PTR)) {
				$this->error = sprintf( t('Cannot locate DNS info for database server \'%s\''), $server);
				$this->connected = false;
				$this->db = null;
				return false;
			}
		}
		return true;
	}


	function dbg($dbg) {
		$this->debug = $dbg;
	}

	function __destruct() {
		if($this->db && $this->connected) {
			$this->close();
		}
	}

	function quote_interval($txt) {
		return $txt;
	}
	
	function optimize_table($table) {
		q('OPTIMIZE TABLE '.$table);
	}
	
	function concat($fld, $sep) {
		return 'GROUP_CONCAT(DISTINCT '.$fld.' SEPARATOR \''.$sep.'\')';
	}
	
	function escapebin($str) {
		return $this->escape($str);
	}
	
	function unescapebin($str) {
		return $str;
	}
	
}



function printable($s) {
	$s = preg_replace("~([\x01-\x08\x0E-\x0F\x10-\x1F\x7F-\xFF])~",".", $s);
	$s = str_replace("\x00",'.',$s);
	if(x($_SERVER,'SERVER_NAME'))
		$s = escape_tags($s);
	return $s;
}

// Procedural functions

function dbg($state) {
	global $db;
	if($db)
	$db->dbg($state);
}


function dbesc($str) {
	global $db;
	if($db && $db->connected)
		return($db->escape($str));
	else
		return(str_replace("'","\\'",$str));
}
function dbescbin($str) {
	global $db;
	return $db->escapebin($str);
}

function dbunescbin($str) {
	global $db;
	return $db->unescapebin($str);
}

function dbescdate($date) {
	if(ACTIVE_DBTYPE == DBTYPE_POSTGRES && $date == '0000-00-00 00:00:00') {
		$date = NULL_DATE;
	} else if(ACTIVE_DBTYPE != DBTYPE_POSTGRES && $date == '0001-01-01 00:00:00') {
		$date = NULL_DATE;
	}
	return $date;
}

function db_quoteinterval($txt) {
	global $db;
	return $db->quote_interval($txt);
}

function dbesc_identifier($str) {
	global $db;
	return $db->escape_identifier($txt);
}

function db_utcnow() {
	global $db;
	return $db->utcnow();
}

function db_optimizetable($table) {
	global $db;
	$db->optimize_table($table);
}

function db_concat($fld, $sep) {
	global $db;
	return $db->concat($fld, $sep);
}

// Function: q($sql,$args);
// Description: execute SQL query with printf style args.
// Example: $r = q("SELECT * FROM `%s` WHERE `uid` = %d",
//                   'user', 1);


function q($sql) {

	global $db;
	$args = func_get_args();
	unset($args[0]);

	if($db && $db->connected) {
		$stmt = vsprintf($sql,$args);
		if($stmt === false) {
			if(version_compare(PHP_VERSION,'5.4.0') >= 0)
				logger('dba: vsprintf error: ' . print_r(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT,1),true));
			else
				logger('dba: vsprintf error: ' . print_r(debug_backtrace(),true));
		}
		return $db->q($stmt);
	}

	/**
	 *
	 * This will happen occasionally trying to store the 
	 * session data after abnormal program termination 
	 *
	 */
	logger('dba: no database: ' . print_r($args,true));
	return false; 

}

/**
 *
 * Raw db query, no arguments
 *
 */


function dbq($sql) {

	global $db;
	if($db && $db->connected)
		$ret = $db->q($sql);
	else
		$ret = false;
	return $ret;
}


// Caller is responsible for ensuring that any integer arguments to 
// dbesc_array are actually integers and not malformed strings containing
// SQL injection vectors. All integer array elements should be specifically 
// cast to int to avoid trouble. 



function dbesc_array_cb(&$item, $key) {
	if(is_string($item)) {
		if($item == '0000-00-00 00:00:00' && ACTIVE_DBTYPE == DBTYPE_POSTGRES)
			$item = '0001-01-01 00:00:00';
		$item = dbesc($item);
	}
}



function dbesc_array(&$arr) {
	if(is_array($arr) && count($arr)) {
		array_walk($arr,'dbesc_array_cb');
	}
}

function db_getfunc($f) {
	$lookup = array(
		'rand'=>array(
			DBTYPE_MYSQL=>'RAND()', 
			DBTYPE_POSTGRES=>'RANDOM()'
		),
		'utc_timestamp'=>array(
			DBTYPE_MYSQL=>'UTC_TIMESTAMP()',
			DBTYPE_POSTGRES=>"now() at time zone 'UTC'"
		),
		'regexp'=>array(
			DBTYPE_MYSQL=>'REGEXP',
			DBTYPE_POSTGRES=>'~'
		)
	);
	$f = strtolower($f);
	if(isset($lookup[$f]) && isset($lookup[$f][ACTIVE_DBTYPE]))
		return $lookup[$f][ACTIVE_DBTYPE];
		
	logger('Unable to abstract DB function "'. $f . '"', LOG_DEBUG);
	return $f;
}

