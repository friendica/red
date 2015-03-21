<?php
/**
 * @file include/network.php
 */

/**
 * @brief Returns path to CA file.
 *
 * @return string
 */
function get_capath() {
	return appdirpath() . '/library/cacert.pem';
}

/**
 * @brief fetches an URL.
 *
 * @param string $url
 *    URL to fetch
 * @param boolean $binary default false
 *    TRUE if asked to return binary results (file download)
 * @param int $redirects default 0
 *    internal use, recursion counter
 * @param array $opts (optional parameters) assoziative array with:
 *  * \b accept_content => supply Accept: header with 'accept_content' as the value
 *  * \b timeout => int seconds, default system config value or 60 seconds
 *  * \b http_auth => username:password
 *  * \b novalidate => do not validate SSL certs, default is to validate using our CA list
 *  * \b nobody => only return the header
 *
 * @return array an assoziative array with:
 *  * \e int \b return_code => HTTP return code or 0 if timeout or failure
 *  * \e boolean \b success => boolean true (if HTTP 2xx result) or false
 *  * \e string \b header => HTTP headers
 *  * \e string \b body => fetched content
 */
function z_fetch_url($url, $binary = false, $redirects = 0, $opts = array()) {

	$ret = array('return_code' => 0, 'success' => false, 'header' => "", 'body' => "");

	$ch = @curl_init($url);
	if(($redirects > 8) || (! $ch)) 
		return false;

	@curl_setopt($ch, CURLOPT_HEADER, true);
	@curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	@curl_setopt($ch, CURLOPT_CAINFO, get_capath());
	@curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	@curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	@curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; Red)");

	$ciphers = @get_config('system','curl_ssl_ciphers');
	if($ciphers)
		@curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, $ciphers);

	if(x($opts,'headers'))
		@curl_setopt($ch, CURLOPT_HTTPHEADER, $opts['headers']);

	if(x($opts,'nobody'))
		@curl_setopt($ch, CURLOPT_NOBODY, $opts['nobody']);

	if(x($opts,'timeout') && intval($opts['timeout'])) {
		@curl_setopt($ch, CURLOPT_TIMEOUT, $opts['timeout']);
	}
	else {
		$curl_time = intval(get_config('system','curl_timeout'));
		@curl_setopt($ch, CURLOPT_TIMEOUT, (($curl_time !== false) ? $curl_time : 60));
	}

	if(x($opts,'http_auth')) {
		// "username" . ':' . "password"
		@curl_setopt($ch, CURLOPT_USERPWD, $opts['http_auth']);
	}

	@curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 
		((x($opts,'novalidate') && intval($opts['novalidate'])) ? false : true));

	$prx = get_config('system','proxy');
	if(strlen($prx)) {
		@curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
		@curl_setopt($ch, CURLOPT_PROXY, $prx);
		$prxusr = @get_config('system','proxyuser');
		if(strlen($prxusr))
			@curl_setopt($ch, CURLOPT_PROXYUSERPWD, $prxusr);
	}
	if($binary)
		@curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);


	// don't let curl abort the entire application'
	// if it throws any errors.

	$s = @curl_exec($ch);

	$base = $s;
	$curl_info = @curl_getinfo($ch);
	$http_code = $curl_info['http_code'];
	//logger('fetch_url:' . $http_code . ' data: ' . $s);
	$header = '';

	// Pull out multiple headers, e.g. proxy and continuation headers
	// allow for HTTP/2.x without fixing code

	while(preg_match('/^HTTP\/[1-2].+? [1-5][0-9][0-9]/',$base)) {
		$chunk = substr($base,0,strpos($base,"\r\n\r\n")+4);
		$header .= $chunk;
		$base = substr($base,strlen($chunk));
	}

	if($http_code == 301 || $http_code == 302 || $http_code == 303 || $http_code == 307 || $http_code == 308) {
		$matches = array();
		preg_match('/(Location:|URI:)(.*?)\n/', $header, $matches);
		$newurl = trim(array_pop($matches));
		if(strpos($newurl,'/') === 0)
			$newurl = $url . $newurl;
		$url_parsed = @parse_url($newurl);
		if (isset($url_parsed)) {
			@curl_close($ch);
			return z_fetch_url($newurl,$binary,++$redirects,$opts);
		}
	}

	$rc = intval($http_code);
	$ret['return_code'] = $rc;
	$ret['success'] = (($rc >= 200 && $rc <= 299) ? true : false);
	if(! $ret['success']) {
		$ret['error'] = curl_error($ch);
		$ret['debug'] = $curl_info;
		logger('z_fetch_url: error: ' . $url . ': ' . $ret['error'], LOGGER_DEBUG);
		logger('z_fetch_url: debug: ' . print_r($curl_info,true), LOGGER_DATA);
	}
	$ret['body'] = substr($s,strlen($header));
	$ret['header'] = $header;

	if(x($opts,'debug')) {
		$ret['debug'] = $curl_info;
	}

	@curl_close($ch);
	return($ret);
}


/**
 * @brief
 *
 * @param string $url
 *    URL to post
 * @param mixed $params
 *   The full data to post in a HTTP "POST" operation. This parameter can 
 *   either be passed as a urlencoded string like 'para1=val1&para2=val2&...' 
 *   or as an array with the field name as key and field data as value. If value 
 *   is an array, the Content-Type header will be set to multipart/form-data. 
 * @param int $redirects = 0
 *    internal use, recursion counter
 * @param array $opts (optional parameters)
 *    'accept_content' => supply Accept: header with 'accept_content' as the value
 *    'timeout' => int seconds, default system config value or 60 seconds
 *    'http_auth' => username:password
 *    'novalidate' => do not validate SSL certs, default is to validate using our CA list
 * @return array an assoziative array with:
 *  * \e int \b return_code => HTTP return code or 0 if timeout or failure
 *  * \e boolean \b success => boolean true (if HTTP 2xx result) or false
 *  * \e string \b header => HTTP headers
 *  * \e string \b body => content
 *  * \e string \b debug => from curl_info()
 */
