<?php

set_time_limit(0);
ini_set('pcre.backtrack_limit', 250000);


define ( 'FRIENDIKA_VERSION',      '2.2.1030' );
define ( 'DFRN_PROTOCOL_VERSION',  '2.21'    );
define ( 'DB_UPDATE_VERSION',      1073      );

define ( 'EOL',                    "<br />\r\n"     );
define ( 'ATOM_TIME',              'Y-m-d\TH:i:s\Z' );
define ( 'DOWN_ARROW',             '&#x21e9;'       );

/**
 *
 * Image storage quality. Lower numbers save space at cost of image detail.
 * For ease of upgrade, please do not change here. Change jpeg quality with 
 * set_config('system','jpeg_quality',n) in .htconfig.php
 * where n is netween 1 and 100, and with very poor results below about 50 
 *
 */

define ( 'JPEG_QUALITY',            100  );         

/**
 * SSL redirection policies
 */

define ( 'SSL_POLICY_NONE',         0 );
define ( 'SSL_POLICY_FULL',         1 );
define ( 'SSL_POLICY_SELFSIGN',     2 );


/**
 * log levels
 */

define ( 'LOGGER_NORMAL',          0 );
define ( 'LOGGER_TRACE',           1 );
define ( 'LOGGER_DEBUG',           2 );
define ( 'LOGGER_DATA',            3 );
define ( 'LOGGER_ALL',             4 );

/**
 * registration policies
 */

define ( 'REGISTER_CLOSED',        0 );
define ( 'REGISTER_APPROVE',       1 );
define ( 'REGISTER_OPEN',          2 );

/**
 * relationship types
 * When used in contact records, this indicates that 'uid' has 
 * this relationship with contact['name']
 */

define ( 'REL_VIP',        1);     // other person is 'following' us
define ( 'REL_FAN',        2);     // we are 'following' other person
define ( 'REL_BUD',        3);     // mutual relationship

/**
 * Hook array order
 */
 
define ( 'HOOK_HOOK',      0);
define ( 'HOOK_FILE',      1);
define ( 'HOOK_FUNCTION',  2);

/**
 *
 * page/profile types
 *
 * PAGE_NORMAL is a typical personal profile account
 * PAGE_SOAPBOX automatically approves all friend requests as REL_FAN, (readonly)
 * PAGE_COMMUNITY automatically approves all friend requests as REL_FAN, but with 
 *      write access to wall and comments (no email and not included in page owner's ACL lists)
 * PAGE_FREELOVE automatically approves all friend requests as full friends (REL_BUD). 
 *
 */

define ( 'PAGE_NORMAL',            0 );
define ( 'PAGE_SOAPBOX',           1 );
define ( 'PAGE_COMMUNITY',         2 );
define ( 'PAGE_FREELOVE',          3 );

/**
 * Network and protocol family types 
 */

define ( 'NETWORK_DFRN',             'dfrn');    // Friendika, Mistpark, other DFRN implementations
define ( 'NETWORK_OSTATUS',          'stat');    // status.net, identi.ca, GNU-social, other OStatus implementations
define ( 'NETWORK_FEED',             'feed');    // RSS/Atom feeds with no known "post/notify" protocol
define ( 'NETWORK_DIASPORA',         'dspr');    // Diaspora
define ( 'NETWORK_MAIL',             'mail');    // IMAP/POP
define ( 'NETWORK_FACEBOOK',         'face');    // Facebook API     


/**
 * Maximum number of "people who like (or don't like) this"  that we will list by name
 */

define ( 'MAX_LIKERS',    75);

/**
 * email notification options
 */

define ( 'NOTIFY_INTRO',   0x0001 );
define ( 'NOTIFY_CONFIRM', 0x0002 );
define ( 'NOTIFY_WALL',    0x0004 );
define ( 'NOTIFY_COMMENT', 0x0008 );
define ( 'NOTIFY_MAIL',    0x0010 );

/**
 * various namespaces we may need to parse
 */

define ( 'NAMESPACE_DFRN' ,           'http://purl.org/macgirvin/dfrn/1.0' ); 
define ( 'NAMESPACE_THREAD' ,         'http://purl.org/syndication/thread/1.0' );
define ( 'NAMESPACE_TOMB' ,           'http://purl.org/atompub/tombstones/1.0' );
define ( 'NAMESPACE_ACTIVITY',        'http://activitystrea.ms/spec/1.0/' );
define ( 'NAMESPACE_ACTIVITY_SCHEMA', 'http://activitystrea.ms/schema/1.0/' );
define ( 'NAMESPACE_MEDIA',           'http://purl.org/syndication/atommedia' );
define ( 'NAMESPACE_SALMON_ME',       'http://salmon-protocol.org/ns/magic-env' );
define ( 'NAMESPACE_OSTATUSSUB',      'http://ostatus.org/schema/1.0/subscribe' );
define ( 'NAMESPACE_GEORSS',          'http://www.georss.org/georss' );
define ( 'NAMESPACE_POCO',            'http://portablecontacts.net/spec/1.0' );
define ( 'NAMESPACE_FEED',            'http://schemas.google.com/g/2010#updates-from' );
define ( 'NAMESPACE_OSTATUS',         'http://ostatus.org/schema/1.0' );
define ( 'NAMESPACE_STATUSNET',       'http://status.net/schema/api/1/' );

/**
 * activity stream defines
 */

define ( 'ACTIVITY_LIKE',        NAMESPACE_ACTIVITY_SCHEMA . 'like' );
define ( 'ACTIVITY_DISLIKE',     NAMESPACE_DFRN            . '/dislike' );
define ( 'ACTIVITY_OBJ_HEART',   NAMESPACE_DFRN            . '/heart' );

define ( 'ACTIVITY_FRIEND',      NAMESPACE_ACTIVITY_SCHEMA . 'make-friend' );
define ( 'ACTIVITY_FOLLOW',      NAMESPACE_ACTIVITY_SCHEMA . 'follow' );
define ( 'ACTIVITY_UNFOLLOW',    NAMESPACE_ACTIVITY_SCHEMA . 'stop-following' );
define ( 'ACTIVITY_POST',        NAMESPACE_ACTIVITY_SCHEMA . 'post' );
define ( 'ACTIVITY_UPDATE',      NAMESPACE_ACTIVITY_SCHEMA . 'update' );
define ( 'ACTIVITY_TAG',         NAMESPACE_ACTIVITY_SCHEMA . 'tag' );

define ( 'ACTIVITY_OBJ_COMMENT', NAMESPACE_ACTIVITY_SCHEMA . 'comment' );
define ( 'ACTIVITY_OBJ_NOTE',    NAMESPACE_ACTIVITY_SCHEMA . 'note' );
define ( 'ACTIVITY_OBJ_PERSON',  NAMESPACE_ACTIVITY_SCHEMA . 'person' );
define ( 'ACTIVITY_OBJ_PHOTO',   NAMESPACE_ACTIVITY_SCHEMA . 'photo' );
define ( 'ACTIVITY_OBJ_P_PHOTO', NAMESPACE_ACTIVITY_SCHEMA . 'profile-photo' );
define ( 'ACTIVITY_OBJ_ALBUM',   NAMESPACE_ACTIVITY_SCHEMA . 'photo-album' );
define ( 'ACTIVITY_OBJ_EVENT',   NAMESPACE_ACTIVITY_SCHEMA . 'event' );

/**
 * item weight for query ordering
 */

define ( 'GRAVITY_PARENT',       0);
define ( 'GRAVITY_LIKE',         3);
define ( 'GRAVITY_COMMENT',      6);

/**
 *
 * Reverse the effect of magic_quotes_gpc if it is enabled.
 * Please disable magic_quotes_gpc so we don't have to do this.
 * See http://php.net/manual/en/security.magicquotes.disabling.php
 *
 */

if (get_magic_quotes_gpc()) {
    $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
    while (list($key, $val) = each($process)) {
        foreach ($val as $k => $v) {
            unset($process[$key][$k]);
            if (is_array($v)) {
                $process[$key][stripslashes($k)] = $v;
                $process[] = &$process[$key][stripslashes($k)];
            } else {
                $process[$key][stripslashes($k)] = stripslashes($v);
            }
        }
    }
    unset($process);
}

/*
 * translation system
 */
require_once("include/pgettext.php");


/**
 *
 * class: App
 *
 * Our main application structure for the life of this page
 * Primarily deals with the URL that got us here
 * and tries to make some sense of it, and 
 * stores our page contents and config storage
 * and anything else that might need to be passed around 
 * before we spit the page out. 
 *
 */

