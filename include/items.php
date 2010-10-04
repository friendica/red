<?php

require_once('bbcode.php');

function get_feed_for(&$a, $dfrn_id, $owner_id, $last_update, $direction = 0) {


	// default permissions - anonymous user

	$sql_extra = " 
		AND `allow_cid` = '' 
		AND `allow_gid` = '' 
		AND `deny_cid`  = '' 
		AND `deny_gid`  = '' 
	";

	if(strlen($owner_id) && ! intval($owner_id)) {
		$r = q("SELECT `uid`, `nickname` FROM `user` WHERE `nickname` = '%s' LIMIT 1",
			dbesc($owner_id)
		);
		if(count($r)) {
			$owner_id = $r[0]['uid'];
			$owner_nick = $r[0]['nickname'];
		}
	}

	$r = q("SELECT * FROM `contact` WHERE `self` = 1 AND `uid` = %d LIMIT 1",
		intval($owner_id)
	);
	if(count($r))
		$owner = $r[0];
	else
		killme();

	if($dfrn_id && $dfrn_id != '*') {

		$sql_extra = '';
		switch($direction) {
			case (-1):
				$sql_extra = sprintf(" AND `issued-id` = '%s' ", dbesc($dfrn_id));
				$my_id = $dfrn_id;
				break;
			case 0:
				$sql_extra = sprintf(" AND `issued-id` = '%s' AND `duplex` = 1 ", dbesc($dfrn_id));
				$my_id = '1:' . $dfrn_id;
				break;
			case 1:
				$sql_extra = sprintf(" AND `dfrn-id` = '%s' AND `duplex` = 1 ", dbesc($dfrn_id));
				$my_id = '0:' . $dfrn_id;
				break;
			default:
				return false;
				break; // NOTREACHED
		}

		$r = q("SELECT * FROM `contact` WHERE `blocked` = 0 AND `pending` = 0 AND `contact`.`uid` = %d $sql_extra LIMIT 1",
			intval($owner_id)
		);

		if(! count($r))
			return false;

		$contact = $r[0];
		$groups = init_groups_visitor($contact['id']);

		if(count($groups)) {
			for($x = 0; $x < count($groups); $x ++) 
				$groups[$x] = '<' . intval($groups[$x]) . '>' ;
			$gs = implode('|', $groups);
		}
		else
			$gs = '<<>>' ; // Impossible to match 

		$sql_extra = sprintf(" 
			AND ( `allow_cid` = '' OR     `allow_cid` REGEXP '<%d>' ) 
			AND ( `deny_cid`  = '' OR NOT `deny_cid`  REGEXP '<%d>' ) 
			AND ( `allow_gid` = '' OR     `allow_gid` REGEXP '%s' )
			AND ( `deny_gid`  = '' OR NOT `deny_gid`  REGEXP '%s') 
		",
			intval($contact['id']),
			intval($contact['id']),
			dbesc($gs),
			dbesc($gs)
		);
	}

	if($dfrn_id === '' || $dfrn_id === '*')
		$sort = 'DESC';
	else
		$sort = 'ASC';

	if(! strlen($last_update))
		$last_update = 'now - 30 days';

	$check_date = datetime_convert('UTC','UTC',$last_update,'Y-m-d H:i:s');

	$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
		`contact`.`name`, `contact`.`photo`, `contact`.`url`, 
		`contact`.`name-date`, `contact`.`uri-date`, `contact`.`avatar-date`,
		`contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`, 
		`contact`.`id` AS `contact-id`, `contact`.`uid` AS `contact-uid`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		WHERE `item`.`uid` = %d AND `item`.`visible` = 1 
		AND `item`.`wall` = 1 AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
		AND ( `item`.`edited` > '%s' OR `item`.`changed` > '%s' )
		$sql_extra
		ORDER BY `parent` %s, `created` ASC LIMIT 0, 300",
		intval($owner_id),
		dbesc($check_date),
		dbesc($check_date),
		dbesc($sort)
	);

	// Will check further below if this actually returned results.
	// We will provide an empty feed in any case.

	$items = $r;

	$feed_template = load_view_file('view/atom_feed.tpl');
	$tomb_template = load_view_file('view/atom_tomb.tpl');
	$item_template = load_view_file('view/atom_item.tpl');
	$cmnt_template = load_view_file('view/atom_cmnt.tpl');

	$atom = '';

	$hub = get_config('system','huburl');

	$hubxml = ((strlen($hub)) ? '<link rel="hub" href="' . xmlify($hub) . '" />' . "\n" : '');


	$atom .= replace_macros($feed_template, array(
		'$feed_id'      => xmlify($a->get_baseurl() . '/profile/' . $owner_nick),
		'$feed_title'   => xmlify($owner['name']),
		'$feed_updated' => xmlify(datetime_convert('UTC', 'UTC', $updated . '+00:00' , ATOM_TIME)) ,
		'$hub'          => $hubxml,
		'$name'         => xmlify($owner['name']),
		'$profile_page' => xmlify($owner['url']),
		'$photo'        => xmlify($owner['photo']),
		'$thumb'        => xmlify($owner['thumb']),
		'$picdate'      => xmlify(datetime_convert('UTC','UTC',$owner['avatar-date'] . '+00:00' , ATOM_TIME)) ,
		'$uridate'      => xmlify(datetime_convert('UTC','UTC',$owner['uri-date']    . '+00:00' , ATOM_TIME)) ,
		'$namdate'      => xmlify(datetime_convert('UTC','UTC',$owner['name-date']   . '+00:00' , ATOM_TIME)) 
	));


	if(! count($items)) {
		$atom .= '</feed>' . "\r\n";
		return $atom;
	}

	foreach($items as $item) {

		// public feeds get html, our own nodes use bbcode

		if($dfrn_id === '*') {
			$allow = (($item['last-child']) ? 1 : 0);
			$item['body'] = bbcode($item['body']);
			$type = 'html';
		}
		else {
			$allow = ((($item['last-child']) && ($contact['rel']) && ($contact['rel'] != REL_FAN)) ? 1 : 0);
			$type = 'text';
		}

		if($item['deleted']) {
			$atom .= replace_macros($tomb_template, array(
				'$id'      => xmlify($item['uri']),
				'$updated' => xmlify(datetime_convert('UTC', 'UTC', $item['edited'] . '+00:00' , ATOM_TIME))
			));
		}
		else {
			$verb = construct_verb($item);
			$actobj = construct_activity($item);

			if($item['parent'] == $item['id']) {
				$atom .= replace_macros($item_template, array(
					'$name'               => xmlify($item['name']),
					'$profile_page'       => xmlify($item['url']),
					'$thumb'              => xmlify($item['thumb']),
					'$owner_name'         => xmlify($item['owner-name']),
					'$owner_profile_page' => xmlify($item['owner-link']),
					'$owner_thumb'        => xmlify($item['owner-avatar']),
					'$item_id'            => xmlify($item['uri']),
					'$title'              => xmlify($item['title']),
					'$published'          => xmlify(datetime_convert('UTC', 'UTC', $item['created'] . '+00:00' , ATOM_TIME)),
					'$updated'            => xmlify(datetime_convert('UTC', 'UTC', $item['edited']  . '+00:00' , ATOM_TIME)),
					'$location'           => xmlify($item['location']),
					'$type'               => $type,
					'$alt'           => xmlify($a->get_baseurl() . '/display/' . $owner_nick . '/' . $item['id']),
					'$content'            => xmlify($item['body']),
					'$verb'               => xmlify($verb),
					'$actobj'             => $actobj,  // do not xmlify
					'$comment_allow'      => $allow
				));
			}
			else {
				$atom .= replace_macros($cmnt_template, array(
					'$name'          => xmlify($item['name']),
					'$profile_page'  => xmlify($item['url']),
					'$thumb'         => xmlify($item['thumb']),
					'$item_id'       => xmlify($item['uri']),
					'$title'         => xmlify($item['title']),
					'$published'     => xmlify(datetime_convert('UTC', 'UTC', $item['created'] . '+00:00' , ATOM_TIME)),
					'$updated'       => xmlify(datetime_convert('UTC', 'UTC', $item['edited']  . '+00:00' , ATOM_TIME)),
					'$type'          => $type,
					'$content'       => xmlify($item['body']),
					'$alt'           => xmlify($a->get_baseurl() . '/display/' . $owner_nick . '/' . $item['id']),
					'$verb'          => xmlify($verb),
					'$actobj'        => $actobj, // do not xmlify
					'$parent_id'     => xmlify($item['parent-uri']),
					'$comment_allow' => $allow
				));
			}
		}
	}

	$atom .= '</feed>' . "\r\n";
	return $atom;
}


