<?php


function oexchange_init(&$a) {

	if(($a->argc > 1) && ($a->argv[1] === 'xrd')) {
		$tpl = get_markup_template('oexchange_xrd.tpl');

		$o = replace_macros($tpl, array('$base' => $a->get_baseurl()));
		echo $o;
		killme();
	}

		


}

function oexchange_content(&$a) {

	if(! local_user()) {
		$o = login(false);
		return $o;
	}

	if(($a->argc > 1) && $a->argv[1] === 'done') {
		notice( t('Post successful.') . EOL);
		return;
	}

	$url = (((x($_GET,'url')) && strlen($_GET['url'])) ? notags(trim($_GET['url'])) : '');

	$s = fetch_url($a->get_baseurl() . '/parse_url&url=' . $url);

	if(! strlen($s))
		return;

	require_once('include/html2bbcode.php');

	$post = array();

	$post['profile_uid'] = local_user();
	$post['return'] = '/oexchange/done' ;
	$post['body'] = html2bbcode($s);
	$post['type'] = 'wall';

	$_POST = $post;
	require_once('mod/item.php');
	item_post($a);

}