function z_post_url($url,$params, $redirects = 0, $opts = array()) {

	$ret = array('return_code' => 0, 'success' => false, 'header' => "", 'body' => "");

	$ch = curl_init($url);
	if(($redirects > 8) || (! $ch)) 
		return ret;

	@curl_setopt($ch, CURLOPT_HEADER, true);
	@curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	@curl_setopt($ch, CURLOPT_CAINFO, get_capath());
	@curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	@curl_setopt($ch, CURLOPT_POST,1);
	@curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
	@curl_setopt($ch, CURLOPT_USERAGENT, "Red");

	$ciphers = @get_config('system','curl_ssl_ciphers');
	if($ciphers)
		@curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, $ciphers);

	if(x($opts,'headers'))
		@curl_setopt($ch, CURLOPT_HTTPHEADER, $opts['headers']);

	if(x($opts,'nobody'))
		@curl_setopt($ch, CURLOPT_NOBODY, $opts['nobody']);

	if(x($opts,'timeout') && intval($opts['timeout'])) {
		@curl_setopt($ch, CURLOPT_TIMEOUT, $opts['timeout']);
	}
	else {
		$curl_time = intval(get_config('system','curl_timeout'));
		@curl_setopt($ch, CURLOPT_TIMEOUT, (($curl_time !== false) ? $curl_time : 60));
	}

	if(x($opts,'http_auth')) {
		// "username" . ':' . "password"
		@curl_setopt($ch, CURLOPT_USERPWD, $opts['http_auth']);
	}

	@curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 
		((x($opts,'novalidate') && intval($opts['novalidate'])) ? false : true));

	$prx = get_config('system','proxy');
	if(strlen($prx)) {
		@curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
		@curl_setopt($ch, CURLOPT_PROXY, $prx);
		$prxusr = get_config('system','proxyuser');
		if(strlen($prxusr))
			@curl_setopt($ch, CURLOPT_PROXYUSERPWD, $prxusr);
	}

	// don't let curl abort the entire application
	// if it throws any errors.

	$s = @curl_exec($ch);

	$base = $s;
	$curl_info = @curl_getinfo($ch);
	$http_code = $curl_info['http_code'];

	$header = '';

	// Pull out multiple headers, e.g. proxy and continuation headers
	// allow for HTTP/2.x without fixing code

	while(preg_match('/^HTTP\/[1-2].+? [1-5][0-9][0-9]/',$base)) {
		$chunk = substr($base,0,strpos($base,"\r\n\r\n")+4);
		$header .= $chunk;
		$base = substr($base,strlen($chunk));
	}

	if($http_code == 301 || $http_code == 302 || $http_code == 303 || $http_code == 307 || $http_code == 308) {
		$matches = array();
		preg_match('/(Location:|URI:)(.*?)\n/', $header, $matches);
		$newurl = trim(array_pop($matches));
		if(strpos($newurl,'/') === 0)
			$newurl = $url . $newurl;
		$url_parsed = @parse_url($newurl);
		if (isset($url_parsed)) {
			curl_close($ch);
			if($http_code == 303) {
				return z_fetch_url($newurl,false,$redirects++,$opts);
			} else {
				return z_post_url($newurl,$params,++$redirects,$opts);
			}
		}
	}
	$rc = intval($http_code);
	$ret['return_code'] = $rc;
	$ret['success'] = (($rc >= 200 && $rc <= 299) ? true : false);
	if(! $ret['success']) {
		$ret['error'] = curl_error($ch);
		$ret['debug'] = $curl_info;
		logger('z_post_url: error: ' . $url . ': ' . $ret['error'], LOGGER_DEBUG);
		logger('z_post_url: debug: ' . print_r($curl_info,true), LOGGER_DATA);
	}

	$ret['body'] = substr($s, strlen($header));
	$ret['header'] = $header;

	if(x($opts,'debug')) {
		$ret['debug'] = $curl_info;
	}

	curl_close($ch);
	return($ret);
}

/**
 * @brief Like z_post_url() but with an application/json HTTP header.
 *
 * Add a "Content-Type: application/json" HTTP-header to $opts and call z_post_url().
 *
 * @see z_post_url()
 *
 * @param string $url
 * @param array $params
 * @param number $redirects default 0
 * @param array $opts (optional) curl options
 * @return z_post_url()
 */
function z_post_url_json($url, $params, $redirects = 0, $opts = array()) {

	$opts = array_merge($opts, array('headers' => array('Content-Type: application/json')));

	return z_post_url($url,json_encode($params),$redirects,$opts);
}


function json_return_and_die($x) {
	header("content-type: application/json");
	echo json_encode($x);
	killme();
}



// Generic XML return
// Outputs a basic dfrn XML status structure to STDOUT, with a <status> variable 
// of $st and an optional text <message> of $message and terminates the current process. 


function xml_status($st, $message = '') {

	$xml_message = ((strlen($message)) ? "\t<message>" . xmlify($message) . "</message>\r\n" : '');

	if($st)
		logger('xml_status returning non_zero: ' . $st . " message=" . $message);

	header( "Content-type: text/xml" );
	echo '<?xml version="1.0" encoding="UTF-8"?>'."\r\n";
	echo "<result>\r\n\t<status>$st</status>\r\n$xml_message</result>\r\n";
	killme();
}

/**
 * @brief Send HTTP status header and exit.
 *
 * @param int $val
 *    integer HTTP status result value
 * @param string $msg
 *    optional message
 * @returns (does not return, process is terminated)
 */
function http_status_exit($val, $msg = '') {

	if ($val >= 400)
		$msg = (($msg) ? $msg : 'Error');
	if ($val >= 200 && $val < 300)
		$msg = (($msg) ? $msg : 'OK');

	logger('http_status_exit ' . $val . ' ' . $msg);	
	header($_SERVER['SERVER_PROTOCOL'] . ' ' . $val . ' ' . $msg);
	killme();
}


// convert an XML document to a normalised, case-corrected array
// used by webfinger


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
}

// Take a URL from the wild, prepend http:// if necessary
// and check DNS to see if it's real (or check if is a valid IP address)
// return true if it's OK, false if something is wrong with it


function validate_url(&$url) {
	
	// no naked subdomains (allow localhost for tests)
	if(strpos($url,'.') === false && strpos($url,'/localhost/') === false)
		return false;
	if(substr($url,0,4) != 'http')
		$url = 'http://' . $url;
	$h = @parse_url($url);
	
	if(($h) && (@dns_get_record($h['host'], DNS_A + DNS_CNAME + DNS_PTR) || filter_var($h['host'], FILTER_VALIDATE_IP) )) {
		return true;
	}
	return false;
}

