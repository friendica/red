<?php


function display_content(&$a, $update = 0, $load = false) {

//	logger("mod-display: update = $update load = $load");

	if(intval(get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		notice( t('Public access denied.') . EOL);
		return;
	}

	require_once("include/bbcode.php");
	require_once('include/security.php');
	require_once('include/conversation.php');
	require_once('include/acl_selectors.php');
	require_once('include/items.php');


	$a->page['htmlhead'] .= replace_macros(get_markup_template('display-head.tpl'), array());

	if(argc() > 1 && argv(1) !== 'load')
		$item_hash = argv(1);


	if($_REQUEST['mid'])
		$item_hash = $_REQUEST['mid'];


	if(! $item_hash) {
		$a->error = 404;
		notice( t('Item not found.') . EOL);
		return;
	}

	$observer_is_owner = false;

	// This page can be viewed by anybody so the query could be complicated
	// First we'll see if there is a copy of the item which is owned by us - if we're logged in locally.
	// If that fails (or we aren't logged in locally), 
	// query an item in which the observer (if logged in remotely) has cid or gid rights
	// and if that fails, look for a copy of the post that has no privacy restrictions.  
	// If we find the post, but we don't find a copy that we're allowed to look at, this fact needs to be reported.

	// find a copy of the item somewhere

	$target_item = null;

	$r = q("select mid, parent_mid from item where mid = '%s' limit 1",
		dbesc($item_hash)
	);

	if($r) {
		$target_item = $r[0];
	}

	if((! $update) && (! $load)) {


		$o .= '<div id="live-display"></div>' . "\r\n";
		$o .= "<script> var profile_uid = " . ((intval(local_user())) ? local_user() : (-1))
			. "; var netargs = '?f='; var profile_page = " . $a->pager['page'] . "; </script>\r\n";

		$a->page['htmlhead'] .= replace_macros(get_markup_template("build_query.tpl"),array(
			'$baseurl' => z_root(),
			'$pgtype' => 'display',
			'$uid' => '0',
			'$gid' => '0',
			'$cid' => '0',
			'$cmin' => '0',
			'$cmax' => '99',
			'$star' => '0',
			'$liked' => '0',
			'$conv' => '0',
			'$spam' => '0',
			'$nouveau' => '0',
			'$wall' => '0',
			'$page' => (($a->pager['page'] != 1) ? $a->pager['page'] : 1),
			'$search' => '',
			'$order' => '',
			'$file' => '',
			'$cats' => '',
			'$dend' => '',
			'$dbegin' => '',
			'$mid' => $item_hash
		));


	}

	$sql_extra = public_permissions_sql(get_observer_hash());

	if($update && $load) {

		$updateable = false;

		$pager_sql = sprintf(" LIMIT %d, %d ",intval($a->pager['start']), intval($a->pager['itemspage']));

		if($load) {
			$r = null;
			if(local_user()) {
				$r = q("SELECT * from item
					WHERE item_restrict = 0
					and uid = %d
					and mid = '%s'
					limit 1",
					intval(local_user()),
					dbesc($target_item['parent_mid'])
				);
				if($r) {
					$updateable = true;

				}
			}
			if($r === null) {

				$r = q("SELECT * from item
					WHERE item_restrict = 0
					and mid = '%s'
					AND ((( `item`.`allow_cid` = ''  AND `item`.`allow_gid` = '' AND `item`.`deny_cid`  = '' 
					AND `item`.`deny_gid`  = '' AND item_private = 0 ) 
					and owner_xchan in ( " . stream_perms_xchans(($observer) ? PERMS_NETWORK : PERMS_PUBLIC) . " ))
					$sql_extra )
					group by mid limit 1",
					dbesc($target_item['parent_mid'])
				);

			}

		}
		else {
			$r = array();
		}
	}

	if($r) {

		$parents_str = ids_to_querystr($r,'id');
		if($parents_str) {

			$items = q("SELECT `item`.*, `item`.`id` AS `item_id` 
				FROM `item`
				WHERE item_restrict = 0 and parent in ( %s ) ",
				dbesc($parents_str)
			);

			xchan_query($items);
			$items = fetch_post_tags($items,true);
			$items = conv_sort($items,'created');
		}
	} else {
		$items = array();
	}



	$o .= conversation($a, $items, 'display', $update, 'client');

	if($updateable) {
		$x = q("UPDATE item SET item_flags = ( item_flags ^ %d )
			WHERE (item_flags & %d) AND uid = %d and parent = %d ",
			intval(ITEM_UNSEEN),
			intval(ITEM_UNSEEN),
			intval(local_user()),
			intval($r[0]['parent'])
		);
	}

	$o .= '<div id="content-complete"></div>';

	return $o;


/*
	elseif((! $update) && (!  {
		
		$r = q("SELECT `id`, item_flags FROM `item` WHERE `id` = '%s' OR `mid` = '%s' LIMIT 1",
			dbesc($item_hash),
			dbesc($item_hash)
		);
		if($r) {
			if($r[0]['item_flags'] & ITEM_DELETED) {
				notice( t('Item has been removed.') . EOL );
			}
			else {	
				notice( t('Permission denied.') . EOL ); 
			}
		}
		else {
			notice( t('Item not found.') . EOL );
		}

	}
*/
	return $o;
}

