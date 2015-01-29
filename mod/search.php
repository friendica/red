<?php

function search_init(&$a) {
	if(x($_REQUEST,'search'))
		$a->data['search'] = $_REQUEST['search'];
}


function search_content(&$a,$update = 0, $load = false) {

	if((get_config('system','block_public')) || (get_config('system','block_public_search'))) {
		if ((! local_channel()) && (! remote_channel())) {
			notice( t('Public access denied.') . EOL);
		return;
		}
	}
	nav_set_selected('search');

	require_once("include/bbcode.php");
	require_once('include/security.php');
	require_once('include/conversation.php');
	require_once('include/items.php');

	$format = (($_REQUEST['format']) ? $_REQUEST['format'] : '');
	if($format !== '') {
		$update = $load = 1;
	}

	$observer = $a->get_observer();
	$observer_hash = (($observer) ? $observer['xchan_hash'] : '');

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

	if((! local_channel()) || (! feature_enabled(local_channel(),'savedsearch')))
		$o .= search($search,'search-box','/search',((local_channel()) ? true : false));

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
		$regstr = db_getfunc('REGEXP');
		$sql_extra = sprintf(" AND `item`.`body` $regstr '%s' ", dbesc(protect_sprintf(preg_quote($search))));
	}

	// Here is the way permissions work in the search module...
	// Only public posts can be shown
	// OR your own posts if you are a logged in member
	// No items will be shown if the member has a blocked profile wall. 

	if((! $update) && (! $load)) {

		// This is ugly, but we can't pass the profile_uid through the session to the ajax updater,
		// because browser prefetching might change it on us. We have to deliver it with the page.

		$o .= '<div id="live-search"></div>' . "\r\n";
		$o .= "<script> var profile_uid = " . ((intval(local_channel())) ? local_channel() : (-1))
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
			'$fh' => '0',
			'$nouveau' => '0',
			'$wall' => '0',
			'$list' => ((x($_REQUEST,'list')) ? intval($_REQUEST['list']) : 0),
			'$page' => (($a->pager['page'] != 1) ? $a->pager['page'] : 1),
			'$search' => (($tag) ? urlencode('#') : '') . $search,
			'$order' => '',
			'$file' => '',
			'$cats' => '',
			'$tags' => '',
			'$mid' => '',
			'$verb' => '',
			'$dend' => '',
			'$dbegin' => ''
		));


	}

	$pub_sql = public_permissions_sql($observer_hash);

	require_once('include/identity.php');

	$sys = get_sys_channel();

	if(($update) && ($load)) {
		$itemspage = get_pconfig(local_channel(),'system','itemspage');
		$a->set_pager_itemspage(((intval($itemspage)) ? $itemspage : 20));
		$pager_sql = sprintf(" LIMIT %d OFFSET %d ", intval($a->pager['itemspage']), intval($a->pager['start']));

		// in case somebody turned off public access to sys channel content with permissions

		if(! perm_is_allowed($sys['channel_id'],$observer_hash,'view_stream'))
			$sys['xchan_hash'] .= 'disabled';

		if($load) {
			$r = null;
			
			if(ACTIVE_DBTYPE == DBTYPE_POSTGRES) {
				$prefix = 'distinct on (created, mid)';
				$suffix = 'ORDER BY created DESC, mid';
			} else {
				$prefix = 'distinct';
				$suffix = 'group by mid ORDER BY created DESC';
			}
			if(local_channel()) {
				$r = q("SELECT $prefix mid, item.id as item_id, item.* from item
					WHERE item_restrict = 0
					AND ((( `item`.`allow_cid` = ''  AND `item`.`allow_gid` = '' AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = '' AND item_private = 0 ) 
					OR ( `item`.`uid` = %d )) OR item.owner_xchan = '%s' )
					$sql_extra
					$suffix $pager_sql ",
					intval(local_channel()),
					dbesc($sys['xchan_hash'])
				);
			}
			if($r === null) {
				$r = q("SELECT $prefix mid, item.id as item_id, item.* from item
					WHERE item_restrict = 0
					AND (((( `item`.`allow_cid` = ''  AND `item`.`allow_gid` = '' AND `item`.`deny_cid`  = ''
					AND `item`.`deny_gid`  = '' AND item_private = 0 )
					and owner_xchan in ( " . stream_perms_xchans(($observer) ? (PERMS_NETWORK|PERMS_PUBLIC) : PERMS_PUBLIC) . " ))
						$pub_sql ) OR owner_xchan = '%s')
					$sql_extra 
					$suffix $pager_sql",
					dbesc($sys['xchan_hash'])
				);
			}
		}
		else {
			$r = array();
		}
	}

	if($r) {
		xchan_query($r);
		$items = fetch_post_tags($r,true);
	} else {
		$items = array();
	}


	if($format == 'json') {
		$result = array();
		require_once('include/conversation.php');
		foreach($items as $item) {
			$item['html'] = bbcode($item['body']);
			$x = encode_item($item);
			$x['html'] = prepare_text($item['body'],$item['mimetype']);
			$result[] = $x;
		}
		json_return_and_die(array('success' => true,'messages' => $result));
	}

	if($tag) 
		$o .= '<h2>Items tagged with: ' . htmlspecialchars($search, ENT_COMPAT,'UTF-8') . '</h2>';
	else
		$o .= '<h2>Search results for: ' . htmlspecialchars($search, ENT_COMPAT,'UTF-8') . '</h2>';

	$o .= conversation($a,$items,'search',$update,'client');

	return $o;
}

