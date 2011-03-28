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

	if((x($a->profile,'page-flags')) && ($a->profile['page-flags'] & PAGE_COMMUNITY)) {
		$a->page['htmlhead'] .= '<meta name="friendika.community" content="true" />';
	}
	if(x($a->profile,'openidserver'))				
		$a->page['htmlhead'] .= '<link rel="openid.server" href="' . $a->profile['openidserver'] . '" />' . "\r\n";
	if(x($a->profile,'openid')) {
		$delegate = ((strstr($a->profile['openid'],'://')) ? $a->profile['openid'] : 'http://' . $a->profile['openid']);
		$a->page['htmlhead'] .= '<link rel="openid.delegate" href="' . $delegate . '" />' . "\r\n";
	}

	$keywords = ((x($a->profile,'pub_keywords')) ? $a->profile['pub_keywords'] : '');
	$keywords = str_replace(array(',',' ',',,'),array(' ',',',','),$keywords);
	if(strlen($keywords))
		$a->page['htmlhead'] .= '<meta name="keywords" content="' . $keywords . '" />' . "\r\n" ;

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
			'$phototab' => $a->get_baseurl() . '/photos/' . $a->profile['nickname'],
			'$status' => t('Status'),
			'$profile' => t('Profile'),
			'$photos' => t('Photos')
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
				'$nickname' => $a->profile['nickname'],
				'$linkurl' => t('Please enter a link URL:'),
				'$utubeurl' => t('Please enter a YouTube link:'),
				'$vidurl' => t("Please enter a video\x28.ogg\x29 link/URL:"),
				'$audurl' => t("Please enter an audio\x28.ogg\x29 link/URL:"),
				'$whereareu' => t('Where are you right now?') 
			));

			require_once('include/acl_selectors.php');

			$tpl = load_view_file('view/jot.tpl');

			if(is_array($a->user) && ((strlen($a->user['allow_cid'])) || (strlen($a->user['allow_gid'])) || (strlen($a->user['deny_cid'])) || (strlen($a->user['deny_gid']))))
				$lockstate = 'lock';
			else
				$lockstate = 'unlock';
       
			$jotplugins = '';
			$jotnets = '';
			call_hooks('jot_tool', $jotplugins); 

			call_hooks('jot_networks', $jotnets);

			$tpl = replace_macros($tpl,array('$jotplugins' => $jotplugins));	

			$o .= replace_macros($tpl,array(
				'$baseurl' => $a->get_baseurl(),
				'$action' => 'item',
				'$share' => t('Share'),
				'$upload' => t('Upload photo'),
				'$weblink' => t('Insert web link'),
				'$youtube' => t('Insert YouTube video'),
				'$video' => t('Insert Vorbis [.ogg] video'),
				'$audio' => t('Insert Vorbis [.ogg] audio'),
				'$setloc' => t('Set your location'),
				'$noloc' => t('Clear browser location'),
				'$wait' => t('Please wait'),
				'$permset' => t('Permission settings'),
				'$content' => '',
				'$post_id' => '',
				'$defloc' => (($is_owner) ? $a->user['default-location'] : ''),
				'$return_path' => $a->cmd,
				'$visitor' => (($is_owner || $commvisitor) ? 'block' : 'none'),
				'$lockstate' => $lockstate,
				'$emailcc' => t('CC: email addresses'),
				'$jotnets' => $jotnets,
				'$emtitle' => t('Example: bob@example.com, mary@example.com'),
				'$bang' => '',
				'$acl' => (($is_owner) ? populate_acl($a->user, $celeb) : ''),
				'$profile_uid' => $a->profile['profile_uid']
			));
		}

		// This is ugly, but we can't pass the profile_uid through the session to the ajax updater,
		// because browser prefetching might change it on us. We have to deliver it with the page.

		if($tab === 'posts') {
			$o .= '<div id="live-profile"></div>' . "\r\n";
			$o .= "<script> var profile_uid = " . $a->profile['profile_uid'] 
				. "; var netargs = ''; var profile_page = " . $a->pager['page'] . "; </script>\r\n";
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
		AND `item`.`id` = `item`.`parent` AND `item`.`wall` = 1
		$sql_extra ",
		intval($a->profile['profile_uid'])

	);

	if(count($r)) {
		$a->set_pager_total($r[0]['total']);
		$a->set_pager_itemspage(40);
	}

	$r = q("SELECT `item`.`id` AS `item_id`, `contact`.`uid` AS `contact-uid`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
		AND `item`.`id` = `item`.`parent` AND `item`.`wall` = 1
		$sql_extra
		LIMIT %d ,%d ",
		intval($a->profile['profile_uid']),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])

	);

	$parents_arr = array();
	$parents_str = '';

	if(count($r)) {
		foreach($r as $rr)
			$parents_arr[] = $rr['item_id'];
		$parents_str = implode(', ', $parents_arr);
 
		$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
			`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`network`, `contact`.`rel`, 
			`contact`.`thumb`, `contact`.`self`, 
			`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
			FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
			WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			AND `item`.`parent` IN ( %s )
			$sql_extra
			ORDER BY `parent` DESC, `gravity` ASC, `item`.`id` ASC ",
			intval($a->profile['profile_uid']),
			dbesc($parents_str)
		);
	}

	if($is_owner && ! $update)
		$o .= get_birthdays();

	$cmnt_tpl = load_view_file('view/comment_item.tpl');

	$like_tpl = load_view_file('view/like.tpl');
	$noshare_tpl = load_view_file('view/like_noshare.tpl');

	$tpl = load_view_file('view/wall_item.tpl');

	$droptpl = load_view_file('view/wall_item_drop.tpl');
	$fakedrop = load_view_file('view/wall_fake_drop.tpl');

	if($update)
		$return_url = $_SESSION['return_url'];
	else
		$return_url = $_SESSION['return_url'] = $a->cmd;

	$alike = array();
	$dlike = array();

	if($r !== false && count($r)) {

		$comments = array();
		foreach($r as $rr) {
			if(intval($rr['gravity']) == 6) {
				if(! x($comments,$rr['parent']))
					$comments[$rr['parent']] = 1;
				else
					$comments[$rr['parent']] += 1;
			}
		}

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

			if($item['id'] == $item['parent']) {
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
				$o .= '</div></div>';
			}

			$lock = ((($item['private']) || (($item['uid'] == local_user()) && (strlen($item['allow_cid']) || strlen($item['allow_gid']) 
				|| strlen($item['deny_cid']) || strlen($item['deny_gid']))))
				? '<div class="wall-item-lock"><img src="images/lock_icon.gif" class="lockview" alt="' . t('Private Message') . '" onclick="lockview(event,' . $item['id'] . ');" /></div>'
				: '<div class="wall-item-lock"></div>');

			if(can_write_wall($a,$a->profile['profile_uid'])) {
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

			// This is my profile page but I'm not the author of this post/comment. If it's somebody that's a fan or mutual friend,
			// I can go directly to their profile as an authenticated guest.

			if(local_user() && ($item['contact-uid'] == $_SESSION['uid']) 
				&& ($item['network'] === 'dfrn') && (! $item['self'] )) {
				$profile_url = $redirect_url;
				$sparkle = ' sparkle';
			}
			else
				$sparkle = '';


			$edpost = '';
			if((local_user()) && ($a->profile['profile_uid'] == local_user()) && ($item['id'] == $item['parent']) && (intval($item['wall']) == 1)) 
				$edpost = '<a class="editpost" href="' . $a->get_baseurl() . '/editpost/' . $item['id'] . '" title="' . t('Edit') . '"><img src="images/pencil.gif" /></a>';


			// We would prefer to use our own avatar link for this item because the one in the author-avatar might reference a 
			// remote site (which could be down). We will use author-avatar if we haven't got something stored locally.
			// We use this same logic block in mod/network.php to determine it this is a third party post and we don't have any 
			// local contact info at all. In this module you should never encounter a third-party author, but we still will do
			// the right thing if you ever do. 

			$diff_author = ((link_compare($item['url'],$item['author-link'])) ? false : true);

			$profile_name   = (((strlen($item['author-name']))   && $diff_author) ? $item['author-name']   : $item['name']);
			$profile_avatar = (((strlen($item['author-avatar'])) && $diff_author) ? $item['author-avatar'] : $item['thumb']);

			$profile_link = $profile_url;

			$drop = '';
			$dropping = false;

			if(($item['contact-id'] == remote_user()) || ($item['uid'] == local_user()))
				$dropping = true;

			$drop = replace_macros((($dropping)? $droptpl : $fakedrop), array('$id' => $item['id'], '$delete' => t('Delete')));


			$like    = ((isset($alike[$item['id']])) ? format_like($alike[$item['id']],$alike[$item['id'] . '-l'],'like',$item['id']) : '');
			$dislike = ((isset($dlike[$item['id']])) ? format_like($dlike[$item['id']],$dlike[$item['id'] . '-l'],'dislike',$item['id']) : '');
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

			$tmp_item = replace_macros($template,array(
				'$id' => $item['item_id'],
				'$linktitle' => t('View $name\'s profile'),
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
				'$plink' => get_plink($item),
				'$edpost' => $edpost,
				'$drop' => $drop,
				'$like' => $like,
				'$vote' => $likebuttons,
				'$dislike' => $dislike,
				'$comment' => $comment
			));

			$arr = array('item' => $item, 'output' => $tmp_item);
			call_hooks('display_item', $arr);

			$o .= $arr['output'];
			
		}
	}

	if($update) {
		return $o;
	}
		
	$o .= paginate($a);
	$o .= '<div class="cc-license">' . t('Shared content is covered by the <a href="http://creativecommons.org/licenses/by/3.0/">Creative Commons Attribution 3.0</a> license.') . '</div>';

	return $o;
}