if(! class_exists('App')) {
class App {

	public  $module_loaded = false;
	public  $query_string;
	public  $config;
	public  $page;
	public  $profile;
	public  $user;
	public  $cid;
	public  $contact;
	public  $contacts;
	public  $page_contact;
	public  $content;
	public  $data;
	public  $error = false;
	public  $cmd;
	public  $argv;
	public  $argc;
	public  $module;
	public  $pager;
	public  $strings;   
	public  $path;
	public  $hooks;
	public  $timezone;
	public  $interactive = true;
	public  $plugins;
	public  $apps;
	public  $identities;

	private $scheme;
	private $hostname;
	private $baseurl;
	private $db;

	private $curl_code;
	private $curl_headers;

	function __construct() {

		$this->config = array();
		$this->page = array();
		$this->pager= array();

		$this->query_string = '';

		$this->scheme = ((isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS']))	?  'https' : 'http' );

		if(x($_SERVER,'SERVER_NAME')) {
			$this->hostname = $_SERVER['SERVER_NAME'];

			/** 
			 * Figure out if we are running at the top of a domain
			 * or in a sub-directory and adjust accordingly
			 */

			$path = trim(dirname($_SERVER['SCRIPT_NAME']),'/\\');
			if(isset($path) && strlen($path) && ($path != $this->path))
				$this->path = $path;
		}

		set_include_path("include/$this->hostname" . PATH_SEPARATOR . 'include' . PATH_SEPARATOR . '.' );

		if((x($_SERVER,'QUERY_STRING')) && substr($_SERVER['QUERY_STRING'],0,2) === "q=")
			$this->query_string = substr($_SERVER['QUERY_STRING'],2);
		if(x($_GET,'q'))
			$this->cmd = trim($_GET['q'],'/\\');



		/**
		 *
		 * Break the URL path into C style argc/argv style arguments for our
		 * modules. Given "http://example.com/module/arg1/arg2", $this->argc
		 * will be 3 (integer) and $this->argv will contain:
		 *   [0] => 'module'
		 *   [1] => 'arg1'
		 *   [2] => 'arg2'
		 *
		 *
		 * There will always be one argument. If provided a naked domain
		 * URL, $this->argv[0] is set to "home".
		 *
		 */

		$this->argv = explode('/',$this->cmd);
		$this->argc = count($this->argv);
		if((array_key_exists('0',$this->argv)) && strlen($this->argv[0])) {
			$this->module = str_replace(".", "_", $this->argv[0]);
		}
		else {
			$this->argc = 1;
			$this->argv = array('home');
			$this->module = 'home';
		}

		/**
		 * Special handling for the webfinger/lrdd host XRD file
		 * Just spit out the contents and exit.
		 */

		if($this->cmd === '.well-known/host-meta') {
			require_once('include/hostxrd.php');
			hostxrd($this->get_baseurl());
			// NOTREACHED
		}

		/**
		 * See if there is any page number information, and initialise 
		 * pagination
		 */

		$this->pager['page'] = ((x($_GET,'page')) ? $_GET['page'] : 1);
		$this->pager['itemspage'] = 50;
		$this->pager['start'] = ($this->pager['page'] * $this->pager['itemspage']) - $this->pager['itemspage'];
		$this->pager['total'] = 0;
	}

	function get_baseurl($ssl = false) {

		$scheme = $this->scheme;

		if(x($this->config,'ssl_policy')) {
			if(($ssl) || ($this->config['ssl_policy'] == SSL_POLICY_FULL)) 
				$scheme = 'https';
			if(($this->config['ssl_policy'] == SSL_POLICY_SELFSIGN) && (local_user() || x($_POST,'auth-params')))
				$scheme = 'https';
		}

		$this->baseurl = $scheme . "://" . $this->hostname . ((isset($this->path) && strlen($this->path)) ? '/' . $this->path : '' );
		return $this->baseurl;
	}

	function set_baseurl($url) {
		$parsed = @parse_url($url);

		$this->baseurl = $url;

		if($parsed) {		
			$this->scheme = $parsed['scheme'];

			$this->hostname = $parsed['host'];
			if(x($parsed,'port'))
				$this->hostname .= ':' . $parsed['port'];
			if(x($parsed,'path'))
				$this->path = trim($parsed['path'],'\\/');
		}

	}

	function get_hostname() {
		return $this->hostname;
	}

	function set_hostname($h) {
		$this->hostname = $h;
	}

	function set_path($p) {
		$this->path = trim(trim($p),'/');
	} 

	function get_path() {
		return $this->path;
	}

	function set_pager_total($n) {
		$this->pager['total'] = intval($n);
	}

	function set_pager_itemspage($n) {
		$this->pager['itemspage'] = intval($n);
		$this->pager['start'] = ($this->pager['page'] * $this->pager['itemspage']) - $this->pager['itemspage'];

	} 

	function init_pagehead() {
		$this->page['title'] = $this->config['sitename'];
		$tpl = file_get_contents('view/head.tpl');
		$this->page['htmlhead'] = replace_macros($tpl,array(
			'$baseurl' => $this->get_baseurl(),
			'$generator' => 'Friendika' . ' ' . FRIENDIKA_VERSION,
			'$delitem' => t('Delete this item?'),
			'$comment' => t('Comment')
		));
	}

	function set_curl_code($code) {
		$this->curl_code = $code;
	}

	function get_curl_code() {
		return $this->curl_code;
	}

	function set_curl_headers($headers) {
		$this->curl_headers = $headers;
	}

	function get_curl_headers() {
		return $this->curl_headers;
	}


}}

// retrieve the App structure
// useful in functions which require it but don't get it passed to them

if(! function_exists('get_app')) {
function get_app() {
	global $a;
	return $a;
}};


// Multi-purpose function to check variable state.
// Usage: x($var) or $x($array,'key')
// returns false if variable/key is not set
// if variable is set, returns 1 if has 'non-zero' value, otherwise returns 0.
// e.g. x('') or x(0) returns 0;

if(! function_exists('x')) {
function x($s,$k = NULL) {
	if($k != NULL) {
		if((is_array($s)) && (array_key_exists($k,$s))) {
			if($s[$k])
				return (int) 1;
			return (int) 0;
		}
		return false;
	}
	else {		
		if(isset($s)) {
			if($s) {
				return (int) 1;
			}
			return (int) 0;
		}
		return false;
	}
}}

// called from db initialisation if db is dead.

if(! function_exists('system_unavailable')) {
function system_unavailable() {
	include('system_unavailable.php');
	system_down();
	killme();
}}


// install and uninstall plugin
if (! function_exists('uninstall_plugin')){
function uninstall_plugin($plugin){
	logger("Addons: uninstalling " . $plugin);
	q("DELETE FROM `addon` WHERE `name` = '%s' LIMIT 1",
		dbesc($plugin)
	);

	@include_once('addon/' . $plugin . '/' . $plugin . '.php');
	if(function_exists($plugin . '_uninstall')) {
		$func = $plugin . '_uninstall';
		$func();
	}
}}

if (! function_exists('install_plugin')){
function install_plugin($plugin){
	logger("Addons: installing " . $plugin);
	$t = filemtime('addon/' . $plugin . '/' . $plugin . '.php');
	@include_once('addon/' . $plugin . '/' . $plugin . '.php');
	if(function_exists($plugin . '_install')) {
		$func = $plugin . '_install';
		$func();
		
		$plugin_admin = (function_exists($plugin."_plugin_admin")?1:0);
		
		$r = q("INSERT INTO `addon` (`name`, `installed`, `timestamp`, `plugin_admin`) VALUES ( '%s', 1, %d , %d ) ",
			dbesc($plugin),
			intval($t),
			$plugin_admin
		);
	}
}}

// Primarily involved with database upgrade, but also sets the 
// base url for use in cmdline programs which don't have
// $_SERVER variables, and synchronising the state of installed plugins.


if(! function_exists('check_config')) {
function check_config(&$a) {

	$build = get_config('system','build');
	if(! x($build))
		$build = set_config('system','build',DB_UPDATE_VERSION);

	$url = get_config('system','url');

	// if the url isn't set or the stored url is radically different 
	// than the currently visited url, store the current value accordingly.
	// "Radically different" ignores common variations such as http vs https 
	// and www.example.com vs example.com.

	if((! x($url)) || (! link_compare($url,$a->get_baseurl())))
		$url = set_config('system','url',$a->get_baseurl());

	if($build != DB_UPDATE_VERSION) {
		$stored = intval($build);
		$current = intval(DB_UPDATE_VERSION);
		if(($stored < $current) && file_exists('update.php')) {

			// We're reporting a different version than what is currently installed.
			// Run any existing update scripts to bring the database up to current.

			require_once('update.php');

			// make sure that boot.php and update.php are the same release, we might be
			// updating right this very second and the correct version of the update.php
			// file may not be here yet. This can happen on a very busy site.

			if(DB_UPDATE_VERSION == UPDATE_VERSION) {

				for($x = $stored; $x < $current; $x ++) {
					if(function_exists('update_' . $x)) {
						$func = 'update_' . $x;
						$func($a);
					}
				}
				set_config('system','build', DB_UPDATE_VERSION);
			}
		}
	}

	/**
	 *
	 * Synchronise plugins:
	 *
	 * $a->config['system']['addon'] contains a comma-separated list of names
	 * of plugins/addons which are used on this system. 
	 * Go through the database list of already installed addons, and if we have
	 * an entry, but it isn't in the config list, call the uninstall procedure
	 * and mark it uninstalled in the database (for now we'll remove it).
	 * Then go through the config list and if we have a plugin that isn't installed,
	 * call the install procedure and add it to the database.
	 *
	 */

	$r = q("SELECT * FROM `addon` WHERE `installed` = 1");
	if(count($r))
		$installed = $r;
	else
		$installed = array();

	$plugins = get_config('system','addon');
	$plugins_arr = array();

	if($plugins)
		$plugins_arr = explode(',',str_replace(' ', '',$plugins));

	$a->plugins = $plugins_arr;

	$installed_arr = array();

	if(count($installed)) {
		foreach($installed as $i) {
			if(! in_array($i['name'],$plugins_arr)) {
				uninstall_plugin($i['name']);
			}
			else
				$installed_arr[] = $i['name'];
		}
	}

	if(count($plugins_arr)) {
		foreach($plugins_arr as $p) {
			if(! in_array($p,$installed_arr)) {
				install_plugin($p);
			}
		}
	}


	load_hooks();

	return;
}}

// reload all updated plugins

if(! function_exists('reload_plugins')) {
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
				
				$t = filemtime('addon/' . $pl . '/' . $pl . '.php');
				foreach($installed as $i) {
					if(($i['name'] == $pl) && ($i['timestamp'] != $t)) {	
						logger('Reloading plugin: ' . $i['name']);
						@include_once('addon/' . $pl . '/' . $pl . '.php');

						if(function_exists($pl . '_uninstall')) {
							$func = $pl . '_uninstall';
							$func();
						}
						if(function_exists($pl . '_install')) {
							$func = $pl . '_install';
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
}}
				


// This is our template processor.
// $s is the string requiring macro substitution.
// $r is an array of key value pairs (search => replace)
// returns substituted string.
// WARNING: this is pretty basic, and doesn't properly handle search strings that are substrings of each other.
// For instance if 'test' => "foo" and 'testing' => "bar", testing could become either bar or fooing, 
// depending on the order in which they were declared in the array.   

require_once("include/template_processor.php");

if(! function_exists('replace_macros')) {  
function replace_macros($s,$r) {
	global $t;
	
	return $t->replace($s,$r);

}}


// curl wrapper. If binary flag is true, return binary
// results. 

if(! function_exists('fetch_url')) {
function fetch_url($url,$binary = false, &$redirects = 0) {

	$a = get_app();

	$ch = curl_init($url);
	if(($redirects > 8) || (! $ch)) 
		return false;

	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Friendika");

	$curl_time = intval(get_config('system','curl_timeout'));
	curl_setopt($ch, CURLOPT_TIMEOUT, (($curl_time !== false) ? $curl_time : 60));

	// by default we will allow self-signed certs
	// but you can override this

	$check_cert = get_config('system','verifyssl');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, (($check_cert) ? true : false));

	$prx = get_config('system','proxy');
	if(strlen($prx)) {
		curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
		curl_setopt($ch, CURLOPT_PROXY, $prx);
		$prxusr = get_config('system','proxyuser');
		if(strlen($prxusr))
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, $prxusr);
	}
	if($binary)
		curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);

	$a->set_curl_code(0);

	// don't let curl abort the entire application
	// if it throws any errors.

	$s = @curl_exec($ch);

	$base = $s;
	$curl_info = curl_getinfo($ch);
	$http_code = $curl_info['http_code'];

	$header = '';

	// Pull out multiple headers, e.g. proxy and continuation headers
	// allow for HTTP/2.x without fixing code

	while(preg_match('/^HTTP\/[1-2].+? [1-5][0-9][0-9]/',$base)) {
		$chunk = substr($base,0,strpos($base,"\r\n\r\n")+4);
		$header .= $chunk;
		$base = substr($base,strlen($chunk));
	}

	if($http_code == 301 || $http_code == 302 || $http_code == 303 || $http_code == 307) {
        $matches = array();
        preg_match('/(Location:|URI:)(.*?)\n/', $header, $matches);
        $url = trim(array_pop($matches));
        $url_parsed = @parse_url($url);
        if (isset($url_parsed)) {
            $redirects++;
            return fetch_url($url,$binary,$redirects);
        }
    }

	$a->set_curl_code($http_code);

	$body = substr($s,strlen($header));

	$a->set_curl_headers($header);

	curl_close($ch);
	return($body);
}}

// post request to $url. $params is an array of post variables.

if(! function_exists('post_url')) {
function post_url($url,$params, $headers = null, &$redirects = 0) {
	$a = get_app();
	$ch = curl_init($url);
	if(($redirects > 8) || (! $ch)) 
		return false;

	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch, CURLOPT_POST,1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
	curl_setopt($ch, CURLOPT_USERAGENT, "Friendika");

	$curl_time = intval(get_config('system','curl_timeout'));
	curl_setopt($ch, CURLOPT_TIMEOUT, (($curl_time !== false) ? $curl_time : 60));

	if(!is_array($headers)) {
		$headers = array('Expect:');
	} else {
		if(!in_array('Expect:', $headers)) {
			array_push($headers, 'Expect:');
		}
	}

	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	$check_cert = get_config('system','verifyssl');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, (($check_cert) ? true : false));
	$prx = get_config('system','proxy');
	if(strlen($prx)) {
		curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
		curl_setopt($ch, CURLOPT_PROXY, $prx);
		$prxusr = get_config('system','proxyuser');
		if(strlen($prxusr))
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, $prxusr);
	}

	$a->set_curl_code(0);

	// don't let curl abort the entire application
	// if it throws any errors.

	$s = @curl_exec($ch);

	$base = $s;
	$curl_info = curl_getinfo($ch);
	$http_code = $curl_info['http_code'];

	$header = '';

	// Pull out multiple headers, e.g. proxy and continuation headers
	// allow for HTTP/2.x without fixing code

	while(preg_match('/^HTTP\/[1-2].+? [1-5][0-9][0-9]/',$base)) {
		$chunk = substr($base,0,strpos($base,"\r\n\r\n")+4);
		$header .= $chunk;
		$base = substr($base,strlen($chunk));
	}

	if($http_code == 301 || $http_code == 302 || $http_code == 303) {
        $matches = array();
        preg_match('/(Location:|URI:)(.*?)\n/', $header, $matches);
        $url = trim(array_pop($matches));
        $url_parsed = @parse_url($url);
        if (isset($url_parsed)) {
            $redirects++;
            return post_url($url,$binary,$headers,$redirects);
        }
    }
	$a->set_curl_code($http_code);
	$body = substr($s,strlen($header));

	$a->set_curl_headers($header);

	curl_close($ch);
	return($body);
}}

