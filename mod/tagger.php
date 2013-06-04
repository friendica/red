<?php

require_once('include/security.php');
require_once('include/bbcode.php');
require_once('include/items.php');


function tagger_content(&$a) {

	if(! local_user() && ! remote_user()) {
		return;
	}

	$term = notags(trim($_GET['term']));
	// no commas allowed
	$term = str_replace(array(',',' '),array('','_'),$term);

	if(! $term)
		return;

	$item_id = ((argc() > 1) ? notags(trim(argv(1))) : 0);

	logger('tagger: tag ' . $term . ' item ' . $item_id);


	$r = q("SELECT * FROM `item` left join xchan on xchan_hash = author_hash WHERE `id` = '%s' and uid = %d LIMIT 1",
		dbesc($item_id),
		intval(local_user())
	);

	if((! $item_id) || (! $r)) {
		logger('tagger: no item ' . $item_id);
		return;
	}

	$item = $r[0];

	$owner_uid = $item['uid'];

	switch($item['resource_type']) {
		case 'photo':
			$targettype = ACTIVITY_OBJ_PHOTO;
			$post_type = t('photo');
			break;
		case 'event':
			$targgettype = ACTIVITY_OBJ_EVENT;
			$post_type = t('event');
			break;
		default:
			$targettype = ACTIVITY_OBJ_NOTE;
			$post_type = t('status');
			if($item['mid'] != $item['parent_mid'])
				$post_type = t('comment');
			break;
	}


	$links = array(array('rel' => 'alternate','type' => 'text/html', 
		'href' => z_root() . '/display/' . $item['mid']));

	$target = json_encode(array(
		'type'    => $targettype,
		'id'      => $item['mid'],
		'link'    => $links,
		'title'   => $item['title'],
		'content' => $item['body'],
		'created' => $item['created'],
		'edited'  => $item['edited'],
		'author'  => array(
			'name'     => $item['xchan_name'],
			'address'  => $item['xchan_addr'],
			'guid'     => $item['xchan_guid'],
			'guid_sig' => $item['xchan_guid_sig'],
			'link'     => array(
				array('rel' => 'alternate', 'type' => 'text/html', 'href' => $item['xchan_url']),
				array('rel' => 'photo', 'type' => $item['xchan_photo_mimetype'], 'href' => $item['xchan_photo_m'])),
			),
	));



	$mid = item_message_id();
	$xterm = xmlify($term);

	$link = xmlify('<link rel="alternate" type="text/html" href="' 
		. $a->get_baseurl() . '/display/' . $owner['nickname'] . '/' . $item['id'] . '" />' . "\n") ;

	$tagid = $a->get_baseurl() . '/search?tag=' . $term;
	$objtype = ACTIVITY_OBJ_TAGTERM;

	$obj = json_encode(array(
		'type'    => $objtype,
		'id'      => $tagid,
		'link'    => array(array('rel' => 'alternate','type' => 'text/html', 'href' => $tagid)),
		'title'   => $term,
		'content' => $term
	));

	$bodyverb = t('%1$s tagged %2$s\'s %3$s with %4$s');

	$termlink = html_entity_decode('&#x2317;') . '[zrl=' . $a->get_baseurl() . '/search?tag=' . urlencode($term) . ']'. $term . '[/zrl]';

	$channel = $a->get_channel();


	$arr = array();


	$arr['owner_hash'] = $item['owner_hash'];
	$arr['author_hash'] = $channel['channel_hash'];

// FIXME - everything past this point is still unported
	
	$ulink = '[zrl=' . $contact['url'] . ']' . $contact['name'] . '[/zrl]';
	$alink = '[zrl=' . $item['author-link'] . ']' . $item['author-name'] . '[/zrl]';
	$plink = '[zrl=' . $item['plink'] . ']' . $post_type . '[/zrl]';
	$arr['body'] =  sprintf( $bodyverb, $ulink, $alink, $plink, $termlink );

	$arr['verb'] = ACTIVITY_TAG;
	$arr['tgt_type'] = $targettype;
	$arr['target'] = $target;
	$arr['obj_type'] = $objtype;
	$arr['object'] = $obj;
	$arr['private'] = $item['private'];
	$arr['allow_cid'] = $item['allow_cid'];
	$arr['allow_gid'] = $item['allow_gid'];
	$arr['deny_cid'] = $item['deny_cid'];
	$arr['deny_gid'] = $item['deny_gid'];
	$arr['visible'] = 1;
	$arr['unseen'] = 1;
	$arr['origin'] = 1;

	$post_id = item_store($arr);	

	q("UPDATE `item` set plink = '%s' where id = %d limit 1",
		dbesc($a->get_baseurl() . '/display/' . $owner_nick . '/' . $post_id),
		intval($post_id)
	);
		

	if(! $item['visible']) {
		$r = q("UPDATE `item` SET `visible` = 1 WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($item['id']),
			intval($owner_uid)
		);
	}			

	if((! $blocktags) && (! stristr($item['tag'], ']' . $term . '[' ))) {
		q("update item set tag = '%s' where id = %d limit 1",
			dbesc($item['tag'] . (strlen($item['tag']) ? ',' : '') . '#[zrl=' . $a->get_baseurl() . '/search?tag=' . $term . ']'. $term . '[/zrl]'),
			intval($item['id'])
		);
	}

	// if the original post is on this site, update it.

	$r = q("select `tag`,`id`,`uid` from item where `origin` = 1 AND `mid` = '%s' LIMIT 1",
		dbesc($item['mid'])
	);
	if(count($r)) {
		$x = q("SELECT `blocktags` FROM `user` WHERE `uid` = %d limit 1",
			intval($r[0]['uid'])
		);
		if(count($x) && !$x[0]['blocktags'] && (! stristr($r[0]['tag'], ']' . $term . '['))) {
			q("update item set tag = '%s' where id = %d limit 1",
				dbesc($r[0]['tag'] . (strlen($r[0]['tag']) ? ',' : '') . '#[zrl=' . $a->get_baseurl() . '/search?tag=' . $term . ']'. $term . '[/zrl]'),
				intval($r[0]['id'])
			);
		}

	}
		

	$arr['id'] = $post_id;

	call_hooks('post_local_end', $arr);

	proc_run('php',"include/notifier.php","tag","$post_id");

	killme();

	return; // NOTREACHED


}