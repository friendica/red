<?php /** @file */

require_once('include/items.php');


function thing_init(&$a) {

	if(! local_user())
		return;

	$account_id = $a->get_account();
	$channel    = $a->get_channel();


	$name = escape_tags($_REQUEST['term']);
	$verb = escape_tags($_REQUEST['verb']);
	$profile = escape_tags($_REQUEST['profile']);
	$url = $_REQUEST['link'];
	$photo = $_REQUEST['photo'];

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
	 * Things, objects: We do not provide definite (a, an) or indefinite (the) articles or singular/plural designators
	 * That needs to be specified in your thing. e.g. Mike has "a carrot", Greg wants "balls", Bob likes "the Boston Red Sox".  
	 */

	/**
	 * Future work on this module might produce more complex activities with targets, e.g. Phillip likes Karen's moustache
	 * and to describe other non-thing objects like channels, such as Karl wants Susan - where Susan represents a channel profile.
	 */
 
	if((! $name) || (! $translated_verb))
		return;

	if(! $profile) {
		$r = q("select profile_guid from profile where is_default = 1 and uid = %d limit 1",
			intval(local_user())
		);
		if($r)
			$profile = $r[0]['profile_guid'];
	}

	if(! $profile)
		return;


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
			dbesc(($photo) ? $photo : ''),
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

	$r = q("insert into obj ( obj_page, obj_verb, obj_type, obj_channel) values ('%s','%s', %d,%d) ",
		dbesc($profile),
		dbesc($verb),
		intval(TERM_OBJ_THING),
		intval(local_user())
	);

	if(! $r) {
		notice('Object store: failed');
		return;
	}


	$arr = array();
	$links = array(array('rel' => 'alternate','type' => 'text/html', 
		'href' => $term['url']));

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

	$arr['verb'] = $verb;
	$arr['obj_type'] = $objtype;
	$arr['object'] = $obj;
	
	$ret = post_activity_item($arr);

	if($ret['success'])
		proc_run('php','include/notifier.php','tag',$ret['activity']['id']);
	
}


function thing_content(&$a) {
	
	/* placeholders */

	if(argc() > 1) {
		return t('not yet implemented.');
	}

	goaway(z_root() . '/network');


}
