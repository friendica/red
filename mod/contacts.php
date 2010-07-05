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
				q("DELETE FROM `item` WHERE `contact-id` = %d LIMIT 1",
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

	switch($sort_type) {
		case DIRECTION_BOTH :
			$sql_extra = " AND `dfrn-id` != '' AND `ret-id` != '' ";
			break;
		case DIRECTION_IN :
			$sql_extra = " AND `dfrn-id` != '' AND `ret-id` = '' ";
			break;
		case DIRECTION_OUT :
			$sql_extra = " AND `dfrn-id` = '' AND `ret-id` != '' ";
			break;
		case DIRECTION_ANY :
		default:
			$sql_extra = '';
			break;
	}

	$r = q("SELECT * FROM `contact` WHERE `uid` = %d $sql_extra",
		intval($_SESSION['uid']));

	if(count($r)) {

		$tpl = file_get_contents("view/contact_template.tpl");

		foreach($r as $rr) {
			if($rr['self'])
				continue;
			$direction = '';
			if(strlen($rr['dfrn-id'])) {
				if(strlen($rr['ret-id'])) {
					$direction = DIRECTION_BOTH;
					$dir_icon = 'images/lrarrow.gif';
					$alt_text = 'Mutual Friendship';
				}
				else {
					$direction = DIRECTION_OUT;
					$dir_icon = 'images/rarrow.gif';
					$alt_text = 'You are a fan of';
				}
			}
			else {
				$direction = DIRECTION_IN;
				$dir_icon = 'images/larrow.gif';
				$alt_text = 'is a fan of yours';
			}

			$o .= replace_macros($tpl, array(
				'$id' => $rr['id'],
				'$alt_text' => $alt_text,
				'$dir_icon' => $dir_icon,
				'$thumb' => $rr['thumb'], 
				'$name' => $rr['name'],
				'$url' => (($direction != DIRECTION_IN) ? "redir/{$rr['id']}" : $rr['url'] )
			));
		}
	}
	return $o;


}