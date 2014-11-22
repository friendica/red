<?php

require_once('include/dba/dba_driver.php');


class dba_postgres extends dba_driver {
	const INSTALL_SCRIPT='install/schema_postgres.sql';
	const NULL_DATE = '0001-01-01 00:00:00';
	const UTC_NOW = "now() at time zone 'UTC'";
	
	function connect($server,$port,$user,$pass,$db) {
		if(!$port) $port = 5432;
		$connstr = 'host=' . $server . ' port='.$port . ' user=' . $user . ' password=' . $pass . ' dbname='. $db;
		$this->db = pg_connect($connstr);
		if($this->db !== false) {
			$this->connected = true;
		} else {
			$this->connected = false;
		}
		$this->q("SET standard_conforming_strings = 'off'; SET backslash_quote = 'on';"); // emulate mysql string escaping to prevent massive code-clobber
		return $this->connected;
	}
	
	function q($sql) {
		if((! $this->db) || (! $this->connected))
			return false;
		
		if(!strpos($sql, ';'))
			$sql .= ';';
			
		if(strpos($sql, '`')) // this is a hack. quoted identifiers should be replaced everywhere in the code with dbesc_identifier(), remove this once it is
			$sql = str_replace('`', '"', $sql);
			
		$this->error = '';
		$result = @pg_query($this->db, $sql);
		if(file_exists('db-allqueries.out')) {
			$bt = debug_backtrace();
			$trace = array();
			foreach($bt as $frame) {
				if(!empty($frame['file']) && @strstr($frame['file'], $_SERVER['DOCUMENT_ROOT']))
					$frame['file'] = substr($frame['file'], strlen($_SERVER['DOCUMENT_ROOT'])+1);
					
				$trace[] = $frame['file'] . ':' . $frame['function'] . '():' . $frame['line'] ;
			}
			$compact = join(', ', $trace);
			file_put_contents('db-allqueries.out', datetime_convert() . ": " . $sql . ' is_resource: '.var_export(is_resource($result), true).', backtrace: '.$compact."\n\n", FILE_APPEND);
		}

		if($result === false)
			$this->error = pg_last_error($this->db);

		if($result === false || $this->error) {
			//logger('dba_postgres: ' . printable($sql) . ' returned false.' . "\n" . $this->error);
			if(file_exists('dbfail.out'))
				file_put_contents('dbfail.out', datetime_convert() . "\n" . printable($sql) . ' returned false' . "\n" . $this->error . "\n", FILE_APPEND);
		}
			
		if(($result === true) || ($result === false))
			return $result;
		
		if(pg_result_status($result) == PGSQL_COMMAND_OK)
			return true;
			
		$r = array();
		if(pg_num_rows($result)) {
			while($x = pg_fetch_array($result, null, PGSQL_ASSOC))
				$r[] = $x;
			pg_free_result($result);
			if($this->debug)
				logger('dba_postgres: ' . printable(print_r($r,true)));
		}
		return $r;
	}
	
	function escape($str) {
		if($this->db && $this->connected) {
			$x = @pg_escape_string($this->db, $str);
			return $x;
		}
	}
	
	function escapebin($str) {
		return pg_escape_bytea($str);
	}
	
	function unescapebin($str) {
		return pg_unescape_bytea($str);
	}
	
	function close() {
		if($this->db)
			pg_close($this->db);
		$this->connected = false;
	}
	
	function quote_interval($txt) {
		return "'$txt'";
	}
	
	function escape_identifier($str) {
		return pg_escape_identifier($this->db, $str);
	}
	
	function optimize_table($table) {
		// perhaps do some equivalent thing here, vacuum, etc? I think this is the DBA's domain anyway. Applications should not need to muss with this.
		// for now do nothing without a compelling reason. function overrides default legacy mysql.
	}
	
	function concat($fld, $sep) {
		return 'string_agg(' . $fld . ',\'' . $sep . '\')';
	}
	
	function getdriver() {
		return 'pgsql';
	}
}