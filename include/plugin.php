<?php /** @file */

require_once("include/friendica_smarty.php");

// install and uninstall plugin

function unload_plugin($plugin){
	logger("Addons: unloading " . $plugin, LOGGER_DEBUG);
    
	@include_once('addon/' . $plugin . '/' . $plugin . '.php');
	if(function_exists($plugin . '_unload')) {
		$func = $plugin . '_unload';
		$func();
	}
}



function uninstall_plugin($plugin) {

	unload_plugin($plugin);

	if(! file_exists('addon/' . $plugin . '/' . $plugin . '.php'))
		return false;

	logger("Addons: uninstalling " . $plugin);
	$t = @filemtime('addon/' . $plugin . '/' . $plugin . '.php');
	@include_once('addon/' . $plugin . '/' . $plugin . '.php');
	if(function_exists($plugin . '_uninstall')) {
		$func = $plugin . '_uninstall';
		$func();
	}

	q("DELETE FROM `addon` WHERE `name` = '%s' ",
		dbesc($plugin)
	);

}

function install_plugin($plugin) {
	if(! file_exists('addon/' . $plugin . '/' . $plugin . '.php'))
		return false;

	logger("Addons: installing " . $plugin);
	$t = @filemtime('addon/' . $plugin . '/' . $plugin . '.php');
	@include_once('addon/' . $plugin . '/' . $plugin . '.php');
	if(function_exists($plugin . '_install')) {
		$func = $plugin . '_install';
		$func();
	}

	$plugin_admin = (function_exists($plugin . "_plugin_admin") ? 1 : 0);
		
	$r = q("INSERT INTO `addon` (`name`, `installed`, `timestamp`, `plugin_admin`) VALUES ( '%s', 1, %d , %d ) ",
		dbesc($plugin),
		intval($t),
		$plugin_admin
	);

	load_plugin($plugin);
			
}


function load_plugin($plugin) {
	// silently fail if plugin was removed

	if(! file_exists('addon/' . $plugin . '/' . $plugin . '.php'))
		return false;

	logger("Addons: loading " . $plugin);
	$t = @filemtime('addon/' . $plugin . '/' . $plugin . '.php');
	@include_once('addon/' . $plugin . '/' . $plugin . '.php');
	if(function_exists($plugin . '_load')) {
		$func = $plugin . '_load';
		$func();
		

		// we can add the following with the previous SQL
		// once most site tables have been updated.
		// This way the system won't fall over dead during the update.

		if(file_exists('addon/' . $plugin . '/.hidden')) {
			q("update addon set hidden = 1 where name = '%s' limit 1",
				dbesc($plugin)
			);
		}
		return true;
	}
	else {
		logger("Addons: FAILED loading " . $plugin);
		return false;
	}

}

// reload all updated plugins

function reload_plugins() {
	$plugins = get_config('system','addon');
	if(strlen($plugins)) {

		$r = q("SELECT * FROM `addon` WHERE `installed` = 1");
		if(count($r))
			$installed = $r;
		else
			$installed = array();

		$parr = explode(',',$plugins);

		if(count($parr)) {
			foreach($parr as $pl) {

				$pl = trim($pl);

				$fname = 'addon/' . $pl . '/' . $pl . '.php';

				if(file_exists($fname)) {
					$t = @filemtime($fname);
					foreach($installed as $i) {
						if(($i['name'] == $pl) && ($i['timestamp'] != $t)) {	
							logger('Reloading plugin: ' . $i['name']);
							@include_once($fname);

							if(function_exists($pl . '_unload')) {
								$func = $pl . '_unload';
								$func();
							}
							if(function_exists($pl . '_load')) {
								$func = $pl . '_load';
								$func();
							}
							q("UPDATE `addon` SET `timestamp` = %d WHERE `id` = %d LIMIT 1",
								intval($t),
								intval($i['id'])
							);
						}
					}
				}
			}
		}
	}
}
				





