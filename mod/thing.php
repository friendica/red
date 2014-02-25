<?php /** @file */

require_once('include/items.php');
require_once('include/contact_selectors.php');


function thing_init(&$a) {

	if(! local_user())
		return;




	$account_id = $a->get_account();
	$channel    = $a->get_channel();

	$term_hash = (($_REQUEST['term_hash']) ? $_REQUEST['term_hash'] : '');

	$name = escape_tags($_REQUEST['term']);
	$verb = escape_tags($_REQUEST['verb']);
	$activity = intval($_REQUEST['activity']);
	$profile_guid = escape_tags($_REQUEST['profile_assign']);
	$url = $_REQUEST['link'];
	$photo = $_REQUEST['img'];

	$hash = random_string();


	$verbs = obj_verbs();

	/** 
	 * verbs: [0] = first person singular, e.g. "I want", [1] = 3rd person singular, e.g. "Bill wants" 
	 * We use the first person form when creating an activity, but the third person for use in activities
	 * FIXME: There is no accounting for verb gender for languages where this is significant. We may eventually
	 * require obj_verbs() to provide full conjugations and specify which form to use in the $_REQUEST params to this module.
	 */

	$translated_verb = $verbs[$verb][1];

	/** 
	 * The site administrator can do things that normals cannot.
	 * This is restricted because it will likely cause
	 * an activitystreams protocol violation and the activity might
	 * choke in some other network and result in unnecessary 
	 * support requests. It isn't because we're trying to be heavy-handed
	 * about what you can and can't do. 
	 */

	if(! $translated_verb) {
		if(is_site_admin())
			$translated_verb = $verb;
	} 
	
	/**
	 * Things, objects: We do not provide definite (a, an) or indefinite (the) articles or singular/plural designators
	 * That needs to be specified in your thing. e.g. Mike has "a carrot", Greg wants "balls", Bob likes "the Boston Red Sox".  
	 */

	/**
	 * Future work on this module might produce more complex activities with targets, e.g. Phillip likes Karen's moustache
	 * and to describe other non-thing objects like channels, such as Karl wants Susan - where Susan represents a channel profile.
	 */
 
	if((! $name) || (! $translated_verb))
		return;





	if($term_hash) {
		$t = q("select * from obj left join term on obj_obj = term_hash where term_hash != '' and obj_type = %d and term_hash = '%s' limit 1",
			intval(TERM_OBJ_THING),
			dbesc($term_hash)
		);
		if(! $t) {
			notice( t('Item not found.') . EOL);
			return;
		}
		$orig_record = $t[0];
		if($photo != $orig_record['imgurl']) {
			$arr = import_profile_photo($photo,get_observer_hash(),true);
			$local_photo = $arr[0];
			$local_photo_type = $arr[3];
		}
		else
			$local_photo = $orig_record['imgurl'];

		$r = q("update term  set term = '%s', url = '%s', imgurl = '%s' where term_hash = '%s' and uid = %d limit 1",
			dbesc($name),
			dbesc(($url) ? $url : z_root() . '/thing/' . $term_hash),
			dbesc($local_photo),
			dbesc($term_hash),
			intval(local_user())
		);

		info( t('Thing updated') . EOL);
		return;
	}

	$sql = (($profile_guid) ? " and profile_guid = '" . dbesc($profile_guid) . "' " : " and is_default = 1 ");
	$p = q("select profile_guid, is_default from profile where uid = %d $sql limit 1",
		intval(local_user())
	);

	if($p)
		$profile = $p[0];
	else
		return;

	$local_photo = null;

	if($photo) {
		$arr = import_profile_photo($photo,get_observer_hash(),true);
		$local_photo = $arr[0];
		$local_photo_type = $arr[3];
	}


	$r = q("select * from term where uid = %d and otype = %d and type = %d and term = '%s' limit 1",
		intval(local_user()),
		intval(TERM_OBJ_THING),
		intval(TERM_THING),
		dbesc($name)
	);
	if(! $r) {
		$r = q("insert into term ( aid, uid, oid, otype, type, term, url, imgurl, term_hash )
			values( %d, %d, %d, %d, %d, '%s', '%s', '%s', '%s' ) ",
			intval($account_id),
			intval(local_user()),
			0,
			intval(TERM_OBJ_THING),
			intval(TERM_THING),
			dbesc($name),
			dbesc(($url) ? $url : z_root() . '/thing/' . $hash),
			dbesc(($photo) ? $local_photo : ''),
			dbesc($hash)
		);
		$r = q("select * from term where uid = %d and otype = %d and type = %d and term = '%s' limit 1",
			intval(local_user()),
			intval(TERM_OBJ_THING),
			intval(TERM_THING),
			dbesc($name)
		);
	}
	$term = $r[0];

	$r = q("insert into obj ( obj_page, obj_verb, obj_type, obj_channel, obj_obj) values ('%s','%s', %d, %d, '%s') ",
		dbesc($profile['profile_guid']),
		dbesc($verb),
		intval(TERM_OBJ_THING),
		intval(local_user()),
		dbesc($term['term_hash'])
	);

	if(! $r) {
		notice( t('Object store: failed'));
		return;
	}

	info( t('Thing added'));


	if($activity) {
		$arr = array();
		$links = array(array('rel' => 'alternate','type' => 'text/html', 'href' => $term['url']));
		if($local_photo)
			$links[] = array('rel' => 'photo', 'type' => $local_photo_type, 'href' => $local_photo);


		$objtype = ACTIVITY_OBJ_THING;

		$obj = json_encode(array(
			'type'    => $objtype,
			'id'      => $term['url'],
			'link'    => $links,
			'title'   => $term['term'],
			'content' => $term['term']
		));

		$bodyverb = str_replace('OBJ: ', '',t('OBJ: %1$s %2$s %3$s'));

		$arr['owner_xchan']  = $channel['channel_hash'];
		$arr['author_xchan'] = $channel['channel_hash'];


		$arr['item_flags'] = ITEM_ORIGIN|ITEM_WALL|ITEM_THREAD_TOP;
	
		$ulink = '[zrl=' . $channel['xchan_url'] . ']' . $channel['channel_name'] . '[/zrl]';
		$plink = '[zrl=' . $term['url'] . ']' . $term['term'] . '[/zrl]';

		$arr['body'] =  sprintf( $bodyverb, $ulink, $translated_verb, $plink );

		if($local_photo)
			$arr['body'] .= "\n\n[zmg]" . $local_photo . "[/zmg]";

		$arr['verb'] = $verb;
		$arr['obj_type'] = $objtype;
		$arr['object'] = $obj;

		if(! $profile['is_default']) {
			$arr['item_private'] = true;
			$str = '';
			$r = q("select abook_xchan from abook where abook_channel = %d and abook_profile = '%s'",
				intval(local_user()),
				dbesc($profile_guid)
			);
			if($r) {
				$arr['allow_cid'] = '';
				foreach($r as $rr)
					$arr['allow_cid'] .= '<' . $rr['abook_xchan'] . '>';
			}
			else
				$arr['allow_cid'] = '<' . get_observer_hash() . '>';
		}
	
		$ret = post_activity_item($arr);
	}
}


