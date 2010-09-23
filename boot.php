<?php

set_time_limit(0);

define ( 'BUILD_ID' , 1003 );

define ( 'EOL', "<br />\r\n");
define ( 'ATOM_TIME',  'Y-m-d\TH:i:s\Z' );

define ( 'REGISTER_CLOSED',  0);
define ( 'REGISTER_APPROVE', 1);
define ( 'REGISTER_OPEN',    2);

// relationship types

define ( 'REL_VIP',        1);
define ( 'REL_FAN',        2);
define ( 'REL_BUD',        3);

define ( 'NOTIFY_INTRO',   0x0001 );
define ( 'NOTIFY_CONFIRM', 0x0002 );
define ( 'NOTIFY_WALL',    0x0004 );
define ( 'NOTIFY_COMMENT', 0x0008 );
define ( 'NOTIFY_MAIL',    0x0010 );

define ( 'NAMESPACE_DFRN' ,           'http://purl.org/macgirvin/dfrn/1.0' ); 
define ( 'NAMESPACE_THREAD' ,         'http://purl.org/syndication/thread/1.0' );
define ( 'NAMESPACE_TOMB' ,           'http://purl.org/atompub/tombstones/1.0' );
define ( 'NAMESPACE_ACTIVITY',        'http://activitystrea.ms/spec/1.0/' );
define ( 'NAMESPACE_ACTIVITY_SCHEMA', 'http://activitystrea.ms/schema/1.0/');

define ( 'ACTIVITY_LIKE',        NAMESPACE_ACTIVITY_SCHEMA . 'like' );
define ( 'ACTIVITY_DISLIKE',     NAMESPACE_DFRN            . '/dislike' );
define ( 'ACTIVITY_OBJ_HEART',   NAMESPACE_DFRN            . '/heart' );

define ( 'ACTIVITY_FRIEND',      NAMESPACE_ACTIVITY_SCHEMA . 'make-friend' );
define ( 'ACTIVITY_POST',        NAMESPACE_ACTIVITY_SCHEMA . 'post' );
define ( 'ACTIVITY_UPDATE',      NAMESPACE_ACTIVITY_SCHEMA . 'update' );

define ( 'ACTIVITY_OBJ_COMMENT', NAMESPACE_ACTIVITY_SCHEMA . 'comment' );
define ( 'ACTIVITY_OBJ_NOTE',    NAMESPACE_ACTIVITY_SCHEMA . 'note' );
define ( 'ACTIVITY_OBJ_PERSON',  NAMESPACE_ACTIVITY_SCHEMA . 'person' );
define ( 'ACTIVITY_OBJ_PHOTO',   NAMESPACE_ACTIVITY_SCHEMA . 'photo' );
define ( 'ACTIVITY_OBJ_P_PHOTO', NAMESPACE_ACTIVITY_SCHEMA . 'profile-photo' );
define ( 'ACTIVITY_OBJ_ALBUM',   NAMESPACE_ACTIVITY_SCHEMA . 'photo-album' );

define ( 'GRAVITY_PARENT',       0);
define ( 'GRAVITY_LIKE',         3);
define ( 'GRAVITY_COMMENT',      6);


