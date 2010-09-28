<?php


function display_content(&$a) {

	require_once('mod/profile.php');
	profile_init($a);

	$item_id = (($a->argc > 2) ? intval($a->argv[2]) : 0);

	if(! $item_id) {
		$a->error = 404;
		notice( t('Item not found.') . EOL);
		return;
	}

	require_once("include/bbcode.php");
	require_once('include/security.php');


	$groups = array();

	$tab = 'posts';


	$contact = null;
	$remote_contact = false;

	if(remote_user()) {
		$contact_id = $_SESSION['visitor_id'];
		$groups = init_groups_visitor($contact_id);
		$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($contact_id),
			intval($a->profile['uid'])
		);
		if(count($r)) {
			$contact = $r[0];
			$remote_contact = true;
		}
	}

	if(! $remote_contact) {
		if(local_user()) {
			$contact_id = $_SESSION['cid'];
			$contact = $a->contact;
		}
	}


	$sql_extra = "
		AND `allow_cid` = '' 
		AND `allow_gid` = '' 
		AND `deny_cid`  = '' 
		AND `deny_gid`  = '' 
	";


	// Profile owner - everything is visible

	if(local_user() && (get_uid() == $a->profile['uid'])) {
		$sql_extra = ''; 		
	}

	// authenticated visitor - here lie dragons
	// If $remotecontact is true, we know that not only is this a remotely authenticated
	// person, but that it is *our* contact, which is important in multi-user mode.

	elseif($remote_contact) {
		$gs = '<<>>'; // should be impossible to match
		if(count($groups)) {
			foreach($groups as $g)
				$gs .= '|<' . intval($g) . '>';
		} 
		$sql_extra = sprintf(
			" AND ( `allow_cid` = '' OR `allow_cid` REGEXP '<%d>' ) 
			  AND ( `deny_cid`  = '' OR  NOT `deny_cid` REGEXP '<%d>' ) 
			  AND ( `allow_gid` = '' OR `allow_gid` REGEXP '%s' )
			  AND ( `deny_gid`  = '' OR  NOT `deny_gid` REGEXP '%s') ",

			intval($_SESSION['visitor_id']),
			intval($_SESSION['visitor_id']),
			dbesc($gs),
			dbesc($gs)
		);
	}

	$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
		`contact`.`name`, `contact`.`photo`, `contact`.`url`, 
		`contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`, 
		`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
		AND `item`.`parent` = ( SELECT `parent` FROM `item` WHERE ( `id` = '%s' OR `uri` = '%s' ))
		$sql_extra
		ORDER BY `parent` DESC, `gravity` ASC, `id` ASC ",
		intval($a->profile['uid']),
		dbesc($item_id),
		dbesc($item_id)
	);


	$cmnt_tpl = load_view_file('view/comment_item.tpl');
	$like_tpl = load_view_file('view/like.tpl');
	$tpl = load_view_file('view/wall_item.tpl');
	$wallwall = load_view_file('view/wallwall_item.tpl');

	$return_url = $_SESSION['return_url'] = $a->cmd;

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
			
			$redirect_url = $a->get_baseurl() . '/redir/' . $item['cid'] ;
			
			if((($item['verb'] == ACTIVITY_LIKE) || ($item['verb'] == ACTIVITY_DISLIKE)) && ($item['id'] != $item['parent'])) 
				continue;

			if(can_write_wall($a,$a->profile['uid'])) {
				if($item['last-child']) {
					$comment = replace_macros($cmnt_tpl,array(
						'$return_path' => $_SESSION['return_url'],
						'$type' => 'wall-comment',
						'$id' => $item['item_id'],
						'$parent' => $item['parent'],
						'$profile_uid' =>  $a->profile['uid'],
						'$mylink' => $contact['url'],
						'$mytitle' => t('Me'),
						'$myphoto' => $contact['thumb'],
						'$ww' => ''
					));
				}
			}


			$profile_url = $item['url'];
			$sparkle = '';


			$redirect_url = $a->get_baseurl() . '/redir/' . $item['cid'] ;

			if(($item['rel'] == REL_VIP || $item['rel'] == REL_BUD) && (! $item['self'] )) {
				$profile_url = $redirect_url;
				$sparkle = ' sparkle';
			}


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
					if(($item['owner-link'] == $item['url']) && ($item['rel'] == REL_VIP || $item['rel'] == REL_BUD)) {
						$owner_url = $redirect_url;
						$osparkle = ' sparkle';
					}


				}
			}

			$profile_name = ((strlen($item['author-name'])) ? $item['author-name'] : $item['name']);
			$profile_avatar = ((strlen($item['author-avatar'])) ? $item['author-avatar'] : $item['thumb']);
			$profile_link = $profile_url;

			$drop = '';

			if(($item['contact-id'] == $_SESSION['visitor_id']) || ($item['uid'] == get_uid()))
				$drop = replace_macros(load_view_file('view/wall_item_drop.tpl'), array('$id' => $item['id']));

			$like    = (($alike[$item['id']]) ? format_like($alike[$item['id']],$alike[$item['id'] . '-l'],'like',$item['id']) : '');
			$dislike = (($dlike[$item['id']]) ? format_like($dlike[$item['id']],$dlike[$item['id'] . '-l'],'dislike',$item['id']) : '');

			$likebuttons = '';
			if($item['id'] == $item['parent']) {
				$likebuttons = replace_macros($like_tpl,array('$id' => $item['id']));
			}



			$o .= replace_macros($template,array(
				'$id' => $item['item_id'],
				'$profile_url' => $profile_link,
				'$name' => $profile_name,
				'$sparkle' => $sparkle,
				'$osparkle' => $osparkle,
				'$thumb' => $profile_avatar,
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
	else {
		$r = q("SELECT `id` FROM `item` WHERE `id` = '%s' OR `uri` = '%s' LIMIT 1",
			dbesc($item_id),
			dbesc($item_id)
		);
		if(count($r)) {
			if($r[0]['deleted']) {
				notice( t('Item has been removed.') . EOL );
			}
			else {	
				notice( t('Permission denied.') . EOL ); 
			}
		}
		else {
			notice( t('Item not found.') . EOL );
		}

	}
	return $o;
}

