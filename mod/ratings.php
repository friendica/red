<?php

require_once('include/dir_fns.php');

function ratings_init(&$a) {

	if((get_config('system','block_public')) && (! local_channel()) && (! remote_channel())) {
		return;
	}

	if(local_channel())
		load_contact_links(local_channel());

	$dirmode = intval(get_config('system','directory_mode'));

	$x = find_upstream_directory($dirmode);
	if($x)
		$url = $x['url'];

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

	$results = false;

	$x = z_fetch_url($url . '/ratingsearch/' . $hash);


	if($x['success'])
		$results = json_decode($x['body'],true);


	if((! $results) || (! $results['success'])) {

		notice('No results.');
		return;
	} 

	$a->poi = $results['target'];

	$friends = array();
	$others = array();

	if($results['ratings']) {
		foreach($results['ratings'] as $n) {
			if(is_array($a->contacts) && array_key_exists($n['xchan_hash'],$a->contacts))
				$friends[] = $n;
			else
				$others[] = $n;
		}
	}

	$a->data = array_merge($friends,$others);

	if(! $a->data) {
		notice( t('No ratings') . EOL);
	}

	return;
}





function ratings_content(&$a) {

	if((get_config('system','block_public')) && (! local_channel()) && (! remote_channel())) {
		notice( t('Public access denied.') . EOL);
		return;
	}

	$poco_rating = get_config('system','poco_rating_enable');
	// if unset default to enabled
	if($poco_rating === false)
		$poco_rating = true;

	if(! $poco_rating)
		return;

	$o = replace_macros(get_markup_template('prep.tpl'),array(
		'$header' => t('Ratings'),
		'$rating_lbl' => t('Rating: ' ),
		'$rating_text_lbl' => t('Description: '),
		'$raters' => $a->data
	));

	return $o;
}

			