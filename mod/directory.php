<?php


function directory_content(&$a) {


	$tpl .= file_get_contents('view/directory_header');

	$o .= replace_macros($tpl);

	$r = q("SELECT * FORM `profile` WHERE `default` = 1 AND `publish` = 1");
	if(count($r)) {

		$tpl = file_get_contents('view/directory_item);

		foreach($r as $rr) {

			$o .= directory_block($a,$rr,$tpl);

		}
	}
	else
		notice("No entries (some entries may be hidden).");
}