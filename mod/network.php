<?php


function network_init(&$a) {
	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}
  
	$group_id = (($a->argc > 1 && intval($a->argv[1])) ? intval($a->argv[1]) : 0);
		  
	require_once('include/group.php');
	if(! x($a->page,'aside'))
		$a->page['aside'] = '';

	$search = ((x($_GET,'search')) ? escape_tags($_GET['search']) : '');
	$srchurl = '/network' . ((x($_GET,'cid')) ? '?cid=' . $_GET['cid'] : '') . ((x($_GET,'star')) ? '?star=' . $_GET['star'] : '');


	$a->page['aside'] .= search($search,'netsearch-box',$srchurl);

	$a->page['aside'] .= '<div id="network-new-link">';


	$a->page['aside'] .= '<div id="network-view-link">';
	if(($a->argc > 1 && $a->argv[1] === 'new') || ($a->argc > 2 && $a->argv[2] === 'new') || x($_GET,'search'))
		$a->page['aside'] .= '<a href="' . $a->get_baseurl() . '/' . str_replace('/new', '', $a->cmd) . ((x($_GET,'cid')) ? '?cid=' . $_GET['cid'] : '') . '">' . t('View Conversations') . '</a></div>';
	else { 
		$a->page['aside'] .= '<a href="' . $a->get_baseurl() . '/' . $a->cmd . '/new' . ((x($_GET,'cid')) ? '/?cid=' . $_GET['cid'] : '') . '">' . t('View New Items') . '</a></div>';

		if(x($_GET,'star'))
			$a->page['aside'] .= '<div id="network-star-link">'
				. '<a class="network-star" href="' . $a->get_baseurl() . '/' . $a->cmd 
				. ((x($_GET,'cid')) ? '/?cid=' . $_GET['cid'] : '') . '">' 
				. t('View Any Items') . '</a>' 
				. '<span class="network-star icon starred"></span>' 
				. '<span class="network-star icon unstarred"></span>' 
				. '<div class="clear"></div></div>';
		else
			$a->page['aside'] .= '<div id="network-star-link">'
				. '<a class="network-star" href="' . $a->get_baseurl() . '/' . $a->cmd 
				. ((x($_GET,'cid')) ? '/?cid=' . $_GET['cid'] : '') . '&star=1" >' 
				. t('View Starred Items') . '</a>'
				. '<span class="network-star icon starred"></span>' 
				. '<div class="clear"></div></div>';

	}

	$a->page['aside'] .= '</div>';

	$a->page['aside'] .= group_side('network','network',true,$group_id);
}


