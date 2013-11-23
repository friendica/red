<?php /** @file */

require_once("bbcode.php");
require_once("datetime.php");
require_once("conversation.php");
require_once("oauth.php");
require_once("html2plain.php");
require_once('include/security.php');
require_once('include/photos.php');

	/*
	 *
	 * Red API. Loosely based on and possibly compatible with a Twitter-Like API but all similarities end there. 
	 *
	 */


	/**
	 ** TWITTER API
	 */

	$API = array();

	$called_api = Null;

	// All commands which require authentication accept a "channel" parameter
	// which is the left hand side of the channel address/nickname.
	// If provided, the desired channel is selected before caarying out the command.
	// If not provided, the default channel associated with the account is used.   
	// If channel selection fails, the API command requiring login will fail. 

	function api_user() {
		$aid = get_account_id();
		$channel = get_app()->get_channel();
		
		if(($aid) && (x($_REQUEST,'channel'))) {

			// Only change channel if it is different than the current channel

			if($channel && x($channel,'channel_address') && $channel['channel_address'] != $_REQUEST['channel']) {
				$c = q("select channel_id from channel where channel_address = '%s' and channel_account_id = %d limit 1",
					dbesc($_REQUEST['channel']),
					intval($aid)
				);
				if((! $c) || (! change_channel($c[0]['channel_id'])))
					return false;
			}
		}			
		if ($_SESSION["allow_api"])
			return local_user();
		return false;
	}


	function api_date($str){
		//Wed May 23 06:01:13 +0000 2007
		return datetime_convert('UTC', 'UTC', $str, "D M d H:i:s +0000 Y" );
	}


	function api_register_func($path, $func, $auth=false){
		global $API;
		$API[$path] = array('func'=>$func,
							'auth'=>$auth);
	}

	/**
	 * Simple HTTP Login
	 */

	function api_login(&$a){
		// login with oauth
		try {
			$oauth = new FKOAuth1();
			$req = OAuthRequest::from_request();
			list($consumer,$token) = $oauth->verify_request($req);
//			list($consumer,$token) = $oauth->verify_request(OAuthRequest::from_request());
			if (!is_null($token)){
				$oauth->loginUser($token->uid);
				call_hooks('logged_in', $a->user);
				return;
			}
			echo __file__.__line__.__function__."<pre>"; 
			var_dump($consumer, $token); 
			die();
		}
		catch(Exception $e) {
			logger(__file__.__line__.__function__."\n".$e);
		}

		
		// workaround for HTTP-auth in CGI mode
		if(x($_SERVER,'REDIRECT_REMOTE_USER')) {
		 	$userpass = base64_decode(substr($_SERVER["REDIRECT_REMOTE_USER"],6)) ;
			if(strlen($userpass)) {
			 	list($name, $password) = explode(':', $userpass);
				$_SERVER['PHP_AUTH_USER'] = $name;
				$_SERVER['PHP_AUTH_PW'] = $password;
			}
		}

		if(x($_SERVER,'HTTP_AUTHORIZATION')) {
		 	$userpass = base64_decode(substr($_SERVER["HTTP_AUTHORIZATION"],6)) ;
			if(strlen($userpass)) {
			 	list($name, $password) = explode(':', $userpass);
				$_SERVER['PHP_AUTH_USER'] = $name;
				$_SERVER['PHP_AUTH_PW'] = $password;
			}
		}


		if (!isset($_SERVER['PHP_AUTH_USER'])) {
		   logger('API_login: ' . print_r($_SERVER,true), LOGGER_DEBUG);
		    header('WWW-Authenticate: Basic realm="Red"');
		    header('HTTP/1.0 401 Unauthorized');
		    die('This api requires login');
		}
		
		// process normal login request
		require_once('include/auth.php');
		$record = account_verify_password($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW']);
		if(! $record) {
		   logger('API_login failure: ' . print_r($_SERVER,true), LOGGER_DEBUG);
		    header('WWW-Authenticate: Basic realm="Red"');
		    header('HTTP/1.0 401 Unauthorized');
		    die('This api requires login');
		}

		require_once('include/security.php');
		authenticate_success($record);
		$_SESSION['allow_api'] = true;
	}
	
	/**************************
	 *  MAIN API ENTRY POINT  *
	 **************************/

	function api_call(&$a){
		GLOBAL $API, $called_api;

		// preset
		$type="json";

		foreach ($API as $p=>$info){
			if (strpos($a->query_string, $p)===0){
				$called_api= explode("/",$p);
				//unset($_SERVER['PHP_AUTH_USER']);
				if ($info['auth'] === true && api_user() === false) {
						api_login($a);
				}

				load_contact_links(api_user());

				$channel = $a->get_channel();

				logger('API call for ' . $channel['channel_name'] . ': ' . $a->query_string);
				logger('API parameters: ' . print_r($_REQUEST,true));

				$type="json";

				if (strpos($a->query_string, ".xml")>0) $type="xml";
				if (strpos($a->query_string, ".json")>0) $type="json";
				if (strpos($a->query_string, ".rss")>0) $type="rss";
				if (strpos($a->query_string, ".atom")>0) $type="atom";
				if (strpos($a->query_string, ".as")>0) $type="as";

				$r = call_user_func($info['func'], $a, $type);
				if ($r===false) return;

				switch($type){
					case "xml":
						$r = mb_convert_encoding($r, "UTF-8",mb_detect_encoding($r));
						header ("Content-Type: text/xml");
						return '<?xml version="1.0" encoding="UTF-8"?>'."\n".$r;
						break;
					case "json":
						header ("Content-Type: application/json");
						foreach($r as $rr)
						    return json_encode($rr);
						break;
					case "rss":
						header ("Content-Type: application/rss+xml");
						return '<?xml version="1.0" encoding="UTF-8"?>'."\n".$r;
						break;
					case "atom":
						header ("Content-Type: application/atom+xml");
						return '<?xml version="1.0" encoding="UTF-8"?>'."\n".$r;
						break;
					case "as":
						//header ("Content-Type: application/json");
						//foreach($r as $rr)
						//    return json_encode($rr);
						return json_encode($r);
						break;

				}
				//echo "<pre>"; var_dump($r); die();
			}
		}
		header("HTTP/1.1 404 Not Found");
		logger('API call not implemented: '.$a->query_string." - ".print_r($_REQUEST,true));
		$r = '<status><error>not implemented</error></status>';
		switch($type){
			case "xml":
				header ("Content-Type: text/xml");
				return '<?xml version="1.0" encoding="UTF-8"?>'."\n".$r;
				break;
			case "json":
				header ("Content-Type: application/json");
			    return json_encode(array('error' => 'not implemented'));
				break;
			case "rss":
				header ("Content-Type: application/rss+xml");
				return '<?xml version="1.0" encoding="UTF-8"?>'."\n".$r;
				break;
			case "atom":
				header ("Content-Type: application/atom+xml");
				return '<?xml version="1.0" encoding="UTF-8"?>'."\n".$r;
				break;
		}
	}

	/**
	 * RSS extra info
	 */
	function api_rss_extra(&$a, $arr, $user_info){
		if (is_null($user_info)) $user_info = api_get_user($a);
		$arr['$user'] = $user_info;
		$arr['$rss'] = array(
			'alternate' => $user_info['url'],
			'self' => $a->get_baseurl(). "/". $a->query_string,
			'base' => $a->get_baseurl(),
			'updated' => api_date(null),
			'atom_updated' => datetime_convert('UTC','UTC','now',ATOM_TIME),
			'language' => $user_info['language'],
			'logo'	=> $a->get_baseurl()."/images/rm-64.png",
		);
		
		return $arr;
	}
	 
	/**
	 * Returns user info array.
	 */

	function api_get_user(&$a, $contact_id = Null){
		global $called_api;
		$user = null;
		$extra_query = "";


		if(!is_null($contact_id)){
			$user=$contact_id;
			$extra_query = " AND abook_id = %d ";
		}
		
		if(is_null($user) && x($_GET, 'user_id')) {
			$user = intval($_GET['user_id']);	
			$extra_query = " AND abook_id = %d ";
		}
		if(is_null($user) && x($_GET, 'screen_name')) {
			$user = dbesc($_GET['screen_name']);	
			$extra_query = " AND xchan_addr like '%s@%%' ";
			if (api_user()!==false)
				$extra_query .= " AND abook_channel = ".intval(api_user());
		}
		
		if (is_null($user) && argc() > (count($called_api)-1)){
			$argid = count($called_api);
			list($user, $null) = explode(".",argv($argid));
			if(is_numeric($user)){
				$user = intval($user);
				$extra_query = " AND abook_id = %d ";
			} else {
				$user = dbesc($user);
				$extra_query = " AND xchan_addr like '%s@%%' ";
				if (api_user() !== false)
					$extra_query .= " AND abook_channel = ".intval(api_user());
			}
		}
		
		if (! $user) {
			if (api_user() === false) {
				api_login($a); 
				return False;
			} else {
				$user = local_user();
				$extra_query = " AND abook_channel = %d AND (abook_flags & " . ABOOK_FLAG_SELF . " ) ";
			}
			
		}
		
		logger('api_user: ' . $extra_query . ', user: ' . $user);
		// user info		
		$uinfo = q("SELECT * from abook left join xchan on abook_xchan = xchan_hash 
				WHERE 1
				$extra_query",
				$user
		);
		if (count($uinfo)==0) {
			return False;
		}
		
		if($uinfo[0]['abook_flags'] & ABOOK_FLAG_SELF) {
			$usr = q("select * from channel where channel_id = %d limit 1",
				intval(api_user())
			);
			$profile = q("select * from profile where uid = %d and `is_default` = 1 limit 1",
				intval(api_user())
			);

			// count public wall messages
			$r = q("SELECT COUNT(`id`) as `count` FROM `item`
					WHERE `uid` = %d
					AND ( item_flags & %d ) and item_restrict = 0 
					AND `allow_cid`='' AND `allow_gid`='' AND `deny_cid`='' AND `deny_gid`=''",
					intval($usr[0]['channel_id']),
					intval(ITEM_WALL)
			);
			$countitms = $r[0]['count'];
		}
		else {
			$r = q("SELECT COUNT(`id`) as `count` FROM `item`
					WHERE author_xchan = '%s'
					AND `allow_cid`='' AND `allow_gid`='' AND `deny_cid`='' AND `deny_gid`=''",
					intval($uinfo[0]['xchan_hash'])
			);
			$countitms = $r[0]['count'];
		}


		// count friends
		if($usr) {
			$r = q("SELECT COUNT(abook_id) as `count` FROM abook
					WHERE abook_channel = %d AND abook_flags = 0 ",
					intval($usr[0]['channel_id'])
			);
			$countfriends = $r[0]['count'];
			$countfollowers = $r[0]['count'];
		}

		$r = q("SELECT count(`id`) as `count` FROM item where ( item_flags & %d ) and uid = %d and item_restrict = 0",
			intval($uinfo[0]['channel_id']),
			intval(ITEM_STARRED)
		);
		$starred = $r[0]['count'];
	

		if(! ($uinfo[0]['abook_flags'] & ABOOK_FLAG_SELF)) {
			$countfriends = 0;
			$countfollowers = 0;
			$starred = 0;
		}

		$ret = Array(
			'id' => intval($uinfo[0]['abook_id']),
			'self' => (($uinfo[0]['abook_flags'] & ABOOK_FLAG_SELF) ? 1 : 0),
			'uid' => intval($uinfo[0]['abook_channel']),
			'guid' => $uinfo[0]['xchan_hash'],
			'name' => (($uinfo[0]['xchan_name']) ? $uinfo[0]['xchan_name'] : substr($uinfo[0]['xchan_addr'],0,strpos($uinfo[0]['xchan_addr'],'@'))),
			'screen_name' => substr($uinfo[0]['xchan_addr'],0,strpos($uinfo[0]['xchan_addr'],'@')),
			'location' => ($usr) ? $usr[0]['channel_location'] : '',
			'profile_image_url' => $uinfo[0]['xchan_photo_l'],
			'url' => $uinfo[0]['xchan_url'],
			'contact_url' => $a->get_baseurl()."/connections/".$uinfo[0]['abook_id'],
			'protected' => false,	
			'friends_count' => intval($countfriends),
			'created_at' => api_date($uinfo[0]['abook_created']),
			'utc_offset' => "+00:00",
			'time_zone' => 'UTC', //$uinfo[0]['timezone'],
			'geo_enabled' => false,
			'statuses_count' => intval($countitms), #XXX: fix me 
			'lang' => get_app()->language,
			'description' => (($profile) ? $profile[0]['pdesc'] : ''),
			'followers_count' => intval($countfollowers),
			'favourites_count' => intval($starred),
			'contributors_enabled' => false,
			'follow_request_sent' => true,
			'profile_background_color' => 'cfe8f6',
			'profile_text_color' => '000000',
			'profile_link_color' => 'FF8500',
			'profile_sidebar_fill_color' =>'AD0066',
			'profile_sidebar_border_color' => 'AD0066',
			'profile_background_image_url' => '',
			'profile_background_tile' => false,
			'profile_use_background_image' => false,
			'notifications' => false,
			'following' => '', #XXX: fix me
			'verified' => true, #XXX: fix me
			'status' => array()
		);
	
		return $ret;
		
	}


	function api_item_get_user(&$a, $item) {
		global $usercache;

		// The author is our direct contact, in a conversation with us.

		if($item['author']['abook_id']) {
			return api_get_user($a,$item['author']['abook_id']);
		}	
		
		// We don't know this person directly.
		
		$nick = substr($item['author']['xchan_addr'],0,strpos($item['author']['xchan_addr'],'@'));
		$name = $item['author']['xchan_name'];

		// Generating a random ID
		if (is_null($usercache[$nick]) or !array_key_exists($nick, $usercache))
			$usercache[$nick] = mt_rand(2000000, 2100000);

		$ret = array(
			'id' => $usercache[$nick],
			'name' => $name,
			'screen_name' => $nick,
			'location' => '', //$uinfo[0]['default-location'],
			'description' => '',
			'profile_image_url' => $item['author']['xchan_photo_m'],
			'url' => $item['author']['xchan_url'],
			'protected' => false,	#
			'followers_count' => 0,
			'friends_count' => 0,
			'created_at' => '',
			'favourites_count' => 0,
			'utc_offset' => 0, #XXX: fix me
			'time_zone' => '', //$uinfo[0]['timezone'],
			'statuses_count' => 0,
			'following' => 1,
			'statusnet_blocking' => false,
			'notifications' => false,
			'uid' => 0,
			'contact_url' => 0,
			'geo_enabled' => false,
			'lang' => 'en', #XXX: fix me
			'contributors_enabled' => false,
			'follow_request_sent' => false,
			'profile_background_color' => 'cfe8f6',
			'profile_text_color' => '000000',
			'profile_link_color' => 'FF8500',
			'profile_sidebar_fill_color' =>'AD0066',
			'profile_sidebar_border_color' => 'AD0066',
			'profile_background_image_url' => '',
			'profile_background_tile' => false,
			'profile_use_background_image' => false,
			'verified' => true, #XXX: fix me
			'followers' => '', #XXX: fix me
			'status' => array()
		);

		return $ret; 
	}


	/**
	 *  load api $templatename for $type and replace $data array
	 */
	function api_apply_template($templatename, $type, $data){

		$a = get_app();

		switch($type){
			case "atom":
			case "rss":
			case "xml":
				$data = array_xmlify($data);
				$tpl = get_markup_template("api_".$templatename."_".$type.".tpl");
				$ret = replace_macros($tpl, $data);
				break;
			case "json":
				$ret = $data;
				break;
		}
		return $ret;
	}
	
	
	/**
	 * Returns an HTTP 200 OK response code and a representation of the requesting user if authentication was successful; 
	 * returns a 401 status code and an error message if not. 
	 * http://developer.twitter.com/doc/get/account/verify_credentials
	 */
	function api_account_verify_credentials(&$a, $type){
		if (api_user()===false) return false;
		$user_info = api_get_user($a);
		
		return api_apply_template("user", $type, array('$user' => $user_info));

	}
	api_register_func('api/account/verify_credentials','api_account_verify_credentials', true);


	function api_account_logout(&$a, $type){
		require_once('include/auth.php');
		nuke_session();
		return api_apply_template("user", $type, array('$user' => null));

	}
	api_register_func('api/account/logout','api_account_logout', false);
	 	


	/**
	 * get data from $_REQUEST ( e.g. $_POST or $_GET )
	 */
	function requestdata($k) {
		if(array_key_exists($k,$_REQUEST))
			return $_REQUEST[$k];
		return null;
	}


	/*
	 * Red basic channel export
	 */

	function api_export_basic(&$a, $type) {
		if(api_user() === false) {
			logger('api_export_basic: no user');
			return false;
		}

		require_once('include/identity.php');

		json_return_and_die(identity_basic_export(api_user()));	
	}
	api_register_func('api/export/basic','api_export_basic', true);
	api_register_func('api/red/channel/export/basic','api_export_basic', true);


	function api_channel_stream(&$a, $type) {
		if(api_user() === false) {
			logger('api_channel_stream: no user');
			return false;
		}

		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			json_return_and_die(post_activity_item($_REQUEST));
		}
		else {
			// fetch stream

		}
	}
	api_register_func('api/red/channel/stream','api_channel_stream', true);


	function api_albums(&$a,$type) {
		json_return_and_die(photos_albums_list($a->get_channel(),$a->get_observer()));
	}
	api_register_func('api/red/albums','api_albums', true);

	function api_photos(&$a,$type) {
		$album = $_REQUEST['album'];
		json_return_and_die(photos_list_photos($a->get_channel(),$a->get_observer()),$album);
	}
	api_register_func('api/red/photos','api_photos', true);







    function api_statuses_mediap(&$a, $type) {
		if (api_user() === false) {
			logger('api_statuses_update: no user');
			return false;
		}
		$user_info = api_get_user($a);

		$_REQUEST['type'] = 'wall';
		$_REQUEST['profile_uid'] = api_user();
		$_REQUEST['api_source'] = true;
                
		$txt = requestdata('status');

		require_once('library/HTMLPurifier.auto.php');
		require_once('include/html2bbcode.php');

		if((strpos($txt,'<') !== false) || (strpos($txt,'>') !== false)) {
			$txt = html2bb_video($txt);
			$config = HTMLPurifier_Config::createDefault();
			$config->set('Cache.DefinitionImpl', null);
			$purifier = new HTMLPurifier($config);
			$txt = $purifier->purify($txt);
		}
		$txt = html2bbcode($txt);
		
        $a->argv[1] = $user_info['screen_name'];
		
		$_REQUEST['silent']='1'; //tell wall_upload function to return img info instead of echo
		require_once('mod/wall_upload.php');
		$posted = wall_upload_post($a);
                
		//now that we have the img url in bbcode we can add it to the status and insert the wall item.
		$_REQUEST['body']=$txt."\n\n".$posted;
		require_once('mod/item.php');
		item_post($a);

		// this should output the last post (the one we just posted).
		return api_status_show($a,$type);
	}
	api_register_func('api/statuses/mediap','api_statuses_mediap', true);

	function api_statuses_update(&$a, $type) {
		if (api_user() === false) {
			logger('api_statuses_update: no user');
			return false;
		}

		logger('api_statuses_update: REQUEST ' . print_r($_REQUEST,true));
		logger('api_statuses_update: FILES ' . print_r($_FILES,true));


		// set this so that the item_post() function is quiet and doesn't redirect or emit json

		$_REQUEST['api_source'] = true;


		$user_info = api_get_user($a);

		// convert $_POST array items to the form we use for web posts.

		// logger('api_post: ' . print_r($_POST,true));

		if(requestdata('htmlstatus')) {
			require_once('library/HTMLPurifier.auto.php');
			require_once('include/html2bbcode.php');

			$txt = requestdata('htmlstatus');
			if((strpos($txt,'<') !== false) || (strpos($txt,'>') !== false)) {

				$txt = html2bb_video($txt);

				$config = HTMLPurifier_Config::createDefault();
				$config->set('Cache.DefinitionImpl', null);


				$purifier = new HTMLPurifier($config);
				$txt = $purifier->purify($txt);

				$_REQUEST['body'] = html2bbcode($txt);
			}

		}
		else
			$_REQUEST['body'] = requestdata('status');

		$parent = requestdata('in_reply_to_status_id');

		if(ctype_digit($parent))
			$_REQUEST['parent'] = $parent;
		else
			$_REQUEST['parent_mid'] = $parent;

		if(requestdata('lat') && requestdata('long'))
			$_REQUEST['coord'] = sprintf("%s %s",requestdata('lat'),requestdata('long'));

		$_REQUEST['profile_uid'] = api_user();

		if($parent)
			$_REQUEST['type'] = 'net-comment';
		else {
			$_REQUEST['type'] = 'wall';
		
			if(x($_FILES,'media')) {
				$_FILES['userfile'] = $_FILES['media'];
				// upload the image if we have one
				$_REQUEST['silent']='1'; //tell wall_upload function to return img info instead of echo
				require_once('mod/wall_upload.php');
				$media = wall_upload_post($a);
				if(strlen($media)>0)
					$_REQUEST['body'] .= "\n\n".$media;
			}
		}

		// call out normal post function

		require_once('mod/item.php');
		item_post($a);	

		// this should output the last post (the one we just posted).
		return api_status_show($a,$type);
	}
	api_register_func('api/statuses/update','api_statuses_update', true);


	function api_status_show(&$a, $type){
		$user_info = api_get_user($a);

		// get last public message

		require_once('include/security.php');

		$lastwall = q("SELECT * from item where 1
			and item_private = 0 and item_restrict = 0
			and author_xchan = '%s'
			and allow_cid = '' and allow_gid = '' and deny_cid = '' and deny_gid = ''
			and verb = '%s'
			and uid in ( " . stream_perms_api_uids() . " )
			order by created desc limit 1",
			dbesc($user_info['guid']),
			dbesc(ACTIVITY_POST)
		);

		if($lastwall){
			$lastwall = $lastwall[0];
			
			$in_reply_to_status_id = '';
			$in_reply_to_user_id = '';
			$in_reply_to_screen_name = '';

			if($lastwall['author_xchan'] != $lastwall['owner_xchan']) {
				$w = q("select * from abook left join xchan on abook_xchan = xchan_hash where
					xchan_hash = '%s' limit 1",
					dbesc($lastwall['owner_xchan'])
				);
				if($w) {
					$in_reply_to_user_id = $w[0]['abook_id'];
					$in_reply_to_screen_name = substr($w[0]['xchan_addr'],0,strpos($w[0]['xchan_addr'],'@'));
				}
			}
			
			if ($lastwall['parent']!=$lastwall['id']) {
				$in_reply_to_status_id=$lastwall['thr_parent'];
				if(! $in_reply_to_user_id) {
					$in_reply_to_user_id = $user_info['id'];
					$in_reply_to_screen_name = $user_info['screen_name'];
				}
			}
			unobscure($lastwall);  
			$status_info = array(
				'text' => html2plain(prepare_text($lastwall['body'],$lastwall['mimetype']), 0),
				'truncated' => false,
				'created_at' => api_date($lastwall['created']),
				'in_reply_to_status_id' => $in_reply_to_status_id,
				'source' => (($lastwall['app']) ? $lastwall['app'] : 'web'),
				'id' => (($w) ? $w[0]['abook_id'] : $user_info['id']),
				'in_reply_to_user_id' => $in_reply_to_user_id,
				'in_reply_to_screen_name' => $in_reply_to_screen_name,
				'geo' => '',
				'favorited' => false,
				'coordinates' => $lastwall['coord'],
				'place' => $lastwall['location'],
				'contributors' => ''					
			);
			$status_info['user'] = $user_info;
		}

		return  api_apply_template("status", $type, array('$status' => $status_info));
		
	}

		
	/**
	 * Returns extended information of a given user, specified by ID or screen name as per the required id parameter.
	 * The author's most recent status will be returned inline.
	 * http://developer.twitter.com/doc/get/users/show
	 */

