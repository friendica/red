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

	$sql_extra = '';

	$tables = array('name','address','locale','region','postcode','country','gender','marital','sexual','keywords');


	if($_REQUEST['query']) {
		$advanced = dir_parse_query($_REQUEST['query']);
		if($advanced) {
			foreach($advanced as $adv) {
				if(in_array($adv['field'],$tables)) {
					if($adv['field'] === 'name')
						$sql_extra .= dir_query_build($adv['logic'],'xchan_name',$adv['value']);
					elseif($adv['field'] === 'address')
 						$sql_extra .= dir_query_build($adv['logic'],'xchan_addr',$adv['value']);
					else
						$sql_extra .= dir_query_build($adv['logic'],'xprof_' . $adv['field'],$adv['value']);
				}
			}
		}
	}

	$hash     = ((x($_REQUEST['hash']))    ? $_REQUEST['hash']     : '');

	$name     = ((x($_REQUEST,'name'))     ? $_REQUEST['name']     : '');
	$hub      = ((x($_REQUEST,'hub'))      ? $_REQUEST['hub']      : '');
	$address  = ((x($_REQUEST,'address'))  ? $_REQUEST['address']  : '');
	$locale   = ((x($_REQUEST,'locale'))   ? $_REQUEST['locale']   : '');
	$region   = ((x($_REQUEST,'region'))   ? $_REQUEST['region']   : '');
	$postcode = ((x($_REQUEST,'postcode')) ? $_REQUEST['postcode'] : '');
	$country  = ((x($_REQUEST,'country'))  ? $_REQUEST['country']  : '');
	$gender   = ((x($_REQUEST,'gender'))   ? $_REQUEST['gender']   : '');
	$marital  = ((x($_REQUEST,'marital'))  ? $_REQUEST['marital']  : '');
	$sexual   = ((x($_REQUEST,'sexual'))   ? $_REQUEST['sexual']   : '');
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

	$joiner = ' OR ';
	if($_REQUEST['and'])
		$joiner = ' AND ';

	if($name)
		$sql_extra .= dir_query_build($joiner,'xchan_name',$name);
	if($hub)
		$sql_extra .= " $joiner xchan_hash in (select hubloc_hash from hubloc where hubloc_host =  '" . protect_sprintf(dbesc($hub)) . "') ";
	if($address)
		$sql_extra .= dir_query_build($joiner,'xchan_addr',$address);
	if($city)
		$sql_extra .= dir_query_build($joiner,'xprof_locale',$city);
	if($region)
		$sql_extra .= dir_query_build($joiner,'xprof_region',$region);
	if($post)
		$sql_extra .= dir_query_build($joiner,'xprof_postcode',$post);
	if($country)
		$sql_extra .= dir_query_build($joiner,'xprof_country',$country);
	if($gender)
		$sql_extra .= dir_query_build($joiner,'xprof_gender',$gender);
	if($marital)
		$sql_extra .= dir_query_build($joiner,'xprof_marital',$marital);
	if($sexual)
		$sql_extra .= dir_query_build($joiner,'xprof_sexual',$sexual);
	if($keywords)
		$sql_extra .= dir_query_build($joiner,'xprof_keywords',$keywords);

	// we only support an age range currently. You must set both agege 
	// (greater than or equal) and agele (less than or equal) 

	if($agele && $agege) {
		$sql_extra .= " $joiner ( xprof_age <= " . intval($agele) . " ";
		$sql_extra .= " AND  xprof_age >= " . intval($agege) . ") ";
	}


	if($hash) {
		$sql_extra = " AND xchan_hash = '" . dbesc($hash) . "' ";
	}


    $perpage      = (($_REQUEST['n'])              ? $_REQUEST['n']                    : 300);
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

	if($hash)
		$logic = 1;

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
				$flags = array();
				if($rr['ud_flags'] & UPDATE_FLAGS_DELETED)
					$flags[] = 'deleted';
				if($rr['ud_flags'] & UPDATE_FLAGS_FORCED)
					$flags[] = 'forced';

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

			$entry['url']         = $rr['xchan_url'];
			$entry['photo_l']     = $rr['xchan_photo_l'];
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
			$entry['sexual']      = $rr['xprof_sexual'];
			$entry['about']       = $rr['xprof_about'];
			$entry['homepage']    = $rr['xprof_homepage'];
			$entry['hometown']    = $rr['xprof_hometown'];
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

function dir_query_build($joiner,$field,$s) {
	$ret = '';
	if(trim($s))
		$ret .= dbesc($joiner) . " " . dbesc($field) . " like '" . protect_sprintf( '%' . dbesc($s) . '%' ) . "' ";
	return $ret;
}

function dir_parse_query($s) {

	$ret = array();
	$curr = array();
	$all = explode(' ',$s);
	$quoted_string = false;

	if($all) {
		foreach($all as $q) {
			if($q === 'and') {
				$curr['logic'] = 'and';
				continue;
			}
			if($q === 'or') {
				$curr['logic'] = 'or';
				continue;
			}
			if($q === 'not') {
				$curr['logic'] .= ' not';
				continue;
			}
			if(strpos($q,'=')) {
				if(! isset($curr['logic']))
					$curr['logic'] = 'or';
				$curr['field'] = trim(substr($q,0,strpos($q,'=')));
				$curr['value'] = trim(substr($q,strpos($q,'=')+1));
				if(strpos($curr['value'],'"') !== false) {
					$quoted_string = true;
					$curr['value'] = substr($curr['value'],strpos($curr['value'],'"')+1);
				}
				else {
					$ret[] = $curr;
					$curr = array();
					$continue;
				}
			}
			elseif($quoted_string) {
				if(strpos($q,'"') !== false) {
					$curr['value'] .= ' ' . str_replace('"','',trim($q));
					$ret[] = $curr;
					$curr = array();
					$quoted_string = false;
				}
				else
					$curr['value'] .= ' ' . trim(q);
			}
		}
	}
	logger('dir_parse_query:' . print_r($ret,true),LOGGER_DATA);
	return $ret;
}

	





function list_public_sites() {
	$r = q("select * from site where site_access != 0 and site_register !=0 order by rand()");
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
