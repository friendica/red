<?php


function select_contact_profile($current) {

	$o = '';
	$o .= "<select id=\"contact_profile_selector\" name=\"profile_id\" />";

	$r = q("SELECT `id` FROM `profile` WHERE `uid` = %d",
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

