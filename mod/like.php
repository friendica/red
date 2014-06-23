<?php

require_once('include/security.php');
require_once('include/bbcode.php');
require_once('include/items.php');


function like_content(&$a) {


	$observer = $a->get_observer();

	$verb = notags(trim($_GET['verb']));

	if(! $verb)
		$verb = 'like';

	switch($verb) {
		case 'like':
		case 'unlike':
			$activity = ACTIVITY_LIKE;
			break;
		case 'dislike':
		case 'undislike':
			$activity = ACTIVITY_DISLIKE;
			break;
		default:
			return;
			break;
	}

	$extended_like = false;
	$object = $target = null;
	$post_type = '';
	$objtype = '';

	if(argc() == 3) {

		if(! $observer)
			killme();

		$extended_like = true;
		$obj_type = argv(1);
		$obj_id = argv(2);
		$public = true;

		if($obj_type == 'profile') {
			$r = q("select * from profile where profile_guid = '%s' limit 1",
				dbesc(argv(2))
			);
			if(! $r)
				killme();			
			$owner_uid = $r[0]['uid'];
			if($r[0]['is_default'])
				$public = true;
			if(! $public) {
				$d = q("select abook_xchan from abook where abook_profile = '%s' and abook_channel = %d",
					dbesc($r[0]['profile_guid']),
					intval($owner_uid)
				);
				if(! $d) {
					// forgery - illegal
					killme();
				}
				// $d now contains a list of those who can see this profile - only send the status notification
				// to them.
				$allow_cid = $allow_gid = $deny_cid = $deny_gid = '';
				foreach($d as $dd) {
					$allow_gid .= '<' . $dd['abook_xchan'] . '>';
				}
			}
			$post_type = t('channel');
			$objtype = ACTIVITY_OBJ_PROFILE;


		}
		elseif($obj_type == 'thing') {

			$r = q("select * from obj left join term on obj_obj = term_hash where term_hash != '' 
				and obj_type = %d and term_hash = '%s' limit 1",
            	intval(TERM_OBJ_THING),
            	dbesc(argv(2))
        	);

			if(! $r)
				killme();		

			$owner_uid = $r[0]['obj_channel'];

			$allow_cid = $r[0]['allow_cid'];
			$allow_gid = $r[0]['allow_gid'];
			$deny_cid = $r[0]['deny_cid'];
			$deny_gid = $r[0]['deny_gid'];
			if($allow_cid || $allow_gid || $deny_cid || $deny_gid)			
				$public = false;

			$post_type = t('thing');
			$objtype = ACTIVITY_OBJ_PROFILE;
			$tgttype = ACTIVITY_OBJ_THING;

			$links   = array();
			$links[] = array('rel' => 'alternate', 'type' => 'text/html',
				'href' => z_root() . '/thing/' . $r[0]['term_hash']);
			if($r[0]['imgurl'])	
				$links[] = array('rel' => 'photo', 'href' => $r[0]['imgurl']);

			$target = json_encode(array(
				'type'  => $tgttype,
				'title' => $r[0]['term'],
				'id'    => z_root() . '/thing/' . $r[0]['term_hash'],
				'link'  => $links
			));

			$plink = '[zrl=' . z_root() . '/thing/' . $r[0]['term_hash'] . ']' . $r[0]['term'] . '[/zrl]';

		}
		
		if(! ($owner_uid && $r))
			killme();

		// The resultant activity is going to be a wall-to-wall post, so make sure this is allowed

		$perms = get_all_perms($owner_uid,$observer['xchan_hash']);

		if(! ($perms['post_wall'] && $perms['view_profile']))
			killme();

		$ch = q("select * from channel left join xchan on channel_hash = xchan_hash where channel_id = %d limit 1",
			intval($owner_uid)
		);
		if(! $ch)
			killme();


		if(! $plink)
			$plink = '[zrl=' . z_root() . '/profile/' . $ch[0]['channel_address'] . ']' . $post_type . '[/zrl]';
	
		$links   = array();
		$links[] = array('rel' => 'alternate', 'type' => 'text/html',
			'href' => z_root() . '/profile/' . $ch[0]['channel_address']);
		$links[] = array('rel' => 'photo', 'type' => $ch[0]['xchan_photo_mimetype'],
			'href' => $ch[0]['xchan_photo_l']);

		$object = json_encode(array(
			'type'  => ACTIVITY_OBJ_PROFILE,
			'title' => $ch[0]['channel_name'],
			'id'    => $ch[0]['xchan_url'] . '/' . $ch[0]['xchan_hash'],
			'link'  => $links
		));


		// second like of the same thing is "undo" for the first like

		$z = q("select * from likes where channel_id = %d and liker = '%s' and verb = '%s' and target_type = '%s' and target_id = '%s' limit 1",
			intval($ch[0]['channel_id']),
			dbesc($observer['xchan_hash']),
			dbesc($activity),
			dbesc(($tgttype)?$tgttype:$objtype),
			dbesc($obj_id)
		);

		if($z) {
			q("delete from likes where id = %d limit 1",
				intval($z[0]['id'])
			);
			drop_item($z[0]['iid'],false);
			killme();
		}
	}
	else {

		$item_id = ((argc() == 2) ? notags(trim(argv(1))) : 0);

		logger('like: verb ' . $verb . ' item ' . $item_id, LOGGER_DEBUG);


		$r = q("SELECT * FROM item WHERE id = %d and item_restrict = 0 LIMIT 1",
			dbesc($item_id)
		);

		if(! $item_id || (! $r)) {
			logger('like: no item ' . $item_id);
			killme();
		}


		$item = $r[0];
		$owner_uid = $item['uid'];
		$owner_aid = $item['aid'];


		$sys = get_sys_channel();


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


		$r = q("SELECT * FROM item WHERE verb = '%s' AND item_restrict = 0 
			AND author_xchan = '%s' AND ( parent = %d OR thr_parent = '%s') LIMIT 1",
			dbesc($activity),
			dbesc($observer['xchan_hash']),
			intval($item_id),
			dbesc($item['mid'])
		);
		if($r) {
			$like_item = $r[0];

			// Already liked/disliked it, delete it

			$r = q("UPDATE item SET item_restrict = ( item_restrict ^ %d ), changed = '%s' WHERE id = %d LIMIT 1",
				intval(ITEM_DELETED),
				dbesc(datetime_convert()),
				intval($like_item['id'])
			);

			proc_run('php',"include/notifier.php","like",$like_item['id']);
			return;
		}

	}

	$mid = item_message_id();

	if($extended_like) {
		$item_flags = ITEM_THREAD_TOP|ITEM_ORIGIN|ITEM_WALL;

	}
	else {
		$post_type = (($item['resource_type'] === 'photo') ? t('photo') : t('status'));

		$links = array(array('rel' => 'alternate','type' => 'text/html', 'href' => $item['plink']));
		$objtype = (($item['resource_type'] === 'photo') ? ACTIVITY_OBJ_PHOTO : ACTIVITY_OBJ_NOTE ); 

		$body = $item['body'];

		$object = json_encode(array(
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

		$item_flags = ITEM_ORIGIN | ITEM_NOTSHOWN;
		if($item['item_flags'] & ITEM_WALL)
			$item_flags |= ITEM_WALL;

	}

	if($verb === 'like')
		$bodyverb = t('%1$s likes %2$s\'s %3$s');
	if($verb === 'dislike')
		$bodyverb = t('%1$s doesn\'t like %2$s\'s %3$s');

	if(! isset($bodyverb))
			killme(); 

	
	$arr = array();

	if($extended_like) {
		$ulink = '[zrl=' . $ch[0]['xchan_url'] . ']' . $ch[0]['xchan_name'] . '[/zrl]';
		$alink = '[zrl=' . $observer['xchan_url'] . ']' . $observer['xchan_name'] . '[/zrl]';
		$private = (($public) ? 0 : 1);
	}
	else {
		$arr['parent']       = $item['id'];
		$arr['thr_parent']   = $item['mid'];
		$ulink = '[zrl=' . $item_author['xchan_url'] . ']' . $item_author['xchan_name'] . '[/zrl]';
		$alink = '[zrl=' . $observer['xchan_url'] . ']' . $observer['xchan_name'] . '[/zrl]';
		$plink = '[zrl=' . $a->get_baseurl() . '/display/' . $item['mid'] . ']' . $post_type . '[/zrl]';
		$allow_cid       = $item['allow_cid'];
		$allow_gid       = $item['allow_gid'];
		$deny_cid        = $item['deny_cid'];
		$deny_gid        = $item['deny_gid'];
		$private         = $item['private'];

	}
	

	$arr['mid']          = $mid;
	$arr['aid']          = (($extended_like) ? $ch[0]['channel_account_id'] : $owner_aid);
	$arr['uid']          = $owner_uid;
	$arr['item_flags']   = $item_flags;
	$arr['parent_mid']   = (($extended_like) ? $mid : $item['mid']);
	$arr['owner_xchan']  = (($extended_like) ? $ch[0]['xchan_hash'] : $thread_owner['xchan_hash']);
	$arr['author_xchan'] = $observer['xchan_hash'];

	
	$arr['body']          =  sprintf( $bodyverb, $alink, $ulink, $plink );
	if($obj_type === 'thing' && $r[0]['imgurl']) {
		$arr['body'] .= "\n\n[zmg=80x80]" . $r[0]['imgurl'] . '[/zmg]';
	}	


	$arr['verb']          = $activity;
	$arr['obj_type']      = $objtype;
	$arr['object']        = $object;

	if($target) {
		$arr['tgt_type']  = $tgttype;
		$arr['target']    = $target;
	}

	$arr['allow_cid']     = $allow_cid;
	$arr['allow_gid']     = $allow_gid;
	$arr['deny_cid']      = $deny_cid;
	$arr['deny_gid']      = $deny_gid;
	$arr['item_private']  = $private;


	$post = item_store($arr);	
	$post_id = $post['item_id'];

	$arr['id'] = $post_id;

	call_hooks('post_local_end', $arr);


	if($extended_like) {
		$r = q("insert into likes (channel_id,liker,likee,iid,verb,target_type,target_id,target) values (%d,'%s','%s',%d,'%s','%s','%s','%s')",
			intval($ch[0]['channel_id']),
			dbesc($observer['xchan_hash']),
			dbesc($ch[0]['channel_hash']),
			intval($post_id),
			dbesc($activity),
			dbesc(($tgttype)?$tgttype:$objtype),
			dbesc($obj_id),
			dbesc(json_encode(($target)?$target:$object))
		);
	};


	proc_run('php',"include/notifier.php","like","$post_id");

	killme();
}


