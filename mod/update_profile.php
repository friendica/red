<?php


require_once('mod/profile.php');


function update_profile_content(&$a) {

	echo "<html>\r\n";
	echo profile_content($a,true);
	echo "</html>\r\n";
	killme();

}