// FIXME - this is essentially the same as api_status_show except for the template formatting at the end. Consolidate.
 

	function api_users_show(&$a, $type){
		$user_info = api_get_user($a);

		require_once('include/security.php');

		$lastwall = q("SELECT * from item where 1
			and item_private != 0 and item_restrict = 0
			and author_xchan = '%s'
			and allow_cid = '' and allow_gid = '' and deny_cid = '' and deny_gid = ''
			and verb = '%s'
			and uid in ( " . stream_perms_api_uids() . " )
			order by created desc limit 1",
			dbesc($user_info['guid']),
			dbesc(ACTIVITY_POST)
		);

		if($lastwall){
			$lastwall = $lastwall[0];
			
			$in_reply_to_status_id = '';
			$in_reply_to_user_id = '';
			$in_reply_to_screen_name = '';

			if($lastwall['author_xchan'] != $lastwall['owner_xchan']) {
				$w = q("select * from abook left join xchan on abook_xchan = xchan_hash where
					xchan_hash = '%s' limit 1",
					dbesc($lastwall['owner_xchan'])
				);
				if($w) {
					$in_reply_to_user_id = $w[0]['abook_id'];
					$in_reply_to_screen_name = substr($w[0]['xchan_addr'],0,strpos($w[0]['xchan_addr'],'@'));
				}
			}
			
			if ($lastwall['parent']!=$lastwall['id']) {
				$in_reply_to_status_id=$lastwall['thr_parent'];
				if(! $in_reply_to_user_id) {
					$in_reply_to_user_id = $user_info['id'];
					$in_reply_to_screen_name = $user_info['screen_name'];
				}
			}  
			unobscure($lastwall);
			$user_info['status'] = array(
				'text' => html2plain(prepare_text($lastwall['body'],$lastwall['mimetype']), 0),
				'truncated' => false,
				'created_at' => api_date($lastwall['created']),
				'in_reply_to_status_id' => $in_reply_to_status_id,
				'source' => (($lastwall['app']) ? $lastwall['app'] : 'web'),
				'id' => (($w) ? $w[0]['abook_id'] : $user_info['id']),
				'in_reply_to_user_id' => $in_reply_to_user_id,
				'in_reply_to_screen_name' => $in_reply_to_screen_name,
				'geo' => '',
				'favorited' => false,
				'coordinates' => $lastwall['coord'],
				'place' => $lastwall['location'],
				'contributors' => ''					
			);

		}
		return  api_apply_template("user", $type, array('$user' => $user_info));

	}
	api_register_func('api/users/show','api_users_show');

	/**
	 *
	 * http://developer.twitter.com/doc/get/statuses/home_timeline
	 *
	 * TODO: Optional parameters
	 * TODO: Add reply info
	 */

	function api_statuses_home_timeline(&$a, $type){
		if (api_user() === false) 
			return false;

		$user_info = api_get_user($a);
		// get last network messages


		// params
		$count           = (x($_REQUEST,'count')?$_REQUEST['count']:20);
		$page            = (x($_REQUEST,'page')?$_REQUEST['page']-1:0);
		if($page < 0) 
			$page = 0;
		$since_id        = (x($_REQUEST,'since_id')?$_REQUEST['since_id']:0);
		$max_id          = (x($_REQUEST,'max_id')?$_REQUEST['max_id']:0);
		$exclude_replies = (x($_REQUEST,'exclude_replies')?1:0);
		//$since_id = 0;//$since_id = (x($_REQUEST,'since_id')?$_REQUEST['since_id']:0);

		$start = $page*$count;

		//$include_entities = (x($_REQUEST,'include_entities')?$_REQUEST['include_entities']:false);

		$sql_extra = '';
		if ($max_id > 0)
			$sql_extra .= ' AND `item`.`id` <= '.intval($max_id);
		if ($exclude_replies > 0)
			$sql_extra .= ' AND `item`.`parent` = `item`.`id`';

		if (api_user() != $user_info['uid']) {
			$observer = get_app()->get_observer();
			require_once('include/permissions.php');
			if(! perm_is_allowed($user_info['uid'],(($observer) ? $observer['xchan_hash'] : ''),'view_stream'))
				return '';
			$sql_extra .= " and item_private = 0 ";
		}

		$r = q("SELECT * from item WHERE uid = %d and item_restrict = 0
			$sql_extra
			AND id > %d
			ORDER BY received DESC LIMIT %d ,%d ",
			intval($user_info['uid']),
			intval($since_id),
			intval($start),	
			intval($count)
		);

		xchan_query($r,true);

		$ret = api_format_items($r,$user_info);

		// We aren't going to try to figure out at the item, group, and page
		// level which items you've seen and which you haven't. If you're looking
		// at the network timeline just mark everything seen. 
	
		if (api_user() == $user_info['uid']) {
			$r = q("UPDATE `item` SET item_flags = ( item_flags ^ %d )
				WHERE item_flags & %d and uid = %d",
				intval(ITEM_UNSEEN),
				intval(ITEM_UNSEEN),
				intval($user_info['uid'])
			);
		}


		$data = array('$statuses' => $ret);
		switch($type){
			case "atom":
			case "rss":
				$data = api_rss_extra($a, $data, $user_info);
				break;
			case "as":
				$as = api_format_as($a, $ret, $user_info);
				$as['title'] = $a->config['sitename']." Home Timeline";
				$as['link']['url'] = $a->get_baseurl()."/".$user_info["screen_name"]."/all";
				return($as);
				break;
		}

		return  api_apply_template("timeline", $type, $data);
	}
	api_register_func('api/statuses/home_timeline','api_statuses_home_timeline', true);
	api_register_func('api/statuses/friends_timeline','api_statuses_home_timeline', true);

	function api_statuses_public_timeline(&$a, $type){
		if (api_user()===false) return false;

		$user_info = api_get_user($a);
		// get last newtork messages


		// params
		$count = (x($_REQUEST,'count')?$_REQUEST['count']:20);
		$page = (x($_REQUEST,'page')?$_REQUEST['page']-1:0);
		if ($page<0) $page=0;
		$since_id = (x($_REQUEST,'since_id')?$_REQUEST['since_id']:0);
		$max_id = (x($_REQUEST,'max_id')?$_REQUEST['max_id']:0);
		//$since_id = 0;//$since_id = (x($_REQUEST,'since_id')?$_REQUEST['since_id']:0);

		$start = $page*$count;

		//$include_entities = (x($_REQUEST,'include_entities')?$_REQUEST['include_entities']:false);

		if ($max_id > 0)
			$sql_extra = 'AND `item`.`id` <= '.intval($max_id);
		require_once('include/security.php');

        $r = q("select * from item where item_restrict = 0
			and allow_cid = ''  and allow_gid = ''
			and deny_cid  = ''  and deny_gid  = ''
            and item_private = 0
			and uid in ( " . stream_perms_api_uids() . " )
			$sql_extra
			AND id > %d group by mid
            order by received desc LIMIT %d, %d ",
			intval($since_id),
			intval($start),
			intval($count)
		);

		xchan_query($r,true);

		$ret = api_format_items($r,$user_info);


		$data = array('$statuses' => $ret);
		switch($type){
			case "atom":
			case "rss":
				$data = api_rss_extra($a, $data, $user_info);
				break;
			case "as":
				$as = api_format_as($a, $ret, $user_info);
				$as['title'] = $a->config['sitename']. " " . t('Public Timeline');
				$as['link']['url'] = $a->get_baseurl()."/";
				return($as);
				break;
		}

		return  api_apply_template("timeline", $type, $data);
	}
	api_register_func('api/statuses/public_timeline','api_statuses_public_timeline', true);

	/**
	 * 

	 */
	function api_statuses_show(&$a, $type){
		if (api_user()===false) return false;

		$user_info = api_get_user($a);

		// params
		$id = intval(argv(3));

		logger('API: api_statuses_show: '.$id);

		//$include_entities = (x($_REQUEST,'include_entities')?$_REQUEST['include_entities']:false);
		$conversation = (x($_REQUEST,'conversation')?1:0);

		$sql_extra = '';
		if ($conversation)
			$sql_extra .= " AND `item`.`parent` = %d  ORDER BY `received` ASC ";
		else
			$sql_extra .= " AND `item`.`id` = %d";

		$r = q("select * from item where item_restrict = 0 $sql_extra",
			intval($id)
		);
		xchan_query($r,true);

		$ret = api_format_items($r,$user_info);

		if ($conversation) {
			$data = array('$statuses' => $ret);
			return api_apply_template("timeline", $type, $data);
		} else {
			$data = array('$status' => $ret[0]);
			/*switch($type){
				case "atom":
				case "rss":
					$data = api_rss_extra($a, $data, $user_info);
			}*/
			return  api_apply_template("status", $type, $data);
		}
	}
	api_register_func('api/statuses/show','api_statuses_show', true);


	/**
	 * 
	 */
	function api_statuses_repeat(&$a, $type){
		if (api_user()===false) return false;

		$user_info = api_get_user($a);

		// params
		$id = intval(argv(3));

		logger('API: api_statuses_repeat: '.$id);

		//$include_entities = (x($_REQUEST,'include_entities')?$_REQUEST['include_entities']:false);

		$observer = get_app()->get_observer();

		$r = q("SELECT * from item where item_restrict = 0 and id = %d limit 1",
			intval($id)
		);

		if(perm_is_allowed($r[0]['uid'],$observer['xchan_hash'],'view_stream')) {
			if ($r[0]['body'] != "") {
				$_REQUEST['body'] = html_entity_decode("&#x2672; ", ENT_QUOTES, 'UTF-8')."[zrl=".$r[0]['reply_url']."]".$r[0]['reply_author']."[/zrl] \n".$r[0]['body'];
				$_REQUEST['profile_uid'] = api_user();
				$_REQUEST['type'] = 'wall';
				$_REQUEST['api_source'] = true;

				require_once('mod/item.php');
				item_post($a);
			}
		}
		else
			return false;

		if ($type == 'xml')
			$ok = "true";
		else
			$ok = "ok";

		return api_apply_template('test', $type, array('$ok' => $ok));
	}
	api_register_func('api/statuses/retweet','api_statuses_repeat', true);

	/**
	 * 
	 */

	function api_statuses_destroy(&$a, $type){
		if (api_user()===false) return false;

		$user_info = api_get_user($a);

		// params
		$id = intval(argv(3));

		logger('API: api_statuses_destroy: '.$id);

		require_once('include/items.php');
		drop_item($id, false);

		if ($type == 'xml')
			$ok = "true";
		else
			$ok = "ok";

		return api_apply_template('test', $type, array('$ok' => $ok));
	}
	api_register_func('api/statuses/destroy','api_statuses_destroy', true);

	/**
	 * 
	 * http://developer.twitter.com/doc/get/statuses/mentions
	 * 
	 */

