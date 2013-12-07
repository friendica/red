<?php

require_once('include/items.php');
require_once('include/group.php');
require_once('include/contact_widgets.php');
require_once('include/conversation.php');
require_once('include/acl_selectors.php');


function network_init(&$a) {
	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$channel = $a->get_channel();
	$a->profile_uid = local_user();
	head_set_icon($channel['xchan_photo_s']);
	
    
	if(! x($a->page,'aside'))
		$a->page['aside'] = '';

	$search = ((x($_GET,'search')) ? $_GET['search'] : '');
	


	if(x($_GET,'save') && $search) {
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


	$a->page['aside'] .= group_side('network','network',true,$_GET['gid']);
	$a->page['aside'] .= posted_date_widget($a->get_baseurl() . '/network',local_user(),false);	
	$a->page['aside'] .= suggest_widget();

	$a->page['aside'] .= saved_searches($search);
	$a->page['aside'] .= fileas_widget($a->get_baseurl(true) . '/network',(x($_GET, 'file') ? $_GET['file'] : ''));


	if($search) {
		if(strpos($search,'@') === 0) {
			$r = q("select abook_id from abook left join xchan on abook_xchan = xchan_hash where xchan_name = '%s' and abook_channel = %d limit 1",
				dbesc(substr($search,1)),
				intval(local_user())
			);
			if($r) {
				$_GET['cid'] = $r[0]['abook_id'];
				$search = $_GET['search'] = '';
			}
		}
		elseif(strpos($search,'#') === 0) {
			$search = $_GET['search'] = substr($search,1);
		}
	}

	$group_id = ((x($_GET,'gid')) ? intval($_GET['gid']) : 0);




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
		'$searchbox' => search('','netsearch-box',$srchurl,true),
		'$saved' 	 => $saved,
	));
	
	return $o;

}



function network_content(&$a, $update = 0, $load = false) {


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
        $r = q("SELECT * FROM `group` WHERE id = %d AND uid = %d LIMIT 1",
            intval($gid),
            intval(local_user())
        );
        if(! $r) {
			if($update)
				killme();
			notice( t('No such group') . EOL );
			goaway($a->get_baseurl(true) . '/network');
			// NOTREACHED
		}

		$group = $gid;
		$group_hash = $r[0]['hash'];
		$def_acl = array('allow_gid' => '<' . $r[0]['hash'] . '>');
	}

	$o = '';

	

	// if no tabs are selected, defaults to comments

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
			$x = replace_macros($tpl,array(
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
			$arr = array('html' => $x);
			call_hooks('main_slider',$arr);
			$o .= $arr['html']; 
		}
 	

		$o .= network_tabs();

		// --- end item filter tabs

		$search = (($_GET['search']) ? $_GET['search'] : '');
		// search terms header
		if($search)
			$o .= '<h2>' . t('Search Results For:') . ' '  . htmlspecialchars($search) . '</h2>';

		nav_set_selected('network');

		$channel_acl = array(
			'allow_cid' => $channel['channel_allow_cid'], 
			'allow_gid' => $channel['channel_allow_gid'], 
			'deny_cid' => $channel['channel_deny_cid'], 
			'deny_gid' => $channel['channel_deny_gid']
		); 


		$x = array(
			'is_owner' => true,
			'allow_location' => ((intval(get_pconfig($channel['channel_id'],'system','use_browser_location'))) ? '1' : ''),
			'default_location' => $channel['channel_location'],
			'nickname' => $channel['channel_address'],
			'lockstate' => (($group || $cid || $channel['channel_allow_cid'] || $channel['channel_allow_gid'] || $channel['channel_deny_cid'] || $channel['channel_deny_gid']) ? 'lock' : 'unlock'),
			'acl' => populate_acl((($group || $cid) ? $def_acl : $channel_acl)),
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
		$contact_str = '';
        $contacts = group_get_members($group);
        if($contacts) {
			foreach($contacts as $c) {
				if($contact_str)
					$contact_str .= ',';
            	$contact_str .= "'" . $c['xchan'] . "'";
			}
        }
        else {
			$contact_str = ' 0 ';	
			info( t('Group is empty'));
        }

        $sql_extra = " AND item.parent IN ( SELECT DISTINCT parent FROM item WHERE true $sql_options AND (( author_xchan IN ( $contact_str ) OR owner_xchan in ( $contact_str )) or allow_gid like '" . protect_sprintf('%<' . dbesc($group_hash) . '>%') . "' ) and id = parent and item_restrict = 0 ) ";

		$x = group_rec_byhash(local_user(), $group_hash);

		if($x)
			$o = '<h2>' . t('Collection: ') . $x['name'] . '</h2>' . $o;


    }

	elseif($cid) {

        $r = q("SELECT abook.*, xchan.* from abook left join xchan on abook_xchan = xchan_hash where abook_id = %d and abook_channel = %d and not ( abook_flags & " . intval(ABOOK_FLAG_BLOCKED) . ") limit 1",
			intval($cid),
			intval(local_user())
        );
        if($r) {
            $sql_extra = " AND item.parent IN ( SELECT DISTINCT parent FROM item WHERE true $sql_options AND uid = " . intval(local_user()) . " AND ( author_xchan = '" . dbesc($r[0]['abook_xchan']) . "' or owner_xchan = '" . dbesc($r[0]['abook_xchan']) . "' ) and item_restrict = 0 ) ";
			$o = '<h2>' . t('Connection: ') . $r[0]['xchan_name'] . '</h2>' . $o;
        }
        else {
			notice( t('Invalid connection.') . EOL);
			goaway($a->get_baseurl(true) . '/network');
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
			'$search' => (($search) ? $search : ''),
			'$order' => $order,
			'$file' => $file,
			'$cats' => '',
			'$dend' => $datequery,
			'$mid' => '',
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
		$a->set_pager_itemspage(((intval($itemspage)) ? $itemspage : 20));
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

		$items = fetch_post_tags($items,true);
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
		$parents_str = '';
		$update_unseen = '';

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

			$items = fetch_post_tags($items,true);

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


	if(($items) && (! $update)) 
        $o .= alt_pager($a,count($items));

	if($load) {
//		logger('mod_network: load: ' . count($items) . ' items', LOGGER_DATA);

		profiler($start,$first,'network parents');
		profiler($first,$second,'network children');
		profiler($second,$third,'network authors');
		profiler($third,$fourth,'network tags');
		profiler($fourth,$fifth,'network sort');
		profiler($fifth,$sixth,'network render');
		profiler($start,$sixth,'network total');
		profiler(1,1,'-- ' . count($items));
	}

	return $o;
}
