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


	$r = q("SELECT `abook_id`, `xchan_name`, `xchan_photo_s`, `xchan_url` FROM `abook` left join xchan on abook_xchan = xchan_hash WHERE abook_channel = %d 
		$people_sql_extra
		ORDER BY `xchan_name` ASC ",
		intval(local_user())
	);

	if($r) {
		foreach($r as $g) {
			$x['photos'][] = $g['xchan_photo_s'];
			$x['links'][] = $g['xchan_url'];
			$x['suggestions'][] = '@' . $g['xchan_name'];
			$x['data'][] = 'cid=' . intval($g['abook_id']);
		}
	}

	$r = q("select distinct term, tid, url from term where type = %d $tag_sql_extra group by term order by term asc",
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


