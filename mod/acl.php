<?php
/* ACL selector json backend */

require_once("include/acl_selectors.php");

function acl_init(&$a){

//	logger('mod_acl: ' . print_r($_REQUEST,true));

	$start = (x($_REQUEST,'start')?$_REQUEST['start']:0);
	$count = (x($_REQUEST,'count')?$_REQUEST['count']:100);
	$search = (x($_REQUEST,'search')?$_REQUEST['search']:"");
	$type = (x($_REQUEST,'type')?$_REQUEST['type']:"");
	

	// For use with jquery.autocomplete for private mail completion

	if(x($_REQUEST,'query') && strlen($_REQUEST['query'])) {
		if(! $type)
			$type = 'm';
		$search = $_REQUEST['query'];
	}


	if(!(local_user()))
		if($type != 'x')
			killme();

	if ($search != "") {
		$sql_extra = " AND `name` LIKE " . protect_sprintf( "'%" . dbesc($search) . "%'" ) . " ";
		$sql_extra2 = "AND ( xchan_name LIKE " . protect_sprintf( "'%" . dbesc($search) . "%'" ) . " OR xchan_addr LIKE " . protect_sprintf( "'%" . dbesc($search) . "%'" ) . ") ";

		$col = ((strpos($search,'@') !== false) ? 'xchan_addr' : 'xchan_name' );
		$sql_extra3 = "AND $col like " . protect_sprintf( "'%" . dbesc($search) . "%'" ) . " ";

	} else {
		$sql_extra = $sql_extra2 = $sql_extra3 = "";
	}
	
	// count groups and contacts
	if ($type=='' || $type=='g'){
		$r = q("SELECT COUNT(`id`) AS g FROM `groups` WHERE `deleted` = 0 AND `uid` = %d $sql_extra",
			intval(local_user())
		);
		$group_count = (int)$r[0]['g'];
	} else {
		$group_count = 0;
	}
	
	if ($type=='' || $type=='c'){
		$r = q("SELECT COUNT(abook_id) AS c FROM abook left join xchan on abook_xchan = xchan_hash 
				WHERE abook_channel = %d AND not ( abook_flags & %d ) and not (xchan_flags & %d ) $sql_extra2" ,
			intval(local_user()),
			intval(ABOOK_FLAG_BLOCKED|ABOOK_FLAG_PENDING|ABOOK_FLAG_ARCHIVED),
			intval(XCHAN_FLAGS_DELETED)
		);
		$contact_count = (int)$r[0]['c'];

		if(intval(get_config('system','taganyone')) || intval(get_pconfig(local_user(),'system','taganyone'))) {
			if(((! $r) || (! $r[0]['total'])) && $type == 'c') {
				$r = q("SELECT COUNT(xchan_hash) AS c FROM xchan 
					WHERE not (xchan_flags & %d ) $sql_extra2" ,
					intval(XCHAN_FLAGS_DELETED)
				);
				$contact_count = (int)$r[0]['c'];
			}
		}

	} 

	elseif ($type == 'm') {

		// autocomplete for Private Messages


		$r = q("SELECT count(xchan_hash) as c
			FROM abook left join xchan on abook_xchan = xchan_hash
			WHERE abook_channel = %d and ( (abook_their_perms = null) or (abook_their_perms & %d ))
			and not ( xchan_flags & %d )
			$sql_extra2 ",
			intval(local_user()),
			intval(PERMS_W_MAIL),
			intval(XCHAN_FLAGS_DELETED)
		);

		if($r)
			$contact_count = (int)$r[0]['c'];

	}
	elseif ($type == 'a') {

		// autocomplete for Contacts

		$r = q("SELECT COUNT(abook_id) AS c FROM abook left join xchan on abook_xchan = xchan_hash 
				WHERE abook_channel = %d and not ( xchan_flags & %d ) $sql_extra2" ,
			intval(local_user()),
			intval(XCHAN_FLAGS_DELETED)
		);
		$contact_count = (int)$r[0]['c'];

	} else {
		$contact_count = 0;
	}
	
	$tot = $group_count+$contact_count;
	
	$groups = array();
	$contacts = array();
	
	if ($type=='' || $type=='g'){
		
		$r = q("SELECT `groups`.`id`, `groups`.`hash`, `groups`.`name`, 
				GROUP_CONCAT(DISTINCT `group_member`.`xchan` SEPARATOR ',') as uids
				FROM `groups`,`group_member` 
				WHERE `groups`.`deleted` = 0 AND `groups`.`uid` = %d 
					AND `group_member`.`gid`=`groups`.`id`
					$sql_extra
				GROUP BY `groups`.`id`
				ORDER BY `groups`.`name` 
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
				"id"	=> $g['id'],
				"xid"   => $g['hash'],
				"uids"  => explode(",",$g['uids']),
				"link"  => ''
			);
		}
	}

	if ($type=='' || $type=='c') {
		$r = q("SELECT abook_id as id, xchan_hash as hash, xchan_name as name, xchan_photo_s as micro, xchan_url as url, xchan_addr as nick, abook_their_perms, abook_flags 
				FROM abook left join xchan on abook_xchan = xchan_hash 
				WHERE abook_channel = %d AND not ( abook_flags & %d ) and not (xchan_flags & %d ) $sql_extra2 order by xchan_name asc" ,
			intval(local_user()),
			intval(ABOOK_FLAG_BLOCKED|ABOOK_FLAG_PENDING|ABOOK_FLAG_ARCHIVED),
			intval(XCHAN_FLAGS_DELETED)
		);
		if(intval(get_config('system','taganyone')) || intval(get_pconfig(local_user(),'system','taganyone'))) {
			if((! $r) && $type == 'c') {
				$r = q("SELECT substr(xchan_hash,1,18) as id, xchan_hash as hash, xchan_name as name, xchan_photo_s as micro, xchan_url as url, xchan_addr as nick, 0 as abook_their_perms, 0 as abook_flags 
					FROM xchan 
					WHERE not (xchan_flags & %d ) $sql_extra2 order by xchan_name asc" ,
					intval(XCHAN_FLAGS_DELETED)
				);
			}
		}
	}
	elseif($type == 'm') {

		$r = q("SELECT xchan_hash as id, xchan_name as name, xchan_addr as nick, xchan_photo_s as micro, xchan_url as url 
			FROM abook left join xchan on abook_xchan = xchan_hash
			WHERE abook_channel = %d and ( (abook_their_perms = null) or (abook_their_perms & %d ))
			and not (xchan_flags & %d)
			$sql_extra3
			ORDER BY `xchan_name` ASC ",
			intval(local_user()),
			intval(PERMS_W_MAIL),
			intval(XCHAN_FLAGS_DELETED)
		);
	}
	elseif($type == 'a') {
		$r = q("SELECT abook_id as id, xchan_name as name, xchan_hash as hash, xchan_addr as nick, xchan_photo_s as micro, xchan_network as network, xchan_url as url, xchan_addr as attag , abook_their_perms FROM abook left join xchan on abook_xchan = xchan_hash
			WHERE abook_channel = %d
			and not (xchan_flags & %d)
			$sql_extra3
			ORDER BY xchan_name ASC ",
			intval(local_user()),
			intval(XCHAN_FLAGS_DELETED)

		);
	}
	elseif($type == 'x') {

		$r = navbar_complete($a);
		$x = array();
		$x['query']       = $search;
		$x['photos']      = array();
		$x['links']       = array();
		$x['suggestions'] = array();
		$x['data']        = array();
		if($r) {
			foreach($r as $g) {
				$x['photos'][]      = $g['photo'];
				$x['links'][]       = $g['url'];
				$x['suggestions'][] = '@' .  $g['name'];
				$x['data'][]        = $g['name'];
			}
		}
		echo json_encode($x);
		killme();

	}
	else
		$r = array();


	if($type == 'm' || $type == 'a') {
		$x = array();
		$x['query']       = $search;
		$x['photos']      = array();
		$x['links']       = array();
		$x['suggestions'] = array();
		$x['data']        = array();
		if(count($r)) {
			foreach($r as $g) {
				$x['photos'][]      = $g['micro'];
				$x['links'][]       = $g['url'];
				$x['suggestions'][] = $g['name'];
				$x['data'][]        = $g['id'];
			}
		}
		echo json_encode($x);
		killme();
	}

	if(count($r)) {
		foreach($r as $g){

			// remove RSS feeds from ACLs - they are inaccessible
			if(strpos($g['hash'],'/'))
				continue;

			if(($g['abook_their_perms'] & PERMS_W_TAGWALL) && $type == 'c') {
				$contacts[] = array(
					"type"     => "c",
					"photo"    => "images/twopeople.png",
					"name"     => $g['name'] . '+',
					"id"	   => $g['id'] . '+',
					"xid"      => $g['hash'],
					"link"     => $g['nick'],
					"nick"     => substr($g['nick'],0,strpos($g['nick'],'@')),
					"self"     => (($g['abook_flags'] & ABOOK_FLAG_SELF) ? 'abook-self' : ''),
					"taggable" => 'taggable',
					"label"    => t('network')
				);
			}
			$contacts[] = array(
				"type"     => "c",
				"photo"    => $g['micro'],
				"name"     => $g['name'],
				"id"	   => $g['id'],
				"xid"      => $g['hash'],
				"link"     => $g['nick'],
				"nick"     => substr($g['nick'],0,strpos($g['nick'],'@')),
				"self"     => (($g['abook_flags'] & ABOOK_FLAG_SELF) ? 'abook-self' : ''),
				"taggable" => '',
				"label"    => '',
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


function navbar_complete(&$a) {

//	logger('navbar_complete');

	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		return;
	}

	$dirmode = intval(get_config('system','directory_mode'));
	$search = ((x($_REQUEST,'query')) ? htmlentities($_REQUEST['query'],ENT_COMPAT,'UTF-8',false) : '');
	if(! $search || mb_strlen($search) < 2)
		return array();

	$star = false;
	$address = false;

	if(substr($search,0,1) === '@')
		$search = substr($search,1);

	if(substr($search,0,1) === '*') {
		$star = true;
		$search = substr($search,1);
	}

	if(strpos($search,'@') !== false) {
		$address = true;
	}

	if(($dirmode == DIRECTORY_MODE_PRIMARY) || ($dirmode == DIRECTORY_MODE_STANDALONE)) {
		$url = z_root() . '/dirsearch';
	}

	if(! $url) {
		require_once("include/dir_fns.php");
		$directory = find_upstream_directory($dirmode);
		$url = $directory['url'] . '/dirsearch';
	}

	if($url) {
		$query = $url . '?f=' ;
		$query .= '&name=' . urlencode($search) . '&limit=50' . (($address) ? '&address=' . urlencode($search) : '');

		$x = z_fetch_url($query);
		if($x['success']) {
			$t = 0;
			$j = json_decode($x['body'],true);
			if($j && $j['results']) {
				return $j['results'];
			}
		}
	}
	return array();
}
