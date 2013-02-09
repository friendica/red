<?php

require_once('include/items.php');

function network_init(&$a) {
	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}
	
	$is_a_date_query = false;

	if($a->argc > 1) {
		for($x = 1; $x < $a->argc; $x ++) {
			if(is_a_date_arg($a->argv[$x])) {
				$is_a_date_query = true;
				break;
			}
		}
	}
    
    // convert query string to array and remove first element (wich is friendica args)
    $query_array = array();
    parse_str($a->query_string, $query_array);
    array_shift($query_array);
    
	// fetch last used tab and redirect if needed
	$sel_tabs = network_query_get_sel_tab($a);
	$last_sel_tabs = get_pconfig(local_user(), 'network.view','tab.selected');
	if (is_array($last_sel_tabs)){
		$tab_urls = array(
			'/network?f=&order=comment',//all
			'/network?f=&order=post',		//postord
			'/network?f=&conv=1',			//conv
			'/network/new',					//new
			'/network?f=&star=1',			//starred
			'/network?f=&spam=1',			//spam
		);
		
		// redirect if current selected tab is 'no_active' and
		// last selected tab is _not_ 'all_active'. 
		// and this isn't a date query

		if ($sel_tabs[0] == 'active' && $last_sel_tabs[0]!='active' && (! $is_a_date_query)) {
			$k = array_search('active', $last_sel_tabs);
          
            // merge tab querystring with request querystring
            $dest_qa = array();
            list($dest_url,$dest_qs) = explode("?", $tab_urls[$k]);
            parse_str( $dest_qs, $dest_qa);
            $dest_qa = array_merge($query_array, $dest_qa);
            $dest_qs = build_querystring($dest_qa);
            
            // groups filter is in form of "network/nnn". Add it to $dest_url, if it's possible
            if ($a->argc==2 && is_numeric($a->argv[1]) && strpos($dest_url, "/",1)===false){
                $dest_url .= "/".$a->argv[1];
            }

//			goaway($a->get_baseurl() . $dest_url."?".$dest_qs);
		}
	}
	
	$group_id = ((x($_GET,'gid')) ? intval($_GET['gid']) : 0);
		  
	require_once('include/group.php');
	require_once('include/contact_widgets.php');
	require_once('include/items.php');

	if(! x($a->page,'aside'))
		$a->page['aside'] = '';

	$search = ((x($_GET,'search')) ? $_GET['search'] : '');

	if(x($_GET,'save')) {
		$r = q("select * from `term` where `uid` = %d and `type` = %d and `term` = '%s' limit 1",
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
	if(x($_GET,'remove')) {
		q("delete from `term` where `uid` = %d and `type` = %d and `term` = '%s' limit 1",
			intval(local_user()),
			intval(TERM_SAVEDSEARCH),
			dbesc($search)
		);
	}


	$a->page['aside'] .= group_side('network','network',true,$group_id);
	$a->page['aside'] .= posted_date_widget($a->get_baseurl() . '/network',local_user(),false);	

	$a->page['aside'] .= saved_searches($search);
	$a->page['aside'] .= fileas_widget($a->get_baseurl(true) . '/network',(x($_GET, 'file') ? $_GET['file'] : ''));

	$base = $a->get_baseurl();

	$a->page['htmlhead'] .= <<< EOT

<script>$(document).ready(function() { 
	var a; 
	a = $("#search-text").autocomplete({ 
		serviceUrl: '$base/search_ac',
		minChars: 2,
		width: 350,
	});
}); 
</script>
EOT;



}

function saved_searches($search) {

	if(! feature_enabled(local_user(),'savedsearch'))
		return '';

	$a = get_app();

	$srchurl = '/network?f=' 
		. ((x($_GET,'cid'))   ? '&cid='   . $_GET['cid']   : '') 
		. ((x($_GET,'star'))  ? '&star='  . $_GET['star']  : '')
		. ((x($_GET,'conv'))  ? '&conv='  . $_GET['conv']  : '')
		. ((x($_GET,'cmin'))  ? '&cmin='  . $_GET['cmin']  : '')
		. ((x($_GET,'cmax'))  ? '&cmax='  . $_GET['cmax']  : '')
		. ((x($_GET,'file'))  ? '&file='  . $_GET['file']  : '');
	;
	
	$o = '';

	$r = q("select `tid`,`term` from `term` WHERE `uid` = %d and `type` = %d ",
		intval(local_user()),
		intval(TERM_SAVEDSEARCH)
	);

	$saved = array();

	if(count($r)) {
		foreach($r as $rr) {
			$saved[] = array(
				'id'            => $rr['tid'],
				'term'			=> $rr['term'],
				'displayterm'   => htmlspecialchars($rr['term']),
				'encodedterm' 	=> urlencode($rr['term']),
				'delete'		=> t('Remove term'),
				'selected'		=> ($search==$rr['term']),
			);
		}
	}		

	
	$tpl = get_markup_template("saved_searches_aside.tpl");
	$o = replace_macros($tpl, array(
		'$title'	 => t('Saved Searches'),
		'$add'		 => t('add'),
		'$searchbox' => search($search,'netsearch-box',$srchurl,true),
		'$saved' 	 => $saved,
	));
	
	return $o;

}

/**
 * Return selected tab from query
 * 
 * urls -> returns
 * 		'/network'					=> $no_active = 'active'
 * 		'/network?f=&order=comment'	=> $comment_active = 'active'
 * 		'/network?f=&order=post'	=> $postord_active = 'active'
 * 		'/network?f=&conv=1',		=> $conv_active = 'active'
 * 		'/network/new',				=> $new_active = 'active'
 * 		'/network?f=&star=1',		=> $starred_active = 'active'
 * 		'/network?f=&spam=1',		=> $spam_active = 'active'
 * 
 * @return Array ( $no_active, $comment_active, $postord_active, $conv_active, $new_active, $starred_active, $spam_active );
 */
function network_query_get_sel_tab($a) {
	$no_active='';
	$starred_active = '';
	$new_active = '';
	$all_active = '';
	$search_active = '';
	$conv_active = '';
	$spam_active = '';
	$postord_active = '';

	if(x($_GET,'new')) {
		$new_active = 'active';
	}
	
	if(x($_GET,'search')) {
		$search_active = 'active';
	}
	
	if(x($_GET,'star')) {
		$starred_active = 'active';
	}
	
	if(x($_GET,'conv')) {
		$conv_active = 'active';
	}

	if(x($_GET,'spam')) {
		$spam_active = 'active';
	}

	
	
	if (($new_active == '') 
		&& ($starred_active == '') 
		&& ($conv_active == '')
		&& ($search_active == '')
		&& ($spam_active == '')) {
			$no_active = 'active';
	}

	if ($no_active=='active' && x($_GET,'order')) {
		switch($_GET['order']){
		 case 'post': $postord_active = 'active'; $no_active=''; break;
		 case 'comment' : $all_active = 'active'; $no_active=''; break;
		}
	}
	
	return array($no_active, $all_active, $postord_active, $conv_active, $new_active, $starred_active, $spam_active);
}


function network_content(&$a, $update = 0, $load = false) {

	require_once('include/conversation.php');

	if(! local_user()) {
		$_SESSION['return_url'] = $a->query_string;
    	return login(false);
	}



	$arr = array('query' => $a->query_string);

	call_hooks('network_content_init', $arr);

	$channel = $a->get_channel();

	$datequery = $datequery2 = '';

	$group = 0;

	$nouveau = false;

	$datequery = ((x($_GET,'dend') && is_a_date_arg($_GET['dend'])) ? notags($_GET['dend']) : '');
	$datequery2 = ((x($_GET,'dbegin') && is_a_date_arg($_GET['dbegin'])) ? notags($_GET['dbegin']) : '');
	$nouveau = ((x($_GET,'new')) ? intval($_GET['new']) : 0);
	$gid = ((x($_GET,'gid')) ? intval($_GET['gid']) : 0);


	if($datequery)
		$_GET['order'] = 'post';

	if($gid) {
		$group = $gid;
		$def_acl = array('allow_gid' => '<' . $group . '>');
	}

	$o = '';

	// item filter tabs
	// TODO: fix this logic, reduce duplication
	//$a->page['content'] .= '<div class="tabs-wrapper">';
	
	list($no_active, $all_active, $postord_active, $conv_active, $new_active, $starred_active, $spam_active) = network_query_get_sel_tab($a);
	// if no tabs are selected, defaults to comments
	if ($no_active=='active') $all_active='active';
	//echo "<pre>"; var_dump($no_active, $all_active, $postord_active, $conv_active, $new_active, $starred_active, $bookmarked_active, $spam_active); killme();

	$cmd = (($datequery) ? '' : $a->cmd);
	$len_naked_cmd = strlen(str_replace('/new','',$cmd));		

	// tabs
	$tabs = array(
		array(
			'label' => t('Commented Order'),
			'url'=>$a->get_baseurl(true) . '/' . $cmd . '?f=&order=comment' . ((x($_GET,'cid')) ? '&cid=' . $_GET['cid'] : ''), 
			'sel'=>$all_active,
			'title'=> t('Sort by Comment Date'),
		),
		array(
			'label' => t('Posted Order'),
			'url'=>$a->get_baseurl(true) . '/' . $cmd . '?f=&order=post' . ((x($_GET,'cid')) ? '&cid=' . $_GET['cid'] : ''), 
			'sel'=>$postord_active,
			'title' => t('Sort by Post Date'),
		),

		array(
			'label' => t('Personal'),
			'url' => $a->get_baseurl(true) . '/' . $cmd . ((x($_GET,'cid')) ? '/?f=&cid=' . $_GET['cid'] : '') . '&conv=1',
			'sel' => $conv_active,
			'title' => t('Posts that mention or involve you'),
		),
		array(
			'label' => t('New'),
			'url' => $a->get_baseurl(true) . '/' . $cmd . ((x($_GET,'cid')) ? '/?f=&cid=' . $_GET['cid'] : '') . '&new=1',
			'sel' => $new_active,
			'title' => t('Activity Stream - by date'),
		),

	);

	if(feature_enabled(local_user(),'star_posts')) 
		$tabs[] = array(
			'label' => t('Starred'),
			'url'=>$a->get_baseurl(true) . '/' . $cmd . ((x($_GET,'cid')) ? '/?f=&cid=' . $_GET['cid'] : '') . '&star=1',
			'sel'=>$starred_active,
			'title' => t('Favourite Posts'),
		);

	// Not yet implemented

	if(feature_enabled(local_user(),'spam_filter')) 
		$tabs[] = array(
			'label' => t('Spam'),
			'url'=>$a->get_baseurl(true) . '/network?f=&spam=1',
			'sel'=> $spam_active,
			'title' => t('Posts flagged as SPAM'),
		);	



	
	// save selected tab, but only if not in search or file mode
	if(!x($_GET,'search') && !x($_GET,'file')) {
		set_pconfig( local_user(), 'network.view','tab.selected',array($all_active, $postord_active, $conv_active, $new_active, $starred_active, $bookmarked_active, $spam_active) );
	}


	$contact_id = $a->cid;

	require_once('include/acl_selectors.php');

	$cid = ((x($_GET,'cid')) ? intval($_GET['cid']) : 0);
	$star = ((x($_GET,'star')) ? intval($_GET['star']) : 0);
	$order = ((x($_GET,'order')) ? notags($_GET['order']) : 'comment');
	$liked = ((x($_GET,'liked')) ? intval($_GET['liked']) : 0);
	$conv = ((x($_GET,'conv')) ? intval($_GET['conv']) : 0);
	$spam = ((x($_GET,'spam')) ? intval($_GET['spam']) : 0);
	$cmin = ((x($_GET,'cmin')) ? intval($_GET['cmin']) : 0);
	$cmax = ((x($_GET,'cmax')) ? intval($_GET['cmax']) : 99);
	$file = ((x($_GET,'file')) ? $_GET['file'] : '');



	if(x($_GET,'search') || x($_GET,'file'))
		$nouveau = true;
	if($cid)
		$def_acl = array('allow_cid' => '<' . intval($cid) . '>');


	if(! $update) {

		if(feature_enabled(local_user(),'affinity')) {
			$tpl = get_markup_template('main_slider.tpl');
			$o .= replace_macros($tpl,array(
				'$val' => intval($cmin) . ';' . intval($cmax),
				'$refresh' => t('Refresh'),
				'$me' => t('Me'),
				'$intimate' => t('Best Friends'),
				'$friends' => t('Friends'),
				'$coworkers' => t('Co-workers'),
				'$oldfriends' => t('Former Friends'),
				'$acquaintances' => t('Acquaintances'),
				'$world' => t('Everybody')
			));
		}
 	
		$arr = array('tabs' => $tabs);
		call_hooks('network_tabs', $arr);

		$o .= replace_macros(get_markup_template('common_tabs.tpl'), array('$tabs'=> $arr['tabs']));

		// --- end item filter tabs


		// search terms header
		if($search)
			$o .= '<h2>' . t('Search Results For:') . ' '  . htmlspecialchars($search) . '</h2>';

		nav_set_selected('network');

		$celeb = ((($a->user['page-flags'] == PAGE_SOAPBOX) || ($a->user['page-flags'] == PAGE_COMMUNITY)) ? true : false);

		$x = array(
			'is_owner' => true,
			'allow_location' => $a->user['allow_location'], // FIXME
			'default_location' => $channel['channel_location'],
			'nickname' => $channel['channel_address'],
			'lockstate' => (($group || $cid || $channel['channel_allow_cid'] || $channel['channel_allow_gid'] || $channel['channel_deny_cid'] || $channel['channel_deny_gid']) ? 'lock' : 'unlock'),
// FIXME
			'acl' => populate_acl((($group || $cid || $nets) ? $def_acl : $channel), $celeb),
			'bang' => (($group || $cid) ? '!' : ''),
			'visitor' => 'block',
			'profile_uid' => local_user()
		);

		$o .= status_editor($a,$x);

	}


	// We don't have to deal with ACL's on this page. You're looking at everything
	// that belongs to you, hence you can see all of it. We will filter by group if
	// desired. 

	
	$sql_options  = (($star) 
		? " and (item_flags & " . intval(ITEM_STARRED) . ")" 
		: '');

	$sql_nets = '';

	$sql_extra = " AND `item`.`parent` IN ( SELECT `parent` FROM `item` WHERE (item_flags & " . intval(ITEM_THREAD_TOP) . ") $sql_options ) ";

	if($group) {
		$r = q("SELECT `name`, `id` FROM `group` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($group),
			intval($_SESSION['uid'])
		);
		if(! count($r)) {
			if($update)
				killme();
			notice( t('No such group') . EOL );
			goaway($a->get_baseurl(true) . '/network');
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

		$sql_extra = " AND `item`.`parent` IN ( SELECT DISTINCT(`parent`) FROM `item` WHERE 1 $sql_options AND ( `contact-id` IN ( $contact_str ) OR `allow_gid` like '" . protect_sprintf('%<' . intval($group) . '>%') . "' ) and deleted = 0 ) ";
		$o = '<h2>' . t('Group: ') . $r[0]['name'] . '</h2>' . $o;
	}
	elseif($cid) {

		$r = q("SELECT `id`,`name`,`network`,`writable`,`nurl` FROM `contact` WHERE `id` = %d 
				AND `blocked` = 0 AND `pending` = 0 LIMIT 1",
			intval($cid)
		);
		if(count($r)) {
			$sql_extra = " AND `item`.`parent` IN ( SELECT DISTINCT(`parent`) FROM `item` WHERE 1 $sql_options AND `contact-id` = " . intval($cid) . " and deleted = 0 ) ";
			$o = '<h2>' . t('Contact: ') . $r[0]['name'] . '</h2>' . $o;
		}
		else {
			notice( t('Invalid contact.') . EOL);
			goaway($a->get_baseurl(true) . '/network');
			// NOTREACHED
		}
	}


	if(! $update) {
		// The special div is needed for liveUpdate to kick in for this page.
		// We only launch liveUpdate if you aren't filtering in some incompatible 
		// way and also you aren't writing a comment (discovered in javascript).

		$o .= '<div id="live-network"></div>' . "\r\n";
		$o .= "<script> var profile_uid = " . $_SESSION['uid'] 
			. "; var netargs = '" . substr($a->cmd,8)
			. '?f='
			. ((x($_GET,'cid'))    ? '&cid='    . $_GET['cid']    : '')
			. ((x($_GET,'search')) ? '&search=' . $_GET['search'] : '') 
			. ((x($_GET,'star'))   ? '&star='   . $_GET['star']   : '') 
			. ((x($_GET,'order'))  ? '&order='  . $_GET['order']  : '') 
			. ((x($_GET,'liked'))  ? '&liked='  . $_GET['liked']  : '') 
			. ((x($_GET,'conv'))   ? '&conv='   . $_GET['conv']   : '') 
			. ((x($_GET,'spam'))   ? '&spam='   . $_GET['spam']   : '') 
			. ((x($_GET,'cmin'))   ? '&cmin='   . $_GET['cmin']   : '') 
			. ((x($_GET,'cmax'))   ? '&cmax='   . $_GET['cmax']   : '') 
			. ((x($_GET,'file'))   ? '&file='   . $_GET['file']   : '') 

			. "'; var profile_page = " . $a->pager['page'] . ";</script>";


		$a->page['htmlhead'] .= replace_macros(get_markup_template("build_query.tpl"),array(
			'$baseurl' => z_root(),
			'$pgtype' => 'network',
			'$uid' => ((local_user()) ? local_user() : '0'),
			'$gid' => (($gid) ? $gid : '0'),
			'$cid' => (($cid) ? $cid : '0'),
			'$cmin' => (($cmin) ? $cmin : '0'),
			'$cmax' => (($cmax) ? $cmax : '0'),
			'$star' => (($star) ? $star : '0'),
			'$liked' => (($liked) ? $liked : '0'),
			'$conv' => (($conv) ? $conv : '0'),
			'$spam' => (($spam) ? $spam : '0'),
			'$nouveau' => (($nouveau) ? $nouveau : '0'),
			'$wall' => '0',
			'$page' => (($a->pager['page'] != 1) ? $a->pager['page'] : 1),
			'$search' => $search,
			'$order' => $order,
			'$file' => $file,
			'$cats' => '',
			'$dend' => $datequery,
			'$dbegin' => $datequery2
		));
	}

	$sql_extra3 = '';

	if($datequery) {
		$sql_extra3 .= protect_sprintf(sprintf(" AND item.created <= '%s' ", dbesc(datetime_convert(date_default_timezone_get(),'',$datequery))));
	}
	if($datequery2) {
		$sql_extra3 .= protect_sprintf(sprintf(" AND item.created >= '%s' ", dbesc(datetime_convert(date_default_timezone_get(),'',$datequery2))));
	}

	$sql_extra2 = (($nouveau) ? '' : " AND `item`.`parent` = `item`.`id` ");
	$sql_extra3 = (($nouveau) ? '' : $sql_extra3);

	if(x($_GET,'search')) {
		$search = escape_tags($_GET['search']);
		if(strpos($search,'#') === 0)
			$sql_extra .= term_query('item',substr($search,1),TERM_HASHTAG);
		else
			$sql_extra .= sprintf(" AND `item`.`body` like '%s' ",
				dbesc(protect_sprintf('%' . $search . '%'))
			);
	}

	if(strlen($file)) {
		$sql_extra .= term_query('item',$file,TERM_FILE);
	}

	if($conv) {
		$sql_extra .= sprintf(" AND parent IN (SELECT distinct(parent) from item where ( author_xchan like '%s' or ( item_flags & %d ))) ",
			dbesc(protect_sprintf($channel['channel_hash'])),
			intval(ITEM_MENTIONSME)
		);
	}

	if($update && ! $load) {

		// only setup pagination on initial page view
		$pager_sql = '';

	}
	else {
		$itemspage = get_pconfig(local_user(),'system','itemspage');
		$a->set_pager_itemspage(((intval($itemspage)) ? $itemspage : 30));
		$pager_sql = sprintf(" LIMIT %d, %d ",intval($a->pager['start']), intval($a->pager['itemspage']));
	}


	if(($cmin != 0) || ($cmax != 99)) {

		// Not everybody who shows up in the network stream will be in your address book.
		// By default those that aren't are assumed to have closeness = 99; but this isn't
		// recorded anywhere. So if cmax is 99, we'll open the search up to anybody in 
		// the stream with a NULL address book entry.

		$sql_nets .= " AND ";

		if($cmax == 99)
			$sql_nets .= " ( ";

		$sql_nets .= "( abook.abook_closeness >= " . intval($cmin) . " ";
		$sql_nets .= " AND abook.abook_closeness <= " . intval($cmax) . " ) ";

		if($cmax == 99)
			$sql_nets .= " OR abook.abook_closeness IS NULL ) ";


	}

	$simple_update = (($update) ? " and ( item.item_flags & " . intval(ITEM_UNSEEN) . " ) " : '');
	if($load)
		$simple_update = '';

	$start = dba_timer();

	if($nouveau && $load) {
		// "New Item View" - show all items unthreaded in reverse created date order

		$items = q("SELECT `item`.*, `item`.`id` AS `item_id` FROM `item` 
			WHERE `item`.`uid` = %d AND item_restrict = 0 
			$simple_update
			$sql_extra $sql_nets
			ORDER BY `item`.`received` DESC $pager_sql ",
			intval($_SESSION['uid'])
		);

		require_once('include/items.php');

		xchan_query($items);

		$items = fetch_post_tags($items);
	}
	elseif($update) {

		// Normal conversation view

		if($order === 'post')
				$ordering = "`created`";
		else
				$ordering = "`commented`";

		if($load) {

			// Fetch a page full of parent items for this page

			$r = q("SELECT distinct item.id AS item_id FROM item 
				left join abook on item.author_xchan = abook.abook_xchan
				WHERE item.uid = %d AND item.item_restrict = 0
				AND item.parent = item.id
				and ((abook.abook_flags & %d) = 0 or abook.abook_flags is null)
				$sql_extra3 $sql_extra $sql_nets
				ORDER BY item.$ordering DESC $pager_sql ",
				intval(local_user()),
				intval(ABOOK_FLAG_BLOCKED)
			);

		}
		else {
			// update
			$r = q("SELECT item.parent AS item_id FROM item
				left join abook on item.author_xchan = abook.abook_xchan
				WHERE item.uid = %d AND item.item_restrict = 0 $simple_update
				and ((abook.abook_flags & %d) = 0 or abook.abook_flags is null)
				$sql_extra3 $sql_extra $sql_nets ",
				intval(local_user()),
				intval(ABOOK_FLAG_BLOCKED)
			);

		}

		$first = dba_timer();

		// Then fetch all the children of the parents that are on this page

		if($r) {

			$parents_str = ids_to_querystr($r,'item_id');

			$items = q("SELECT `item`.*, `item`.`id` AS `item_id` FROM `item` 
				WHERE `item`.`uid` = %d AND `item`.`item_restrict` = 0
				AND `item`.`parent` IN ( %s )
				$sql_extra ",
				intval(local_user()),
				dbesc($parents_str)
			);

			$second = dba_timer();

			xchan_query($items);

			$third = dba_timer();

			$items = fetch_post_tags($items);

			$fourth = dba_timer();

			$items = conv_sort($items,$ordering);

			

			//logger('items: ' . print_r($items,true));

		} 
		else {
			$items = array();
		}

		if($parents_str)
			$update_unseen = ' AND parent IN ( ' . dbesc($parents_str) . ' )';

	}

//	logger('items: ' . count($items));

	if($update_unseen)
		$r = q("UPDATE `item` SET item_flags = ( item_flags ^ %d)
			WHERE (item_flags & %d) AND `uid` = %d $update_unseen ",
			intval(ITEM_UNSEEN),
			intval(ITEM_UNSEEN),
			intval(local_user())
		);

	$mode = (($nouveau) ? 'network-new' : 'network');

	$fifth = dba_timer();

	$o .= conversation($a,$items,$mode,$update,'client');

	$sixth = dba_timer();


	if(! $update) 
        $o .= alt_pager($a,count($items));

	if($load) {
		profiler($start,$first,'network parents');
		profiler($first,$second,'network children');
		profiler($second,$third,'network authors');
		profiler($third,$fourth,'network tags');
		profiler($fourth,$fifth,'network sort');
		profiler($fifth,$sixth,'network render');
		profiler($start,$sixth,'network total');
		profiler(1,1,'--');
	}

	return $o;
}
