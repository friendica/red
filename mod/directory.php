<?php


function directory_content(&$a) {


	$tpl .= file_get_contents('view/directory_header.tpl');

	$o .= replace_macros($tpl, array(

	));

	$r = q("SELECT `profile`.*, `profile`.`uid` AS `profile_uid`, `user`.`nickname` FROM `profile` LEFT JOIN `user` ON `user`.`uid` = `profile`.`uid` WHERE `is-default` = 1 AND `publish` = 1 ORDER BY `name` ASC");
	if(count($r)) {

		$tpl = file_get_contents('view/directory_item.tpl');

		if(in_array('small', $a->argv))
			$photo = 'thumb';
		else
			$photo = 'photo';

		foreach($r as $rr) {

			$profile_link = $a->get_baseurl() . '/profile/' . ((strlen($rr['nickname'])) ? $rr['nickname'] : $rr['profile_uid']);
			$details = '';
			if(strlen($rr['locality']))
				$details .= $rr['locality'];
			if(strlen($rr['region'])) {
				if(strlen($rr['locality']))
					$details .= ', ';
				$details .= $rr['region'];
			}
			if(strlen($rr['country-name'])) {
				if(strlen($details))
					$details .= ', ';
				$details .= $rr['country-name'];
			}
			if(strlen($rr['dob']))
				$details .= '<br />Age: ' ; // . calculate age($rr['dob'])) ;
			if(strlen($rr['gender']))
				$details .= '<br />Gender: ' . $rr['gender'];

			$o .= replace_macros($tpl,array(
				'$id' => $rr['id'],
				'$profile-link' => $profile_link,
				'$photo' => $rr[$photo],
				'$alt-text' => $rr['name'],
				'$name' => $rr['name'],
				'$details' => $details  


			));

		}
		$o .= "<div class=\"directory-end\" ></div>\r\n";
	}
	else
		notice("No entries (some entries may be hidden).");

	return $o;
}