<?php /** @file */

require_once('include/dir_fns.php');
require_once('include/contact_widgets.php');


function widget_profile($args) {
	$a = get_app();
	$block = (((get_config('system','block_public')) && (! local_user()) && (! remote_user())) ? true : false);
	return profile_sidebar($a->profile, $block, true);
}

// FIXME The problem with the next widget is that we don't have a search function for webpages that we can send the links to.
// Then we should also provide an option to search webpages and conversations.

function widget_tagcloud($args) {

	$o = '';
	$tab = 0;
	$a = get_app();
	$uid = $a->profile_uid;
	$count = ((x($args,'count')) ? intval($args['count']) : 24);
	$flags = 0;
	$type = TERM_CATEGORY;

	$r = tagadelic($uid,$count,$authors,$flags,ITEM_WEBPAGE,$type);

	if($r) {
		$o = '<div class="tagblock widget"><h3>' . t('Categories') . '</h3><div class="tags" align="center">';
		foreach($r as $rr) {
			$o .= '<span class="tag'.$rr[2].'">'.$rr[0].'</span> ' . "\r\n";
		}
		$o .= '</div></div>';
	}
	return $o;
}

function widget_collections($args) {
	require_once('include/group.php');

	$mode = ((array_key_exists('mode',$args)) ? $args['mode'] : 'conversation');
	switch($mode) {
		case 'conversation':
				$every = argv(0);
				$each = argv(0);
				$edit = true;
				$current = $_REQUEST['gid'];
				$abook_id = 0;
				$wmode = 0;
				break;
		case 'groups':
				$every = 'connections';
				$each = argv(0);
				$edit = false;
				$current = intval(argv(1));
				$abook_id = 0;
				$wmode = 1;
				break;
		case 'abook':
				$every = 'connections';
				$each = 'group';
				$edit = false;
				$current = 0;
				$abook_id = get_app()->poi['abook_xchan'];
				$wmode = 1;
				break;
		default:
			return '';
			break;
	}
			
	return group_side($every, $each, $edit, $current, $abook_id, $wmode);

}


function widget_suggestions($arr) {

	if((! local_user()) || (! feature_enabled(local_user(),'suggest')))
		return '';

	require_once('include/socgraph.php');

	$r = suggestion_query(local_user(),get_observer_hash(),0,20);

	if(! $r) {
		return;
	}

	$arr = array();

	// Get two random entries from the top 20 returned.
	// We'll grab the first one and the one immediately following.
	// This will throw some entropy intot he situation so you won't 
	// be looking at the same two mug shots every time the widget runs


	$index = ((count($r) > 2) ? mt_rand(0,count($r) - 2) : 0);
		

	for($x = $index; $x <= ($index+1); $x ++) {

		$rr = $r[$x];
		if(! $rr['xchan_url'])
			break;
		
		$connlnk = z_root() . '/follow/?url=' . $rr['xchan_addr'];

		$arr[] = array(
			'url' => chanlink_url($rr['xchan_url']),
			'profile' => $rr['xchan_url'],
			'name' => $rr['xchan_name'],
			'photo' => $rr['xchan_photo_m'],
			'ignlnk' => z_root() . '/suggest?ignore=' . $rr['xchan_hash'],
			'conntxt' => t('Connect'),
			'connlnk' => $connlnk,
			'ignore' => t('Ignore/Hide')
		);
	}


	$o = replace_macros(get_markup_template('suggest_widget.tpl'),array(
		'$title' => t('Suggestions'),
		'$more' => t('See more...'),
		'$entries' => $arr
	));

	return $o;

}


function widget_follow($args) {
	if(! local_user())
		return '';
	$a = get_app();
	$uid =$a->channel['channel_id'];
	$r = q("select count(*) as total from abook where abook_channel = %d and not (abook_flags & %d) ",
		intval($uid),
		intval(ABOOK_FLAG_SELF)
	);
	if($r)
		$total_channels = $r[0]['total'];	
	$limit = service_class_fetch($uid,'total_channels');
	if($limit !== false) {
			$abook_usage_message = sprintf( t("You have %1$.0f of %2$.0f allowed connections."), $total_channels, $limit);
	}
	else {
			$abook_usage_message = '';
 	}
	return replace_macros(get_markup_template('follow.tpl'),array(
		'$connect' => t('Add New Connection'),
		'$desc' => t('Enter the channel address'),
		'$hint' => t('Example: bob@example.com, http://example.com/barbara'),
		'$follow' => t('Connect'),
		'$abook_usage_message' => $abook_usage_message
	));

}