function construct_verb($item) {
	if($item['verb'])
		return $item['verb'];
	return ACTIVITY_POST;
}

function construct_activity($item) {

	if($item['object']) {
		$o = '<as:object>' . "\r\n";
		$r = @simplexml_load_string($item['object']);
		if($r->type)
			$o .= '<as:object-type>' . $r->type . '</as:object-type>' . "\r\n";
		if($r->id)
			$o .= '<id>' . $r->id . '</id>' . "\r\n";
		if($r->link)
			$o .= '<link rel="alternate" type="text/html" href="' . $r->link . '" />' . "\r\n";
		if($r->title)
			$o .= '<title>' . $r->title . '</title>' . "\r\n";
		if($r->content)
			$o .= '<content type="html" >' . bbcode($r->content) . '</content>' . "\r\n";
		$o .= '</as:object>' . "\r\n";
		return $o;
	}

	return '';
} 




function get_atom_elements($item) {

	require_once('library/HTMLPurifier.auto.php');
	require_once('include/html2bbcode.php');

	$res = array();

	$raw_author = $item->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10,'author');
	if($raw_author) {
		if($raw_author[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'][0]['attribs']['']['rel'] === 'photo')
		$res['author-avatar'] = unxmlify($raw_author[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'][0]['attribs']['']['href']);
	}

	$author = $item->get_author();
	$res['author-name'] = unxmlify($author->get_name());
	$res['author-link'] = unxmlify($author->get_link());
	if(! $res['author-avatar'])
		$res['author-avatar'] = unxmlify($author->get_avatar());
	$res['uri'] = unxmlify($item->get_id());
	$res['title'] = unxmlify($item->get_title());
	$res['body'] = unxmlify($item->get_content());

	$maxlen = get_max_import_size();
	if($maxlen && (strlen($res['body']) > $maxlen))
		$res['body'] = substr($res['body'],0, $maxlen);

	// It isn't certain at this point whether our content is plaintext or html and we'd be foolish to trust 
	// the content type. Our own network only emits text normally, though it might have been converted to 
	// html if we used a pubsubhubbub transport. But if we see even one html open tag in our text, we will
	// have to assume it is all html and needs to be purified.

	// It doesn't matter all that much security wise - because before this content is used anywhere, we are 
	// going to escape any tags we find regardless, but this lets us import a limited subset of html from 
	// the wild, by sanitising it and converting supported tags to bbcode before we rip out any remaining 
	// html.


	if(strpos($res['body'],'<')) {

		$res['body'] = preg_replace('#<object[^>]+>.+?' . 'http://www.youtube.com/((?:v|cp)/[A-Za-z0-9\-_=]+).+?</object>#s',
			'[youtube]$1[/youtube]', $res['body']);

		$config = HTMLPurifier_Config::createDefault();
		$config->set('Core.DefinitionCache', null);

		// we shouldn't need a whitelist, because the bbcode converter
		// will strip out any unsupported tags.
		// $config->set('HTML.Allowed', 'p,b,a[href],i'); 

		$purifier = new HTMLPurifier($config);
		$res['body'] = $purifier->purify($res['body']);
	}

	
	$res['body'] = html2bbcode($res['body']);


	$allow = $item->get_item_tags(NAMESPACE_DFRN,'comment-allow');
	if($allow && $allow[0]['data'] == 1)
		$res['last-child'] = 1;
	else
		$res['last-child'] = 0;

	$rawcreated = $item->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10,'published');
	if($rawcreated)
		$res['created'] = unxmlify($rawcreated[0]['data']);

	$rawlocation = $item->get_item_tags(NAMESPACE_DFRN, 'location');
	if($rawlocation)
		$res['location'] = unxmlify($rawlocation[0]['data']);


	$rawedited = $item->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10,'updated');
	if($rawedited)
		$res['edited'] = unxmlify($rawcreated[0]['data']);

	$rawowner = $item->get_item_tags(NAMESPACE_DFRN, 'owner');
	if($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['name'][0]['data'])
		$res['owner-name'] = unxmlify($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['name'][0]['data']);
	elseif($rawowner[0]['child'][NAMESPACE_DFRN]['name'][0]['data'])
		$res['owner-name'] = unxmlify($rawowner[0]['child'][NAMESPACE_DFRN]['name'][0]['data']);
	if($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['uri'][0]['data'])
		$res['owner-link'] = unxmlify($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['uri'][0]['data']);
	elseif($rawowner[0]['child'][NAMESPACE_DFRN]['uri'][0]['data'])
		$res['owner-link'] = unxmlify($rawowner[0]['child'][NAMESPACE_DFRN]['uri'][0]['data']);

	if($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'][0]['attribs']['']['rel'] === 'photo')
		$res['owner-avatar'] = unxmlify($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'][0]['attribs']['']['href']);
	elseif($rawowner[0]['child'][NAMESPACE_DFRN]['avatar'][0]['data'])
		$res['owner-avatar'] = unxmlify($rawowner[0]['child'][NAMESPACE_DFRN]['avatar'][0]['data']);

	$rawverb = $item->get_item_tags(NAMESPACE_ACTIVITY, 'verb');
	// select between supported verbs
	if($rawverb)
		$res['verb'] = unxmlify($rawverb[0]['data']);

	$rawobj = $item->get_item_tags(NAMESPACE_ACTIVITY, 'object');


	if($rawobj) {
		$res['object'] = '<object>' . "\n";
		if($rawobj[0]['child'][NAMESPACE_ACTIVITY]['object-type'][0]['data']) {
			$res['object-type'] = $rawobj[0]['child'][NAMESPACE_ACTIVITY]['object-type'][0]['data'];
			$res['object'] .= '<type>' . $rawobj[0]['child'][NAMESPACE_ACTIVITY]['object-type'][0]['data'] . '</type>' . "\n";
		}	
		if($rawobj[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['id'][0]['data'])
			$res['object'] .= '<id>' . $rawobj[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['id'][0]['data'] . '</id>' . "\n";
		
		if($rawobj[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'][0]['attribs']['']['rel'] === 'alternate')
			$res['object'] .= '<link>' . $rawobj[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'][0]['attribs']['']['href'] . '</link>' . "\n";
		if($rawobj[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['title'][0]['data'])
			$res['object'] .= '<title>' . $rawobj[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['title'][0]['data'] . '</title>' . "\n";
		if($rawobj[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['content'][0]['data']) {
			$body = $rawobj[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['content'][0]['data'];
			if(! $body)
				$body = $rawobj[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['summary'][0]['data'];
			if(strpos($body,'<')) {

				$body = preg_replace('#<object[^>]+>.+?' . 'http://www.youtube.com/((?:v|cp)/[A-Za-z0-9\-_=]+).+?</object>#s',
					'[youtube]$1[/youtube]', $body);

				$config = HTMLPurifier_Config::createDefault();
				$config->set('Core.DefinitionCache', null);

				$purifier = new HTMLPurifier($config);
				$body = $purifier->purify($body);
			}

			$body = html2bbcode($body);
			$res['object'] .= '<content>' . $body . '</content>' . "\n";
		}

		$res['object'] .= '</object>' . "\n";
	}

	return $res;
}

function item_store($arr) {

//print_r($arr);


	if($arr['gravity'])
		$arr['gravity'] = intval($arr['gravity']);
	elseif($arr['parent-uri'] == $arr['uri'])
		$arr['gravity'] = 0;
	elseif($arr['verb'] == ACTIVITY_POST)
		$arr['gravity'] = 6;

	if(! x($arr,'type'))
		$arr['type'] = 'remote';
	$arr['wall'] = ((intval($arr['wall'])) ? 1 : 0);
	$arr['uri'] = notags(trim($arr['uri']));
	$arr['author-name'] = notags(trim($arr['author-name']));
	$arr['author-link'] = notags(trim($arr['author-link']));
	$arr['author-avatar'] = notags(trim($arr['author-avatar']));
	$arr['owner-name'] = notags(trim($arr['owner-name']));
	$arr['owner-link'] = notags(trim($arr['owner-link']));
	$arr['owner-avatar'] = notags(trim($arr['owner-avatar']));
	$arr['created'] = ((x($arr,'created') !== false) ? datetime_convert('UTC','UTC',$arr['created']) : datetime_convert());
	$arr['edited']  = ((x($arr,'edited')  !== false) ? datetime_convert('UTC','UTC',$arr['edited'])  : datetime_convert());
	$arr['changed'] = datetime_convert();
	$arr['title'] = notags(trim($arr['title']));
	$arr['location'] = notags(trim($arr['location']));
	$arr['body'] = escape_tags(trim($arr['body']));
	$arr['last-child'] = intval($arr['last-child']);
	$arr['visible'] = ((x($arr,'visible') !== false) ? intval($arr['visible']) : 1);
	$arr['deleted'] = 0;
	$arr['parent-uri'] = notags(trim($arr['parent-uri']));
	$arr['verb'] = notags(trim($arr['verb']));
	$arr['object-type'] = notags(trim($arr['object-type']));
	$arr['object'] = trim($arr['object']);

	$parent_id = 0;
	$parent_missing = false;

	dbesc_array($arr);

	$r = q("INSERT INTO `item` (`" 
			. implode("`, `", array_keys($arr)) 
			. "`) VALUES ('" 
			. implode("', '", array_values($arr)) 
			. "')" );

	// find the parent and snarf the item id and ACL's

	$r = q("SELECT * FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
		dbesc($arr['parent-uri']),
		intval($arr['uid'])
	);

	if(count($r)) {
		$parent_id = $r[0]['id'];
		$allow_cid = $r[0]['allow_cid'];
		$allow_gid = $r[0]['allow_gid'];
		$deny_cid  = $r[0]['deny_cid'];
		$deny_gid  = $r[0]['deny_gid'];
	}
	else {
		$parent_missing = true;
	}

	$r = q("SELECT `id` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
		$arr['uri'],           // already dbesc'd
		intval($arr['uid'])
	);
	if(count($r))
		$current_post = $r[0]['id'];
	else
		return 0;

	if($parent_missing) {

		// perhaps the parent was deleted, but in any case, this thread is dead
		// and unfortunately our brand new item now has to be destroyed

		q("DELETE FROM `item` WHERE `id` = %d LIMIT 1",
			intval($current_post)
		);
		return 0;
	}

	// Set parent id - all of the parent's ACL's are also inherited by this post

	$r = q("UPDATE `item` SET `parent` = %d, `allow_cid` = '%s', `allow_gid` = '%s',
		`deny_cid` = '%s', `deny_gid` = '%s' WHERE `id` = %d LIMIT 1",
		intval($parent_id),
		dbesc($allow_cid),
		dbesc($allow_gid),
		dbesc($deny_cid),
		dbesc($deny_gid),
		intval($current_post)
	);

	return $current_post;
}

function get_item_contact($item,$contacts) {
	if(! count($contacts) || (! is_array($item)))
		return false;
	foreach($contacts as $contact) {
		if($contact['id'] == $item['contact-id']) {
			return $contact;
			break; // NOTREACHED
		}
	}
	return false;
}


function dfrn_deliver($contact,$atom,$debugging = false) {


	if((! strlen($contact['dfrn-id'])) && (! $contact['duplex']))
		return 3;

	$idtosend = $orig_id = (($contact['dfrn-id']) ? $contact['dfrn-id'] : $contact['issued-id']);

	if($contact['duplex'] && $contact['dfrn-id'])
		$idtosend = '0:' . $orig_id;
	if($contact['duplex'] && $contact['issued-id'])
		$idtosend = '1:' . $orig_id;		

	$url = $contact['notify'] . '?dfrn_id=' . $idtosend;

	if($debugging)
		echo "URL: $url";

	$xml = fetch_url($url);

	if($debugging)
		echo $xml;

	if(! $xml)
		return 3;

	$res = simplexml_load_string($xml);

	if((intval($res->status) != 0) || (! strlen($res->challenge)) || (! strlen($res->dfrn_id)))
		return (($res->status) ? $res->status : 3);

	$postvars     = array();
	$sent_dfrn_id = hex2bin($res->dfrn_id);
	$challenge    = hex2bin($res->challenge);

	$final_dfrn_id = '';

	if($contact['duplex'] && strlen($contact['prvkey'])) {
		openssl_private_decrypt($sent_dfrn_id,$final_dfrn_id,$contact['prvkey']);
		openssl_private_decrypt($challenge,$postvars['challenge'],$contact['prvkey']);
	}
	else {
		openssl_public_decrypt($sent_dfrn_id,$final_dfrn_id,$contact['pubkey']);
		openssl_public_decrypt($challenge,$postvars['challenge'],$contact['pubkey']);
	}

	$final_dfrn_id = substr($final_dfrn_id, 0, strpos($final_dfrn_id, '.'));

	if(strpos($final_dfrn_id,':') == 1)
		$final_dfrn_id = substr($final_dfrn_id,2);

	if($final_dfrn_id != $orig_id) {
		// did not decode properly - cannot trust this site 
		return 3;
	}

	$postvars['dfrn_id'] = $idtosend;


	if(($contact['rel']) && ($contact['rel'] != REL_FAN) && (! $contact['blocked']) && (! $contact['readonly'])) {
		$postvars['data'] = $atom;
	}
	else {
		$postvars['data'] = str_replace('<dfrn:comment-allow>1','<dfrn:comment-allow>0',$atom);
	}

	$xml = post_url($contact['notify'],$postvars);

	if($debugging)
		echo $xml;

	$res = simplexml_load_string($xml);

	return $res->status;
 
}


function consume_feed($xml,$importer,$contact, &$hub) {

	require_once('simplepie/simplepie.inc');

	$feed = new SimplePie();
	$feed->set_raw_data($xml);
	$feed->enable_order_by_date(false);
	$feed->init();

	// Check at the feed level for updated contact name and/or photo

	$name_updated  = '';
	$new_name = '';
	$photo_timestamp = '';
	$photo_url = '';


	$foundhub = $feed->get_link(0,'hub');

	if(strlen($foundhub))
		$hub = $foundhub;

	$rawtags = $feed->get_feed_tags( SIMPLEPIE_NAMESPACE_ATOM_10, 'author');
	if($rawtags) {
		$elems = $rawtags[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10];
		if($elems['name'][0]['attribs'][NAMESPACE_DFRN]['updated']) {
			$name_updated = $elems['name'][0]['attribs'][NAMESPACE_DFRN]['updated'];
			$new_name = $elems['name'][0]['data'];
		} 
		if(($elems['link'][0]['attribs']['']['rel'] === 'photo') && ($elems['link'][0]['attribs'][NAMESPACE_DFRN]['updated'])) {
			$photo_timestamp = datetime_convert('UTC','UTC',$elems['link'][0]['attribs'][NAMESPACE_DFRN]['updated']);
			$photo_url = $elems['link'][0]['attribs']['']['href'];
		}
	}
	if(! $photo_timestamp) {
		$photo_rawupdate = $feed->get_feed_tags(NAMESPACE_DFRN,'icon-updated');
		if($photo_rawupdate) {
			$photo_timestamp = datetime_convert('UTC','UTC',$photo_rawupdate[0]['data']);
			$photo_url = $feed->get_image_url();
		}
	}
	if(($photo_timestamp) && (strlen($photo_url)) && ($photo_timestamp > $contact['avatar-date'])) {

		require_once("Photo.php");
		$photo_failure = false;

		$r = q("SELECT `resource-id` FROM `photo` WHERE `contact-id` = %d AND `uid` = %d LIMIT 1",
			intval($contact['id']),
			intval($contact['uid'])
		);
		if(count($r)) {
			$resource_id = $r[0]['resource-id'];
			$img_str = fetch_url($photo_url,true);
			$img = new Photo($img_str);
			if($img->is_valid()) {
				q("DELETE FROM `photo` WHERE `resource-id` = '%s' AND contact-id` = %d AND `uid` = %d",
					dbesc($resource_id),
					intval($contact['id']),
					intval($contact['uid'])
				);

				$img->scaleImageSquare(175);
				
				$hash = $resource_id;
				$r = $img->store($contact['uid'], $contact['id'], $hash, basename($photo_url), t('Contact Photos') , 4);
				
				$img->scaleImage(80);
				$r = $img->store($contact['uid'], $contact['id'], $hash, basename($photo_url), t('Contact Photos') , 5);
				if($r)
					q("UPDATE `contact` SET `avatar-date` = '%s' WHERE `uid` = %d AND `id` = %d LIMIT 1",
						dbesc(datetime_convert()),
						intval($contact['uid']),
						intval($contact['id'])
					);
			}
		}
	}

	if(($name_updated) && (strlen($new_name)) && ($name_updated > $contact['name-date'])) {
		q("UPDATE `contact` SET `name` = '%s', `name-date` = '%s' WHERE `uid` = %d AND `id` = %d LIMIT 1",
			dbesc(notags(trim($new_name))),
			dbesc(datetime_convert()),
			intval($contact['uid']),
			intval($contact['id'])
		);
	}

	// Now process the feed
	if($feed->get_item_quantity()) {		
		foreach($feed->get_items() as $item) {

			$deleted = false;

			$rawdelete = $item->get_item_tags( NAMESPACE_TOMB, 'deleted-entry');
			if(isset($rawdelete[0]['attribs']['']['ref'])) {
				$uri = $rawthread[0]['attribs']['']['ref'];
				$deleted = true;
				if(isset($rawdelete[0]['attribs']['']['when'])) {
					$when = $rawthread[0]['attribs']['']['when'];
					$when = datetime_convert('UTC','UTC', $when, 'Y-m-d H:i:s');
				}
				else
					$when = datetime_convert('UTC','UTC','now','Y-m-d H:i:s');
			}
			if($deleted) {
				$r = q("SELECT * FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
					dbesc($uri),
					intval($importer['uid'])
				);
				if(count($r)) {
					$item = $r[0];
					if($item['uri'] == $item['parent-uri']) {
						$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s',
							`body` = '', `title` = ''
							WHERE `parent-uri` = '%s' AND `uid` = %d",
							dbesc($when),
							dbesc(datetime_convert()),
							dbesc($item['uri']),
							intval($importer['uid'])
						);
					}
					else {
						$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s',
							`body` = '', `title` = '' 
							WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
							dbesc($when),
							dbesc(datetime_convert()),
							dbesc($uri),
							intval($importer['uid'])
						);
						if($item['last-child']) {
							// ensure that last-child is set in case the comment that had it just got wiped.
							$q("UPDATE `item` SET `last-child` = 0, `changed` = '%s' WHERE `parent-uri` = '%s' AND `uid` = %d ",
								dbesc(datetime_convert()),
								dbesc($item['parent-uri']),
								intval($item['uid'])
							);
							// who is the last child now? 
							$r = q("SELECT `id` FROM `item` WHERE `parent-uri` = '%s' AND `type` != 'activity' AND `deleted` = 0 AND `uid` = %d 
								ORDER BY `created` DESC LIMIT 1",
									dbesc($item['parent-uri']),
									intval($importer['uid'])
							);
							if(count($r)) {
								q("UPDATE `item` SET `last-child` = 1 WHERE `id` = %d LIMIT 1",
									intval($r[0]['id'])
								);
							}
						}	
					}
				}	
				continue;
			}


			$is_reply = false;		
			$item_id = $item->get_id();
			$rawthread = $item->get_item_tags( NAMESPACE_THREAD,'in-reply-to');
			if(isset($rawthread[0]['attribs']['']['ref'])) {
				$is_reply = true;
				$parent_uri = $rawthread[0]['attribs']['']['ref'];
			}


			if($is_reply) {
	
				// Have we seen it? If not, import it.
	
				$item_id = $item->get_id();
	
				$r = q("SELECT `uid`, `last-child`, `edited` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
					dbesc($item_id),
					intval($importer['uid'])
				);
				// FIXME update content if 'updated' changes
				if(count($r)) {
					$allow = $item->get_item_tags( NAMESPACE_DFRN, 'comment-allow');
					if($allow && $allow[0]['data'] != $r[0]['last-child']) {
						$r = q("UPDATE `item` SET `last-child` = 0, `changed` = '%s' WHERE `parent-uri` = '%s' AND `uid` = %d",
							dbesc(datetime_convert()),
							dbesc($parent_uri),
							intval($importer['uid'])
						);
						$r = q("UPDATE `item` SET `last-child` = %d , `changed` = '%s'  WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
							intval($allow[0]['data']),
							dbesc(datetime_convert()),
							dbesc($item_id),
							intval($importer['uid'])
						);

					}
					continue;
				}
				$datarray = get_atom_elements($item);
				$datarray['parent-uri'] = $parent_uri;
				$datarray['uid'] = $importer['uid'];
				$datarray['contact-id'] = $contact['id'];
				if(($datarray['verb'] == ACTIVITY_LIKE) || ($datarray['verb'] == ACTIVITY_DISLIKE)) {
					$datarray['type'] = 'activity';
					$datarray['gravity'] = GRAVITY_LIKE;
				}

				$r = item_store($datarray);
				continue;
			}

			else {
				// Head post of a conversation. Have we seen it? If not, import it.

				$item_id = $item->get_id();
				$r = q("SELECT `uid`, `last-child`, `edited` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
					dbesc($item_id),
					intval($importer['uid'])
				);
				if(count($r)) {
					$allow = $item->get_item_tags( NAMESPACE_DFRN, 'comment-allow');
					if($allow && $allow[0]['data'] != $r[0]['last-child']) {
						$r = q("UPDATE `item` SET `last-child` = %d , `changed` = '%s' WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
							intval($allow[0]['data']),
							dbesc(datetime_convert()),
							dbesc($item_id),
							intval($importer['uid'])
						);
					}
					continue;
				}
				$datarray = get_atom_elements($item);
				$datarray['parent-uri'] = $item_id;
				$datarray['uid'] = $importer['uid'];
				$datarray['contact-id'] = $contact['id'];
				$r = item_store($datarray);
				continue;

			}
		}
	}

}


function subscribe_to_hub($url,$importer,$contact) {

	if(is_array($importer)) {
		$r = q("SELECT `nickname` FROM `user` WHERE `uid` = %d LIMIT 1",
			intval($importer['uid'])
		);
	}
	if(! count($r))
		return;

	$push_url = get_config('system','url') . '/pubsub/' . $r[0]['nickname'] . '/' . $contact['id'];

	$verify_token = random_string();

	$params= 'hub.mode=subscribe&hub.callback=' . urlencode($push_url) . '&hub.topic=' . urlencode($contact['poll']) . '&hub.verify=async&hub.verify_token=' . $verify_token;

	$r = q("UPDATE `contact` SET `hub-verify` = '%s' WHERE `id` = %d LIMIT 1",
		dbesc($verify_token),
		intval($contact['id'])
	);

	post_url($url,$params);			
	return;

}