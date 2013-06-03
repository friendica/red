<?php

// Tool to assist with figuring out what variables are passed to templates. 
// It will take a source php file and print all the template calls it finds, including the passed args. 
// With no args it will enumerate all templates in boot.php, include/* and mod/*
// This is a quick hack and far from perfect (there's a template call in boot.php that buggers the regex from the get-go) 
// but is one step towards template documentation. 


if($argc > 1) {
	echo "{$argv[1]}: templates\n";
	print_template($argv[1]);
}
else {


	echo 'boot.php: templates' . "\n";
	print_template('boot.php');

	$files = glob('include/*.php');
	foreach($files as $file) {
		echo $file . ': templates'. "\n";
		print_template($file);
	}

	$files = glob('mod/*.php');
	foreach($files as $file) {
		echo $file . ': templates'. "\n";
		print_template($file);
	}
}

function print_template($s) {
	$x = file_get_contents($s);

	$cnt = preg_match_all('/replace_macros(.*?)\)\;/ism',$x,$matches);

	if($cnt) {
		print_r($matches[0]);

	}

}