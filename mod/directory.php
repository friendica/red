<?php


function directory_content(&$a) {


	$tpl .= file_get_contents('view/directory_header');

	$o .= replace_macros($tpl);

	$r = q("SELECT * FROM `profile` WHERE `default` = 1 AND `publish` = 1");
	if(count($r)) {

		$tpl = file_get_contents('view/directory_item.tpl');

		foreach($r as $rr) {

			$o .= expand_macros($tpl,array(



			));

		}
	}
	else
		notice("No entries (some entries may be hidden).");
}