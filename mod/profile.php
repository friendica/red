<?php

function profile_init(&$a) {

	if($a->argc > 1)
		$which = $a->argv[1];
	else {
		notice( t('No profile') . EOL );
		$a->error = 404;
		return;
	}

	$profile = 0;
	if((local_user()) && ($a->argc > 2) && ($a->argv[2] === 'view')) {
		$which = $a->user['nickname'];
		$profile = $a->argv[1];		
	}

	profile_load($a,$which,$profile);

	if (!get_config('system','no_openid') && $a->profile['openid']!=""){
		if (!isset($a->profile['openidserver'])){
			die('friendika user table must be updated. `openidserver` field is missing');
		}
		if ($a->profile['openidserver']==''){
			require_once('library/openid.php');
			$openid = new LightOpenID;
			$openid->identity = $a->profile['openid'];
			$a->profile['openidserver'] = $openid->discover($openid->identity);

			q("UPDATE `user` SET `openidserver` = '%s' WHERE `uid` = %d LIMIT 1",
				dbesc($a->profile['openidserver']),
				intval($a->profile['uid'])
			);
		}
		
		
		$a->page['htmlhead'] .= '<link rel="openid.server" href="'.$a->profile['openidserver'].'" />'. "\r\n";
		$a->page['htmlhead'] .= '<link rel="openid.delegate" href="'.$a->profile['openid'].'" />'. "\r\n";
		    

	}

	

	$a->page['htmlhead'] .= '<meta name="dfrn-global-visibility" content="' . (($a->profile['net-publish']) ? 'true' : 'false') . '" />' . "\r\n" ;
	$a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . $a->get_baseurl() . '/dfrn_poll/' . $which .'" />' . "\r\n" ;
	$uri = urlencode('acct:' . $a->profile['nickname'] . '@' . $a->get_hostname() . (($a->path) ? '/' . $a->path : ''));
	$a->page['htmlhead'] .= '<link rel="lrdd" type="application/xrd+xml" href="' . $a->get_baseurl() . '/xrd/?uri=' . $uri . '" />' . "\r\n";
	header('Link: <' . $a->get_baseurl() . '/xrd/?uri=' . $uri . '>; rel="lrdd"; type="application/xrd+xml"', false);
  
	
	$dfrn_pages = array('request', 'confirm', 'notify', 'poll');
	foreach($dfrn_pages as $dfrn)
		$a->page['htmlhead'] .= "<link rel=\"dfrn-{$dfrn}\" href=\"".$a->get_baseurl()."/dfrn_{$dfrn}/{$which}\" />\r\n";

}


