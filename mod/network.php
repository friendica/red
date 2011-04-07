<?php


function network_init(&$a) {
	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}
  
  
	require_once('include/group.php');
	if(! x($a->page,'aside'))
		$a->page['aside'] = '';

	$a->page['aside'] .= '<div id="network-new-link">';

	if(($a->argc > 1 && $a->argv[1] === 'new') || ($a->argc > 2 && $a->argv[2] === 'new'))
		$a->page['aside'] .= '<a href="' . $a->get_baseurl() . '/' . str_replace('/new', '', $a->cmd) . '">' . t('Normal View') . '</a>';
	else 
		$a->page['aside'] .= '<a href="' . $a->get_baseurl() . '/' . $a->cmd . '/new' . '">' . t('New Item View') . '</a>';

	$a->page['aside'] .= '</div>';

	$a->page['aside'] .= group_side('network','network');
}


function network_content(&$a, $update = 0) {

	if(! local_user())
    	return login(false);

	$o = '';

	require_once("include/bbcode.php");

	$contact_id = $a->cid;

	$group = 0;

	$nouveau = false;
	require_once('include/acl_selectors.php');

	if(($a->argc > 2) && $a->argv[2] === 'new')
		$nouveau = true;

	if($a->argc > 1) {
		if($a->argv[1] === 'new')
			$nouveau = true;
		else {
			$group = intval($a->argv[1]);
			$group_acl = array('allow_gid' => '<' . $group . '>');
		}
	}

	if(! $update) {
		if(group) {
			if(($t = group_public_members($group)) && (! get_pconfig(local_user(),'system','nowarn_insecure'))) {
				$plural_form = sprintf( tt('%d member', '%d members', $t), $t);
				notice( sprintf( t('Warning: This group contains %s from an insecure network.'), $plural_form ) . EOL);
				notice( t('Private messages to this group are at risk of public disclosure.') . EOL);
			}
		}

		$o .= '<script>	$(document).ready(function() { $(\'#nav-network-link\').addClass(\'nav-selected\'); });</script>';

		$_SESSION['return_url'] = $a->cmd;

		$geotag = (($a->user['allow_location']) ? load_view_file('view/jot_geotag.tpl') : '');

		$tpl = load_view_file('view/jot-header.tpl');
	
		$a->page['htmlhead'] .= replace_macros($tpl, array(
			'$baseurl' => $a->get_baseurl(),
			'$geotag' => $geotag,
			'$nickname' => $a->user['nickname'],
			'$linkurl' => t('Please enter a link URL:'),
			'$utubeurl' => t('Please enter a YouTube link:'),
			'$vidurl' => t("Please enter a video\x28.ogg\x29 link/URL:"),
			'$audurl' => t("Please enter an audio\x28.ogg\x29 link/URL:"),
			'$whereareu' => t('Where are you right now?'),
			'$title' => t('Enter a title for this item') 
		));


		$tpl = load_view_file("view/jot.tpl");
		
		if(($group) || (is_array($a->user) && ((strlen($a->user['allow_cid'])) || (strlen($a->user['allow_gid'])) || (strlen($a->user['deny_cid'])) || (strlen($a->user['deny_gid'])))))
				$lockstate = 'lock';
			else
				$lockstate = 'unlock';

		$celeb = ((($a->user['page-flags'] == PAGE_SOAPBOX) || ($a->user['page-flags'] == PAGE_COMMUNITY)) ? true : false);

		$jotplugins = '';
		$jotnets = '';
		call_hooks('jot_tool', $jotplugins);
		call_hooks('jot_networks', $jotnets);

		$tpl = replace_macros($tpl,array('$jotplugins' => $jotplugins));	

		$o .= replace_macros($tpl,array(
			'$return_path' => $a->cmd,
			'$action' => 'item',
			'$share' => t('Share'),
			'$upload' => t('Upload photo'),
			'$weblink' => t('Insert web link'),
			'$youtube' => t('Insert YouTube video'),
			'$video' => t('Insert Vorbis [.ogg] video'),
			'$audio' => t('Insert Vorbis [.ogg] audio'),
			'$setloc' => t('Set your location'),
			'$noloc' => t('Clear browser location'),
			'$title' => t('Set title'),
			'$wait' => t('Please wait'),
			'$permset' => t('Permission settings'),
			'$content' => '',
			'$post_id' => '',
			'$baseurl' => $a->get_baseurl(),
			'$defloc' => $a->user['default-location'],
			'$visitor' => 'block',
			'$emailcc' => t('CC: email addresses'),
			'$jotnets' => $jotnets,
			'$emtitle' => t('Example: bob@example.com, mary@example.com'),
			'$lockstate' => $lockstate,
			'$acl' => populate_acl((($group) ? $group_acl : $a->user), $celeb),
			'$bang' => (($group) ? '!' : ''),
			'$profile_uid' => local_user()
		));


		// The special div is needed for liveUpdate to kick in for this page.
		// We only launch liveUpdate if you are on the front page, you aren't
		// filtering by group and also you aren't writing a comment (the last
		// criteria is discovered in javascript).

			$o .= '<div id="live-network"></div>' . "\r\n";
			$o .= "<script> var profile_uid = " . $_SESSION['uid'] 
				. "; var netargs = '" . substr($a->cmd,8) 
				. "'; var profile_page = " . $a->pager['page'] . "; </script>\r\n";

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
			if($update)
				killme();
			notice( t('No such group') . EOL );
			goaway($a->get_baseurl() . '/network');
			return; // NOTREACHED
		}

		$contacts = expand_groups(array($group));
		if((is_array($contacts)) && count($contacts)) {
			$contact_str = implode(',',$contacts);
		}
		else {
				$contact_str = ' 0 ';
				notice( t('Group is empty'));
		}

		$sql_extra = " AND `item`.`parent` IN ( SELECT `parent` FROM `item` WHERE `id` = `parent` AND `contact-id` IN ( $contact_str )) ";
		$o = '<h2>' . t('Group: ') . $r[0]['name'] . '</h2>' . $o;
	}

	if((! $group) && (! $update))
		$o .= get_birthdays();


	$r = q("SELECT COUNT(*) AS `total`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
		$sql_extra ",
		intval($_SESSION['uid'])
	);

	if(count($r)) {
		$a->set_pager_total($r[0]['total']);
		$a->set_pager_itemspage(40);
	}


	if($nouveau) {

		// "New Item View" - show all items unthreaded in reverse created date order

		$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
			`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`,
			`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
			`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
			FROM `item`, `contact`
			WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
			AND `contact`.`id` = `item`.`contact-id`
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			$sql_extra
			ORDER BY `item`.`created` DESC LIMIT %d ,%d ",
			intval($_SESSION['uid']),
			intval($a->pager['start']),
			intval($a->pager['itemspage'])
		);
		
	}
	else {

		// Normal conversation view
		// First fetch a known number of parent items

		$r = q("SELECT `item`.`id` AS `item_id`, `contact`.`uid` AS `contact_uid`
			FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
			WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			AND `item`.`parent` = `item`.`id`
			$sql_extra
			ORDER BY `item`.`created` DESC LIMIT %d ,%d ",
			intval(local_user()),
			intval($a->pager['start']),
			intval($a->pager['itemspage'])
		);


		// Then fetch all the children of the parents that are on this page

		$parents_arr = array();
		$parents_str = '';

		if(count($r)) {
			foreach($r as $rr)
				$parents_arr[] = $rr['item_id'];
			$parents_str = implode(', ', $parents_arr);

			$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
				`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`,
				`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
				`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
				FROM `item`, (SELECT `p`.`id`,`p`.`created` FROM `item` AS `p` WHERE `p`.`parent`=`p`.`id`) as `parentitem`, `contact`
				WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
				AND `contact`.`id` = `item`.`contact-id`
				AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
				AND `item`.`parent` = `parentitem`.`id` AND `item`.`parent` IN ( %s )
				$sql_extra
				ORDER BY `parentitem`.`created`  DESC, `item`.`gravity` ASC, `item`.`created` ASC ",
				intval(local_user()),
				dbesc($parents_str)
			);
		}
	}

	// find all the authors involved in remote conversations
	// We will use a local profile photo if they are one of our contacts
	// otherwise we have to get the photo from the item owner's site

	$author_contacts = extract_item_authors($r,local_user());

	$cmnt_tpl = load_view_file('view/comment_item.tpl');
	$like_tpl = load_view_file('view/like.tpl');
	$noshare_tpl = load_view_file('view/like_noshare.tpl');
	$tpl = load_view_file('view/wall_item.tpl');
	$wallwall = load_view_file('view/wallwall_item.tpl');

	$alike = array();
	$dlike = array();
	
	if(count($r)) {

		if($nouveau) {

			// "New Item View" - just loop through the items and format them minimally for display

			$tpl = load_view_file('view/search_item.tpl');
			$droptpl = load_view_file('view/wall_fake_drop.tpl');

			foreach($r as $item) {

				$comment     = '';
				$owner_url   = '';
				$owner_photo = '';
				$owner_name  = '';
				$sparkle     = '';
			
				$profile_name   = ((strlen($item['author-name']))   ? $item['author-name']   : $item['name']);
				$profile_avatar = ((strlen($item['author-avatar'])) ? $item['author-avatar'] : $item['thumb']);
				$profile_link   = ((strlen($item['author-link']))   ? $item['author-link']   : $item['url']);

				$redirect_url = $a->get_baseurl() . '/redir/' . $item['cid'] ;

				if(strlen($item['author-link'])) {
					if(link_compare($item['author-link'],$item['url']) && ($item['network'] === 'dfrn') && (! $item['self'])) {
						$profile_link = $redirect_url;
						$sparkle = ' sparkle';
					}
					elseif(isset($author_contacts[$item['author-link']])) {
						$profile_link = $a->get_baseurl() . '/redir/' . $author_contacts[$item['author-link']];
						$sparkle = ' sparkle';
					}
				}

				$location = (($item['location']) ? '<a target="map" title="' . $item['location'] . '" href="http://maps.google.com/?q=' . urlencode($item['location']) . '">' . $item['location'] . '</a>' : '');
				$coord = (($item['coord']) ? '<a target="map" title="' . $item['coord'] . '" href="http://maps.google.com/?q=' . urlencode($item['coord']) . '">' . $item['coord'] . '</a>' : '');
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
					'$item_photo_menu' => item_photo_menu($item),
					'$name' => $profile_name,
					'$sparkle' => $sparkle,
					'$lock' => $lock,
					'$thumb' => $profile_avatar,
					'$title' => $item['title'],
					'$body' => smilies(bbcode($item['body'])),
					'$ago' => relative_date($item['created']),
					'$location' => $location,
					'$indent' => '',
					'$owner_url' => $owner_url,
					'$owner_photo' => $owner_photo,
					'$owner_name' => $owner_name,
					'$drop' => $drop,
					'$conv' => '<a href="' . $a->get_baseurl() . '/display/' . $a->user['nickname'] . '/' . $item['id'] . '">' . t('View in context') . '</a>'
				));

			}
			$o .= paginate($a);

			return $o;

		}

		// Normal View


		// Figure out how many comments each parent has
		// (Comments all have gravity of 6)
		// Store the result in the $comments array

		$comments = array();
		foreach($r as $rr) {
			if(intval($rr['gravity']) == 6) {
				if(! x($comments,$rr['parent']))
					$comments[$rr['parent']] = 1;
				else
					$comments[$rr['parent']] += 1;
			}
		}

		// map all the like/dislike activities for each parent item 
		// Store these in the $alike and $dlike arrays

		foreach($r as $item) {
			like_puller($a,$item,$alike,'like');
			like_puller($a,$item,$dlike,'dislike');
		}

		$comments_collapsed = false;
		$blowhard = 0;
		$blowhard_count = 0;

		foreach($r as $item) {

			$comment = '';
			$template = $tpl;
			$commentww = '';
			$sparkle = '';
			$owner_url = $owner_photo = $owner_name = '';


			// We've already parsed out like/dislike for special treatment. We can ignore them now

			if(((activity_match($item['verb'],ACTIVITY_LIKE)) 
				|| (activity_match($item['verb'],ACTIVITY_DISLIKE))) 
				&& ($item['id'] != $item['parent']))
				continue;

			// Take care of author collapsing and comment collapsing
			// If a single author has more than 3 consecutive top-level posts, squash the remaining ones.
			// If there are more than two comments, squash all but the last 2.

			if($item['id'] == $item['parent']) {
				if($blowhard == $item['cid'] && (! $item['self'])) {
					$blowhard_count ++;
					if($blowhard_count == 3) {
						$o .= '<div class="icollapse-wrapper fakelink" id="icollapse-wrapper-' . $item['parent'] . '" onclick="openClose(' . '\'icollapse-' . $item['parent'] . '\');" >' . t('See more posts like this') . '</div>' . '<div class="icollapse" id="icollapse-' . $item['parent'] . '" style="display: none;" >';
					}
				}
				else {
					$blowhard = $item['cid'];					
					if($blowhard_count >= 3)
						$o .= '</div>';
					$blowhard_count = 0;
				}

				$comments_seen = 0;
				$comments_collapsed = false;
			}
			else
				$comments_seen ++;


			if(($comments[$item['parent']] > 2) && ($comments_seen <= ($comments[$item['parent']] - 2)) && ($item['gravity'] == 6)) {
				if(! $comments_collapsed) {
					$o .= '<div class="ccollapse-wrapper fakelink" id="ccollapse-wrapper-' . $item['parent'] . '" onclick="openClose(' . '\'ccollapse-' . $item['parent'] . '\');" >' . sprintf( t('See all %d comments'), $comments[$item['parent']]) . '</div>';
					$o .= '<div class="ccollapse" id="ccollapse-' . $item['parent'] . '" style="display: none;" >';
					$comments_collapsed = true;
				}
			}
			if(($comments[$item['parent']] > 2) && ($comments_seen == ($comments[$item['parent']] - 1))) {
				$o .= '</div>';
			}



			$redirect_url = $a->get_baseurl() . '/redir/' . $item['cid'] ;

			$lock = ((($item['private']) || (($item['uid'] == local_user()) && (strlen($item['allow_cid']) || strlen($item['allow_gid']) 
				|| strlen($item['deny_cid']) || strlen($item['deny_gid']))))
				? '<div class="wall-item-lock"><img src="images/lock_icon.gif" class="lockview" alt="' . t('Private Message') . '" onclick="lockview(event,' . $item['id'] . ');" /></div>'
				: '<div class="wall-item-lock"></div>');


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
				if(($item['type'] === 'remote') && (strlen($item['owner-link'])) && ($item['owner-link'] != $item['author-link'])) {
					// Could be anybody. 
					$owner_url = $item['owner-link'];
					$owner_photo = $item['owner-avatar'];
					$owner_name = $item['owner-name'];
					$template = $wallwall;
					$commentww = 'ww';
					// If it is our contact, use a friendly redirect link
					if((link_compare($item['owner-link'],$item['url'])) 
						&& ($item['network'] === 'dfrn')) {
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
				$likebuttons = replace_macros((($item['private']) ? $noshare_tpl : $like_tpl),array(
					'$id' => $item['id'],
					'$likethis' => t("I like this \x28toggle\x29"),
					'$nolike' => t("I don't like this \x28toggle\x29"),
					'$share' => t('Share'),
					'$wait' => t('Please wait') 
				));
			}

			if($item['last-child']) {
				$comment = replace_macros($cmnt_tpl,array(
					'$return_path' => '', 
					'$jsreload' => '', // $_SESSION['return_url'],
					'$type' => 'net-comment',
					'$id' => $item['item_id'],
					'$parent' => $item['parent'],
					'$profile_uid' =>  $_SESSION['uid'],
					'$mylink' => $a->contact['url'],
					'$mytitle' => t('This is you'),
					'$myphoto' => $a->contact['thumb'],
					'$comment' => t('Comment'),
					'$submit' => t('Submit'),
					'$ww' => $commentww
				));
			}

			$edpost = '';
			if(($item['id'] == $item['parent']) && (intval($item['wall']) == 1)) 
				$edpost = '<a class="editpost" href="' . $a->get_baseurl() . '/editpost/' . $item['id'] . '" title="' . t('Edit') . '"><img src="images/pencil.gif" /></a>';
			$drop = replace_macros(load_view_file('view/wall_item_drop.tpl'), array('$id' => $item['id'], '$delete' => t('Delete')));

			$photo = $item['photo'];
			$thumb = $item['thumb'];

			// Post was remotely authored.

			$diff_author = ((link_compare($item['url'],$item['author-link'])) ? false : true);

			$profile_name   = (((strlen($item['author-name']))   && $diff_author) ? $item['author-name']   : $item['name']);
			$profile_avatar = (((strlen($item['author-avatar'])) && $diff_author) ? $item['author-avatar'] : $thumb);

			if(strlen($item['author-link'])) {
				$profile_link = $item['author-link'];
				if(link_compare($item['author-link'],$item['url']) && ($item['network'] === 'dfrn') && (! $item['self'])) {
					$profile_link = $redirect_url;
					$sparkle = ' sparkle';
				}
				elseif(isset($author_contacts[$item['author-link']])) {
					$profile_link = $a->get_baseurl() . '/redir/' . $author_contacts[$item['author-link']];
					$sparkle = ' sparkle';
				}
			}
			else 
				$profile_link = $item['url'];

			$like    = ((x($alike,$item['id'])) ? format_like($alike[$item['id']],$alike[$item['id'] . '-l'],'like',$item['id']) : '');
			$dislike = ((x($dlike,$item['id'])) ? format_like($dlike[$item['id']],$dlike[$item['id'] . '-l'],'dislike',$item['id']) : '');

			$location = (($item['location']) ? '<a target="map" title="' . $item['location'] . '" href="http://maps.google.com/?q=' . urlencode($item['location']) . '">' . $item['location'] . '</a>' : '');
			$coord = (($item['coord']) ? '<a target="map" title="' . $item['coord'] . '" href="http://maps.google.com/?q=' . urlencode($item['coord']) . '">' . $item['coord'] . '</a>' : '');
			if($coord) {
				if($location)
					$location .= '<br /><span class="smalltext">(' . $coord . ')</span>';
				else
					$location = '<span class="smalltext">' . $coord . '</span>';
			}

			$indent = (($item['parent'] != $item['item_id']) ? ' comment' : '');

			if(strcmp(datetime_convert('UTC','UTC',$item['created']),datetime_convert('UTC','UTC','now - 12 hours')) > 0)
				$indent .= ' shiny'; 



			// Build the HTML

			$tmp_item = replace_macros($template,array(
				'$id' => $item['item_id'],
				'$linktitle' => t('View $name\'s profile'),
				'$olinktitle' => t('View $owner_name\'s profile'),
				'$to' => t('to'),
				'$wall' => t('Wall-to-Wall'),
				'$vwall' => t('via Wall-To-Wall:'),
				'$profile_url' => $profile_link,
				'$item_photo_menu' => item_photo_menu($item),
				'$name' => $profile_name,
				'$thumb' => $profile_avatar,
				'$osparkle' => $osparkle,
				'$sparkle' => $sparkle,
				'$title' => $item['title'],
				'$body' => smilies(bbcode($item['body'])),
				'$ago' => relative_date($item['created']),
				'$lock' => $lock,
				'$location' => $location,
				'$indent' => $indent,
				'$owner_url' => $owner_url,
				'$owner_photo' => $owner_photo,
				'$owner_name' => $owner_name,
				'$plink' => get_plink($item),
				'$edpost' => $edpost,
				'$drop' => $drop,
				'$vote' => $likebuttons,
				'$like' => $like,
				'$dislike' => $dislike,
				'$comment' => $comment
			));

			$arr = array('item' => $item, 'output' => $tmp_item);
			call_hooks('display_item', $arr);

			$o .= $arr['output'];

		}
	}

	if(! $update) {

		// if author collapsing is in force but didn't get closed, close it off now.

		if($blowhard_count >= 3)
			$o .= '</div>';


		$o .= paginate($a);
		$o .= '<div class="cc-license">' . t('Shared content is covered by the <a href="http://creativecommons.org/licenses/by/3.0/">Creative Commons Attribution 3.0</a> license.') . '</div>';
	}

	return $o;
}
