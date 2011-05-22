<?php

require_once('include/api.php');

function api_content(&$a) {
	echo api_call($a);
	killme();
}