// checks that email is an actual resolvable internet address


function validate_email($addr) {

	if(get_config('system','disable_email_validation'))
		return true;

	if(! strpos($addr,'@'))
		return false;
	$h = substr($addr,strpos($addr,'@') + 1);

	if(($h) && (@dns_get_record($h, DNS_A + DNS_CNAME + DNS_PTR + DNS_MX) || filter_var($h, FILTER_VALIDATE_IP) )) {
		return true;
	}
	return false;
}

// Check $url against our list of allowed sites,
// wildcards allowed. If allowed_sites is unset return true;
// If url is allowed, return true.
// otherwise, return false


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
}

// check if email address is allowed to register here.
// Compare against our list (wildcards allowed).
// Returns false if not allowed, true if allowed or if
// allowed list is not configured.


function allowed_email($email) {


	$domain = strtolower(substr($email,strpos($email,'@') + 1));
	if(! $domain)
		return false;

	$str_allowed = get_config('system','allowed_email');
	$str_not_allowed = get_config('system','not_allowed_email');
		
	if(! $str_allowed && ! $str_not_allowed)
		return true;

	$return = false;
	$found_allowed = false;	
	$found_not_allowed = false;
	
	$fnmatch = function_exists('fnmatch');

	$allowed = explode(',',$str_allowed);

	if(count($allowed)) {
		foreach($allowed as $a) {
			$pat = strtolower(trim($a));
			if(($fnmatch && fnmatch($pat,$email)) || ($pat == $domain)) {
				$found_allowed = true; 
				break;
			}
		}
	}

	$not_allowed = explode(',',$str_not_allowed);

	if(count($not_allowed)) {
		foreach($not_allowed as $na) {
			$pat = strtolower(trim($na));
			if(($fnmatch && fnmatch($pat,$email)) || ($pat == $domain)) {
				$found_not_allowed = true; 
				break;
			}
		}
	}	
	
	if ($found_allowed) {
		$return = true;	
	} elseif (!$str_allowed && !$found_not_allowed) {
		$return = true;	
	}
	return $return;
}



function avatar_img($email) {

	$avatar = array();
	$a = get_app();

	$avatar['size'] = 175;
	$avatar['email'] = $email;
	$avatar['url'] = '';
	$avatar['success'] = false;

	call_hooks('avatar_lookup', $avatar);

	if (! $avatar['success'])
		$avatar['url'] = $a->get_baseurl() . '/' . get_default_profile_photo();

	logger('Avatar: ' . $avatar['email'] . ' ' . $avatar['url'], LOGGER_DEBUG);

	return $avatar['url'];
}



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
}


function scale_external_images($s, $include_link = true, $scale_replace = false) {

	$a = get_app();

	// Picture addresses can contain special characters
	$s = htmlspecialchars_decode($s, ENT_COMPAT);

	$matches = null;
	$c = preg_match_all('/\[([zi])mg(.*?)\](.*?)\[\/[zi]mg\]/ism',$s,$matches,PREG_SET_ORDER);
	if($c) {
		require_once('include/photo/photo_driver.php');

		foreach($matches as $mtch) {
			logger('scale_external_image: ' . $mtch[2] . ' ' . $mtch[3]);
			
			if(substr($mtch[1],0,1) == '=') {
				$owidth = intval(substr($mtch[2],1));
				if(intval($owidth) > 0 && intval($owidth) < 640)
					continue;
			}

			$hostname = str_replace('www.','',substr($a->get_baseurl(),strpos($a->get_baseurl(),'://')+3));
			if(stristr($mtch[3],$hostname))
				continue;

			// $scale_replace, if passed, is an array of two elements. The
			// first is the name of the full-size image. The second is the
			// name of a remote, scaled-down version of the full size image.
			// This allows Friendica to display the smaller remote image if
			// one exists, while still linking to the full-size image
			if($scale_replace)
				$scaled = str_replace($scale_replace[0], $scale_replace[1], $mtch[3]);
			else
				$scaled = $mtch[3];
			$i = z_fetch_url($scaled);


			$cache = get_config('system','itemcache');
			if (($cache != '') and is_dir($cache)) {
				$cachefile = $cache."/".hash("md5", $scaled);
				file_put_contents($cachefile, $i['body']);
			}

			// guess mimetype from headers or filename
			$type = guess_image_type($mtch[3],$i['header']);
			
			if($i['success']) {
				$ph = photo_factory($i['body'], $type);
				if($ph->is_valid()) {
					$orig_width = $ph->getWidth();
					$orig_height = $ph->getHeight();

					if($orig_width > 640 || $orig_height > 640) {
						$tag = (($match[1] == 'z') ? 'zmg' : 'img');
						$ph->scaleImage(640);
						$new_width = $ph->getWidth();
						$new_height = $ph->getHeight();
						logger('scale_external_images: ' . $orig_width . '->' . $new_width . 'w ' . $orig_height . '->' . $new_height . 'h' . ' match: ' . $mtch[0], LOGGER_DEBUG);
						$s = str_replace($mtch[0],'[' . $tag . '=' . $new_width . 'x' . $new_height. ']' . $scaled . '[/' . $tag . ']'
							. "\n" . (($include_link) 
								? '[zrl=' . $mtch[2] . ']' . t('view full size') . '[/zrl]' . "\n"
								: ''),$s);
						logger('scale_external_images: new string: ' . $s, LOGGER_DEBUG);
					}
				}
			}
		}
	}

	// replace the special char encoding

	$s = htmlspecialchars($s,ENT_COMPAT,'UTF-8');

	return $s;
}

/**
 * xml2array() will convert the given XML text to an array in the XML structure.
 * Link: http://www.bin-co.com/php/scripts/xml2array/
 * Portions significantly re-written by mike@macgirvin.com for Friendica (namespaces, lowercase tags, get_attribute default changed, more...)
 * Arguments : $contents - The XML text
 *                $namespaces - true or false include namespace information in the returned array as array elements.
 *                $get_attributes - 1 or 0. If this is 1 the function will get the attributes as well as the tag values - this results in a different array structure in the return value.
 *                $priority - Can be 'tag' or 'attribute'. This will change the way the resulting array sturcture. For 'tag', the tags are given more importance.
 * Return: The parsed XML in an array form. Use print_r() to see the resulting array structure.
 * Examples: $array =  xml2array(file_get_contents('feed.xml'));
 *              $array =  xml2array(file_get_contents('feed.xml', true, 1, 'attribute'));
 */ 

