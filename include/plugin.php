<?php
/**
 * @file include/plugin.php
 *
 * @brief Some functions to handle addons and themes.
 */

require_once("include/smarty.php");


/**
 * @brief unloads an addon.
 *
 * @param string $plugin name of the addon
 */
function unload_plugin($plugin){
	logger("Addons: unloading " . $plugin, LOGGER_DEBUG);

	@include_once('addon/' . $plugin . '/' . $plugin . '.php');
	if(function_exists($plugin . '_unload')) {
		$func = $plugin . '_unload';
		$func();
	}
}

/**
 * @brief uninstalls an addon.
 *
 * @param string $plugin name of the addon
 * @return boolean
 */
function uninstall_plugin($plugin) {
	unload_plugin($plugin);

	if(! file_exists('addon/' . $plugin . '/' . $plugin . '.php'))
		return false;

	logger("Addons: uninstalling " . $plugin);
	//$t = @filemtime('addon/' . $plugin . '/' . $plugin . '.php');
	@include_once('addon/' . $plugin . '/' . $plugin . '.php');
	if(function_exists($plugin . '_uninstall')) {
		$func = $plugin . '_uninstall';
		$func();
	}

	q("DELETE FROM `addon` WHERE `name` = '%s' ",
		dbesc($plugin)
	);
}

/**
 * @brief installs an addon.
 *
 * @param string $plugin name of the addon
 * @return bool
 */
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

	$plugin_admin = (function_exists($plugin . '_plugin_admin') ? 1 : 0);

	q("INSERT INTO `addon` (`name`, `installed`, `timestamp`, `plugin_admin`) VALUES ( '%s', 1, %d , %d ) ",
		dbesc($plugin),
		intval($t),
		$plugin_admin
	);

	load_plugin($plugin);
}

/**
 * @brief loads an addon by it's name.
 *
 * @param string $plugin name of the addon
 * @return bool
 */
