<?php


function group_select($selname,$selclass,$preselected = false,$size = 4) {

	$o = '';

	$o .= "<select name=\"{$selname}[]\" class=\"$selclass\" multiple=\"multiple\" size=\"$size\" />\r\n";

	$r = q("SELECT * FROM `group` WHERE `uid` = %d ORDER BY `name` ASC",
		$_SESSION['uid']
	);

	if(count($r)) {
		foreach($r as $rr) {
			if((is_array($preselected)) && in_array($rr['id'], $preselected))
				$selected = " selected=\"selected\" ";
			else
				$selected = '';

			$o .= "<option value=\"{$rr['id']}\" $selected >{$rr['name']}</option>\r\n";
		}
	
	}
	$o .= "</select>\r\n";


	return $o;
}



function contact_select($selname, $selclass, $preselected = false, $size = 4, $privmail = false) {

	$o = '';

	// When used for private messages, we limit correspondence to mutual friends and the selector
	// to one recipient. By default our selector allows multiple selects amongst all contacts.

	if($privmail) {
		$sql_extra = " AND `issued-id` != '' AND `dfrn-id` != '' ";
		$o .= "<select name=\"$selname\" class=\"$selclass\" size=\"$size\" />\r\n";
	}
	else {
		$sql_extra = '';
		$o .= "<select name=\"{$selname}[]\" class=\"$selclass\" multiple=\"multiple\" size=\"$size\" />\r\n";
	}

	$r = q("SELECT `id`, `name`, `url` FROM `contact` 
		WHERE `uid` = %d AND `self` = 0 AND `blocked` = 0 AND `pending` = 0 
		$sql_extra
		ORDER BY `name` ASC ",
		$_SESSION['uid']
	);

	if(count($r)) {
		foreach($r as $rr) {
			if((is_array($preselected)) && in_array($rr['id'], $preselected))
				$selected = " selected=\"selected\" ";
			else
				$selected = '';
			$o .= "<option value=\"{$rr['id']}\" $selected  title=\"{$rr['url']}\" >{$rr['name']}</option>\r\n";
		}
	
	}
	$o .= "</select>\r\n";


	return $o;
}

function fixacl(&$item) {
	$item = intval(str_replace(array('<','>'),array('',''),$item));
}

function populate_acl($user = null) {

	$allow_cid = $allow_gid = $deny_cid = $deny_gid = false;

	if(is_array($user)) {
		$allow_cid = ((strlen($user['allow_cid'])) 
			? explode('><', $user['allow_cid']) : array() );
		$allow_gid = ((strlen($user['allow_gid']))
			? explode('><', $user['allow_gid']) : array() );
		$deny_cid  = ((strlen($user['deny_cid']))
			? explode('><', $user['deny_cid']) : array() );
		$deny_gid  = ((strlen($user['deny_gid']))
			? explode('><', $user['deny_gid']) : array() );
		array_walk($allow_cid,'fixacl');
		array_walk($allow_gid,'fixacl');
		array_walk($deny_cid,'fixacl');
		array_walk($deny_gid,'fixacl');
	}

	$o = '';
	$o .= '<div id="acl-wrapper">';
	$o .= '<div id="acl-permit-outer-wrapper">';
	$o .= '<div id="acl-permit-text">' . t('Visible To:') . '</div>';
	$o .= '<div id="acl-permit-text-end"></div>';
	$o .= '<div id="acl-permit-wrapper">';
	$o .= '<div id="group_allow_wrapper">';
	$o .= '<label id="acl-allow-group-label" for="group_allow" >' . t('Groups') . '</label>';
	$o .= group_select('group_allow','group_allow',$allow_gid);
	$o .= '</div>';
	$o .= '<div id="contact_allow_wrapper">';
	$o .= '<label id="acl-allow-contact-label" for="contact_allow" >' . t('Contacts') . '</label>';
	$o .= contact_select('contact_allow','contact_allow',$allow_cid);
	$o .= '</div>';
	$o .= '</div>' . "\r\n";
	$o .= '<div id="acl-allow-end"></div>' . "\r\n";
	$o .= '</div>';
	$o .= '<div id="acl-deny-outer-wrapper">';
	$o .= '<div id="acl-deny-text">' . t('Except For:') . '</div>';
	$o .= '<div id="acl-deny-text-end"></div>';
	$o .= '<div id="acl-deny-wrapper">';
	$o .= '<div id="group_deny_wrapper" >';
	$o .= '<label id="acl-deny-group-label" for="group_deny" >' . t('Groups') . '</label>';
	$o .= group_select('group_deny','group_deny', $deny_gid);
	$o .= '</div>';
	$o .= '<div id="contact_deny_wrapper" >';
	$o .= '<label id="acl-deny-contact-label" for="contact_deny" >' . t('Contacts') . '</label>';
	$o .= contact_select('contact_deny','contact_deny', $deny_cid);
	$o .= '</div>';
	$o .= '</div>' . "\r\n";
	$o .= '<div id="acl-deny-end"></div>' . "\r\n";
	$o .= '</div>';
	$o .= '</div>' . "\r\n";
	$o .= '<div id="acl-wrapper-end"></div>' . "\r\n";
	return $o;

}