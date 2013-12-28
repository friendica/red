<?php

function sslify_init(&$a) {
	$x = z_fetch_url($_REQUEST['url']);
	if($x['success']) {
		$h = explode("\n",$x['headers']);
		foreach ($h as $l) {
			list($k,$v) = array_map("trim", explode(":", trim($l), 2));
			$hdrs[$k] = $v;
		}
		if (array_key_exists('Content-Type', $hdrs))
			$type = $hdrs['Content-Type'];
	
		header('Content-Type: ' . $type);
		echo $x['body'];
		killme();
	}
	killme();
	// for some reason when this fallback is in place - it gets triggered
	// often, (creating mixed content exceptions) even though there is 
	// nothing obvious missing on the page when we bypass it. 
	goaway($_REQUEST['url']);
}