function xml2array($contents, $namespaces = true, $get_attributes=1, $priority = 'attribute') {
	if(!$contents) return array();

	if(!function_exists('xml_parser_create')) {
		logger('xml2array: parser function missing');
		return array();
	}


	libxml_use_internal_errors(true);
	libxml_clear_errors();

	if($namespaces)
		$parser = @xml_parser_create_ns("UTF-8",':');
	else
		$parser = @xml_parser_create();

	if(! $parser) {
		logger('xml2array: xml_parser_create: no resource');
		return array();
	}

	xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); 
	// http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
	xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
	@xml_parse_into_struct($parser, trim($contents), $xml_values);
	@xml_parser_free($parser);

	if(! $xml_values) {
		logger('xml2array: libxml: parse error: ' . $contents, LOGGER_DATA);
		foreach(libxml_get_errors() as $err)
			logger('libxml: parse: ' . $err->code . " at " . $err->line . ":" . $err->column . " : " . $err->message, LOGGER_DATA);
		libxml_clear_errors();
		return;
	}

	//Initializations
	$xml_array = array();
	$parents = array();
	$opened_tags = array();
	$arr = array();

	$current = &$xml_array; // Reference

	// Go through the tags.
	$repeated_tag_index = array(); // Multiple tags with same name will be turned into an array
	foreach($xml_values as $data) {
		unset($attributes,$value); // Remove existing values, or there will be trouble

		// This command will extract these variables into the foreach scope
		// tag(string), type(string), level(int), attributes(array).
		extract($data); // We could use the array by itself, but this cooler.

		$result = array();
		$attributes_data = array();
		
		if(isset($value)) {
			if($priority == 'tag') $result = $value;
			else $result['value'] = $value; // Put the value in a assoc array if we are in the 'Attribute' mode
		}

		//Set the attributes too.
		if(isset($attributes) and $get_attributes) {
			foreach($attributes as $attr => $val) {
				if($priority == 'tag') $attributes_data[$attr] = $val;
				else $result['@attributes'][$attr] = $val; // Set all the attributes in a array called 'attr'
			}
		}

		// See tag status and do the needed.
		if($namespaces && strpos($tag,':')) {
			$namespc = substr($tag,0,strrpos($tag,':')); 
			$tag = strtolower(substr($tag,strlen($namespc)+1));
			$result['@namespace'] = $namespc;
		}
		$tag = strtolower($tag);

		if($type == "open") {   // The starting of the tag '<tag>'
			$parent[$level-1] = &$current;
			if(!is_array($current) or (!in_array($tag, array_keys($current)))) { // Insert New tag
				$current[$tag] = $result;
				if($attributes_data) $current[$tag. '_attr'] = $attributes_data;
				$repeated_tag_index[$tag.'_'.$level] = 1;

				$current = &$current[$tag];

			} else { // There was another element with the same tag name

				if(isset($current[$tag][0])) { // If there is a 0th element it is already an array
					$current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
					$repeated_tag_index[$tag.'_'.$level]++;
				} else { // This section will make the value an array if multiple tags with the same name appear together
					$current[$tag] = array($current[$tag],$result); // This will combine the existing item and the new item together to make an array
					$repeated_tag_index[$tag.'_'.$level] = 2;
					
					if(isset($current[$tag.'_attr'])) { // The attribute of the last(0th) tag must be moved as well
						$current[$tag]['0_attr'] = $current[$tag.'_attr'];
						unset($current[$tag.'_attr']);
					}

				}
				$last_item_index = $repeated_tag_index[$tag.'_'.$level]-1;
				$current = &$current[$tag][$last_item_index];
			}

		} elseif($type == "complete") { // Tags that ends in 1 line '<tag />'
			//See if the key is already taken.
			if(!isset($current[$tag])) { //New Key
				$current[$tag] = $result;
				$repeated_tag_index[$tag.'_'.$level] = 1;
				if($priority == 'tag' and $attributes_data) $current[$tag. '_attr'] = $attributes_data;

			} else { // If taken, put all things inside a list(array)
				if(isset($current[$tag][0]) and is_array($current[$tag])) { // If it is already an array...

					// ...push the new element into that array.
					$current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
					
					if($priority == 'tag' and $get_attributes and $attributes_data) {
						$current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
					}
					$repeated_tag_index[$tag.'_'.$level]++;

				} else { // If it is not an array...
					$current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
					$repeated_tag_index[$tag.'_'.$level] = 1;
					if($priority == 'tag' and $get_attributes) {
						if(isset($current[$tag.'_attr'])) { // The attribute of the last(0th) tag must be moved as well
							
							$current[$tag]['0_attr'] = $current[$tag.'_attr'];
							unset($current[$tag.'_attr']);
						}
						
						if($attributes_data) {
							$current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
						}
					}
					$repeated_tag_index[$tag.'_'.$level]++; // 0 and 1 indexes are already taken
				}
			}

		} elseif($type == 'close') { // End of tag '</tag>'
			$current = &$parent[$level-1];
		}
	}
	
	return($xml_array);
}  


function email_header_encode($in_str, $charset = 'UTF-8') {
	$out_str = $in_str;
	$need_to_convert = false;

	for($x = 0; $x < strlen($in_str); $x ++) {
		if((ord($in_str[$x]) == 0) || ((ord($in_str[$x]) > 128))) {
			$need_to_convert = true;
		}
	}

	if(! $need_to_convert)
		return $in_str;

	if ($out_str && $charset) {

		// define start delimimter, end delimiter and spacer
		$end = "?=";
		$start = "=?" . $charset . "?B?";
		$spacer = $end . "\r\n " . $start;

		// determine length of encoded text within chunks
		// and ensure length is even
		$length = 75 - strlen($start) - strlen($end);

		/*
			[EDIT BY danbrown AT php DOT net: The following
			is a bugfix provided by (gardan AT gmx DOT de)
			on 31-MAR-2005 with the following note:
			"This means: $length should not be even,
			but divisible by 4. The reason is that in
			base64-encoding 3 8-bit-chars are represented
			by 4 6-bit-chars. These 4 chars must not be
			split between two encoded words, according
			to RFC-2047.
		*/
		$length = $length - ($length % 4);

		// encode the string and split it into chunks
		// with spacers after each chunk
		$out_str = base64_encode($out_str);
		$out_str = chunk_split($out_str, $length, $spacer);

		// remove trailing spacer and
		// add start and end delimiters
		$spacer = preg_quote($spacer,'/');
		$out_str = preg_replace("/" . $spacer . "$/", "", $out_str);
		$out_str = $start . $out_str . $end;
	}
	return $out_str;
}

