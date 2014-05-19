<?php /** @file */

/**
 * apps
 *
 */

require_once('include/plugin.php');

function get_system_apps() {

	$ret = array();
	$files = glob('app/*.apd');
	if($files) {
		foreach($files as $f) {
			$x = parse_app_description($f);
			if($x) {
				$ret[] = $x;
			}
		}
	}
	$files = glob('addon/*/*.apd');
	if($files) {
		foreach($files as $f) {
			$n = basename($f,'.apd');
			if(plugin_is_installed($n)) {
				$x = parse_app_description($f);
				if($x) {
					$ret[] = $x;
				}
			}
		}
	}

	return $ret;

}


function parse_app_description($f) {
	$ret = array();

	$baseurl = z_root();
	$channel = get_app()->get_channel();
	$address = (($channel) ? $channel['channel_address'] : '');
		
	//future expansion

	$observer = get_app()->get_observer();
	

	$lines = @file($f);
	if($lines) {
		foreach($lines as $x) {
			if(preg_match('/^([a-zA-Z].*?):(.*?)$/ism',$x,$matches)) {
				$ret[$matches[1]] = trim(str_replace(array('$baseurl','$nick'),array($baseurl,$address),$matches[2]));
			}
		}
	}



	if(! $ret['photo'])
		$ret['photo'] = $baseurl . '/' . get_default_profile_photo(80);


	foreach($ret as $k => $v) {
		if(strpos($v,'http') === 0)
			$ret[$k] = zid($v);
	}

	if(array_key_exists('hover',$ret))
		$ret['hover'] = str_replace(array('\'','"'),array('&#39;','&dquot;'),$ret['hover']);

	if(array_key_exists('requires',$ret)) {
		$require = trim(strtolower($ret['requires']));
		switch($require) {
			case 'nologin':
				if(local_user())
					unset($ret);
				break;
			case 'local_user':
				if(! local_user())
					unset($ret);
				break;
			case 'observer':
				if(! $observer)
					unset($ret);
				break;
			default:
				if(! local_user() && feature_enabled(local_user(),$require))
					unset($ret);
				break;

		}
		logger('require: ' . print_r($ret,true));
	}
	if($ret) {
		translate_system_apps($ret);
		return $ret;
	}
	return false;
}	


function translate_system_apps(&$arr) {
	$apps = array( 'Matrix' => t('Matrix'), 
		'Channel Home' => t('Channel Home'), 
		'Profile' => t('Profile'),
		'Photos' => t('Photos'), 
		'Events' => t('Events'), 
		'Directory' => t('Directory'), 
		'Help' => t('Help')
	);

	if(array_key_exists($arr['name'],$apps))
		$arr['name'] = $apps[$arr['name']];

}

function app_render($app) {
//debugging
	return print_r($app,true);	

}


function app_install($uid,$app) {



}


function app_installed($uid,$app) {

	$r = q("select id from app where app_id = '%s' and app_version = '%s' and app_channel = %d limit 1",
		dbesc((array_key_exists('guid',$app)) ? $app['guid'] : ''), 
		dbesc((array_key_exists('version',$app)) ? $app['version'] : ''), 
		intval($uid)
	);
	return(($r) ? true : false);

}


function app_list($uid) {
	$r = q("select * from app where app_channel = %d order by app_name asc",
		intval($uid)
	);
	return($r);
}


function app_decode($s) {
	$x = base64_decode(str_replace(array('<br />',"\r","\n",' '),array('','','',''),$s));
	return json_decode($x,true);
}


function app_store($arr) {

	$darray = array();
	$ret = array('success' => false);

	$darray['app_url'] = ((x($arr,'url')) ? $arr['url'] : '');
	$darray['app_channel'] = ((x($arr,'uid')) ? $arr['uid'] : 0);
	if((! $darray['url']) || (! $darray['app_channel']))
		return $ret;

	$darray['app_id'] = ((x($arr,'guid')) ? $arr['guid'] : random_string());
	$darray['app_sig'] = ((x($arr,'sig')) ? $arr['sig'] : '');
	$darray['app_author'] = ((x($arr,'author')) ? $arr['author'] : get_observer_hash());
	$darray['app_name'] = ((x($arr,'name')) ? escape_tags($arr['name']) : t('Unknown'));
	$darray['app_desc'] = ((x($arr,'desc')) ? escape_tags($arr['desc']) : '');
	$darray['app_photo'] = ((x($arr,'photo')) ? $arr['photo'] : z_root() . '/' . get_default_profile_photo(80));
	$darray['app_version'] = ((x($arr,'version')) ? escape_tags($arr['version']) : '');
	$darray['app_addr'] = ((x($arr,'addr')) ? escape_tags($arr['addr']) : '');
	$darray['app_price'] = ((x($arr,'price')) ? escape_tags($arr['price']) : '');
	$darray['app_page'] = ((x($arr,'page')) ? escape_tags($arr['page']) : '');

	$r = q("insert into app ( app_id, app_sig, app_author, app_name, app_desc, app_url, app_photo, app_version, app_channel, app_addr, app_price, app_page ) values ( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s' )",
		dbesc($darray['app_id']),
		dbesc($darray['app_sig']),
		dbesc($darray['app_author']),
		dbesc($darray['app_name']),
		dbesc($darray['app_desc']),
		dbesc($darray['app_url']),
		dbesc($darray['app_photo']),
		dbesc($darray['app_version']),
		intval($darray['app_channel']),
		dbesc($darray['app_addr']),
		dbesc($darray['app_price']),
		dbesc($darray['app_page'])
	);
	if($r)
		$ret['success'] = true;

	return $ret;
}


function app_update($arr) {




}


function app_encode($app) {

	$ret = array();

	if($app['app_id'])
		$ret['guid'] = $app['app_id'];

	if($app['app_id'])
		$ret['guid'] = $app['app_id'];

	if($app['app_sig'])
		$ret['sig'] = $app['app_sig'];

	if($app['app_author'])
		$ret['author'] = $app['app_author'];

	if($app['app_name'])
		$ret['name'] = $app['app_name'];

	if($app['app_desc'])
		$ret['desc'] = $app['app_desc'];

	if($app['app_url'])
		$ret['url'] = $app['app_url'];

	if($app['app_photo'])
		$ret['photo'] = $app['app_photo'];

	if($app['app_version'])
		$ret['version'] = $app['app_version'];

	if($app['app_addr'])
		$ret['addr'] = $app['app_addr'];

	if($app['app_price'])
		$ret['price'] = $app['app_price'];

	if($app['app_page'])
		$ret['page'] = $app['app_page'];

	$j = json_encode($ret);
	return '[app]' . chunk_split(base64_encode($j),72,"\n") . '[/app]';

}