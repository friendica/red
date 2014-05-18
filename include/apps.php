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
	$x = base64_decode($s);
	return json_decode($x,true);
}
