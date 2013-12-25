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

	goaway($_REQUEST['url']);
}

