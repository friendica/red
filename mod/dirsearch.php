<?php

require_once('include/dir_fns.php');


function dirsearch_init(&$a) {
	$a->set_pager_itemspage(60);

}

function dirsearch_content(&$a) {

	$ret = array('success' => false);

	// If you've got a public directory server, you probably shouldn't block public access

	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		$ret['message'] = t('Public access denied.');
		return;
	}

	$dirmode = intval(get_config('system','directory_mode'));

	if($dirmode == DIRECTORY_MODE_NORMAL) {
		$ret['message'] = t('This site is not a directory server');
		return;
	}

	$name = ((x($_REQUEST,'name')) ? $_REQUEST['name'] : '');
	$address = ((x($_REQUEST,'address')) ? $_REQUEST['address'] : '');
	$locale = ((x($_REQUEST,'locale')) ? $_REQUEST['locale'] : '');
	$region = ((x($_REQUEST,'region')) ? $_REQUEST['region'] : '');
	$postcode = ((x($_REQUEST,'postcode')) ? $_REQUEST['postcode'] : '');
	$country = ((x($_REQUEST,'country')) ? $_REQUEST['country'] : '');
	$gender = ((x($_REQUEST,'gender')) ? $_REQUEST['gender'] : '');
	$marital = ((x($_REQUEST,'marital')) ? $_REQUEST['marital'] : '');
	$keywords = ((x($_REQUEST,'keywords')) ? $_REQUEST['keywords'] : '');

	$sql_extra = '';

	if($name)
		$sql_extra .= " AND xchan_name like '" . protect_sprintf( '%' . dbesc($name) . '%' ) . "' ";
	if($addr)
		$sql_extra .= " AND xchan_addr like '" . protect_sprintf( '%' . dbesc($addr) . '%' ) . "' ";
	if($city)
		$sql_extra .= " AND xprof_locale like '" . protect_sprintf( '%' . dbesc($city) . '%' ) . "' ";
	if($region)
		$sql_extra .= " AND xprof_region like '" . protect_sprintf( '%' . dbesc($region) . '%' ) . "' ";
	if($post)
		$sql_extra .= " AND xprof_postcode like '" . protect_sprintf( '%' . dbesc($post) . '%' ) . "' ";
	if($country)
		$sql_extra .= " AND xprof_country like '" . protect_sprintf( '%' . dbesc($country) . '%' ) . "' ";
	if($gender)
		$sql_extra .= " AND xprof_gender like '" . protect_sprintf( '%' . dbesc($gender) . '%' ) . "' ";
	if($marital)
		$sql_extra .= " AND xprof_marital like '" . protect_sprintf( '%' . dbesc($marital) . '%' ) . "' ";
	if($keywords)
		$sql_extra .= " AND xprof_keywords like '" . protect_sprintf( '%' . dbesc($keywords) . '%' ) . "' ";

    $perpage = (($_REQUEST['n']) ? $_REQUEST['n'] : 80);
    $page = (($_REQUEST['p']) ? intval($_REQUEST['p'] - 1) : 0);
    $startrec = (($page+1) * $perpage) - $perpage;

	// ok a separate tag table won't work. 
	// merge them into xprof

	$ret['success'] = true;

	$r = q("SELECT COUNT(xchan_hash) AS `total` FROM xchan left join xprof on xchan_hash = xprof_hash where 1 $sql_extra");
	if($r) {
		$ret['total_items'] = $r[0]['total'];
	}

	$order = " ORDER BY `xchan_name` ASC "; 

	$r = q("SELECT xchan.*, xprof.* from xchan left join xprof on xchan_hash = xprof_hash where 1 $sql_extra $order LIMIT %d , %d ",
		intval($startrec),
		intval($perpage)
	);

	$ret['page'] = $page + 1;
	$ret['records'] = count($r);		

	if($r) {

		$entries = array();


		foreach($r as $rr) {
			$entry = array();

			$entry['name']        = $rr['xchan_name'];
			$entry['url']         = $rr['xchan_url'];
			$entry['photo']       = $rr['xchan_photo_m'];
			$entry['address']     = $rr['xchan_addr'];
			$entry['description'] = $rr['xprof_desc'];
			$entry['locale']      = $rr['xprof_locale'];
			$entry['region']      = $rr['xprof_region'];
			$entry['postcode']    = $rr['xprof_postcode'];
			$entry['country']     = $rr['xprof_country'];
			$entry['birthday']    = $rr['xprof_dob'];
			$entry['gender']      = $rr['xprof_gender'];
			$entry['marital']     = $rr['xprof_marital'];
			$entry['keywords']    = $rr['xprof_keywords'];

			$entries[] = $entry;

		}

		$ret['results'] = $entries;

	}		
	json_return_and_die($ret);

}