if(! class_exists('App')) {
class App {

	public  $module_loaded = false;
	public  $config;
	public  $page;
	public  $profile;
	public  $user;
	public  $cid;
	public  $contact;
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

	private $scheme;
	private $hostname;
	private $baseurl;
	private $db;

	function __construct() {

		$this->config = array();
		$this->page = array();
		$this->pager= array();

		$this->scheme = ((isset($_SERVER['HTTPS']) 
				&& ($_SERVER['HTTPS']))	?  'https' : 'http' );
		$this->hostname = str_replace('www.','',
				$_SERVER['SERVER_NAME']);
		set_include_path("include/$this->hostname" 
				. PATH_SEPARATOR . 'include' 
				. PATH_SEPARATOR . '.' );

                if(substr($_SERVER['QUERY_STRING'],0,2) == "q=")
			$_SERVER['QUERY_STRING'] = substr($_SERVER['QUERY_STRING'],2);
		$this->cmd = trim($_GET['q'],'/');


		$this->argv = explode('/',$this->cmd);
		$this->argc = count($this->argv);
		if((array_key_exists('0',$this->argv)) && strlen($this->argv[0])) {
			$this->module = $this->argv[0];
		}
		else {
			$this->module = 'home';
		}

		if($this->cmd == '.well-known/host-meta')
			require_once('include/hostxrd.php');

		$this->pager['page'] = ((x($_GET,'page')) ? $_GET['page'] : 1);
		$this->pager['itemspage'] = 50;
		$this->pager['start'] = ($this->pager['page'] * $this->pager['itemspage']) - $this->pager['itemspage'];
		$this->pager['total'] = 0;
	}

	function get_baseurl($ssl = false) {
		if(strlen($this->baseurl))
			return $this->baseurl;

		$this->baseurl = (($ssl) ? 'https' : $this->scheme) . "://" . $this->hostname
			. ((isset($this->path) && strlen($this->path)) 
			? '/' . $this->path : '' );
		return $this->baseurl;
	}

	function set_baseurl($url) {
		$this->baseurl = $url;
		$this->hostname = basename($url);
	}

	function get_hostname() {
		return $this->hostname;
	}

	function set_hostname($h) {
		$this->hostname = $h;
	}

	function set_path($p) {
		$this->path = ltrim(trim($p),'/');
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
		$tpl = load_view_file("view/head.tpl");
		$this->page['htmlhead'] = replace_macros($tpl,array(
			'$baseurl' => $this->get_baseurl()
		));
	}

}}


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

if(! function_exists('system_unavailable')) {
function system_unavailable() {
	include('system_unavailable.php');
	killme();
}}


if(! function_exists('check_config')) {
function check_config(&$a) {

	load_config('system');

	$build = get_config('system','build');
	if(! x($build))
		$build = set_config('system','build',BUILD_ID);

	$url = get_config('system','url');
	if(! x($url))
		$url = set_config('system','url',$a->get_baseurl());

	if($build != BUILD_ID) {
		$stored = intval($build);
		$current = intval(BUILD_ID);
		if(($stored < $current) && file_exists('update.php')) {

			// We're reporting a different version than what is currently installed.
			// Run any existing update scripts to bring the database up to current.

			require_once('update.php');
			for($x = $stored; $x < $current; $x ++) {
				if(function_exists('update_' . $x)) {
					$func = 'update_' . $x;
					$func($a);
				}
			}
			set_config('system','build', BUILD_ID);
		}
	}
	return;
}}



if(! function_exists('replace_macros')) {  
function replace_macros($s,$r) {

	$search = array();
	$replace = array();

	if(is_array($r) && count($r)) {
		foreach ($r as $k => $v ) {
			$search[] =  $k;
			$replace[] = $v;
		}
	}
	return str_replace($search,$replace,$s);
}}


if(! function_exists('load_translation_table')) {
function load_translation_table($lang) {
	global $a;

}}

if(! function_exists('t')) {
function t($s) {
	global $a;

	if($a->strings[$s])
		return $a->strings[$s];
	return $s;
}}

if(! function_exists('fetch_url')) {
function fetch_url($url,$binary = false) {
	$ch = curl_init($url);
	if(! $ch) return false;

        curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION,true);
	curl_setopt($ch, CURLOPT_MAXREDIRS,8);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
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

	$s = curl_exec($ch);
	curl_close($ch);
	return($s);
}}


if(! function_exists('post_url')) {
function post_url($url,$params) {
	$ch = curl_init($url);
	if(! $ch) return false;

        curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION,true);
	curl_setopt($ch, CURLOPT_MAXREDIRS,8);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch, CURLOPT_POST,1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
	$prx = get_config('system','proxy');
	if(strlen($prx)) {
		curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
		curl_setopt($ch, CURLOPT_PROXY, $prx);
		$prxusr = get_config('system','proxyuser');
		if(strlen($prxusr))
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, $prxusr);
	}

	$s = curl_exec($ch);
	curl_close($ch);
	return($s);
}}


if(! function_exists('random_string')) {
function random_string() {
	return(hash('sha256',uniqid(rand(),true)));
}}

if(! function_exists('notags')) {
function notags($string) {
	// protect against :<> with high-bit set
	return(str_replace(array("<",">","\xBA","\xBC","\xBE"), array('[',']','','',''), $string));
}}

