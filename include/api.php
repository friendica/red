<?php
	require_once("bbcode.php");
	require_once("datetime.php");
	
	/* 
	 * Twitter-Like API
	 *  
	 */

	$API = Array();
	 

	function api_date($str){
		//Wed May 23 06:01:13 +0000 2007
		return datetime_convert('UTC', 'UTC', $str, "D M d h:i:s +0000 Y" );
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
		// workaround for HTTP-auth in CGI mode
		if(x($_SERVER,'REDIRECT_REMOTE_USER')) {
		 	$userpass = base64_decode(substr($_SERVER["REDIRECT_REMOTE_USER"],6)) ;
			if(strlen($userpass)) {
			 	list($name, $password) = explode(':', $userpass);
				$_SERVER['PHP_AUTH_USER'] = $name;
				$_SERVER['PHP_AUTH_PW'] = $password;
			}
		}

		if (!isset($_SERVER['PHP_AUTH_USER'])) {
		   logger('API_login: ' . print_r($_SERVER,true), LOGGER_DEBUG);
		    header('WWW-Authenticate: Basic realm="Friendika"');
		    header('HTTP/1.0 401 Unauthorized');
		    die('This api requires login');
		}
		
		$user = $_SERVER['PHP_AUTH_USER'];
		$encrypted = hash('whirlpool',trim($_SERVER['PHP_AUTH_PW']));
    		
		
			/**
			 *  next code from mod/auth.php. needs better solution
			 */
			
		// process normal login request

		$r = q("SELECT * FROM `user` WHERE ( `email` = '%s' OR `nickname` = '%s' ) 
			AND `password` = '%s' AND `blocked` = 0 AND `verified` = 1 LIMIT 1",
			dbesc(trim($user)),
			dbesc(trim($user)),
			dbesc($encrypted)
		);
		if(count($r)){
			$record = $r[0];
		} else {
		   logger('API_login failure: ' . print_r($_SERVER,true), LOGGER_DEBUG);
		    header('WWW-Authenticate: Basic realm="Friendika"');
		    header('HTTP/1.0 401 Unauthorized');
		    die('This api requires login');
		}
		$_SESSION['uid'] = $record['uid'];
		$_SESSION['theme'] = $record['theme'];
		$_SESSION['authenticated'] = 1;
		$_SESSION['page_flags'] = $record['page-flags'];
		$_SESSION['my_url'] = $a->get_baseurl() . '/profile/' . $record['nickname'];
		$_SESSION['addr'] = $_SERVER['REMOTE_ADDR'];

		//notice( t("Welcome back ") . $record['username'] . EOL);
		$a->user = $record;

		if(strlen($a->user['timezone'])) {
			date_default_timezone_set($a->user['timezone']);
			$a->timezone = $a->user['timezone'];
		}

		$r = q("SELECT * FROM `contact` WHERE `uid` = %s AND `self` = 1 LIMIT 1",
			intval($_SESSION['uid']));
		if(count($r)) {
			$a->contact = $r[0];
			$a->cid = $r[0]['id'];
			$_SESSION['cid'] = $a->cid;
		}
		q("UPDATE `user` SET `login_date` = '%s' WHERE `uid` = %d LIMIT 1",
			dbesc(datetime_convert()),
			intval($_SESSION['uid'])
		);

		call_hooks('logged_in', $a->user);

		header('X-Account-Management-Status: active; name="' . $a->user['username'] . '"; id="' . $a->user['nickname'] .'"');
	}
	
	/**************************
	 *  MAIN API ENTRY POINT  *
	 **************************/
	function api_call(&$a){
		GLOBAL $API;
		foreach ($API as $p=>$info){
			if (strpos($a->query_string, $p)===0){
				#unset($_SERVER['PHP_AUTH_USER']);
				if ($info['auth']===true && local_user()===false) {
						api_login($a);
				}

				load_contact_links(local_user());

				logger('API call for ' . $a->user['username'] . ': ' . $a->query_string);		
				$type="json";		
				if (strpos($a->query_string, ".xml")>0) $type="xml";
				if (strpos($a->query_string, ".json")>0) $type="json";
				if (strpos($a->query_string, ".rss")>0) $type="rss";
				if (strpos($a->query_string, ".atom")>0) $type="atom";				
				
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
						
				}
				//echo "<pre>"; var_dump($r); die();
			}
		}
		return false;
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
			'logo'	=> $a->get_baseurl()."/images/friendika-32.png",
		);
		
		return $arr;
	}
	 
	/**
	 * Returns user info array.
	 */
	function api_get_user(&$a, $contact_id = Null){
		$user = null;
		$extra_query = "";

		if(!is_null($contact_id)){
			$user=$contact_id;
			$extra_query = "AND `contact`.`id` = %d ";
		}
		
		if(is_null($user) && x($_GET, 'user_id')) {
			$user = intval($_GET['user_id']);	
			$extra_query = "AND `contact`.`id` = %d ";
		}
		if(is_null($user) && x($_GET, 'screen_name')) {
			$user = dbesc($_GET['screen_name']);	
			$extra_query = "AND `contact`.`nick` = '%s' ";
		}
		
		if (is_null($user)){
			list($user, $null) = explode(".",$a->argv[3]);
			if(is_numeric($user)){
				$user = intval($user);
				$extra_query = "AND `contact`.`id` = %d ";
			} else {
				$user = dbesc($user);
				$extra_query = "AND `contact`.`nick` = '%s' ";
			}
		}
		
		if ($user==='') {
			if (local_user()===false) {
				api_login($a); return False;
			} else {
				$user = $_SESSION['uid'];
				$extra_query = "AND `contact`.`uid` = %d AND `contact`.`self` = 1 ";
			}
			
		}
		

		// user info		
		$uinfo = q("SELECT *, `contact`.`id` as `cid` FROM `contact`
				WHERE 1
				$extra_query",
				$user
		);
		if (count($uinfo)==0) {
			return False;
		}
		
		// count public wall messages
		$r = q("SELECT COUNT(`id`) as `count` FROM `item`
				WHERE  `uid` = %d
				AND `type`='wall' 
				AND `allow_cid`='' AND `allow_gid`='' AND `deny_cid`='' AND `deny_gid`=''",
				intval($uinfo[0]['uid'])
		);
		$countitms = $r[0]['count'];
		
		// count friends
		$r = q("SELECT COUNT(`id`) as `count` FROM `contact`
				WHERE  `uid` = %d
				AND `self`=0 AND `blocked`=0", 
				intval($uinfo[0]['uid'])
		);
		$countfriends = $r[0]['count'];
				

		$ret = Array(
			'uid' => $uinfo[0]['uid'],
			'id' => $uinfo[0]['cid'],
			'name' => $uinfo[0]['name'],
			'screen_name' => $uinfo[0]['nick'],
			'location' => '', //$uinfo[0]['default-location'],
			'profile_image_url' => $uinfo[0]['micro'],
			'url' => $uinfo[0]['url'],
			'contact_url' => $a->get_baseurl()."/contacts/".$uinfo[0]['cid'],
			'protected' => false,	#
			'friends_count' => $countfriends,
			'created_at' => api_date($uinfo[0]['name-date']),
			'utc_offset' => 0, #XXX: fix me
			'time_zone' => '', //$uinfo[0]['timezone'],
			'geo_enabled' => false,
			'statuses_count' => $countitms, #XXX: fix me 
			'lang' => 'en', #XXX: fix me
			'description' => '',
			'followers_count' => $countfriends, #XXX: fix me
			'favourites_count' => 0,
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
			'notifications' => false,
			'verified' => true, #XXX: fix me
			'followers' => '', #XXX: fix me
			#'status' => null
		);
	
		return $ret;
		
	}

	function api_item_get_user(&$a, $item) {
		// The author is our direct contact, in a conversation with us.
		if(link_compare($item['url'],$item['author-link'])) {
			return api_get_user($a,$item['cid']);
		}
		else {
			// The author may be a contact of ours, but is replying to somebody else. 
			// Figure out if we know him/her.
			$normalised = normalise_link((strlen($item['author-link'])) ? $item['author-link'] : $item['url']);
            if(($normalised != 'mailbox') && (x($a->contacts[$normalised])))
				return api_get_user($a,$a->contacts[$normalised]['id']);
		}
		// We don't know this person directly.
		$ret = array(
			'uid' => 0,
			'id' => 0,
			'name' => $item['author-name'],
			'screen_name' => '',
			'location' => '', //$uinfo[0]['default-location'],
			'profile_image_url' => $item['author-avatar'],
			'url' => $item['author-link'],
			'contact_url' => 0,
			'protected' => false,	#
			'friends_count' => 0,
			'created_at' => '',
			'utc_offset' => 0, #XXX: fix me
			'time_zone' => '', //$uinfo[0]['timezone'],
			'geo_enabled' => false,
			'statuses_count' => 0,
			'lang' => 'en', #XXX: fix me
			'description' => '',
			'followers_count' => 0,
			'favourites_count' => 0,
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
			'notifications' => false,
			'verified' => true, #XXX: fix me
			'followers' => '', #XXX: fix me
			#'status' => null
		);

		return $ret; 
	}

	/**
	 * apply xmlify() to all values of array $val, recursively
	 */
	function api_xmlify($val){
		if (is_bool($val)) return $val?"true":"false";
		if (is_array($val)) return array_map('api_xmlify', $val);
		return xmlify($val);
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
				$data = api_xmlify($data);
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
	 ** TWITTER API
	 */
	
	/**
	 * Returns an HTTP 200 OK response code and a representation of the requesting user if authentication was successful; 
	 * returns a 401 status code and an error message if not. 
	 * http://developer.twitter.com/doc/get/account/verify_credentials
	 */
	function api_account_verify_credentials(&$a, $type){
		if (local_user()===false) return false;
		$user_info = api_get_user($a);
		
		return api_apply_template("user", $type, array('$user' => $user_info));

	}
	api_register_func('api/account/verify_credentials','api_account_verify_credentials', true);
	 	

	/**
	 * get data from $_POST or $_GET
	 */
	function requestdata($k){
		if (isset($_POST[$k])){
			return $_POST[$k];
		}
		if (isset($_GET[$k])){
			return $_GET[$k];
		}
		return null;
	}
	// TODO - media uploads
	function api_statuses_update(&$a, $type) {
		if (local_user()===false) return false;
		$user_info = api_get_user($a);

		// convert $_POST array items to the form we use for web posts.

		// logger('api_post: ' . print_r($_POST,true));

		$_POST['body'] = urldecode(requestdata('status'));

		$parent = requestdata('in_reply_to_status_id');
		if(ctype_digit($parent))
			$_POST['parent'] = $parent;
		else
			$_POST['parent_uri'] = $parent;

		if(requestdata('lat') && requestdata('long'))
			$_POST['coord'] = sprintf("%s %s",requestdata('lat'),requestdata('long'));
		$_POST['profile_uid'] = local_user();
		if(requestdata('parent'))
			$_POST['type'] = 'net-comment';
		else
			$_POST['type'] = 'wall';

		// set this so that the item_post() function is quiet and doesn't redirect or emit json

		$_POST['api_source'] = true;

		// call out normal post function

		require_once('mod/item.php');
		item_post($a);	

		// this should output the last post (the one we just posted).
		return api_status_show($a,$type);
	}
	api_register_func('api/statuses/update','api_statuses_update', true);


	function api_status_show(&$a, $type){
		$user_info = api_get_user($a);
		// get last public wall message
		$lastwall = q("SELECT `item`.*, `i`.`contact-id` as `reply_uid`, `i`.`nick` as `reply_author`
				FROM `item`, `contact`,
					(SELECT `item`.`id`, `item`.`contact-id`, `contact`.`nick` FROM `item`,`contact` WHERE `contact`.`id`=`item`.`contact-id`) as `i` 
				WHERE `item`.`contact-id` = %d
					AND `i`.`id` = `item`.`parent`
					AND `contact`.`id`=`item`.`contact-id` AND `contact`.`self`=1
					AND `type`!='activity'
					AND `item`.`allow_cid`='' AND `item`.`allow_gid`='' AND `item`.`deny_cid`='' AND `item`.`deny_gid`=''
				ORDER BY `created` DESC 
				LIMIT 1",
				intval($user_info['id'])
		);

		if (count($lastwall)>0){
			$lastwall = $lastwall[0];
			
			$in_reply_to_status_id = '';
			$in_reply_to_user_id = '';
			$in_reply_to_screen_name = '';
			if ($lastwall['parent']!=$lastwall['id']) {
				$in_reply_to_status_id=$lastwall['parent'];
				$in_reply_to_user_id = $lastwall['reply_uid'];
				$in_reply_to_screen_name = $lastwall['reply_author'];
			}  
			$status_info = array(
				'created_at' => api_date($lastwall['created']),
				'id' => $lastwall['contact-id'],
				'text' => strip_tags(bbcode($lastwall['body'])),
				'source' => (($lastwall['app']) ? $lastwall['app'] : 'web'),
				'truncated' => false,
				'in_reply_to_status_id' => $in_reply_to_status_id,
				'in_reply_to_user_id' => $in_reply_to_user_id,
				'favorited' => false,
				'in_reply_to_screen_name' => $in_reply_to_screen_name,
				'geo' => '',
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
	function api_users_show(&$a, $type){
		$user_info = api_get_user($a);
		// get last public wall message
		$lastwall = q("SELECT `item`.*, `i`.`contact-id` as `reply_uid`, `i`.`nick` as `reply_author`
				FROM `item`, `contact`,
					(SELECT `item`.`id`, `item`.`contact-id`, `contact`.`nick` FROM `item`,`contact` WHERE `contact`.`id`=`item`.`contact-id`) as `i` 
				WHERE `item`.`contact-id` = %d
					AND `i`.`id` = `item`.`parent`
					AND `contact`.`id`=`item`.`contact-id` AND `contact`.`self`=1
					AND `type`!='activity'
					AND `item`.`allow_cid`='' AND `item`.`allow_gid`='' AND `item`.`deny_cid`='' AND `item`.`deny_gid`=''
				ORDER BY `created` DESC 
				LIMIT 1",
				intval($user_info['id'])
		);

		if (count($lastwall)>0){
			$lastwall = $lastwall[0];
			
			$in_reply_to_status_id = '';
			$in_reply_to_user_id = '';
			$in_reply_to_screen_name = '';
			if ($lastwall['parent']!=$lastwall['id']) {
				$in_reply_to_status_id=$lastwall['parent'];
				$in_reply_to_user_id = $lastwall['reply_uid'];
				$in_reply_to_screen_name = $lastwall['reply_author'];
			}  
			$user_info['status'] = array(
				'created_at' => api_date($lastwall['created']),
				'id' => $lastwall['contact-id'],
				'text' => strip_tags(bbcode($lastwall['body'])),
				'source' => (($lastwall['app']) ? $lastwall['app'] : 'web'),
				'truncated' => false,
				'in_reply_to_status_id' => $in_reply_to_status_id,
				'in_reply_to_user_id' => $in_reply_to_user_id,
				'favorited' => false,
				'in_reply_to_screen_name' => $in_reply_to_screen_name,
				'geo' => '',
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
		if (local_user()===false) return false;
		
		$user_info = api_get_user($a);
		// get last newtork messages
//		$sql_extra = " AND `item`.`parent` IN ( SELECT `parent` FROM `item` WHERE `id` = `parent` ) ";

		$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
			`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`,
			`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
			`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
			FROM `item`, `contact`
			WHERE `item`.`uid` = %d
			AND `item`.`visible` = 1 AND `item`.`deleted` = 0
			AND `contact`.`id` = `item`.`contact-id`
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			$sql_extra
			ORDER BY `item`.`received` DESC LIMIT %d ,%d ",
			intval($user_info['uid']),
			0,20
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
	api_register_func('api/statuses/home_timeline','api_statuses_home_timeline', true);
	api_register_func('api/statuses/friends_timeline','api_statuses_home_timeline', true);



	function api_statuses_user_timeline(&$a, $type){
		if (local_user()===false) return false;
		
		$user_info = api_get_user($a);
		// get last newtork messages
//		$sql_extra = " AND `item`.`parent` IN ( SELECT `parent` FROM `item` WHERE `id` = `parent` ) ";

		$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
			`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`,
			`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
			`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
			FROM `item`, `contact`
			WHERE `item`.`uid` = %d
			AND `item`.`visible` = 1 AND `item`.`deleted` = 0
			AND `item`.`wall` = 1
			AND `contact`.`id` = `item`.`contact-id`
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			$sql_extra
			ORDER BY `item`.`received` DESC LIMIT %d ,%d ",
			intval($user_info['uid']),
			0,20
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
		if (local_user()===false) return false;
		
		$user_info = api_get_user($a);
		// get last newtork messages
//		$sql_extra = " AND `item`.`parent` IN ( SELECT `parent` FROM `item` WHERE `id` = `parent` ) ";

		$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
			`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`,
			`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
			`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
			FROM `item`, `contact`
			WHERE `item`.`uid` = %d
			AND `item`.`visible` = 1 AND `item`.`deleted` = 0
			AND `item`.`starred` = 1
			AND `contact`.`id` = `item`.`contact-id`
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			$sql_extra
			ORDER BY `item`.`received` DESC LIMIT %d ,%d ",
			intval($user_info['uid']),
			0,20
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

	api_register_func('api/favorites','api_favorites', true);

	
	function api_format_items($r,$user_info) {
		$a = get_app();
		$ret = Array();

		foreach($r as $item) {
			$status_user = (($item['cid']==$user_info['id'])?$user_info: api_item_get_user($a,$item));
			$status = array(
				'created_at'=> api_date($item['created']),
				'published' => datetime_convert('UTC','UTC',$item['created'],ATOM_TIME),
				'updated'   => datetime_convert('UTC','UTC',$item['edited'],ATOM_TIME),
				'id'		=> $item['uri'],
				'text'		=> strip_tags(bbcode($item['body'])),
				'html'		=> bbcode($item['body']),
				'source'    => (($item['app']) ? $item['app'] : 'web'),
				'url'		=> ($item['plink']!=''?$item['plink']:$item['author-link']),
				'truncated' => False,
				'in_reply_to_status_id' => ($item['parent']!=$item['id']?$item['parent']:''),
				'in_reply_to_user_id' => '',
				'favorited' => false,
				'in_reply_to_screen_name' => '',
				'geo' => '',
				'coordinates' => $item['coord'],
				'place' => $item['location'],
				'contributors' => '',
				'annotations'  => '',
				'entities'  => '',
				'user' =>  $status_user ,
				'objecttype' => (($item['object-type']) ? $item['object-type'] : ACTIVITY_OBJ_NOTE),
				'verb' => (($item['verb']) ? $item['verb'] : ACTIVITY_POST),
				'self' => $a->get_baseurl()."/api/statuses/show/".$item['id'].".".$type,
				'edit' => $a->get_baseurl()."/api/statuses/show/".$item['id'].".".$type,				
			);
			$ret[]=$status;
		};
		return $ret;
	}


	function api_account_rate_limit_status(&$a,$type) {

		$hash = array(
			  'remaining_hits' => (string) 150,
			  'hourly_limit' => (string) 150,
			  'reset_time' => datetime_convert('UTC','UTC','now + 1 hour',ATOM_TIME),
			  'reset_time_in_seconds' => strtotime('now + 1 hour')
		);

		return api_apply_template('ratelimit', $type, array('$hash' => $hash));

	}
	api_register_func('api/account/rate_limit_status','api_account_rate_limit_status',true);


	function api_statusnet_config(&$a,$type) {
		$name = $a->config['sitename'];
		$server = $a->get_hostname();
		$logo = $a->get_baseurl() . '/images/friendika-64.png';
		$email = $a->config['admin_email'];
		$closed = (($a->config['register_policy'] == REGISTER_CLOSED) ? 'true' : 'false');
		$private = (($a->config['system']['block_public']) ? 'true' : 'false');
		$textlimit = (($a->config['max_import_size']) ? $a->config['max_import_size'] : '200000');
		$ssl = (($a->config['system']['have_ssl']) ? 'true' : 'false');
		$sslserver = (($ssl === 'true') ? str_replace('http:','https:',$a->get_baseurl()) : '');

		$config = array(
			'site' => array('name' => $name,'server' => $server, 'theme' => 'default', 'path' => '',
				'logo' => $logo, 'fancy' => 'true', 'language' => 'en', 'email' => $email, 'broughtby' => '',
				'broughtbyurl' => '', 'timezone' => 'UTC', 'closed' => $closed, 'inviteonly' => 'false',
				'private' => $private, 'textlimit' => $textlimit, 'sslserver' => $sslserver, 'ssl' => $ssl,
				'shorturllength' => '30'
			),
		);  

		return api_apply_template('config', $type, array('$config' => $config));

	}
	api_register_func('api/statusnet/config','api_statusnet_config',false);


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
