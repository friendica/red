<?php
/**
 * Poor Man Cron. Execute updates on pageviews
 *
 * Addon Name: poormancron
 *
 */

function poormancron_install() {

	register_hook('profile_sidebar', 'addon/poormancron/poormancron.php', 'poormancron_hook');
    register_hook('proc_run', 'addon/poormancron/poormancron.php','poormancron_procrun');

	logger("installed poormancron");
}

function poormancron_uninstall() {

	unregister_hook('profile_sidebar', 'addon/poormancron/poormancron.php', 'poormancron_hook');
    unregister_hook('proc_run', 'addon/poormancron/poormancron.php','poormancron_procrun');
    logger("removed poormancron");
}



function poormancron_hook($a,&$b) {
    $now = time();
    $lastupdate = get_config('poormancron', 'lastupdate');

    // 300 secs, 5 mins
    if (!$lastupdate || ($now-$lastupdate)>300) {
        set_config('poormancron','lastupdate', $now);
		$b .= "<img src='".$a->get_baseurl()."/queue_wrapper.php' width='1px' height='1px' style='display:none'>";        
        $b .= "<img src='".$a->get_baseurl()."/poller_wrapper.php' width='1px' height='1px' style='display:none'>";

    }
    
}


function poormancron_procrun($a, $args) {
	$argv = array_shift($args);
	$argc = count($argv);
	function killme(){
		// pass
	}
	require_once($argv[0]);	
}


?>
