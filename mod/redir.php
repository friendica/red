<?php

function redir_init(&$a) {

	if((! local_user()) || (! ($a->argc == 2)) || (! intval($a->argv[1])))
		goaway(z_root());
	$cid = $a->argv[1];
	$url = ((x($_GET,'url')) ? $_GET['url'] : '');

	$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($cid),
		intval(local_user())
	);

	if((! count($r)) || ($r[0]['network'] !== 'dfrn'))
		goaway(z_root());

	$dfrn_id = $orig_id = (($r[0]['issued-id']) ? $r[0]['issued-id'] : $r[0]['dfrn-id']);

	if($r[0]['duplex'] && $r[0]['issued-id']) {
		$orig_id = $r[0]['issued-id'];
		$dfrn_id = '1:' . $orig_id;
	}
	if($r[0]['duplex'] && $r[0]['dfrn-id']) {
		$orig_id = $r[0]['dfrn-id'];
		$dfrn_id = '0:' . $orig_id;
	}

	$sec = random_string();

	q("INSERT INTO `profile_check` ( `uid`, `cid`, `dfrn_id`, `sec`, `expire`)
		VALUES( %d, %s, '%s', '%s', %d )",
		intval(local_user()),
		intval($cid),
		dbesc($dfrn_id),
		dbesc($sec),
		intval(time() + 45)
	);

	logger('mod_redir: ' . $r[0]['name'] . ' ' . $sec, LOGGER_DEBUG); 
	$dest = (($url) ? '&destination_url=' . $url : '');
	goaway ($r[0]['poll'] . '?dfrn_id=' . $dfrn_id 
		. '&dfrn_version=' . DFRN_PROTOCOL_VERSION . '&type=profile&sec=' . $sec . $dest );
	
}
