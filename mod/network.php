<?php


function network_init(&$a) {
	require_once('include/group.php');
	$a->page['aside'] .= group_side('network','network');
}


function network_content(&$a, $update = 0) {

	if(! local_user())
		return;

	require_once("include/bbcode.php");

	$contact_id = $a->cid;

	$group = 0;

	if(! $update) {
		$o .= '<script>	$(document).ready(function() { $(\'#nav-network-link\').addClass(\'nav-selected\'); });</script>';

			// pull out the group here because the updater might have different args
		if($a->argc > 1) {
			$group = intval($a->argv[1]);
			$group_acl = array('allow_gid' => '<' . $group . '>');
		}
		$_SESSION['return_url'] = $a->cmd;

		$tpl = load_view_file('view/jot-header.tpl');
	
		$a->page['htmlhead'] .= replace_macros($tpl, array('$baseurl' => $a->get_baseurl()));

		require_once('view/acl_selectors.php');

		$tpl = load_view_file("view/jot.tpl");
		
		if(($group) || (is_array($a->user) && ((strlen($a->user['allow_cid'])) || (strlen($a->user['allow_gid'])) || (strlen($a->user['deny_cid'])) || (strlen($a->user['deny_gid'])))))
				$lockstate = 'lock';
			else
				$lockstate = 'unlock';

		$o .= replace_macros($tpl,array(
			'$return_path' => $a->cmd,
			'$baseurl' => $a->get_baseurl(),
			'$defloc' => $a->user['default-location'],
			'$visitor' => 'block',
			'$lockstate' => $lockstate,
			'$acl' => populate_acl(($group) ? $group_acl : $a->user),
			'$bang' => (($group) ? '!' : ''),
			'$profile_uid' => $_SESSION['uid']
		));


		// The special div is needed for liveUpdate to kick in for this page.
		// We only launch liveUpdate if you are on the front page, you aren't
		// filtering by group and also you aren't writing a comment (the last
		// criteria is discovered in javascript).

		if($a->pager['start'] == 0 && $a->argc == 1) {
			$o .= '<div id="live-network"></div>' . "\r\n";
			$o .= "<script> var profile_uid = " . $_SESSION['uid'] . "; </script>\r\n";
		}

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

	$sql_extra = " AND `item`.`parent` IN ( SELECT `parent` FROM `item` WHERE `id` = `parent` ) ";

	if($group) {
		$r = q("SELECT `name`, `id` FROM `group` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($group),
			intval($_SESSION['uid'])
		);
		if(! count($r)) {
			notice( t('No such group') . EOL );
			goaway($a->get_baseurl() . '/network');
			return; // NOTREACHED
		}

		$contacts = expand_groups(array($group));
		$contact_str = implode(',',$contacts);
                $sql_extra = " AND `item`.`parent` IN ( SELECT `parent` FROM `item` WHERE `id` = `parent` AND `contact-id` IN ( $contact_str )) ";
                $o = '<h4>' . t('Group: ') . $r[0]['name'] . '</h4>' . $o;

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
		`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`,
		`contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`, 
		`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
		$sql_extra
		ORDER BY `parent` DESC, `gravity` ASC, `created` ASC LIMIT %d ,%d ",
		intval($_SESSION['uid']),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);


	$cmnt_tpl = load_view_file('view/comment_item.tpl');
	$like_tpl = load_view_file('view/like.tpl');
	$tpl = load_view_file('view/wall_item.tpl');
	$wallwall = load_view_file('view/wallwall_item.tpl');

	$alike = array();
	$dlike = array();
	
	if(count($r)) {
		foreach($r as $item) {

			$sparkle = '';

			if(($item['verb'] == ACTIVITY_LIKE) && ($item['id'] != $item['parent'])) {
				$url = $item['url'];
				if(($item['rel'] == REL_VIP || $item['rel'] == REL_BUD) && (! $item['self'])) { 
					$url = $a->get_baseurl() . '/redir/' . $item['contact-id'];
					$sparkle = ' class="sparkle"';
				}
				if(! is_array($alike[$item['parent'] . '-l']))
					$alike[$item['parent'] . '-l'] = array();
				$alike[$item['parent']] ++;
				$alike[$item['parent'] . '-l'][] = '<a href="'. $url . '"' . $sparkle . '>' . $item['name'] . '</a>';
			}
			if(($item['verb'] == ACTIVITY_DISLIKE) && ($item['id'] != $item['parent'])) {
				$url = $item['url'];
				if(($item['rel'] == REL_VIP || $item['rel'] == REL_BUD) && (! $item['self'])) { 
					$url = $a->get_baseurl() . '/redir/' . $item['contact-id'];
					$sparkle = ' class="sparkle"';
				}
				if(! is_array($dlike[$item['parent'] . '-l']))
					$dlike[$item['parent'] . '-l'] = array();
				$dlike[$item['parent']] ++;
				$dlike[$item['parent'] . '-l'][] = '<a href="'. $url . '"' . $sparkle . '>' . $item['name'] . '</a>';
			}
		}

		foreach($r as $item) {

			$comment = '';
			$template = $tpl;
			$commentww = '';

			$profile_url = $item['url'];
			$redirect_url = $a->get_baseurl() . '/redir/' . $item['cid'] ;

			if((($item['verb'] == ACTIVITY_LIKE) || ($item['verb'] == ACTIVITY_DISLIKE)) && ($item['id'] != $item['parent'])) 
				continue;

			// Top-level wall post not written by the wall owner (wall-to-wall)
			// First figure out who owns it. 

			$osparkle = '';

			if(($item['parent'] == $item['item_id']) && (! $item['self'])) {

				if($item['type'] === 'wall') {
					// I do. Put me on the left of the wall-to-wall notice.
					$owner_url = $a->contact['url'];
					$owner_photo = $a->contact['thumb'];
					$owner_name = $a->contact['name'];
					$template = $wallwall;
					$commentww = 'ww';	
				}
				if($item['type'] === 'remote' && ($item['owner-link'] != $item['author-link'])) {
					// Could be anybody. 
					$owner_url = $item['owner-link'];
					$owner_photo = $item['owner-avatar'];
					$owner_name = $item['owner-name'];
					$template = $wallwall;
					$commentww = 'ww';
					// If it is our contact, use a friendly redirect link
					if(($item['owner-link'] == $item['url']) 
						&& ($item['rel'] == REL_VIP || $item['rel'] == REL_BUD)) {
						$owner_url = $redirect_url;
						$osparkle = ' sparkle';
					}

				}
			}

			if($update)
				$return_url = $_SESSION['return_url'];
			else
				$return_url = $_SESSION['return_url'] = $a->cmd;

			$likebuttons = '';
			if($item['id'] == $item['parent']) {
				$likebuttons = replace_macros($like_tpl,array('$id' => $item['id']));
			}

			if($item['last-child']) {
				$comment = replace_macros($cmnt_tpl,array(
					'$return_path' => $_SESSION['return_url'],
					'$type' => 'net-comment',
					'$id' => $item['item_id'],
					'$parent' => $item['parent'],
					'$profile_uid' =>  $_SESSION['uid'],
					'$mylink' => $a->contact['url'],
					'$mytitle' => t('This is you'),
					'$myphoto' => $a->contact['thumb'],
					'$ww' => $commentww
				));
			}


			$drop = replace_macros(load_view_file('view/wall_item_drop.tpl'), array('$id' => $item['id']));


	
			if(($item['rel'] == REL_VIP || $item['rel'] == REL_BUD) && (! $item['self'] )) {
				$profile_url = $redirect_url;
				$sparkle = ' sparkle';
			}

			$photo = $item['photo'];
			$thumb = $item['thumb'];

			// Post was remotely authored.

			$profile_name = ((strlen($item['author-name'])) ? $item['author-name'] : $item['name']);
			$profile_avatar = ((strlen($item['author-avatar'])) ? $item['author-avatar'] : $thumb);

			$profile_link = $profile_url;

			// Can we use our special contact URL for this author? 

			if(strlen($item['author-link'])) {
				if($item['author-link'] == $item['url'] && (! $item['self'])) {
					$profile_link = $redirect_url;
					$sparkle = ' sparkle';
				}
				else {
					$profile_link = $item['author-link'];
					$sparkle = '';
				}
			}


			$like    = (($alike[$item['id']]) ? format_like($alike[$item['id']],$alike[$item['id'] . '-l'],'like',$item['id']) : '');
			$dislike = (($dlike[$item['id']]) ? format_like($dlike[$item['id']],$dlike[$item['id'] . '-l'],'dislike',$item['id']) : '');


			// Build the HTML

			$o .= replace_macros($template,array(
				'$id' => $item['item_id'],
				'$profile_url' => $profile_link,
				'$name' => $profile_name,
				'$thumb' => $profile_avatar,
				'$osparkle' => $osparkle,
				'$sparkle' => $sparkle,
				'$title' => $item['title'],
				'$body' => bbcode($item['body']),
				'$ago' => relative_date($item['created']),
				'$location' => (($item['location']) ? '<a target="map" href="http://maps.google.com/?q=' . urlencode($item['location']) . '">' . $item['location'] . '</a>' : ''),
				'$indent' => (($item['parent'] != $item['item_id']) ? ' comment' : ''),
				'$owner_url' => $owner_url,
				'$owner_photo' => $owner_photo,
				'$owner_name' => $owner_name,
				'$drop' => $drop,
				'$vote' => $likebuttons,
				'$like' => $like,
				'$dislike' => $dislike,
				'$comment' => $comment
			));
		}
	}

	if(! $update)
		$o .= paginate($a);

	return $o;
}