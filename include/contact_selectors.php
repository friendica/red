<?php


function contact_profile_assign($current,$foreign_net) {

	$o = '';

	$disabled = (($foreign_net) ? ' disabled="true" ' : '');

	$o .= "<select id=\"contact-profile-selector\" $disabled name=\"profile-assign\" />\r\n";

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
		0 => t('Unknown | Not categorised'),
		1 => t('Block immediately'),
		2 => t('Shady, spammer, self-marketer'),
		3 => t('Known to me, but no opinion'),
		4 => t('OK, probably harmless'),
		5 => t('Reputable, has my trust')
	);

	foreach($rep as $k => $v) {
		$selected = (($k == $current) ? " selected=\"selected\" " : "");
		$o .= "<option value=\"$k\" $selected >$v</option>\r\n";
	}
	$o .= "</select>\r\n";
	return $o;
}


function contact_poll_interval($current, $disabled = false) {

	$dis = (($disabled) ? ' disabled="disabled" ' : '');
	$o = '';
	$o .= "<select id=\"contact-poll-interval\" name=\"poll\" $dis />" . "\r\n";

	$rep = array(
		0 => t('Frequently'),
		1 => t('Hourly'),
		2 => t('Twice daily'),
		3 => t('Daily'),
		4 => t('Weekly'),
		5 => t('Monthly')
	);

	foreach($rep as $k => $v) {
		$selected = (($k == $current) ? " selected=\"selected\" " : "");
		$o .= "<option value=\"$k\" $selected >$v</option>\r\n";
	}
	$o .= "</select>\r\n";
	return $o;
}


function network_to_name($s) {

	call_hooks('network_to_name', $s);

	return str_replace(array(NETWORK_DFRN,NETWORK_OSTATUS,NETWORK_FEED,NETWORK_MAIL,NETWORK_DIASPORA,NETWORK_FACEBOOK,NETWORK_ZOT),
		array(t('Friendika'),t('OStatus'),t('RSS/Atom'),t('Email'),t('Diaspora'),t('Facebook'),t('Zot!')),$s);

}
