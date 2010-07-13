<?php


function group_select($selname,$selclass,$preselected = false) {

	$o = '';

	$o .= "<select name=\"{$selname}[]\" class=\"$selclass\" multiple=\"multiple\" size=\"4\" />\r\n";

	$r = q("SELECT * FROM `group` WHERE `uid` = %d ORDER BY `name` ASC",
		$_SESSION['uid']
	);

	if(count($r)) {
		foreach($r as $rr) {
			if((is_array($preselected)) && in_array($rr['name'], $preselected))
				$selected = " selected=\"selected\" ";
			else
				$selected = '';

			$o .= "<option value=\"{$rr['name']}\" $selected >{$rr['name']}</option>\r\n";
		}
	
	}
	$o .= "</select>\r\n";


	return $o;
}



function contact_select($selname,$selclass,$preselected = false) {

	$o = '';

	$o .= "<select name=\"{$selname}[]\" class=\"$selclass\" multiple=\"multiple\" size=\"4\" />\r\n";

	$r = q("SELECT `id`, `name` FROM `contact` WHERE `uid` = %d AND `self` = 0 AND `blocked` = 0 ORDER BY `name` ASC ",
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


function populate_acl() {

	$o = '';
	$o .= "<div id=\"acl-wrapper\">";
	$o .= "<div id=\"acl-permit-outer-wrapper\">";
	$o .= "<div id=\"acl-permit-text\">Visible To:</div>";
	$o .= "<div id=\"acl-permit-text-end\"></div>";
	$o .= "<div id=\"acl-permit-wrapper\">";
	$o .= "<div id=\"group_allow_wrapper\">";
	$o .= "<label id=\"acl-allow-group-label\" for=\"group_allow\" >Groups</label>";
	$o .= group_select('group_allow','group_allow');
	$o .= "</div>";
	$o .= "<div id=\"contact_allow_wrapper\">";
	$o .= "<label id=\"acl-allow-contact-label\" for=\"contact_allow\" >Contacts</label>";
	$o .= contact_select('contact_allow','contact_allow');
	$o .= "</div>";
	$o .= "</div>\r\n";
	$o .= "<div id=\"acl-allow-end\"></div>\r\n";
	$o .= "</div>";
	$o .= "<div id=\"acl-deny-outer-wrapper\">";
	$o .= "<div id=\"acl-deny-text\">Except For:</div>";
	$o .= "<div id=\"acl-deny-text-end\"></div>";
	$o .= "<div id=\"acl-deny-wrapper\">";
	$o .= "<div id=\"group_deny_wrapper\" >";
	$o .= "<label id=\"acl-deny-group-label\" for=\"group_deny\" >Groups</label>";
	$o .= group_select('group_deny','group_deny');
	$o .= "</div>";
	$o .= "<div id=\"contact_deny_wrapper\" >";
	$o .= "<label id=\"acl-deny-contact-label\" for=\"contact_deny\" >Contacts</label>";
	$o .= contact_select('contact_deny','contact_deny');
	$o .= "</div>";
	$o .= "</div>\r\n";
	$o .= "<div id=\"acl-deny-end\"></div>\r\n";
	$o .= "</div>";
	$o .= "</div>\r\n";
	$o .= "<div id=\"acl-wrapper-end\"></div>";
	return $o;

}