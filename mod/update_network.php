<?php

// See update_profile.php for documentation

require_once('mod/network.php');
require_once('include/group.php');

function update_network_content(&$a) {

	$profile_uid = intval($_GET['p']);
	$load = (((argc() > 1) && (argv(1) == 'load')) ? 1 : 0);
	header("Content-type: text/html");
	echo "<!DOCTYPE html><html><body>\r\n";
	echo ((array_key_exists('msie',$_GET) && $_GET['msie'] == 1) ? '<div>' : '<section>');


        $text = network_content($a,$profile_uid, $load);
        $pattern = "/<img([^>]*) src=\"([^\"]*)\"/";
        $replace = "<img\${1} dst=\"\${2}\"";
        $text = preg_replace($pattern, $replace, $text);

		if(! $load) {
			$replace = '<br />' . t('[Embedded content - reload page to view]') . '<br />';
    	    $pattern = "/<\s*audio[^>]*>(.*?)<\s*\/\s*audio>/i";
        	$text = preg_replace($pattern, $replace, $text);
	        $pattern = "/<\s*video[^>]*>(.*?)<\s*\/\s*video>/i";
    	    $text = preg_replace($pattern, $replace, $text);
        	$pattern = "/<\s*embed[^>]*>(.*?)<\s*\/\s*embed>/i";
	        $text = preg_replace($pattern, $replace, $text);
    	    $pattern = "/<\s*iframe[^>]*>(.*?)<\s*\/\s*iframe>/i";
        	$text = preg_replace($pattern, $replace, $text);
		}

        echo str_replace("\t",'       ',$text);
	echo ((array_key_exists('msie',$_GET) && $_GET['msie'] == 1) ? '</div>' : '</section>');
	echo "</body></html>\r\n";
//	logger('update_network: ' . $text);
	killme();

}