function register_hook($hook,$file,$function,$priority=0) {

	$r = q("SELECT * FROM `hook` WHERE `hook` = '%s' AND `file` = '%s' AND `function` = '%s' LIMIT 1",
		dbesc($hook),
		dbesc($file),
		dbesc($function)
	);
	if(count($r))
		return true;

	$r = q("INSERT INTO `hook` (`hook`, `file`, `function`, `priority`) VALUES ( '%s', '%s', '%s', '%s' ) ",
		dbesc($hook),
		dbesc($file),
		dbesc($function),
		dbesc($priority)
	);
	return $r;
}


function unregister_hook($hook,$file,$function) {

	$r = q("DELETE FROM hook WHERE hook = '%s' AND `file` = '%s' AND `function` = '%s' LIMIT 1",
		dbesc($hook),
		dbesc($file),
		dbesc($function)
	);
	return $r;
}


//
// It might not be obvious but themes can manually add hooks to the $a->hooks
// array in their theme_init() and use this to customise the app behaviour.  
//


function load_hooks() {
	$a = get_app();
	$a->hooks = array();
	$r = q("SELECT * FROM hook WHERE true ORDER BY priority DESC");
	if($r) {
		foreach($r as $rr) {
			if(! array_key_exists($rr['hook'],$a->hooks))
				$a->hooks[$rr['hook']] = array();
			$a->hooks[$rr['hook']][] = array($rr['file'],$rr['function']);
		}
	}
}



function call_hooks($name, &$data = null) {
	$a = get_app();

	if((is_array($a->hooks)) && (array_key_exists($name,$a->hooks))) {
		foreach($a->hooks[$name] as $hook) {
			@include_once($hook[0]);
			if(function_exists($hook[1])) {
				$func = $hook[1];
				$func($a,$data);
			}
			else {
				// remove orphan hooks
				q("delete from hook where hook = '%s' and file = '$s' and function = '%s' limit 1",
					dbesc($name),
					dbesc($hook[0]),
					dbesc($hook[1])
				);
			}
		}
	}

}


/*
 * parse plugin comment in search of plugin infos.
 * like
 * 	
 * 	 * Name: Plugin
 *   * Description: A plugin which plugs in
 * 	 * Version: 1.2.3
 *   * Author: John <profile url>
 *   * Author: Jane <email>
 *   * Compat: Red [(version)], Friendica [(version)]
 *   *
 */


function get_plugin_info($plugin){
	$info=Array(
		'name' => $plugin,
		'description' => "",
		'author' => array(),
		'version' => "",
		'compat' => ""
	);
	
	if (!is_file("addon/$plugin/$plugin.php")) return $info;
	
	$f = file_get_contents("addon/$plugin/$plugin.php");
	$r = preg_match("|/\*.*\*/|msU", $f, $m);
	
	if ($r){
		$ll = explode("\n", $m[0]);
		foreach( $ll as $l ) {
			$l = trim($l,"\t\n\r */");
			if ($l!=""){
				list($k,$v) = array_map("trim", explode(":",$l,2));
				$k= strtolower($k);
				if ($k=="author"){
					$r=preg_match("|([^<]+)<([^>]+)>|", $v, $m);
					if ($r) {
						$info['author'][] = array('name'=>$m[1], 'link'=>$m[2]);
					} else {
						$info['author'][] = array('name'=>$v);
					}
				} else {
					if (array_key_exists($k,$info)){
						$info[$k]=$v;
					}
				}
				
			}
		}
		
	}
	return $info;
}


/*
 * parse theme comment in search of theme infos.
 * like
 * 	
 * 	 * Name: My Theme
 *   * Description: My Cool Theme
 * 	 * Version: 1.2.3
 *   * Author: John <profile url>
 *   * Maintainer: Jane <profile url>
 *   * Compat: Friendica [(version)], Red [(version)]
 *   *
 */


