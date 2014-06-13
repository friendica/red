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
    var a;
    a = $("#nav-search-text").autocomplete({
        serviceUrl: '$base/acl',
        minChars: 2,
        width: 250,
        id: 'nav-search-text-ac',
    });
    a.setOptions({ autoSubmit: true, params: { type: 'x' }});

});

</script>
EOT;



	if(local_user()) {
		$channel = $a->get_channel();
		$observer = $a->get_observer();
	}
	elseif(remote_user())
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

	if(local_user()) {
		$nav['logout'] = Array('logout',t('Logout'), "", t('End this session'));
		
		// user menu
		$nav['usermenu'][] = Array('channel/' . $channel['channel_address'], t('Home'), "", t('Your posts and conversations'));
		$nav['usermenu'][] = Array('profile/' . $channel['channel_address'], t('View Profile'), "", t('Your profile page'));
		if(feature_enabled(local_user(),'multi_profiles'))
			$nav['usermenu'][]   = Array('profiles', t('Edit Profiles'),"", t('Manage/Edit profiles'));
		$nav['usermenu'][] = Array('photos/' . $channel['channel_address'], t('Photos'), "", t('Your photos'));
		$nav['usermenu'][] = Array('cloud/' . $channel['channel_address'],t('Files'),"",t('Your files'));
		$nav['usermenu'][] = Array('chat/' . $channel['channel_address'],t('Chat'),"",t('Your chatrooms'));
		$nav['usermenu'][] = Array('events', t('Events'), "", t('Your events'));
		$nav['usermenu'][] = Array('bookmarks', t('Bookmarks'), "", t('Your bookmarks'));
		if(feature_enabled($channel['channel_id'],'webpages'))
			$nav['usermenu'][] = Array('webpages/' . $channel['channel_address'],t('Webpages'),"",t('Your webpages'));	
	}
	else {
		if(! get_account_id()) 
			$nav['login'] = Array('login',t('Login'), ($a->module == 'login'?'selected':''), t('Sign in'));
		else
			$nav['alogout'] = Array('logout',t('Logout'), "", t('End this session'));


	}

	if($observer) {
			$userinfo = array(
			'icon' => $observer['xchan_photo_m'],
			'name' => $observer['xchan_addr'],
		);
	}

	if($observer) {
		$nav['locked'] = true;
		$nav['lock'] = array('logout','','lock', 
			sprintf( t('%s - click to logout'), $observer['xchan_addr']));
	}
	else {
		$nav['locked'] = false;
		$nav['lock'] = array('rmagic','','unlock', 
			t('Click to authenticate to your home hub'));
	}

	/**
	 * "Home" should also take you home from an authenticated remote profile connection
	 */

	$homelink = get_my_url();
	if(! $homelink) {
		$observer = $a->get_observer();
		$homelink = (($observer) ? $observer['xchan_url'] : '');
	}

	if(($a->module != 'home') && (! (local_user()))) 
		$nav['home'] = array($homelink, t('Home'), "", t('Home Page'));


	if(($a->config['system']['register_policy'] == REGISTER_OPEN) && (! local_user()) && (! remote_user()))
		$nav['register'] = array('register',t('Register'), "", t('Create an account'));

	$help_url = z_root() . '/help?f=&cmd=' . $a->cmd;

	if(! get_config('system','hide_help'))
		$nav['help'] = array($help_url, t('Help'), "", t('Help and documentation'));


	$nav['apps'] = array('apps', t('Apps'), "", t('Applications, utilities, links, games'));

	$nav['search'] = array('search', t('Search'), "", t('Search site content'));


	$nav['directory'] = array('directory', t('Directory'), "", t('Channel Locator')); 


	/**
	 *
	 * The following nav links are only show to logged in users
	 *
	 */

	if(local_user()) {

		$nav['network'] = array('network', t('Matrix'), "", t('Your matrix'));
		$nav['network']['mark'] = array('', t('Mark all matrix notifications seen'), '','');

		$nav['home'] = array('channel/' . $channel['channel_address'], t('Channel Home'), "", t('Channel home'));
		$nav['home']['mark'] = array('', t('Mark all channel notifications seen'), '','');


		$nav['intros'] = array('connections/ifpending',	t('Connections'), "", t('Connections'));


		$nav['notifications'] = array('notifications/system',	t('Notices'), "", t('Notifications'));
		$nav['notifications']['all']=array('notifications/system', t('See all notifications'), "", "");
		$nav['notifications']['mark'] = array('', t('Mark all system notifications seen'), '','');

		$nav['messages'] = array('message', t('Mail'), "", t('Private mail'));
		$nav['messages']['all']=array('message', t('See all private messages'), "", "");
		$nav['messages']['mark'] = array('', t('Mark all private messages seen'), '','');
		$nav['messages']['inbox'] = array('message', t('Inbox'), "", t('Inbox'));
		$nav['messages']['outbox']= array('message/sent', t('Outbox'), "", t('Outbox'));
		$nav['messages']['new'] = array('mail/new', t('New Message'), "", t('New Message'));


		$nav['all_events'] = array('events', t('Events'), "", t('Event Calendar'));
		$nav['all_events']['all']=array('events', t('See all events'), "", "");
		$nav['all_events']['mark'] = array('', t('Mark all events seen'), '','');
		
		$nav['manage'] = array('manage', t('Channel Select'), "", t('Manage Your Channels'));

		$nav['settings'] = array('settings', t('Settings'),"", t('Account/Channel Settings'));

		$nav['contacts'] = array('connections', t('Connections'),"", t('Manage/Edit Friends and Connections'));
	}

	/**
	 * Admin page
	 */
	 if (is_site_admin()){
		 $nav['admin'] = array('admin/', t('Admin'), "", t('Site Setup and Configuration'));
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

	$tpl = get_markup_template('nav.tpl');

	$a->page['nav'] .= replace_macros($tpl, array(
        '$baseurl' => $a->get_baseurl(),
		'$sitelocation' => $sitelocation,
		'$nav' => $x['nav'],
		'$banner' =>  $banner,
		'$emptynotifications' => t('Nothing new here'),
		'$userinfo' => $x['usermenu'],
		'$localuser' => local_user(),
		'$sel' => 	$a->nav_sel,
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