function email_send($addr, $subject, $headers, $item) {
	//$headers .= 'MIME-Version: 1.0' . "\n";
	//$headers .= 'Content-Type: text/html; charset=UTF-8' . "\n";
	//$headers .= 'Content-Type: text/plain; charset=UTF-8' . "\n";
	//$headers .= 'Content-Transfer-Encoding: 8bit' . "\n\n";

	$part = uniqid("", true);

	$html	= prepare_body($item);

	$headers .= "Mime-Version: 1.0\n";
	$headers .= 'Content-Type: multipart/alternative; boundary="=_'.$part.'"'."\n\n";

	$body = "\n--=_".$part."\n";
	$body .= "Content-Transfer-Encoding: 8bit\n";
	$body .= "Content-Type: text/plain; charset=utf-8; format=flowed\n\n";

	$body .= html2plain($html)."\n";

	$body .= "--=_".$part."\n";
	$body .= "Content-Transfer-Encoding: 8bit\n";
	$body .= "Content-Type: text/html; charset=utf-8\n\n";

	$body .= '<html><head></head><body style="word-wrap: break-word; -webkit-nbsp-mode: space; -webkit-line-break: after-white-space; ">'.$html."</body></html>\n";

	$body .= "--=_".$part."--";

	//$message = '<html><body>' . $html . '</body></html>';
	//$message = html2plain($html);
	logger('notifier: email delivery to ' . $addr);
	mail($addr, $subject, $body, $headers);
}



function discover_by_url($url,$arr = null) {
	require_once('library/HTML5/Parser.php');

	$x = scrape_feed($url);
	if(! $x) {
		if(! $arr)
			return false;
		$network = (($arr['network']) ? $arr['network'] : 'unknown');
		$name = (($arr['name']) ? $arr['name'] : 'unknown');
		$photo = (($arr['photo']) ? $arr['photo'] : '');
		$addr = (($arr['addr']) ? $arr['addr'] : '');
		$guid = $url;
	}

	$profile = $url;

	logger('scrape_feed results: ' . print_r($x,true));

	if($x['feed_atom'])
		$guid = $x['feed_atom'];
	if($x['feed_rss'])
		$guid = $x['feed_rss'];

	if(! $guid)
		return false;


	// try and discover stuff from the feeed

	require_once('library/simplepie/simplepie.inc');
	$feed = new SimplePie();
	$level = 0;
    $x = z_fetch_url($guid,false,$level,array('novalidate' => true));
	if(! $x['success']) {
		logger('probe_url: feed fetch failed for ' . $poll);
		return false;
	}
	$xml = $x['body'];
	logger('probe_url: fetch feed: ' . $guid . ' returns: ' . $xml, LOGGER_DATA);
	logger('probe_url: scrape_feed: headers: ' . $x['header'], LOGGER_DATA);

	// Don't try and parse an empty string
	$feed->set_raw_data(($xml) ? $xml : '<?xml version="1.0" encoding="utf-8" ?><xml></xml>');

	$feed->init();
	if($feed->error())
		logger('probe_url: scrape_feed: Error parsing XML: ' . $feed->error());

	$name = unxmlify(trim($feed->get_title()));
	$photo = $feed->get_image_url();
	$author = $feed->get_author();

	if($author) {
		if(! $name)
			$name = unxmlify(trim($author->get_name()));
		if(! $name) {
			$name = trim(unxmlify($author->get_email()));
			if(strpos($name,'@') !== false)
				$name = substr($name,0,strpos($name,'@'));
		}
		if(! $profile && $author->get_link())
			$profile = trim(unxmlify($author->get_link()));
		if(! $photo) {
			$rawtags = $feed->get_feed_tags( SIMPLEPIE_NAMESPACE_ATOM_10, 'author');
			if($rawtags) {
				$elems = $rawtags[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10];
				if((x($elems,'link')) && ($elems['link'][0]['attribs']['']['rel'] === 'photo'))
					$photo = $elems['link'][0]['attribs']['']['href'];
			}
		}
	}
	else {
		$item = $feed->get_item(0);
		if($item) {
			$author = $item->get_author();
			if($author) {
				if(! $name) {
					$name = trim(unxmlify($author->get_name()));
					if(! $name)
						$name = trim(unxmlify($author->get_email()));
					if(strpos($name,'@') !== false)
						$name = substr($name,0,strpos($name,'@'));
				}
				if(! $profile && $author->get_link())
					$profile = trim(unxmlify($author->get_link()));
			}
			if(! $photo) {
				$rawmedia = $item->get_item_tags('http://search.yahoo.com/mrss/','thumbnail');
				if($rawmedia && $rawmedia[0]['attribs']['']['url'])
					$photo = unxmlify($rawmedia[0]['attribs']['']['url']);
			}
			if(! $photo) {
				$rawtags = $item->get_item_tags( SIMPLEPIE_NAMESPACE_ATOM_10, 'author');
				if($rawtags) {
					$elems = $rawtags[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10];
					if((x($elems,'link')) && ($elems['link'][0]['attribs']['']['rel'] === 'photo'))
						$photo = $elems['link'][0]['attribs']['']['href'];
				}
			}
		}
	}
	if($poll === $profile)
		$lnk = $feed->get_permalink();
	if(isset($lnk) && strlen($lnk))
		$profile = $lnk;

	if(! $network) {
		$network = 'rss';
	}

	if(! $name)
		$name = notags($feed->get_description());

	if(! $guid)
		return false;

	$r = q("select * from xchan where xchan_hash = '%s' limit 1",
		dbesc($guid)
	);
	if($r)
		return true;

	if(! $photo)
		$photo = z_root() . '/images/rss_icon.png';

	$r = q("insert into xchan ( xchan_hash, xchan_guid, xchan_pubkey, xchan_addr, xchan_url, xchan_name, xchan_network, xchan_instance_url, xchan_name_date ) values ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s') ",
		dbesc($guid),
		dbesc($guid),
		dbesc($pubkey),
		dbesc($addr),
		dbesc($profile),
		dbesc($name),
		dbesc($network),
		dbesc(z_root()),
		dbesc(datetime_convert())
	);

	$photos = import_profile_photo($photo,$guid);
	$r = q("update xchan set xchan_photo_date = '%s', xchan_photo_l = '%s', xchan_photo_m = '%s', xchan_photo_s = '%s', xchan_photo_mimetype = '%s' where xchan_hash = '%s'",
		dbesc(datetime_convert()),
		dbesc($photos[0]),
		dbesc($photos[1]),
		dbesc($photos[2]),
		dbesc($photos[3]),
		dbesc($guid)
	);
	return true;

}

