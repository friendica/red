<?php

require_once('include/security.php');
require_once('include/bbcode.php');
require_once('include/items.php');


function subthread_content(&$a) {

	if((! local_channel()) && (! remote_channel())) {
		return;
	}

	$activity = ACTIVITY_FOLLOW;

	$item_id = ((argc() > 1) ? notags(trim(argv(1))) : 0);

	$r = q("SELECT * FROM `item` WHERE `parent` = '%s' OR `parent_mid` = '%s' and parent = id LIMIT 1",
		dbesc($item_id),
		dbesc($item_id)
	);

	if((! $item_id) || (! $r)) {
		logger('subthread: no item ' . $item_id);
		return;
	}

	$item = $r[0];

	$owner_uid = $item['uid'];
	$observer = $a->get_observer();
	$ob_hash = (($observer) ? $observer['xchan_hash'] : '');

	if(! perm_is_allowed($owner_uid,$ob_hash,'post_comments'))
		return;

	$sys = get_sys_channel();

	$owner_uid = $item['uid'];
	$owner_aid = $item['aid'];

	// if this is a "discover" item, (item['uid'] is the sys channel),
	// fallback to the item comment policy, which should've been
	// respected when generating the conversation thread.
	// Even if the activity is rejected by the item owner, it should still get attached
	// to the local discover conversation on this site. 

	if(($owner_uid != $sys['channel_id']) && (! perm_is_allowed($owner_uid,$observer['xchan_hash'],'post_comments'))) {
			notice( t('Permission denied') . EOL);
			killme();
	}

	$r = q("select * from xchan where xchan_hash = '%s' limit 1",
		dbesc($item['owner_xchan'])
	);
	if($r)
		$thread_owner = $r[0];
	else
		killme();

	$r = q("select * from xchan where xchan_hash = '%s' limit 1",
		dbesc($item['author_xchan'])
	);
	if($r)
		$item_author = $r[0];
	else
		killme();


	$mid = item_message_id();

	$post_type = (($item['resource_type'] === 'photo') ? t('photo') : t('status'));

	$links = array(array('rel' => 'alternate','type' => 'text/html', 'href' => $item['plink']));
	$objtype = (($item['resource_type'] === 'photo') ? ACTIVITY_OBJ_PHOTO : ACTIVITY_OBJ_NOTE ); 

	$body = $item['body'];

	$obj = json_encode(array(
		'type'    => $objtype,
		'id'      => $item['mid'],
		'parent'  => (($item['thr_parent']) ? $item['thr_parent'] : $item['parent_mid']),
		'link'    => $links,
		'title'   => $item['title'],
		'content' => $item['body'],
		'created' => $item['created'],
		'edited'  => $item['edited'],
		'author'  => array(
			'name'     => $item_author['xchan_name'],
			'address'  => $item_author['xchan_addr'],
			'guid'     => $item_author['xchan_guid'],
			'guid_sig' => $item_author['xchan_guid_sig'],
			'link'     => array(
				array('rel' => 'alternate', 'type' => 'text/html', 'href' => $item_author['xchan_url']),
				array('rel' => 'photo', 'type' => $item_author['xchan_photo_mimetype'], 'href' => $item_author['xchan_photo_m'])),
			),
	));

	if(! ($item['item_flags'] & ITEM_THREAD_TOP))
		$post_type = 'comment';		


	$bodyverb = t('%1$s is following %2$s\'s %3$s');

	$item_flags = ITEM_ORIGIN | ITEM_NOTSHOWN;
	if($item['item_flags'] & ITEM_WALL)
		$item_flags |= ITEM_WALL;
	

	$arr = array();

	$arr['mid']          = $mid;
	$arr['aid']          = $owner_aid;
	$arr['uid']          = $owner_uid;
	$arr['item_flags']   = $item_flags;
	$arr['parent']       = $item['id'];
	$arr['parent_mid']   = $item['mid'];
	$arr['thr_parent']   = $item['mid'];
	$arr['owner_xchan']  = $thread_owner['xchan_hash'];
	$arr['author_xchan'] = $observer['xchan_hash'];

	
	$ulink = '[zrl=' . $item_author['xchan_url'] . ']' . $item_author['xchan_name'] . '[/zrl]';
	$alink = '[zrl=' . $observer['xchan_url'] . ']' . $observer['xchan_name'] . '[/zrl]';
	$plink = '[zrl=' . $a->get_baseurl() . '/display/' . $item['mid'] . ']' . $post_type . '[/zrl]';
	
	$arr['body']          =  sprintf( $bodyverb, $alink, $ulink, $plink );

	$arr['verb']          = $activity;
	$arr['obj_type']      = $objtype;
	$arr['object']        = $obj;

	$arr['allow_cid']     = $item['allow_cid'];
	$arr['allow_gid']     = $item['allow_gid'];
	$arr['deny_cid']      = $item['deny_cid'];
	$arr['deny_gid']      = $item['deny_gid'];


	$post = item_store($arr);	
	$post_id = $post['item_id'];

	$arr['id'] = $post_id;

	call_hooks('post_local_end', $arr);

	killme();



























	$post_type = (($item['resource_id']) ? t('photo') : t('status'));
	$objtype = (($item['resource_id']) ? ACTIVITY_OBJ_PHOTO : ACTIVITY_OBJ_NOTE ); 

	$link = xmlify('<link rel="alternate" type="text/html" href="' . $a->get_baseurl() . '/display/' . $owner['nickname'] . '/' . $item['id'] . '" />' . "\n") ;
	$body = $item['body'];

	$obj = <<< EOT

	<object>
		<type>$objtype</type>
		<local>1</local>
		<id>{$item['mid']}</id>
		<link>$link</link>
		<title></title>
		<content>$body</content>
	</object>
EOT;

	$arr = array();

	$arr['mid'] = $mid;
	$arr['uid'] = $owner_uid;
	$arr['contact-id'] = $contact['id'];
	$arr['type'] = 'activity';
	$arr['wall'] = $item['wall'];
	$arr['origin'] = 1;
	$arr['gravity'] = GRAVITY_LIKE;
	$arr['parent'] = $item['id'];
	$arr['parent-mid'] = $item['mid'];
	$arr['thr_parent'] = $item['mid'];
	$arr['owner-name'] = $remote_owner['name'];
	$arr['owner-link'] = $remote_owner['url'];
	$arr['owner-avatar'] = $remote_owner['thumb'];
	$arr['author-name'] = $contact['name'];
	$arr['author-link'] = $contact['url'];
	$arr['author-avatar'] = $contact['thumb'];
	
	$ulink = '[zrl=' . $contact['url'] . ']' . $contact['name'] . '[/zrl]';
	$alink = '[zrl=' . $item['author-link'] . ']' . $item['author-name'] . '[/zrl]';
	$plink = '[zrl=' . $a->get_baseurl() . '/display/' . $owner['nickname'] . '/' . $item['id'] . ']' . $post_type . '[/zrl]';
	$arr['body'] =  sprintf( $bodyverb, $ulink, $alink, $plink );

	$arr['verb'] = $activity;
	$arr['object-type'] = $objtype;
	$arr['object'] = $obj;
	$arr['allow_cid'] = $item['allow_cid'];
	$arr['allow_gid'] = $item['allow_gid'];
	$arr['deny_cid'] = $item['deny_cid'];
	$arr['deny_gid'] = $item['deny_gid'];
	$arr['visible'] = 1;
	$arr['unseen'] = 1;
	$arr['last-child'] = 0;

	$post = item_store($arr);	
	$post_id = $post['item_id'];

	if(! $item['visible']) {
		$r = q("UPDATE `item` SET `visible` = 1 WHERE `id` = %d AND `uid` = %d",
			intval($item['id']),
			intval($owner_uid)
		);
	}			

	$arr['id'] = $post_id;

	call_hooks('post_local_end', $arr);

	killme();

}


