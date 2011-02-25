<?php

function qsearch_init(&$a) {

	if(! local_user())
		killme();

	$limit = (get_config('system','lsearch_limit') ? intval(get_config('system','lsearch_limit')) : 100);

	$search = ((x($_GET,'s')) ? notags(trim(urldecode($_GET['s']))) : '');

	if(! strlen($search))
		killme();


	if($search)
		$search = dbesc($search);

	$results = array();

	$r = q("SELECT * FROM `group` WHERE `name` REGEXP '$search' AND `deleted` = 0 AND `uid` = %d LIMIT 0, %d ",
		intval(local_user()),
		intval($limit)
	);

	if(count($r)) {

		foreach($r as $rr)
			$results[] = array( t('Group: ') . $rr['name'],'[group]<' . $rr['id'] . '>','');
	}

	$sql_extra = ((strlen($search)) ? " AND (`name` REGEXP '$search' OR `nick` REGEXP '$search') " : "");


	$r = q("SELECT * FROM `contact` WHERE `uid` = %d $sql_extra ORDER BY `name` ASC LIMIT 0, %d ",
		intval(local_user()),
		intval($limit)
	);


	if(count($r)) {

		foreach($r as $rr)
			$results[] = array($rr['name'],$rr['url'],$rr['photo']);
	}

	echo str_replace('\\/','/',json_encode((object) $results));
	killme();
}