function widget_notes($arr) {
	if(! local_user())
		return '';
	if(! feature_enabled(local_user(),'private_notes'))
		return '';

	$text = get_pconfig(local_user(),'notes','text');

	$o = replace_macros(get_markup_template('notes.tpl'), array(
		'$banner' => t('Notes'),
		'$text' => $text,
		'$save' => t('Save'),
	));
	return $o;
}


function widget_savedsearch($arr) {
	if((! local_user()) || (! feature_enabled(local_user(),'savedsearch')))
		return '';

	$a = get_app();

	$search = ((x($_GET,'search')) ? $_GET['search'] : '');

	if(x($_GET,'searchsave') && $search) {
		$r = q("select * from `term` where `uid` = %d and `type` = %d and `term` = '%s' limit 1",
			intval(local_user()),
			intval(TERM_SAVEDSEARCH),
			dbesc($search)
		);
		if(! $r) {
			q("insert into `term` ( `uid`,`type`,`term` ) values ( %d, %d, '%s') ",
				intval(local_user()),
				intval(TERM_SAVEDSEARCH),
				dbesc($search)
			);
		}
	}

	if(x($_GET,'searchremove') && $search) {
		q("delete from `term` where `uid` = %d and `type` = %d and `term` = '%s' limit 1",
			intval(local_user()),
			intval(TERM_SAVEDSEARCH),
			dbesc($search)
		);
		$search = '';
	}



	$srchurl = $a->query_string;

	$srchurl =  rtrim(preg_replace('/searchsave\=[^\&].*?(\&|$)/is','',$srchurl),'&');
	$hasq = ((strpos($srchurl,'?') !== false) ? true : false);
	$srchurl =  rtrim(preg_replace('/searchremove\=[^\&].*?(\&|$)/is','',$srchurl),'&');
	$hasq = ((strpos($srchurl,'?') !== false) ? true : false);

	$srchurl =  rtrim(preg_replace('/search\=[^\&].*?(\&|$)/is','',$srchurl),'&');
	$srchurl = str_replace(array('?f=','&f='),array('',''),$srchurl);
	$hasq = ((strpos($srchurl,'?') !== false) ? true : false);
	
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
				'dellink'       => z_root() . '/' . $srchurl . (($hasq) ? '' : '?f=') . '&amp;searchremove=1&amp;search=' . urlencode($rr['term']),
				'srchlink'      => z_root() . '/' . $srchurl . (($hasq) ? '' : '?f=') . '&amp;search=' . urlencode($rr['term']),
				'displayterm'   => htmlspecialchars($rr['term'], ENT_COMPAT,'UTF-8'),
				'encodedterm' 	=> urlencode($rr['term']),
				'delete'		=> t('Remove term'),
				'selected'		=> ($search==$rr['term']),
			);
		}
	}		

	
	$tpl = get_markup_template("saved_searches.tpl");
	$o = replace_macros($tpl, array(
		'$title'	 => t('Saved Searches'),
		'$add'		 => t('add'),
		'$searchbox' => searchbox('','netsearch-box',$srchurl . (($hasq) ? '' : '?f='),true),
		'$saved' 	 => $saved,
	));

	return $o;

}


function widget_filer($arr) {
	if(! local_user())
		return '';

	$a = get_app();

	$selected = ((x($_REQUEST,'file')) ? $_REQUEST['file'] : '');

	$terms = array();
	$r = q("select distinct(term) from term where uid = %d and type = %d order by term asc",
		intval(local_user()),
		intval(TERM_FILE)
	);
	if(! $r)
		return;

	foreach($r as $rr)
		$terms[] = array('name' => $rr['term'], 'selected' => (($selected == $rr['term']) ? 'selected' : ''));

	return replace_macros(get_markup_template('fileas_widget.tpl'),array(
		'$title' => t('Saved Folders'),
		'$desc' => '',
		'$sel_all' => (($selected == '') ? 'selected' : ''),
		'$all' => t('Everything'),
		'$terms' => $terms,
		'$base' => z_root() . '/' . $a->cmd

	));
}

