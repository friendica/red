<?php

function pretheme_init(&$a) {
	if($_REQUEST['theme']) echo json_encode(array('img' => get_theme_screenshot($_REQUEST['theme'])));
	killme();
}
