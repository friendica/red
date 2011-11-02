<?php

require_once('include/socgraph.php');

function suggest_content(&$a) {

	$o = '';
	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$o .= '<h2>' . t('Friend Suggestions') . '</h2>';


	$r = suggestion_query(local_user());

	if(! count($r)) {
		$o .= t('No suggestions. This works best when you have more than one contact/friend.');
		return $o;
	}

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
//	$o .= paginate($a);
	return $o;
}
