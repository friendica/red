<?php


function manage_post(&$a) {

	if(! local_user())
		return;

	$uid = local_user();
	$orig_record = $a->user;

	if((x($_SESSION,'submanage')) && intval($_SESSION['submanage'])) {
		$r = q("select * from user where uid = %d limit 1",
			intval($_SESSION['submanage'])
		);
		if(count($r)) {
			$uid = intval($r[0]['uid']);
			$orig_record = $r[0];
		}
	}

	$r = q("select * from manage where uid = %d",
		intval($uid)
	);

	$submanage = $r;

	$identity = ((x($_POST['identity'])) ? intval($_POST['identity']) : 0);
	if(! $identity)
		return;

	$limited_id = 0;
	$original_id = $uid;

	if(count($submanage)) {
		foreach($submanage as $m) {
			if($identity == $m['mid']) {
				$limited_id = $m['mid'];
				break;
			}
		}
	}

	if($limited_id) {
		$r = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
			intval($limited_id)
		);
	}
	else {
		$r = q("SELECT * FROM `user` WHERE `uid` = %d AND `email` = '%s' AND `password` = '%s' LIMIT 1",
			intval($identity),
			dbesc($orig_record['email']),
			dbesc($orig_record['password'])
		);
	}

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
	if(x($_SESSION,'submanage'))
		unset($_SESSION['submanage']);

	require_once('include/security.php');
	authenticate_success($r[0],true,true);

	if($limited_id)
		$_SESSION['submanage'] = $original_id;

	goaway($a->get_baseurl(true) . '/profile/' . $a->user['nickname']);
	// NOTREACHED
}



function manage_content(&$a) {

	if(! get_account_id()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$active = null;

	if(local_user()) {
		$r = q("select * from entity where entity_id = %d limit 1",
			intval(local_user())
		);

		if($r && count($r))
			$active = $r[0];
	}


	$o = replace_macros(get_markup_template('channels.tpl'), array(
		'$header' => t('Manage Profile Channels'),
		'$desc' => t('These are your Profile Channels. Select any Profile Channel to attach and make that the current channel.'),
		'$active' => $active,
	));


	return $o;

}
