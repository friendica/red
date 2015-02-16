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

	$x = z_fetch_url($url . '/ratingsearch/' . urlencode($hash));


	if($x['success'])
		$results = json_decode($x['body'],true);


	if((! $results) || (! $results['success'])) {

		notice('No results.');
		return;
	} 

	if(array_key_exists('xchan_hash',$results['target']))
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

	$a->data = array('target' => $results['target'], 'results' => array_merge($friends,$others));

	if(! $a->data['results']) {
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

	$site_target = ((array_key_exists('target',$a->data) && array_key_exists('site_url',$a->data['target'])) ?
		'<a href="' . $a->data['target']['site_url'] . '" >' . $a->data['target']['site_url'] . '</a>' : '');


	$o = replace_macros(get_markup_template('prep.tpl'),array(
		'$header' => t('Ratings'),
		'$rating_lbl' => t('Rating: ' ),
		'$website' => t('Website: '),
		'$site' => $site_target,
		'$rating_text_lbl' => t('Description: '),
		'$raters' => $a->data['results']
	));

	return $o;
}

			