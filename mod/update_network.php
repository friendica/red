<?php


require_once('mod/network.php');


function update_network_content(&$a) {

	header("Content-type: text/html");
	echo "<!DOCTYPE html><html><body>\r\n";
	echo (($_GET['msie'] == 1) ? '<div>' : '<section>');
	echo str_replace("\t",'       ',network_content($a,true));
	echo (($_GET['msie'] == 1) ? '</div>' : '</section>');
	echo "</body></html>\r\n";
	killme();

}