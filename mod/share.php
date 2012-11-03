<?php

require_once('bbcode.php');

function share_init(&$a) {

	$post_id = ((argc() > 1) ? intval(argv(1)) : 0);
	if((! $post_id) || (! local_user()))
		killme();

	$r = q("SELECT * from item WHERE id = %d AND uid = %d and item_restrict = 0 LIMIT 1",
		intval($post_id),
		intval(local_user())
	);
	if((! $r) || $r[0]['item_private'])
		killme();

	xchan_query($r);

	$o = '[share]' . "\n";

	$o .= "\xE2\x99\xb2" . ' [url=' . $r[0]['author']['xchan_url'] . ']' . $r[0]['author']['xchan_name'] . '[/url]' . "\n";
	if($r[0]['title'])
		$o .= '[b]' . $r[0]['title'] . '[/b]' . "\n";
	$o .= $r[0]['body'] . "\n" ;

	$o .= (($r[0]['plink']) ? '[url=' . $r[0]['plink'] . ']' . t('link') . '[/url]' . "\n" : '') . '[/share]';

	echo $o;
	killme();  
}
