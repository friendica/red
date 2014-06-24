<?php /** @file */

require_once('include/dba/dba_driver.php');

class dba_mysqli extends dba_driver {

	function connect($server, $port, $user,$pass,$db) {
		if($port)
			$this->db = new mysqli($server,$user,$pass,$db, $port);
		else
			$this->db = new mysqli($server,$user,$pass,$db);

		if(! mysqli_connect_errno()) {
			$this->connected = true;
		}
		if($this->connected) {
			return true;
		}
		$this->error = $this->db->connect_error;
		return false;
	}

	function q($sql) {
		if((! $this->db) || (! $this->connected))
			return false;

		$this->error = '';
		$result = $this->db->query($sql);

		if($this->db->errno)
			$this->error = $this->db->error;


		if($this->error) {
			logger('dba_mysqli: ERROR: ' . printable($sql) . "\n" . $this->error);
			if(file_exists('dbfail.out')) {
				file_put_contents('dbfail.out', datetime_convert() . "\n" . printable($sql) . "\n" . $this->error . "\n", FILE_APPEND);
			}
		}

		if(($result === true) || ($result === false)) {
			if($this->debug) {
				logger('dba_mysqli: DEBUG: returns ' . (($result) ? 'true' : 'false'));
			}
			return $result;
		}

		if($this->debug) {
			logger('dba_mysqli: DEBUG: ' . printable($sql) . ' returned ' . $result->num_rows . ' results.'); 
		}

		$r = array();
		if($result->num_rows) {
			while($x = $result->fetch_array(MYSQLI_ASSOC))
				$r[] = $x;
			$result->free_result();
			if($this->debug) {
				logger('dba_mysqli: ' . printable(print_r($r,true)));
			}
		}
		return $r;
	}

	function escape($str) {
		if($this->db && $this->connected) {
			return @$this->db->real_escape_string($str);
		}
	}

	function close() {
		if($this->db)
			$this->db->close();
		$this->connected = false;
	}

}