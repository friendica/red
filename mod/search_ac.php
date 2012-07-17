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
		$people_sql_extra = protect_sprintf(" AND `name` LIKE '%". dbesc($search) . "%' ");
		$tag_sql_extra = protect_sprintf(" AND term LIKE '%". dbesc($search) . "%' ");
	}

	$r = q("SELECT `id`, `name`, `micro`, `url` FROM `contact` 
		WHERE `uid` = %d AND `pending` = 0
		$people_sql_extra
		ORDER BY `name` ASC ",
		intval(local_user())
	);

	if(count($r)) {
		foreach($r as $g) {
			$x['photos'][] = $g['micro'];
			$x['links'][] = $g['url'];
			$x['suggestions'][] = '@' . $g['name'];
			$x['data'][] = intval($g['id']);
		}
	}

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


