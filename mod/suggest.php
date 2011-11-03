<?php

require_once('include/socgraph.php');
require_once('include/contact_widgets.php');


function suggest_init(&$a) {
	if(! local_user())
		return;

	if(x($_GET,'ignore') && intval($_GET['ignore'])) {
		q("insert into gcign ( uid, gcid ) values ( %d, %d ) ",
			intval(local_user()),
			intval($_GET['ignore'])
		);
	}

}
		




function suggest_content(&$a) {

	$o = '';
	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$a->page['aside'] .= follow_widget();
	$a->page['aside'] .= findpeople_widget();


	$o .= '<h2>' . t('Friend Suggestions') . '</h2>';


	$r = suggestion_query(local_user());

	if(! count($r)) {
		$o .= t('No suggestions. This works best when you have more than one contact/friend.');
		return $o;
	}

	$tpl = get_markup_template('suggest_friends.tpl');

	foreach($r as $rr) {
			
		$o .= replace_macros($tpl,array(
			'$url' => $rr['url'],
			'$name' => $rr['name'],
			'$photo' => $rr['photo'],
			'$ignlnk' => $a->get_baseurl() . '/suggest?ignore=' . $rr['id'],
			'$ignore' => t('Ignore/Hide')
		));
	}

	$o .= cleardiv();
//	$o .= paginate($a);
	return $o;
}
