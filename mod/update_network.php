<?php

// See update_profile.php for documentation

require_once('mod/network.php');
require_once('include/group.php');

function update_network_content(&$a) {

	$profile_uid = intval($_GET['p']);

	header("Content-type: text/html");
	echo "<!DOCTYPE html><html><body>\r\n";
	echo (($_GET['msie'] == 1) ? '<div>' : '<section>');


        $text = network_content($a,$profile_uid);
        $pattern = "/<img([^>]*) src=\"([^\"]*)\"/";
        $replace = "<img\${1} dst=\"\${2}\"";
        $text = preg_replace($pattern, $replace, $text);

        echo str_replace("\t",'       ',$text);
	echo (($_GET['msie'] == 1) ? '</div>' : '</section>');
	echo "</body></html>\r\n";
	killme();

}