function discover_by_webbie($webbie) {
	require_once('library/HTML5/Parser.php');

	$webbie = strtolower($webbie);

	$x = webfinger_rfc7033($webbie);
	if($x && array_key_exists('links',$x) && $x['links']) {
		foreach($x['links'] as $link) {
			if(array_key_exists('rel',$link) && $link['rel'] == 'http://purl.org/zot/protocol') {
				logger('discover_by_webbie: zot found for ' . $webbie, LOGGER_DEBUG);
				$z = z_fetch_url($link['href']);
				if($z['success']) {
					$j = json_decode($z['body'],true);
					$i = import_xchan($j);
					return true;
				}
			}
		}
	}

	$result = array();
	$network = null;
	$diaspora = false;

	$diaspora_base = '';
	$diaspora_guid = '';
	$diaspora_key = '';
	$dfrn = false;

	$x = old_webfinger($webbie);			
	if($x) {
		logger('old_webfinger: ' . print_r($x,true));
		foreach($x as $link) {
			if($link['@attributes']['rel'] === NAMESPACE_DFRN)
				$dfrn = unamp($link['@attributes']['href']);				
			if($link['@attributes']['rel'] === 'salmon')
				$notify = unamp($link['@attributes']['href']);
 			if($link['@attributes']['rel'] === NAMESPACE_FEED)
				$poll = unamp($link['@attributes']['href']);
			if($link['@attributes']['rel'] === 'http://microformats.org/profile/hcard')
				$hcard = unamp($link['@attributes']['href']);
			if($link['@attributes']['rel'] === 'http://webfinger.net/rel/profile-page')
				$profile = unamp($link['@attributes']['href']);
			if($link['@attributes']['rel'] === 'http://portablecontacts.net/spec/1.0')
				$poco = unamp($link['@attributes']['href']);
			if($link['@attributes']['rel'] === 'http://joindiaspora.com/seed_location') {
				$diaspora_base = unamp($link['@attributes']['href']);
				$diaspora = true;
			}
			if($link['@attributes']['rel'] === 'http://joindiaspora.com/guid') {
				$diaspora_guid = unamp($link['@attributes']['href']);
				$diaspora = true;
			}
			if($link['@attributes']['rel'] === 'diaspora-public-key') {
				$diaspora_key = base64_decode(unamp($link['@attributes']['href']));
				if(strstr($diaspora_key,'RSA '))
					$pubkey = rsatopem($diaspora_key);
				else
					$pubkey = $diaspora_key;
				$diaspora = true;
			}
		}

		if($diaspora && $diaspora_base && $diaspora_guid) {
			$guid = $diaspora_guid;
			$diaspora_base = trim($diaspora_base,'/');

			$notify = $diaspora_base . '/receive';

			if(strpos($webbie,'@')) {
				$addr = str_replace('acct:', '', $webbie);
				$hostname = substr($webbie,strpos($webbie,'@')+1);
			}
			$network = 'diaspora';
			// until we get a dfrn layer, we'll use diaspora protocols for Friendica,
			// but give it a different network so we can go back and fix these when we get proper support. 
			// It really should be just 'friendica' but we also want to distinguish
			// between Friendica sites that we can use D* protocols with and those we can't.
			// Some Friendica sites will have Diaspora disabled. 
			if($dfrn)
				$network = 'friendica-over-diaspora';
			if($hcard) {
				$vcard = scrape_vcard($hcard);
				$vcard['nick'] = substr($webbie,0,strpos($webbie,'@'));
			} 

			$r = q("select * from xchan where xchan_hash = '%s' limit 1",
				dbesc($webbie)
			);
			if(! $r) {

				$r = q("insert into xchan ( xchan_hash, xchan_guid, xchan_pubkey, xchan_addr, xchan_url, xchan_name, xchan_network, xchan_instance_url, xchan_name_date ) values ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s') ",
					dbesc($addr),
					dbesc($guid),
					dbesc($pubkey),
					dbesc($addr),
					dbesc($profile),
					dbesc($vcard['fn']),
					dbesc($network),
					dbesc(z_root()),
					dbescdate(datetime_convert())
				);
			}

			$r = q("select * from hubloc where hubloc_hash = '%s' limit 1",
				dbesc($webbie)
			);

			if(! $r) {

				$r = q("insert into hubloc ( hubloc_guid, hubloc_hash, hubloc_addr, hubloc_network, hubloc_url, hubloc_host, hubloc_callback, hubloc_updated, hubloc_flags ) values ('%s','%s','%s','%s','%s','%s','%s','%s', %d)",
					dbesc($guid),
					dbesc($addr),
					dbesc($addr),
					dbesc($network),
					dbesc(trim($diaspora_base,'/')),
					dbesc($hostname),
					dbesc($notify),
					dbescdate(datetime_convert()),
					intval(HUBLOC_FLAGS_PRIMARY)
				);
			}
			$photos = import_profile_photo($vcard['photo'],$addr);
			$r = q("update xchan set xchan_photo_date = '%s', xchan_photo_l = '%s', xchan_photo_m = '%s', xchan_photo_s = '%s', xchan_photo_mimetype = '%s' where xchan_hash = '%s'",
				dbescdate(datetime_convert('UTC','UTC',$arr['photo_updated'])),
				dbesc($photos[0]),
				dbesc($photos[1]),
				dbesc($photos[2]),
				dbesc($photos[3]),
				dbesc($addr)
			);
			return true;

		}

	return false;

/*
	$vcard['fn'] = notags($vcard['fn']);
	$vcard['nick'] = str_replace(' ','',notags($vcard['nick']));

	$result['name'] = $vcard['fn'];
	$result['nick'] = $vcard['nick'];
	$result['guid'] = $guid;
	$result['url'] = $profile;
	$result['hostname'] = $hostname;
	$result['addr'] = $addr;
	$result['batch'] = $batch;
	$result['notify'] = $notify;
	$result['poll'] = $poll;
	$result['request'] = $request;
	$result['confirm'] = $confirm;
	$result['poco'] = $poco;
	$result['photo'] = $vcard['photo'];
	$result['priority'] = $priority;
	$result['network'] = $network;
	$result['alias'] = $alias;
	$result['pubkey'] = $pubkey;

	logger('probe_url: ' . print_r($result,true), LOGGER_DEBUG);

	return $result;

*/

/* Sample Diaspora result.

Array
(
	[name] => Mike Macgirvin
	[nick] => macgirvin
	[guid] => a9174a618f8d269a
	[url] => https://joindiaspora.com/u/macgirvin
	[hostname] => joindiaspora.com
	[addr] => macgirvin@joindiaspora.com
	[batch] => 
	[notify] => https://joindiaspora.com/receive
	[poll] => https://joindiaspora.com/public/macgirvin.atom
	[request] => 
	[confirm] => 
	[poco] => 
	[photo] => https://joindiaspora.s3.amazonaws.com/uploads/images/thumb_large_fec4e6eef13ae5e56207.jpg
	[priority] => 
	[network] => diaspora
	[alias] => 
	[pubkey] => -----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAtihtyIuRDWkDpCA+I1UaQ
jI4S7k625+A7EEJm+pL2ZVSJxeCKiFeEgHBQENjLMNNm8l8F6blxgQqE6ZJ9Spa7f
tlaXYTRCrfxKzh02L3hR7sNA+JS/nXJaUAIo+IwpIEspmcIRbD9GB7Wv/rr+M28uH
31EeYyDz8QL6InU/bJmnCdFvmEMBQxJOw1ih9tQp7UNJAbUMCje0WYFzBz7sfcaHL
OyYcCOqOCBLdGucUoJzTQ9iDBVzB8j1r1JkIHoEb2moUoKUp+tkCylNfd/3IVELF9
7w1Qjmit3m50OrJk2DQOXvCW9KQxaQNdpRPSwhvemIt98zXSeyZ1q/YjjOwG0DWDq
AF8aLj3/oQaZndTPy/6tMiZogKaijoxj8xFLuPYDTw5VpKquriVC0z8oxyRbv4t9v
8JZZ9BXqzmayvY3xZGGp8NulrfjW+me2bKh0/df1aHaBwpZdDTXQ6kqAiS2FfsuPN
vg57fhfHbL1yJ4oDbNNNeI0kJTGchXqerr8C20khU/cQ2Xt31VyEZtnTB665Ceugv
kp3t2qd8UpAVKl430S5Quqx2ymfUIdxdW08CEjnoRNEL3aOWOXfbf4gSVaXmPCR4i
LSIeXnd14lQYK/uxW/8cTFjcmddsKxeXysoQxbSa9VdDK+KkpZdgYXYrTTofXs6v+
4afAEhRaaY+MCAwEAAQ==
-----END PUBLIC KEY-----

)
*/




	}
}


