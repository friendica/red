<?php

require_once('include/socgraph.php');
require_once('include/contact_widgets.php');


function suggest_init(&$a) {
	if(! local_user())
		return;

	if(x($_GET,'ignore')) {
		q("insert into xign ( uid, xchan ) values ( %d, '%s' ) ",
			intval(local_user()),
			dbesc($_GET['ignore'])
		);
	}

}
		




function suggest_content(&$a) {

	$o = '';
	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$_SESSION['return_url'] = $a->get_baseurl() . '/' . $a->cmd;

	$a->page['aside'] .= follow_widget();
	$a->page['aside'] .= findpeople_widget();


	$o .= '<h2>' . t('Friend Suggestions') . '</h2>';

	$r = suggestion_query(local_user(),get_observer_hash());

	if(! $r) {
		$o .= t('No suggestions available. If this is a new site, please try again in 24 hours.');
		return $o;
	}

	$tpl = get_markup_template('suggest_friends.tpl');

	foreach($r as $rr) {

		$connlnk = $a->get_baseurl() . '/follow/?url=' . $rr['xchan_addr'];

		$o .= replace_macros($tpl,array(
			'$url' => zid($rr['xchan_url']),
			'$name' => $rr['xchan_name'],
			'$photo' => $rr['xchan_photo_m'],
			'$ignlnk' => $a->get_baseurl() . '/suggest?ignore=' . $rr['xchan_hash'],
			'$conntxt' => t('Connect'),
			'$connlnk' => $connlnk,
			'$ignore' => t('Ignore/Hide')
		));
	}

	$o .= cleardiv();
//	$o .= paginate($a);
	return $o;
}
