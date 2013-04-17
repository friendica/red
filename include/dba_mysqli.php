<?php /** @file */

require_once('include/dba_driver.php');

abstract class dba_mysqli extends dba_driver {

	protected function connect($server,$user,$pass,$db) {
		$this->db = @new mysqli($server,$user,$pass,$db);
		if(! mysqli_connect_errno()) {
			$this->connected = true;
		}
		if($this->connected) {
			return true;
		}
		return false;
	}

	protected function q($sql) {
		if((! $this->db) || (! $this->connected))
			return false;

		$this->error = '';
		$result = @$this->db->query($sql);

		if($this->db->errno)
			$this->error = $this->db->error;

		if($result === false || $this->error) {
			logger('dba_mysqli: ' . printable($sql) . ' returned false.' . "\n" . $this->error);
			if(file_exists('dbfail.out'))
				file_put_contents('dbfail.out', datetime_convert() . "\n" . printable($sql) . ' returned false' . "\n" . $this->error . "\n", FILE_APPEND);
		}

		if(($result === true) || ($result === false))
			return $result;

		$r = array();
		if($result->num_rows) {
			while($x = $result->fetch_array(MYSQLI_ASSOC))
				$r[] = $x;
			$result->free_result();
			if($this->debug)
				logger('dba_mysqli: ' . printable(print_r($r,true)));
		}
		return $r;
	}

	protected function escape($str) {
		if($this->db && $this->connected) {
			return @$this->db->real_escape_string($str);
		}
	}

	protected function close() {
		if($this->db)
			$this->db->close();
		$this->connected = flase;
	}

}