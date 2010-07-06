<?php


function contact_profile_assign($current) {

	$o = '';
	$o .= "<select id=\"contact_profile_selector\" name=\"profile_assign\" />";

	$r = q("SELECT `profile-name` FROM `profile` WHERE `uid` = %d",
                        intval($_SESSION['uid']));

	if(count($r)) {
		foreach($r as $rr) {
			$selected = (($rr['profile-name'] == $current) ? " selected=\"selected\" " : "");
			$o .= "<option value=\"{$rr['profile-name']}\" $selected >{$rr['profile-name']}</option>";
		}
	}
	$o .= "</select>";
	return $o;
}

