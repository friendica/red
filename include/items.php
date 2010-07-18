<?php


function get_feed_for(&$a,$dfrn_id,$owner_id,$last_update) {

	// default permissions - anonymous user

	$sql_extra = " AND `allow_cid` = '' AND `allow_gid` = '' AND `deny_cid` = '' AND `deny_gid` = '' ";

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

			intval($_SESSION['visitor_id']),
			intval($_SESSION['visitor_id']),
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
		AND `item`.`type` != 'remote' AND `contact`.`blocked` = 0 
		AND `item`.`edited` > '%s'
		$sql_extra
		ORDER BY `parent` DESC, `id` ASC LIMIT 0, 300",
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
				'$id' => xmlify(((strlen($item['remote-id'])) ? $item['remote-id'] : "urn:X-dfrn:$baseurl:{$owner['uid']}:{$item['hash']}")),
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
					'$item_id' => xmlify(((strlen($item['remote-id'])) ? $item['remote-id'] : "urn:X-dfrn:$baseurl:{$owner['uid']}:{$item['hash']}")),
					'$title' => xmlify($item['name']),
					'$published' => xmlify(datetime_convert('UTC', 'UTC', $item['created'] . '+00:00' , 'Y-m-d\TH:i:s\Z')),
					'$updated' => xmlify(datetime_convert('UTC', 'UTC', $item['edited'] . '+00:00' , 'Y-m-d\TH:i:s\Z')),
					'$content' =>xmlify($item['body']),
					'$comment_allow' => (($item['last-child'] && strlen($contact['dfrn-id'] && (! $contact['blocked']))) ? 1 : 0)
				));
			}
			else {
				$atom .= replace_macros($cmnt_template, array(
					'$name' => xmlify($item['name']),
					'$profile_page' => xmlify($item['url']),
					'$thumb' => xmlify($item['thumb']),
					'$item_id' => xmlify(((strlen($item['remote-id'])) ? $item['remote-id'] : "urn:X-dfrn:$baseurl:{$owner['uid']}:{$item['hash']}")),
					'$title' => xmlify($item['title']),
					'$published' => xmlify(datetime_convert('UTC', 'UTC', $item['created'] . '+00:00' , 'Y-m-d\TH:i:s\Z')),
					'$updated' => xmlify(datetime_convert('UTC', 'UTC', $item['edited'] . '+00:00' , 'Y-m-d\TH:i:s\Z')),
					'$content' =>xmlify($item['body']),
					'$parent_id' => xmlify("urn:X-dfrn:$baseurl:{$owner['uid']}:{$items[0]['hash']}"),
					'$comment_allow' => (($item['last-child']) ? 1 : 0)
				));
			}
		}
	}


	$atom .= "</feed>\r\n";

	return $atom;
} 