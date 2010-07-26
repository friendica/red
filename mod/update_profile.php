<?php


require_once('mod/profile.php');

function update_profile_content(&$a) {

	header("Content-type: text/html");
	echo "<!DOCTYPE html><html><body>\r\n";
	echo (($_GET['msie'] == 1) ? '<div>' : '<section>');
	echo str_replace("\t",'       ',profile_content($a,true));
	echo (($_GET['msie'] == 1) ? '</div>' : '</section>');
	echo "</body></html>\r\n";
	killme();

}