<?php

function redir_init(&$a) {

	if((! local_user()) || (! ($a->argc == 2)) || (! intval($a->argv[1])))
		goaway($a->get_baseurl());
	$r = q("SELECT `network`, `issued-id`, `dfrn-id`, `duplex`, `poll` FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($a->argv[1]),
		intval(local_user())
	);
	if((! count($r)) || ($r[0]['network'] !== 'dfrn'))
		goaway($a->get_baseurl());

	$dfrn_id = $orig_id = (($r[0]['issued-id']) ? $r[0]['issued-id'] : $r[0]['dfrn-id']);

	if($r[0]['duplex'] && $r[0]['issued-id']) {
		$orig_id = $r[0]['issued-id'];
		$dfrn_id = '1:' . $orig_id;
	}
	if($r[0]['duplex'] && $r[0]['dfrn-id']) {
		$orig_id = $r[0]['dfrn-id'];
		$dfrn_id = '0:' . $orig_id;
	}
	q("INSERT INTO `profile_check` ( `uid`, `dfrn_id`, `expire`)
		VALUES( %d, '%s', %d )",
		intval($_SESSION['uid']),
		dbesc($dfrn_id),
		intval(time() + 45));
	goaway ($r[0]['poll'] . '?dfrn_id=' . $dfrn_id 
		. '&dfrn_version=' . DFRN_PROTOCOL_VERSION . '&type=profile');
	
}
