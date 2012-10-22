<?php
require_once("include/oembed.php");

function oembed_init(&$a){
	// logger('mod_oembed ' . $a->query_string, LOGGER_ALL);

	if(argc() > 1) {
		if (argv(1) == 'b2h'){
			$url = array( "", trim(hex2bin($_REQUEST['url'])));
			echo oembed_replacecb($url);
			killme();
		}
	
		elseif (argv(1) == 'h2b'){
			$text = trim(hex2bin($_REQUEST['text']));
			echo oembed_html2bbcode($text);
			killme();
		}
	
		else {
			echo "<html><body>";
			$url = base64url_decode($argv(1));
			$j = oembed_fetch_url($url);
			echo $j->html;
//		    logger('mod-oembed ' . $j->html, LOGGER_ALL);
			echo "</body></html>";
		}
	}
	killme();
}