// random hash, 64 chars

if(! function_exists('random_string')) {
function random_string() {
	return(hash('sha256',uniqid(rand(),true)));
}}

/**
 * This is our primary input filter. 
 *
 * The high bit hack only involved some old IE browser, forget which (IE5/Mac?)
 * that had an XSS attack vector due to stripping the high-bit on an 8-bit character
 * after cleansing, and angle chars with the high bit set could get through as markup.
 * 
 * This is now disabled because it was interfering with some legitimate unicode sequences 
 * and hopefully there aren't a lot of those browsers left. 
 *
 * Use this on any text input where angle chars are not valid or permitted
 * They will be replaced with safer brackets. This may be filtered further
 * if these are not allowed either.   
 *
 */

if(! function_exists('notags')) {
function notags($string) {

	return(str_replace(array("<",">"), array('[',']'), $string));

//  High-bit filter no longer used
//	return(str_replace(array("<",">","\xBA","\xBC","\xBE"), array('[',']','','',''), $string));
}}

// use this on "body" or "content" input where angle chars shouldn't be removed,
// and allow them to be safely displayed.

if(! function_exists('escape_tags')) {
function escape_tags($string) {

	return(htmlspecialchars($string));
}}

// wrapper for adding a login box. If $register == true provide a registration
// link. This will most always depend on the value of $a->config['register_policy'].
// returns the complete html for inserting into the page

if(! function_exists('login')) {
function login($register = false) {
	$o = "";
	$register_tpl = (($register) ? get_markup_template("register-link.tpl") : "");
	
	$register_html = replace_macros($register_tpl,array(
		'$title' => t('Create a New Account'),
		'$desc' => t('Register')
	));

	$noid = get_config('system','no_openid');
	if($noid) {
		$classname = 'no-openid';
		$namelabel = t('Nickname or Email address: ');
		$passlabel = t('Password: ');
		$login     = t('Login');
	}
	else {
		$classname = 'openid';
		$namelabel = t('Nickname/Email/OpenID: ');
		$passlabel = t("Password \x28if not OpenID\x29: ");
		$login     = t('Login');
	}
	$lostpass = t('Forgot your password?');
	$lostlink = t('Password Reset');

	if(local_user()) {
		$tpl = get_markup_template("logout.tpl");
	}
	else {
		$tpl = get_markup_template("login.tpl");

	}

	$o = '<script type="text/javascript"> $(document).ready(function() { $("#login-name").focus();} );</script>';	

	$o .= replace_macros($tpl,array(
		'$logout'        => t('Logout'),
		'$register_html' => $register_html, 
		'$classname'     => $classname,
		'$namelabel'     => $namelabel,
		'$passlabel'     => $passlabel,
		'$login'         => $login,
		'$lostpass'      => $lostpass,
		'$lostlink'      => $lostlink 
	));

	return $o;
}}

// generate a string that's random, but usually pronounceable. 
// used to generate initial passwords

if(! function_exists('autoname')) {
function autoname($len) {

	$vowels = array('a','a','ai','au','e','e','e','ee','ea','i','ie','o','ou','u'); 
	if(mt_rand(0,5) == 4)
		$vowels[] = 'y';

	$cons = array(
			'b','bl','br',
			'c','ch','cl','cr',
			'd','dr',
			'f','fl','fr',
			'g','gh','gl','gr',
			'h',
			'j',
			'k','kh','kl','kr',
			'l',
			'm',
			'n',
			'p','ph','pl','pr',
			'qu',
			'r','rh',
			's','sc','sh','sm','sp','st',
			't','th','tr',
			'v',
			'w','wh',
			'x',
			'z','zh'
			);

	$midcons = array('ck','ct','gn','ld','lf','lm','lt','mb','mm', 'mn','mp',
				'nd','ng','nk','nt','rn','rp','rt');

	$noend = array('bl', 'br', 'cl','cr','dr','fl','fr','gl','gr',
				'kh', 'kl','kr','mn','pl','pr','rh','tr','qu','wh');

	$start = mt_rand(0,2);
  	if($start == 0)
    		$table = $vowels;
  	else
    		$table = $cons;

	$word = '';

	for ($x = 0; $x < $len; $x ++) {
  		$r = mt_rand(0,count($table) - 1);
  		$word .= $table[$r];
  
  		if($table == $vowels)
    			$table = array_merge($cons,$midcons);
  		else
    			$table = $vowels;

	}

	$word = substr($word,0,$len);

	foreach($noend as $noe) {
  		if((strlen($word) > 2) && (substr($word,-2) == $noe)) {
    			$word = substr($word,0,-1);
    			break;
  		}
	}
	if(substr($word,-1) == 'q')
		$word = substr($word,0,-1);    
	return $word;
}}

// Used to end the current process, after saving session state. 

if(! function_exists('killme')) {
function killme() {
	session_write_close();
	exit;
}}

// redirect to another URL and terminate this process.

if(! function_exists('goaway')) {
function goaway($s) {
	header("Location: $s");
	killme();
}}

// Generic XML return
// Outputs a basic dfrn XML status structure to STDOUT, with a <status> variable 
// of $st and an optional text <message> of $message and terminates the current process. 

if(! function_exists('xml_status')) {
function xml_status($st, $message = '') {

	$xml_message = ((strlen($message)) ? "\t<message>" . xmlify($message) . "</message>\r\n" : '');

	if($st)
		logger('xml_status returning non_zero: ' . $st . " message=" . $message);

	header( "Content-type: text/xml" );
	echo '<?xml version="1.0" encoding="UTF-8"?>'."\r\n";
	echo "<result>\r\n\t<status>$st</status>\r\n$xml_message</result>\r\n";
	killme();
}}

// Returns the uid of locally logged in user or false.

if(! function_exists('local_user')) {
function local_user() {
	if((x($_SESSION,'authenticated')) && (x($_SESSION,'uid')))
		return intval($_SESSION['uid']);
	return false;
}}

// Returns contact id of authenticated site visitor or false

if(! function_exists('remote_user')) {
function remote_user() {
	if((x($_SESSION,'authenticated')) && (x($_SESSION,'visitor_id')))
		return intval($_SESSION['visitor_id']);
	return false;
}}

// contents of $s are displayed prominently on the page the next time
// a page is loaded. Usually used for errors or alerts.

if(! function_exists('notice')) {
function notice($s) {
	$a = get_app();
	if($a->interactive)
		$_SESSION['sysmsg'] .= $s;
}}
if(! function_exists('info')) {
function info($s) {
	$a = get_app();
	if($a->interactive)
		$_SESSION['sysmsg_info'] .= $s;
}}


// wrapper around config to limit the text length of an incoming message

if(! function_exists('get_max_import_size')) {
function get_max_import_size() {
	global $a;
	return ((x($a->config,'max_import_size')) ? $a->config['max_import_size'] : 0 );
}}


// escape text ($str) for XML transport
// returns escaped text.

if(! function_exists('xmlify')) {
function xmlify($str) {
	$buffer = '';
	
	for($x = 0; $x < strlen($str); $x ++) {
		$char = $str[$x];
        
		switch( $char ) {

			case "\r" :
				break;
			case "&" :
				$buffer .= '&amp;';
				break;
			case "'" :
				$buffer .= '&apos;';
				break;
			case "\"" :
				$buffer .= '&quot;';
				break;
			case '<' :
				$buffer .= '&lt;';
				break;
			case '>' :
				$buffer .= '&gt;';
				break;
			case "\n" :
				$buffer .= "\n";
				break;
			default :
				$buffer .= $char;
				break;
		}	
	}
	$buffer = trim($buffer);
	return($buffer);
}}

// undo an xmlify
// pass xml escaped text ($s), returns unescaped text

if(! function_exists('unxmlify')) {
function unxmlify($s) {
	$ret = str_replace('&amp;','&', $s);
	$ret = str_replace(array('&lt;','&gt;','&quot;','&apos;'),array('<','>','"',"'"),$ret);
	return $ret;	
}}

// convenience wrapper, reverse the operation "bin2hex"

if(! function_exists('hex2bin')) {
function hex2bin($s) {
	if(! ctype_xdigit($s)) {
		logger('hex2bin: illegal input: ' . print_r(debug_backtrace(), true));
		return($s);
	}

	return(pack("H*",$s));
}}

// Automatic pagination.
// To use, get the count of total items.
// Then call $a->set_pager_total($number_items);
// Optionally call $a->set_pager_itemspage($n) to the number of items to display on each page
// Then call paginate($a) after the end of the display loop to insert the pager block on the page
// (assuming there are enough items to paginate).
// When using with SQL, the setting LIMIT %d, %d => $a->pager['start'],$a->pager['itemspage']
// will limit the results to the correct items for the current page. 
// The actual page handling is then accomplished at the application layer. 