function widget_archive($arr) {

	$o = '';
	$a = get_app();

	if(! $a->profile_uid) {
		return '';
	}

	$uid = $a->profile_uid;

	if(! feature_enabled($uid,'archives'))
		return '';


	$wall = ((array_key_exists('wall', $arr)) ? intval($arr['wall']) : 0);
	$url = z_root() . '/' . $a->cmd;


	$ret = posted_dates($uid,$wall);

	if(! count($ret))
		return '';

	$o = replace_macros(get_markup_template('posted_date_widget.tpl'),array(
		'$title' => t('Archives'),
		'$size' => ((count($ret) > 6) ? 6 : count($ret)),
		'$url' => $url,
		'$dates' => $ret
	));
	return $o;
}


function widget_fullprofile($arr) {
	$a = get_app();
	if(! $a->profile['profile_uid'])
		return;

	$block = (((get_config('system','block_public')) && (! local_user()) && (! remote_user())) ? true : false);

	return profile_sidebar($a->profile, $block);
}

function widget_categories($arr) {
	$a = get_app();
	$cat = ((x($_REQUEST,'cat')) ? htmlspecialchars($_REQUEST['cat'],ENT_COMPAT,'UTF-8') : '');
	$srchurl = $a->query_string;
	$srchurl =  rtrim(preg_replace('/cat\=[^\&].*?(\&|$)/is','',$srchurl),'&');
	$srchurl = str_replace(array('?f=','&f='),array('',''),$srchurl);
	return categories_widget($srchurl,$cat);

}

function widget_tagcloud_wall($arr) {
	$a = get_app();
	if((! $a->profile['profile_uid']) || (! $a->profile['channel_hash']))
		return '';
	$limit = ((array_key_exists('limit',$arr)) ? intval($arr['limit']) : 50);
	if(feature_enabled($a->profile['profile_uid'],'tagadelic'))
		return tagblock('search',$a->profile['profile_uid'],$limit,$a->profile['channel_hash'],ITEM_WALL);
	return '';
}


