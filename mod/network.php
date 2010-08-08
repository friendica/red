<?php


function network_init(&$a) {
	require_once('include/group.php');
	$a->page['aside'] .= group_side('network','network');
}


function network_content(&$a, $update = false) {

	if(! local_user())
		return;

	require_once("include/bbcode.php");

	$contact_id = $a->cid;

	$group = 0;

	if(! $update) {
			// pull out the group here because the updater might have different args
		if($a->argc > 1)
			$group = intval($a->argv[1]);

		$_SESSION['return_url'] = $a->cmd;

		$tpl = file_get_contents('view/jot-header.tpl');
	
		$a->page['htmlhead'] .= replace_macros($tpl, array('$baseurl' => $a->get_baseurl()));

		require_once('view/acl_selectors.php');

		$tpl = file_get_contents("view/jot.tpl");

		$o .= replace_macros($tpl,array(
			'$return_path' => $a->cmd,
			'$baseurl' => $a->get_baseurl(),
			'$visitor' => 'block',
			'$lockstate' => 'unlock',
			'$acl' => populate_acl($a->user),
			'$profile_uid' => $_SESSION['uid']
		));


		// The special div is needed for liveUpdate to kick in for this page.
		// We only launch liveUpdate if you are on the front page, you aren't
		// filtering by group and also you aren't writing a comment (the last
		// criteria is discovered in javascript).

		if($a->pager['start'] == 0 && $a->argc == 1)
			$o .= '<div id="live-network"></div>' . "\r\n";
	}

	// We aren't going to try and figure out at the item, group, and page level 
	// which items you've seen and which you haven't. You're looking at some
	// subset of items, so just mark everything seen. 
	
	$r = q("UPDATE `item` SET `unseen` = 0 
		WHERE `unseen` = 1 AND `uid` = %d",
		intval($_SESSION['uid'])
	);

	// We don't have to deal with ACL's on this page. You're looking at everything
	// that belongs to you, hence you can see all of it. We will filter by group if
	// desired. 

	// TODO: Perhaps we should limit the group filter to those with the group in the ACL,
	// rather than just the contact-id of the post.
	// Otherwise we're not showing complete conversations, unless all the conversants
	// happen to be in the group.

	$sql_extra = ''; 

	if($group) {
		$r = q("SELECT `id` FROM `group` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($group),
			intval($_SESSION['uid'])
		);
		if(! count($r)) {
			notice("No such group");
			goaway($a->get_baseurl() . '/network');
			return; // NOTREACHED
		}

		$contacts = expand_groups(array($group));
		$contacts[] = $_SESSION['cid'];
		$contact_str = implode(',',$contacts);
		$sql_extra = dbesc(" AND `contact`.`id` IN ( $contact_str ) ");

	}

	$r = q("SELECT COUNT(*) AS `total`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
		$sql_extra ",
		intval($_SESSION['uid'])
	);

	if(count($r))
		$a->set_pager_total($r[0]['total']);

	$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
		`contact`.`name`, `contact`.`photo`, `contact`.`url`, 
		`contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`, 
		`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
		$sql_extra
		ORDER BY `parent` DESC, `created` ASC LIMIT %d ,%d ",
		intval($_SESSION['uid']),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);


	$cmnt_tpl = file_get_contents('view/comment_item.tpl');

	$tpl = file_get_contents('view/wall_item.tpl');
	$wallwall = file_get_contents('view/wallwall_item.tpl');

	if(count($r)) {
		foreach($r as $item) {

			$comment = '';
			$template = $tpl;
			$commentww = '';

			$profile_url = $item['url'];
			$redirect_url = $a->get_baseurl() . '/redir/' . $item['cid'] ;


			// Top-level wall post not written by the wall owner (wall-to-wall)
			// First figure out who owns it. 

			if(($item['parent'] == $item['item_id']) && (! $item['self'])) {
				
				if($item['type'] == 'wall') {
					// I do. Put me on the left of the wall-to-wall notice.
					$owner_url = $a->contact['url'];
					$owner_photo = $a->contact['thumb'];
					$owner_name = $a->contact['name'];
					$template = $wallwall;
					$commentww = 'ww';	
				}
				if($item['type'] == 'remote' && ($item['owner-link'] != $item['author-link'])) {
					// Could be anybody. 
					$owner_url = $item['owner-link'];
					$owner_photo = $item['owner-avatar'];
					$owner_name = $item['owner-name'];
					$template = $wallwall;
					$commentww = 'ww';
					// If it is our contact, use a friendly redirect link
					if($item['owner-link'] == $item['url'])
						$owner_url = $redirect_url;

				}
			}

			if($update)
				$return_url = $_SESSION['return_url'];
			else
				$return_url = $_SESSION['return_url'] = $a->cmd;


			if($item['last-child']) {
				$comment = replace_macros($cmnt_tpl,array(
					'$return_path' => $_SESSION['return_url'],
					'$type' => 'net-comment',
					'$id' => $item['item_id'],
					'$parent' => $item['parent'],
					'$profile_uid' =>  $_SESSION['uid'],
					'$ww' => $commentww
				));
			}


			$drop = replace_macros(file_get_contents('view/wall_item_drop.tpl'), array('$id' => $item['id']));


	
			if(($item['contact-uid'] == $_SESSION['uid']) && (strlen($item['dfrn-id'])) && (! $item['self'] ))
				$profile_url = $redirect_url;

			$photo = $item['photo'];
			$thumb = $item['thumb'];

			// Post was remotely authored.

			$profile_name = ((strlen($item['author-name'])) ? $item['author-name'] : $item['name']);
			$profile_avatar = ((strlen($item['author-avatar'])) ? $item['author-avatar'] : $thumb);

			$profile_link = $profile_url;

			// Can we use our special contact URL for this author? 

			if(strlen($item['author-link'])) {
				if($item['author-link'] == $item['url'])
					$profile_link = $redirect_url;
				else
					$profile_link = $item['author-link'];
			}

			// Build the HTML

			$o .= replace_macros($template,array(
				'$id' => $item['item_id'],
				'$profile_url' => $profile_link,
				'$name' => $profile_name,
				'$thumb' => $profile_avatar,
				'$title' => $item['title'],
				'$body' => bbcode($item['body']),
				'$ago' => relative_date($item['created']),
				'$indent' => (($item['parent'] != $item['item_id']) ? ' comment' : ''),
				'$owner_url' => $owner_url,
				'$owner_photo' => $owner_photo,
				'$owner_name' => $owner_name,
				'$drop' => $drop,
				'$comment' => $comment
			));
		}
	}

	if(! $update)
		$o .= paginate($a);

	return $o;
}