<?php


function poco_init(&$a) {

	if($a->argc > 1) {
		$user = notags(trim($a->argv[1]));
	}
	if(! x($user) || get_config('system','block_public'))
		killme();

	$justme = false;

	if($a->argc > 2 && $a->argv[2] === '@me')
		$justme = true;
	if($a->argc > 3 && $a->argv[3] === '@all')
		$justme = false;
	if($a->argc > 3 && $a->argv[3] === '@self')
		$justme = true;
	if($a->argc > 4 && intval($a->argv[4]) && $justme == false)
		$cid = intval($a->argv[4]);
 		

	$r = q("SELECT `user`.*,`profile`.`hide-friends` from user left join profile on `user`.`uid` = `profile`.`uid`
		where `user`.`nickname` = '%s' and `profile`.`is-default` = 1 limit 1",
		dbesc($user)
	);
	if(! count($r) || $r[0]['hidewall'] || $r[0]['hide-friends'])
		killme();

	$user = $r[0];

	if($justme)
		$sql_extra = " and `contact`.`self` = 1 ";

	if($cid)
		$sql_extra = sprintf(" and `contact`.`id` = %d ",intval($cid));

	$r = q("SELECT count(*) as `total` from `contact` where `uid` = %d and blocked = 0 and pending = 0
		$sql_extra ",
		intval($user['uid'])
	);
	if(count($r))
		$totalResults = intval($r[0]['total']);
	else
		$totalResults = 0;

	$startIndex = intval($_GET['startIndex']);
	if(! $startIndex)
		$startIndex = 0;
	$itemsPerPage = ((x($_GET,'count') && intval($_GET['count'])) ? intval($_GET['count']) : $totalResults);

	$r = q("SELECT * from `contact` where `uid` = %d and blocked = 0 and pending = 0
		$sql_extra LIMIT %d, %d",
		intval($user['uid']),
		intval($startIndex),
		intval($itemsPerPage)
	);

	$ret = array(
		'startIndex' => $startIndex,
		'itemsPerPage' => $itemsPerPage,
		'totalResults' => $totalResults,
		'entry' => array()
	);

	if(count($r)) {
		foreach($r as $rr) {
			$entry = array();
			$entry['id'] = $rr['id'];
			$entry['displayName'] = $rr['name'];
			$entry['urls'] = array('value' => $rr['url'], 'type' => 'profile');
			$entry['preferredUsername'] = $rr['nick'];
			$entry['photos'] = array('value' => $rr['photo'], 'type' => 'profile');
			$ret['entry'][] = $entry;
		}
	}
	header('Content-type: application/json');
	echo json_encode($ret);
	killme();	



}