<?php

require_once('include/dir_fns.php');


function dirsearch_init(&$a) {
	$a->set_pager_itemspage(80);

}

function dirsearch_content(&$a) {

	$ret = array('success' => false);

	// If you've got a public directory server, you probably shouldn't block public access


	$dirmode = intval(get_config('system','directory_mode'));

	if($dirmode == DIRECTORY_MODE_NORMAL) {
		$ret['message'] = t('This site is not a directory server');
		json_return_and_die($ret);
	}

	if(argc() > 1 && argv(1) === 'sites') {
		$ret = list_public_sites();
		json_return_and_die($ret);
	}


	$name     = ((x($_REQUEST,'name'))     ? $_REQUEST['name']     : '');
	$hub      = ((x($_REQUEST,'hub'))      ? $_REQUEST['hub']     : '');
	$address  = ((x($_REQUEST,'address'))  ? $_REQUEST['address']  : '');
	$locale   = ((x($_REQUEST,'locale'))   ? $_REQUEST['locale']   : '');
	$region   = ((x($_REQUEST,'region'))   ? $_REQUEST['region']   : '');
	$postcode = ((x($_REQUEST,'postcode')) ? $_REQUEST['postcode'] : '');
	$country  = ((x($_REQUEST,'country'))  ? $_REQUEST['country']  : '');
	$gender   = ((x($_REQUEST,'gender'))   ? $_REQUEST['gender']   : '');
	$marital  = ((x($_REQUEST,'marital'))  ? $_REQUEST['marital']  : '');
	$keywords = ((x($_REQUEST,'keywords')) ? $_REQUEST['keywords'] : '');
	$agege    = ((x($_REQUEST,'agege'))    ? intval($_REQUEST['agege']) : 0 );
	$agele    = ((x($_REQUEST,'agele'))    ? intval($_REQUEST['agele']) : 0 );
	$kw       = ((x($_REQUEST,'kw'))       ? intval($_REQUEST['kw'])    : 0 );

	// by default use a safe search
	$safe     = ((x($_REQUEST,'safe')));    // ? intval($_REQUEST['safe'])  : 1 );
	if ($safe === false)
			$safe = 1;
		
	if(array_key_exists('sync',$_REQUEST)) {
		if($_REQUEST['sync'])
			$sync = datetime_convert('UTC','UTC',$_REQUEST['sync']);
		else
			$sync = datetime_convert('UTC','UTC','2010-01-01 01:01:00');
	}
	else
		$sync = false;

	$sort_order  = ((x($_REQUEST,'order')) ? $_REQUEST['order'] : '');

// TODO - a meta search which joins all of these things to one search string

	$sql_extra = '';

	if($name)
		$sql_extra .= " OR xchan_name like '" . protect_sprintf( '%' . dbesc($name) . '%' ) . "' ";
	if($hub)
		$sql_extra .= " OR xchan_hash in (select hubloc_hash from hubloc where hubloc_host =  '" . protect_sprintf(dbesc($hub)) . "') ";
	if($address)
		$sql_extra .= " OR xchan_addr like '" . protect_sprintf( '%' . dbesc($address) . '%' ) . "' ";
	if($city)
		$sql_extra .= " OR xprof_locale like '" . protect_sprintf( '%' . dbesc($city) . '%' ) . "' ";
	if($region)
		$sql_extra .= " OR xprof_region like '" . protect_sprintf( '%' . dbesc($region) . '%' ) . "' ";
	if($post)
		$sql_extra .= " OR xprof_postcode like '" . protect_sprintf( '%' . dbesc($post) . '%' ) . "' ";
	if($country)
		$sql_extra .= " OR xprof_country like '" . protect_sprintf( '%' . dbesc($country) . '%' ) . "' ";
	if($gender)
		$sql_extra .= " OR xprof_gender like '" . protect_sprintf( '%' . dbesc($gender) . '%' ) . "' ";
	if($marital)
		$sql_extra .= " OR xprof_marital like '" . protect_sprintf( '%' . dbesc($marital) . '%' ) . "' ";
	if($keywords)
		$sql_extra .= " OR xprof_keywords like '" . protect_sprintf( '%' . dbesc($keywords) . '%' ) . "' ";

	// we only support an age range currently. You must set both agege 
	// (greater than or equal) and agele (less than or equal) 

	if($agele && $agege) {
		$sql_extra .= " OR ( xprof_age <= " . intval($agele) . " ";
		$sql_extra .= " AND  xprof_age >= " . intval($agege) . ") ";
	}

    $perpage      = (($_REQUEST['n'])              ? $_REQUEST['n']                    : 80);
    $page         = (($_REQUEST['p'])              ? intval($_REQUEST['p'] - 1)        : 0);
    $startrec     = (($page+1) * $perpage) - $perpage;
	$limit        = (($_REQUEST['limit'])          ? intval($_REQUEST['limit'])        : 0);
	$return_total = ((x($_REQUEST,'return_total')) ? intval($_REQUEST['return_total']) : 0);

	// mtime is not currently working

	$mtime        = ((x($_REQUEST,'mtime'))        ? datetime_convert('UTC','UTC',$_REQUEST['mtime']) : '');

	// ok a separate tag table won't work. 
	// merge them into xprof

	$ret['success'] = true;

	// If &limit=n, return at most n entries
	// If &return_total=1, we count matching entries and return that as 'total_items' for use in pagination.
	// By default we return one page (default 80 items maximum) and do not count total entries

	$logic = ((strlen($sql_extra)) ? 0 : 1);

	$safesql = (($safe > 0) ? " and not ( xchan_flags & " . intval(XCHAN_FLAGS_CENSORED|XCHAN_FLAGS_SELFCENSORED) . " ) " : '');
	if($safe < 0)
		$safesql = " and ( xchan_flags & " . intval(XCHAN_FLAGS_CENSORED|XCHAN_FLAGS_SELFCENSORED) . " ) ";

	if($limit) 
		$qlimit = " LIMIT $limit ";
	else {
		$qlimit = " LIMIT " . intval($startrec) . " , " . intval($perpage);
		if($return_total) {
			$r = q("SELECT COUNT(xchan_hash) AS `total` FROM xchan left join xprof on xchan_hash = xprof_hash where $logic $sql_extra and not ( xchan_flags & %d) and not ( xchan_flags & %d ) and not ( xchan_flags & %d ) $safesql ",
				intval(XCHAN_FLAGS_HIDDEN),
				intval(XCHAN_FLAGS_ORPHAN),
				intval(XCHAN_FLAGS_DELETED)
			);
			if($r) {
				$ret['total_items'] = $r[0]['total'];
			}
		}
	}


	if($sort_order == 'normal')
		$order = " order by xchan_name asc ";
	elseif($sort_order == 'reverse')
		$order = " order by xchan_name desc ";
	else	
		$order = " order by xchan_name_date desc ";


	if($sync) {
		$spkt = array('transactions' => array());
		$r = q("select * from updates where ud_date >= '%s' and ud_guid != '' order by ud_date desc",
			dbesc($sync)
		);
		if($r) {
			foreach($r as $rr) {
				$flags = (($rr['ud_flags'] & UPDATE_FLAGS_DELETED) ? array('deleted') : array());
				$spkt['transactions'][] = array(
					'hash' => $rr['ud_hash'],
					'address' => $rr['ud_addr'],
					'transaction_id' => $rr['ud_guid'],
					'timestamp' => $rr['ud_date'],
					'flags' => $flags
				);
			}
		}
		json_return_and_die($spkt);
	}
	else {

		$r = q("SELECT xchan.*, xprof.* from xchan left join xprof on xchan_hash = xprof_hash where ( $logic $sql_extra ) and not ( xchan_flags & %d ) and not ( xchan_flags & %d ) and not ( xchan_flags & %d ) $safesql $order $qlimit ",
			intval(XCHAN_FLAGS_HIDDEN),
			intval(XCHAN_FLAGS_ORPHAN),
			intval(XCHAN_FLAGS_DELETED)
		);

	}

	$ret['page'] = $page + 1;
	$ret['records'] = count($r);		

	if($r) {

		$entries = array();


		foreach($r as $rr) {
			$entry = array();

			$entry['name']        = $rr['xchan_name'];
			$entry['hash']        = $rr['xchan_hash'];

//			$entry['updated']     = (($rr['ud_date']) ? $rr['ud_date'] : '0000-00-00 00:00:00');
//			$entry['update_guid'] = (($rr['ud_guid']) ? $rr['ud_guid'] : ''); 
			$entry['url']         = $rr['xchan_url'];
			$entry['photo']       = $rr['xchan_photo_m'];
			$entry['address']     = $rr['xchan_addr'];
			$entry['description'] = $rr['xprof_desc'];
			$entry['locale']      = $rr['xprof_locale'];
			$entry['region']      = $rr['xprof_region'];
			$entry['postcode']    = $rr['xprof_postcode'];
			$entry['country']     = $rr['xprof_country'];
			$entry['birthday']    = $rr['xprof_dob'];
			$entry['age']         = $rr['xprof_age'];
			$entry['gender']      = $rr['xprof_gender'];
			$entry['marital']     = $rr['xprof_marital'];
			$entry['keywords']    = $rr['xprof_keywords'];

			$entries[] = $entry;

		}

		$ret['results'] = $entries;
		if($kw) {
			$k = dir_tagadelic($kw);
			if($k) {
				$ret['keywords'] = array();
				foreach($k as $kv) {
					$ret['keywords'][] = array('term' => $kv[0],'weight' => $kv[1], 'normalise' => $kv[2]);
				}
			}
		}
	}		
	json_return_and_die($ret);

}


