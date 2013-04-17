<?php /** @file */

abstract class dba_driver {

	abstract protected function connect($server,$user,$pass,$db);
	abstract protected function q($sql);
	abstract protected function escape($str);
	abstract protected function close();

}