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
	
	$dfrn_pages = array('request', 'confirm', 'notify', 'poll');
	foreach($dfrn_pages as $dfrn)
		$a->page['htmlhead'] .= "<link rel=\"dfrn-{$dfrn}\" href=\"".$a->get_baseurl()."/dfrn_{$dfrn}/{$which}\" />\r\n";
}

function item_display(&$a, $item,$template,$comment) {


	$profile_url = $item['url'];

	if(local_user() && ($item['contact-uid'] == $_SESSION['uid']) && (strlen($item['dfrn-id'])) && (! $item['self'] ))
		$profile_url = $a->get_baseurl() . '/redir/' . $item['cid'] ;

	$o .= replace_macros($template,array(
		'$id' => $item['item_id'],
		'$profile_url' => $profile_url,
		'$name' => $item['name'],
		'$thumb' => $item['thumb'],
		'$body' => bbcode($item['body']),
		'$ago' => relative_date($item['created']),
		'$comment' => $comment
	));


	return $o;
}



function profile_content(&$a) {

	require_once("include/bbcode.php");
	require_once('include/security.php');

//	$tpl = file_get_contents('view/profile_tabs.tpl');


	if(remote_user())
		$contact_id = $_SESSION['visitor_id'];
	if(local_user()) {
		$r = q("SELECT `id` FROM `contact` WHERE `uid` = %d AND `self` = 1 LIMIT 1",
			$_SESSION['uid']
		);
		if(count($r))
			$contact_id = $r[0]['id'];
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


	if($a->profile['is-default']) {

		// TODO left join with contact which will carry names and photos. (done)Store local users in contact as well as user.(done)
		// Alter registration and settings 
		// and profile to update contact table when names and  photos change.  
		// work on item_display and can_write_wall

		// Add comments. 

		$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, `contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`, `contact`.`id` AS `cid`,
			`contact`.`uid` AS `contact-uid`
			FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
			WHERE `item`.`uid` = %d AND `item`.`visible` = 1
			AND `contact`.`blocked` = 0
			AND `allow_uid` = '' AND `allow_gid` = '' AND `deny_uid` = '' AND `deny_gid` = ''
			GROUP BY `item`.`parent`, `item`.`id`
			ORDER BY `created` DESC LIMIT 0,30 ",
			intval($a->profile['uid'])
		);

		$template = file_get_contents('view/comment_item.tpl');




		$tpl = file_get_contents('view/wall_item.tpl');

		if(count($r)) {
			foreach($r as $rr) {
				$comment = replace_macros($template,array(
					'$id' => $rr['item_id'],
					'$profile_uid' =>  $a->profile['profile_uid']
				));



				$o .= item_display($a,$rr,$tpl,$comment);
			}
		}
	}

	return $o;


}