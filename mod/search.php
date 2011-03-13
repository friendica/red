<?php


function search_post(&$a) {
	if(x($_POST,'search'))
		$a->data['search'] = $_POST['search'];
}


function search_content(&$a) {

	if(x($_SESSION,'theme'))
		unset($_SESSION['theme']);

	$o = '<div id="live-search"></div>' . "\r\n";

	$o .= '<h3>' . t('Search') . '</h3>';

	if(x($a->data,'search'))
		$search = notags(trim($a->data['search']));
	else
		$search = ((x($_GET,'search')) ? notags(trim(rawurldecode($_GET['search']))) : '');

	$o .= search($search);

	if(! $search)
		return $o;

	require_once("include/bbcode.php");
	require_once('include/security.php');

	$sql_extra = "
		AND `item`.`allow_cid` = '' 
		AND `item`.`allow_gid` = '' 
		AND `item`.`deny_cid`  = '' 
		AND `item`.`deny_gid`  = '' 
	";

	$r = q("SELECT COUNT(*) AS `total`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		WHERE `item`.`visible` = 1 AND `item`.`deleted` = 0
		AND ( `wall` = 1 OR `contact`.`uid` = %d )
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
		AND MATCH (`item`.`body`) AGAINST ( '%s' IN BOOLEAN MODE )
		$sql_extra ",
		intval(local_user()),
		dbesc($search)
	);

	if(count($r))
		$a->set_pager_total($r[0]['total']);

	if(! $r[0]['total']) {
		notice( t('No results.') . EOL);
		return $o;
	}

	$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
		`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`,
		`contact`.`network`, `contact`.`thumb`, `contact`.`self`, 
		`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`,
		`user`.`nickname`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		LEFT JOIN `user` ON `user`.`uid` = `item`.`uid` 
		WHERE `item`.`visible` = 1 AND `item`.`deleted` = 0
		AND ( `wall` = 1 OR `contact`.`uid` = %d )
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
		AND MATCH (`item`.`body`) AGAINST ( '%s' IN BOOLEAN MODE )
		$sql_extra
		ORDER BY `parent` DESC ",
		intval(local_user()),
		dbesc($search)
	);

	$tpl = load_view_file('view/search_item.tpl');
	$droptpl = load_view_file('view/wall_fake_drop.tpl');

	$return_url = $_SESSION['return_url'] = $a->cmd;

	if(count($r)) {

		foreach($r as $item) {

			$total       = 0;
			$comment     = '';
			$owner_url   = '';
			$owner_photo = '';
			$owner_name  = '';
			$sparkle     = '';
			
			if(((activity_match($item['verb'],ACTIVITY_LIKE)) || (activity_match($item['verb'],ACTIVITY_DISLIKE))) 
				&& ($item['id'] != $item['parent']))
				continue;

			$total ++;

			$profile_name   = ((strlen($item['author-name']))   ? $item['author-name']   : $item['name']);
			$profile_avatar = ((strlen($item['author-avatar'])) ? $item['author-avatar'] : $item['thumb']);
			$profile_link   = ((strlen($item['author-link']))   ? $item['author-link']   : $item['url']);


			$location = (($item['location']) ? '<a target="map" href="http://maps.google.com/?q=' . urlencode($item['location']) . '">' . $item['location'] . '</a>' : '');
			$coord = (($item['coord']) ? '<a target="map" href="http://maps.google.com/?q=' . urlencode($item['coord']) . '">' . $item['coord'] . '</a>' : '');
			if($coord) {
				if($location)
					$location .= '<br /><span class="smalltext">(' . $coord . ')</span>';
				else
					$location = '<span class="smalltext">' . $coord . '</span>';
			}

			$drop = replace_macros($droptpl,array('$id' => $item['id']));
			$lock = '<div class="wall-item-lock"></div>';

			$o .= replace_macros($tpl,array(
				'$id' => $item['item_id'],
				'$linktitle' => t('View $name\'s profile'),
				'$profile_url' => $profile_link,
				'$name' => $profile_name,
				'$sparkle' => $sparkle,
				'$lock' => $lock,
				'$thumb' => $profile_avatar,
				'$title' => $item['title'],
				'$body' => bbcode($item['body']),
				'$ago' => relative_date($item['created']),
				'$location' => $location,
				'$indent' => '',
				'$owner_url' => $owner_url,
				'$owner_photo' => $owner_photo,
				'$owner_name' => $owner_name,
				'$drop' => $drop,
				'$conv' => '<a href="' . $a->get_baseurl() . '/display/' . $item['nickname'] . '/' . $item['id'] . '">' . t('View in context') . '</a>'
			));

		}
	}

	$o .= paginate($a);

	return $o;
}

