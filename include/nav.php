<?php

function nav(&$a) {

	/**
	 *
	 * Build page header and site navigation bars
	 *
	 */

	$ssl_state = ((local_user()) ? true : false);

	if(!(x($a->page,'nav')))
		$a->page['nav'] = '';

	$base = $a->get_baseurl($ssl_state);
    $a->page['htmlhead'] .= <<< EOT

<script>$(document).ready(function() {
    var a;
    a = $("#nav-search-text").autocomplete({
        serviceUrl: '$base/acl',
        minChars: 2,
        width: 250,
    });
    a.setOptions({ autoSubmit: true, params: { type: 'x' }});

});

</script>
EOT;



	/**
	 * Placeholder div for popup panel
	 */

	/**
	 *
	 * Our network is distributed, and as you visit friends some of the 
	 * sites look exactly the same - it isn't always easy to know where you are.
	 * Display the current site location as a navigation aid.
	 *
	 */

	if(local_user()) {
		$channel = $a->get_channel();
		$observer = $a->get_observer();
	}
	elseif(remote_user())
		$observer = $a->get_observer();
	

	$myident = (($channel) ? $channel['xchan_addr'] : '');
		
	$sitelocation = (($myident) ? $myident : $a->get_hostname());


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
		$nav['usermenu'][] = Array('channel/' . $channel['channel_address'], t('Status'), "", t('Your posts and conversations'));
		$nav['usermenu'][] = Array('profile/' . $channel['channel_address'], t('Profile'), "", t('Your profile page'));

		$nav['usermenu'][] = Array('photos/' . $channel['channel_address'], t('Photos'), "", t('Your photos'));
		$nav['usermenu'][] = Array('events/', t('Events'), "", t('Your events'));
		
	}
	else {
		$nav['login'] = Array('login',t('Login'), ($a->module == 'login'?'selected':''), t('Sign in'));
	}

	if($observer) {
			$userinfo = array(
			'icon' => $observer['xchan_photo_s'],
			'name' => $observer['xchan_addr'],
		);
	}


	$nav['lock'] = array('rmagic','',(($observer) ? 'lock' : 'unlock'), (($observer) ? $observer['xchan_addr'] : t('Click to authenticate to your home hub')));

	/**
	 * "Home" should also take you home from an authenticated remote profile connection
	 */

	$homelink = get_my_url();
	if(! $homelink)
		$homelink = ((x($_SESSION,'visitor_home')) ? $_SESSION['visitor_home'] : '');

	if(($a->module != 'home') && (! (local_user()))) 
		$nav['home'] = array($homelink, t('Home'), "", t('Home Page'));


	if(($a->config['register_policy'] == REGISTER_OPEN) && (! local_user()) && (! remote_user()))
		$nav['register'] = array('register',t('Register'), "", t('Create an account'));

	$help_url = $a->get_baseurl($ssl_state) . '/help';

	if(! get_config('system','hide_help'))
		$nav['help'] = array($help_url, t('Help'), "", t('Help and documentation'));

	if(count($a->get_apps()) > 0)
		$nav['apps'] = array('apps', t('Apps'), "", t('Addon applications, utilities, games'));

	$nav['search'] = array('search', t('Search'), "", t('Search site content'));

	$gdirpath = 'directory';

	$nav['directory'] = array($gdirpath, t('Directory'), "", t('People directory')); 

	/**
	 *
	 * The following nav links are only show to logged in users
	 *
	 */

	if(local_user()) {

		$nav['network'] = array('network', t('Network'), "", t('Conversations from your friends'));

		$nav['home'] = array('channel/' . $channel['channel_address'], t('Home'), "", t('Your posts and conversations'));

		$nav['intros'] = array('intro',	t('Introductions'), "", t('New Connections'));


		$nav['notifications'] = array('notifications',	t('Notifications'), "", t('Notifications'));
		$nav['notifications']['all']=array('notifications/system', t('See all notifications'), "", "");
		$nav['notifications']['mark'] = array('', t('Mark all system notifications seen'), '','');

		$nav['messages'] = array('message', t('Messages'), "", t('Private mail'));
		$nav['messages']['inbox'] = array('message', t('Inbox'), "", t('Inbox'));
		$nav['messages']['outbox']= array('message/sent', t('Outbox'), "", t('Outbox'));
		$nav['messages']['new'] = array('message/new', t('New Message'), "", t('New Message'));
		
		$nav['manage'] = array('manage', t('Channel Select'), "", t('Manage Your Channels'));

		$nav['settings'] = array('settings', t('Settings'),"", t('Account/Channel Settings'));
		if(feature_enabled(local_user(),'multi_profiles'))
			$nav['profiles'] = array('profiles', t('Profiles'),"", t('Manage/Edit Profiles'));

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
//		$banner .= '<a href="http://friendica.com"><img id="logo-img" src="images/fred-32.png" alt="logo" /></a>';
		$banner = 'red';

	$tpl = get_markup_template('nav.tpl');

	$a->page['nav'] .= replace_macros($tpl, array(
        '$baseurl' => $a->get_baseurl(),
		'$langselector' => lang_selector(),
		'$sitelocation' => $sitelocation,
		'$nav' => $nav,
		'$banner' =>  $banner,
		'$emptynotifications' => t('Nothing new here'),
		'$userinfo' => $userinfo,
		'$localuser' => local_user(),
		'$sel' => 	$a->nav_sel,
		'$apps' => $a->get_apps(),
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
	$a->nav_sel[$item] = 'selected';
}
