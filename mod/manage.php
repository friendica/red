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
	unset($_SESSION['return_url']);


	require_once('include/security.php');
	authenticate_success($r[0],true,true);

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