if(! function_exists('paginate')) {
function paginate(&$a) {
	$o = '';
	$stripped = preg_replace('/(&page=[0-9]*)/','',$a->query_string);
	$stripped = str_replace('q=','',$stripped);
	$stripped = trim($stripped,'/');
	$pagenum = $a->pager['page'];
	$url = $a->get_baseurl() . '/' . $stripped;


	  if($a->pager['total'] > $a->pager['itemspage']) {
		$o .= '<div class="pager">';
    		if($a->pager['page'] != 1)
			$o .= '<span class="pager_prev">'."<a href=\"$url".'&page='.($a->pager['page'] - 1).'">' . t('prev') . '</a></span> ';

		$o .=  "<span class=\"pager_first\"><a href=\"$url"."&page=1\">" . t('first') . "</a></span> ";

    		$numpages = $a->pager['total'] / $a->pager['itemspage'];

			$numstart = 1;
    		$numstop = $numpages;

    		if($numpages > 14) {
      			$numstart = (($pagenum > 7) ? ($pagenum - 7) : 1);
      			$numstop = (($pagenum > ($numpages - 7)) ? $numpages : ($numstart + 14));
    		}
   
		for($i = $numstart; $i <= $numstop; $i++){
      			if($i == $a->pager['page'])
				$o .= '<span class="pager_current">'.(($i < 10) ? '&nbsp;'.$i : $i);
			else
				$o .= "<span class=\"pager_n\"><a href=\"$url"."&page=$i\">".(($i < 10) ? '&nbsp;'.$i : $i)."</a>";
			$o .= '</span> ';
		}

		if(($a->pager['total'] % $a->pager['itemspage']) != 0) {
			if($i == $a->pager['page'])
				$o .= '<span class="pager_current">'.(($i < 10) ? '&nbsp;'.$i : $i);
			else
				$o .= "<span class=\"pager_n\"><a href=\"$url"."&page=$i\">".(($i < 10) ? '&nbsp;'.$i : $i)."</a>";
			$o .= '</span> ';
		}

		$lastpage = (($numpages > intval($numpages)) ? intval($numpages)+1 : $numpages);
		$o .= "<span class=\"pager_last\"><a href=\"$url"."&page=$lastpage\">" . t('last') . "</a></span> ";

    		if(($a->pager['total'] - ($a->pager['itemspage'] * $a->pager['page'])) > 0)
			$o .= '<span class="pager_next">'."<a href=\"$url"."&page=".($a->pager['page'] + 1).'">' . t('next') . '</a></span>';
		$o .= '</div>'."\r\n";
	}
	return $o;
}}

// Turn user/group ACLs stored as angle bracketed text into arrays

if(! function_exists('expand_acl')) {
function expand_acl($s) {
	// turn string array of angle-bracketed elements into numeric array
	// e.g. "<1><2><3>" => array(1,2,3);
	$ret = array();

	if(strlen($s)) {
		$t = str_replace('<','',$s);
		$a = explode('>',$t);
		foreach($a as $aa) {
			if(intval($aa))
				$ret[] = intval($aa);
		}
	}
	return $ret;
}}		

// Used to wrap ACL elements in angle brackets for storage 

if(! function_exists('sanitise_acl')) {
function sanitise_acl(&$item) {
	if(intval($item))
		$item = '<' . intval(notags(trim($item))) . '>';
	else
		unset($item);
}}

// retrieve a "family" of config variables from database to cached storage

if(! function_exists('load_config')) {
function load_config($family) {
	global $a;
	$r = q("SELECT * FROM `config` WHERE `cat` = '%s'",
		dbesc($family)
	);
	if(count($r)) {
		foreach($r as $rr) {
			$k = $rr['k'];
			if ($rr['cat'] === 'config') {
				$a->config[$k] = $rr['v'];
			} else {
				$a->config[$family][$k] = $rr['v'];
			}
		}
	}
}}

// get a particular config variable given the family name
// and key. Returns false if not set.
// $instore is only used by the set_config function
// to determine if the key already exists in the DB
// If a key is found in the DB but doesn't exist in
// local config cache, pull it into the cache so we don't have
// to hit the DB again for this item.

if(! function_exists('get_config')) {
function get_config($family, $key, $instore = false) {

	global $a;

	if(! $instore) {
		if(isset($a->config[$family][$key])) {
			if($a->config[$family][$key] === '!<unset>!') {
				return false;
			}
			return $a->config[$family][$key];
		}
	}
	$ret = q("SELECT `v` FROM `config` WHERE `cat` = '%s' AND `k` = '%s' LIMIT 1",
		dbesc($family),
		dbesc($key)
	);
	if(count($ret)) {
		// manage array value
		$val = (preg_match("|^a:[0-9]+:{.*}$|", $ret[0]['v'])?unserialize( $ret[0]['v']):$ret[0]['v']);
		$a->config[$family][$key] = $val;
		return $val;
	}
	else {
		$a->config[$family][$key] = '!<unset>!';
	}
	return false;
}}

// Store a config value ($value) in the category ($family)
// under the key ($key)
// Return the value, or false if the database update failed

if(! function_exists('set_config')) {
function set_config($family,$key,$value) {
	global $a;
	
	// manage array value
	$dbvalue = (is_array($value)?serialize($value):$value);

	if(get_config($family,$key,true) === false) {
		$a->config[$family][$key] = $value;
		$ret = q("INSERT INTO `config` ( `cat`, `k`, `v` ) VALUES ( '%s', '%s', '%s' ) ",
			dbesc($family),
			dbesc($key),
			dbesc($dbvalue)
		);
		if($ret) 
			return $value;
		return $ret;
	}
	
	$ret = q("UPDATE `config` SET `v` = '%s' WHERE `cat` = '%s' AND `k` = '%s' LIMIT 1",
		dbesc($dbvalue),
		dbesc($family),
		dbesc($key)
	);

	$a->config[$family][$key] = $value;

	if($ret)
		return $value;
	return $ret;
}}


if(! function_exists('load_pconfig')) {
function load_pconfig($uid,$family) {
	global $a;
	$r = q("SELECT * FROM `pconfig` WHERE `cat` = '%s' AND `uid` = %d",
		dbesc($family),
		intval($uid)
	);
	if(count($r)) {
		foreach($r as $rr) {
			$k = $rr['k'];
			$a->config[$uid][$family][$k] = $rr['v'];
		}
	}
}}



if(! function_exists('get_pconfig')) {
function get_pconfig($uid,$family, $key, $instore = false) {

	global $a;

	if(! $instore) {
		if(isset($a->config[$uid][$family][$key])) {
			if($a->config[$uid][$family][$key] === '!<unset>!') {
				return false;
			}
			return $a->config[$uid][$family][$key];
		}
	}

	$ret = q("SELECT `v` FROM `pconfig` WHERE `uid` = %d AND `cat` = '%s' AND `k` = '%s' LIMIT 1",
		intval($uid),
		dbesc($family),
		dbesc($key)
	);

	if(count($ret)) {
		$a->config[$uid][$family][$key] = $ret[0]['v'];
		return $ret[0]['v'];
	}
	else {
		$a->config[$uid][$family][$key] = '!<unset>!';
	}
	return false;
}}

if(! function_exists('del_config')) {
function del_config($family,$key) {

	global $a;
	if(x($a->config[$family],$key))
		unset($a->config[$family][$key]);
	$ret = q("DELETE FROM `config` WHERE `cat` = '%s' AND `k` = '%s' LIMIT 1",
		dbesc($cat),
		dbesc($key)
	);
	return $ret;
}}



// Same as above functions except these are for personal config storage and take an
// additional $uid argument.

if(! function_exists('set_pconfig')) {
function set_pconfig($uid,$family,$key,$value) {

	global $a;

	if(get_pconfig($uid,$family,$key,true) === false) {
		$a->config[$uid][$family][$key] = $value;
		$ret = q("INSERT INTO `pconfig` ( `uid`, `cat`, `k`, `v` ) VALUES ( %d, '%s', '%s', '%s' ) ",
			intval($uid),
			dbesc($family),
			dbesc($key),
			dbesc($value)
		);
		if($ret) 
			return $value;
		return $ret;
	}
	$ret = q("UPDATE `pconfig` SET `v` = '%s' WHERE `uid` = %d AND `cat` = '%s' AND `k` = '%s' LIMIT 1",
		dbesc($value),
		intval($uid),
		dbesc($family),
		dbesc($key)
	);

	$a->config[$uid][$family][$key] = $value;

	if($ret)
		return $value;
	return $ret;
}}

if(! function_exists('del_pconfig')) {
function del_pconfig($uid,$family,$key) {

	global $a;
	if(x($a->config[$uid][$family],$key))
		unset($a->config[$uid][$family][$key]);
	$ret = q("DELETE FROM `pconfig` WHERE `uid` = %d AND `cat` = '%s' AND `k` = '%s' LIMIT 1",
		intval($uid),
		dbesc($family),
		dbesc($key)
	);
	return $ret;
}}


// convert an XML document to a normalised, case-corrected array
// used by webfinger

if(! function_exists('convert_xml_element_to_array')) {
function convert_xml_element_to_array($xml_element, &$recursion_depth=0) {

        // If we're getting too deep, bail out
        if ($recursion_depth > 512) {
                return(null);
        }

        if (!is_string($xml_element) &&
        !is_array($xml_element) &&
        (get_class($xml_element) == 'SimpleXMLElement')) {
                $xml_element_copy = $xml_element;
                $xml_element = get_object_vars($xml_element);
        }

        if (is_array($xml_element)) {
                $result_array = array();
                if (count($xml_element) <= 0) {
                        return (trim(strval($xml_element_copy)));
                }

                foreach($xml_element as $key=>$value) {

                        $recursion_depth++;
                        $result_array[strtolower($key)] =
                convert_xml_element_to_array($value, $recursion_depth);
                        $recursion_depth--;
                }
                if ($recursion_depth == 0) {
                        $temp_array = $result_array;
                        $result_array = array(
                                strtolower($xml_element_copy->getName()) => $temp_array,
                        );
                }

                return ($result_array);

        } else {
                return (trim(strval($xml_element)));
        }
}}

// Given an email style address, perform webfinger lookup and 
// return the resulting DFRN profile URL, or if no DFRN profile URL
// is located, returns an OStatus subscription template (prefixed 
// with the string 'stat:' to identify it as on OStatus template).
// If this isn't an email style address just return $s.
// Return an empty string if email-style addresses but webfinger fails,
// or if the resultant personal XRD doesn't contain a supported 
// subscription/friend-request attribute.

if(! function_exists('webfinger_dfrn')) {
function webfinger_dfrn($s) {
	if(! strstr($s,'@')) {
		return $s;
	}
	$links = webfinger($s);
	logger('webfinger_dfrn: ' . $s . ':' . print_r($links,true), LOGGER_DATA);
	if(count($links)) {
		foreach($links as $link)
			if($link['@attributes']['rel'] === NAMESPACE_DFRN)
				return $link['@attributes']['href'];
		foreach($links as $link)
			if($link['@attributes']['rel'] === NAMESPACE_OSTATUSSUB)
				return 'stat:' . $link['@attributes']['template'];		
	}
	return '';
}}

// Given an email style address, perform webfinger lookup and 
// return the array of link attributes from the personal XRD file.
// On error/failure return an empty array.


