<?php

function pretheme_init(&$a) {
	
	if($_REQUEST['theme']) {
		$theme = $_REQUEST['theme'];
		$info = get_theme_info($theme);
		if($info) {
			// unfortunately there will be no translation for this string
			$desc = $info['description'] . ' ' . $info['version'];
		}
		else $desc = '';
		echo json_encode(array('img' => get_theme_screenshot($theme), 'desc' => $desc));
	}
	killme();
}
