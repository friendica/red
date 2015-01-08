<?php

// Autocomplete for saved searches. Should probably be put in the same place as the other autocompletes
function search_ac_init(&$a){
	if(!local_user())
		killme();


	$start = (x($_REQUEST,'start')?$_REQUEST['start']:0);
	$count = (x($_REQUEST,'count')?$_REQUEST['count']:100);
	$search = (x($_REQUEST,'search')?$_REQUEST['search']:"");

	if(x($_REQUEST,'query') && strlen($_REQUEST['query'])) {
		$search = $_REQUEST['query'];
	}

	// Priority to people searches

	if ($search) {
		$people_sql_extra = protect_sprintf(" AND `xchan_name` LIKE '%". dbesc($search) . "%' ");
		$tag_sql_extra = protect_sprintf(" AND term LIKE '%". dbesc($search) . "%' ");
	}


	$r = q("SELECT `abook_id`, `xchan_name`, `xchan_photo_s`, `xchan_url`, `xchan_addr` FROM `abook` left join xchan on abook_xchan = xchan_hash WHERE abook_channel = %d 
		$people_sql_extra
		ORDER BY `xchan_name` ASC ",
		intval(local_user())
	);

	$results = array();
	if($r) {
		foreach($r as $g) {
			$results[] = array(
				"photo"    => $g['xchan_photo_s'],
				"name"     => '@'.$g['xchan_name'],
				"id"       => $g['abook_id'],
				"link"     => $g['xchan_url'],
				"label"    => '',
				"nick"     => '',
			);
		}
	}

	$r = q("select distinct term, tid, url from term where type = %d $tag_sql_extra group by term order by term asc",
		intval(TERM_HASHTAG)
	);

	if(count($r)) {
		foreach($r as $g) {
			$results[] = array(
				"photo"    => $a->get_baseurl() . '/images/hashtag.png',
				"name"     => '#'.$g['term'],
				"id"       => $g['tid'],
				"link"     => $g['url'],
				"label"    => '',
				"nick"     => '',
			);
		}
	}

	header("content-type: application/json");
	$o = array(
		'start' => $start,
		'count'	=> $count,
		'items'	=> $results,
	);
	echo json_encode($o);

	logger('search_ac: ' . print_r($x,true));

	killme();
}