if(! function_exists('webfinger')) {
function webfinger($s) {
	$host = '';
	if(strstr($s,'@')) {
		$host = substr($s,strpos($s,'@') + 1);
	}
	if(strlen($host)) {
		$tpl = fetch_lrdd_template($host);
		logger('webfinger: lrdd template: ' . $tpl);
		if(strlen($tpl)) {
			$pxrd = str_replace('{uri}', urlencode('acct:' . $s), $tpl);
			logger('webfinger: pxrd: ' . $pxrd);
			$links = fetch_xrd_links($pxrd);
			if(! count($links)) {
				// try with double slashes
				$pxrd = str_replace('{uri}', urlencode('acct://' . $s), $tpl);
				logger('webfinger: pxrd: ' . $pxrd);
				$links = fetch_xrd_links($pxrd);
			}
			return $links;
		}
	}
	return array();
}}

if(! function_exists('lrdd')) {
function lrdd($uri) {

	$a = get_app();

	// default priority is host priority, host-meta first

	$priority = 'host';

	// All we have is an email address. Resource-priority is irrelevant
	// because our URI isn't directly resolvable.

	if(strstr($uri,'@')) {	
		return(webfinger($uri));
	}

	// get the host meta file

	$host = @parse_url($uri);

	if($host) {
		$url  = ((x($host,'scheme')) ? $host['scheme'] : 'http') . '://';
		$url .= $host['host'] . '/.well-known/host-meta' ;
	}
	else
		return array();

	logger('lrdd: constructed url: ' . $url);

	$xml = fetch_url($url);
	$headers = $a->get_curl_headers();

	if (! $xml)
		return array();

	logger('lrdd: host_meta: ' . $xml, LOGGER_DATA);

	$h = parse_xml_string($xml);
	if(! $h)
		return array();

	$arr = convert_xml_element_to_array($h);

	if(isset($arr['xrd']['property'])) {
		$property = $arr['crd']['property'];
		if(! isset($property[0]))
			$properties = array($property);
		else
			$properties = $property;
		foreach($properties as $prop)
			if((string) $prop['@attributes'] === 'http://lrdd.net/priority/resource')
				$priority = 'resource';
	} 

	// save the links in case we need them

	$links = array();

	if(isset($arr['xrd']['link'])) {
		$link = $arr['xrd']['link'];
		if(! isset($link[0]))
			$links = array($link);
		else
			$links = $link;
	}

	// do we have a template or href?

	if(count($links)) {
		foreach($links as $link) {
			if($link['@attributes']['rel'] && attribute_contains($link['@attributes']['rel'],'lrdd')) {
				if(x($link['@attributes'],'template'))
					$tpl = $link['@attributes']['template'];
				elseif(x($link['@attributes'],'href'))
					$href = $link['@attributes']['href'];
			}
		}		
	}

	if((! isset($tpl)) || (! strpos($tpl,'{uri}')))
		$tpl = '';

	if($priority === 'host') {
		if(strlen($tpl)) 
			$pxrd = str_replace('{uri}', urlencode($uri), $tpl);
		elseif(isset($href))
			$pxrd = $href;
		if(isset($pxrd)) {
			logger('lrdd: (host priority) pxrd: ' . $pxrd);
			$links = fetch_xrd_links($pxrd);
			return $links;
		}

		$lines = explode("\n",$headers);
		if(count($lines)) {
			foreach($lines as $line) {				
				if((stristr($line,'link:')) && preg_match('/<([^>].*)>.*rel\=[\'\"]lrdd[\'\"]/',$line,$matches)) {
					return(fetch_xrd_links($matches[1]));
					break;
				}
			}
		}
	}


	// priority 'resource'


	$html = fetch_url($uri);
	$headers = $a->get_curl_headers();
	logger('lrdd: headers=' . $headers, LOGGER_DEBUG);

	// don't try and parse raw xml as html
	if(! strstr($html,'<?xml')) {
		require_once('library/HTML5/Parser.php');
		$dom = @HTML5_Parser::parse($html);

		if($dom) {
			$items = $dom->getElementsByTagName('link');
			foreach($items as $item) {
				$x = $item->getAttribute('rel');
				if($x == "lrdd") {
					$pagelink = $item->getAttribute('href');
					break;
				}
			}
		}
	}

	if(isset($pagelink))
		return(fetch_xrd_links($pagelink));

	// next look in HTTP headers

	$lines = explode("\n",$headers);
	if(count($lines)) {
		foreach($lines as $line) {				
			// TODO alter the following regex to support multiple relations (space separated)
			if((stristr($line,'link:')) && preg_match('/<([^>].*)>.*rel\=[\'\"]lrdd[\'\"]/',$line,$matches)) {
				$pagelink = $matches[1];
				break;
			}
			// don't try and run feeds through the html5 parser
			if(stristr($line,'content-type:') && ((stristr($line,'application/atom+xml')) || (stristr($line,'application/rss+xml'))))
				return array();
			if(stristr($html,'<rss') || stristr($html,'<feed'))
				return array();
		}
	}

	if(isset($pagelink))
		return(fetch_xrd_links($pagelink));

	// If we haven't found any links, return the host xrd links (which we have already fetched)

	if(isset($links))
		return $links;

	return array();

}}



// Given a host name, locate the LRDD template from that
// host. Returns the LRDD template or an empty string on
// error/failure.

if(! function_exists('fetch_lrdd_template')) {
function fetch_lrdd_template($host) {
	$tpl = '';

	$url1 = 'https://' . $host . '/.well-known/host-meta' ;
	$url2 = 'http://' . $host . '/.well-known/host-meta' ;
	$links = fetch_xrd_links($url1);
	logger('fetch_lrdd_template from: ' . $url1);
	logger('template (https): ' . print_r($links,true));
	if(! count($links)) {
		logger('fetch_lrdd_template from: ' . $url2);
		$links = fetch_xrd_links($url2);
		logger('template (http): ' . print_r($links,true));
	}
	if(count($links)) {
		foreach($links as $link)
			if($link['@attributes']['rel'] && $link['@attributes']['rel'] === 'lrdd')
				$tpl = $link['@attributes']['template'];
	}
	if(! strpos($tpl,'{uri}'))
		$tpl = '';
	return $tpl;
}}

// Given a URL, retrieve the page as an XRD document.
// Return an array of links.
// on error/failure return empty array.

if(! function_exists('fetch_xrd_links')) {
function fetch_xrd_links($url) {


	$xml = fetch_url($url);
	if (! $xml)
		return array();

	logger('fetch_xrd_links: ' . $xml, LOGGER_DATA);
	$h = parse_xml_string($xml);
	if(! $h)
		return array();

	$arr = convert_xml_element_to_array($h);

	$links = array();

	if(isset($arr['xrd']['link'])) {
		$link = $arr['xrd']['link'];
		if(! isset($link[0]))
			$links = array($link);
		else
			$links = $link;
	}
	if(isset($arr['xrd']['alias'])) {
		$alias = $arr['xrd']['alias'];
		if(! isset($alias[0]))
			$aliases = array($alias);
		else
			$aliases = $alias;
		foreach($aliases as $alias) {
			$links[]['@attributes'] = array('rel' => 'alias' , 'href' => $alias);
		}
	}

	logger('fetch_xrd_links: ' . print_r($links,true), LOGGER_DATA);

	return $links;

}}

// Convert an ACL array to a storable string

if(! function_exists('perms2str')) {
function perms2str($p) {
	$ret = '';
	$tmp = $p;
	if(is_array($tmp)) {
		array_walk($tmp,'sanitise_acl');
		$ret = implode('',$tmp);
	}
	return $ret;
}}

// generate a guaranteed unique (for this domain) item ID for ATOM
// safe from birthday paradox

if(! function_exists('item_new_uri')) {
function item_new_uri($hostname,$uid) {

	do {
		$dups = false;
		$hash = random_string();

		$uri = "urn:X-dfrn:" . $hostname . ':' . $uid . ':' . $hash;

		$r = q("SELECT `id` FROM `item` WHERE `uri` = '%s' LIMIT 1",
			dbesc($uri));
		if(count($r))
			$dups = true;
	} while($dups == true);
	return $uri;
}}

// Generate a guaranteed unique photo ID.
// safe from birthday paradox

if(! function_exists('photo_new_resource')) {
function photo_new_resource() {

	do {
		$found = false;
		$resource = hash('md5',uniqid(mt_rand(),true));
		$r = q("SELECT `id` FROM `photo` WHERE `resource-id` = '%s' LIMIT 1",
			dbesc($resource)
		);
		if(count($r))
			$found = true;
	} while($found == true);
	return $resource;
}}


// Take a URL from the wild, prepend http:// if necessary
// and check DNS to see if it's real
// return true if it's OK, false if something is wrong with it

if(! function_exists('validate_url')) {
function validate_url(&$url) {
	if(substr($url,0,4) != 'http')
		$url = 'http://' . $url;
	$h = @parse_url($url);

	if(($h) && (dns_get_record($h['host'], DNS_A + DNS_CNAME + DNS_PTR))) {
		return true;
	}
	return false;
}}

// checks that email is an actual resolvable internet address

if(! function_exists('validate_email')) {
function validate_email($addr) {

	if(! strpos($addr,'@'))
		return false;
	$h = substr($addr,strpos($addr,'@') + 1);

	if(($h) && (dns_get_record($h, DNS_A + DNS_CNAME + DNS_PTR + DNS_MX))) {
		return true;
	}
	return false;
}}

// Check $url against our list of allowed sites,
// wildcards allowed. If allowed_sites is unset return true;
// If url is allowed, return true.
// otherwise, return false

if(! function_exists('allowed_url')) {
function allowed_url($url) {

	$h = @parse_url($url);

	if(! $h) {
		return false;
	}

	$str_allowed = get_config('system','allowed_sites');
	if(! $str_allowed)
		return true;

	$found = false;

	$host = strtolower($h['host']);

	// always allow our own site

	if($host == strtolower($_SERVER['SERVER_NAME']))
		return true;

	$fnmatch = function_exists('fnmatch');
	$allowed = explode(',',$str_allowed);

	if(count($allowed)) {
		foreach($allowed as $a) {
			$pat = strtolower(trim($a));
			if(($fnmatch && fnmatch($pat,$host)) || ($pat == $host)) {
				$found = true; 
				break;
			}
		}
	}
	return $found;
}}

// check if email address is allowed to register here.
// Compare against our list (wildcards allowed).
// Returns false if not allowed, true if allowed or if
// allowed list is not configured.

if(! function_exists('allowed_email')) {
function allowed_email($email) {


	$domain = strtolower(substr($email,strpos($email,'@') + 1));
	if(! $domain)
		return false;

	$str_allowed = get_config('system','allowed_email');
	if(! $str_allowed)
		return true;

	$found = false;

	$fnmatch = function_exists('fnmatch');
	$allowed = explode(',',$str_allowed);

	if(count($allowed)) {
		foreach($allowed as $a) {
			$pat = strtolower(trim($a));
			if(($fnmatch && fnmatch($pat,$domain)) || ($pat == $domain)) {
				$found = true; 
				break;
			}
		}
	}
	return $found;
}}



// wrapper to load a view template, checking for alternate
// languages before falling back to the default

// obsolete, deprecated.