if(! function_exists('escape_tags')) {
function escape_tags($string) {

	return(htmlspecialchars($string));
}}

if(! function_exists('login')) {
function login($register = false) {
	$o = "";
	$register_html = (($register) ? load_view_file("view/register-link.tpl") : "");


	if(x($_SESSION,'authenticated')) {
		$o = load_view_file("view/logout.tpl");
	}
	else {
		$o = load_view_file("view/login.tpl");

		$o = replace_macros($o,array('$register_html' => $register_html ));
	}
	return $o;
}}


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

if(! function_exists('killme')) {
function killme() {
	session_write_close();
	exit;
}}

if(! function_exists('goaway')) {
function goaway($s) {
	header("Location: $s");
	killme();
}}


if(! function_exists('xml_status')) {
function xml_status($st) {
	header( "Content-type: text/xml" );
	echo '<?xml version="1.0" encoding="UTF-8"?>'."\r\n";
	echo "<result><status>$st</status></result>\r\n";
	killme();
}}

if(! function_exists('local_user')) {
function local_user() {
	if((x($_SESSION,'authenticated')) && (x($_SESSION,'uid')))
		return $_SESSION['uid'];
	return false;
}}

if(! function_exists('remote_user')) {
function remote_user() {
	if((x($_SESSION,'authenticated')) && (x($_SESSION,'visitor_id')))
		return $_SESSION['visitor_id'];
	return false;
}}

if(! function_exists('notice')) {
function notice($s) {

	$_SESSION['sysmsg'] .= $s;

}}

if(! function_exists('get_max_import_size')) {
function get_max_import_size() {
	global $a;
	return ((x($a->config,'max_import_size')) ? $a->config['max_import_size'] : 0 );
}}

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
				$buffer .= ' ';
				break;
			default :
				$buffer .= $char;
				break;
		}	
	}
	$buffer = trim($buffer);
	return($buffer);
}}

if(! function_exists('unxmlify')) {
function unxmlify($s) {
	$ret = str_replace('&amp;','&', $s);
	$ret = str_replace(array('&lt;','&gt;','&quot;','&apos;'),array('<','>','"',"'"),$ret);
	return $ret;	
}}

if(! function_exists('hex2bin')) {
function hex2bin($s) {
	return(pack("H*",$s));
}}


