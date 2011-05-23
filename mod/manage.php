<?php


function manage_post(&$a) {

	if(! local_user() || ! is_array($a->identities))
		return;

	$identity = ((x($_POST['identity'])) ? intval($_POST['identity']) : 0);
	if(! $identity)
		return;

	$r = q("SELECT * FROM `user` WHERE `uid` = %d AND `email` = '%s' AND `password` = '%s' LIMIT 1",
		intval($identity),
		dbesc($a->user['email']),
		dbesc($a->user['password'])
	);

	if(! count($r))
		return;

	unset($_SESSION['authenticated']);
	unset($_SESSION['uid']);
	unset($_SESSION['visitor_id']);
	unset($_SESSION['administrator']);
	unset($_SESSION['cid']);
	unset($_SESSION['theme']);
	unset($_SESSION['page_flags']);


	$_SESSION['uid'] = $r[0]['uid'];
	$_SESSION['theme'] = $r[0]['theme'];
	$_SESSION['authenticated'] = 1;
	$_SESSION['page_flags'] = $r[0]['page-flags'];
	$_SESSION['my_url'] = $a->get_baseurl() . '/profile/' . $r[0]['nickname'];

	info( sprintf( t("Welcome back %s") , $r[0]['username']) . EOL);
	$a->user = $r[0];

	if(strlen($a->user['timezone'])) {
		date_default_timezone_set($a->user['timezone']);
		$a->timezone = $a->user['timezone'];
	}

	$r = q("SELECT `uid`,`username` FROM `user` WHERE `password` = '%s' AND `email` = '%s'",
		dbesc($a->user['password']),
		dbesc($a->user['email'])
	);
	if(count($r))
		$a->identities = $r;

	$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 1 LIMIT 1",
		intval($_SESSION['uid']));
	if(count($r)) {
		$a->contact = $r[0];
		$a->cid = $r[0]['id'];
		$_SESSION['cid'] = $a->cid;
	}

	q("UPDATE `user` SET `login_date` = '%s' WHERE `uid` = %d LIMIT 1",
		dbesc(datetime_convert()),
		intval($_SESSION['uid'])
	);

	header('X-Account-Management-Status: active; name="' . $a->user['username'] . '"; id="' . $a->user['nickname'] .'"');
	goaway($a->get_baseurl() . '/profile/' . $a->user['nickname']);
	// NOTREACHED
}



function manage_content(&$a) {

	if(! local_user() || ! is_array($a->identities)) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$r = q("SELECT * FROM `user` WHERE `email` = '%s' AND `password` = '%s'",
		dbesc($a->user['email']),
		dbesc($a->user['password'])
	);
	if(! count($r))
		return;


	$o = '<h3>' . t('Manage Identities and/or Pages') . '</h3>';

	
	$o .= '<div id="identity-manage-desc">' . t("\x28Toggle between different identities or community/group pages which share your account details.\x29") . '</div>';

	$o .= '<div id="identity-manage-choose">' . t('Select an identity to manage: ') . '</div>';

	$o .= '<div id="identity-selector-wrapper">' . "\r\n";
	$o .= '<form action="manage" method="post" >' . "\r\n";
	$o .= '<select name="identity" size="4">' . "\r\n";

	foreach($r as $rr) {
		$selected = (($rr['nickname'] === $a->user['nickname']) ? ' selected="selected" ' : '');
		$o .= '<option ' . $selected . 'value="' . $rr['uid'] . '">' . $rr['username'] . ' (' . $rr['nickname'] . ')</option>' . "\r\n";
	}

	$o .= '</select>' . "\r\n";
	$o .= '<div id="identity-select-break"></div>' . "\r\n";

	$o .= '<input id="identity-submit" type="submit" name="submit" value="' . t('Submit') . '" /></div></form>' . "\r\n";

	return $o;

}
