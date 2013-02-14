<?php

require_once('include/socgraph.php');

function common_init(&$a) {

	if(argc() > 1 && intval(argv(1)))
		$channel_id = intval(argv(1));
	else {
		notice( t('No channel.') . EOL );
		$a->error = 404;
		return;
	}

	$x = q("select channel_address from channel where channel_id = %d limit 1",
		intval($channel_id)
	);

	if($x)
		profile_load($a,$x[0]['channel_address'],0);

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

	if(! $t) {
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
