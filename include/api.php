<?php
	require_once("bbcode.php");
	require_once("datetime.php");
	
	/* 
	 * Twitter-Like API
	 *  
	 */

	$API = Array();
	 
	class XMLSerializer {
	
	    // functions adopted from http://www.sean-barton.co.uk/2009/03/turning-an-array-or-object-into-xml-using-php/
	
	    public static function generateValidXmlFromObj(stdClass $obj, $node_block='nodes', $node_name='node') {
	        $arr = get_object_vars($obj);
	        return self::generateValidXmlFromArray($arr, $node_block, $node_name);
	    }
	
	    public static function generateValidXmlFromArray($array, $node_block='nodes', $node_name='node') {
			$attrs="";
			if ($array instanceof Container){
				$node_block=$array->name;
				foreach($array->attrs as $n=>$v){
					$attrs .= " $n='$v'";
				}
			}
	
	
	        $xml = '<?xml version="1.0" encoding="UTF-8" ?>';
	
	        $xml .= '<' . $node_block . $attrs. '>';
	        $xml .= self::generateXmlFromArray($array, $node_name);
	        $xml .= '</' . $node_block . '>';
	
	        return $xml;
	    }
	
	    private static function generateXmlFromArray($array, $node_name) {
	        $xml = '';
				
	        if (is_array($array) || is_object($array)) {
	            foreach ($array as $key=>$value) {
	            	$attrs="";
					if ($value instanceof Container){
						$node_name=$value->name;
						foreach($value->attrs as $n=>$v){
							$attrs .= " $n='$v'";
						}
					}		            	
	                if (is_numeric($key)) {
	                    $key = $node_name;
	                }
	
	
	                $xml .= '<' . $key . $attrs.'>' . self::generateXmlFromArray($value, $node_name) . '</' . $key . '>';
	            }
	        } else {
	        	if (is_bool($array)) $array = ($array===true?"true":"false");
	            $xml = htmlspecialchars($array, ENT_QUOTES);
	        }
	
	        return $xml;
	    }
	
	}
	
	// this is used when json and xml are not translatable to arrays
	// like [{text:'text'},{text:'text2'}]
	//	and	<statuses type='array'><status><text>text</text></status><status><text>text2</text></status></statuses>
	class Container extends ArrayObject{
		public $name;
		public $attrs=Array();
		function __construct($name){
			$this->name = $name;
			$args = func_get_args();
			unset($args[0]);
			call_user_func_array(array(parent,'__construct'), $args);
		}
	}
	
	function api_date($str){
		//Wed May 23 06:01:13 +0000 2007
		return datetime_convert('UTC', 'UTC', $str, "D M d h:i:s +0000 Y" );
	}
	 
	
	function api_register_func($path, $func, $auth=false){
		global $API;
		$API[$path] = array('func'=>$func,
							'auth'=>auth);
	}
	
	/**
	 * Simple HTTP Login
	 */
	function api_login(&$a){
		if (!isset($_SERVER['PHP_AUTH_USER'])) {
		    header('WWW-Authenticate: Basic realm="Friendika"');
		    header('HTTP/1.0 401 Unauthorized');
		    die('This api require login');
		}
		
		$user = $_SERVER['PHP_AUTH_USER'];
		$encrypted = hash('whirlpool',trim($_SERVER['PHP_AUTH_PW']));
    		
		
			// da auth.php
		
		// process normal login request

		$r = q("SELECT * FROM `user` WHERE ( `email` = '%s' OR `nickname` = '%s' ) 
			AND `password` = '%s' AND `blocked` = 0 AND `verified` = 1 LIMIT 1",
			dbesc(trim($user)),
			dbesc(trim($user)),
			dbesc($encrypted)
		);
		if(count($r))
			$record = $r[0];
		$_SESSION['uid'] = $record['uid'];
		$_SESSION['theme'] = $record['theme'];
		$_SESSION['authenticated'] = 1;
		$_SESSION['page_flags'] = $record['page-flags'];
		$_SESSION['my_url'] = $a->get_baseurl() . '/profile/' . $record['nickname'];
		$_SESSION['addr'] = $_SERVER['REMOTE_ADDR'];

		notice( t("Welcome back ") . $record['username'] . EOL);
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
	
	function api_call(&$a){
		GLOBAL $API;
		foreach ($API as $p=>$info){
			if (strpos($a->query_string, $p)===0){
				if ($info['auth']===true) api_login($a);
				
				$r = call_user_func($info['func'], $a);
				if ($r===false) return;
				
				if ($r instanceof Container){
					$name=NULL; $values=$r;
				} else {						
					foreach($r as $name=>$values){}
				}
				
				// return xml
				if (strpos($a->query_string, ".xml")>0){
					return XMLSerializer::generateValidXmlFromArray($values, $name);
				}
				// return json
				if (strpos($a->query_string, ".json")>0){
					if ($values instanceof Container) $values= iterator_to_array($values);
					return json_encode($values);
				}
				//echo "<pre>"; var_dump($r); die();
			}
		}
		return false;
	}
	
		
	/**
	 * Returns extended information of a given user, specified by ID or screen name as per the required id parameter.
	 * The author's most recent status will be returned inline.
	 * http://developer.twitter.com/doc/get/users/show
	 */
	function api_users_show(&$a){
		
		$user = null;
		$extra_query = "";
		if(x($_GET, 'user_id')) {
			$user = intval($_GET['user_id']);	
			$extra_query = "AND `user`.`uid` = %d ";
		}
		if(x($_GET, 'screen_name')) {
			$user = dbesc($_GET['screen_name']);	
			$extra_query = "AND `user`.`nickname` = '%s' ";
		}
		
		if ($user===null){
			list($user, $null) = explode(".",$a->argv[3]);
			if(is_numeric($user)){
				$user = intval($user);
				$extra_query = "AND `user`.`uid` = %d ";
			} else {
				$user = dbesc($user);
				$extra_query = "AND `user`.`nickname` = '%s' ";
			}
		}
		
		if ($user==='') {
			return False;
		}
		

		// user info		
		$uinfo = q("SELECT * FROM `user`, `contact`
				WHERE `user`.`uid`=`contact`.`uid` AND `contact`.`self`=1
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
		
		// get last public wall message
		$lastwall = q("SELECT * FROM `item`
				WHERE  `uid` = %d
				AND `type`='wall' 
				AND `allow_cid`='' AND `allow_gid`='' AND `deny_cid`='' AND `deny_gid`=''
				ORDER BY `created` DESC LIMIT 1",
				intval($uinfo[0]['uid'])
		);
	
		//echo "<pre>"; var_dump($lastwall); die();
		
		$ret = Array(
			'user' => Array(
				'id' => $uinfo[0]['uid'],
				'name' => $uinfo[0]['username'],
				'screen_name' => $uinfo[0]['nickname'],
				'location' => $uinfo[0]['default-location'],
				'profile_image_url' => $uinfo[0]['photo'],
				'url' => $uinfo[0]['url'],
				'protected' => false,	#
				'friends_count' => $countfriends,
				'created_at' => api_date($uinfo[0]['created']),
				'utc_offset' => 0, #XXX: fix me
				'time_zone' => $uinfo[0]['timezone'],
				'geo_enabled' => false,
				'statuses_count' => $countitms, #XXX: fix me 
  				'lang' => 'en', #XXX: fix me
  				'status' => array(
  					'created_at' => api_date($lastwall[0]['created']),
  					'id' => $lastwall[0]['id'],
  					'text' => bbcode($lastwall[0]['body']),
  					'source' => 'web',
  					'truncated' => false,
  					'in_reply_to_status_id' => '',
  					'in_reply_to_user_id' => '',
  					'favorited' => false,
  					'in_reply_to_screen_name' => '',
  					'geo' => '',
    				'coordinates' => $lastwall[0]['coord'],
    				'place' => $lastwall[0]['location'],
    				'contributors' => ''					
  				)
				
			)
		);
		
		return $ret;
		
	}
	api_register_func('api/users/show','api_users_show');
	
	/**
	 * 
	 * http://developer.twitter.com/doc/get/statuses/home_timeline
	 */
	function api_statuses_home_timeline(&$a){
		if (local_user()===false) return false;
		
		// count public wall messages
		$r = q("SELECT COUNT(`id`) as `count` FROM `item`
				WHERE  `uid` = %d
				AND `type`='wall' 
				AND `allow_cid`='' AND `allow_gid`='' AND `deny_cid`='' AND `deny_gid`=''",
				intval($uinfo[0]['uid'])
		);
		$countitms = $r[0]['count'];
		
		// get last newtork messages
		$sql_extra = " AND `item`.`parent` IN ( SELECT `parent` FROM `item` WHERE `id` = `parent` ) ";
		
		$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
			`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`,
			`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
			`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
			FROM `item`, `contact`, `user`
			WHERE `item`.`uid` = %d AND `user`.`uid` = `item`.`uid` 
			AND `item`.`visible` = 1 AND `item`.`deleted` = 0
			AND `contact`.`id` = `item`.`contact-id`
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			$sql_extra
			ORDER BY `item`.`created` DESC LIMIT %d ,%d ",
			intval($_SESSION['uid']),
			intval($a->pager['start']),
			intval($a->pager['itemspage'])
		);
		$ret = new Container("statuses");
		$ret->attrs['type']='array';

		#foreach($r as $item) {
		{
			$item = $r[0];
			$status = new Container('status', array(
				'created_at'=> api_date($item['created']),
				'id'		=> $item['id'],
				'text'		=> bbcode($item['body']),
				'source'	=> 'web', 	#XXX: Fix me!
				'truncated' => False,
				'in_reply_to_status_id' => '',
				'in_reply_to_user_id' => '',
				'favorited' => false,
				'in_reply_to_screen_name' => '',
				'geo' => '',
				'coordinates' => $item['coord'],
				'place' => $item['location'],
				'contributors' => '',
				'annotations'  => '',
				'entities'  => '',
				'user' => Array(
					'id' => $item['uid'],
					'name' => $item['username'],
					'screen_name' => $item['nickname'],
					'location' => $item['default-location'],
					'description' => '',
					'profile_image_url' => $item['photo'],
					'url' => $item['url'],
					'protected' => false,	#
					'followers_count' => $countfriends, #XXX: fix me
					'friends_count' => $countfriends,
					'created_at' => api_date($item['created']),
					'utc_offset' => 0, #XXX: fix me
					'time_zone' => $item['timezone'],
					'geo_enabled' => false,
					'statuses_count' => $countitms, #XXX: fix me 
	  				'lang' => 'en', #XXX: fix me
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
				)					
			
			));
			$ret[]=$status;
		};
		
		return $ret;
	}
	api_register_func('api/statuses/home_timeline','api_statuses_home_timeline', true);
