<?php /** @file */

function sitelist_init(&$a) {

	$start = (($_REQUEST['start']) ? intval($_REQUEST['start']) : 0);
	$limit = ((intval($_REQUEST['limit'])) ? intval($_REQUEST['limit']) : 30);
	$order = (($_REQUEST['order']) ? $_REQUEST['order'] : 'random');
	$open = (($_REQUEST['open']) ? intval($_REQUEST['open']) : false);


	$sql_order = " order by site_url ";
	if($order == 'random')
		$sql_order = " order by rand() ";

	$sql_limit = " limit $start, $limit ";

	$sql_extra = "";
	if($open)
		$sql_extra = " and site_register = " . intval(REGISTER_OPEN) . " ";


	$result = array('success' => false);

	$r = q("select count(site_url) as total from site where 1 $sql_extra ");
	
	if($r)
		$result['total'] = intval($r[0]['total']);

	$result['start'] = $start;
	$result['limit'] = $limit;	

	$r = q("select * from site where true $sql_extra $sql_order $sql_limit");

	$result['results'] = 0;
	$result['entries'] = array();

	if($r) {
		$result['success'] = true;		
		$result['results'] = count($r);
		
		foreach($r as $rr) {
			$result['entries'][] = array('url' => $rr['site_url']);
		}

	}

	echo json_encode($result);
	killme();
			

}