function load_plugin($plugin) {
	// silently fail if plugin was removed
	if(! file_exists('addon/' . $plugin . '/' . $plugin . '.php'))
		return false;

	logger("Addons: loading " . $plugin, LOGGER_DEBUG);
	//$t = @filemtime('addon/' . $plugin . '/' . $plugin . '.php');
	@include_once('addon/' . $plugin . '/' . $plugin . '.php');
	if(function_exists($plugin . '_load')) {
		$func = $plugin . '_load';
		$func();

		// we can add the following with the previous SQL
		// once most site tables have been updated.
		// This way the system won't fall over dead during the update.

		if(file_exists('addon/' . $plugin . '/.hidden')) {
			q("update addon set hidden = 1 where name = '%s'",
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

function plugin_is_installed($name) {
	$r = q("select name from addon where name = '%s' and installed = 1 limit 1",
		dbesc($name)
	);
	if($r)
		return true;

	return false;
}


// reload all updated plugins

function reload_plugins() {
	$plugins = get_config('system', 'addon');
	if(strlen($plugins)) {
		$r = q("SELECT * FROM `addon` WHERE `installed` = 1");
		if(count($r))
			$installed = $r;
		else
			$installed = array();

		$parr = explode(',', $plugins);

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
							q("UPDATE `addon` SET `timestamp` = %d WHERE `id` = %d",
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


/**
 * @brief registers a hook.
 *
 * @param string $hook the name of the hook
 * @param string $file the name of the file that hooks into
 * @param string $function the name of the function that the hook will call
 * @param int $priority A priority (defaults to 0)
 * @return mixed|bool
 */
function register_hook($hook, $file, $function, $priority = 0) {
	$r = q("SELECT * FROM `hook` WHERE `hook` = '%s' AND `file` = '%s' AND `function` = '%s' LIMIT 1",
		dbesc($hook),
		dbesc($file),
		dbesc($function)
	);
	if(count($r))
		return true;

	$r = q("INSERT INTO `hook` (`hook`, `file`, `function`, `priority`) VALUES ( '%s', '%s', '%s', '%s' )",
		dbesc($hook),
		dbesc($file),
		dbesc($function),
		dbesc($priority)
	);

	return $r;
}


/**
 * @brief unregisters a hook.
 * 
 * @param string $hook the name of the hook
 * @param string $file the name of the file that hooks into
 * @param string $function the name of the function that the hook called
 * @return array
 */
function unregister_hook($hook, $file, $function) {
	$r = q("DELETE FROM hook WHERE hook = '%s' AND `file` = '%s' AND `function` = '%s'",
		dbesc($hook),
		dbesc($file),
		dbesc($function)
	);

	return $r;
}


//
// It might not be obvious but themes can manually add hooks to the $a->hooks
// array in their theme_init() and use this to customise the app behaviour.  
// UPDATE: use insert_hook($hookname,$function_name) to do this
//


function load_hooks() {
	$a = get_app();
//	if(! is_array($a->hooks))
		$a->hooks = array();

	$r = q("SELECT * FROM hook WHERE true ORDER BY priority DESC");
	if($r) {
		foreach($r as $rr) {
			if(! array_key_exists($rr['hook'],$a->hooks))
				$a->hooks[$rr['hook']] = array();

			$a->hooks[$rr['hook']][] = array($rr['file'],$rr['function']);
		}
	}
//logger('hooks: ' . print_r($a->hooks,true));
}

/**
 * @brief Inserts a hook into a page request.
 *
 * Insert a short-lived hook into the running page request. 
 * Hooks are normally persistent so that they can be called 
 * across asynchronous processes such as delivery and poll
 * processes.
 *
 * insert_hook lets you attach a hook callback immediately
 * which will not persist beyond the life of this page request
 * or the current process. 
 *
 * @param string $hook
 *     name of hook to attach callback
 * @param string $fn
 *     function name of callback handler
 */ 
function insert_hook($hook, $fn) {
	$a = get_app();
	if(! is_array($a->hooks))
		$a->hooks = array();

	if(! array_key_exists($hook, $a->hooks))
		$a->hooks[$hook] = array();

	$a->hooks[$hook][] = array('', $fn);
}

/**
 * @brief Calls a hook.
 *
 * Use this function when you want to be able to allow a hook to manipulate
 * the provided data.
 *
 * @param string $name of the hook to call
 * @param string|array &$data to transmit to the callback handler
 */
function call_hooks($name, &$data = null) {
	$a = get_app();

	if((is_array($a->hooks)) && (array_key_exists($name, $a->hooks))) {
		foreach($a->hooks[$name] as $hook) {
			if($hook[0])
				@include_once($hook[0]);

			if(function_exists($hook[1])) {
				$func = $hook[1];
				$func($a, $data);
			} else {
				// remove orphan hooks
				q("DELETE FROM hook WHERE hook = '%s' AND file = '%s' AND function = '%s'",
					dbesc($name),
					dbesc($hook[0]),
					dbesc($hook[1])
				);
			}
		}
	}
}


/**
 * @brief Parse plugin comment in search of plugin infos.
 *
 * like
 * \code
 *   * Name: Plugin
 *   * Description: A plugin which plugs in
 *   * Version: 1.2.3
 *   * Author: John <profile url>
 *   * Author: Jane <email>
 *   * Compat: Red [(version)], Friendica [(version)]
 *   *
 *\endcode
 * @param string $plugin the name of the plugin
 * @return array with the plugin information
 */
function get_plugin_info($plugin){
	$m = array();
	$info = array(
		'name' => $plugin,
		'description' => '',
		'author' => array(),
		'version' => '',
		'compat' => ''
	);

	if (!is_file("addon/$plugin/$plugin.php"))
		return $info;

	$f = file_get_contents("addon/$plugin/$plugin.php");
	$r = preg_match("|/\*.*\*/|msU", $f, $m);

	if ($r){
		$ll = explode("\n", $m[0]);
		foreach( $ll as $l ) {
			$l = trim($l, "\t\n\r */");
			if ($l != ""){
				list($k, $v) = array_map("trim", explode(":", $l, 2));
				$k = strtolower($k);
				if ($k == 'author'){
					$r = preg_match("|([^<]+)<([^>]+)>|", $v, $m);
					if ($r) {
						$info['author'][] = array('name' => $m[1], 'link' => $m[2]);
					} else {
						$info['author'][] = array('name' => $v);
					}
				} else {
					if (array_key_exists($k, $info)){
						$info[$k] = $v;
					}
				}
			}
		}
	}

	return $info;
}


/**
 * @brief Parse theme comment in search of theme infos.
 *
 * like
 * \code
 *   * Name: My Theme
 *   * Description: My Cool Theme
 *   * Version: 1.2.3
 *   * Author: John <profile url>
 *   * Maintainer: Jane <profile url>
 *   * Compat: Friendica [(version)], Red [(version)]
 *   *
 * \endcode
 * @param string $theme the name of the theme
 * @return array
 */
function get_theme_info($theme){
	$m = array();
	$info = array(
		'name' => $theme,
		'description' => '',
		'author' => array(),
		'version' => '',
		'compat' => '',
		'credits' => '',
		'maintainer' => array(),
		'experimental' => false,
		'unsupported' => false
	);

	if(file_exists("view/theme/$theme/experimental"))
		$info['experimental'] = true;

	if(file_exists("view/theme/$theme/unsupported"))
		$info['unsupported'] = true;

	if (!is_file("view/theme/$theme/php/theme.php"))
		return $info;

	$f = file_get_contents("view/theme/$theme/php/theme.php");
	$r = preg_match("|/\*.*\*/|msU", $f, $m);

	if ($r){
		$ll = explode("\n", $m[0]);
		foreach( $ll as $l ) {
			$l = trim($l, "\t\n\r */");
			if ($l != ""){
				list($k, $v) = array_map("trim", explode(":", $l, 2));
				$k = strtolower($k);
				if ($k == 'author'){
					$r = preg_match("|([^<]+)<([^>]+)>|", $v, $m);
					if ($r) {
						$info['author'][] = array('name' => $m[1], 'link' => $m[2]);
					} else {
						$info['author'][] = array('name' => $v);
					}
				}
				elseif ($k == 'maintainer'){
					$r = preg_match("|([^<]+)<([^>]+)>|", $v, $m);
					if ($r) {
						$info['maintainer'][] = array('name' => $m[1], 'link' => $m[2]);
					} else {
						$info['maintainer'][] = array('name' => $v);
					}
				} else {
					if (array_key_exists($k, $info)){
						$info[$k] = $v;
					}
				}
			}
		}
	}

	return $info;
}

/**
 * @brief Returns the theme's screenshot.
 *
 * The screenshot is expected as view/theme/$theme/img/screenshot.[png|jpg].
 *
 * @param sring $theme The name of the theme
 * @return string
 */
function get_theme_screenshot($theme) {
	$a = get_app();
	$exts = array('.png', '.jpg');
	foreach($exts as $ext) {
		if(file_exists('view/theme/' . $theme . '/img/screenshot' . $ext))
			return($a->get_baseurl() . '/view/theme/' . $theme . '/img/screenshot' . $ext);
	}

	return($a->get_baseurl() . '/images/blank.png');
}

/**
 * @brief add CSS to \<head\>
 *
 * @param string $src
 * @param string $media change media attribute (default to 'screen')
 */
function head_add_css($src, $media = 'screen') {
	get_app()->css_sources[] = array($src, $media);
}

function head_remove_css($src, $media = 'screen') {
	$a = get_app();
	$index = array_search(array($src, $media), $a->css_sources);
	if ($index !== false)
		unset($a->css_sources[$index]);
}

function head_get_css() {
	$str = '';
	$sources = get_app()->css_sources;
	if (count($sources)) {
		foreach ($sources as $source)
			$str .= format_css_if_exists($source);
	}

	return $str;
}

function format_css_if_exists($source) {
	if (strpos($source[0], '/') !== false)
		$path = $source[0];
	else
		$path = theme_include($source[0]);

	if($path)
		return '<link rel="stylesheet" href="' . script_path() . '/' . $path . '" type="text/css" media="' . $source[1] . '">' . "\r\n";
}

function script_path() {
	if(x($_SERVER,'HTTPS') && $_SERVER['HTTPS'])
		$scheme = 'https';
	elseif(x($_SERVER,'SERVER_PORT') && (intval($_SERVER['SERVER_PORT']) == 443))
		$scheme = 'https';
	elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on')
		$scheme = 'https';
	else
		$scheme = 'http';

	if(x($_SERVER,'SERVER_NAME')) {
		$hostname = $_SERVER['SERVER_NAME'];
	}
	else {
		return z_root();
	}

	if(x($_SERVER,'SERVER_PORT') && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) {
		$hostname .= ':' . $_SERVER['SERVER_PORT'];
	}

	return $scheme . '://' . $hostname;
}

function head_add_js($src) {
	get_app()->js_sources[] = $src;
}

function head_remove_js($src) {
	$a = get_app();
	$index = array_search($src, $a->js_sources);
	if($index !== false)
		unset($a->js_sources[$index]);
}

function head_get_js() {
	$str = '';
	$sources = get_app()->js_sources;
	if(count($sources)) 
		foreach($sources as $source) {
			if($source === 'main.js')
				continue;
			$str .= format_js_if_exists($source);
		}
	return $str;
}

function head_get_main_js() {
	$str = '';
	$sources = array('main.js');
	if(count($sources)) 
		foreach($sources as $source)
			$str .= format_js_if_exists($source,true);
	return $str;
}

function format_js_if_exists($source) {
	if(strpos($source,'/') !== false)
		$path = $source;
	else
		$path = theme_include($source);
	if($path)
		return '<script src="' . script_path() . '/' . $path . '" ></script>' . "\r\n" ;
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
		"{$root}view/site/$ext/$file",
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


function get_intltext_template($s, $root = '') {
	$a = get_app();
	$t = $a->template_engine();

	$template = $t->get_intltext_template($s, $root);
	return $template;
}


function get_markup_template($s, $root = '') {
	$a = get_app();
	$t = $a->template_engine();
	$template = $t->get_markup_template($s, $root);
	return $template;
}