if(! function_exists('load_view_file')) {
function load_view_file($s) {
	global $lang, $a;
	if(! isset($lang))
		$lang = 'en';
	$b = basename($s);
	$d = dirname($s);
	if(file_exists("$d/$lang/$b"))
		return file_get_contents("$d/$lang/$b");
	
	$theme = current_theme();
	
	if(file_exists("$d/theme/$theme/$b"))
		return file_get_contents("$d/theme/$theme/$b");
			
	return file_get_contents($s);
}}

if(! function_exists('get_intltext_template')) {
function get_intltext_template($s) {
	global $lang;

	if(! isset($lang))
		$lang = 'en';

	if(file_exists("view/$lang/$s"))
		return file_get_contents("view/$lang/$s");
	elseif(file_exists("view/en/$s"))
		return file_get_contents("view/en/$s");
	else
		return file_get_contents("view/$s");
}}

if(! function_exists('get_markup_template')) {
function get_markup_template($s) {

	$theme = current_theme();
	
	if(file_exists("view/theme/$theme/$s"))
		return file_get_contents("view/theme/$theme/$s");
	else
		return file_get_contents("view/$s");

}}





// for html,xml parsing - let's say you've got
// an attribute foobar="class1 class2 class3"
// and you want to find out if it contains 'class3'.
// you can't use a normal sub string search because you
// might match 'notclass3' and a regex to do the job is 
// possible but a bit complicated. 
// pass the attribute string as $attr and the attribute you 
// are looking for as $s - returns true if found, otherwise false

if(! function_exists('attribute_contains')) {
function attribute_contains($attr,$s) {
	$a = explode(' ', $attr);
	if(count($a) && in_array($s,$a))
		return true;
	return false;
}}

if(! function_exists('logger')) {
function logger($msg,$level = 0) {
	$debugging = get_config('system','debugging');
	$loglevel  = intval(get_config('system','loglevel'));
	$logfile   = get_config('system','logfile');

	if((! $debugging) || (! $logfile) || ($level > $loglevel))
		return;
	
	@file_put_contents($logfile, datetime_convert() . ':' . session_id() . ' ' . $msg . "\n", FILE_APPEND);
	return;
}}


if(! function_exists('activity_match')) {
function activity_match($haystack,$needle) {
	if(($haystack === $needle) || ((basename($needle) === $haystack) && strstr($needle,NAMESPACE_ACTIVITY_SCHEMA)))
		return true;
	return false;
}}


// Pull out all #hashtags and @person tags from $s;
// We also get @person@domain.com - which would make 
// the regex quite complicated as tags can also
// end a sentence. So we'll run through our results
// and strip the period from any tags which end with one.
// Returns array of tags found, or empty array.


if(! function_exists('get_tags')) {
function get_tags($s) {
	$ret = array();

	// ignore anything in a code block

	$s = preg_replace('/\[code\](.*?)\[\/code\]/sm','',$s);

	if(preg_match_all('/([@#][^ \x0D\x0A,:?]+ [^ \x0D\x0A,:?]+)([ \x0D\x0A,:?]|$)/',$s,$match)) {
		foreach($match[1] as $mtch) {
			if(strstr($mtch,"]")) {
				// we might be inside a bbcode color tag - leave it alone
				continue;
			}
			if(substr($mtch,-1,1) === '.')
				$ret[] = substr($mtch,0,-1);
			else
				$ret[] = $mtch;
		}
	}

	if(preg_match_all('/([@#][^ \x0D\x0A,:?]+)([ \x0D\x0A,:?]|$)/',$s,$match)) {
		foreach($match[1] as $mtch) {
			if(strstr($mtch,"]")) {
				// we might be inside a bbcode color tag - leave it alone
				continue;
			}
			if(substr($mtch,-1,1) === '.')
				$ret[] = substr($mtch,0,-1);
			else
				$ret[] = $mtch;
		}
	}
	return $ret;
}}


// quick and dirty quoted_printable encoding

if(! function_exists('qp')) {
function qp($s) {
return str_replace ("%","=",rawurlencode($s));
}} 



if(! function_exists('get_mentions')) {
function get_mentions($item) {
	$o = '';
	if(! strlen($item['tag']))
		return $o;

	$arr = explode(',',$item['tag']);
	foreach($arr as $x) {
		$matches = null;
		if(preg_match('/@\[url=([^\]]*)\]/',$x,$matches)) {
			$o .= "\t\t" . '<link rel="mentioned" href="' . $matches[1] . '" />' . "\r\n";
			$o .= "\t\t" . '<link rel="ostatus:attention" href="' . $matches[1] . '" />' . "\r\n";
		}
	}
	return $o;
}}

if(! function_exists('contact_block')) {
function contact_block() {
	$o = '';
	$a = get_app();

	$shown = get_pconfig($a->profile['uid'],'system','display_friend_count');
	if(! $shown)
		$shown = 24;

	if((! is_array($a->profile)) || ($a->profile['hide-friends']))
		return $o;
	$r = q("SELECT COUNT(*) AS `total` FROM `contact` WHERE `uid` = %d AND `self` = 0 AND `blocked` = 0 and `pending` = 0",
			intval($a->profile['uid'])
	);
	if(count($r)) {
		$total = intval($r[0]['total']);
	}
	if(! $total) {
		$o .= '<h4 class="contact-h4">' . t('No contacts') . '</h4>';
		return $o;
	}
	$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 0 AND `blocked` = 0 and `pending` = 0 ORDER BY RAND() LIMIT %d",
			intval($a->profile['uid']),
			intval($shown)
	);
	if(count($r)) {
		$o .= '<h4 class="contact-h4">' .  sprintf( tt('%d Contact','%d Contacts', $total),$total) . '</h4><div id="contact-block">';
		foreach($r as $rr) {
			$o .= micropro($rr,true,'mpfriend');
		}
		$o .= '</div><div id="contact-block-end"></div>';
		$o .=  '<div id="viewcontacts"><a id="viewcontacts-link" href="viewcontacts/' . $a->profile['nickname'] . '">' . t('View Contacts') . '</a></div>';
		
	}

	$arr = array('contacts' => $r, 'output' => $o);

	call_hooks('contact_block_end', $arr);
	return $o;

}}

if(! function_exists('micropro')) {
function micropro($contact, $redirect = false, $class = '', $textmode = false) {

	if($class)
		$class = ' ' . $class;

	$url = $contact['url'];
	$sparkle = '';

	if($redirect) {
		$a = get_app();
		$redirect_url = $a->get_baseurl() . '/redir/' . $contact['id'];
		if(local_user() && ($contact['uid'] == local_user()) && ($contact['network'] === 'dfrn')) {
			$url = $redirect_url;
			$sparkle = ' sparkle';
		}
	}
	$click = ((x($contact,'click')) ? ' onclick="' . $contact['click'] . '" ' : '');
	if($click)
		$url = '';
	if($textmode) {
		return '<div class="contact-block-textdiv' . $class . '"><a class="contact-block-link' . $class . $sparkle 
			. (($click) ? ' fakelink' : '') . '" '
			. (($url) ? ' href="' . $url . '"' : '') . $click
			. '" title="' . $contact['name'] . ' [' . $contact['url'] . ']" alt="' . $contact['name'] 
			. '" >'. $contact['name'] . '</a></div>' . "\r\n";
	}
	else {
		return '<div class="contact-block-div' . $class . '"><a class="contact-block-link' . $class . $sparkle 
			. (($click) ? ' fakelink' : '') . '" '
			. (($url) ? ' href="' . $url . '"' : '') . $click . ' ><img class="contact-block-img' . $class . $sparkle . '" src="' 
			. $contact['micro'] . '" title="' . $contact['name'] . ' [' . $contact['url'] . ']" alt="' . $contact['name'] 
			. '" /></a></div>' . "\r\n";
	}
}}



if(! function_exists('search')) {
function search($s,$id='search-box',$url='/search') {
	$a = get_app();
	$o  = '<div id="' . $id . '">';
	$o .= '<form action="' . $a->get_baseurl() . $url . '" method="get" >';
	$o .= '<input type="text" name="search" id="search-text" value="' . $s .'" />';
	$o .= '<input type="submit" name="submit" id="search-submit" value="' . t('Search') . '" />'; 
	$o .= '</form></div>';
	return $o;
}}

if(! function_exists('valid_email')) {
function valid_email($x){
	if(preg_match('/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$/',$x))
		return true;
	return false;
}}


if(! function_exists('gravatar_img')) {
function gravatar_img($email) {
	$size = 175;
	$opt = 'identicon';   // psuedo-random geometric pattern if not found
	$rating = 'pg';
	$hash = md5(trim(strtolower($email)));
	
	$url = 'http://www.gravatar.com/avatar/' . $hash . '.jpg' 
		. '?s=' . $size . '&d=' . $opt . '&r=' . $rating;

	logger('gravatar: ' . $email . ' ' . $url);
	return $url;
}}

if(! function_exists('aes_decrypt')) {
function aes_decrypt($val,$ky)
{
    $key="\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
    for($a=0;$a<strlen($ky);$a++)
      $key[$a%16]=chr(ord($key[$a%16]) ^ ord($ky[$a]));
    $mode = MCRYPT_MODE_ECB;
    $enc = MCRYPT_RIJNDAEL_128;
    $dec = @mcrypt_decrypt($enc, $key, $val, $mode, @mcrypt_create_iv( @mcrypt_get_iv_size($enc, $mode), MCRYPT_DEV_URANDOM ) );
    return rtrim($dec,(( ord(substr($dec,strlen($dec)-1,1))>=0 and ord(substr($dec, strlen($dec)-1,1))<=16)? chr(ord( substr($dec,strlen($dec)-1,1))):null));
}}


if(! function_exists('aes_encrypt')) {
function aes_encrypt($val,$ky)
{
    $key="\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
    for($a=0;$a<strlen($ky);$a++)
      $key[$a%16]=chr(ord($key[$a%16]) ^ ord($ky[$a]));
    $mode=MCRYPT_MODE_ECB;
    $enc=MCRYPT_RIJNDAEL_128;
    $val=str_pad($val, (16*(floor(strlen($val) / 16)+(strlen($val) % 16==0?2:1))), chr(16-(strlen($val) % 16)));
    return mcrypt_encrypt($enc, $key, $val, $mode, mcrypt_create_iv( mcrypt_get_iv_size($enc, $mode), MCRYPT_DEV_URANDOM));
}} 


/**
 *
 * Function: linkify
 *
 * Replace naked text hyperlink with HTML formatted hyperlink
 *
 */

if(! function_exists('linkify')) {
function linkify($s) {
	$s = preg_replace("/(https?\:\/\/[a-zA-Z0-9\:\/\-\?\&\.\=\_\~\#\'\%\$\!\+]*)/", ' <a href="$1" target="external-link">$1</a>', $s);
	return($s);
}}


/**
 * 
 * Function: smilies
 *
 * Description:
 * Replaces text emoticons with graphical images
 *
 * @Parameter: string $s
 *
 * Returns string
 */

