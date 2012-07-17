<?php


function network_query($a,$arr) {


	$parent_options = '';
	$child_options = '';

	$ordering = (($arr['order'] === 'post') ? "`created`" ? "`commented`") . " DESC";

	$itemspage = get_pconfig($arr['uid'],'system','itemspage_network');
	$a->set_pager_itemspage(((intval($itemspage_network)) ? $itemspage_network : 40));

	$pager_sql = ((intval($arr['update'])) ? '' : sprintf(" LIMIT %d, %d ",intval($a->pager['start']), intval($a->pager['itemspage'])));  

	$arr['cmin'] = ((x($arr,'cmin')) ? $arr['cmin'] : 0);
	$arr['cmax'] = ((x($arr,'cmax')) ? $arr['cmax'] : 0);

	$simple_update = (($arr['update']) ? " and `item`.`unseen` = 1 " : '');

	if($arr['new']) {

		// "New Item View" - show all items unthreaded in reverse created date order

		$items = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
			`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`, `contact`.`writable`,
			`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
			`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
			FROM `item`, `contact`
			WHERE `item`.`uid` = %d AND `item`.`visible` = 1 
			AND `item`.`deleted` = 0 and `item`.`moderated` = 0
			$simple_update
			AND `contact`.`closeness` >= %d and `contact`.`closeness` <= %d
			AND `contact`.`id` = `item`.`contact-id`
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			$sql_extra $sql_nets
			ORDER BY `item`.`received` DESC $pager_sql ",
			intval($arr['uid']),
			intval($arr['cmin']),
			intval($arr['cmax'])

		);

		$items = fetch_post_tags($items);
		return $items;

	}
	if($update) {
		$r = q("SELECT `parent` AS `item_id`, `contact`.`uid` AS `contact_uid`
			FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
			WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND
			`contact`.`closeness` >= %d and `contact`.`closeness` <= %d
			(`item`.`deleted` = 0 OR item.verb = '" . ACTIVITY_LIKE ."' OR item.verb = '" . ACTIVITY_DISLIKE . "')
			and `item`.`moderated` = 0 $simple_update
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			$sql_extra3 $sql_extra $sql_nets ",
			intval($arr['uid']),
			intval($arr['cmin']),
			intval($arr['cmax'])
		);
	}
	else {
		$r = q("SELECT `item`.`id` AS `item_id`, `contact`.`uid` AS `contact_uid`
			FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
			WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
			AND `item`.`moderated` = 0 AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			AND `contact`.`closeness` >= %d and `contact`.`closeness` <= %d
			AND `item`.`parent` = `item`.`id`
			$sql_extra3 $sql_extra $sql_nets
			ORDER BY `item`.$ordering $pager_sql ",
			intval($arr['uid']),
			intval($arr['cmin']),
			intval($arr['cmax'])
		);
	}

	// Then fetch all the children of the parents that are on this page

	$parents_arr = array();
	$parents_str = '';

	if(count($r)) {
		foreach($r as $rr)
			if(! in_array($rr['item_id'],$parents_arr))
				$parents_arr[] = $rr['item_id'];
		$parents_str = implode(', ', $parents_arr);

		$items = q("SELECT `item`.*, `item`.`id` AS `item_id`,
			`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`alias`, 
			`contact`.`rel`, `contact`.`writable`,
			`contact`.`network`, `contact`.`thumb`, `contact`.`self`,
			`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
			FROM `item`, `contact`
			WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
			AND `item`.`moderated` = 0 AND `contact`.`id` = `item`.`contact-id`
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			AND `item`.`parent` IN ( %s )
			$sql_extra ",
			intval($arr['uid']),
			dbesc($parents_str)
		);

		$items = fetch_post_tags($items);

		$items = conv_sort($items,$ordering);
	} 
	else {
		$items = array();
	}

	return $items;
}


























}