function get_theme_info($theme){
	$info=Array(
		'name' => $theme,
		'description' => "",
		'author' => array(),
		'maintainer' => array(),
		'version' => "",
		'credits' => "",
		'compat' => "",
		'experimental' => false,
		'unsupported' => false
	);

	if(file_exists("view/theme/$theme/experimental"))
		$info['experimental'] = true;
	if(file_exists("view/theme/$theme/unsupported"))
		$info['unsupported'] = true;

	if (!is_file("view/theme/$theme/php/theme.php")) return $info;
	
	$f = file_get_contents("view/theme/$theme/php/theme.php");
	$r = preg_match("|/\*.*\*/|msU", $f, $m);
	
	
	if ($r){
		$ll = explode("\n", $m[0]);
		foreach( $ll as $l ) {
			$l = trim($l,"\t\n\r */");
			if ($l!=""){
				list($k,$v) = array_map("trim", explode(":",$l,2));
				$k= strtolower($k);
				if ($k=="author"){

					$r=preg_match("|([^<]+)<([^>]+)>|", $v, $m);
					if ($r) {
						$info['author'][] = array('name'=>$m[1], 'link'=>$m[2]);
					} else {
						$info['author'][] = array('name'=>$v);
					}
				}
				elseif ($k=="maintainer"){
					$r=preg_match("|([^<]+)<([^>]+)>|", $v, $m);
					if ($r) {
						$info['maintainer'][] = array('name'=>$m[1], 'link'=>$m[2]);
					} else {
						$info['maintainer'][] = array('name'=>$v);
					}
				} else {
					if (array_key_exists($k,$info)){
						$info[$k]=$v;
					}
				}
				
			}
		}
		
	}
	return $info;
}


function get_theme_screenshot($theme) {
	$a = get_app();
	$exts = array('.png','.jpg');
	foreach($exts as $ext) {
		if(file_exists('view/theme/' . $theme . '/img/screenshot' . $ext))
			return($a->get_baseurl() . '/view/theme/' . $theme . '/img/screenshot' . $ext);
	}
	return($a->get_baseurl() . '/images/blank.png');
}


// check service_class restrictions. If there are no service_classes defined, everything is allowed.
// if $usage is supplied, we check against a maximum count and return true if the current usage is 
// less than the subscriber plan allows. Otherwise we return boolean true or false if the property
// is allowed (or not) in this subscriber plan. An unset property for this service plan means 
// the property is allowed, so it is only necessary to provide negative properties for each plan, 
// or what the subscriber is not allowed to do. 


function service_class_allows($uid,$property,$usage = false) {

	if($uid == local_user()) {
		$service_class = $a->user['service_class'];
	}
	else {
		$r = q("select service_class from user where uid = %d limit 1",
			intval($uid)
		);
		if($r !== false and count($r)) {
			$service_class = $r[0]['service_class'];
		}
	}
	if(! x($service_class))
		return true; // everything is allowed

	$arr = get_config('service_class',$service_class);
	if(! is_array($arr) || (! count($arr)))
		return true;

	if($usage === false)
		return ((x($arr[$property])) ? (bool) $arr['property'] : true);
	else {
		if(! array_key_exists($property,$arr))
			return true;
		return (((intval($usage)) < intval($arr[$property])) ? true : false);
	}
}


function service_class_fetch($uid,$property) {

	if($uid == local_user()) {
		$service_class = $a->user['service_class'];
	}
	else {
		$r = q("select service_class from user where uid = %d limit 1",
			intval($uid)
		);
		if($r !== false and count($r)) {
			$service_class = $r[0]['service_class'];
		}
	}
	if(! x($service_class))
		return false; // everything is allowed

	$arr = get_config('service_class',$service_class);
	if(! is_array($arr) || (! count($arr)))
		return false;

	return((array_key_exists($property,$arr)) ? $arr[$property] : false);

}

