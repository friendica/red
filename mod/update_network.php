<?php


require_once('mod/network.php');


function update_network_content(&$a) {

	echo "<html>\r\n";
	echo network_content($a,true);
	echo "</html>\r\n";
	killme();

}