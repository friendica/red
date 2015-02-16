<?php


function ratingsearch_init(&$a) {

	$ret = array('success' => false);

	$dirmode = intval(get_config('system','directory_mode'));

	if($dirmode == DIRECTORY_MODE_NORMAL) {
		$ret['message'] = 'This site is not a directory server.';
		json_return_and_die($ret);
	}

	if(argc() > 1)
		$hash = argv(1);

	if(! $hash) {
		$ret['message'] = 'No channel identifier';
		json_return_and_die($ret);
	}

	if(strpos($hash,'@')) {
		$r = q("select * from hubloc where hubloc_addr = '%s' limit 1",
			dbesc($hash)
		);
		if($r)
			$hash = $r[0]['hubloc_hash'];
	} 

	$p = q("select * from xchan where xchan_hash like '%s'",
		dbesc($hash . '%')
	);

	if($p)
		$target = $p[0]['xchan_hash'];
	else {
		$p = q("select * from site where site_url like '%s' ",
			dbesc('%' . $hash)
		);
		if($p) {
			$target = strtolower($hash);
		}
		else {
			$ret['message'] = 'Rating target not found';
			json_return_and_die($ret);
		}
	}

	if($p)
		$ret['target']  = $p[0];

	$ret['success'] = true;

	$r = q("select * from xlink left join xchan on xlink_xchan = xchan_hash 
		where xlink_link = '%s' and xlink_rating != 0 and xlink_static = 1 order by xchan_name asc",
		dbesc($target)
	);

	if($r) {
		$ret['ratings'] = $r;
	}
	else
		$ret['ratings'] = array();
		
	json_return_and_die($ret);

}

