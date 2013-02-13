<?php

require_once('include/socgraph.php');

function common_init(&$a) {

	if(argc() > 1)
		$which = argv(1);
	else {
		notice( t('Requested profile is not available.') . EOL );
		$a->error = 404;
		return;
	}

	$profile = 0;
	$channel = $a->get_channel();

	if((local_user()) && (argc() > 2) && (argv(2) === 'view')) {
		$which = $channel['channel_address'];
		$profile = argv(1);		
	}

	// Run profile_load() here to make sure the theme is set before
	// we start loading content

	profile_load($a,$which,$profile);

}

function common_aside(&$a) {
	if(! $a->profile['profile_uid'])
		return;

	profile_create_sidebar($a);
}


function common_content(&$a) {

	$o = '';

	if(! $a->profile['profile_uid'])
		return;

	$observer_hash = get_observer_hash();


	if(! perm_is_allowed($a->profile['profile_uid'],$observer_hash,'view_contacts')) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$o .= '<h2>' . t('Common connections') . '</h2>';

	$t = count_common_friends($a->profile['profile_uid'],$observer_hash);

	if(! $t)
		notice( t('No connections in common.') . EOL);
		return $o;
	}

	$r = common_friends($a->profile['profile_uid'],$observer_hash);

	if($r) {

	$tpl = get_markup_template('common_friends.tpl');

	foreach($r as $rr) {
			$o .= replace_macros($tpl,array(
			'$url' => $rr['url'],
			'$name' => $rr['name'],
			'$photo' => $rr['photo'],
			'$tags' => ''
		));
	}

	$o .= cleardiv();
	return $o;
}