function upgrade_link($bbcode = false) {
	$l = get_config('service_class','upgrade_link');
	if(! $l)
		return '';
	if($bbcode)
		$t = sprintf('[zrl=%s]' . t('Click here to upgrade.') . '[/zrl]', $l);
	else
		$t = sprintf('<a href="%s">' . t('Click here to upgrade.') . '</div>', $l);
	return $t;
}

function upgrade_message($bbcode = false) {
	$x = upgrade_link($bbcode);
	return t('This action exceeds the limits set by your subscription plan.') . (($x) ? ' ' . $x : '') ;
}

function upgrade_bool_message($bbcode = false) {
	$x = upgrade_link($bbcode);
	return t('This action is not available under your subscription plan.') . (($x) ? ' ' . $x : '') ;
}



function head_add_css($src,$media = 'screen') {
	get_app()->css_sources[] = array($src,$media);
}

function head_get_css() {
	$str = '';
	$sources = get_app()->css_sources;
	if(count($sources)) 
		foreach($sources as $source)
			$str .= format_css_if_exists($source);
	return $str;
}

function format_css_if_exists($source) {
	
	if(strpos($source[0],'/') !== false)
		$path = $source[0];
	else
		$path = theme_include($source[0]);

	if($path)
		return '<link rel="stylesheet" href="' . z_root() . '/' . $path . '" type="text/css" media="' . $source[1] . '" />' . "\r\n";
		
}


function head_add_js($src) {
	get_app()->js_sources[] = $src;
}

function head_get_js() {
	$str = '';
	$sources = get_app()->js_sources;
	if(count($sources)) 
		foreach($sources as $source)
			$str .= format_js_if_exists($source);
	return $str;
}

function format_js_if_exists($source) {

	if(strpos($source,'/') !== false)
		$path = $source;
	else
		$path = theme_include($source);
	if($path)
		return '<script src="' . z_root() . '/' . $path . '" ></script>' . "\r\n" ;

}


function theme_include($file, $root = '') {

	$a = get_app();

	// Make sure $root ends with a slash / if it's not blank
	if($root !== '' && $root[strlen($root)-1] !== '/')
		$root = $root . '/';

	$theme_info = $a->theme_info;

	if(array_key_exists('extends',$theme_info))
		$parent = $theme_info['extends'];
	else
		$parent = 'NOPATH';

	$theme = current_theme();

	$ext = substr($file,strrpos($file,'.')+1);

	$paths = array(
		"{$root}view/theme/$theme/$ext/$file",
		"{$root}view/theme/$parent/$ext/$file",
		"{$root}view/$ext/$file",
	);

	foreach($paths as $p) {
		// strpos() is faster than strstr when checking if one string is in another (http://php.net/manual/en/function.strstr.php)
		if(strpos($p,'NOPATH') !== false)
			continue;
		if(file_exists($p))
			return $p;
	}
	return '';
}




function get_intltext_template($s) {
	global $a;

	if(! isset($a->language))
		$a->language = 'en';

	$engine = '';
	if($a->get_template_engine() === 'smarty3')
		$engine = "/smarty3";

	$file = '';
	if(file_exists("view/{$a->language}$engine/$s"))
		$file = "view/{$a->language}$engine/$s";
	elseif(file_exists("view/en$engine/$s"))
		$file = "view/en$engine/$s";
	else
		$file = "view/tpl/$engine/$s";
	if($engine === '/smarty3') {
		$template = new FriendicaSmarty();
		$template->filename = $file;

		return $template;
	}
	else
		return file_get_contents($file);

}


function get_markup_template($s, $root = '') {

	$a = get_app();

	$template_eng = $a->get_template_engine();
	if($template_eng === 'internal') {
		$template_file = theme_include($s, $root);
		if($template_file)
			return file_get_contents($template_file);
	}
	else {
		$template_file = theme_include("$template_eng/$s", $root);

		if($template_file) {
			$template = new FriendicaSmarty();
			$template->filename = $template_file;

			return $template;
		}
	}	
}

