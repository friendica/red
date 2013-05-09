<?php

function search_saved_searches() {

	if(! feature_enabled(local_user(),'savedsearch'))
		return '';

	$o = '';

	$r = q("select `tid`,`term` from `term` WHERE `uid` = %d and type = %d",
		intval(local_user()),
		intval(TERM_SAVEDSEARCH)
	);

	if(count($r)) {
		$o .= '<div id="saved-search-list" class="widget">';
		$o .= '<h3>' . t('Saved Searches') . '</h3>' . "\r\n";
		$o .= '<ul id="saved-search-ul">' . "\r\n";
		foreach($r as $rr) {
			$o .= '<li class="saved-search-li clear"><a href="search/?f=&remove=1&search=' . $rr['term'] . '" class="icon drophide savedsearchdrop" title="' . t('Remove term') . '" onclick="return confirmDelete();" onmouseover="imgbright(this);" onmouseout="imgdull(this);" ></a> <a href="search/?f=&search=' . $rr['term'] . '" class="savedsearchterm" >' . htmlspecialchars($rr['term']) . '</a></li>' . "\r\n";
		}
		$o .= '</ul><div class="clear"></div></div>' . "\r\n";
	}		

	return $o;

}


function search_init(&$a) {

	$search = ((x($_GET,'search')) ? trim(rawurldecode($_GET['search'])) : '');

	if(local_user()) {
		if(x($_GET,'save') && $search) {
			$r = q("select `tid` from `term` where `uid` = %d and `type` = %d and `term` = '%s' limit 1",
				intval(local_user()),
				intval(TERM_SAVEDSEARCH),
				dbesc($search)
			);
			if(! count($r)) {
				q("insert into `term` ( `uid`,`type`,`term` ) values ( %d, %d, '%s') ",
					intval(local_user()),
					intval(TERM_SAVEDSEARCH),
					dbesc($search)
				);
			}
		}
		if(x($_GET,'remove') && $search) {
			q("delete from `term` where `uid` = %d and `type` = %d and `term` = '%s' limit 1",
				intval(local_user()),
				intval(TERM_SAVEDSEARCH),
				dbesc($search)
			);
		}

		$a->page['aside'] .= search_saved_searches();

	}
	else {
		unset($_SESSION['theme']);
		unset($_SESSION['mobile-theme']);
	}



}



function search_post(&$a) {
	if(x($_POST,'search'))
		$a->data['search'] = $_POST['search'];
}


