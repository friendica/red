<?php


function network_init(&$a) {

}


function network_content(&$a) {

	if(! local_user())
		return;

	require_once("include/bbcode.php");

	$contact_id = $a->cid;

	$r = q("UPDATE `item` SET `unseen` = 0 
		WHERE `unseen` = 1 AND `uid` = %d",
		intval($_SESSION['uid'])
	);


	$tpl = file_get_contents('view/jot-header.tpl');
	
	$a->page['htmlhead'] .= replace_macros($tpl, array('$baseurl' => $a->get_baseurl()));

	require_once('view/acl_selectors.php');

	$tpl = file_get_contents("view/jot.tpl");

	$o .= replace_macros($tpl,array(
		'$return_path' => $a->cmd,
		'$baseurl' => $a->get_baseurl(),
		'$visitor' => 'block',
		'$lockstate' => 'unlock',
		'$acl' => populate_acl(),
		'$profile_uid' => $_SESSION['uid']
	));


	$sql_extra = ''; 


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

			if($item['last-child']) {
				$comment = replace_macros($cmnt_tpl,array(
					'$return_path' => $a->cmd,
					'$id' => $item['item_id'],
					'$parent' => $item['parent'],
					'$profile_uid' =>  $_SESSION['uid'],
					'$ww' => $commentww
				));
			}

	
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
				'$body' => bbcode($item['body']),
				'$ago' => relative_date($item['created']),
				'$indent' => (($item['parent'] != $item['item_id']) ? 'comment-' : ''),
				'$owner_url' => $owner_url,
				'$owner_photo' => $owner_photo,
				'$owner_name' => $owner_name,
				'$comment' => $comment
			));
		}
	}
	$o .= paginate($a);
	return $o;
}