<?php
/**
 * Name: Poor Man Cron
 * Description: Execute updates on pageviews, without the need of commandline php
 * Version: 1.2
 * Author: Fabio Comuni <http://kirgroup.com/profile/fabrix>
 */

function poormancron_install() {
	// check for command line php
	$a = get_app();
	$ex = Array();
	$ex[0] = ((x($a->config,'php_path')) && (strlen($a->config['php_path'])) ? $a->config['php_path'] : 'php');
	$ex[1] = dirname(dirname(dirname(__file__)))."/testargs.php";
	$ex[2] = "test";
	$out = exec(implode(" ", $ex));
	if ($out==="test") {
		set_config('poormancron','usecli',1);
		logger("poormancron will use cli php");
	} else {
		set_config('poormancron','usecli',0);
		logger("poormancron will NOT use cli php");
	}
	
	register_hook('page_end', 'addon/poormancron/poormancron.php', 'poormancron_hook');
	register_hook('proc_run', 'addon/poormancron/poormancron.php','poormancron_procrun');
	logger("installed poormancron");
}

function poormancron_uninstall() {
	unregister_hook('page_end', 'addon/poormancron/poormancron.php', 'poormancron_hook');
	unregister_hook('proc_run', 'addon/poormancron/poormancron.php','poormancron_procrun');
	logger("removed poormancron");
}



function poormancron_hook(&$a,&$b) {
    $now = time();
    $lastupdate = get_config('poormancron', 'lastupdate');

    // 300 secs, 5 mins
    if (!$lastupdate || ($now-$lastupdate)>300) {
        set_config('poormancron','lastupdate', $now);
        proc_run('php',"include/poller.php");
    }
}

function poormancron_procrun(&$a, &$arr) {
	if (get_config('poormancron','usecli')==1) return;
	$argv = $arr['args'];
	$arr['run_cmd'] = false;
	logger("poormancron procrun ".implode(", ",$argv));
	array_shift($argv);
	$argc = count($argv);
	logger("poormancron procrun require_once ".basename($argv[0]));
	require_once(basename($argv[0]));
	$funcname=str_replace(".php", "", basename($argv[0]))."_run";
  
	$funcname($argv, $argc);
}


?>
