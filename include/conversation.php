<?php


function conversation(&$a,$r, $mode, $update) {

	require_once('bbcode.php');

	$profile_owner = 0;
	$writable      = false;

	if($mode === 'network') {
		$profile_owner = local_user();
		$writable = true;
	}

	if($mode === 'profile') {
		$profile_owner = $a->profile['profile_uid'];
		$writable = can_write_wall($a,$profile_owner);
	}

	if($mode === 'display') {
		$profile_owner = $a->profile['uid'];
		$writable = can_write_wall($a,$profile_owner);
	}



	if($update)
		$return_url = $_SESSION['return_url'];
	else
		$return_url = $_SESSION['return_url'] = $a->cmd;


	// find all the authors involved in remote conversations
	// We will use a local profile photo if they are one of our contacts
	// otherwise we have to get the photo from the item owner's site

	$author_contacts = extract_item_authors($r,local_user());


	$cmnt_tpl    = load_view_file('view/comment_item.tpl');
	$like_tpl    = load_view_file('view/like.tpl');
	$noshare_tpl = load_view_file('view/like_noshare.tpl');
	$tpl         = load_view_file('view/wall_item.tpl');
	$wallwall    = load_view_file('view/wallwall_item.tpl');

	$alike = array();
	$dlike = array();
	
	if(count($r)) {

		if($mode === 'network-new' || $mode === 'search') {

			// "New Item View" on network page or search page results 
			// - just loop through the items and format them minimally for display

			$tpl = load_view_file('view/search_item.tpl');
			$droptpl = load_view_file('view/wall_fake_drop.tpl');

			foreach($r as $item) {

				$comment     = '';
				$owner_url   = '';
				$owner_photo = '';
				$owner_name  = '';
				$sparkle     = '';

				if($mode === 'search') {
					if(((activity_match($item['verb'],ACTIVITY_LIKE)) || (activity_match($item['verb'],ACTIVITY_DISLIKE))) 
						&& ($item['id'] != $item['parent']))
						continue;
					$nickname = $item['nickname'];
				}
				else
					$nickname = $a->user['nickname'];
			
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

				$drop = '';
				$dropping = false;

				if((intval($item['contact-id']) && $item['contact-id'] == remote_user()) || ($item['uid'] == local_user()))
					$dropping = true;

	            $drop = replace_macros((($dropping)? $droptpl : $fakedrop), array('$id' => $item['id'], '$delete' => t('Delete')));



				$drop = replace_macros($droptpl,array('$id' => $item['id']));
				$lock = '<div class="wall-item-lock"></div>';
				
				$o .= replace_macros($tpl,array(
					'$id' => $item['item_id'],
					'$linktitle' => sprintf( t('View %s\'s profile'), $profile_name),
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
					'$conv' => '<a href="' . $a->get_baseurl() . '/display/' . $nickname . '/' . $item['id'] . '">' . t('View in context') . '</a>'
				));

			}

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
				if($blowhard == $item['cid'] && (! $item['self']) && ($mode != 'profile')) {
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

			if(($item['parent'] == $item['item_id']) && (! $item['self']) && ($mode !== 'profile')) {

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


			$likebuttons = '';

			if($writable) {
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
						'$jsreload' => (($mode === 'display') ? $_SESSION['return_url'] : ''),
						'$type' => (($mode === 'profile') ? 'wall-comment' : 'net-comment'),
						'$id' => $item['item_id'],
						'$parent' => $item['parent'],
						'$profile_uid' =>  $profile_owner,
						'$mylink' => $a->contact['url'],
						'$mytitle' => t('This is you'),
						'$myphoto' => $a->contact['thumb'],
						'$comment' => t('Comment'),
						'$submit' => t('Submit'),
						'$ww' => (($mode === 'network') ? $commentww : '')
					));
				}
			}

			$edpost = '';
			if(($profile_owner == local_user()) && ($item['id'] == $item['parent']) && (intval($item['wall']) == 1)) 
				$edpost = '<a class="editpost" href="' . $a->get_baseurl() . '/editpost/' . $item['id'] . '" title="' . t('Edit') . '"><img src="images/pencil.gif" /></a>';
			$drop = replace_macros(load_view_file('view/wall_item_drop.tpl'), array('$id' => $item['id'], '$delete' => t('Delete')));

			$photo = $item['photo'];
			$thumb = $item['thumb'];

			// Post was remotely authored.

			$diff_author = ((link_compare($item['url'],$item['author-link'])) ? false : true);

			$profile_name   = (((strlen($item['author-name']))   && $diff_author) ? $item['author-name']   : $item['name']);
			$profile_avatar = (((strlen($item['author-avatar'])) && $diff_author) ? $item['author-avatar'] : $thumb);

			if($mode === 'profile') {
				if(local_user() && ($item['contact-uid'] == local_user()) && ($item['network'] === 'dfrn') && (! $item['self'] )) {
	                $profile_link = $redirect_url;
    	            $sparkle = ' sparkle';
        	    }
				else {
					$profile_link = $item['url'];
					$sparkle = '';
				}
			}
			elseif(strlen($item['author-link'])) {
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
				'$linktitle' => sprintf( t('View %s\'s profile'), $profile_name),
				'$olinktitle' => sprintf( t('View %s\'s profile'), $owner_name),
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


	// if author collapsing is in force but didn't get closed, close it off now.

	if($blowhard_count >= 3)
		$o .= '</div>';

	return $o;
} 