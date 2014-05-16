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

	if(array_key_exists('requires',$ret)) {
		$require = trim(strtolower($ret['requires']));
		switch($require) {
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
	$apps = array( 'Matrix' => t('Matrix'), 'Channel Home' => t('Channel Home'), 'Profile' => t('Profile'),
		'Photos' => t('Photos'), 'Events' => t('Events'), 'Directory' => t('Directory')

	);

	if(array_key_exists($arr['name'],$apps))
		$arr['name'] = $apps[$arr['name']];

}