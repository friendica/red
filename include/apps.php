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

	$observer = get_observer();
	


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

	return $ret;
}	
