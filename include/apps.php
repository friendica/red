<?php /** @file */

/**
 * apps
 *
 */

require_once('include/plugin.php');
require_once('include/identity.php');

function get_system_apps() {

	$ret = array();
	if(is_dir('apps'))
		$files = glob('apps/*.apd');
	else
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

function app_name_compare($a,$b) {
	return strcmp($a['name'],$b['name']);
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

	$ret['type'] = 'system';

	foreach($ret as $k => $v) {
		if(strpos($v,'http') === 0)
			$ret[$k] = zid($v);
	}

	if(array_key_exists('desc',$ret))
		$ret['desc'] = str_replace(array('\'','"'),array('&#39;','&dquot;'),$ret['desc']);

	if(array_key_exists('target',$ret))
		$ret['target'] = str_replace(array('\'','"'),array('&#39;','&dquot;'),$ret['target']);

	if(array_key_exists('requires',$ret)) {
		$requires = explode(',',$ret['requires']);
		foreach($requires as $require) {
			$require = trim(strtolower($require));
			switch($require) {
				case 'nologin':
					if(local_channel())
						unset($ret);
					break;
				case 'admin':
					if(! is_site_admin())
						unset($ret);
					break;
				case 'local_channel':
					if(! local_channel())
						unset($ret);
					break;
				case 'public_profile':
					if(! is_public_profile())
						unset($ret);
					break;
				case 'observer':
					if(! $observer)
						unset($ret);
					break;
				default:
					if(! (local_channel() && feature_enabled(local_channel(),$require)))
						unset($ret);
					break;

			}
		}
	}
	if($ret) {
		translate_system_apps($ret);
		return $ret;
	}
	return false;
}	


function translate_system_apps(&$arr) {
	$apps = array(
		'Site Admin' => t('Site Admin'),
		'Bookmarks' => t('Bookmarks'),
		'Address Book' => t('Address Book'),
		'Login' => t('Login'),
		'Channel Manager' => t('Channel Manager'), 
		'Matrix' => t('Matrix'), 
		'Settings' => t('Settings'),
		'Files' => t('Files'),
		'Webpages' => t('Webpages'),
		'Channel Home' => t('Channel Home'), 
		'Profile' => t('Profile'),
		'Photos' => t('Photos'), 
		'Events' => t('Events'), 
		'Directory' => t('Directory'), 
		'Help' => t('Help'),
		'Mail' => t('Mail'),
		'Mood' => t('Mood'),
		'Poke' => t('Poke'),
		'Chat' => t('Chat'),
		'Search' => t('Search'),
		'Probe' => t('Probe'),
		'Suggest' => t('Suggest'),
		'Random Channel' => t('Random Channel'),
		'Invite' => t('Invite'),
		'Features' => t('Features'),
		'Language' => t('Language'),
		'Post' => t('Post'),
		'Profile Photo' => t('Profile Photo')
	);

	if(array_key_exists($arr['name'],$apps))
		$arr['name'] = $apps[$arr['name']];

}


// papp is a portable app

function app_render($papp,$mode = 'view') {

	/**
	 * modes:
	 *    view: normal mode for viewing an app via bbcode from a conversation or page
	 *       provides install/update button if you're logged in locally
	 *    list: normal mode for viewing an app on the app page
	 *       no buttons are shown
	 *    edit: viewing the app page in editing mode provides a delete button
	 */

	$installed = false;

	if(! $papp['photo'])
		$papp['photo'] = z_root() . '/' . get_default_profile_photo(80);
	
	if(! $papp)
		return;

	$papp['papp'] = papp_encode($papp);

	foreach($papp as $k => $v) {
		if(strpos($v,'http') === 0 && $k != 'papp')
			$papp[$k] = zid($v);
		if($k === 'desc')
			$papp['desc'] = str_replace(array('\'','"'),array('&#39;','&dquot;'),$papp['desc']);

		if($k === 'requires') {
			$requires = explode(',',$v);
			foreach($requires as $require) {
				$require = trim(strtolower($require));
				switch($require) {
					case 'nologin':
						if(local_channel())
							return '';
						break;
					case 'admin':
						if(! is_site_admin())
							return '';
						break;
					case 'local_channel':
						if(! local_channel())
							return '';
						break;
					case 'public_profile':
						if(! is_public_profile())
							return '';
						break;
					case 'observer':
						$observer = get_app()->get_observer();
						if(! $observer)
							return '';
						break;
					default:
						if(! (local_channel() && feature_enabled(local_channel(),$require)))
							return '';
						break;

				}
			}

		}
	}

	$hosturl = '';

	if(local_channel()) {
		$installed = app_installed(local_channel(),$papp);
		$hosturl = z_root() . '/';
	}
	elseif(remote_channel()) {
		$observer = get_app()->get_observer();
		if($observer && $observer['xchan_network'] === 'zot') {
			// some folks might have xchan_url redirected offsite, use the connurl
			$x = parse_url($observer['xchan_connurl']);
			if($x) {
				$hosturl = $x['scheme'] . '://' . $x['host'] . '/';
			}
		} 
	}

	$install_action = (($installed) ? t('Update') : t('Install'));

	return replace_macros(get_markup_template('app.tpl'),array(
		'$app' => $papp,
		'$hosturl' => $hosturl,
		'$purchase' => (($papp['page'] && (! $installed)) ? t('Purchase') : ''),
		'$install' => (($hosturl && $mode == 'view') ? $install_action : ''),
		'$edit' => ((local_channel() && $installed && $mode == 'edit') ? t('Edit') : ''),
		'$delete' => ((local_channel() && $installed && $mode == 'edit') ? t('Delete') : '')
	));
}


function app_install($uid,$app) {
	$app['uid'] = $uid;
	if(app_installed($uid,$app))
		$x = app_update($app);
	else
		$x = app_store($app);

	if($x['success'])
		return $x['app_id'];

	return false;
}

function app_destroy($uid,$app) {
	if($uid && $app['guid']) {
		$r = q("delete from app where app_id = '%s' and app_channel = %d",
			dbesc($app['guid']),
			intval($uid)
		);
	}
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
	if($r) {
		for($x = 0; $x < count($r); $x ++) {
			$r[$x]['type'] = 'personal';
		}
	}
	return($r);
}


function app_decode($s) {
	$x = base64_decode(str_replace(array('<br />',"\r","\n",' '),array('','','',''),$s));
	return json_decode($x,true);
}


function app_store($arr) {

	// logger('app_store: ' . print_r($arr,true));

	$darray = array();
	$ret = array('success' => false);

	$darray['app_url']     = ((x($arr,'url')) ? $arr['url'] : '');
	$darray['app_channel'] = ((x($arr,'uid')) ? $arr['uid'] : 0);

	if((! $darray['app_url']) || (! $darray['app_channel']))
		return $ret;

	if($arr['photo'] && ! strstr($arr['photo'],z_root())) {
		$x = import_profile_photo($arr['photo'],get_observer_hash(),true);
		$arr['photo'] = $x[1];
	}


	$darray['app_id']       = ((x($arr,'guid'))     ? $arr['guid'] : random_string(). '.' . get_app()->get_hostname());
	$darray['app_sig']      = ((x($arr,'sig'))      ? $arr['sig'] : '');
	$darray['app_author']   = ((x($arr,'author'))   ? $arr['author'] : get_observer_hash());
	$darray['app_name']     = ((x($arr,'name'))     ? escape_tags($arr['name']) : t('Unknown'));
	$darray['app_desc']     = ((x($arr,'desc'))     ? escape_tags($arr['desc']) : '');
	$darray['app_photo']    = ((x($arr,'photo'))    ? $arr['photo'] : z_root() . '/' . get_default_profile_photo(80));
	$darray['app_version']  = ((x($arr,'version'))  ? escape_tags($arr['version']) : '');
	$darray['app_addr']     = ((x($arr,'addr'))     ? escape_tags($arr['addr']) : '');
	$darray['app_price']    = ((x($arr,'price'))    ? escape_tags($arr['price']) : '');
	$darray['app_page']     = ((x($arr,'page'))     ? escape_tags($arr['page']) : '');
	$darray['app_requires'] = ((x($arr,'requires')) ? escape_tags($arr['requires']) : '');

	$r = q("insert into app ( app_id, app_sig, app_author, app_name, app_desc, app_url, app_photo, app_version, app_channel, app_addr, app_price, app_page, app_requires ) values ( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s' )",
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
		dbesc($darray['app_page']),
		dbesc($darray['app_requires'])
	);
	if($r) {
		$ret['success'] = true;
		$ret['app_id'] = $darray['app_id'];
	}
	return $ret;
}


function app_update($arr) {

	$darray = array();
	$ret = array('success' => false);

	$darray['app_url']     = ((x($arr,'url')) ? $arr['url'] : '');
	$darray['app_channel'] = ((x($arr,'uid')) ? $arr['uid'] : 0);
	$darray['app_id']      = ((x($arr,'guid')) ? $arr['guid'] : 0);

	if((! $darray['app_url']) || (! $darray['app_channel']) || (! $darray['app_id']))
		return $ret;

	if($arr['photo'] && ! strstr($arr['photo'],z_root())) {
		$x = import_profile_photo($arr['photo'],get_observer_hash(),true);
		$arr['photo'] = $x[1];
	}

	$darray['app_sig']      = ((x($arr,'sig')) ? $arr['sig'] : '');
	$darray['app_author']   = ((x($arr,'author')) ? $arr['author'] : get_observer_hash());
	$darray['app_name']     = ((x($arr,'name')) ? escape_tags($arr['name']) : t('Unknown'));
	$darray['app_desc']     = ((x($arr,'desc')) ? escape_tags($arr['desc']) : '');
	$darray['app_photo']    = ((x($arr,'photo')) ? $arr['photo'] : z_root() . '/' . get_default_profile_photo(80));
	$darray['app_version']  = ((x($arr,'version')) ? escape_tags($arr['version']) : '');
	$darray['app_addr']     = ((x($arr,'addr')) ? escape_tags($arr['addr']) : '');
	$darray['app_price']    = ((x($arr,'price')) ? escape_tags($arr['price']) : '');
	$darray['app_page']     = ((x($arr,'page')) ? escape_tags($arr['page']) : '');
	$darray['app_requires'] = ((x($arr,'requires')) ? escape_tags($arr['requires']) : '');

	$r = q("update app set app_sig = '%s', app_author = '%s', app_name = '%s', app_desc = '%s', app_url = '%s', app_photo = '%s', app_version = '%s', app_addr = '%s', app_price = '%s', app_page = '%s', app_requires = '%s' where app_id = '%s' and app_channel = %d",
		dbesc($darray['app_sig']),
		dbesc($darray['app_author']),
		dbesc($darray['app_name']),
		dbesc($darray['app_desc']),
		dbesc($darray['app_url']),
		dbesc($darray['app_photo']),
		dbesc($darray['app_version']),
		dbesc($darray['app_addr']),
		dbesc($darray['app_price']),
		dbesc($darray['app_page']),
		dbesc($darray['app_requires']),
		dbesc($darray['app_id']),
		intval($darray['app_channel'])
	);
	if($r) {
		$ret['success'] = true;
		$ret['app_id'] = $darray['app_id'];
	}

	return $ret;

}


function app_encode($app,$embed = false) {

	$ret = array();

	$ret['type'] = 'personal';

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

	if($app['app_requires'])
		$ret['requires'] = $app['app_requires'];

	if(! $embed)
		return $ret;

	$j = json_encode($ret);
	return '[app]' . chunk_split(base64_encode($j),72,"\n") . '[/app]';

}


function papp_encode($papp) {
	return chunk_split(base64_encode(json_encode($papp)),72,"\n");

}


