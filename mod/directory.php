<?php
function directory_init(&$a) {
	$a->set_pager_itemspage(60);
}

function directory_content(&$a) {
	$o .= '<script>	$(document).ready(function() { $(\'#nav-directory-link\').addClass(\'nav-selected\'); });</script>';

	$search = ((x($_GET,'search')) ? notags(trim($_GET['search'])) : '');

	$tpl .= file_get_contents('view/directory_header.tpl');

	

	$o .= replace_macros($tpl, array(
		'$search' => $search,
		'$finding' => (strlen($search) ? '<h4>' . t('Finding: ') . "'" . $search . "'" . '</h4>' : "")
	));

	if($search)
		$search = dbesc($search);
	$sql_extra = ((strlen($search)) ? " AND MATCH (`profile`.`name`, `user`.`nickname`, `locality`,`region`,`country-name`,`gender`,`marital`,`sexual`,`about`,`romance`,`work`,`education`) AGAINST ('$search' IN BOOLEAN MODE) " : "");


	$r = q("SELECT COUNT(*) AS `total` FROM `profile` LEFT JOIN `user` ON `user`.`uid` = `profile`.`uid` WHERE `is-default` = 1 AND `publish` = 1 AND `user`.`blocked` = 0 $sql_extra ");
	if(count($r))
		$a->set_pager_total($r[0]['total']);



	$r = q("SELECT `profile`.*, `profile`.`uid` AS `profile_uid`, `user`.`nickname`, `user`.`timezone` FROM `profile` LEFT JOIN `user` ON `user`.`uid` = `profile`.`uid` WHERE `is-default` = 1 AND `publish` = 1 AND `user`.`blocked` = 0 $sql_extra ORDER BY `name` ASC LIMIT %d , %d ",
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);
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
			if(strlen($rr['dob'])) {
				if(($years = age($rr['dob'],$rr['timezone'],'')) != 0)
					$details .= "<br />Age: $years" ; 
			}
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
		$o .= paginate($a);

	}
	else
		notice("No entries (some entries may be hidden).");

	return $o;
}