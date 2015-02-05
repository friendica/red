<?php

function prate_init(&$a) {
	if($_SERVER['REQUEST_METHOD'] === 'post')
		return;

	if(! local_channel())
		return;

	$channel = $a->get_channel();

	$target = argv(1);
	if(! $target)
		return;

	$r = q("select * from xlink where xlink_xchan = '%s' and xlink_link = '%s' and xlink_static = 1",
		dbesc($channel['channel_hash']),
		dbesc($target)
	);
	if($r)
		json_return_and_die(array('rating' => $r[0]['xlink_rating'],'rating_text' => $r[0]['xlink_rating_text']));
	killme();
}

function prate_post(&$a) {

	if(! local_channel())
		return;

	$channel = $a->get_channel();

	$target = trim($_REQUEST['target']);
	if(! $target)
		return;

	if($target === $channel['channel_hash'])
		return;

	$rating = intval($_POST['rating']);
	if($rating < (-10))
		$rating = (-10);
	if($rating > 10)
		$rating = 10;

	$rating_text = trim(escape_tags($_REQUEST['rating_text']));

	$signed = $target . '.' . $rating . '.' . $rating_text;

	$sig = base64url_encode(rsa_sign($signed,$channel['channel_prvkey']));


	$z = q("select * from xlink where xlink_xchan = '%s' and xlink_link = '%s' and xlink_static = 1 limit 1",
		dbesc($channel['channel_hash']),
		dbesc($target)
	);
	if($z) {
		$record = $z[0]['xlink_id'];
		$w = q("update xlink set xlink_rating = '%d', xlink_rating_text = '%s', xlink_sig = '%s', xlink_updated = '%s'
			where xlink_id = %d",
			intval($rating),
			dbesc($rating_text),
			dbesc($sig),
			dbesc(datetime_convert()),
			intval($record)
		);
	}
	else {
		$w = q("insert into xlink ( xlink_xchan, xlink_link, xlink_rating, xlink_rating_text, xlink_sig, xlink_updated, xlink_static ) values ( '%s', '%s', %d, '%s', '%s', '%s', 1 ) ",
			dbesc($channel['channel_hash']),
			dbesc($target),
			intval($rating),
			dbesc($rating_text),
			dbesc($sig),
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
		proc_run('php','include/ratenotif.php','rating',$record);
	}

	json_return_and_die(array('result' => true));;
}
			










