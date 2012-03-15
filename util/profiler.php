<?php
function microtime_float()
{
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

function tick_event() {
	static $time = NULL; 
	
	if(NULL===$time) {
		//initialise time with now
		$time=microtime_float(); 
		
		q("INSERT INTO `profiling` (`function`, `file`, `line`, `class`, `time`) VALUES ('initialization', 'index.php', '-1', NULL, '%f'); ",
				floatval($time-$_SERVER['REQUEST_TIME']));
	}
	
	$elapsed=microtime_float()-$time; 
	
	$db_info=array_shift(debug_backtrace()); 
	$function=$db_info['function']; 
	$file=$db_info['file'];
	$line=$db_info['line'];
	$class=$db_info['class'];
	
	//save results
	q("INSERT INTO `profiling` (`function`, `file`, `line`, `class`, `time`) VALUES ('%s', '%s', '%d', '%s', '%f'); ", 
			dbesc($function), dbesc($file), intval($line), dbesc($class), floatval($time)); 
	
	//set time to now
	$time=microtime_float();
}

declare(ticks=1);
register_tick_function('tick_event');