if(! function_exists('paginate')) {
function paginate(&$a) {
	$o = '';
	$stripped = ereg_replace("(&page=[0-9]*)","",$_SERVER['QUERY_STRING']);
	$stripped = str_replace('q=','',$stripped);
	$stripped = trim($stripped,'/');
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

if(! function_exists('expand_acl')) {
function expand_acl($s) {

	if(strlen($s)) {
		$a = explode('<',$s);
		for($x = 0; $x < count($a); $x ++) {
			$a[$x] = intval(str_replace(array('<','>'),array('',''),$a[$x]));
		}
		return $a;
	}
	return array();
}}		

if(! function_exists('sanitise_acl')) {
function sanitise_acl(&$item) {
	if(intval($item))
		$item = '<' . intval(notags(trim($item))) . '>';
	else
		unset($item);
}}

if(! function_exists('load_config')) {
function load_config($family) {
	global $a;
	$r = q("SELECT * FROM `config` WHERE `cat` = '%s'",
		dbesc($family)
	);
	if(count($r)) {
		foreach($r as $rr) {
			$k = $rr['k'];
			$a->config[$family][$k] = $rr['v'];
		}
	}
}}


if(! function_exists('get_config')) {
function get_config($family, $key, $instore = false) {

	global $a;
	if(! $instore) {
		if(isset($a->config[$family][$key])) {
			if($a->config[$family][$key] == '!<unset>!')
				return false;
			return $a->config[$family][$key];
		}
	}
	$ret = q("SELECT `v` FROM `config` WHERE `cat` = '%s' AND `k` = '%s' LIMIT 1",
		dbesc($family),
		dbesc($key)
	);
	if(count($ret)) {
		$a->config[$family][$key] = $ret[0]['v'];
		return $ret[0]['v'];
	}
	else {
		$a->config[$family][$key] = '!<unset>!';
	}
	return false;
}}

if(! function_exists('set_config')) {
function set_config($family,$key,$value) {

	global $a;
	$a->config[$family][$key] = $value;

	if(get_config($family,$key,true) === false) {
		$ret = q("INSERT INTO `config` ( `cat`, `k`, `v` ) VALUES ( '%s', '%s', '%s' ) ",
			dbesc($family),
			dbesc($key),
			dbesc($value)
		);
		if($ret) 
			return $value;
		return $ret;
	}
	$ret = q("UPDATE `config` SET `v` = '%s' WHERE `cat` = '%s' AND `k` = '%s' LIMIT 1",
		dbesc($value),
		dbesc($family),
		dbesc($key)
	);
	if($ret)
		return $value;
	return $ret;
}}

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


if(! function_exists('webfinger')) {
function webfinger($s) {
	if(! strstr($s,'@')) {
		return $s;
	}
	$host = substr($s,strpos($s,'@') + 1);
	$url = 'http://' . $host . '/.well-known/host-meta' ;
	$xml = fetch_url($url);
	if (! $xml)
		return '';
	$h = simplexml_load_string($xml);
	$arr = convert_xml_element_to_array($h);

	if(! isset($arr['xrd']['link']))
		return '';

	$link = $arr['xrd']['link'];
	if(! isset($link[0]))
		$links = array($link);
	else
		$links = $link;

	foreach($links as $link)
		if($link['@attributes']['rel'] && $link['@attributes']['rel'] == 'lrdd')
			$tpl = $link['@attributes']['template'];
	if((empty($tpl)) || (! strpos($tpl, '{uri}')))
		return '';

	$pxrd = str_replace('{uri}', urlencode('acct://'.$s), $tpl);

	$xml = fetch_url($pxrd);
	if (! $xml)
		return '';
	$h = simplexml_load_string($xml);
	$arr = convert_xml_element_to_array($h);

	if(! isset($arr['xrd']['link']))
		return '';

	$link = $arr['xrd']['link'];
	if(! isset($link[0]))
		$links = array($link);
	else
		$links = $link;

	foreach($links as $link)
		if($link['@attributes']['rel'] == NAMESPACE_DFRN)
			return $link['@attributes']['href'];
	return '';
}}

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



if(! function_exists('get_uid')) {
function get_uid() {
	return ((x($_SESSION,'uid')) ? intval($_SESSION['uid']) : 0) ;
}}

if(! function_exists('validate_url')) {
function validate_url(&$url) {
	if(substr($url,0,4) != 'http')
		$url = 'http://' . $url;
	$h = parse_url($url);

	if(! $h) {
		return false;
	}
	if(! checkdnsrr($h['host'], 'ANY')) {
		return false;
	}
	return true;
}}

if(! function_exists('allowed_url')) {
function allowed_url($url) {

	$h = parse_url($url);

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
			if(($fnmatch && fnmatch($pat,$host)) || ($pat == $host)) {
				$found = true; 
				break;
			}
		}
	}
	return $found;
}}


if(! function_exists('format_like')) {
function format_like($cnt,$arr,$type,$id) {
	if($cnt == 1)
		$o .= $arr[0] . (($type == 'like') ? t(' likes this.') : t(' doesn\'t like this.')) . EOL ;
	else {
		$o .= '<span class="fakelink" onclick="openClose(\'' . $type . 'list-' . $id . '\');" >' 
			. $cnt . ' ' . t('people') . '</span> ' . (($type == 'like') ? t('like this.') : t('don\'t like this.')) . EOL ;
		$total = count($arr);
		if($total >= 75)
			$arr = array_slice($arr,0,74);
		if($total < 75)
			$arr[count($arr)-1] = t('and') . ' ' . $arr[count($arr)-1];
		$str = implode(', ', $arr);
		if($total >= 75)
			$str .= t(', and ') . $total - 75 . t(' other people');
		$str .= (($type == 'like') ? t(' like this.') : t(' don\'t like this.'));
		$o .= '<div id="' . $type . 'list-' . $id . '" style="display: none;" >' . $str . '</div>';
	}
	return $o;
}}

if(! function_exists('load_view_file')) {
function load_view_file($s) {
	$b = basename($s);
	$d = dirname($s);
	$lang = get_config('system','language');
	if($lang && file_exists("$d/$lang/$b"))
		return file_get_contents("$d/$lang/$b");
	return file_get_contents($s);
}}