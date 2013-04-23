<?php


/**
* @package util
*/

require_once('boot.php');
require_once('include/cli_startup.php');

cli_startup();

echo "Old DB VERSION: " . $build . "\n";
echo "New DB VERSION: " . DB_UPDATE_VERSION . "\n";


if($build != DB_UPDATE_VERSION) {
	echo "Updating database...";
	check_config($a);
	echo "Done\n";
}

