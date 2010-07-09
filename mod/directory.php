<?php


function directory_content(&$a) {


	$tpl .= file_get_contents('view/directory_header.tpl');

	$o .= replace_macros($tpl, array(

	));

	$r = q("SELECT `profile`.*, `profile`.`uid` AS `profile_uid`, `user`.`nickname` FROM `profile` LEFT JOIN `user` ON `user`.`uid` = `profile`.`uid` WHERE `is-default` = 1 AND `publish` = 1");
	if(count($r)) {

		$tpl = file_get_contents('view/directory_item.tpl');

		foreach($r as $rr) {
			$profile_link = $a->get_baseurl() . '/profile/' . ((strlen($rr['nickname'])) ? $rr['nickname'] : $rr['profile_uid']);


			$o .= replace_macros($tpl,array(
				'$id' => $rr['id'],
				'$profile-link' => $profile_link,
				'$photo' => $rr['photo'],
				'$alt-text' => $rr['name'],
				'$name' => $rr['name'],
				'$details' => $details   // FIXME


			));

		}
		$o .= "<div class=\"directory-end\" ></div>\r\n";
	}
	else
		notice("No entries (some entries may be hidden).");

	return $o;
}