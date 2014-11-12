<?php
/**
 * @file dba_driver.php
 * @brief some database related functions and abstract driver class.
 *
 * This file contains the abstract database driver class dba_driver and some
 * functions for working with databases.
 */

/**
 * @brief Returns the database driver object.
 *
 * If available it will use PHP's mysqli otherwise mysql driver.
 *
 * @param string $server DB server name
 * @param string $port DB port
 * @param string $user DB username
 * @param string $pass DB password
 * @param string $db database name
 * @param bool $install Defaults to false
 * @return null|dba_driver A database driver object (dba_mysql|dba_mysqli) or null if no driver found.
 */
function dba_factory($server, $port, $user, $pass, $db, $install = false) {
	$dba = null;

	if (class_exists('mysqli')) {
        if (is_null($port)) $port = ini_get("mysqli.default_port");
		require_once('include/dba/dba_mysqli.php');
		$dba = new dba_mysqli($server, $port, $user, $pass, $db, $install);
	}
	else {
		if (is_null($port)) $port = "3306";
		require_once('include/dba/dba_mysql.php');
		$dba = new dba_mysql($server, $port, $user, $pass, $db, $install);
	}

	return $dba;
}

/**
 * @brief abstract database driver class.
 *
 * This class gets extended by the real database driver classes, e.g. dba_mysql,
 * dba_mysqli.
 */
abstract class dba_driver {

	protected $debug = 0;
	protected $db;
	public  $connected = false;
	public  $error = false;

	/**
	 * @brief Connect to the database.
	 *
	 * This abstract function needs to be implemented in the real driver.
	 *
	 * @param string $server DB server name
	 * @param string $port DB port
	 * @param string $user DB username
	 * @param string $pass DB password
	 * @param string $db database name
	 * @return bool
	 */
	abstract function connect($server, $port, $user, $pass, $db);

	/**
	 * @brief Perform a DB query with the SQL statement $sql.
	 *
	 * This abstract function needs to be implemented in the real driver.
	 *
	 * @param string $sql The SQL query to execute
	 */
	abstract function q($sql);

	/**
	 * @brief Escape a string before being passed to a DB query.
	 *
	 * This abstract function needs to be implemented in the real driver.
	 *
	 * @param string $str The string to escape.
	 */
	abstract function escape($str);

	/**
	 * @brief Close the database connection.
	 *
	 * This abstract function needs to be implemented in the real driver.
	 */
	abstract function close();


	function __construct($server, $port, $user,$pass,$db,$install = false) {
		if(($install) && (! $this->install($server, $port, $user, $pass, $db))) {
			return;
		}
		$this->connect($server, $port, $user, $pass, $db);
	}

	function install($server, $user, $pass, $db) {
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

	/**
	 * @brief Sets the database driver's debugging state.
	 *
	 * @param int $dbg 0 to disable debugging
	 */
	function dbg($dbg) {
		$this->debug = $dbg;
	}

	function __destruct() {
		if($this->db && $this->connected) {
			$this->close();
		}
	}

} // end abstract dba_driver class



// Procedural functions

function printable($s) {
	$s = preg_replace("~([\x01-\x08\x0E-\x0F\x10-\x1F\x7F-\xFF])~",".", $s);
	$s = str_replace("\x00",'.',$s);
	if(x($_SERVER,'SERVER_NAME'))
		$s = escape_tags($s);
	return $s;
}

/**
 * @brief set database driver debugging state.
 *
 * @param int $state 0 to disable debugging
 */
function dbg($state) {
	global $db;

	if($db)
		$db->dbg($state);
}

/**
 * @brief Escape strings being passed to DB queries.
 *
 * Always escape strings being used in DB queries. This function returns the
 * escaped string. Integer DB parameters should all be proven integers by
 * wrapping with intval().
 *
 * @param string $str A string to pass to a DB query
 * @return Return an escaped string of the value to pass to a DB query.
 */
function dbesc($str) {
	global $db;

	if($db && $db->connected)
		return($db->escape($str));
	else
		return(str_replace("'", "\\'", $str));
}

/**
 * @brief Execute a SQL query with printf style args.
 *
 * printf style arguments %s and %d are replaced with variable arguments, which
 * should each be appropriately dbesc() or intval().
 * SELECT queries return an array of results or false if SQL or DB error. Other
 * queries return true if the command was successful or false if it wasn't.
 *
 * Example:
 *  $r = q("SELECT * FROM `%s` WHERE `uid` = %d",
 *         'user', 1);
 *
 * @param string $sql The SQL query to execute
 * @return bool|array
 */
function q($sql) {
	global $db;

	$args = func_get_args();
	unset($args[0]);

	if($db && $db->connected) {
		$stmt = vsprintf($sql, $args);
		if($stmt === false) {
			if(version_compare(PHP_VERSION, '5.4.0') >= 0)
				logger('dba: vsprintf error: ' .
					print_r(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1), true));
			else
				logger('dba: vsprintf error: ' . print_r(debug_backtrace(), true));
		}
		return $db->q($stmt);
	}

	/*
	 * This will happen occasionally trying to store the 
	 * session data after abnormal program termination 
	 */
	logger('dba: no database: ' . print_r($args,true));

	return false;
}

/**
 * @brief Raw DB query, no arguments.
 *
 * This function executes a raw DB query without any arguments.
 *
 * @param string $sql The SQL query to execute
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
	if(is_string($item))
		$item = dbesc($item);
}


function dbesc_array(&$arr) {
	if(is_array($arr) && count($arr)) {
		array_walk($arr,'dbesc_array_cb');
	}
}
