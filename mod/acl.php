<?php
/* ACL selector json backend */
require_once("include/acl_selectors.php");

function acl_init(&$a){
	if(!local_user())
		return "";


	$start = (x($_REQUEST,'start')?$_REQUEST['start']:0);
	$count = (x($_REQUEST,'count')?$_REQUEST['count']:100);
	$search = (x($_REQUEST,'search')?$_REQUEST['search']:"");
	$type = (x($_REQUEST,'type')?$_REQUEST['type']:"");
	

	// For use with jquery.autocomplete for private mail completion

	if(x($_REQUEST,'query') && strlen($_REQUEST['query'])) {
		$type = 'm';
		$search = $_REQUEST['query'];
	}


	if ($search!=""){
		$sql_extra = "AND `name` LIKE '%%".dbesc($search)."%%'";
		$sql_extra2 = "AND (`attag` LIKE '%%".dbesc($search)."%%' OR `name` LIKE '%%".dbesc($search)."%%' OR `nick` LIKE '%%".dbesc($search)."%%')";
	} else {
		$sql_extra = $sql_extra2 = "";
	}
	
	// count groups and contacts
	if ($type=='' || $type=='g'){
		$r = q("SELECT COUNT(`id`) AS g FROM `group` WHERE `deleted` = 0 AND `uid` = %d $sql_extra",
			intval(local_user())
		);
		$group_count = (int)$r[0]['g'];
	} else {
		$group_count = 0;
	}
	
	if ($type=='' || $type=='c'){
		$r = q("SELECT COUNT(`id`) AS c FROM `contact` 
				WHERE `uid` = %d AND `self` = 0 
				AND `blocked` = 0 AND `pending` = 0 AND `archive` = 0
				AND `notify` != '' $sql_extra2" ,
			intval(local_user())
		);
		$contact_count = (int)$r[0]['c'];
	} 
	elseif ($type == 'm') {

		// autocomplete for Private Messages

		$r = q("SELECT COUNT(`id`) AS c FROM `contact` 
				WHERE `uid` = %d AND `self` = 0 
				AND `blocked` = 0 AND `pending` = 0 AND `archive` = 0 
				AND `network` IN ('%s','%s','%s') $sql_extra2" ,
			intval(local_user()),
			dbesc(NETWORK_DFRN),
			dbesc(NETWORK_ZOT),
			dbesc(NETWORK_DIASPORA)
		);
		$contact_count = (int)$r[0]['c'];

	} else {
		$contact_count = 0;
	}
	
	$tot = $group_count+$contact_count;
	
	$groups = array();
	$contacts = array();
	
	if ($type=='' || $type=='g'){
		
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
//		logger('acl: group: ' . $g['name'] . ' members: ' . $g['uids']);		
			$groups[] = array(
				"type"  => "g",
				"photo" => "images/twopeople.png",
				"name"  => $g['name'],
				"id"	=> intval($g['id']),
				"uids"  => array_map("intval", explode(",",$g['uids'])),
				"link"  => ''
			);
		}
	}
	
	if ($type=='' || $type=='c'){
	
		$r = q("SELECT `id`, `name`, `nick`, `micro`, `network`, `url`, `attag` FROM `contact` 
			WHERE `uid` = %d AND `self` = 0 AND `blocked` = 0 AND `pending` = 0 AND `archive` = 0 AND `notify` != ''
			$sql_extra2
			ORDER BY `name` ASC ",
			intval(local_user())
		);
	}
	elseif($type == 'm') {
		$r = q("SELECT `id`, `name`, `nick`, `micro`, `network`, `url`, `attag` FROM `contact` 
			WHERE `uid` = %d AND `self` = 0 AND `blocked` = 0 AND `pending` = 0 AND `archive` = 0
			AND `network` IN ('%s','%s','%s')
			$sql_extra2
			ORDER BY `name` ASC ",
			intval(local_user()),
			dbesc(NETWORK_DFRN),
			dbesc(NETWORK_ZOT),
			dbesc(NETWORK_DIASPORA)
		);
	}
	else
		$r = array();


	if($type == 'm') {
		$x = array();
		$x['query'] = $search;
		$x['suggestions'] = array();
		$x['data'] = array();
		if(count($r)) {
			foreach($r as $g) {
				$x['suggestions'][] = sprintf( t('%s [%s]'),$g['name'],$g['url']);
					// '<img src="' . $g['micro'] . ' height="16" width="16" alt="' . t('Image/photo') . '" />' . 
				$x['data'][] = intval($g['id']);
			}
		}
		echo json_encode($x);
		killme();
	}

	if(count($r)) {
		foreach($r as $g){
			$contacts[] = array(
				"type"  => "c",
				"photo" => $g['micro'],
				"name"  => $g['name'],
				"id"	=> intval($g['id']),
				"network" => $g['network'],
				"link" => $g['url'],
				"nick" => ($g['attag']) ? $g['attag'] : $g['nick'],
			);
		}			
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


