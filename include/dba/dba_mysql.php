<?php

require_once('include/dba/dba_driver.php');


class dba_mysql extends dba_driver {

	function connect($server, $port, $user,$pass,$db) {
		$this->db = mysql_connect($server.":".$port,$user,$pass);
		if($this->db && mysql_select_db($db,$this->db)) {
			$this->connected = true;
		}
		if($this->connected) {
			return true;
		}
		return false;
	}


	function q($sql) {
		if((! $this->db) || (! $this->connected))
			return false;

		$this->error = '';
		$result = @mysql_query($sql,$this->db);


		if(mysql_errno($this->db))
			$this->error = mysql_error($this->db);

		if($result === false || $this->error) {
			logger('dba_mysql: ' . printable($sql) . ' returned false.' . "\n" . $this->error);
			if(file_exists('dbfail.out'))
				file_put_contents('dbfail.out', datetime_convert() . "\n" . printable($sql) . ' returned false' . "\n" . $this->error . "\n", FILE_APPEND);
		}

		if(($result === true) || ($result === false))
			return $result;

		$r = array();
		if(mysql_num_rows($result)) {
			while($x = mysql_fetch_array($result,MYSQL_ASSOC))
				$r[] = $x;
			mysql_free_result($result);
			if($this->debug)
				logger('dba_mysql: ' . printable(print_r($r,true)));
		}
		return $r;
	}

	function escape($str) {
		if($this->db && $this->connected) {
			return @mysql_real_escape_string($str,$this->db);
		}
	}

	function close() {
		if($this->db)
			mysql_close($this->db);
		$this->connected = false;
	}
	
	function getdriver() {
		return 'mysql';
	}

}	
