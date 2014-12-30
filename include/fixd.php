<?php

	require_once('include/cli_startup.php');

	cli_startup();

	$rand = db_getfunc('RAND');
	$r = q("select xchan_addr, hubloc_url from xchan left join hubloc on hubloc_hash = xchan_hash where xchan_network like '%%diaspora%%' order by $rand");

	if(! $r)
		killme();

	require_once('include/network.php');
	$total = 0;
	foreach ($r as $rr) {
		if($rr['hubloc_url']) {
			continue;
		}
		$total ++;
	}

	print $total . "\n";

	foreach ($r as $rr) {
		if($rr['hubloc_url']) {
			continue;
		}

		$webbie = $rr['xchan_addr'];
		print $webbie . ' ';

		discover_by_webbie($webbie);
	}
