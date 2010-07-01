<?php
function edit_contact(&$a,$contact_id) {

}

function contacts_post(&$a) {

	
	if(($a->argc != 3) || (! local_user()))
		return;

	$contact_id = intval($a->argv[1]);
	if(! $contact_id)
		return;

	$cmd = $a->argv[2];

	$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($contact_id),
		intval($_SESSION['uid'])
	);

	if(! count($r))
		return;
	$photo = str_replace('-4.jpg', '' , $r[0]['photo']);
	$photos = q("SELECT `id` FROM `photo` WHERE `resource-id` = '%s' AND `uid` = %d",
			dbesc($photo),
			intval($_SESSION['uid'])
	);
	

	switch($cmd) {
		case 'edit':
				edit_contact($a,$contact_id);
			break;
		case 'block':
			$r = q("UPDATE `contact` SET `blocked` = 1 WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($contact_id),
				intval($_SESSION['uid'])
			);
			if($r)
				$_SESSION['sysmsg'] .= "Contact has been blocked." . EOL;
			break;
		case 'drop':
			$r = q("DELETE FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($contact_id),
				intval($_SESSION['uid']));
			if(count($photos)) {
				foreach($photos as $p) {
					q("DELETE FROM `photos` WHERE `id` = %d LIMIT 1",
						$p['id']);
				}
			}
			if($intval($contact_id))
				q("DELETE * FROM `item` WHERE `contact-id` = %d ",
					intval($contact_id)
				);

			break;
		default:
			return;
			break;
	}

}











function contacts_content(&$a) {
	if(! local_user()) {
		$_SESSION['sysmsg'] .= "Permission denied." . EOL;
		return;
	}

	if(($a->argc2 == 2) && ($a->argv[1] == 'all'))
		$sql_extra = '';
	else
		$sql_extra = " AND `blocked` = 0 ";

	$tpl = file_get_contents("view/contacts-top.tpl");
	$o .= replace_macros($tpl,array(
		'$hide_url' => ((strlen($sql_extra)) ? 'contacts/all' : 'contacts' ),
		'$hide_text' => ((strlen($sql_extra)) ? 'Show Blocked Connections' : 'Hide Blocked Connections')
	)); 


	$r = q("SELECT * FROM `contact` WHERE `uid` = %d",
		intval($_SESSION['uid']));

	if(count($r)) {

		$tpl = file_get_contents("view/contact_template.tpl");

		foreach($r as $rr) {
			if($rr['self'])
				continue;
			$o .= replace_macros($tpl, array(
				'$id' => $rr['id'],
				'$thumb' => $rr['thumb'], 
				'$name' => $rr['name'],
				'$url' => $rr['url']
			));
		}
	}
	return $o;


}