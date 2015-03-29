<?php

require_once('include/security.php');
require_once('bbcode.php');

function share_init(&$a) {

	$post_id = ((argc() > 1) ? intval(argv(1)) : 0);

	if(! $post_id)
		killme();

	if(! (local_channel() || remote_channel()))
		killme();

	$r = q("SELECT * from item left join xchan on author_xchan = xchan_hash WHERE id = %d  LIMIT 1",
		intval($post_id)
	);
	if(! $r)
		killme();
	if(($r[0]['item_private']) && ($r[0]['xchan_network'] !== 'rss'))
		killme();

	$sql_extra = item_permissions_sql($r[0]['uid']);

	$r = q("select * from item where id = %d $sql_extra",
		intval($post_id)
	);
	if(! $r)
		killme();

	/** @FIXME we only share bbcode */

	if($r[0]['mimetype'] !== 'text/bbcode')
		killme();

	/** @FIXME eventually we want to post remotely via rpost on your home site */
	// When that works remove this next bit:

	if(! local_channel())
		killme();

	xchan_query($r);

	if (strpos($r[0]['body'], "[/share]") !== false) {
		$pos = strpos($r[0]['body'], "[share");
		$o = substr($r[0]['body'], $pos);
	} else {
		$o = "[share author='".urlencode($r[0]['author']['xchan_name']).
			"' profile='".$r[0]['author']['xchan_url'] .
			"' avatar='".$r[0]['author']['xchan_photo_s'].
			"' link='".$r[0]['plink'].
			"' posted='".$r[0]['created'].
			"' message_id='".$r[0]['mid']."']";
		if($r[0]['title'])
			$o .= '[b]'.$r[0]['title'].'[/b]'."\n";
		$o .= $r[0]['body'];
		$o.= "[/share]";
	}

	if(local_channel()) {
		echo $o;
		killme();
	}

	$observer = $a->get_observer();
	$parsed = $observer['xchan_url'];
	if($parsed) {
		$post_url = $parsed['scheme'] . ':' . $parsed['host'] . (($parsed['port']) ? ':' . $parsed['port'] : '')
			. '/rpost';

		/**
		 * @FIXME we were probably called from JS so we don't know the return page.
		 * In fact we won't be able to load the remote page.
		 * we might need an iframe
		 */

		$x = z_post_url($post_url, array('f' => '', 'body' => $o ));
		killme();
	}
}
