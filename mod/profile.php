<?php

if(! function_exists('profile_load')) {
function profile_load(&$a, $username, $profile = 0) {

	if(remote_user()) {
		$r = q("SELECT `profile-id` FROM `contact` WHERE `id` = %d LIMIT 1",
			intval($_SESSION['visitor_id']));
		if(count($r))
			$profile = $r[0]['profile-id'];
	} 

	if($profile) {
		$profile_int = intval($profile);
		$sql_which = " AND `profile`.`id` = $profile_int ";
	}
	else
		$sql_which = " AND `profile`.`is-default` = 1 "; 

	$r = q("SELECT `profile`.`uid` AS `profile_uid`, `profile`.* , `user`.* FROM `profile` 
		LEFT JOIN `user` ON `profile`.`uid` = `user`.`uid`
		WHERE `user`.`nickname` = '%s' $sql_which LIMIT 1",
		dbesc($username)
	);

	if(($r === false) || (! count($r))) {
		notice("No profile" . EOL );
		$a->error = 404;
		return;
	}

	$a->profile = $r[0];

	$a->page['template'] = 'profile';

	$a->page['title'] = $a->profile['name'];

	return;
}}

function profile_init(&$a) {

	if($a->argc > 1)
		$which = $a->argv[1];
	else {
		notice("No profile" . EOL );
		$a->error = 404;
		return;
	}

	if((remote_user()) && ($a->argc > 2) && ($a->argv[2] == 'visit'))
		$_SESSION['is_visitor'] = 1;
	else {
		unset($_SESSION['is_visitor']);
		unset($_SESSION['visitor_id']);
		if(! $_SESSION['uid'])
			unset($_SESSION['authenticated']);
	}

	$profile = 0;
	if((local_user()) && ($a->argc > 2) && ($a->argv[2] == 'view')) {
		$which = $a->user['nickname'];
		$profile = $a->argv[1];		
	}
	profile_load($a,$which,$profile);
        $a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . $a->get_baseurl() . '/dfrn_poll/' . $which .'" />';

	$a->page['htmlhead'] .= "<meta name=\"dfrn-template\" content=\"" . $a->get_baseurl() . "/profile/%s" . "\" />\r\n";
	
	$dfrn_pages = array('request', 'confirm', 'notify', 'poll');
	foreach($dfrn_pages as $dfrn)
		$a->page['htmlhead'] .= "<link rel=\"dfrn-{$dfrn}\" href=\"".$a->get_baseurl()."/dfrn_{$dfrn}/{$which}\" />\r\n";

}


