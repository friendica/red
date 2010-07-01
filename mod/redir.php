<?php

function redir_init(&$a) {

	if((! local_user()) || (! ($a->argc == 2)) || (! intval($a->argv[1])))
		goaway($a->get_baseurl());
	$r = q("SELECT `dfrn-id`, `poll` FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($a->argv[1]),
		intval($_SESSION['uid']));
	if(! count($r))
		goaway($a->get_baseurl());
	q("INSERT INTO `profile_check` ( `uid`, `dfrn_id`, `expire`)
		VALUES( %d, '%s', %d )",
		intval($_SESSION['uid']),
		dbesc($r[0]['dfrn-id']),
		intval(time() + 30));
	goaway ($r[0]['poll'] . '?dfrn_id=' . $r[0]['dfrn-id'] . '&type=profile');

	
	
}