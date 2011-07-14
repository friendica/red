<?php

function nav(&$a) {

	/**
	 *
	 * Build page header and site navigation bars
	 *
	 */

	if(!(x($a->page,'nav')))
		$a->page['nav'] = '';

	/**
	 * Placeholder div for popup panel
	 */

	$a->page['nav'] .= '<div id="panel" style="display: none;"></div>' ;

	/**
	 *
	 * Our network is distributed, and as you visit friends some of the 
	 * sites look exactly the same - it isn't always easy to know where you are.
	 * Display the current site location as a navigation aid.
	 *
	 */

	$myident = ((is_array($a->user) && isset($a->user['nickname'])) ? $a->user['nickname'] . '@' : '');
		
	$sitelocation = $myident . substr($a->get_baseurl(),strpos($a->get_baseurl(),'//') + 2 );


	// nav links: array of array('href', 'text', 'extra css classes', 'title')
	$nav = Array();

	/**
	 * Display login or logout
	 */

	if(local_user()) {
		$nav['logout'] = Array('logout',t('Logout'), "", t('End this session'));
	}
	else {
		$nav['login'] = Array('login',t('Login'), ($a->module == 'login'?'nav-selected':''), t('Sign in'));
	}


	/**
	 * "Home" should also take you home from an authenticated remote profile connection
	 */

	$homelink = ((x($_SESSION,'visitor_home')) ? $_SESSION['visitor_home'] : '');

	if(($a->module != 'home') && (! (local_user()))) 
		$nav['home'] = array($homelink, t('Home'), "", t('Home Page'));


	if(($a->config['register_policy'] == REGISTER_OPEN) && (! local_user()) && (! remote_user()))
		$nav['register'] = array('register',t('Register'), "", t('Create an account'));

	$help_url = $a->get_baseurl() . '/help';

	if(! get_config('system','hide_help'))
		$nav['help'] = array($help_url, t('Help'), "", t('Help and documentation'));

	if($a->apps)
		$nav['apps'] = array('apps', t('Apps'), "", t('Addon applications, utilities, games'));

	$nav['search'] = array('search', t('Search'), "", t('Search site content'));

	$gdirpath = 'directory';

	if(strlen(get_config('system','singleuser'))) {
		$gdir = dirname(get_config('system','directory_submit_url'));
		if(strlen($gdir))
			$gdirpath = $gdir;
	}
	elseif(! get_config('system','no_community_page'))
		$nav['community'] = array('community', t('Community'), "", t('Conversations on this site'));

	$nav['directory'] = array($gdirpath, t('Directory'), "", t('People directory')); 

	/**
	 *
	 * The following nav links are only show to logged in users
	 *
	 */

	if(local_user()) {

		$nav['network'] = array('network', t('Network'), "", t('Conversations from my friends'));

		$nav['home'] = array('profile/' . $a->user['nickname'], t('Home'), "", t('My posts and conversations'));


		/* only show friend requests for normal pages. Other page types have automatic friendship. */

		if($_SESSION['page_flags'] == PAGE_NORMAL) {
			$nav['notifications'] = array('notifications',	t('Notifications'), "", t('Friend requests'));
		}

		$nav['messages'] = array('message', t('Messages'), "", t('Private mail'));
		
		if(is_array($a->identities) && count($a->identities) > 1) {
			$nav['manage'] = array('manage', t('Manage'), "", t('Manage other pages'));
		}

		$nav['settings'] = array('settings', t('Settings'),"", t('Account settings'));
		$nav['profiles'] = array('profiles', t('Profiles'),"", t('Manage/edit profiles'));
		$nav['contacts'] = array('contacts', t('Contacts'),"", t('Manage/edit friends and contacts'));
	}

	/**
	 * Admin page
	 */
	 if (is_site_admin()){
		 $nav['admin'] = array('admin/', t('Admin'), "", t('Site setup and configuration'));
	 }


	/**
	 *
	 * Provide a banner/logo/whatever
	 *
	 */

	$banner = get_config('system','banner');

	if($banner === false) 
		$banner .= '<a href="http://project.friendika.com"><img id="logo-img" src="images/friendika-32.png" alt="logo" /></a><span id="logo-text"><a href="http://project.friendika.com">Friendika</a></span>';


	$tpl = get_markup_template('nav.tpl');

	$a->page['nav'] .= replace_macros($tpl, array(
		'$langselector' => lang_selector(),
		'$sitelocation' => $sitelocation,
		'$nav' => $nav,
		'$banner' =>  $banner,
	));

	call_hooks('page_header', $a->page['nav']);

}