// FIXME
	function api_statuses_mentions(&$a, $type){
		if (api_user()===false) return false;
				
		$user_info = api_get_user($a);
		// get last newtork messages


		// params
		$count = (x($_REQUEST,'count')?$_REQUEST['count']:20);
		$page = (x($_REQUEST,'page')?$_REQUEST['page']-1:0);
		if ($page<0) $page=0;
		$since_id = (x($_REQUEST,'since_id')?$_REQUEST['since_id']:0);
		$max_id = (x($_REQUEST,'max_id')?$_REQUEST['max_id']:0);
		//$since_id = 0;//$since_id = (x($_REQUEST,'since_id')?$_REQUEST['since_id']:0);

		$start = $page*$count;

		//$include_entities = (x($_REQUEST,'include_entities')?$_REQUEST['include_entities']:false);

		$myurl = $a->get_baseurl() . '/channel/'. $a->user['nickname'];
		$myurl = substr($myurl,strpos($myurl,'://')+3);
		$myurl = str_replace(array('www.','.'),array('','\\.'),$myurl);
		$diasp_url = str_replace('/channel/','/u/',$myurl);

		if (get_config('system','use_fulltext_engine'))
                        $sql_extra .= sprintf(" AND `item`.`parent` IN (SELECT distinct(`parent`) from item where (MATCH(`author-link`) AGAINST ('".'"%s"'."' in boolean mode) or MATCH(`tag`) AGAINST ('".'"%s"'."' in boolean mode) or MATCH(tag) AGAINST ('".'"%s"'."' in boolean mode))) ",
                                dbesc(protect_sprintf($myurl)),
                                dbesc(protect_sprintf($myurl)),
                                dbesc(protect_sprintf($diasp_url))
                        );
                else
                        $sql_extra .= sprintf(" AND `item`.`parent` IN (SELECT distinct(`parent`) from item where ( `author-link` like '%s' or `tag` like '%s' or tag like '%s' )) ",
                                dbesc(protect_sprintf('%' . $myurl)),
                                dbesc(protect_sprintf('%' . $myurl . ']%')),
                                dbesc(protect_sprintf('%' . $diasp_url . ']%'))
                        );

		if ($max_id > 0)
			$sql_extra .= ' AND `item`.`id` <= '.intval($max_id);

		$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
			`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`,
			`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn_id`, `contact`.`self`,
			`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
			FROM `item`, `contact`
			WHERE `item`.`uid` = %d
			AND `item`.`visible` = 1 and `item`.`moderated` = 0 AND `item`.`deleted` = 0
			AND `contact`.`id` = `item`.`contact-id`
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			$sql_extra
			AND `item`.`id`>%d
			ORDER BY `item`.`received` DESC LIMIT %d ,%d ",
			intval($user_info['uid']),
			intval($since_id),
			intval($start),	intval($count)
		);

		$ret = api_format_items($r,$user_info);


		$data = array('$statuses' => $ret);
		switch($type){
			case "atom":
			case "rss":
				$data = api_rss_extra($a, $data, $user_info);
				break;
			case "as":
				$as = api_format_as($a, $ret, $user_info);
				$as["title"] = $a->config['sitename']." Mentions";
				$as['link']['url'] = $a->get_baseurl()."/";
				return($as);
				break;
		}

		return  api_apply_template("timeline", $type, $data);
	}
	api_register_func('api/statuses/mentions','api_statuses_mentions', true);
	// FIXME?? I don't think mentions and replies are congruent in this case
	api_register_func('api/statuses/replies','api_statuses_mentions', true);


	function api_statuses_user_timeline(&$a, $type){
		if (api_user()===false) return false;
		
		$user_info = api_get_user($a);
		// get last network messages


		logger("api_statuses_user_timeline: api_user: ". api_user() .
			   "\nuser_info: ".print_r($user_info, true) .
			   "\n_REQUEST:  ".print_r($_REQUEST, true),
			   LOGGER_DEBUG);

		// params
		$count = (x($_REQUEST,'count')?$_REQUEST['count']:20);
		$page = (x($_REQUEST,'page')?$_REQUEST['page']-1:0);
		if ($page<0) $page=0;
		$since_id = (x($_REQUEST,'since_id')?$_REQUEST['since_id']:0);
		$exclude_replies = (x($_REQUEST,'exclude_replies')?1:0);
		//$since_id = 0;//$since_id = (x($_REQUEST,'since_id')?$_REQUEST['since_id']:0);
		
		$start = $page*$count;

		$sql_extra = '';
		if ($user_info['self']==1) $sql_extra .= " AND `item`.`wall` = 1 ";
		if ($exclude_replies > 0)  $sql_extra .= ' AND `item`.`parent` = `item`.`id`';

		$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
			`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`,
			`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn_id`, `contact`.`self`,
			`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
			FROM `item`, `contact`
			WHERE `item`.`uid` = %d
			AND `item`.`contact-id` = %d
			AND `item`.`visible` = 1 and `item`.`moderated` = 0 AND `item`.`deleted` = 0
			AND `contact`.`id` = `item`.`contact-id`
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			$sql_extra
			AND `item`.`id`>%d
			ORDER BY `item`.`received` DESC LIMIT %d ,%d ",
			intval(api_user()),
			intval($user_info['id']),
			intval($since_id),
			intval($start),	intval($count)
		);

		$ret = api_format_items($r,$user_info);


		$data = array('$statuses' => $ret);
		switch($type){
			case "atom":
			case "rss":
				$data = api_rss_extra($a, $data, $user_info);
		}

		return  api_apply_template("timeline", $type, $data);
	}

	api_register_func('api/statuses/user_timeline','api_statuses_user_timeline', true);


	function api_favorites(&$a, $type){
		if (api_user()===false) return false;

		$user_info = api_get_user($a);
		// in friendica starred item are private
		// return favorites only for self
		logger('api_favorites: self:' . $user_info['self']);

		if ($user_info['self']==0) {
			$ret = array();
		} else {


			// params
			$count = (x($_GET,'count')?$_GET['count']:20);
			$page = (x($_REQUEST,'page')?$_REQUEST['page']-1:0);
			if ($page<0) $page=0;

			$start = $page*$count;

			$r = q("SELECT `item`.*, `item`.`id` AS `item_id`,
				`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`,
				`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn_id`, `contact`.`self`,
				`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
				FROM `item`, `contact`
				WHERE `item`.`uid` = %d
				AND `item`.`visible` = 1 and `item`.`moderated` = 0 AND `item`.`deleted` = 0
				AND `item`.`starred` = 1
				AND `contact`.`id` = `item`.`contact-id`
				AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
				$sql_extra
				ORDER BY `item`.`received` DESC LIMIT %d ,%d ",
				intval($user_info['uid']),
				intval($start),	intval($count)
			);

			$ret = api_format_items($r,$user_info);

		}

		$data = array('$statuses' => $ret);
		switch($type){
			case "atom":
			case "rss":
				$data = api_rss_extra($a, $data, $user_info);
		}

		return  api_apply_template("timeline", $type, $data);
	}

	api_register_func('api/favorites','api_favorites', true);

	function api_format_as($a, $ret, $user_info) {

		$as = array();
		$as['title'] = $a->config['sitename']." Public Timeline";
		$items = array();
		foreach ($ret as $item) {
			$singleitem["actor"]["displayName"] = $item["user"]["name"];
			$singleitem["actor"]["id"] = $item["user"]["contact_url"];
			$avatar[0]["url"] = $item["user"]["profile_image_url"];
			$avatar[0]["rel"] = "avatar";
			$avatar[0]["type"] = "";
			$avatar[0]["width"] = 96;
			$avatar[0]["height"] = 96;
			$avatar[1]["url"] = $item["user"]["profile_image_url"];
			$avatar[1]["rel"] = "avatar";
			$avatar[1]["type"] = "";
			$avatar[1]["width"] = 48;
			$avatar[1]["height"] = 48;
			$avatar[2]["url"] = $item["user"]["profile_image_url"];
			$avatar[2]["rel"] = "avatar";
			$avatar[2]["type"] = "";
			$avatar[2]["width"] = 24;
			$avatar[2]["height"] = 24;
			$singleitem["actor"]["avatarLinks"] = $avatar;

			$singleitem["actor"]["image"]["url"] = $item["user"]["profile_image_url"];
			$singleitem["actor"]["image"]["rel"] = "avatar";
			$singleitem["actor"]["image"]["type"] = "";
			$singleitem["actor"]["image"]["width"] = 96;
			$singleitem["actor"]["image"]["height"] = 96;
			$singleitem["actor"]["type"] = "person";
			$singleitem["actor"]["url"] = $item["person"]["contact_url"];
			$singleitem["actor"]["statusnet:profile_info"]["local_id"] = $item["user"]["id"];
			$singleitem["actor"]["statusnet:profile_info"]["following"] = $item["user"]["following"] ? "true" : "false";
			$singleitem["actor"]["statusnet:profile_info"]["blocking"] = "false";
			$singleitem["actor"]["contact"]["preferredUsername"] = $item["user"]["screen_name"];
			$singleitem["actor"]["contact"]["displayName"] = $item["user"]["name"];
			$singleitem["actor"]["contact"]["addresses"] = "";

			$singleitem["body"] = $item["text"];
			$singleitem["object"]["displayName"] = $item["text"];
			$singleitem["object"]["id"] = $item["url"];
			$singleitem["object"]["type"] = "note";
			$singleitem["object"]["url"] = $item["url"];
			//$singleitem["context"] =;
			$singleitem["postedTime"] = date("c", strtotime($item["published"]));
			$singleitem["provider"]["objectType"] = "service";
			$singleitem["provider"]["displayName"] = "Test";
			$singleitem["provider"]["url"] = "http://test.tld";
			$singleitem["title"] = $item["text"];
			$singleitem["verb"] = "post";
			$singleitem["statusnet:notice_info"]["local_id"] = $item["id"];
				$singleitem["statusnet:notice_info"]["source"] = $item["source"];
				$singleitem["statusnet:notice_info"]["favorite"] = "false";
				$singleitem["statusnet:notice_info"]["repeated"] = "false";
				//$singleitem["original"] = $item;
				$items[] = $singleitem;
		}
		$as['items'] = $items;
		$as['link']['url'] = $a->get_baseurl()."/".$user_info["screen_name"]."/all";
		$as['link']['rel'] = "alternate";
		$as['link']['type'] = "text/html";
		return($as);
	}

	function api_format_messages($item, $recipient, $sender) {
		// standard meta information
		$ret = array(
				'id'                    => $item['id'],
				'created_at'            => api_date($item['created']),
				'sender_id'             => $sender['id'] ,
				'sender_screen_name'    => $sender['screen_name'],
				'sender'                => $sender,
				'recipient_id'          => $recipient['id'],
				'recipient_screen_name' => $recipient['screen_name'],
				'recipient'             => $recipient,
		);
		unobscure($item);
		//don't send title to regular StatusNET requests to avoid confusing these apps
		if (x($_GET, 'getText')) {
			$ret['title'] = $item['title'] ;
			if ($_GET["getText"] == "html") {
				$ret['text'] = prepare_text($item['body'],$item['mimetype']);
			}
			elseif ($_GET["getText"] == "plain") {
				$ret['text'] = html2plain(prepare_text($item['body'],$item['mimetype']), 0);
			}
		}
		else {
			$ret['text'] = $item['title']."\n".html2plain(prepare_text($item['body'],$item['mimetype']), 0);
		}
		if (isset($_GET["getUserObjects"]) && $_GET["getUserObjects"] == "false") {
			unset($ret['sender']);
			unset($ret['recipient']);
		}

		return $ret;
	}

	function api_format_items($r,$user_info) {

		//logger('api_format_items: ' . print_r($r,true));

		//logger('api_format_items: ' . print_r($user_info,true));

		$a = get_app();
		$ret = array();

		foreach($r as $item) {
			localize_item($item);

			$status_user = (($item['author_xchan']==$user_info['guid'])?$user_info: api_item_get_user($a,$item));

			if($item['parent'] != $item['id']) {
				$r = q("select id from item where parent= %d and id < %d order by id desc limit 1",
					intval($item['parent']), 
					intval($item['id'])
				);
				if ($r)
					$in_reply_to_status_id = $r[0]['id'];
				else
					$in_reply_to_status_id = $item['parent'];

				xchan_query($r,true);

				$in_reply_to_screen_name = $r[0]['author']['xchan_name'];
				$in_reply_to_user_id = $r[0]['author']['abook_id'];

			} else {
				$in_reply_to_screen_name = '';
				$in_reply_to_user_id = 0;
				$in_reply_to_status_id = 0;
			}
			unobscure($item);
			// Workaround for ostatus messages where the title is identically to the body
			$statusbody = trim(html2plain(prepare_text($item['body'],$item['mimetype']), 0));
			$statustitle = trim($item['title']);

			if (($statustitle != '') and (strpos($statusbody, $statustitle) !== false))
				$statustext = trim($statusbody);
			else
				$statustext = trim($statustitle."\n\n".$statusbody);


			$status = array(
				'text'		                => $statustext,
				'truncated'                 => False,
				'created_at'                => api_date($item['created']),
				'in_reply_to_status_id'     => $in_reply_to_status_id,
				'source'                    => (($item['app']) ? $item['app'] : 'web'),
				'id'		                => intval($item['id']),
				'in_reply_to_user_id'       => $in_reply_to_user_id,
				'in_reply_to_screen_name'   => $in_reply_to_screen_name,
				'geo'                       => '',
				'favorited'                 => (($item['item_flags'] & ITEM_STARRED) ? true : false),
				'user'                      =>  $status_user ,
				'statusnet_html'		    => trim(prepare_text($item['body']),$item['mimetype']),
				'statusnet_conversation_id'	=> $item['parent'],
			);

			// Seesmic doesn't like the following content
			if ($_SERVER['HTTP_USER_AGENT'] != 'Seesmic') {
				$status2 = array(
					'updated'      => api_date($item['edited']),
					'published'    => api_date($item['created']),
					'message_id'   => $item['mid'],
					'url'		   => $item['plink'],
					'coordinates'  => $item['coord'],
					'place'        => $item['location'],
					'contributors' => '',
					'annotations'  => '',
					'entities'     => '',
					'objecttype'   => (($item['obj_type']) ? $item['obj_type'] : ACTIVITY_OBJ_NOTE),
					'verb'         => (($item['verb']) ? $item['verb'] : ACTIVITY_POST),
					'self'         => $a->get_baseurl()."/api/statuses/show/".$item['id'].".".$type,
					'edit'         => $a->get_baseurl()."/api/statuses/show/".$item['id'].".".$type,
				);

				$status = array_merge($status, $status2);
			}

			$ret[]=$status;
		};
		return $ret;
	}


	function api_account_rate_limit_status(&$a,$type) {

		$hash = array(
			  'reset_time_in_seconds' => strtotime('now + 1 hour'),
			  'remaining_hits' => (string) 150,
			  'hourly_limit' => (string) 150,
			  'reset_time' => datetime_convert('UTC','UTC','now + 1 hour',ATOM_TIME),
		);
		if ($type == "xml")
			$hash['resettime_in_seconds'] = $hash['reset_time_in_seconds'];

		return api_apply_template('ratelimit', $type, array('$hash' => $hash));

	}
	api_register_func('api/account/rate_limit_status','api_account_rate_limit_status',true);

	function api_help_test(&$a,$type) {

		if ($type == 'xml')
			$ok = "true";
		else
			$ok = "ok";

		return api_apply_template('test', $type, array('$ok' => $ok));

	}
	api_register_func('api/help/test','api_help_test',false);

	/**
	 *  https://dev.twitter.com/docs/api/1/get/statuses/friends 
	 *  This function is deprecated by Twitter
	 *  returns: json, xml 
	 **/
	function api_statuses_f(&$a, $type, $qtype) {
		if (api_user()===false) return false;
		$user_info = api_get_user($a);
		
		
		// friends and followers only for self
		if ($user_info['self']==0){
			return false;
		}
		
		if (x($_GET,'cursor') && $_GET['cursor']=='undefined'){
			/* this is to stop Hotot to load friends multiple times
			*  I'm not sure if I'm missing return something or
			*  is a bug in hotot. Workaround, meantime
			*/
			
			/*$ret=Array();
			return array('$users' => $ret);*/
			return false;
		}
		
		// For Red, the closest thing we can do to figure out if you're friends is if both of you are sending each other your streams.
		// This won't work if either of you send your stream to everybody on the network
		if($qtype == 'friends')
			$sql_extra = sprintf(" AND ( abook_their_perms & %d ) and ( abook_my_perms & %d ) ", intval(PERMS_W_STREAM), intval(PERMS_W_STREAM));
		if($qtype == 'followers')
			$sql_extra = sprintf(" AND ( abook_my_perms & %d ) and not ( abook_their_perms & %d ) ", intval(PERMS_W_STREAM), intval(PERMS_W_STREAM));
 
		$r = q("SELECT abook_id FROM abook where abook_flags = 0 and abook_channel = %d $sql_extra",
			intval(api_user())
		);

		$ret = array();
		foreach($r as $cid){
			$ret[] = api_get_user($a, $cid['abook_id']);
		}

		
		return array('$users' => $ret);

	}
	function api_statuses_friends(&$a, $type){
		$data =  api_statuses_f($a,$type,"friends");
		if ($data===false) return false;
		return  api_apply_template("friends", $type, $data);
	}
	function api_statuses_followers(&$a, $type){
		$data = api_statuses_f($a,$type,"followers");
		if ($data===false) return false;
		return  api_apply_template("friends", $type, $data);
	}
	api_register_func('api/statuses/friends','api_statuses_friends',true);
	api_register_func('api/statuses/followers','api_statuses_followers',true);






	function api_statusnet_config(&$a,$type) {

		load_config('system');

		$name   = get_config('system','sitename');
		$server = $a->get_hostname();
		$logo   = $a->get_baseurl() . '/images/rm-64.png';
		$email  = get_config('system','admin_email');
		$closed = ((get_config('system','register_policy') == REGISTER_CLOSED) ? 'true' : 'false');
		$private = ((get_config('system','block_public')) ? 'true' : 'false');
		$textlimit = (string) ((get_config('system','max_import_size')) ? get_config('system','max_import_size') : 200000);
		if(get_config('system','api_import_size'))
			$texlimit = string(get_config('system','api_import_size'));
		$ssl = ((get_config('system','have_ssl')) ? 'true' : 'false');
		$sslserver = (($ssl === 'true') ? str_replace('http:','https:',$a->get_baseurl()) : '');

		$config = array(
			'site' => array('name' => $name,'server' => $server, 'theme' => 'default', 'path' => '',
			'logo' => $logo, 'fancy' => 'true', 'language' => 'en', 'email' => $email, 'broughtby' => '',
			'broughtbyurl' => '', 'timezone' => 'UTC', 'closed' => $closed, 'inviteonly' => 'false',
			'private' => $private, 'textlimit' => $textlimit, 'sslserver' => $sslserver, 'ssl' => $ssl,
			'shorturllength' => '30',
        	'friendica' => array(
				'RED_PLATFORM' => RED_PLATFORM,
				'RED_VERSION' => RED_VERSION,
				'ZOT_REVISION' => ZOT_REVISION,
				'DB_UPDATE_VERSION' => DB_UPDATE_VERSION
			)
		));  

		return api_apply_template('config', $type, array('$config' => $config));

	}
	api_register_func('api/statusnet/config','api_statusnet_config',false);
	api_register_func('api/friendica/config','api_statusnet_config',false);
	api_register_func('api/red/config','api_statusnet_config',false);

	function api_statusnet_version(&$a,$type) {

		// liar

		if($type === 'xml') {
			header("Content-type: application/xml");
			echo '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n" . '<version>0.9.7</version>' . "\r\n";
			killme();
		}
		elseif($type === 'json') {
			header("Content-type: application/json");
			echo '"0.9.7"';
			killme();
		}
	}
	api_register_func('api/statusnet/version','api_statusnet_version',false);


	function api_friendica_version(&$a,$type) {

		if($type === 'xml') {
			header("Content-type: application/xml");
			echo '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n" . '<version>' . RED_VERSION . '</version>' . "\r\n";
			killme();
		}
		elseif($type === 'json') {
			header("Content-type: application/json");
			echo '"' . RED_VERSION . '"';
			killme();
		}
	}
	api_register_func('api/friendica/version','api_friendica_version',false);
	api_register_func('api/red/version','api_friendica_version',false);


	function api_ff_ids(&$a,$type,$qtype) {
		if(! api_user())
			return false;


		// For Red, the closest thing we can do to figure out if you're friends is if both of you are sending each other your streams.
		// This won't work if either of you send your stream to everybody on the network

		if($qtype == 'friends')
			$sql_extra = sprintf(" AND ( abook_their_perms & %d ) and ( abook_my_perms & %d ) ", intval(PERMS_W_STREAM), intval(PERMS_W_STREAM));
		if($qtype == 'followers')
			$sql_extra = sprintf(" AND ( abook_my_perms & %d ) and not ( abook_their_perms & %d ) ", intval(PERMS_W_STREAM), intval(PERMS_W_STREAM));
 
		$r = q("SELECT abook_id FROM abook where abook_flags = 0 and abook_channel = %d $sql_extra",
			intval(api_user())
		);

		if(is_array($r)) {
			if($type === 'xml') {
				header("Content-type: application/xml");
				echo '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n" . '<ids>' . "\r\n";
				foreach($r as $rr)
					echo '<id>' . $rr['abook_id'] . '</id>' . "\r\n";
				echo '</ids>' . "\r\n";
				killme();
			}
			elseif($type === 'json') {
				$ret = array();
				header("Content-type: application/json");
				foreach($r as $rr) $ret[] = $rr['abook_id'];
				echo json_encode($ret);
				killme();
			}
		}
	}

	function api_friends_ids(&$a,$type) {
		api_ff_ids($a,$type,'friends');
	}
	function api_followers_ids(&$a,$type) {
		api_ff_ids($a,$type,'followers');
	}
	api_register_func('api/friends/ids','api_friends_ids',true);
	api_register_func('api/followers/ids','api_followers_ids',true);


	function api_direct_messages_new(&$a, $type) {
		if (api_user()===false) return false;
		
		if (!x($_POST, "text") || !x($_POST,"screen_name")) return;

		$sender = api_get_user($a);
		
		require_once("include/message.php");

		$r = q("SELECT `id` FROM `contact` WHERE `uid`=%d AND `nick`='%s'",
				intval(api_user()),
				dbesc($_POST['screen_name']));

		$recipient = api_get_user($a, $r[0]['id']);			
		$replyto = '';
		$sub     = '';
		if (x($_REQUEST,'replyto')) {
			$r = q('SELECT `parent_mid`, `title` FROM `mail` WHERE `uid`=%d AND `id`=%d',
					intval(api_user()),
					intval($_REQUEST['replyto']));
			$replyto = $r[0]['parent_mid'];
			$sub     = $r[0]['title'];
		}
		else {
			if (x($_REQUEST,'title')) {
				$sub = $_REQUEST['title'];
			}
			else {
				$sub = ((strlen($_POST['text'])>10)?substr($_POST['text'],0,10)."...":$_POST['text']);
			}
		}

		$id = send_message($recipient['id'], $_POST['text'], $sub, $replyto);

		if ($id>-1) {
			$r = q("SELECT * FROM `mail` WHERE id=%d", intval($id));
			$ret = api_format_messages($r[0], $recipient, $sender);
		
		} else {
			$ret = array("error"=>$id);	
		}
		
		$data = Array('$messages'=>$ret);
		
		switch($type){
			case "atom":
			case "rss":
				$data = api_rss_extra($a, $data, $user_info);
		}
				
		return  api_apply_template("direct_messages", $type, $data);
				
	}
	api_register_func('api/direct_messages/new','api_direct_messages_new',true);

	function api_direct_messages_box(&$a, $type, $box) {
		if (api_user()===false) return false;
		
		$user_info = api_get_user($a);
		
		// params
		$count = (x($_GET,'count')?$_GET['count']:20);
		$page = (x($_REQUEST,'page')?$_REQUEST['page']-1:0);
		if ($page<0) $page=0;
		
		$start = $page*$count;
		
		$profile_url = $a->get_baseurl() . '/channel/' . $a->user['nickname'];
		if ($box=="sentbox") {
			$sql_extra = "`from-url`='".dbesc( $profile_url )."'";
		}
		elseif ($box=="conversation") {
			$sql_extra = "`parent_mid`='".dbesc( $_GET["uri"] )  ."'";
		}
		elseif ($box=="all") {
			$sql_extra = "true";
		}
		elseif ($box=="inbox") {
			$sql_extra = "`from-url`!='".dbesc( $profile_url )."'";
		}
		
		$r = q("SELECT * FROM `mail` WHERE uid=%d AND $sql_extra ORDER BY created DESC LIMIT %d,%d",
				intval(api_user()),
				intval($start),	intval($count)
		);
		
		$ret = Array();
		foreach($r as $item) {
			if ($box == "inbox" || $item['from-url'] != $profile_url){
				$recipient = $user_info;
				$sender = api_get_user($a,$item['contact-id']);
			}
			elseif ($box == "sentbox" || $item['from-url'] != $profile_url){
				$recipient = api_get_user($a,$item['contact-id']);
				$sender = $user_info;
			}

			$ret[]=api_format_messages($item, $recipient, $sender);
		}
		

		$data = array('$messages' => $ret);
		switch($type){
			case "atom":
			case "rss":
				$data = api_rss_extra($a, $data, $user_info);
		}
				
		return  api_apply_template("direct_messages", $type, $data);
		
	}

	function api_direct_messages_sentbox(&$a, $type){
		return api_direct_messages_box($a, $type, "sentbox");
	}
	function api_direct_messages_inbox(&$a, $type){
		return api_direct_messages_box($a, $type, "inbox");
	}
	function api_direct_messages_all(&$a, $type){
		return api_direct_messages_box($a, $type, "all");
	}
	function api_direct_messages_conversation(&$a, $type){
		return api_direct_messages_box($a, $type, "conversation");
	}
	api_register_func('api/direct_messages/conversation','api_direct_messages_conversation',true);
	api_register_func('api/direct_messages/all','api_direct_messages_all',true);
	api_register_func('api/direct_messages/sent','api_direct_messages_sentbox',true);
	api_register_func('api/direct_messages','api_direct_messages_inbox',true);



	function api_oauth_request_token(&$a, $type){
		try{
			$oauth = new FKOAuth1();
			$req = OAuthRequest::from_request();
logger('Req: ' . var_export($req,true));
			$r = $oauth->fetch_request_token($req);
		}catch(Exception $e){
			logger('oauth_exception: ' . print_r($e->getMessage(),true));
			echo "error=". OAuthUtil::urlencode_rfc3986($e->getMessage()); 
			killme();
		}
		echo $r;
		killme();	
	}
	function api_oauth_access_token(&$a, $type){
		try{
			$oauth = new FKOAuth1();
			$req = OAuthRequest::from_request();
			$r = $oauth->fetch_access_token($req);
		}catch(Exception $e){
			echo "error=". OAuthUtil::urlencode_rfc3986($e->getMessage()); killme();
		}
		echo $r;
		killme();			
	}

	api_register_func('api/oauth/request_token', 'api_oauth_request_token', false);
	api_register_func('api/oauth/access_token', 'api_oauth_access_token', false);

/*
Not implemented by now:
favorites
favorites/create
favorites/destroy
statuses/retweets_of_me
friendships/create
friendships/destroy
friendships/exists
friendships/show
account/update_location
account/update_profile_background_image
account/update_profile_image
blocks/create
blocks/destroy

Not implemented in status.net:
statuses/retweeted_to_me
statuses/retweeted_by_me
direct_messages/destroy
account/end_session
account/update_delivery_device
notifications/follow
notifications/leave
blocks/exists
blocks/blocking
lists
*/

