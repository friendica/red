<?php
	// Tired of chasing typos and finding them after a commit. 
	// Run this from cmdline in basedir and quickly see if we've 
	// got any parse errors in our application files.

	include 'boot.php';

	error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
	ini_set('display_errors', '1');
	ini_set('log_errors','0');


	$a = new App();

	$files = glob('mod/*.php');
	foreach($files as $file)
		include_once($file);


	$files = glob('include/*.php');
	foreach($files as $file)
		include_once($file);
