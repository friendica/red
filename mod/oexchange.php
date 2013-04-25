<?php


function oexchange_init(&$a) {

	if((argc() > 1) && (argv(1) === 'xrd')) {
		$tpl = get_markup_template('oexchange_xrd.tpl');

		$o = replace_macros($tpl, array('$base' => $a->get_baseurl()));
		echo $o;
		killme();
	}
}

function oexchange_content(&$a) {

	if(! local_user()) {
		return login(false);
	}

	if((argc() > 1) && argv(1) === 'done') {
		info( t('Post successful.') . EOL);
		return;
	}

	$url = (((x($_REQUEST,'url')) && strlen($_REQUEST['url'])) 
		? urlencode(notags(trim($_REQUEST['url']))) : '');
	$title = (((x($_REQUEST,'title')) && strlen($_REQUEST['title'])) 
		? '&title=' . urlencode(notags(trim($_REQUEST['title']))) : '');
	$description = (((x($_REQUEST,'description')) && strlen($_REQUEST['description'])) 
		? '&description=' . urlencode(notags(trim($_REQUEST['description']))) : '');
	$tags = (((x($_REQUEST,'tags')) && strlen($_REQUEST['tags'])) 
		? '&tags=' . urlencode(notags(trim($_REQUEST['tags']))) : '');

	$ret = z_fetch_url($a->get_baseurl() . '/parse_url?f=&url=' . $url . $title . $description . $tags);

	if($ret['success'])
		$s = $ret['body'];

	if(! strlen($s))
		return;

	$post = array();

	$post['profile_uid'] = local_user();
	$post['return'] = '/oexchange/done' ;
	$post['body'] = $s;
	$post['type'] = 'wall';

	$_REQUEST = $post;
	require_once('mod/item.php');
	item_post($a);

}


