<?php

require_once('include/items.php');
require_once('include/conversation.php');


function home_init(&$a) {

	$ret = array();
	call_hooks('home_init',$ret);

	$splash = ((argc() > 1 && argv(1) === 'splash') ? true : false);

	$channel = $a->get_channel();
	if(local_channel() && $channel && $channel['xchan_url'] && ! $splash) {
		$dest = $channel['channel_startpage'];
		if(! $dest)
			$dest = get_pconfig(local_channel(),'system','startpage');
		if(! $dest)
			$dest = get_config('system','startpage');
		if(! $dest)
			$dest = z_root() . '/apps';

		goaway($dest);
	}

	if(get_account_id() && ! $splash) {
		goaway(z_root() . '/new_channel');
	}

}


function home_content(&$a, $update = 0, $load = false) {

	$o = '';

	if(x($_SESSION,'theme'))
		unset($_SESSION['theme']);
	if(x($_SESSION,'mobile_theme'))
		unset($_SESSION['mobile_theme']);

	$splash = ((argc() > 1 && argv(1) === 'splash') ? true : false);

	if(get_config('system','projecthome')) {
		$o .= file_get_contents('assets/home.html');
		$a->page['template'] = 'full';
		$a->page['title'] = t('Red Matrix - &quot;The Network&quot;');
		return $o;
	}


	// Deprecated
	$channel_address = get_config("system", "site_channel" );
	
	// See if the sys channel set a homepage
	if (! $channel_address) {
		require_once('include/identity.php');
		$u = get_sys_channel();
		if ($u) {
			$u = array($u);
			// change to channel_id when below deprecated and skip the $u=...
			$channel_address = $u[0]['channel_address'];
		}
	}

	if($channel_address) {

		$page_id = 'home';

		$u = q("select channel_id from channel where channel_address = '%s' limit 1",
			dbesc($channel_address)
		);

		$r = q("select item.* from item left join item_id on item.id = item_id.iid
			where item.uid = %d and sid = '%s' and service = 'WEBPAGE' and 
			item_restrict = %d limit 1",
			intval($u[0]['channel_id']),
			dbesc($page_id),
			intval(ITEM_WEBPAGE)
		);

		if($r) {
			xchan_query($r);
			$r = fetch_post_tags($r,true);
			$a->profile = array('profile_uid' => $u[0]['channel_id']);
			$a->profile_uid = $u[0]['channel_id'];
			$o .= prepare_page($r[0]);
			return $o;
		}
	}

	// Nope, we didn't find an item.  Let's see if there's any html

	if(file_exists('home.html')) {
		$o .= file_get_contents('home.html');
	}
	else {
		$sitename = get_config('system','sitename');
		if($sitename) 
			$o .= '<h1>' . sprintf( t("Welcome to %s") ,$sitename) . '</h1>';

		if(intval(get_config('system','block_public')) && (! local_channel()) && (! remote_channel())) {
			// If there's nothing special happening, just spit out a login box

			if (! $a->config['system']['no_login_on_homepage'])
				$o .= login(($a->config['system']['register_policy'] == REGISTER_CLOSED) ? 0 : 1);
			return $o;
		}
		else {

			if(get_config('system','disable_discover_tab')) {
				call_hooks('home_content',$o);
				return $o;
			}

			if(! $update) {

				$maxheight = get_config('system','home_divmore_height');
				if(! $maxheight)
					$maxheight = 75;

				$o .= '<div id="live-home"></div>' . "\r\n";
				$o .= "<script> var profile_uid = " . ((intval(local_channel())) ? local_channel() : (-1)) 
					. "; var profile_page = " . $a->pager['page'] 
					. "; divmore_height = " . intval($maxheight) . "; </script>\r\n";

				$a->page['htmlhead'] .= replace_macros(get_markup_template("build_query.tpl"),array(
					'$baseurl' => z_root(),
					'$pgtype'  => 'home',
					'$uid'     => ((local_channel()) ? local_channel() : '0'),
					'$gid'     => '0',
					'$cid'     => '0',
					'$cmin'    => '0',
					'$cmax'    => '99',
					'$star'    => '0',
					'$liked'   => '0',
					'$conv'    => '0',
					'$spam'    => '0',
					'$fh'      => '1',
					'$nouveau' => '0',
					'$wall'    => '0',
					'$list'    => '0',
					'$page'    => (($a->pager['page'] != 1) ? $a->pager['page'] : 1),
					'$search'  => '',
					'$order'   => 'comment',
					'$file'    => '',
					'$cats'    => '',
					'$tags'    => '',
					'$dend'    => '',
					'$mid'     => '',
					'$verb'     => '',
					'$dbegin'  => ''
				));
			}

			if($update && ! $load) {
				// only setup pagination on initial page view
				$pager_sql = '';
			}
			else {
				$a->set_pager_itemspage(20);
				$pager_sql = sprintf(" LIMIT %d OFFSET %d ", intval($a->pager['itemspage']), intval($a->pager['start']));
			}

			require_once('include/identity.php');
			$sys = get_sys_channel();
			$uids = " and item.uid  = " . intval($sys['channel_id']) . " ";
			$a->data['firehose'] = intval($sys['channel_id']);

			$page_mode = 'list';

			$simple_update = (($update) ? " and item.item_unseen = 1 " : '');

			if($update && $_SESSION['loadtime'])
				$simple_update .= " and item.changed > '" . datetime_convert('UTC','UTC',$_SESSION['loadtime']) . "' ";
			if($load)
				$simple_update = '';

			//logger('update: ' . $update . ' load: ' . $load);

			if($update) {

				$ordering = "commented";

				if($load) {

					$_SESSION['loadtime'] = datetime_convert();

					// Fetch a page full of parent items for this page

					$r = q("SELECT distinct item.id AS item_id, $ordering FROM item
						left join abook on item.author_xchan = abook.abook_xchan
						WHERE true $uids AND item.item_restrict = 0
						AND item.parent = item.id
						and ((abook.abook_flags & %d) = 0 or abook.abook_flags is null)
						$sql_extra3 $sql_extra $sql_nets
						ORDER BY $ordering DESC $pager_sql ",
						intval(ABOOK_FLAG_BLOCKED)
					);

				}

				// Then fetch all the children of the parents that are on this page
				$parents_str = '';
				$update_unseen = '';

				if($r) {

					$parents_str = ids_to_querystr($r,'item_id');

					$items = q("SELECT item.*, item.id AS item_id FROM item
						WHERE true $uids AND item.item_restrict = 0
						AND item.parent IN ( %s )
						$sql_extra ",
						dbesc($parents_str)
					);

					xchan_query($items,true,(-1));
					$items = fetch_post_tags($items,true);
					$items = conv_sort($items,$ordering);
				}
				else {
					$items = array();
				}

			}

			// fake it
			$mode = ('network');

			$o .= conversation($a,$items,$mode,$update,$page_mode);

			if(($items) && (! $update))
				$o .= alt_pager($a,count($items));

			return $o;

		}	
		call_hooks('home_content',$o);
		return $o;	
	}

	return $o;

}