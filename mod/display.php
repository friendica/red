<?php


function display_content(&$a) {

	$o = '<div id="live-display"></div>' . "\r\n";

	$nick = (($a->argc > 1) ? $a->argv[1] : '');
	profile_load($a,$nick);

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

	if(local_user() && (local_user() == $a->profile['uid'])) {
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
		`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`,
		`contact`.`network`, `contact`.`thumb`, `contact`.`self`, 
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

		if((local_user()) && (local_user() == $a->profile['uid'])) {
			q("UPDATE `item` SET `unseen` = 0 
				WHERE `parent` = %d AND `unseen` = 1",
				intval($r[0]['parent'])
			);
		}

		foreach($r as $item) {
			like_puller($a,$item,$alike,'like');
			like_puller($a,$item,$dlike,'dislike');
		}

		$author_contacts = extract_item_authors($r,$a->profile['uid']);

		foreach($r as $item) {

			$template = $tpl;

			$comment     = '';
			$owner_url   = '';
			$owner_photo = '';
			$owner_name  = '';
			
			$redirect_url = $a->get_baseurl() . '/redir/' . $item['cid'] ;
			
			if(((activity_match($item['verb'],ACTIVITY_LIKE)) || (activity_match($item['verb'],ACTIVITY_DISLIKE))) 
				&& ($item['id'] != $item['parent']))
				continue;

			$lock = ((($item['private']) || (($item['uid'] == local_user()) && (strlen($item['allow_cid']) || strlen($item['allow_gid']) 
				|| strlen($item['deny_cid']) || strlen($item['deny_gid']))))
				? '<div class="wall-item-lock"><img src="images/lock_icon.gif" class="lockview" alt="' . t('Private Message') . '" onclick="lockview(event,' . $item['id'] . ');" /></div>'
				: '<div class="wall-item-lock"></div>');

			if(can_write_wall($a,$a->profile['uid'])) {
				if($item['id'] == $item['parent']) {
					$likebuttons = replace_macros($like_tpl,array(
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
						'$jsreload' => $_SESSION['return_url'],
						'$type' => 'wall-comment',
						'$id' => $item['item_id'],
						'$parent' => $item['parent'],
						'$profile_uid' =>  $a->profile['uid'],
						'$mylink' => $contact['url'],
						'$mytitle' => t('This is you'),
						'$myphoto' => $contact['thumb'],
						'$ww' => ''
					));
				}
			}


			$profile_url = $item['url'];
			$sparkle = '';


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
					if((link_compare($item['owner-link'],$item['url'])) && ($item['network'] === 'dfrn')) {
						$owner_url = $redirect_url;
						$osparkle = ' sparkle';
					}


				}
			}

			$diff_author = ((link_compare($item['url'],$item['author-link'])) ? false : true);

			$profile_name   = (((strlen($item['author-name']))   && $diff_author) ? $item['author-name']   : $item['name']);
			$profile_avatar = (((strlen($item['author-avatar'])) && $diff_author) ? $item['author-avatar'] : $item['thumb']);

			// Can we use our special contact URL for this author? 

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

			if(($item['contact-id'] == remote_user()) || ($item['uid'] == local_user()))
				$drop = replace_macros(load_view_file('view/wall_item_drop.tpl'), array('$id' => $item['id'], '$delete' => t('Delete')));
			else 
				$drop = replace_macros(load_view_file('view/wall_fake_drop.tpl'), array('$id' => $item['id']));

			$like    = ((isset($alike[$item['id']])) ? format_like($alike[$item['id']],$alike[$item['id'] . '-l'],'like',$item['id']) : '');
			$dislike = ((isset($dlike[$item['id']])) ? format_like($dlike[$item['id']],$dlike[$item['id'] . '-l'],'dislike',$item['id']) : '');

			$location = (($item['location']) ? '<a target="map" href="http://maps.google.com/?q=' . urlencode($item['location']) . '">' . $item['location'] . '</a>' : '');
			$coord = (($item['coord']) ? '<a target="map" href="http://maps.google.com/?q=' . urlencode($item['coord']) . '">' . $item['coord'] . '</a>' : '');
			if($coord) {
				if($location)
					$location .= '<br /><span class="smalltext">(' . $coord . ')</span>';
				else
					$location = '<span class="smalltext">' . $coord . '</span>';
			}

			$indent = (($item['parent'] != $item['item_id']) ? ' comment' : '');

			if(strcmp(datetime_convert('UTC','UTC',$item['created']),datetime_convert('UTC','UTC','now - 12 hours')) > 0)
				$indent .= ' shiny'; 


			$tmp_item = replace_macros($template,array(
				'$id' => $item['item_id'],
				'$linktitle' => t('View $name\'s profile'),
				'$olinktitle' => t('View $owner_name\'s profile'),
				'$to' => t('to'),
				'$wall' => t('Wall-to-Wall'),
				'$vwall' => t('via Wall-To-Wall:'),
				'$profile_url' => $profile_link,
				'$name' => $profile_name,
				'$sparkle' => $sparkle,
				'$osparkle' => $osparkle,
				'$thumb' => $profile_avatar,
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

	$o .= '<div class="cc-license">' . t('Shared content is covered by the <a href="http://creativecommons.org/licenses/by/3.0/">Creative Commons Attribution 3.0</a> license.') . '</div>';

	return $o;
}