function thing_content(&$a) {
	
	if(argc() == 2) {

		$r = q("select * from obj left join term on obj_obj = term_hash where term_hash != '' and obj_type = %d and term_hash = '%s' limit 1",
			intval(TERM_OBJ_THING),
			dbesc(argv(1))
		);

		if($r) {
			return replace_macros(get_markup_template('show_thing.tpl'), array(
				'$header' => t('Show Thing'),
				'$edit' => t('Edit'),
				'$delete' => t('Delete'),
				'$canedit' => ((local_user() && local_user() == $r[0]['obj_channel']) ? true : false), 
				'$thing' => $r[0] ));
		}
		else {
			notice( t('item not found.') . EOL);
			return;
		}
	}

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$thing_hash = '';

	if(argc() == 3 && argv(1) === 'edit') {
		$thing_hash = argv(2);


		$r = q("select * from obj left join term on obj_obj = term_hash where term_hash != '' and obj_type = %d and term_hash = '%s' limit 1",
			intval(TERM_OBJ_THING),
			dbesc($thing_hash)
		);

		if((! $r) || ($r[0]['obj_channel'] != local_user())) {
			notice( t('Permission denied.') . EOL);
			return '';
		}


		$o .= replace_macros(get_markup_template('thing_edit.tpl'),array(
			'$thing_hdr' => t('Edit Thing'),
			'$multiprof' => feature_enabled(local_user(),'multi_profiles'),
			'$profile_lbl' => t('Select a profile'),
			'$profile_select' => contact_profile_assign($r[0]['obj_page']),
			'$verb_lbl' => t('Select a category of stuff. e.g. I ______ something'),
			'$verb_select' => obj_verb_selector($r[0]['obj_verb']),
			'$activity' => array('activity',t('Post an activity'),true,t('Only sends to viewers of the applicable profile')),
			'$thing_hash' => $thing_hash,
			'$thing_lbl' => t('Name of thing e.g. something'),
			'$thething' => $r[0]['term'],
			'$url_lbl' => t('URL of thing (optional)'),
			'$theurl' => $r[0]['url'],
			'$img_lbl' => t('URL for photo of thing (optional)'),
			'$imgurl' => $r[0]['imgurl'],
			'$submit' => t('Submit')
		));

		return $o;
	}

	if(argc() == 3 && argv(1) === 'drop') {
		$thing_hash = argv(2);

		$r = q("select * from obj left join term on obj_obj = term_hash where term_hash != '' and obj_type = %d and term_hash = '%s' limit 1",
			intval(TERM_OBJ_THING),
			dbesc($thing_hash)
		);

		if((! $r) || ($r[0]['obj_channel'] != local_user())) {
			notice( t('Permission denied.') . EOL);
			return '';
		}


		$x = q("delete from obj where obj_obj = '%s' and obj_type = %d and obj_channel = %d limit 1",
			dbesc($thing_hash),
			intval(TERM_OBJ_THING),
			intval(local_user())
		);
		$x = q("delete from term where term_hash = '%s' and uid = %d limit 1",
			dbesc($thing_hash),
			intval(local_user())
		);
		return $o;
	}

	$o .= replace_macros(get_markup_template('thing_input.tpl'),array(
		'$thing_hdr' => t('Add Thing to your Profile'),
		'$multiprof' => feature_enabled(local_user(),'multi_profiles'),
		'$profile_lbl' => t('Select a profile'),
		'$profile_select' => contact_profile_assign(''),
		'$verb_lbl' => t('Select a category of stuff. e.g. I ______ something'),
		'$activity' => array('activity',t('Post an activity'),true,t('Only sends to viewers of the applicable profile')),
		'$verb_select' => obj_verb_selector(),
		'$thing_lbl' => t('Name of thing e.g. something'),
		'$url_lbl' => t('URL of thing (optional)'),
		'$img_lbl' => t('URL for photo of thing (optional)'),
		'$submit' => t('Submit')
	));

	return $o;


}
