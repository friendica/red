<?php
	// Tired of chasing typos and finding them after a commit. 
	// Run this from cmdline in basedir and quickly see if we've 
	// got any parse errors in our application files.

	include 'boot.php';

	$a = new App();

	$files = glob('mod/*.php');
	foreach($files as $file)
		include_once($file);


	$files = glob('include/*.php');
	foreach($files as $file)
		include_once($file);
