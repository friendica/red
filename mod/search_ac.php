<?php

function search_ac_init(&$a){
	if(!local_user())
		return "";


	$start = (x($_REQUEST,'start')?$_REQUEST['start']:0);
	$count = (x($_REQUEST,'count')?$_REQUEST['count']:100);
	$search = (x($_REQUEST,'search')?$_REQUEST['search']:"");

	if(x($_REQUEST,'query') && strlen($_REQUEST['query'])) {
		$search = $_REQUEST['query'];
	}


	$sql_extra = '';

	$x = array();
	$x['query'] = $search;
	$x['photos'] = array();
	$x['links'] = array();
	$x['suggestions'] = array();
	$x['data'] = array();


	// Priority to people searches

	if ($search) {
		$people_sql_extra = protect_sprintf(" AND `xchan_name` LIKE '%". dbesc($search) . "%' ");
		$tag_sql_extra = protect_sprintf(" AND term LIKE '%". dbesc($search) . "%' ");
	}


    $r = q("SELECT abook_id as id, xchan_hash as hash, xchan_name as name, xchan_photo_s as micro, xchan_url as url, xchan_addr as nick, abook_their_perms
		FROM abook left join xchan on abook_xchan = xchan_hash
		WHERE abook_channel = %d AND not ( abook_flags & %d ) $people_sql_extra order by xchan_name asc" ,
		intval(local_user()),
		intval(ABOOK_FLAG_SELF|ABOOK_FLAG_BLOCKED|ABOOK_FLAG_PENDING|ABOOK_FLAG_ARCHIVED)
	);

	if($r) {
		foreach($r as $g) {
			$x['photos'][] = $g['micro'];
			$x['links'][] = $g['url'];
			$x['suggestions'][] = '@' . $g['name'];
			$x['data'][] = intval($g['id']);
		}
	}
	else {

		$r = q("SELECT `id`, `name`, `photo`, `url` FROM `gcontact` where 1
			$people_sql_extra
			ORDER BY `name` ASC "
		);

		if(count($r)) {
			foreach($r as $g) {
				$x['photos'][] = $g['photo'];
				$x['links'][] = $g['url'];
				$x['suggestions'][] = '@' . $g['name'];
				$x['data'][] = intval($g['id']);
			}
		}
	}

	$r = q("select tid, term, url from term where type = %d $tag_sql_extra order by term asc",
		intval(TERM_HASHTAG)
	);

	if(count($r)) {
		foreach($r as $g) {
			$x['photos'][] = $a->get_baseurl() . '/images/hashtag.png';
			$x['links'][] = $g['url'];
			$x['suggestions'][] = '#' . $g['term'];
			$x['data'][] = intval($g['tid']);
		}
	}

	header("content-type: application/json");
	echo json_encode($x);

	logger('search_ac: ' . print_r($x,true));

	killme();
}


