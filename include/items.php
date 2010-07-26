<?php


function get_feed_for(&$a,$dfrn_id,$owner_id,$last_update) {

	// default permissions - anonymous user

	$sql_extra = " AND `allow_cid` = '' AND `allow_gid` = '' AND `deny_cid` = '' AND `deny_gid` = '' ";

	if(strlen($owner_id) && ! intval($owner_id)) {
		$r = q("SELECT `uid` FROM `user` WHERE `nickname` = '%s' LIMIT 1",
			dbesc($owner_id)
		);
		if(count($r))
			$owner_id = $r[0]['uid'];
	}

	$r = q("SELECT * FROM `contact` WHERE `self` = 1 AND `uid` = %d LIMIT 1",
		intval($owner_id)
	);
	if(count($r))
		$owner = $r[0];
	else
		killme();

	if($dfrn_id != '*') {

		$r = q("SELECT * FROM `contact` WHERE `issued-id` = '%s' LIMIT 1",
			dbesc($dfrn_id)
		);
		if(! count($r))
			return false;

		$contact = $r[0];
		$groups = init_groups_visitor($contact['id']);


		$gs = '<<>>'; // should be impossible to match
		if(count($groups)) {
			foreach($groups as $g)
				$gs .= '|<' . intval($g) . '>';
		} 
		$sql_extra = sprintf(
			" AND ( `allow_cid` = '' OR `allow_cid` REGEXP '<%d>' ) 
			AND ( `deny_cid` = '' OR  NOT `deny_cid` REGEXP '<%d>' ) 
			AND ( `allow_gid` = '' OR `allow_gid` REGEXP '%s' )
			AND ( `deny_gid` = '' OR NOT `deny_gid` REGEXP '%s') ",

			intval($contact['id']),
			intval($contact['id']),
			dbesc($gs),
			dbesc($gs)
		);
	}

	if(! strlen($last_update))
		$last_update = 'now - 30 days';
	$check_date = datetime_convert('UTC','UTC',$last_update,'Y-m-d H:i:s');

	$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
		`contact`.`name`, `contact`.`photo`, `contact`.`url`, 
		`contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`, 
		`contact`.`id` AS `contact-id`, `contact`.`uid` AS `contact-uid`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
		AND NOT `item`.`type` IN ( 'remote', 'net-comment') AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
		AND `item`.`edited` > '%s'
		$sql_extra
		ORDER BY `parent` ASC, `created` ASC LIMIT 0, 300",
		intval($owner_id),
		dbesc($check_date)
	);
	if(! count($r))
		killme();

	$items = $r;

	$feed_template = file_get_contents('view/atom_feed.tpl');
	$tomb_template = file_get_contents('view/atom_tomb.tpl');
	$item_template = file_get_contents('view/atom_item.tpl');
	$cmnt_template = file_get_contents('view/atom_cmnt.tpl');

	$atom = '';


	$atom .= replace_macros($feed_template, array(
			'$feed_id' => xmlify($a->get_baseurl()),
			'$feed_title' => xmlify($owner['name']),
			'$feed_updated' => xmlify(datetime_convert('UTC', 'UTC', $updated . '+00:00' , 'Y-m-d\TH:i:s\Z')) ,
			'$name' => xmlify($owner['name']),
			'$profile_page' => xmlify($owner['url']),
			'$photo' => xmlify($owner['photo'])
	));

	foreach($items as $item) {
		if($item['deleted']) {
			$atom .= replace_macros($tomb_template, array(
				'$id' => xmlify($item['uri']),
				'$updated' => xmlify(datetime_convert('UTC', 'UTC', $item['edited'] . '+00:00' , 'Y-m-d\TH:i:s\Z'))
			));
		}
		else {

			if($item['parent'] == $item['id']) {
				$atom .= replace_macros($item_template, array(
					'$name' => xmlify($item['name']),
					'$profile_page' => xmlify($item['url']),
					'$thumb' => xmlify($item['thumb']),
					'$owner_name' => xmlify($item['owner-name']),
					'$owner_profile_page' => xmlify($item['owner-link']),
					'$owner_thumb' => xmlify($item['owner-avatar']),
					'$item_id' => xmlify($item['uri']),
					'$title' => xmlify($item['name']),
					'$published' => xmlify(datetime_convert('UTC', 'UTC', $item['created'] . '+00:00' , 'Y-m-d\TH:i:s\Z')),
					'$updated' => xmlify(datetime_convert('UTC', 'UTC', $item['edited'] . '+00:00' , 'Y-m-d\TH:i:s\Z')),
					'$content' =>xmlify($item['body']),
					'$comment_allow' => (($item['last-child'] && strlen($contact['dfrn-id'])) ? 1 : 0)
				));
			}
			else {
				$atom .= replace_macros($cmnt_template, array(
					'$name' => xmlify($item['name']),
					'$profile_page' => xmlify($item['url']),
					'$thumb' => xmlify($item['thumb']),
					'$item_id' => xmlify($item['uri']),
					'$title' => xmlify($item['title']),
					'$published' => xmlify(datetime_convert('UTC', 'UTC', $item['created'] . '+00:00' , 'Y-m-d\TH:i:s\Z')),
					'$updated' => xmlify(datetime_convert('UTC', 'UTC', $item['edited'] . '+00:00' , 'Y-m-d\TH:i:s\Z')),
					'$content' =>xmlify($item['body']),
					'$parent_id' => xmlify($item['parent-uri']),
					'$comment_allow' => (($item['last-child']) ? 1 : 0)
				));
			}
		}
	}

	$atom .= "</feed>\r\n";

	return $atom;
} 