function network_content(&$a, $update = 0) {

	require_once('include/conversation.php');

	if(! local_user())
    	return login(false);

	$o = '';

	$contact_id = $a->cid;

	$group = 0;

	$nouveau = false;
	require_once('include/acl_selectors.php');

	$cid = ((x($_GET['cid'])) ? intval($_GET['cid']) : 0);
	$star = ((x($_GET['star'])) ? intval($_GET['star']) : 0);

	if(($a->argc > 2) && $a->argv[2] === 'new')
		$nouveau = true;

	if($a->argc > 1) {
		if($a->argv[1] === 'new')
			$nouveau = true;
		else {
			$group = intval($a->argv[1]);
			$def_acl = array('allow_gid' => '<' . $group . '>');
		}
	}

	if(x($_GET,'search'))
		$nouveau = true;
	if($cid)
		$def_acl = array('allow_cid' => '<' . intval($cid) . '>');

	if(! $update) {
		if(group) {
			if(($t = group_public_members($group)) && (! get_pconfig(local_user(),'system','nowarn_insecure'))) {
				notice( sprintf( tt('Warning: This group contains %s member from an insecure network.',
									'Warning: This group contains %s members from an insecure network.',
									$t), $t ) . EOL);
				notice( t('Private messages to this group are at risk of public disclosure.') . EOL);
			}
		}

		$o .= '<script>	$(document).ready(function() { $(\'#nav-network-link\').addClass(\'nav-selected\'); });</script>';

		$_SESSION['return_url'] = $a->cmd;

		$celeb = ((($a->user['page-flags'] == PAGE_SOAPBOX) || ($a->user['page-flags'] == PAGE_COMMUNITY)) ? true : false);

		$x = array(
			'is_owner' => true,
			'allow_location' => $a->user['allow_location'],
			'default_location' => $a->user['default_location'],
			'nickname' => $a->user['nickname'],
			'lockstate' => ((($group) || (is_array($a->user) && ((strlen($a->user['allow_cid'])) || (strlen($a->user['allow_gid'])) || (strlen($a->user['deny_cid'])) || (strlen($a->user['deny_gid']))))) ? 'lock' : 'unlock'),
			'acl' => populate_acl((($group || $cid) ? $def_acl : $a->user), $celeb),
			'bang' => (($group || $cid) ? '!' : ''),
			'visitor' => 'block',
			'profile_uid' => local_user()
		);

		$o .= status_editor($a,$x);

		// The special div is needed for liveUpdate to kick in for this page.
		// We only launch liveUpdate if you are on the front page, you aren't
		// filtering by group and also you aren't writing a comment (the last
		// criteria is discovered in javascript).

			$o .= '<div id="live-network"></div>' . "\r\n";
			$o .= "<script> var profile_uid = " . $_SESSION['uid'] 
				. "; var netargs = '" . substr($a->cmd,8)
				. '?f='
				. ((x($_GET,'cid')) ? '&cid=' . $_GET['cid'] : '')
				. ((x($_GET,'search')) ? '&search=' . $_GET['search'] : '') 
				. ((x($_GET,'star')) ? '&star=' . $_GET['star'] : '') 
				. "'; var profile_page = " . $a->pager['page'] . "; </script>\r\n";

	}

	// We aren't going to try and figure out at the item, group, and page
	// level which items you've seen and which you haven't. If you're looking
	// at the top level network page just mark everything seen. 
	
	if((! $group) && (! $cid) && (! $star)) {
		$r = q("UPDATE `item` SET `unseen` = 0 
			WHERE `unseen` = 1 AND `uid` = %d",
			intval($_SESSION['uid'])
		);
	}

	// We don't have to deal with ACL's on this page. You're looking at everything
	// that belongs to you, hence you can see all of it. We will filter by group if
	// desired. 

	$star_sql = (($star) ?  " AND `starred` = 1 " : '');

	$sql_extra = " AND `item`.`parent` IN ( SELECT `parent` FROM `item` WHERE `id` = `parent` $star_sql ) ";

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
			// NOTREACHED
		}

		$contacts = expand_groups(array($group));
		if((is_array($contacts)) && count($contacts)) {
			$contact_str = implode(',',$contacts);
		}
		else {
				$contact_str = ' 0 ';
				info( t('Group is empty'));
		}


		$sql_extra = " AND `item`.`parent` IN ( SELECT `parent` FROM `item` WHERE `id` = `parent` $star_sql AND ( `contact-id` IN ( $contact_str ) OR `allow_gid` REGEXP '<" . intval($group) . ">' )) ";
		$o = '<h2>' . t('Group: ') . $r[0]['name'] . '</h2>' . $o;
	}
	elseif($cid) {

		$r = q("SELECT `id`,`name`,`network`,`writable` FROM `contact` WHERE `id` = %d 
				AND `blocked` = 0 AND `pending` = 0 LIMIT 1",
			intval($cid)
		);
		if(count($r)) {
			$sql_extra = " AND `item`.`parent` IN ( SELECT `parent` FROM `item` WHERE `id` = `parent` $star_sql AND `contact-id` IN ( " . intval($cid) . " )) ";
			$o = '<h2>' . t('Contact: ') . $r[0]['name'] . '</h2>' . $o;
			if($r[0]['network'] !== NETWORK_MAIL && $r[0]['network'] !== NETWORK_DFRN && $r[0]['network'] !== NETWORK_FACEBOOK && $r[0]['writable'] && (! get_pconfig(local_user(),'system','nowarn_insecure'))) {
				notice( t('Private messages to this person are at risk of public disclosure.') . EOL);
			}

		}
		else {
			notice( t('Invalid contact.') . EOL);
			goaway($a->get_baseurl() . '/network');
			// NOTREACHED
		}
	}

	if((! $group) && (! $cid) && (! $update))
		$o .= get_birthdays();

	$sql_extra2 = (($nouveau) ? '' : " AND `item`.`parent` = `item`.`id` ");

	if(x($_GET,'search'))
		$sql_extra .= " AND `item`.`body` REGEXP '" . dbesc(escape_tags($_GET['search'])) . "' ";

	
	$r = q("SELECT COUNT(*) AS `total`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
		$sql_extra2
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
			`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`, `contact`.`writable`,
			`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
			`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
			FROM `item`, `contact`
			WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
			AND `contact`.`id` = `item`.`contact-id`
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			$sql_extra
			ORDER BY `item`.`received` DESC LIMIT %d ,%d ",
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
				`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`, `contact`.`writable`,
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

	// Set this so that the conversation function can find out contact info for our wall-wall items
	$a->page_contact = $a->contact;

	$mode = (($nouveau) ? 'network-new' : 'network');

	$o .= conversation($a,$r,$mode,$update);

	if(! $update) {
		$o .= paginate($a);
		$o .= cc_license();
	}

	return $o;
}
