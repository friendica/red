<?php

if(! function_exists('profile_load')) {
function profile_load(&$a,$uid,$profile = 0) {

	$sql_extra = (($uid) && (intval($uid)) 
		? " WHERE `user`.`uid` = " . intval($uid) 
		: " WHERE `user`.`nickname` = '" . dbesc($uid) . "' " ); 

	if(remote_user()) {
		$r = q("SELECT `profile-id` FROM `contact` WHERE `id` = %d LIMIT 1",
			intval($_SESSION['visitor_id']));
		if(count($r))
			$profile = $r[0]['profile-id'];
	} 

	if($profile) {
		$profile_int = intval($profile);
		$sql_which = " AND `profile`.`id` = $profile_int ";
	}
	else
		$sql_which = " AND `profile`.`is-default` = 1 "; 

	$r = q("SELECT `profile`.`uid` AS `profile_uid`, `profile`.* , `user`.* FROM `profile` 
		LEFT JOIN `user` ON `profile`.`uid` = `user`.`uid`
		$sql_extra $sql_which LIMIT 1"
	);

	if(($r === false) || (! count($r))) {
		$_SESSION['sysmsg'] .= "No profile" . EOL ;
		$a->error = 404;
		return;
	}

	$a->profile = $r[0];

	$a->page['template'] = 'profile';

	$a->page['title'] = $a->profile['name'];

	return;
}}

function profile_init(&$a) {

	if($_SESSION['authenticated']) {

		// choose which page to show (could be remote auth)

	}

	if($a->argc > 1)
		$which = $a->argv[1];
	else {
		$_SESSION['sysmsg'] .= "No profile" . EOL ;
		$a->error = 404;
		return;
	}

	profile_load($a,$which);
	$a->page['htmlhead'] .= "<meta name=\"dfrn-template\" content=\"" . $a->get_baseurl() . "/profile/%s" . "\" />\r\n";
	
	$dfrn_pages = array('request', 'confirm', 'notify', 'poll');
	foreach($dfrn_pages as $dfrn)
		$a->page['htmlhead'] .= "<link rel=\"dfrn-{$dfrn}\" href=\"".$a->get_baseurl()."/dfrn_{$dfrn}/{$which}\" />\r\n";

}

function item_display(&$a, $item,$template,$comment) {


	$profile_url = $item['url'];

	if(local_user() && ($item['contact-uid'] == $_SESSION['uid']) && (strlen($item['dfrn-id'])) && (! $item['self'] ))
		$profile_url = $a->get_baseurl() . '/redir/' . $item['cid'] ;

	$photo = (($item['self']) ? $a->profile['photo'] : $item['photo']);
	$thumb = (($item['self']) ? $a->profile['thumb'] : $item['thumb']);

	$o .= replace_macros($template,array(
		'$id' => $item['item_id'],
		'$profile_url' => $profile_url,
		'$name' => $item['name'],
		'$thumb' => $thumb,
		'$body' => bbcode($item['body']),
		'$ago' => relative_date($item['created']),
		'$indent' => (($item['parent'] != $item['item_id']) ? 'comment-' : ''),
		'$comment' => $comment
	));


	return $o;
}


function profile_content(&$a) {

	require_once("include/bbcode.php");
	require_once('include/security.php');

	$a->page['htmlhead'] .= '<script type="text/javascript" src="include/jquery.js" ></script>';
	$groups = array();

	$tab = 'posts';

	if(x($_GET,'tab'))
		$tab = notags(trim($_GET['tab']));

	$tpl = file_get_contents('view/profile_tabs.tpl');

	$o .= replace_macros($tpl,array(
		'$url' => $a->get_baseurl() . '/' . $a->cmd
	));


	if(remote_user()) {
		$contact_id = $_SESSION['visitor_id'];
		$groups = init_groups_visitor($contact_id);
	}
	if(local_user()) {
		$r = q("SELECT `id` FROM `contact` WHERE `uid` = %d AND `self` = 1 LIMIT 1",
			$_SESSION['uid']
		);
		if(count($r))
			$contact_id = $r[0]['id'];
	}

	if($tab == 'profile') {

		require_once('view/profile_advanced.php');

		return $o;
	}
	if(can_write_wall($a,$a->profile['profile_uid'])) {
		$tpl = file_get_contents('view/jot-header.tpl');
	
		$a->page['htmlhead'] .= replace_macros($tpl, array('$baseurl' => $a->get_baseurl()));

		$tpl = file_get_contents("view/jot.tpl");
		$o .= replace_macros($tpl,array(
			'$baseurl' => $a->get_baseurl(),
			'$profile_uid' => $a->profile['profile_uid']
		));
	}


	// TODO 
	// Alter registration and settings 
	// and profile to update contact table when names and  photos change.  
	// work on item_display and can_write_wall

	// Add comments. 

	// default - anonymous user

	$sql_extra = " AND `allow_cid` = '' AND `allow_gid` = '' AND `deny_cid` = '' AND `deny_gid` = '' ";

	// Profile owner - everything is visible

	if(local_user() && ($_SESSION['uid'] == $a->profile['profile_uid']))
		$sql_extra = ''; 

	// authenticated visitor - here lie dragons

	elseif(remote_user()) {
		$gs = '<<>>'; // should be impossible to match
		if(count($groups)) {
			foreach($groups as $g)
				$gs .= '|<' . dbesc($g) . '>';
		} 
		$sql_extra = sprintf(
			" AND ( `allow_cid` = '' OR `allow_cid` REGEXP '<%d>' ) 
			AND ( `deny_cid` = '' OR  NOT `deny_cid` REGEXP '<%d>' ) 
			AND ( `allow_gid` = '' OR `allow_gid` REGEXP '%s' )
			AND ( `deny_gid` = '' OR NOT `deny_gid` REGEXP '%s') ",

			intval($visitor_id),
			intval($visitor_id),
			$gs,
			$gs
		);
	}

	$r = q("SELECT COUNT(*) AS `total`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
		AND `contact`.`blocked` = 0 
		$sql_extra ",
		intval($a->profile['uid'])

	);

	if(count($r))
		$a->set_pager_total($r[0]['total']);


	$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
		`contact`.`name`, `contact`.`photo`, `contact`.`url`, 
		`contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`, 
		`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
		AND `contact`.`blocked` = 0 
		$sql_extra
		ORDER BY `parent` DESC, `id` ASC LIMIT %d ,%d ",
		intval($a->profile['uid']),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])

	);


	$template = file_get_contents('view/comment_item.tpl');


	$tpl = file_get_contents('view/wall_item.tpl');
	if(count($r)) {
		foreach($r as $rr) {
			$comment = '';
			if(can_write_wall($a,$a->profile['profile_uid'])) {
				if($rr['last-child']) {
					$comment = replace_macros($template,array(
						'$id' => $rr['item_id'],
						'$parent' => $rr['parent'],
						'$profile_uid' =>  $a->profile['profile_uid']
					));
				}
			}
			$o .= item_display($a,$rr,$tpl,$comment);
		}
	}

	$o .= paginate($a);

	return $o;


}