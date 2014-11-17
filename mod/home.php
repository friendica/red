<?php

require_once('include/items.php');
require_once('include/conversation.php');


function home_init(&$a) {

	$ret = array();
	call_hooks('home_init',$ret);

	$splash = ((argc() > 1 && argv(1) === 'splash') ? true : false);

	$channel = $a->get_channel();
	if(local_user() && $channel && $channel['xchan_url'] && ! $splash) {
		$dest = $channel['channel_startpage'];
		if(! $dest)
			$dest = get_pconfig(local_user(),'system','startpage');
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


function home_content(&$a) {

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

		// If there's nothing special happening, just spit out a login box

		$sitename = get_config('system','sitename');
		if($sitename) 
			$o .= '<h1>' . sprintf( t("Welcome to %s") ,$sitename) . '</h1>';
		if (! $a->config['system']['no_login_on_homepage'])
			$o .= login(($a->config['system']['register_policy'] == REGISTER_CLOSED) ? 0 : 1);
	}
	
	call_hooks('home_content',$o);
	return $o;	
}
