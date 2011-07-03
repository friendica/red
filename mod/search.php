<?php


function search_post(&$a) {
	if(x($_POST,'search'))
		$a->data['search'] = $_POST['search'];
}


function search_content(&$a) {

	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		notice( t('Public access denied.') . EOL);
		return;
	}

	require_once("include/bbcode.php");
	require_once('include/security.php');
	require_once('include/conversation.php');

	if(x($_SESSION,'theme'))
		unset($_SESSION['theme']);

	$o = '<div id="live-search"></div>' . "\r\n";

	$o .= '<h3>' . t('Search') . '</h3>';

	if(x($a->data,'search'))
		$search = notags(trim($a->data['search']));
	else
		$search = ((x($_GET,'search')) ? notags(trim(rawurldecode($_GET['search']))) : '');

	$o .= search($search);

	if(! $search)
		return $o;

	// Here is the way permissions work in the search module...
	// Only public wall posts can be shown
	// OR your own posts if you are a logged in member

	$s_bool  = "AND MATCH (`item`.`body`) AGAINST ( '%s' IN BOOLEAN MODE )";
	$s_regx  = "AND `item`.`body` REGEXP '%s' ";

	if(mb_strlen($search) >= 3)
		$search_alg = $s_bool;
	else
		$search_alg = $s_regx;

	$r = q("SELECT COUNT(*) AS `total`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id` LEFT JOIN `profile` ON `profile`.`uid` = `item`.`uid`
		WHERE `item`.`visible` = 1 AND `item`.`deleted` = 0
		AND (( `wall` = 1 AND `item`.`allow_cid` = ''  AND `item`.`allow_gid` = '' AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = '' AND `profile`.`hidewall` = 0) 
			OR `item`.`uid` = %d )
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0 AND `profile`.`is-default` = 1
		$search_alg ",
		intval(local_user()),
		dbesc($search)
	);

	if(count($r))
		$a->set_pager_total($r[0]['total']);

	if(! $r[0]['total']) {
		info( t('No results.') . EOL);
		return $o;
	}

	$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
		`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`,
		`contact`.`network`, `contact`.`thumb`, `contact`.`self`, `contact`.`writable`, 
		`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`,
		`user`.`nickname`, `profile`.`hidewall`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		LEFT JOIN `user` ON `user`.`uid` = `item`.`uid`
		LEFT JOIN `profile` ON `profile`.`uid` = `item`.`uid` 
		WHERE `item`.`visible` = 1 AND `item`.`deleted` = 0
		AND (( `wall` = 1 AND `item`.`allow_cid` = ''  AND `item`.`allow_gid` = '' AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = '' AND `profile`.`hidewall` = 0 ) 
			OR `item`.`uid` = %d )
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0 AND `profile`.`is-default` = 1
		$search_alg
		ORDER BY `parent` DESC ",
		intval(local_user()),
		dbesc($search)
	);



	$o .= conversation($a,$r,'search',false);

	$o .= paginate($a);

	return $o;
}

