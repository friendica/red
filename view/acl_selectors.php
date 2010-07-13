<?php


function group_select($selname,$selclass,$preselected = false) {

	$o = '';

	$o .= "<select name=\"{$selname}[]\" class=\"$selclass\" multiple=\"multiple\" size=\"4\" />\r\n";

	$r = q("SELECT * FROM `group` WHERE `uid` = %d",
		$_SESSION['uid']
	);

	if(count($r)) {
		foreach($r as $rr) {
			if((is_array($preselected)) && $in_array($rr['name'], $preselected))
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

	$r = q("SELECT `name` FROM `contact` WHERE `uid` = %d AND `self` = 0 AND `blocked` = 0 ",
		$_SESSION['uid']
	);

	if(count($r)) {
		foreach($r as $rr) {
			if((is_array($preselected)) && $in_array($rr['name'], $preselected))
				$selected = " selected=\"selected\" ";
			else
				$selected = '';
			$o .= "<option value=\"{$rr['name']}\" $selected >{$rr['name']}</option>\r\n";
		}
	
	}
	$o .= "</select>\r\n";


	return $o;
}


function populate_acl() {

	$o = '';

	$o .= "Allow Groups: " . group_select('group_allow','group_allow');
	$o .= "Allow Contacts: " . contact_select('contact_allow','contact_allow');
	$o .= "<br />\r\n";
	$o .= "Except Groups: " . group_select('group_deny','group_deny');
	$o .= "Except Contacts: " . contact_select('contact_deny','contact_deny');
	return $o;

}