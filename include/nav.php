<?php /** @file */

function nav(&$a) {

	/**
	 *
	 * Build page header and site navigation bars
	 *
	 */

	if(!(x($a->page,'nav')))
		$a->page['nav'] = '';

	$base = z_root();
    $a->page['htmlhead'] .= <<< EOT

<script>$(document).ready(function() {
	$("#nav-search-text").search_autocomplete('$base/acl');
});

</script>
EOT;



	if(local_channel()) {
		$channel = $a->get_channel();
		$observer = $a->get_observer();
		$prof = q("select id from profile where uid = %d and is_default = 1",
			intval($channel['channel_id'])
		);

		$chans = q("select channel_name, channel_id from channel where channel_account_id = %d and not ( channel_pageflags & %d )>0 order by channel_name ",
			intval(get_account_id()),
			intval(PAGE_REMOVED)
		);




	}
	elseif(remote_channel())
		$observer = $a->get_observer();
	

	$myident = (($channel) ? $channel['xchan_addr'] : '');
		
	$sitelocation = (($myident) ? $myident : $a->get_hostname());



	/**
	 *
	 * Provide a banner/logo/whatever
	 *
	 */

	$banner = get_config('system','banner');

	if($banner === false) 
		$banner = get_config('system','sitename');

	$a->page['header'] .= replace_macros(get_markup_template('hdr.tpl'), array(
        '$baseurl' => $a->get_baseurl(),
		'$sitelocation' => $sitelocation,
		'$banner' =>  $banner
	));


	// nav links: array of array('href', 'text', 'extra css classes', 'title')
	$nav = Array();

	/**
	 * Display login or logout
	 */

	$nav['usermenu']=array();
	$userinfo = null;
	$nav['loginmenu']=array();

	if(local_channel()) {


		if($chans && count($chans) > 1 && feature_enabled(local_channel(),'nav_channel_select'))
			$nav['channels'] = $chans;

		$nav['logout'] = Array('logout',t('Logout'), "", t('End this session'),'logout_nav_btn');
		
		// user menu
		$nav['usermenu'][] = Array('channel/' . $channel['channel_address'], t('Home'), "", t('Your posts and conversations'),'channel_nav_btn');
		$nav['usermenu'][] = Array('profile/' . $channel['channel_address'], t('View Profile'), "", t('Your profile page'),'profile_nav_btn');
		if(feature_enabled(local_channel(),'multi_profiles'))
			$nav['usermenu'][]   = Array('profiles', t('Edit Profiles'),"", t('Manage/Edit profiles'),'profiles_nav_btn');
		else
			$nav['usermenu'][]   = Array('profiles/' . $prof[0]['id'], t('Edit Profile'),"", t('Edit your profile'),'profiles_nav_btn');

		$nav['usermenu'][] = Array('photos/' . $channel['channel_address'], t('Photos'), "", t('Your photos'),'photos_nav_btn');
		$nav['usermenu'][] = Array('cloud/' . $channel['channel_address'],t('Files'),"",t('Your files'),'cloud_nav_btn');

		require_once('include/chat.php');
		$has_chats = chatroom_list_count(local_channel());
		if($has_chats) {
			$nav['usermenu'][] = Array('chat/' . $channel['channel_address'],t('Chat'),"",t('Your chatrooms'),'chat_nav_btn');
		}

		require_once('include/menu.php');
		$has_bookmarks = menu_list_count(local_channel(),'',MENU_BOOKMARK) + menu_list_count(local_channel(),'',MENU_SYSTEM|MENU_BOOKMARK);
		if($has_bookmarks) {
			$nav['usermenu'][] = Array('bookmarks', t('Bookmarks'), "", t('Your bookmarks'),'bookmarks_nav_btn');
		}

		if(feature_enabled($channel['channel_id'],'webpages'))
			$nav['usermenu'][] = Array('webpages/' . $channel['channel_address'],t('Webpages'),"",t('Your webpages'),'webpages_nav_btn');
	}
	else {
		if(! get_account_id())  {
			$nav['loginmenu'][] = Array('login',t('Login'),'',t('Sign in'),'login_nav_btn');
		}
		else
			$nav['alogout'] = Array('logout',t('Logout'), "", t('End this session'),'logout_nav_btn');


	}

	if($observer) {
			$userinfo = array(
			'icon' => $observer['xchan_photo_s'],
			'name' => $observer['xchan_addr'],
		);
	}

	if($observer) {
		$nav['lock'] = array('logout','','lock', 
			sprintf( t('%s - click to logout'), $observer['xchan_addr']));
	}
	else {
		$nav['loginmenu'][] = Array('rmagic',t('Remote authentication'),'',t('Click to authenticate to your home hub'),'rmagic_nav_btn');
	}

	/**
	 * "Home" should also take you home from an authenticated remote profile connection
	 */

	$homelink = get_my_url();
	if(! $homelink) {
		$observer = $a->get_observer();
		$homelink = (($observer) ? $observer['xchan_url'] : '');
	}

	if(($a->module != 'home') && (! (local_channel()))) 
		$nav['home'] = array($homelink, t('Home'), "", t('Home Page'),'home_nav_btn');


	if(($a->config['system']['register_policy'] == REGISTER_OPEN) && (! local_channel()) && (! remote_channel()))
		$nav['register'] = array('register',t('Register'), "", t('Create an account'),'register_nav_btn');

	$help_url = z_root() . '/help?f=&cmd=' . $a->cmd;

	if(! get_config('system','hide_help'))
		$nav['help'] = array($help_url, t('Help'), "", t('Help and documentation'),'help_nav_btn');


	$nav['apps'] = array('apps', t('Apps'), "", t('Applications, utilities, links, games'),'apps_nav_btn');

	$nav['search'] = array('search', t('Search'), "", t('Search site content'));


	$nav['directory'] = array('directory', t('Directory'), "", t('Channel Directory'),'directory_nav_btn'); 


	/**
	 *
	 * The following nav links are only show to logged in users
	 *
	 */

	if(local_channel()) {

		$network_options = get_pconfig(local_channel(),'system','network_page_default');
	
		$nav['network'] = array('network' . (($network_options) ? '?f=&' . $network_options : ''), 
			t('Matrix'), "", t('Your matrix'),'network_nav_btn');
		$nav['network']['mark'] = array('', t('Mark all matrix notifications seen'), '','');

		$nav['home'] = array('channel/' . $channel['channel_address'], t('Channel Home'), "", t('Channel home'),'home_nav_btn');
		$nav['home']['mark'] = array('', t('Mark all channel notifications seen'), '','');


		$nav['intros'] = array('connections/ifpending',	t('Connections'), "", t('Connections'),'connections_nav_btn');


		$nav['notifications'] = array('notifications/system',	t('Notices'), "", t('Notifications'),'notifications_nav_btn');
		$nav['notifications']['all']=array('notifications/system', t('See all notifications'), "", "");
		$nav['notifications']['mark'] = array('', t('Mark all system notifications seen'), '','');

		$nav['messages'] = array('message', t('Mail'), "", t('Private mail'),'mail_nav_btn');
		$nav['messages']['all']=array('message', t('See all private messages'), "", "");
		$nav['messages']['mark'] = array('', t('Mark all private messages seen'), '','');
		$nav['messages']['inbox'] = array('message', t('Inbox'), "", t('Inbox'));
		$nav['messages']['outbox']= array('message/sent', t('Outbox'), "", t('Outbox'));
		$nav['messages']['new'] = array('mail/new', t('New Message'), "", t('New Message'));


		$nav['all_events'] = array('events', t('Events'), "", t('Event Calendar'),'events_nav_btn');
		$nav['all_events']['all']=array('events', t('See all events'), "", "");
		$nav['all_events']['mark'] = array('', t('Mark all events seen'), '','');
		
		$nav['manage'] = array('manage', t('Channel Manager'), "", t('Manage Your Channels'),'manage_nav_btn');

		$nav['settings'] = array('settings', t('Settings'),"", t('Account/Channel Settings'),'settings_nav_btn');

	}

	/**
	 * Admin page
	 */
	 if (is_site_admin()){
		 $nav['admin'] = array('admin/', t('Admin'), "", t('Site Setup and Configuration'),'admin_nav_btn');
	 }


	/**
	 *
	 * Provide a banner/logo/whatever
	 *
	 */

	$banner = get_config('system','banner');

	if($banner === false) 
		$banner = get_config('system','sitename');

	$x = array('nav' => $nav, 'usermenu' => $userinfo );
	call_hooks('nav', $x);

// Not sure the best place to put this on the page. So I'm implementing it but leaving it 
// turned off until somebody discovers this and figures out a good location for it. 
$powered_by = '';

//	$powered_by = '<strong>red<img class="smiley" src="' . $a->get_baseurl() . '/images/rm-16.png" alt="r#" />matrix</strong>';

	$tpl = get_markup_template('nav.tpl');

	$a->page['nav'] .= replace_macros($tpl, array(
        '$baseurl' => $a->get_baseurl(),
		'$sitelocation' => $sitelocation,
		'$nav' => $x['nav'],
		'$banner' =>  $banner,
		'$emptynotifications' => t('Loading...'),
		'$userinfo' => $x['usermenu'],
		'$localuser' => local_channel(),
		'$sel' => 	$a->nav_sel,
		'$powered_by' => $powered_by,
		'$help' => t('@name, #tag, content'),
		'$pleasewait' => t('Please wait...')
	));

	call_hooks('page_header', $a->page['nav']);
}

/*
 * Set a menu item in navbar as selected
 * 
 */
function nav_set_selected($item){
	$a = get_app();
    $a->nav_sel = array(
		'community' 	=> null,
		'network' 		=> null,
		'home'			=> null,
		'profiles'		=> null,
		'intros'        => null,
		'notifications'	=> null,
		'messages'		=> null,
		'directory'	    => null,
		'settings'		=> null,
		'contacts'		=> null,
		'manage'        => null,
		'register'      => null,
	);
	$a->nav_sel[$item] = 'active';
}
