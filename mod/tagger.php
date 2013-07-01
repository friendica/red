<?php

require_once('include/security.php');
require_once('include/bbcode.php');
require_once('include/items.php');


function tagger_content(&$a) {

	if(! local_user() && ! remote_user()) {
		return;
	}

	$observer_hash = get_observer_hash();

	$term = notags(trim($_GET['term']));
	// no commas allowed
	$term = str_replace(array(',',' '),array('','_'),$term);

	if(! $term)
		return;

	$item_id = ((argc() > 1) ? notags(trim(argv(1))) : 0);

	logger('tagger: tag ' . $term . ' item ' . $item_id);


	$r = q("SELECT * FROM item left join xchan on xchan_hash = author_xchan WHERE id = '%s' and uid = %d LIMIT 1",
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

	$arr['owner_xchan'] = $item['owner_xchan'];
	$arr['author_xchan'] = $channel['channel_hash'];


	$arr['item_flags'] = ITEM_ORIGIN;
	if($item['item_flags'] & ITEM_WALL)
		$arr['item_flags'] |= ITEM_WALL;
	
	$ulink = '[zrl=' . $channel['xchan_url'] . ']' . $channel['channel_name'] . '[/zrl]';
	$alink = '[zrl=' . $item['xchan_url'] . ']' . $item['xchan_name'] . '[/zrl]';
	$plink = '[zrl=' . $item['plink'] . ']' . $post_type . '[/zrl]';

	$arr['body'] =  sprintf( $bodyverb, $ulink, $alink, $plink, $termlink );

	$arr['verb'] = ACTIVITY_TAG;
	$arr['tgt_type'] = $targettype;
	$arr['target'] = $target;
	$arr['obj_type'] = $objtype;
	$arr['object'] = $obj;
	$arr['parent_mid'] = $item['mid'];
	
	store_item_tag($item['uid'],$item['id'],TERM_OBJ_POST,TERM_HASHTAG,$term,$tagid);
	$ret = post_activity_item($arr);

	if($ret['success'])
		proc_run('php','include/notifier.php','tag',$ret['activity']['id']);

	killme();

}