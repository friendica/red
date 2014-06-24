<?php /** @file */

require_once('boot.php');
require_once('include/cli_startup.php');
require_once('include/socgraph.php');


function cli_suggest_run($argv, $argc){

	cli_startup();

	$a = get_app();

	update_suggestions();

}

if (array_search(__file__,get_included_files())===0){
  cli_suggest_run($argv,$argc);
  killme();
}