function search_content(&$a) {

	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		notice( t('Public access denied.') . EOL);
		return;
	}

	nav_set_selected('search');

	require_once("include/bbcode.php");
	require_once('include/security.php');
	require_once('include/conversation.php');
	require_once('include/items.php');


	$observer = $a->get_observer();

	$o = '<div id="live-search"></div>' . "\r\n";

	$o .= '<h3>' . t('Search') . '</h3>';

	if(x($a->data,'search'))
		$search = trim($a->data['search']);
	else
		$search = ((x($_GET,'search')) ? trim(rawurldecode($_GET['search'])) : '');

	$tag = false;
	if(x($_GET,'tag')) {
		$tag = true;
		$search = ((x($_GET,'tag')) ? trim(rawurldecode($_GET['tag'])) : '');
	}

	$o .= search($search,'search-box','/search',((local_user()) ? true : false));

	if(strpos($search,'#') === 0) {
		$tag = true;
		$search = substr($search,1);
	}
	if(strpos($search,'@') === 0) {
		$search = substr($search,1);
		goaway(z_root() . '/directory' . '?f=1&search=' . $search);
	}

	// look for a naked webbie
	if(strpos($search,'@') !== false) {
		goaway(z_root() . '/directory' . '?f=1&search=' . $search);
	}

	if(! $search)
		return $o;

	if($tag) {
		$sql_extra = sprintf(" AND `item`.`id` IN (select `oid` from term where otype = %d and type = %d and term = '%s') ",
			intval(TERM_OBJ_POST),
			intval(TERM_HASHTAG),
			dbesc(protect_sprintf($search))
		);
	}
	else {
		if (get_config('system','use_fulltext_engine'))
			$sql_extra = sprintf(" AND MATCH (`item`.`body`) AGAINST ('".'"%s"'."' in boolean mode) ", dbesc(protect_sprintf($search)));
		else
			$sql_extra = sprintf(" AND `item`.`body` REGEXP '%s' ", dbesc(protect_sprintf(preg_quote($search))));
	}

	// Here is the way permissions work in the search module...
	// Only public posts can be shown
	// OR your own posts if you are a logged in member
	// No items will be shown if the member has a blocked profile wall. 




	if((! $update) && (! $load)) {

		// This is ugly, but we can't pass the profile_uid through the session to the ajax updater,
		// because browser prefetching might change it on us. We have to deliver it with the page.

		$o .= '<div id="live-search"></div>' . "\r\n";
		$o .= "<script> var profile_uid = " . $a->profile['profile_uid'] 
			. "; var netargs = '?f='; var profile_page = " . $a->pager['page'] . "; </script>\r\n";

		$a->page['htmlhead'] .= replace_macros(get_markup_template("build_query.tpl"),array(
			'$baseurl' => z_root(),
			'$pgtype' => 'search',
			'$uid' => (($a->profile['profile_uid']) ? $a->profile['profile_uid'] : '0'),
			'$gid' => '0',
			'$cid' => '0',
			'$cmin' => '0',
			'$cmax' => '0',
			'$star' => '0',
			'$liked' => '0',
			'$conv' => '0',
			'$spam' => '0',
			'$nouveau' => '0',
			'$wall' => '0',
			'$page' => (($a->pager['page'] != 1) ? $a->pager['page'] : 1),
			'$search' => (($tag) ? '#' : '') . $search,
			'$order' => '',
			'$file' => '',
			'$cats' => '',
			'$mid' => '',
			'$dend' => '',
			'$dbegin' => ''
		));


	}



	if(($update) && ($load)) {
		$pager_sql = sprintf(" LIMIT %d, %d ",intval($a->pager['start']), intval($a->pager['itemspage']));

		if($load) {
			$r = q("SELECT distinct(mid), item.* from item
				WHERE item_restrict = 0
				AND (( `item`.`allow_cid` = ''  AND `item`.`allow_gid` = '' AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = '' AND item_private = 0 ) 
				OR ( `item`.`uid` = %d ))
				$sql_extra
				group by mid ORDER BY created DESC $pager_sql ",
				intval(local_user()),
				intval(ABOOK_FLAG_BLOCKED)

			);
		}
		else {
			$r = array();
		}
	}

	if($r) {

		$parents_str = ids_to_querystr($r,'item_id');
 
		$items = q("SELECT `item`.*, `item`.`id` AS `item_id` 
			FROM `item`
			WHERE item_restrict = 0 and 
			$sql_extra ",
			intval($a->profile['profile_uid']),
			dbesc($parents_str)
		);

		xchan_query($items);
		$items = fetch_post_tags($items,true);
		$items = conv_sort($items,'created');

	} else {
		$items = array();
	}



	$r = q("SELECT distinct(`item`.`mid`), `item`.*, `item`.`id` AS `item_id`, 
		`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`alias`, `contact`.`rel`,
		`contact`.`network`, `contact`.`thumb`, `contact`.`self`, `contact`.`writable`, 
		`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`,
		`user`.`nickname`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		LEFT JOIN `user` ON `user`.`uid` = `item`.`uid`
		WHERE `item`.`visible` = 1 AND `item`.`deleted` = 0 and `item`.`moderated` = 0
		AND (( `item`.`allow_cid` = ''  AND `item`.`allow_gid` = '' AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = '' AND `item`.`private` = 0 AND `user`.`hidewall` = 0 ) 
			OR `item`.`uid` = %d )
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
		$sql_extra
		group by `item`.`mid`	
		ORDER BY `received` DESC LIMIT %d , %d ",
		intval(local_user()),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])

	);


//	$a = fetch_post_tags($a,true);

	if(! count($r)) {
		info( t('No results.') . EOL);
		return $o;
	}


	if($tag) 
		$o .= '<h2>Items tagged with: ' . htmlspecialchars($search) . '</h2>';
	else
		$o .= '<h2>Search results for: ' . htmlspecialchars($search) . '</h2>';

	$o .= conversation($a,$r,'search',false);

	$o .= alt_pager($a,count($r));

	return $o;
}

