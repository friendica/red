<?php

function prep_content(&$a) {


	$poco_rating = get_config('system','poco_rating_enable');
	// if unset default to enabled
	if($poco_rating === false)
		$poco_rating = true;

	if(! $poco_rating)
		return;

	if(argc() > 1)
		$hash = argv(1);

	if(! $hash) {
		notice('Must supply a channel identififier.');
		return;
	}

	$p = q("select * from xchan where xchan_hash like '%s'",
		dbesc($hash . '%')
	);

	$r = q("select * from xlink left join xchan on xlink_xchan = xchan_hash where xlink_link like '%s' and xlink_rating != 0",
		dbesc($hash . '%')
	);

	$ret = array();

	if($p && $r) {
		$ret['poi'] = $p[0];
		$ret['raters'] = $r;

		$o = replace_macros(get_markup_template('prep.tpl'),array(
			'$header' => t('Ratings'),
			'$poi' => $p[0],
			'$raters' => $r
		));

		return $o;
	}
	return '';
}

			