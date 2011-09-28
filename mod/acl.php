<?php
/* ACL selector json backend */
require_once("include/acl_selectors.php");

function acl_init(&$a){
	if(!local_user())
		return "";


	$start = (x($_POST,'start')?$_POST['start']:0);
	$count = (x($_POST,'count')?$_POST['count']:100);
	$search = (x($_POST,'search')?$_POST['search']:"");

	if ($search!=""){
		$sql_extra = "AND `name` LIKE '%%".dbesc($search)."%%'";
	}
	
	// count groups and contacts
	$r = q("SELECT COUNT(`id`) AS g FROM `group` WHERE `deleted` = 0 AND `uid` = %d $sql_extra",
		intval(local_user())
	);
	$group_count = (int)$r[0]['g'];
	$r = q("SELECT COUNT(`id`) AS c FROM `contact` 
			WHERE `uid` = %d AND `self` = 0 
			  AND `blocked` = 0 AND `pending` = 0 
			  AND `notify` != '' $sql_extra" ,
		intval(local_user())
	);
	$contact_count = (int)$r[0]['c'];
	
	$tot = $group_count+$contact_count;
	
	$groups = array();
	$contacts = array();
	
	$r = q("SELECT `group`.`id`, `group`.`name`, GROUP_CONCAT(DISTINCT `group_member`.`contact-id` SEPARATOR ',') as uids
			FROM `group`,`group_member` 
			WHERE `group`.`deleted` = 0 AND `group`.`uid` = %d 
				AND `group_member`.`gid`=`group`.`id`
				$sql_extra
			GROUP BY `group`.`id`
			ORDER BY `group`.`name` 
			LIMIT %d,%d",
		intval(local_user()),
		intval($start),
		intval($count)
	);

	
	foreach($r as $g){
		$groups[] = array(
			"type"  => "g",
			"photo" => "images/default-group-mm.png",
			"name"  => $g['name'],
			"id"	=> intval($g['id']),
			"uids"  => array_map("intval", explode(",",$g['uids'])),
			"link"  => ''
		);
	}
	
	
	$r = q("SELECT `id`, `name`, `micro`, `network`, `url` FROM `contact` 
		WHERE `uid` = %d AND `self` = 0 AND `blocked` = 0 AND `pending` = 0 AND `notify` != ''
		$sql_extra
		ORDER BY `name` ASC ",
		intval(local_user())
	);
	foreach($r as $g){
		$contacts[] = array(
			"type"  => "c",
			"photo" => $g['micro'],
			"name"  => $g['name'],
			"id"	=> intval($g['id']),
			"network" => $g['network'],
			"link" => $g['url'],
		);
	}
		
	
	
	
	$items = array_merge($groups, $contacts);
	
	$o = array(
		'tot'	=> $tot,
		'start' => $start,
		'count'	=> $count,
		'items'	=> $items,
	);
	
	echo json_encode($o);

	killme();
}


