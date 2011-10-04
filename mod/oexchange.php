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
		info( t('Post successful.') . EOL);
		return;
	}

	$url = (((x($_GET,'url')) && strlen($_GET['url'])) 
		? urlencode(notags(trim($_GET['url']))) : '');
	$title = (((x($_GET,'title')) && strlen($_GET['title'])) 
		? '&title=' . urlencode(notags(trim($_GET['title']))) : '');
	$description = (((x($_GET,'description')) && strlen($_GET['description'])) 
		? '&description=' . urlencode(notags(trim($_GET['description']))) : '');
	$tags = (((x($_GET,'tags')) && strlen($_GET['tags'])) 
		? '&tags=' . urlencode(notags(trim($_GET['tags']))) : '');

	$s = fetch_url($a->get_baseurl() . '/parse_url?f=&url=' . $url . $title . $description . $tags);

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


