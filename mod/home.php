<?php

if(! function_exists('home_init')) {
function home_init(&$a) {

	$ret = array();
	call_hooks('home_init',$ret);

	$channel = $a->get_channel();

	if(local_user() && $channel && $channel['xchan_url']) {
		$dest = get_pconfig(local_user(),'system','startpage');
		if(! $dest)
			$dest = z_root() . '/network';

		goaway($dest);
	}

	if(get_account_id()) {
		goaway(z_root() . '/new_channel');
	}

}}


if(! function_exists('home_content')) {
function home_content(&$a) {

	$o = '';

	if(x($_SESSION,'theme'))
		unset($_SESSION['theme']);
	if(x($_SESSION,'mobile_theme'))
		unset($_SESSION['mobile_theme']);

$channel_address = get_config("system", "site_channel" );
    if ($channel_address){

require_once('include/items.php');
require_once('include/conversation.php');


//We can do better, but until we figure out auto-linkification, let's keep things simple
	$page_id = 'home';

	$u = q("select channel_id from channel where channel_address = '%s' limit 1",
		dbesc($channel_address)
	);

	if(! $u) {
		notice( t('Channel not found.') . EOL);
		return;
	}

	$r = q("select item.* from item left join item_id on item.id = item_id.iid
		where item.uid = %d and sid = '%s' and service = 'WEBPAGE' and 
		item_restrict = %d limit 1",
		intval($u[0]['channel_id']),
		dbesc($page_id),
		intval(ITEM_WEBPAGE)
	);

	if(! $r) {
		notice( t('Item not found.') . EOL);
		return;
	}

	xchan_query($r);
	$r = fetch_post_tags($r,true);
	$a->profile = array('profile_uid' => $u[0]['channel_id']);
	$o .= prepare_page($r[0]);

}

// If there's no site channel specified, fallback to the old behaviour
	else {	$o .= '<h1>' . ((x($a->config,'sitename')) ? sprintf( t("Welcome to %s") ,$a->config['sitename']) : "" ) . '</h1>';
	if(file_exists('home.html'))
 		$o .= file_get_contents('home.html');
}

		$o .= login(($a->config['system']['register_policy'] == REGISTER_CLOSED) ? 0 : 1);
	
	call_hooks("home_content",$o);
	return $o;
}
	
}
