<?php

require_once('include/dir_fns.php');


function dirsearch_init(&$a) {
	$a->set_pager_itemspage(60);

}

function dirsearch_content(&$a) {

	$ret = array('success' => false);

//	logger('request: ' . print_r($_REQUEST,true));


	$dirmode = intval(get_config('system','directory_mode'));

	if($dirmode == DIRECTORY_MODE_NORMAL) {
		$ret['message'] = t('This site is not a directory server');
		json_return_and_die($ret);
	}

	$access_token = $_REQUEST['t'];

	$token = get_config('system','realm_token');
	if($token && $access_token != $token) {
		$result['message'] = t('This directory server requires an access token');
		return;
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
	$forums   = ((array_key_exists('pubforums',$_REQUEST)) ? intval($_REQUEST['pubforums']) : 0);



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

	if($forums)
		$safesql .= dir_flag_build(' AND ','xchan_flags',XCHAN_FLAGS_PUBFORUM, $forums);


	// we only support an age range currently. You must set both agege 
	// (greater than or equal) and agele (less than or equal) 

	if($agele && $agege) {
		$sql_extra .= " $joiner ( xprof_age <= " . intval($agele) . " ";
		$sql_extra .= " AND  xprof_age >= " . intval($agege) . ") ";
	}


	if($hash) {
		$sql_extra = " AND xchan_hash like '" . dbesc($hash) . protect_sprintf('%') . "' ";
	}


    $perpage      = (($_REQUEST['n'])              ? $_REQUEST['n']                    : 60);
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

	$logic = ((strlen($sql_extra)) ? 'false' : 'true');

	if($hash)
		$logic = 'true';

	if($dirmode == DIRECTORY_MODE_STANDALONE) {
		$sql_extra .= " and xchan_addr like '%%" . get_app()->get_hostname() . "' ";
	}


	$safesql .= (($safe > 0) ? " and not ( xchan_flags & " . intval(XCHAN_FLAGS_CENSORED|XCHAN_FLAGS_SELFCENSORED) . " )>0 " : '');
	if($safe < 0)
		$safesql .= " and ( xchan_flags & " . intval(XCHAN_FLAGS_CENSORED|XCHAN_FLAGS_SELFCENSORED) . " )>0 ";

	if($limit) 
		$qlimit = " LIMIT $limit ";
	else {
		$qlimit = " LIMIT " . intval($perpage) . " OFFSET " . intval($startrec);
		if($return_total) {
			$r = q("SELECT COUNT(xchan_hash) AS `total` FROM xchan left join xprof on xchan_hash = xprof_hash where $logic $sql_extra and xchan_network = 'zot' and not ( xchan_flags & %d)>0 and not ( xchan_flags & %d )>0 and not ( xchan_flags & %d )>0 $safesql ",
				intval(XCHAN_FLAGS_HIDDEN),
				intval(XCHAN_FLAGS_ORPHAN),
				intval(XCHAN_FLAGS_DELETED)
			);
			if($r) {
				$ret['total_items'] = $r[0]['total'];
			}
		}
	}


	if($sort_order == 'normal') {
		$order = " order by xchan_name asc ";

		// Start the alphabetic search at 'A' 
		// This will make a handful of channels whose names begin with
		// punctuation un-searchable in this mode

		$safesql .= " and ascii(substring(xchan_name FROM 1 FOR 1)) > 64 ";
	}
	elseif($sort_order == 'reverse')
		$order = " order by xchan_name desc ";
	elseif($sort_order == 'reversedate')
		$order = " order by xchan_name_date asc ";
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
		$r = q("select * from xlink where xlink_static = 1 and xlink_updated >= '%s' ",
			dbesc($sync)
		);
		if($r) {
			$spkt['ratings'] = array();
			foreach($r as $rr) {
				$spkt['ratings'][] = array(
					'type' => 'rating', 
					'encoding' => 'zot',
					'channel' => $rr['xlink_xchan'],
					'target' => $rr['xlink_link'],
					'rating' => intval($rr['xlink_rating']),
					'rating_text' => $rr['xlink_rating_text'],
					'signature' => $rr['xlink_sig'],
					'edited' => $rr['xlink_updated']
				);
			}
		}
		json_return_and_die($spkt);
	}
	else {

		$r = q("SELECT xchan.*, xprof.* from xchan left join xprof on xchan_hash = xprof_hash where ( $logic $sql_extra ) and xchan_network = 'zot' and not ( xchan_flags & %d )>0 and not ( xchan_flags & %d )>0 and not ( xchan_flags & %d )>0 $safesql $order $qlimit ",
			intval(XCHAN_FLAGS_HIDDEN),
			intval(XCHAN_FLAGS_ORPHAN),
			intval(XCHAN_FLAGS_DELETED)
		);


		$ret['page'] = $page + 1;
		$ret['records'] = count($r);		
	}


	if($r) {

		$entries = array();

		foreach($r as $rr) {

			$entry = array();

			$pc = q("select count(xlink_rating) as total_ratings from xlink where xlink_link = '%s' and xlink_rating != 0 and xlink_static = 1 group by xlink_rating",
				dbesc($rr['xchan_hash'])
			);

			if($pc)
				$entry['total_ratings'] = intval($pc[0]['total_ratings']);
			else
				$entry['total_ratings'] = 0;

			$entry['name']        = $rr['xchan_name'];
			$entry['hash']        = $rr['xchan_hash'];

			$entry['public_forum'] = (($rr['xchan_flags'] & XCHAN_FLAGS_PUBFORUM) ? true : false);

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

function dir_flag_build($joiner,$field,$bit,$s) {
	return dbesc($joiner) . " ( " . dbesc($field) . " & " . intval($bit) . " ) " . ((intval($s)) ? '>' : '=' ) . " 0 ";
}


function dir_parse_query($s) {

	$ret = array();
	$curr = array();
	$all = explode(' ',$s);
	$quoted_string = false;

	if($all) {
		foreach($all as $q) {
			if($quoted_string === false) {
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
					if($curr['value'][0] == '"' && $curr['value'][strlen($curr['value'])-1] != '"') {
						$quoted_string = true;
						$curr['value'] = substr($curr['value'],1);
						continue;
					}
					elseif($curr['value'][0] == '"' && $curr['value'][strlen($curr['value'])-1] == '"') {
						$curr['value'] = substr($curr['value'],1,strlen($curr['value'])-2);
						$ret[] = $curr;
						$curr = array();
						continue;
					}	
					else {
						$ret[] = $curr;
						$curr = array();
						continue;
					}
				}
			}
			else {
				if($q[strlen($q)-1] == '"') {
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

	$rand = db_getfunc('rand');
	$realm = get_directory_realm();
	if($realm == DIRECTORY_REALM) {
		$r = q("select * from site where site_access != 0 and site_register !=0 and ( site_realm = '%s' or site_realm = '') order by $rand",
			dbesc($realm)
		);
	}
	else {
		$r = q("select * from site where site_access != 0 and site_register !=0 and site_realm = '%s' order by $rand",
			dbesc($realm)
		);
	}
		
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
