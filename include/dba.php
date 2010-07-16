<?php

// MySQL database class
//
// For debugging, insert 'dbg(x);' anywhere in the program flow.
// x = 1: display db success/failure following content
// x = 2: display full queries following content
// x = 3: display full queries using echo; which will mess up display
//        really bad but will return output in stubborn cases.
 
if(! class_exists('dba')) { 
class dba {

	private $debug = 0;
	private $db;

	function __construct($server,$user,$pass,$db,$install = false) {
		$this->db = @new mysqli($server,$user,$pass,$db);
		if((mysqli_connect_errno()) && (! install))
			system_unavailable();    
	}

	public function q($sql) {
		global $debug_text;
		
		if(! $this->db )
			return false;
		
		$result = @$this->db->query($sql);

		if($this->debug) {

			$mesg = '';

			if($this->db->mysqli->errno)
				$debug_text .=  $this->db->mysqli->error . EOL;

			if($result === false)
				$mesg = 'false';
			elseif($result === true)
				$mesg = 'true';
			else
				$mesg = $result->num_rows.' results' . EOL;
        
			$str =  'SQL = ' . $sql . EOL . 'SQL returned ' . $mesg . EOL;

			switch($this->debug) {
				case 3:
					echo $str;
					break;
				default:
					$debug_text .= $str;
					break;
			}
		}

		if(($result === true) || ($result === false))
			return $result;

		$r = array();
		if($result->num_rows) {
			while($x = $result->fetch_array(MYSQL_ASSOC))
				$r[] = $x;
			$result->free_result();
		}
    
		if($this->debug == 2)
			$debug_text .= print_r($r, true). EOL;
//			$debug_text .= quoted_printable_encode(print_r($r, true). EOL);
		elseif($this->debug == 3)
			echo print_r($r, true) . EOL ;
//			echo quoted_printable_encode(print_r($r, true) . EOL) ;

		return($r);
	}

	public function dbg($dbg) {
		$this->debug = $dbg;
	}

	public function escape($str) {
		return @$this->db->real_escape_string($str);
	}

	function __destruct() {
		@$this->db->close();
	}
}}

// Procedural functions
if(! function_exists('dbg')) { 
function dbg($state) {
	global $db;
	$db->dbg($state);
}}

if(! function_exists('dbesc')) { 
function dbesc($str) {
	global $db;
	return($db->escape($str));
}}


// Function: q($sql,$args);
// Description: execute SQL query with printf style args.
// Example: $r = q("SELECT * FROM `%s` WHERE `uid` = %d",
//                   'user', 1);

if(! function_exists('q')) { 
function q($sql) {

	global $db;
	$args = func_get_args();
	unset($args[0]);
	$ret = $db->q(vsprintf($sql,$args));
	return $ret;
}}


// Caller is responsible for ensuring that any integer arguments to 
// dbesc_array are actually integers and not malformed strings containing
// SQL injection vectors. All integer array elements should be specifically 
// cast to int to avoid trouble. 


if(! function_exists('dbesc_array_cb')) {
function dbesc_array_cb(&$item, $key) {
	if(is_string($item))
		$item = dbesc($item);
}}


if(! function_exists('dbesc_array')) {
function dbesc_array(&$arr) {
	if(is_array($arr) && count($arr)) {
		array_walk($arr,'dbesc_array_cb');
	}
}}		