if(! function_exists('smilies')) {
function smilies($s) {
	$a = get_app();

	return str_replace(
	array( '&lt;3', '&lt;/3', '&lt;\\3', ':-)', ':)', ';-)', ':-(', ':(', ':-P', ':P', ':-"', ':-x', ':-X', ':-D', '8-|', '8-O'),
	array(
		'<img src="' . $a->get_baseurl() . '/images/smiley-heart.gif" alt="<3" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-brokenheart.gif" alt="</3" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-brokenheart.gif" alt="<\\3" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-smile.gif" alt=":-)" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-smile.gif" alt=":)" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-wink.gif" alt=";-)" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-frown.gif" alt=":-(" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-frown.gif" alt=":(" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-tongue-out.gif" alt=":-P" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-tongue-out.gif" alt=":P" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-kiss.gif" alt=":-\"" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-kiss.gif" alt=":-x" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-kiss.gif" alt=":-X" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-laughing.gif" alt=":-D" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-surprised.gif" alt="8-|" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-surprised.gif" alt="8-O" />'
	), $s);
}}


/**
 *
 * Function : profile_load
 * @parameter App    $a
 * @parameter string $nickname
 * @parameter int    $profile
 *
 * Summary: Loads a profile into the page sidebar. 
 * The function requires a writeable copy of the main App structure, and the nickname
 * of a registered local account.
 *
 * If the viewer is an authenticated remote viewer, the profile displayed is the
 * one that has been configured for his/her viewing in the Contact manager.
 * Passing a non-zero profile ID can also allow a preview of a selected profile
 * by the owner.
 *
 * Profile information is placed in the App structure for later retrieval.
 * Honours the owner's chosen theme for display. 
 *
 */

if(! function_exists('profile_load')) {
function profile_load(&$a, $nickname, $profile = 0) {
	if(remote_user()) {
		$r = q("SELECT `profile-id` FROM `contact` WHERE `id` = %d LIMIT 1",
			intval($_SESSION['visitor_id']));
		if(count($r))
			$profile = $r[0]['profile-id'];
	} 

	$r = null;

	if($profile) {
		$profile_int = intval($profile);
		$r = q("SELECT `profile`.`uid` AS `profile_uid`, `profile`.* , `user`.* FROM `profile` 
			LEFT JOIN `user` ON `profile`.`uid` = `user`.`uid`
			WHERE `user`.`nickname` = '%s' AND `profile`.`id` = %d LIMIT 1",
			dbesc($nickname),
			intval($profile_int)
		);
	}
	if(! count($r)) {	
		$r = q("SELECT `profile`.`uid` AS `profile_uid`, `profile`.* , `user`.* FROM `profile` 
			LEFT JOIN `user` ON `profile`.`uid` = `user`.`uid`
			WHERE `user`.`nickname` = '%s' AND `profile`.`is-default` = 1 LIMIT 1",
			dbesc($nickname)
		);
	}

	if(($r === false) || (! count($r))) {
		notice( t('No profile') . EOL );
		$a->error = 404;
		return;
	}

	$a->profile = $r[0];


	$a->page['title'] = $a->profile['name'] . " @ " . $a->config['sitename'];
	$_SESSION['theme'] = $a->profile['theme'];

	if(! (x($a->page,'aside')))
		$a->page['aside'] = '';

	$a->page['aside'] .= profile_sidebar($a->profile);
	$a->page['aside'] .= contact_block();

	return;
}}


/**
 *
 * Function: profile_sidebar
 *
 * Formats a profile for display in the sidebar.
 * It is very difficult to templatise the HTML completely
 * because of all the conditional logic.
 *
 * @parameter: array $profile
 *
 * Returns HTML string stuitable for sidebar inclusion
 * Exceptions: Returns empty string if passed $profile is wrong type or not populated
 *
 */


if(! function_exists('profile_sidebar')) {
function profile_sidebar($profile) {

	$o = '';
	$location = '';
	$address = false;

	if((! is_array($profile)) && (! count($profile)))
		return $o;

	call_hooks('profile_sidebar_enter', $profile);

	$fullname = '<div class="fn">' . $profile['name'] . '</div>';

	$pdesc = '<div class="title">' . $profile['pdesc'] . '</div>';

	$tabs = '';

	$photo = '<div id="profile-photo-wrapper"><img class="photo" src="' . $profile['photo'] . '" alt="' . $profile['name'] . '" /></div>';

	// don't show connect link to yourself
	
	$connect = (($profile['uid'] != local_user()) ? '<li><a id="dfrn-request-link" href="dfrn_request/' . $profile['nickname'] . '">' . t('Connect') . '</a></li>' : '');

	// don't show connect link to authenticated visitors either

	if((remote_user()) && ($_SESSION['visitor_visiting'] == $profile['uid']))
		$connect = ''; 

	if((x($profile,'address') == 1) 
		|| (x($profile,'locality') == 1) 
		|| (x($profile,'region') == 1) 
		|| (x($profile,'postal-code') == 1) 
		|| (x($profile,'country-name') == 1))
		$address = true;

	if($address) {
		$location .= '<div class="location"><span class="location-label">' . t('Location:') . '</span> <div class="adr">';
		$location .= ((x($profile,'address') == 1) ? '<div class="street-address">' . $profile['address'] . '</div>' : '');
		$location .= (((x($profile,'locality') == 1) || (x($profile,'region') == 1) || (x($profile,'postal-code') == 1)) 
			? '<span class="city-state-zip"><span class="locality">' . $profile['locality'] . '</span>' 
			. ((x($profile['locality']) == 1) ? t(', ') : '') 
			. '<span class="region">' . $profile['region'] . '</span>'
			. ' <span class="postal-code">' . $profile['postal-code'] . '</span></span>' : '');
		$location .= ((x($profile,'country-name') == 1) ? ' <span class="country-name">' . $profile['country-name'] . '</span>' : '');  
		$location .= '</div></div><div class="profile-clear"></div>';

	}

	$gender = ((x($profile,'gender') == 1) ? '<div class="mf"><span class="gender-label">' . t('Gender:') . '</span> <span class="x-gender">' . $profile['gender'] . '</span></div><div class="profile-clear"></div>' : '');

	$pubkey = ((x($profile,'pubkey') == 1) ? '<div class="key" style="display:none;">' . $profile['pubkey'] . '</div>' : '');

	$marital = ((x($profile,'marital') == 1) ? '<div class="marital"><span class="marital-label"><span class="heart">&hearts;</span> ' . t('Status:') . ' </span><span class="marital-text">' . $profile['marital'] . '</span></div><div class="profile-clear"></div>' : '');

	$homepage = ((x($profile,'homepage') == 1) ? '<div class="homepage"><span class="homepage-label">' . t('Homepage:') . ' </span><span class="homepage-url">' . linkify($profile['homepage']) . '</span></div><div class="profile-clear"></div>' : '');

	if($profile['hidewall'] && (! local_user()) && (! remote_user())) {
		$location = $gender = $marital = $homepage = '';
	}

	$tpl = get_markup_template('profile_vcard.tpl');

	$o .= replace_macros($tpl, array(
		'$fullname' => $fullname,
		'$pdesc'    => $pdesc,
		'$tabs'     => $tabs,
		'$photo'    => $photo,
		'$connect'  => $connect,		
		'$location' => $location,
		'$gender'   => $gender,
		'$pubkey'   => $pubkey,
		'$marital'  => $marital,
		'$homepage' => $homepage
	));


	$arr = array('profile' => &$profile, 'entry' => &$o);

	call_hooks('profile_sidebar', $arr);

	return $o;
}}


if(! function_exists('register_hook')) {
function register_hook($hook,$file,$function) {

	$r = q("SELECT * FROM `hook` WHERE `hook` = '%s' AND `file` = '%s' AND `function` = '%s' LIMIT 1",
		dbesc($hook),
		dbesc($file),
		dbesc($function)
	);
	if(count($r))
		return true;

	$r = q("INSERT INTO `hook` (`hook`, `file`, `function`) VALUES ( '%s', '%s', '%s' ) ",
		dbesc($hook),
		dbesc($file),
		dbesc($function)
	);
	return $r;
}}

if(! function_exists('unregister_hook')) {
function unregister_hook($hook,$file,$function) {

	$r = q("DELETE FROM `hook` WHERE `hook` = '%s' AND `file` = '%s' AND `function` = '%s' LIMIT 1",
		dbesc($hook),
		dbesc($file),
		dbesc($function)
	);
	return $r;
}}


if(! function_exists('load_hooks')) {
function load_hooks() {
	$a = get_app();
	$a->hooks = array();
	$r = q("SELECT * FROM `hook` WHERE 1");
	if(count($r)) {
		foreach($r as $rr) {
			$a->hooks[] = array($rr['hook'], $rr['file'], $rr['function']);
		}
	}
}}


if(! function_exists('call_hooks')) {
function call_hooks($name, &$data = null) {
	$a = get_app();

	if(count($a->hooks)) {
		foreach($a->hooks as $hook) {
			if($hook[HOOK_HOOK] === $name) {
				@include_once($hook[HOOK_FILE]);
				if(function_exists($hook[HOOK_FUNCTION])) {
					$func = $hook[HOOK_FUNCTION];
					$func($a,$data);
				}
			}
		}
	}
}}


if(! function_exists('day_translate')) {
function day_translate($s) {
	$ret = str_replace(array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
		array( t('Monday'), t('Tuesday'), t('Wednesday'), t('Thursday'), t('Friday'), t('Saturday'), t('Sunday')),
		$s);

	$ret = str_replace(array('January','February','March','April','May','June','July','August','September','October','November','December'),
		array( t('January'), t('February'), t('March'), t('April'), t('May'), t('June'), t('July'), t('August'), t('September'), t('October'), t('November'), t('December')),
		$ret);

	return $ret;
}}

if(! function_exists('get_birthdays')) {
function get_birthdays() {

	$a = get_app();
	$o = '';

	if(! local_user())
		return $o;

	$bd_format = t('g A l F d') ; // 8 AM Friday January 18

	$r = q("SELECT `event`.*, `event`.`id` AS `eid`, `contact`.* FROM `event` 
		LEFT JOIN `contact` ON `contact`.`id` = `event`.`cid` 
		WHERE `event`.`uid` = %d AND `type` = 'birthday' AND `start` < '%s' AND `finish` > '%s' 
		ORDER BY `start` DESC ",
		intval(local_user()),
		dbesc(datetime_convert('UTC','UTC','now + 6 days')),
		dbesc(datetime_convert('UTC','UTC','now'))
	);

	if($r && count($r)) {
		$total = 0;
		foreach($r as $rr)
			if(strlen($rr['name']))
				$total ++;

		if($total) {
			$o .= '<div id="birthday-notice" class="birthday-notice fakelink" onclick=openClose(\'birthday-wrapper\'); >' . t('Birthday Reminders') . ' ' . '(' . $total . ')' . '</div>'; 
			$o .= '<div id="birthday-wrapper" style="display: none;" ><div id="birthday-title">' . t('Birthdays this week:') . '</div>'; 
			$o .= '<div id="birthday-adjust">' . t("\x28Adjusted for local time\x29") . '</div>';
			$o .= '<div id="birthday-title-end"></div>';

			foreach($r as $rr) {
				if(! strlen($rr['name']))
					continue;
				$now = strtotime('now');
				$today = (((strtotime($rr['start'] . ' +00:00') < $now) && (strtotime($rr['finish'] . ' +00:00') > $now)) ? true : false); 
	
				$o .= '<div class="birthday-list" id="birthday-' . $rr['eid'] . '"><a class="sparkle" href="' 
				. $a->get_baseurl() . '/redir/'  . $rr['cid'] . '">' . $rr['name'] . '</a> ' 
				. day_translate(datetime_convert('UTC', $a->timezone, $rr['start'], $bd_format)) . (($today) ?  ' ' . t('[today]') : '')
				. '</div>' ;
			}
			$o .= '</div></div>';
		}
	}
	return $o;
}}


