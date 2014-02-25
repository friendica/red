<?php

function search_init(&$a) {
	if(x($_REQUEST,'search'))
		$a->data['search'] = $_REQUEST['search'];
}


function search_content(&$a,$update = 0, $load = false) {

	if((get_config('system','block_public')) || (get_config('system','block_public_search'))) {
		if ((! local_user()) && (! remote_user())) {
			notice( t('Public access denied.') . EOL);
		return;
		}
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
		$o .= "<script> var profile_uid = " . ((intval(local_user())) ? local_user() : (-1))
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
			'$list' => ((x($_REQUEST,'list')) ? intval($_REQUEST['list']) : 0),
			'$page' => (($a->pager['page'] != 1) ? $a->pager['page'] : 1),
			'$search' => (($tag) ? urlencode('#') : '') . $search,
			'$order' => '',
			'$file' => '',
			'$cats' => '',
			'$mid' => '',
			'$dend' => '',
			'$dbegin' => ''
		));


	}

	$pub_sql = public_permissions_sql(get_observer_hash());

	if(($update) && ($load)) {
		$itemspage = get_pconfig(local_user(),'system','itemspage');
		$a->set_pager_itemspage(((intval($itemspage)) ? $itemspage : 20));
		$pager_sql = sprintf(" LIMIT %d, %d ",intval($a->pager['start']), intval($a->pager['itemspage']));

		if($load) {
			$r = null;

			if(local_user()) {
				$r = q("SELECT distinct mid, item.id as item_id, item.* from item
					WHERE item_restrict = 0
					AND (( `item`.`allow_cid` = ''  AND `item`.`allow_gid` = '' AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = '' AND item_private = 0 ) 
					OR ( `item`.`uid` = %d ))
					$sql_extra
					group by mid ORDER BY created DESC $pager_sql ",
					intval(local_user()),
					intval(ABOOK_FLAG_BLOCKED)

				);
			}
			if($r === null) {
               $r = q("SELECT distinct mid, item.id as item_id, item.* from item
                    WHERE item_restrict = 0
                    AND ((( `item`.`allow_cid` = ''  AND `item`.`allow_gid` = '' AND `item`.`deny_cid`  = ''
                    AND `item`.`deny_gid`  = '' AND item_private = 0 )
                    and owner_xchan in ( " . stream_perms_xchans(($observer) ? PERMS_NETWORK : PERMS_PUBLIC) . " ))
					$pub_sql )
                    $sql_extra 
                    group by mid ORDER BY created DESC $pager_sql"
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

	if($tag) 
		$o .= '<h2>Items tagged with: ' . htmlspecialchars($search, ENT_COMPAT,'UTF-8') . '</h2>';
	else
		$o .= '<h2>Search results for: ' . htmlspecialchars($search, ENT_COMPAT,'UTF-8') . '</h2>';

	$o .= conversation($a,$items,'search',$update,'client');

	return $o;
}

