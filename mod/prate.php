<?php


function prate_post(&$a) {
	if(! local_channel())
		return;

	$channel = $a->get_channel();

	$target = $_REQUEST['target'];
	if(! $target)
		return;

	if($target === $channel['channel_hash'])
		return;

	$rating = intval($_POST['rating']);
	if($rating < (-10))
		$rating = (-10);
	if($rating > 10)
		$rating = 10;

	$rating_text = escape_tags($_REQUEST['rating_text']);

	$z = q("select * from xlink where xlink_xchan = '%s' and xlink_xlink = '%s' and xlink_static = 1 limit 1",
		dbesc($channel['channel_hash']),
		dbesc($target)
	);
	if($z) {
		$record = $z[0]['xlink_id'];
		$w = q("update xlink set xlink_rating = '%d', xlink_rating_text = '%s', xlink_updated = '%s'
			where xlink_id = %d",
			intval($rating),
			dbesc($rating_text),
			dbesc(datetime_convert()),
			intval($record)
		);
	}
	else {
		$w = q("insert into xlink ( xlink_xchan, xlink_link, xlink_rating, xlink_rating_text, xlink_updated, xlink_static ) values ( '%s', '%s', %d, '%s', '%s', 1 ) ",
			dbesc($channel['channel_hash']),
			dbesc($target),
			intval($rating),
			dbesc($rating_text),
			dbesc(datetime_convert())
		);
		$z = q("select * from xlink where xlink_xchan = '%s' and xlink_link = '%s' and xlink_static = 1 limit 1",
			dbesc($channel['channel_hash']),
			dbesc($orig_record[0]['abook_xchan'])
		);
		if($z)
			$record = $z[0]['xlink_id'];
	}
	if($record) {
		proc_run('php','include/notifier.php','rating',$record);
	}

	$x = q("select abook_id from abook where abook_xchan = '%s' and abook_channel = %d limit 1",
		dbesc($target),
		intval($local_channel())
	);
	if($x) {
		$w = q("update abook set abook_rating = %d, abook_rating_text = '%s' where abook_xchan = '%s' and abook_channel = %d",
			intval($rating),
			dbesc($rating_text),
			dbesc($target),
			intval(local_channel())
		);
		$x = q("select * from abook where abook_xchan = '%s' and abook_channel = %d limit 1",
			dbesc($target),
			intval($local_channel())
		);
		if($x) {
			unset($x[0]['abook_id']);
			unset($x[0]['abook_account']);
			unset($x[0]['abook_channel']);
			build_sync_packet(0, array('abook' => array($x[0])));
		}
	}
	return;
}
			