function widget_affinity($arr) {

	if(! local_user())
		return '';

	$cmin = ((x($_REQUEST,'cmin')) ? intval($_REQUEST['cmin']) : 0);
	$cmax = ((x($_REQUEST,'cmax')) ? intval($_REQUEST['cmax']) : 99);

	if(feature_enabled(local_user(),'affinity')) {
		$tpl = get_markup_template('main_slider.tpl');
		$x = replace_macros($tpl,array(
			'$val' => $cmin . ';' . $cmax,
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
		return $arr['html']; 
	}
 	return '';
}


function widget_settings_menu($arr) {

	if(! local_user())
		return;

	$a = get_app();
	$channel = $a->get_channel();

	$abook_self_id = 0;

	// Retrieve the 'self' address book entry for use in the auto-permissions link

	$abk = q("select abook_id from abook where abook_channel = %d and ( abook_flags & %d ) limit 1",
		intval(local_user()),
		intval(ABOOK_FLAG_SELF)
	);
	if($abk)
		$abook_self_id = $abk[0]['abook_id'];


	$tabs = array(
		array(
			'label'	=> t('Account settings'),
			'url' 	=> $a->get_baseurl(true).'/settings/account',
			'selected'	=> ((argv(1) === 'account') ? 'active' : ''),
		),
	
		array(
			'label'	=> t('Channel settings'),
			'url' 	=> $a->get_baseurl(true).'/settings/channel',
			'selected'	=> ((argv(1) === 'channel') ? 'active' : ''),
		),
	
		array(
			'label'	=> t('Additional features'),
			'url' 	=> $a->get_baseurl(true).'/settings/features',
			'selected'	=> ((argv(1) === 'features') ? 'active' : ''),
		),

		array(
			'label'	=> t('Feature settings'),
			'url' 	=> $a->get_baseurl(true).'/settings/featured',
			'selected'	=> ((argv(1) === 'featured') ? 'active' : ''),
		),

		array(
			'label'	=> t('Display settings'),
			'url' 	=> $a->get_baseurl(true).'/settings/display',
			'selected'	=> ((argv(1) === 'display') ? 'active' : ''),
		),	
		
		array(
			'label' => t('Connected apps'),
			'url' => $a->get_baseurl(true) . '/settings/oauth',
			'selected' => ((argv(1) === 'oauth') ? 'active' : ''),
		),

		array(
			'label' => t('Export channel'),
			'url' => $a->get_baseurl(true) . '/uexport/basic',
			'selected' => ''
		),

//		array(
//			'label' => t('Export account'),
//			'url' => $a->get_baseurl(true) . '/uexport/complete',
//			'selected' => ''
//		),

		array(
			'label' => t('Automatic Permissions (Advanced)'),
			'url' => $a->get_baseurl(true) . '/connedit/' . $abook_self_id,
			'selected' => ''
		),


	);

	if(feature_enabled(local_user(),'premium_channel')) {
		$tabs[] = array(
			'label' => t('Premium Channel Settings'),
			'url' => $a->get_baseurl(true) . '/connect/' . $channel['channel_address'],
			'selected' => ''
		);

	}

	if(feature_enabled(local_user(),'channel_sources')) {
		$tabs[] = array(
			'label' => t('Channel Sources'),
			'url' => $a->get_baseurl(true) . '/sources',
			'selected' => ''
		);

	}


	
	$tabtpl = get_markup_template("generic_links_widget.tpl");
	return replace_macros($tabtpl, array(
		'$title' => t('Settings'),
		'$class' => 'settings-widget',
		'$items' => $tabs,
	));

}


function widget_mailmenu($arr) {
	if (! local_user())
		return;

	$a = get_app();
	return replace_macros(get_markup_template('message_side.tpl'), array(
		'$tabs'=> array(),

		'$check'=>array(
			'label' => t('Check Mail'),
			'url' => $a->get_baseurl(true) . '/message',
			'sel' => (argv(1) == ''),
		),
		'$new'=>array(
			'label' => t('New Message'),
			'url' => $a->get_baseurl(true) . '/mail/new',
			'sel'=> (argv(1) == 'new'),
		)

	));

}

function widget_design_tools($arr) {
	$a = get_app();

	// mod menu doesn't load a profile. For any modules which load a profile, check it.
	// otherwise local_user() is sufficient for permissions.

	if($a->profile['profile_uid']) 
		if($a->profile['profile_uid'] != local_user())
				return '';
 
	if(! local_user())
		return '';

	return design_tools();
}

function widget_findpeople($arr) {
	return findpeople_widget();
}


function widget_photo_albums($arr) {
	$a = get_app();
	if(! $a->profile['profile_uid'])
		return '';
	$channelx = channelx_by_n($a->profile['profile_uid']);
	if((! $channelx) || (! perm_is_allowed($a->profile['profile_uid'],get_observer_hash(),'view_photos')))
		return '';
	return photos_album_widget($channelx[0],$a->get_observer());	

}


function widget_vcard($arr) {
	require_once ('include/Contact.php');
	return vcard_from_xchan('',get_app()->get_observer());
}


/**
 * The following directory widgets are only useful on the directory page
 */

function widget_dirsafemode($arr) {
	return dir_safe_mode();
}

function widget_dirsort($arr) {
	return dir_sort_links();
}

function widget_dirtags($arr) {
	return dir_tagblock(z_root() . '/directory',null);
}

function widget_menu_preview($arr) {
	if(! get_app()->data['menu_item'])
		return;
	require_once('include/menu.php');
	return menu_render(get_app()->data['menu_item']);
}

function widget_chatroom_list($arr) {

	$a = get_app();

	require_once("include/chat.php");
	$r = chatroom_list($a->profile['profile_uid']);

	return replace_macros(get_markup_template('chatroomlist.tpl'),array(
		'$header' => t('Chat Rooms'),
		'$baseurl' => z_root(),
		'$nickname' => $a->profile['channel_address'],
		'$items' => $r,
	));
}