function profile_content(&$a, $update = 0) {

	require_once("include/bbcode.php");
	require_once('include/security.php');

	$groups = array();

	$tab = 'posts';
	$o = '';

	if($update) {
		// Ensure we've got a profile owner if updating.
		$a->profile['profile_uid'] = $update;
	}
	else {
		if($a->profile['profile_uid'] == local_user())		
			$o .= '<script>	$(document).ready(function() { $(\'#nav-home-link\').addClass(\'nav-selected\'); });</script>';
	}

	$contact = null;
	$remote_contact = false;

	if(remote_user()) {
		$contact_id = $_SESSION['visitor_id'];
		$groups = init_groups_visitor($contact_id);
		$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($contact_id),
			intval($a->profile['profile_uid'])
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

	$is_owner = ((local_user()) && (local_user() == $a->profile['profile_uid']) ? true : false);
	

	if(! $update) {
		if(x($_GET,'tab'))
			$tab = notags(trim($_GET['tab']));

		$tpl = load_view_file('view/profile_tabs.tpl');

		$o .= replace_macros($tpl,array(
			'$url' => $a->get_baseurl() . '/' . $a->cmd,
			'$phototab' => $a->get_baseurl() . '/photos/' . $a->profile['nickname']
		));


		if($tab === 'profile') {
			$profile_lang = get_config('system','language');
			if(! $profile_lang)
				$profile_lang = 'en';
			if(file_exists("view/$profile_lang/profile_advanced.php"))
				require_once("view/$profile_lang/profile_advanced.php");
			else
				require_once('view/profile_advanced.php');

			call_hooks('profile_advanced',$o);

			return $o;
		}

		$commpage = (($a->profile['page-flags'] == PAGE_COMMUNITY) ? true : false);
		$commvisitor = (($commpage && $remote_contact == true) ? true : false);

		$celeb = ((($a->profile['page-flags'] == PAGE_SOAPBOX) || ($a->profile['page-flags'] == PAGE_COMMUNITY)) ? true : false);

		if(can_write_wall($a,$a->profile['profile_uid'])) {

			$geotag = ((($is_owner || $commvisitor) && $a->profile['allow_location']) ? load_view_file('view/jot_geotag.tpl') : '');

			$tpl = load_view_file('view/jot-header.tpl');
	
			$a->page['htmlhead'] .= replace_macros($tpl, array(
				'$baseurl' => $a->get_baseurl(),
				'$geotag'  => $geotag,
				'$nickname' => $a->profile['nickname']
			));

			require_once('include/acl_selectors.php');

			$tpl = load_view_file('view/jot.tpl');

			if(is_array($a->user) && ((strlen($a->user['allow_cid'])) || (strlen($a->user['allow_gid'])) || (strlen($a->user['deny_cid'])) || (strlen($a->user['deny_gid']))))
				$lockstate = 'lock';
			else
				$lockstate = 'unlock';
			$o .= replace_macros($tpl,array(
				'$baseurl' => $a->get_baseurl(),
				'$defloc' => (($is_owner) ? $a->user['default-location'] : ''),
				'$return_path' => $a->cmd,
				'$visitor' => (($is_owner || $commvisitor) ? 'block' : 'none'),
				'$lockstate' => $lockstate,
				'$bang' => '',
				'$acl' => (($is_owner) ? populate_acl($a->user, $celeb) : ''),
				'$profile_uid' => $a->profile['profile_uid']
			));
		}

		// This is ugly, but we can't pass the profile_uid through the session to the ajax updater,
		// because browser prefetching might change it on us. We have to deliver it with the page.

		if($tab === 'posts' && (! $a->pager['start'])) {
			$o .= '<div id="live-profile"></div>' . "\r\n";
			$o .= "<script> var profile_uid = " . $a->profile['profile_uid'] . "; </script>\r\n";
		}

	}

	// Construct permissions

	// default permissions - anonymous user

	$sql_extra = " AND `allow_cid` = '' AND `allow_gid` = '' AND `deny_cid` = '' AND `deny_gid` = '' ";

	// Profile owner - everything is visible

	if($is_owner) {
		$sql_extra = ''; 
		
		// Oh - while we're here... reset the Unseen messages

		$r = q("UPDATE `item` SET `unseen` = 0 
			WHERE `type` != 'remote' AND `unseen` = 1 AND `uid` = %d",
			intval($_SESSION['uid'])
		);

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
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0 
		AND `item`.`parent` IN ( SELECT `parent` FROM `item` WHERE `id` = `parent` AND `wall` = 1 )
		$sql_extra ",
		intval($a->profile['profile_uid'])

	);

	if(count($r))
		$a->set_pager_total($r[0]['total']);

	$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
		`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`network`, `contact`.`rel`, 
		`contact`.`thumb`, `contact`.`self`, 
		`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
		AND `item`.`parent` IN ( SELECT `parent` FROM `item` WHERE `id` = `parent` AND `wall` = 1 )
		$sql_extra
		ORDER BY `parent` DESC, `gravity` ASC, `id` ASC LIMIT %d ,%d ",
		intval($a->profile['profile_uid']),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])

	);

	$cmnt_tpl = load_view_file('view/comment_item.tpl');

	$like_tpl = load_view_file('view/like.tpl');

	$tpl = load_view_file('view/wall_item.tpl');

	$droptpl = load_view_file('view/wall_item_drop.tpl');
	$fakedrop = load_view_file('view/wall_fake_drop.tpl');

	if($update)
		$return_url = $_SESSION['return_url'];
	else
		$return_url = $_SESSION['return_url'] = $a->cmd;

	$alike = array();
	$dlike = array();

	if(count($r)) {

		foreach($r as $item) {
			like_puller($a,$item,$alike,'like');
			like_puller($a,$item,$dlike,'dislike');
		}

		foreach($r as $item) {

			$sparkle = '';		
			$comment = '';
			$likebuttons = '';

			$template = $tpl;
			
			$redirect_url = $a->get_baseurl() . '/redir/' . $item['cid'] ;

			if(((activity_match($item['verb'],ACTIVITY_LIKE)) || (activity_match($item['verb'],ACTIVITY_DISLIKE))) 
				&& ($item['id'] != $item['parent']))
				continue;

			$lock = ((($item['private']) || (($item['uid'] == local_user()) && (strlen($item['allow_cid']) || strlen($item['allow_gid']) 
				|| strlen($item['deny_cid']) || strlen($item['deny_gid']))))
				? '<div class="wall-item-lock"><img src="images/lock_icon.gif" class="lockview" alt="' . t('Private Message') . '" onclick="lockview(event,' . $item['id'] . ');" /></div>'
				: '<div class="wall-item-lock"></div>');

			if(can_write_wall($a,$a->profile['profile_uid'])) {
				if($item['id'] == $item['parent']) {
					$likebuttons = replace_macros($like_tpl,array('$id' => $item['id']));
				}
				if($item['last-child']) {
					$comment = replace_macros($cmnt_tpl,array(
						'$return_path' => $_SESSION['return_url'],
						'$type' => 'wall-comment',
						'$id' => $item['item_id'],
						'$parent' => $item['parent'],
						'$profile_uid' =>  $a->profile['profile_uid'],
						'$mylink' => $contact['url'],
						'$mytitle' => t('This is you'),
						'$myphoto' => $contact['thumb'],
						'$ww' => ''
					));
				}
			}


			$profile_url = $item['url'];

			// This is my profile but I'm not the author of this post/comment. If it's somebody that's a fan or mutual friend,
			// I can go directly to their profile as an authenticated guest.

			if(local_user() && ($item['contact-uid'] == $_SESSION['uid']) 
				&& ($item['network'] === 'dfrn') && (! $item['self'] )) {
				$profile_url = $redirect_url;
				$sparkle = ' sparkle';
			}
			else
				$sparkle = '';

			// We would prefer to use our own avatar link for this item because the one in the author-avatar might reference a 
			// remote site (which could be down). We will use author-avatar if we haven't got something stored locally.
			// We use this same logic block in mod/network.php to determine it this is a third party post and we don't have any 
			// local contact info at all. In this module you should never encounter a third-party author, but we still will do
			// the right thing if you ever do. 

			$diff_author = (($item['url'] !== $item['author-link']) ? true : false);

			$profile_name   = (((strlen($item['author-name']))   && $diff_author) ? $item['author-name']   : $item['name']);
			$profile_avatar = (((strlen($item['author-avatar'])) && $diff_author) ? $item['author-avatar'] : $item['thumb']);

			$profile_link = $profile_url;

			$drop = '';
			$dropping = false;

			if(($item['contact-id'] == remote_user()) || ($item['uid'] == local_user()))
				$dropping = true;

			$drop = replace_macros((($dropping)? $droptpl : $fakedrop), array('$id' => $item['id']));


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

			$o .= replace_macros($template,array(
				'$id' => $item['item_id'],
				'$profile_url' => $profile_link,
				'$name' => $profile_name,
				'$thumb' => $profile_avatar,
				'$sparkle' => $sparkle,
				'$title' => $item['title'],
				'$body' => smilies(bbcode($item['body'])),
				'$ago' => relative_date($item['created']),
				'$lock' => $lock,
				'$location' => $location, 
				'$indent' => $indent, 
				'$drop' => $drop,
				'$like' => $like,
				'$vote' => $likebuttons,
				'$dislike' => $dislike,
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