if(! function_exists('normalise_link')) {
function normalise_link($url) {
	$ret = str_replace(array('https:','//www.'), array('http:','//'), $url);
	return(rtrim($ret,'/'));
}}

/**
 *
 * Compare two URLs to see if they are the same, but ignore
 * slight but hopefully insignificant differences such as if one 
 * is https and the other isn't, or if one is www.something and 
 * the other isn't - and also ignore case differences.
 *
 * Return true if the URLs match, otherwise false.
 *
 */

if(! function_exists('link_compare')) {
function link_compare($a,$b) {
	if(strcasecmp(normalise_link($a),normalise_link($b)) === 0)
		return true;
	return false;
}}


if(! function_exists('prepare_body')) {
function prepare_body($item,$attach = false) {

	$s = prepare_text($item['body']);
	if(! $attach)
		return $s;

	$arr = explode(',',$item['attach']);
	if(count($arr)) {
		$s .= '<div class="body-attach">';
		foreach($arr as $r) {
			$matches = false;
			$icon = '';
			$cnt = preg_match('|\[attach\]href=\"(.*?)\" size=\"(.*?)\" type=\"(.*?)\" title=\"(.*?)\"\[\/attach\]|',$r,$matches);
			if($cnt) {
				$icontype = strtolower(substr($matches[3],0,strpos($matches[3],'/')));
				switch($icontype) {
					case 'video':
					case 'audio':
					case 'image':
					case 'text':
						$icon = '<div class="attachtype type-' . $icontype . '"></div>';
						break;
					default:
						$icon = '<div class="attachtype type-unkn"></div>';
						break;
				}
				$title = ((strlen(trim($matches[4]))) ? escape_tags(trim($matches[4])) : escape_tags($matches[1]));
				$title .= ' ' . $matches[2] . ' ' . t('bytes');

				$s .= '<a href="' . strip_tags($matches[1]) . '" title="' . $title . '" class="attachlink" target="external-link" >' . $icon . '</a>';
			}
		}
		$s .= '<div class="clear"></div></div>';
	}
	return $s;
}}

if(! function_exists('prepare_text')) {
function prepare_text($text) {

	require_once('include/bbcode.php');

	$s = smilies(bbcode($text));

	return $s;
}}

/**
 * 
 * Wrap calls to proc_close(proc_open()) and call hook
 * so plugins can take part in process :)
 * 
 * args:
 * $cmd program to run
 *  next args are passed as $cmd command line
 * 
 * e.g.: proc_run("ls","-la","/tmp");
 * 
 * $cmd and string args are surrounded with ""
 */

if(! function_exists('proc_run')) {
function proc_run($cmd){

	$a = get_app();

	$args = func_get_args();
	call_hooks("proc_run", $args);

	if(count($args) && $args[0] === 'php')
        $args[0] = ((x($a->config,'php_path')) && (strlen($a->config['php_path'])) ? $a->config['php_path'] : 'php');
	
	foreach ($args as $arg){
		$arg = escapeshellarg($arg);
	}
	$cmdline = implode($args," ");
	proc_close(proc_open($cmdline." &",array(),$foo));
}}

if(! function_exists('current_theme')) {
function current_theme(){
	$app_base_themes = array('duepuntozero', 'loozah');
	
	$a = get_app();
	
	$system_theme = ((isset($a->config['system']['theme'])) ? $a->config['system']['theme'] : '');
	$theme_name = ((is_array($_SESSION) && x($_SESSION,'theme')) ? $_SESSION['theme'] : $system_theme);
	
	if($theme_name && file_exists('view/theme/' . $theme_name . '/style.css'))
		return($theme_name);
	
	foreach($app_base_themes as $t) {
		if(file_exists('view/theme/' . $t . '/style.css'))
			return($t);
	}
	
	$fallback = glob('view/theme/*/style.css');
	if(count($fallback))
		return (str_replace('view/theme/','', str_replace("/style.css","",$fallback[0])));

}}

/*
* Return full URL to theme which is currently in effect.
* Provide a sane default if nothing is chosen or the specified theme does not exist.
*/
if(! function_exists('current_theme_url')) {
function current_theme_url() {
	global $a;
	$t = current_theme();
	return($a->get_baseurl() . '/view/theme/' . $t . '/style.css');
}}

if(! function_exists('feed_birthday')) {
function feed_birthday($uid,$tz) {

	/**
	 *
	 * Determine the next birthday, but only if the birthday is published
	 * in the default profile. We _could_ also look for a private profile that the
	 * recipient can see, but somebody could get mad at us if they start getting
	 * public birthday greetings when they haven't made this info public. 
	 *
	 * Assuming we are able to publish this info, we are then going to convert
	 * the start time from the owner's timezone to UTC. 
	 *
	 * This will potentially solve the problem found with some social networks
	 * where birthdays are converted to the viewer's timezone and salutations from
	 * elsewhere in the world show up on the wrong day. We will convert it to the
	 * viewer's timezone also, but first we are going to convert it from the birthday
	 * person's timezone to GMT - so the viewer may find the birthday starting at
	 * 6:00PM the day before, but that will correspond to midnight to the birthday person.
	 *
	 */

	$birthday = '';

	$p = q("SELECT `dob` FROM `profile` WHERE `is-default` = 1 AND `uid` = %d LIMIT 1",
		intval($uid)
	);

	if($p && count($p)) {
		$tmp_dob = substr($p[0]['dob'],5);
		if(intval($tmp_dob)) {
			$y = datetime_convert($tz,$tz,'now','Y');
			$bd = $y . '-' . $tmp_dob . ' 00:00';
			$t_dob = strtotime($bd);
			$now = strtotime(datetime_convert($tz,$tz,'now'));
			if($t_dob < $now)
				$bd = $y + 1 . '-' . $tmp_dob . ' 00:00';
			$birthday = datetime_convert($tz,'UTC',$bd,ATOM_TIME); 
		}
	}

	return $birthday;
}}

/**
 * return atom link elements for all of our hubs
 */

if(! function_exists('feed_hublinks')) {
function feed_hublinks() {

	$hub = get_config('system','huburl');

	$hubxml = '';
	if(strlen($hub)) {
		$hubs = explode(',', $hub);
		if(count($hubs)) {
			foreach($hubs as $h) {
				$h = trim($h);
				if(! strlen($h))
					continue;
				$hubxml .= '<link rel="hub" href="' . xmlify($h) . '" />' . "\n" ;
			}
		}
	}
	return $hubxml;
}}

/* return atom link elements for salmon endpoints */

if(! function_exists('feed_salmonlinks')) {
function feed_salmonlinks($nick) {

	$a = get_app();

	$salmon  = '<link rel="salmon" href="' . xmlify($a->get_baseurl() . '/salmon/' . $nick) . '" />' . "\n" ;

	// old style links that status.net still needed as of 12/2010 

	$salmon .= '  <link rel="http://salmon-protocol.org/ns/salmon-replies" href="' . xmlify($a->get_baseurl() . '/salmon/' . $nick) . '" />' . "\n" ; 
	$salmon .= '  <link rel="http://salmon-protocol.org/ns/salmon-mention" href="' . xmlify($a->get_baseurl() . '/salmon/' . $nick) . '" />' . "\n" ; 
	return $salmon;
}}

if(! function_exists('get_plink')) {
function get_plink($item) {
	$a = get_app();	
	$plink = (((x($item,'plink')) && (! $item['private'])) ? '<div class="wall-item-links-wrapper"><a href="' 
			. $item['plink'] . '" title="' . t('link to source') . '" target="external-link" class="icon remote-link"></a></div>' : '');
	return $plink;
}}

if(! function_exists('unamp')) {
function unamp($s) {
	return str_replace('&amp;', '&', $s);
}}




if(! function_exists('lang_selector')) {
function lang_selector() {
	global $lang;
	$o = '<div id="lang-select-icon" class="icon language" title="' . t('Select an alternate language') . '" onclick="openClose(\'language-selector\');" ></div>';
	$o .= '<div id="language-selector" style="display: none;" >';
	$o .= '<form action="" method="post" ><select name="system_language" onchange="this.form.submit();" >';
	$langs = glob('view/*/strings.php');
	if(is_array($langs) && count($langs)) {
		if(! in_array('view/en/strings.php',$langs))
			$langs[] = 'view/en/';
		asort($langs);
		foreach($langs as $l) {
			$ll = substr($l,5);
			$ll = substr($ll,0,strrpos($ll,'/'));
			$selected = (($ll === $lang) ? ' selected="selected" ' : '');
			$o .= '<option value="' . $ll . '"' . $selected . '>' . $ll . '</option>';
		}
	}
	$o .= '</select></form></div>';
	return $o;
}}


if(! function_exists('parse_xml_string')) {
function parse_xml_string($s,$strict = true) {
	if($strict) {
		if(! strstr($s,'<?xml'))
			return false;
		$s2 = substr($s,strpos($s,'<?xml'));
	}
	else
		$s2 = $s;
	libxml_use_internal_errors(true);

	$x = @simplexml_load_string($s2);
	if(! $x) {
		logger('libxml: parse: error: ' . $s2, LOGGER_DATA);
		foreach(libxml_get_errors() as $err)
			logger('libxml: parse: ' . $err->code." at ".$err->line.":".$err->column." : ".$err->message, LOGGER_DATA);
		libxml_clear_errors();
	}
	return $x;
}}

if(! function_exists('is_site_admin')) {
function is_site_admin() {
	$a = get_app();
	if(local_user() && x($a->user,'email') && x($a->config,'admin_email') && ($a->user['email'] === $a->config['admin_email']))
		return true;
	return false;
}}

/*
 * parse plugin comment in search of plugin infos.
 * like
 * 	
 * 	 * Name: Plugin
 *   * Description: A plugin which plugs in
 * 	 * Version: 1.2.3
 *   * Author: John <profile url>
 *   * Author: Jane <email>
 *   *
 */

if (! function_exists('get_plugin_info')){
function get_plugin_info($plugin){
	if (!is_file("addon/$plugin/$plugin.php")) return false;
	
	$f = file_get_contents("addon/$plugin/$plugin.php");
	$r = preg_match("|/\*.*\*/|msU", $f, $m);
	
	$info=Array(
		'name' => $plugin,
		'description' => "",
		'author' => array(),
		'version' => ""
	);
	
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
}}

if(! function_exists('return_bytes')) {
function return_bytes ($size_str) {
    switch (substr ($size_str, -1))
    {
        case 'M': case 'm': return (int)$size_str * 1048576;
        case 'K': case 'k': return (int)$size_str * 1024;
        case 'G': case 'g': return (int)$size_str * 1073741824;
        default: return $size_str;
    }
}}