function profile_content(&$a, $update = false) {

	require_once("include/bbcode.php");
	require_once('include/security.php');

	$groups = array();

	$tab = 'posts';


	if(! $update) {
		$_SESSION['profile_uid'] = $a->profile['uid'];
	}

	if(remote_user()) {
		$contact_id = $_SESSION['visitor_id'];
		$groups = init_groups_visitor($contact_id);
	}
	if(local_user()) {
		$contact_id = $_SESSION['cid'];
	}

	if($update) {
		// Ensure we've got a profile owner if updating.
		$a->profile['profile_uid'] = $_SESSION['profile_uid'];
	}

	else {
		if(x($_GET,'tab'))
			$tab = notags(trim($_GET['tab']));

		$tpl = file_get_contents('view/profile_tabs.tpl');

		$o .= replace_macros($tpl,array(
			'$url' => $a->get_baseurl() . '/' . $a->cmd,
			'$phototab' => $a->get_baseurl() . '/photos/' . $a->profile['nickname']
		));


		if($tab == 'profile') {
			require_once('view/profile_advanced.php');
			return $o;
		}

		if(can_write_wall($a,$a->profile['profile_uid'])) {
			$tpl = file_get_contents('view/jot-header.tpl');
	
			$a->page['htmlhead'] .= replace_macros($tpl, array('$baseurl' => $a->get_baseurl()));
			require_once('view/acl_selectors.php');

			$tpl = file_get_contents("view/jot.tpl");
			if(is_array($a->user) && ((strlen($a->user['allow_cid'])) || (strlen($a->user['allow_gid'])) || (strlen($a->user['deny_cid'])) || (strlen($a->user['deny_gid']))))
				$lockstate = 'lock';
			else
				$lockstate = 'unlock';
			$o .= replace_macros($tpl,array(
				'$baseurl' => $a->get_baseurl(),
				'$return_path' => $a->cmd,
				'$visitor' => (($_SESSION['uid'] == $a->profile['profile_uid']) ? 'block' : 'none'),
				'$lockstate' => $lockstate,
				'$acl' => (($_SESSION['uid'] == $a->profile['profile_uid']) ? populate_acl($a->user) : ''),
				'$profile_uid' => $a->profile['profile_uid']
			));
		}

		if($tab == 'posts' && (! $a->pager['start']))
			$o .= '<div id="live-profile"></div>' . "\r\n";
	}

	// TODO alter registration and settings and profile to update contact table when names and  photos change.  

	// default permissions - anonymous user

	$sql_extra = " AND `allow_cid` = '' AND `allow_gid` = '' AND `deny_cid` = '' AND `deny_gid` = '' ";

	// Profile owner - everything is visible

	if(local_user() && ($_SESSION['uid'] == $a->profile['uid'])) {
		$sql_extra = ''; 
		
		// Oh - while we're here... reset the Unseen messages

		$r = q("UPDATE `item` SET `unseen` = 0 
			WHERE `type` != 'remote' AND `unseen` = 1 AND `uid` = %d",
			intval($_SESSION['uid'])
		);

	}

	// authenticated visitor - here lie dragons
	elseif(remote_user()) {
		$gs = '<<>>'; // should be impossible to match
		if(count($groups)) {
			foreach($groups as $g)
				$gs .= '|<' . intval($g) . '>';
		} 
		$sql_extra = sprintf(
			" AND ( `allow_cid` = '' OR `allow_cid` REGEXP '<%d>' ) 
			  AND ( `deny_cid`  = '' OR  NOT `deny_cid` REGEXP '<%d>' ) 
			  AND ( `allow_gid` = '' OR `allow_gid` REGEXP '%s' )
			  AND ( `deny_gid`  = '' OR NOT `deny_gid` REGEXP '%s') ",

			intval($_SESSION['visitor_id']),
			intval($_SESSION['visitor_id']),
			dbesc($gs),
			dbesc($gs)
		);
	}

	$r = q("SELECT COUNT(*) AS `total`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
		AND NOT `item`.`type` IN ( 'remote', 'net-comment') AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0 
		$sql_extra ",
		intval($a->profile['uid'])

	);

	if(count($r))
		$a->set_pager_total($r[0]['total']);


	$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
		`contact`.`name`, `contact`.`photo`, `contact`.`url`, 
		`contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`, 
		`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
		AND NOT `item`.`type` IN ( 'remote', 'net-comment') AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
		$sql_extra
		ORDER BY `parent` DESC, `id` ASC LIMIT %d ,%d ",
		intval($a->profile['uid']),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])

	);


	$cmnt_tpl = file_get_contents('view/comment_item.tpl');

	$tpl = file_get_contents('view/wall_item.tpl');

	if($update)
		$return_url = $_SESSION['return_url'];
	else
		$return_url = $_SESSION['return_url'] = $a->cmd;

	if(count($r)) {
		foreach($r as $item) {
			$comment = '';
			$template = $tpl;
			
			$redirect_url = $a->get_baseurl() . '/redir/' . $item['cid'] ;
			


			if(can_write_wall($a,$a->profile['uid'])) {
				if($item['last-child']) {
					$comment = replace_macros($cmnt_tpl,array(
						'$return_path' => $_SESSION['return_url'],
						'$type' => 'wall-comment',
						'$id' => $item['item_id'],
						'$parent' => $item['parent'],
						'$profile_uid' =>  $a->profile['uid'],
						'$mylink' => $a->contact['url'],
						'$mytitle' => t('Me'),
						'$myphoto' => $a->contact['thumb'],
						'$ww' => ''
					));
				}
			}


			$profile_url = $item['url'];

			// This is my profile but I'm not the author of this post/comment. If it's somebody that's a fan or mutual friend,
			// I can go directly to their profile as an authenticated guest.

			if(local_user() && ($item['contact-uid'] == $_SESSION['uid']) && (strlen($item['dfrn-id'])) && (! $item['self'] ))
				$profile_url = $redirect_url;

			// FIXME tryng to solve the mishmash of profile photos. 

		//	$photo = (($item['self']) ? $a->profile['photo'] : $item['photo']);
		//	$thumb = (($item['self']) ? $a->profile['thumb'] : $item['thumb']);
	

			// We received this post via a remote feed. It's either a wall-to-wall or a remote comment. The author is
			// known to us and is reflected in the contact-id for this item. We can use the contact url or redirect rather than 
			// use the link in the feed. This is different than on the network page where we may not know the author.
 
			$profile_name = ((strlen($item['author-name'])) ? $item['author-name'] : $item['name']);
			$profile_avatar = ((strlen($item['author-avatar'])) ? $item['author-avatar'] : $item['thumb']);
			$profile_link = $profile_url;

			$drop = '';

			if(($item['contact-id'] == $_SESSION['visitor_id']) || ($item['uid'] == $_SESSION['uid']))
				$drop = replace_macros(file_get_contents('view/wall_item_drop.tpl'), array('$id' => $item['id']));




			$o .= replace_macros($template,array(
				'$id' => $item['item_id'],
				'$profile_url' => $profile_link,
				'$name' => $profile_name,
				'$thumb' => $profile_avatar,
				'$title' => $item['title'],
				'$body' => bbcode($item['body']),
				'$ago' => relative_date($item['created']),
				'$indent' => (($item['parent'] != $item['item_id']) ? ' comment' : ''),
				'$drop' => $drop,
				'$comment' => $comment
			));
			
		}
	}

	if($update) {
		return $o;
	}
		
	$o .= paginate($a);

	return $o;
}