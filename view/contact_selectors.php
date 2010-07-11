<?php


function contact_profile_assign($current) {

	$o = '';
	$o .= "<select id=\"contact-profile-selector\" name=\"profile-assign\" />\r\n";

	$r = q("SELECT `id`, `profile-name` FROM `profile` WHERE `uid` = %d",
                        intval($_SESSION['uid']));

	if(count($r)) {
		foreach($r as $rr) {
			$selected = (($rr['id'] == $current) ? " selected=\"selected\" " : "");
			$o .= "<option value=\"{$rr['id']}\" $selected >{$rr['profile-name']}</option>\r\n";
		}
	}
	$o .= "</select>\r\n";
	return $o;
}


function contact_reputation($current) {

	$o = '';
	$o .= "<select id=\"contact-reputation-selector\" name=\"reputation\" />\r\n";

	$rep = array(
		0 => "Unknown | Not categorised",
		1 => "Block immediately",
		2 => "Shady, spammer, self-marketer",
		3 => "Known to me, but no opinion",
		4 => "OK, probably harmless",
		5 => "Reputable, has my trust"
	);

	foreach($rep as $k => $v) {
		$selected = (($k == $current) ? " selected=\"selected\" " : "");
		$o .= "<option value=\"$k\" $selected >$v</option>\r\n";
	}
	$o .= "</select>\r\n";
	return $o;
}



