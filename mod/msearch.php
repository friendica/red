<?php

function msearch_post(&$a) {

	$perpage = (($_POST['n']) ? $_POST['n'] : 80);
	$page = (($_POST['p']) ? intval($_POST['p'] - 1) : 0);
	$startrec = (($page+1) * $perpage) - $perpage;

	$search = $_POST['s'];
	if(! strlen($search))
		killme();

	$r = q("SELECT COUNT(*) AS `total` FROM `profile` LEFT JOIN `user` ON `user`.`uid` = `profile`.`uid` WHERE `is_default` = 1 AND `user`.`hidewall` = 0 AND MATCH `keywords` AGAINST ('%s') ",
		dbesc($search)
	);
	if(count($r))
		$total = $r[0]['total'];

	$r = q("SELECT `keywords`, `username`, `nickname`, `user`.`uid` FROM `user` LEFT JOIN `profile` ON `user`.`uid` = `profile`.`uid` WHERE `is_default` = 1 AND `user`.`hidewall` = 0 AND MATCH `keywords` AGAINST ('%s') LIMIT %d , %d ",
		dbesc($search),
		intval($startrec),
		intval($perpage)
	);

	$results = array();
	if(count($r)) {
		foreach($r as $rr)
			$results[] = array(
				'name' => $rr['name'], 
				'url' => $a->get_baseurl() . '/channel/' . $rr['nickname'], 
				'photo' => $a->get_baseurl() . '/photo/avatar/' . $rr['uid'],
				'tags' => str_replace(array(',','  '),array(' ',' '),$rr['keywords'])
			);
	}

	$output = array('total' => $total, 'items_page' => $perpage, 'page' => $page + 1, 'results' => $results);

	echo json_encode($output);

	killme();

}