function webfinger_rfc7033($webbie) {


	if(! strpos($webbie,'@'))
		return false;
	$lhs = substr($webbie,0,strpos($webbie,'@'));
	$rhs = substr($webbie,strpos($webbie,'@')+1);

	$resource = 'acct:' . $webbie;

	$s = z_fetch_url('https://' . $rhs . '/.well-known/webfinger?resource=' . $resource);

	if($s['success'])
		$j = json_decode($s['body'],true);
	else
		return false;
	return($j);
}


function old_webfinger($webbie) {

	$host = '';
	if(strstr($webbie,'@'))
		$host = substr($webbie,strpos($webbie,'@') + 1);

	if(strlen($host)) {
		$tpl = fetch_lrdd_template($host);
		logger('old_webfinger: lrdd template: ' . $tpl,LOGGER_DATA);
		if(strlen($tpl)) {
			$pxrd = str_replace('{uri}', urlencode('acct:' . $webbie), $tpl);
			logger('old_webfinger: pxrd: ' . $pxrd,LOGGER_DATA);
			$links = fetch_xrd_links($pxrd);
			if(! count($links)) {
				// try with double slashes
				$pxrd = str_replace('{uri}', urlencode('acct://' . $webbie), $tpl);
				logger('old_webfinger: pxrd: ' . $pxrd,LOGGER_DATA);
				$links = fetch_xrd_links($pxrd);
			}
			return $links;
		}
	}
	return array();
}


function fetch_lrdd_template($host) {
	$tpl = '';

	$url1 = 'https://' . $host . '/.well-known/host-meta' ;
	$url2 = 'http://' . $host . '/.well-known/host-meta' ;
	$links = fetch_xrd_links($url1);
	logger('fetch_lrdd_template from: ' . $url1, LOGGER_DEBUG);
	logger('template (https): ' . print_r($links,true),LOGGER_DEBUG);
	if(! count($links)) {
		logger('fetch_lrdd_template from: ' . $url2);
		$links = fetch_xrd_links($url2);
		logger('template (http): ' . print_r($links,true),LOGGER_DEBUG);
	}
	if(count($links)) {
		foreach($links as $link)
			if($link['@attributes']['rel'] && $link['@attributes']['rel'] === 'lrdd' && (!$link['@attributes']['type'] || $link['@attributes']['type'] === 'application/xrd+xml'))
				$tpl = $link['@attributes']['template'];
	}
	if(! strpos($tpl,'{uri}'))
		$tpl = '';
	return $tpl;

}


