<?php
	include 'boot.php';

	$a = new App();

	$files = glob('mod/*.php');
	foreach($files as $file)
		include_once($file);


	$files = glob('include/*.php');
	foreach($files as $file)
		include_once($file);
