<?php

function redir_init(&$a) {

	if((! local_user()) || (! ($a->argc == 2)) || (! intval($a->argv[1])))
		goaway($a->get_baseurl());
	$r = q("SELECT `issued-id`, `dfrn-id`, `duplex`, `poll` FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($a->argv[1]),
		intval($_SESSION['uid']));
	if(! count($r))
		goaway($a->get_baseurl());

	$dfrn_id = (($r[0]['duplex']) ? $r[0]['dfrn-id'] : $r[0]['issued-id']);

	q("INSERT INTO `profile_check` ( `uid`, `dfrn_id`, `expire`)
		VALUES( %d, '%s', %d )",
		intval($_SESSION['uid']),
		dbesc($dfrn_id),
		intval(time() + 45));
	goaway ($r[0]['poll'] . '?dfrn_id=' . $dfrn_id . '&type=profile');

	
	
}