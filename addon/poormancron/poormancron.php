<?php
/**
 * Poor Man Cron. Execute updates on pageviews
 *
 * Addon Name: poormancron
 *
 */

function poormancron_install() {
	register_hook('page_end', 'addon/poormancron/poormancron.php', 'poormancron_hook');
	register_hook('proc_run', 'addon/poormancron/poormancron.php','poormancron_procrun');
	logger("installed poormancron");
}

function poormancron_uninstall() {
	unregister_hook('page_end', 'addon/poormancron/poormancron.php', 'poormancron_hook');
	unregister_hook('proc_run', 'addon/poormancron/poormancron.php','poormancron_procrun');
	logger("removed poormancron");
}



function poormancron_hook($a,&$b) {
    $now = time();
    $lastupdate = get_config('poormancron', 'lastupdate');

    // 300 secs, 5 mins
    if (!$lastupdate || ($now-$lastupdate)>300) {
        set_config('poormancron','lastupdate', $now);
        $php_path = ((strlen($a->config['php_path'])) ? $a->config['php_path'] : 'php');
        proc_run($php_path,"include/poller.php");
    }
}

function poormancron_procrun($a, $argv) {
	logger("poormancron procrun ".implode(", ",$argv));
	array_shift($argv);
	$argc = count($argv);
	logger("poormancron procrun require_once ".basename($argv[0]));
	require_once(basename($argv[0]));
	$funcname=str_replace(".php", "", basename($argv[0]))."_run";
  
	$funcname($argv, $argc);
}



?>