function fetch_xrd_links($url) {

logger('fetch_xrd_links: ' . $url);

	$redirects = 0;
	$x = z_fetch_url($url,false,$redirects,array('timeout' => 20));

	if(! $x['success'])
		return array();

	$xml = $x['body'];
	logger('fetch_xrd_links: ' . $xml, LOGGER_DATA);

	if ((! $xml) || (! stristr($xml,'<xrd')))
		return array();

	// fix diaspora's bad xml
	$xml = str_replace(array('href=&quot;','&quot;/>'),array('href="','"/>'),$xml);

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
		if(is_array($aliases) && count($aliases)) {
			foreach($aliases as $alias) {
				$links[]['@attributes'] = array('rel' => 'alias' , 'href' => $alias);
			}
		}
	}

	logger('fetch_xrd_links: ' . print_r($links,true), LOGGER_DATA);

	return $links;
}


function scrape_vcard($url) {

	$a = get_app();

	$ret = array();

	logger('scrape_vcard: url=' . $url);

	$x = z_fetch_url($url);
	if(! $x['success'])
		return $ret;

	$s = $x['body'];

	if(! $s)
		return $ret;

	$headers = $x['header'];
	$lines = explode("\n",$headers);
	if(count($lines)) {
		foreach($lines as $line) {
			// don't try and run feeds through the html5 parser
			if(stristr($line,'content-type:') && ((stristr($line,'application/atom+xml')) || (stristr($line,'application/rss+xml'))))
				return ret;
		}
	}

	try {
		$dom = HTML5_Parser::parse($s);
	} catch (DOMException $e) {
		logger('scrape_vcard: parse error: ' . $e);
	}

	if(! $dom)
		return $ret;

	// Pull out hCard profile elements

	$largest_photo = 0;

	$items = $dom->getElementsByTagName('*');
	foreach($items as $item) {
		if(attribute_contains($item->getAttribute('class'), 'vcard')) {
			$level2 = $item->getElementsByTagName('*');
			foreach($level2 as $x) {
				if(attribute_contains($x->getAttribute('class'),'fn'))
					$ret['fn'] = $x->textContent;
				if((attribute_contains($x->getAttribute('class'),'photo'))
					|| (attribute_contains($x->getAttribute('class'),'avatar'))) {
					$size = intval($x->getAttribute('width'));
					if(($size > $largest_photo) || (! $largest_photo)) {
						$ret['photo'] = $x->getAttribute('src');
						$largest_photo = $size;
					}
				}
				if((attribute_contains($x->getAttribute('class'),'nickname'))
					|| (attribute_contains($x->getAttribute('class'),'uid'))) {
					$ret['nick'] = $x->textContent;
				}
			}
		}
	}

	return $ret;
}



function scrape_feed($url) {

	$a = get_app();

	$ret = array();
	$level = 0;
	$x = z_fetch_url($url,false,$level,array('novalidate' => true));

	if(! $x['success'])
		return $ret;

	$headers = $x['header'];
	$code = $x['return_code'];
	$s = $x['body'];

	logger('scrape_feed: returns: ' . $code . ' headers=' . $headers, LOGGER_DEBUG);

	if(! $s) {
		logger('scrape_feed: no data returned for ' . $url);
		return $ret;
	}


	$lines = explode("\n",$headers);
	if(count($lines)) {
		foreach($lines as $line) {
			if(stristr($line,'content-type:')) {
				if(stristr($line,'application/atom+xml') || stristr($s,'<feed')) {
					$ret['feed_atom'] = $url;
					return $ret;
				}
 				if(stristr($line,'application/rss+xml') || stristr($s,'<rss')) {
					$ret['feed_rss'] = $url;
					return $ret;
				}
			}
		}
		// perhaps an RSS version 1 feed with a generic or incorrect content-type?
		if(stristr($s,'</item>')) {
			$ret['feed_rss'] = $url;
			return $ret;
		}
	}

	try {
		$dom = HTML5_Parser::parse($s);
	} catch (DOMException $e) {
		logger('scrape_feed: parse error: ' . $e);
	}

	if(! $dom) {
		logger('scrape_feed: failed to parse.');
		return $ret;
	}


	$head = $dom->getElementsByTagName('base');
	if($head) {
		foreach($head as $head0) {
			$basename = $head0->getAttribute('href');
			break;
		}
	}
	if(! $basename)
		$basename = implode('/', array_slice(explode('/',$url),0,3)) . '/';

	$items = $dom->getElementsByTagName('link');

	// get Atom/RSS link elements, take the first one of either.

	if($items) {
		foreach($items as $item) {
			$x = $item->getAttribute('rel');
			if(($x === 'alternate') && ($item->getAttribute('type') === 'application/atom+xml')) {
				if(! x($ret,'feed_atom'))
					$ret['feed_atom'] = $item->getAttribute('href');
			}
			if(($x === 'alternate') && ($item->getAttribute('type') === 'application/rss+xml')) {
				if(! x($ret,'feed_rss'))
					$ret['feed_rss'] = $item->getAttribute('href');
			}
		}
	}

	// Drupal and perhaps others only provide relative URL's. Turn them into absolute.

	if(x($ret,'feed_atom') && (! strstr($ret['feed_atom'],'://')))
		$ret['feed_atom'] = $basename . $ret['feed_atom'];
	if(x($ret,'feed_rss') && (! strstr($ret['feed_rss'],'://')))
		$ret['feed_rss'] = $basename . $ret['feed_rss'];

	return $ret;
}



function service_plink($contact, $guid) {

	$plink = '';

	$m = parse_url($contact['xchan_url']);
	if($m) {
		$url = $m['scheme'] . '://' . $m['host'] . (($m['port']) ? ':' . $m['port'] : '');
	}
	else
		$url = 'https://' . substr($contact['xchan_addr'],strpos($contact['xchan_addr'],'@')+1);

	$handle = substr($contact['xchan_addr'], 0, strpos($contact['xchan_addr'],'@'));

	if($contact['xchan_network'] === 'diaspora')
		$plink = $url . '/posts/' . $guid;
	if($contact['xchan_network'] === 'friendica-over-diaspora')
		$plink = $url . '/display/' . $handle . '/' . $guid;
	if($contact['xchan_network'] === 'zot')
		$plink = $url . '/channel/' . $handle . '?f=&mid=' . $guid;

	return $plink;
}
