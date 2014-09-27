<?php /** @file */

require_once('include/bb2diaspora.php');

// used in Diaspora communications to provide a server copy of a sent post in XML format.

function p_init(&$a) {

	if(argc() < 2)
		http_status_exit(401);

	$mid = str_replace('.xml','',argv(1));

	$r = q("select * from item where mid = '%s' and (item_flags & %d) and item_private = 0 limit 1",
		dbesc($mid),
		intval(ITEM_WALL)
	);


	if((! $r) || (! perm_is_allowed($r[0]['uid'],'','view_stream')))
		http_status_exit(404);


	$c = q("select * from channel where channel_id = %d limit 1",
		intval($r[0]['uid'])
	);

	if(! $c)
		http_status_exit(404);

	$myaddr = $c[0]['channel_address'] . '@' . $a->get_hostname();

	$item = $r[0];
   
	$title = $item['title'];
	$body = bb2diaspora_itembody($item);
	$created = datetime_convert('UTC','UTC',$item['created'],'Y-m-d H:i:s \U\T\C');

	$tpl = get_markup_template('diaspora_post.tpl');
	$msg = replace_macros($tpl, array(
		'$body' => xmlify($body),
		'$guid' => $item['mid'],
		'$handle' => xmlify($myaddr),
		'$public' => 'true',
		'$created' => $created,
		'$provider' => (($item['app']) ? $item['app'] : 'redmatrix')
	));

	header('Content-type: text/xml');
	echo $msg;
	killme();
}