function list_public_sites() {
	$r = q("select * from site where site_access != 0 order by rand()");
	$ret = array('success' => false);

	if($r) {
		$ret['success'] = true;
		$ret['sites'] = array();
		$insecure = array();

		foreach($r as $rr) {
			
			if($rr['site_access'] == ACCESS_FREE)
				$access = 'free';
			elseif($rr['site_access'] == ACCESS_PAID)
				$access = 'paid';
			elseif($rr['site_access'] == ACCESS_TIERED)
				$access = 'tiered';
			else
				$access = 'private';

			if($rr['site_register'] == REGISTER_OPEN)
				$register = 'open';
			elseif($rr['site_register'] == REGISTER_APPROVE)
				$register = 'approve';
			else
				$register = 'closed';

			if(strpos($rr['site_url'],'https://') !== false)
				$ret['sites'][] = array('url' => $rr['site_url'], 'access' => $access, 'register' => $register, 'sellpage' => $rr['site_sellpage'], 'location' => $rr['site_location']);
			else
				$insecure[] = array('url' => $rr['site_url'], 'access' => $access, 'register' => $register, 'sellpage' => $rr['site_sellpage'], 'location' => $rr['site_location']);
		}
		if($insecure) {
			$ret['sites'] = array_merge($ret['sites'],$insecure);
		}
	}
	return $ret;
}		