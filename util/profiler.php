<?php
function microtime_float()
{
	return microtime(true);
}

function tick_event() {
	$db_info=debug_backtrace(); 
	$db_info=$db_info[1]; 
	$function=$db_info['function']; 
	$file=$db_info['file'];
	$line=$db_info['line'];
	$class=$db_info['class'];
	
	//save results
	q("INSERT INTO `profiling` (`function`, `file`, `line`, `class`, `time`) VALUES ('%s', '%s', '%d', '%s', '%f'); ", 
			dbesc($function), dbesc($file), intval($line), dbesc($class), microtime_float()*1000); 
}

register_tick_function('tick_event');
declare(ticks=50);