function get_atom_elements($item) {

	$res = array();

	$author = $item->get_author();
	$res['author-name'] = unxmlify($author->get_name());
	$res['author-link'] = unxmlify($author->get_link());
	$res['author-avatar'] = unxmlify($author->get_avatar());
	$res['uri'] = unxmlify($item->get_id());
	$res['title'] = unxmlify($item->get_title());
	$res['body'] = unxmlify($item->get_content());

	$maxlen = get_max_import_size();
	if($maxlen && (strlen($res['body']) > $maxlen))
		$res['body'] = substr($res['body'],0, $maxlen);

	$allow = $item->get_item_tags('http://purl.org/macgirvin/dfrn/1.0','comment-allow');
	if($allow && $allow[0]['data'] == 1)
		$res['last-child'] = 1;
	else
		$res['last-child'] = 0;

	$rawcreated = $item->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10,'published');
	if($rawcreated)
		$res['created'] = unxmlify($rawcreated[0]['data']);

	$rawedited = $item->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10,'updated');
	if($rawedited)
		$res['edited'] = unxmlify($rawcreated[0]['data']);

	$rawowner = $item->get_item_tags('http://purl.org/macgirvin/dfrn/1.0', 'owner');
	if($rawowner[0]['child']['http://purl.org/macgirvin/dfrn/1.0']['name'][0]['data'])
		$res['owner-name'] = unxmlify($rawowner[0]['child']['http://purl.org/macgirvin/dfrn/1.0']['name'][0]['data']);
	if($rawowner[0]['child']['http://purl.org/macgirvin/dfrn/1.0']['uri'][0]['data'])
		$res['owner-link'] = unxmlify($rawowner[0]['child']['http://purl.org/macgirvin/dfrn/1.0']['uri'][0]['data']);
	if($rawowner[0]['child']['http://purl.org/macgirvin/dfrn/1.0']['avatar'][0]['data'])
		$res['owner-avatar'] = unxmlify($rawowner[0]['child']['http://purl.org/macgirvin/dfrn/1.0']['avatar'][0]['data']);

	return $res;
}

function post_remote($a,$arr) {


	if(! x($arr,'type'))
		$arr['type'] = 'remote';
	$arr['uri'] = notags(trim($arr['uri']));
	$arr['author-name'] = notags(trim($arr['author-name']));
	$arr['author-link'] = notags(trim($arr['author-link']));
	$arr['author-avatar'] = notags(trim($arr['author-avatar']));
	$arr['owner-name'] = notags(trim($arr['owner-name']));
	$arr['owner-link'] = notags(trim($arr['owner-link']));
	$arr['owner-avatar'] = notags(trim($arr['owner-avatar']));
	$arr['created'] = datetime_convert('UTC','UTC',$arr['created'],'Y-m-d H:i:s');
	$arr['edited'] = datetime_convert('UTC','UTC',$arr['edited'],'Y-m-d H:i:s');
	$arr['title'] = notags(trim($arr['title']));
	$arr['body'] = escape_tags(trim($arr['body']));
	$arr['last-child'] = intval($arr['last-child']);
	$arr['visible'] = 1;
	$arr['deleted'] = 0;
	$arr['parent-uri'] = notags(trim($arr['parent-uri']));

	$parent_id = 0;

	dbesc_array($arr);
//dbg(3);
	$r = q("INSERT INTO `item` (`" 
			. implode("`, `", array_keys($arr)) 
			. "`) VALUES ('" 
			. implode("', '", array_values($arr)) 
			. "')" );

	$r = q("SELECT `id` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
		dbesc($arr['parent-uri']),
		intval($arr['uid'])
	);

	if(count($r))
		$parent_id = $r[0]['id'];
	else {
		// if parent is missing, what do we do?
	}

	$r = q("SELECT `id` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
		$arr['uri'],
		intval($arr['uid'])
	);
	if(count($r))
		$current_post = $r[0]['id'];

	$r = q("UPDATE `item` SET `parent` = %d WHERE `id` = %d LIMIT 1",
		intval($parent_id),
		intval($current_post)
	);

	return $current_post;
}
