<?php


function rate_init(&$a) {

	if(! local_channel())
		return;

	$channel = $a->get_channel();

	$target = $_REQUEST['target'];
	if(! $target)
		return;

	$a->data['target'] = $target;

	if($target) {
		$r = q("SELECT * FROM xchan where xchan_hash like '%s' LIMIT 1",
			dbesc($target)
		);
		if($r) {
			$a->poi = $r[0];
		}
	}


	return;

}


function rate_post(&$a) {

	if(! local_channel())
		return;

	if(! $a->data['target'])
		return;

	if(! $_REQUEST['execute'])
		return;

	$channel = $a->get_channel();

	$rating = intval($_POST['rating']);
	if($rating < (-10))
		$rating = (-10);
	if($rating > 10)
		$rating = 10;

	$rating_text = trim(escape_tags($_REQUEST['rating_text']));

	$signed = $a->data['target'] . '.' . $rating . '.' . $rating_text;

	$sig = base64url_encode(rsa_sign($signed,$channel['channel_prvkey']));

	$z = q("select * from xlink where xlink_xchan = '%s' and xlink_link = '%s' and xlink_static = 1 limit 1",
		dbesc($channel['channel_hash']),
		dbesc($a->data['target'])
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
			dbesc($a->data['target']),
			intval($rating),
			dbesc($rating_text),
			dbesc($sig),
			dbesc(datetime_convert())
		);
		$z = q("select * from xlink where xlink_xchan = '%s' and xlink_link = '%s' and xlink_static = 1 limit 1",
			dbesc($channel['channel_hash']),
			dbesc($a->data['target'])
		);
		if($z)
			$record = $z[0]['xlink_id'];
	}

	if($record) {
		proc_run('php','include/ratenotif.php','rating',$record);
	}

}



function rate_content(&$a) {

	if(! local_channel()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

//	if(! $a->data['target']) {
//		notice( t('No recipients.') . EOL);
//		return;
//	}

	$poco_rating = get_config('system','poco_rating_enable');
	if((! $poco_rating) && ($poco_rating !== false)) {
		notice('Ratings are disabled on this site.');
		return;
	}

	$channel = $a->get_channel();

	$r = q("select * from xlink where xlink_xchan = '%s' and xlink_link = '%s' and xlink_static = 1",
		dbesc($channel['channel_hash']),
		dbesc($a->data['target'])
	);
	if($r)
		$a->data['xlink'] = $r[0];				

	$rating_val = $r[0]['xlink_rating'];
	$rating_text = $r[0]['xlink_rating_text'];


	// if unset default to enabled
	if($poco_rating === false)
		$poco_rating = true;

	if($poco_rating) {
		$rating = replace_macros(get_markup_template('rating_slider.tpl'),array(
			'$min' => -10,
			'$val' => $rating_val
		));
	}
	else {
		$rating = false;
	}

	$o = replace_macros(get_markup_template('rating_form.tpl'),array(
		'$header' => t('Rating'),
		'target' => $a->data['target'],
		'$tgt_name' => (($a->poi && $a->poi['xchan_name']) ? $a->poi['xchan_name'] : sprintf( t('Remote Channel [%s] (not yet known on this site)'), substr($a->data['target'],0,16))),
		'$lbl_rating'     => t('Rating (this information is public)'),
		'$lbl_rating_txt' => t('Optionally explain your rating (this information is public)'),
		'$rating_txt'     => $rating_text,
		'$rating'         => $rating,
		'$rating_val'     => $rating_val,
		'$slide'          => $slide,
		'$submit' => t('Submit')
	));

	return $o;

}