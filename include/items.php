<?php /** @file */

require_once('include/bbcode.php');
require_once('include/oembed.php');
require_once('include/crypto.php');
require_once('include/photo/photo_driver.php');
require_once('include/permissions.php');


function collect_recipients($item,&$private) {

// FIXME - this needs a revision to handle public scope (this site, this network, etc.)
// We'll be changing this to return an array of
// - recipients
// - private
// - scope if message is public ('global', 'network: red', 'site: $sitename', 'connections')
// The receiving site will need to check the scope before creating a list of local recipients

	require_once('include/group.php');

	if($item['item_private'])
		$private = true;

	if($item['allow_cid'] || $item['allow_gid'] || $item['deny_cid'] || $item['deny_gid']) {
		$allow_people = expand_acl($item['allow_cid']);
		$allow_groups = expand_groups(expand_acl($item['allow_gid']));

		$recipients = array_unique(array_merge($allow_people,$allow_groups));

		// if you specifically deny somebody but haven't allowed anybody, we'll allow everybody in your 
		// address book minus the denied connections. The post is still private and can't be seen publicly
		// as that would allow the denied person to see the post by logging out. 

		if((! $item['allow_cid']) && (! $item['allow_gid'])) {
			$r = q("select * from abook where abook_channel = %d and not (abook_flags & %d) and not (abook_flags & %d) and not (abook_flags & %d)",
				intval($item['uid']),
				intval(ABOOK_FLAG_SELF),
				intval(ABOOK_FLAG_PENDING),
				intval(ABOOK_FLAG_ARCHIVED)
			);

			if($r) {
				foreach($r as $rr) {
					$recipients[] = $rr['abook_xchan'];
				}
			}
		}

		$deny_people  = expand_acl($item['deny_cid']);
		$deny_groups  = expand_groups(expand_acl($item['deny_gid']));

		$deny = array_unique(array_merge($deny_people,$deny_groups));
		$recipients = array_diff($recipients,$deny);
		$private = true;
	}
	else {
		$recipients = array();
		$r = q("select * from abook where abook_channel = %d and not (abook_flags & %d) and not (abook_flags & %d) and not (abook_flags & %d)",
			intval($item['uid']),
			intval(ABOOK_FLAG_SELF),
			intval(ABOOK_FLAG_PENDING),
			intval(ABOOK_FLAG_ARCHIVED)
		);
		if($r) {
			foreach($r as $rr) {
				$recipients[] = $rr['abook_xchan'];
			}
		}
		$private = false;
	}

	// This is a somewhat expensive operation but important.
	// Don't send this item to anybody who isn't allowed to see it

	$recipients = check_list_permissions($item['uid'],$recipients,'view_stream');

	// add ourself just in case we have nomadic clones that need to get a copy.
 
	$recipients[] = $item['author_xchan'];
	if($item['owner_xchan'] != $item['author_xchan'])
		$recipients[] = $item['owner_xchan'];
	return $recipients;

}

/**
 * @function can_comment_on_post($observer_xchan,$item);
 *
 * This function examines the comment_policy attached to an item and decides if the current observer has
 * sufficient privileges to comment. This will normally be called on a remote site where perm_is_allowed()
 * will not be suitable because the post owner does not have a local channel_id.
 * Generally we should look at the item - in particular the author['book_flags'] and see if ABOOK_FLAG_SELF is set.
 * If it is, you should be able to use perm_is_allowed( ... 'post_comments'), and if it isn't you need to call 
 * can_comment_on_post()
 */
function can_comment_on_post($observer_xchan,$item) {

//	logger('can_comment_on_post: comment_policy: ' . $item['comment_policy'], LOGGER_DEBUG);

	if(! $observer_xchan)
		return false;
	if($item['comment_policy'] === 'none')
		return false;
	if($observer_xchan === $item['author_xchan'] || $observer_xchan === $item['owner_xchan'])
		return true;
	switch($item['comment_policy']) {
		case 'self':
			if($observer_xchan === $item['author_xchan'] || $observer_xchan === $item['owner_xchan'])
				return true;
			break;
		case 'public':
			// We don't allow public comments yet, until a policy 
			// for dealing with anonymous comments is in place with 
			// a means to moderate comments. Until that time, return 
			// false.
			return false;
			break;
		case 'contacts':
		case '':
			if(array_key_exists('owner',$item)) {
				if(($item['owner']['abook_xchan']) && ($item['owner']['abook_their_perms'] & PERMS_W_COMMENT))
					return true;
			}
			break;
		default:
			break;
	}
	if(strstr($item['comment_policy'],'network:') && strstr($item['comment_policy'],'red'))
		return true;
	if(strstr($item['comment_policy'],'site:') && strstr($item['comment_policy'],get_app()->get_hostname()))
		return true;
	
	return false;
}


/**
 * @function red_zrl_callback
 *   preg_match function when fixing 'naked' links in mod item.php
 *   Check if we've got a hubloc for the site and use a zrl if we do, a url if we don't. 
 * 
 */


function red_zrl_callback($matches) {
	$m = @parse_url($matches[2]);
	$zrl = false;
	if($m['host']) {
		$r = q("select hubloc_url from hubloc where hubloc_host = '%s' limit 1",
			dbesc($m['host'])
		);
		if($r)
			$zrl = true;
	}
	if($zrl)
		return $matches[1] . '[zrl=' . $matches[2] . ']' . $matches[2] . '[/zrl]';
	return $matches[0];
}



/**
 * @function post_activity_item($arr)
 *
 *     post an activity
 * 
 * @param array $arr
 *
 * In its simplest form one needs only to set $arr['body'] to post a note to the logged in channel's wall.
 * Much more complex activities can be created. Permissions are checked. No filtering, tag expansion 
 * or other processing is performed.
 *
 * @returns array 
 *      'success' => true or false 
 *      'activity' => the resulting activity if successful
 */

function post_activity_item($arr) {

	$ret = array('success' => false);

	$is_comment = false;
	if((($arr['parent']) && $arr['parent'] != $arr['id']) || (($arr['parent_mid']) && $arr['parent_mid'] != $arr['mid']))
		$is_comment = true;

	if(! x($arr,'item_flags')) {
		if($is_comment)
			$arr['item_flags'] = ITEM_ORIGIN;
		else
			$arr['item_flags'] = ITEM_ORIGIN | ITEM_WALL | ITEM_THREAD_TOP;
	}	


	$channel  = get_app()->get_channel();
	$observer = get_app()->get_observer();

	$arr['aid']          = 	((x($arr,'aid')) ? $arr['aid'] : $channel['channel_account_id']);
	$arr['uid']          = 	((x($arr,'uid')) ? $arr['uid'] : $channel['channel_id']);

	if(! perm_is_allowed($arr['uid'],$observer['xchan_hash'],(($is_comment) ? 'post_comments' : 'post_wall'))) {
		$ret['message'] = t('Permission denied');
		return $ret;
	}

	if(array_key_exists('content_type',$arr) && $arr['content_type'] == 'text/html')
		$arr['body'] = purify_html($arr['body']);
	else
		$arr['body'] = escape_tags($arr['body']);


	$arr['mid']          = 	((x($arr,'mid')) ? $arr['mid'] : item_message_id());
	$arr['parent_mid']   =  ((x($arr,'parent_mid')) ? $arr['parent_mid'] : $arr['mid']);
	$arr['thr_parent']   =  ((x($arr,'thr_parent')) ? $arr['thr_parent'] : $arr['mid']);

	$arr['owner_xchan']  = 	((x($arr,'owner_xchan'))  ? $arr['owner_xchan']  : $channel['channel_hash']);
	$arr['author_xchan'] = 	((x($arr,'author_xchan')) ? $arr['author_xchan'] : $observer['xchan_hash']);

	$arr['verb']         = 	((x($arr,'verb')) ? $arr['verb'] : ACTIVITY_POST);
	$arr['obj_type']     =  ((x($arr,'obj_type')) ? $arr['obj_type'] : ACTIVITY_OBJ_NOTE);

	$arr['allow_cid']    = ((x($arr,'allow_cid')) ? $arr['allow_cid'] : $channel['channel_allow_cid']);
	$arr['allow_gid']    = ((x($arr,'allow_gid')) ? $arr['allow_gid'] : $channel['channel_allow_gid']);
	$arr['deny_cid']     = ((x($arr,'deny_cid')) ? $arr['deny_cid'] : $channel['channel_deny_cid']);
	$arr['deny_gid']     = ((x($arr,'deny_gid')) ? $arr['deny_gid'] : $channel['channel_deny_gid']);

	$arr['comment_policy'] = map_scope($channel['channel_w_comment']); 

	// for the benefit of plugins, we will behave as if this is an API call rather than a normal online post

	$_REQUEST['api_source'] = 1;

	call_hooks('post_local',$arr);

	if(x($arr,'cancel')) {
		logger('post_activity_item: post cancelled by plugin.');
		return $ret;
	}


	$post = item_store($arr);	
	if($post['result'])
		$post_id = $post['item_id'];

	if($post_id) {
		$arr['id'] = $post_id;
		call_hooks('post_local_end', $arr);
		proc_run('php','include/notifier.php','activity',$post_id);
		$ret['success'] = true;
		$r = q("select * from item where id = %d limit 1",
			intval($post_id)
		);
		if($r)
			$ret['activity'] = $r[0];
	}

	return $ret;

}


function get_public_feed($channel,$params) {

	$type      = 'xml';
	$begin     = '0000-00-00 00:00:00';
	$end       = '';
	$start     = 0;
	$records   = 40;
	$direction = 'desc';
	$pages     = 0;

	if(! $params)
		$params = array();

	$params['type']      = ((x($params,'type'))      ? $params['type']          : 'xml');
	$params['begin']     = ((x($params,'begin'))     ? $params['begin']         : '0000-00-00 00:00:00');
	$params['end']       = ((x($params,'end'))       ? $params['end']           : datetime_convert('UTC','UTC','now'));
	$params['start']     = ((x($params,'start'))     ? $params['start']         : 0);
	$params['records']   = ((x($params,'records'))   ? $params['records']       : 40);
	$params['direction'] = ((x($params,'direction')) ? $params['direction']     : 'desc');
	$params['pages']     = ((x($params,'pages'))     ? intval($params['pages']) : 0);
		
	switch($params['type']) {
		case 'json':
			header("Content-type: application/atom+json");
			break;
		case 'xml':
		default:
			header("Content-type: application/atom+xml");
			break;
	}

	
	return get_feed_for($channel,get_observer_hash(),$params);
}

function get_feed_for($channel, $observer_hash, $params) {

	if(! channel)
		http_status_exit(401);


	if($params['pages']) {
		if(! perm_is_allowed($channel['channel_id'],$observer_hash,'view_pages'))
			http_status_exit(403);
	}
	else {
		if(! perm_is_allowed($channel['channel_id'],$observer_hash,'view_stream'))
			http_status_exit(403);
	}
	$items = items_fetch(array(
		'wall' => '1',
		'datequery' => $params['begin'],
		'datequery2' => $params['end'],
		'start' => $params['start'],          // FIXME
	 	'records' => $params['records'],      // FIXME
		'direction' => $params['direction'],  // FIXME
		'pages' => $params['pages'],
		'order' => 'post'
		), $channel, $observer_hash, CLIENT_MODE_NORMAL, get_app()->module);


	$feed_template = get_markup_template('atom_feed.tpl');

	$atom = '';

	$atom .= replace_macros($feed_template, array(
		'$version'      => xmlify(RED_VERSION),
		'$red'          => xmlify(RED_PLATFORM),
		'$feed_id'      => xmlify($channel['channel_url']),
		'$feed_title'   => xmlify($channel['channel_name']),
		'$feed_updated' => xmlify(datetime_convert('UTC', 'UTC', 'now' , ATOM_TIME)) ,
		'$hub'          => '', // feed_hublinks(),
		'$salmon'       => '', // feed_salmonlinks($channel['channel_address']),
		'$name'         => xmlify($channel['channel_name']),
		'$profile_page' => xmlify($channel['channel_url']),
		'$mimephoto'    => xmlify($channel['xchan_photo_mimetype']),
		'$photo'        => xmlify($channel['xchan_photo_l']),
		'$thumb'        => xmlify($channel['xchan_photo_m']),
		'$picdate'      => '',
		'$uridate'      => '',
		'$namdate'      => '',
		'$birthday'     => '',
		'$community'    => '',
	));

	call_hooks('atom_feed', $atom);

	if($items) {
		$type = 'html';
		foreach($items as $item) {
			if($item['item_private'])
				continue;

			$atom .= atom_entry($item,$type,null,$owner,true);
		}
	}

	call_hooks('atom_feed_end', $atom);

	$atom .= '</feed>' . "\r\n";

	return $atom;
}


function construct_verb($item) {
	if($item['verb'])
		return $item['verb'];
	return ACTIVITY_POST;
}

function construct_activity_object($item) {

	if($item['object']) {
		$o = '<as:object>' . "\r\n";
		$r = json_decode($item['object'],false);

		if(! $r)
			return '';
		if($r->type)
			$o .= '<as:obj_type>' . xmlify($r->type) . '</as:obj_type>' . "\r\n";
		if($r->id)
			$o .= '<id>' . xmlify($r->id) . '</id>' . "\r\n";
		if($r->title)
			$o .= '<title>' . xmlify($r->title) . '</title>' . "\r\n";
		if($r->links) {
			// FIXME!!
			if(substr($r->link,0,1) === '<') {
				$r->link = preg_replace('/\<link(.*?)\"\>/','<link$1"/>',$r->link);
				$o .= $r->link;
			}					
			else
				$o .= '<link rel="alternate" type="text/html" href="' . xmlify($r->link) . '" />' . "\r\n";
		}
		if($r->content)
			$o .= '<content type="html" >' . xmlify(bbcode($r->content)) . '</content>' . "\r\n";
		$o .= '</as:object>' . "\r\n";
		return $o;
	}

	return '';
} 

function construct_activity_target($item) {

	if($item['target']) {
		$o = '<as:target>' . "\r\n";
		$r = json_decode($item['target'],false);
		if(! $r)
			return '';
		if($r->type)
			$o .= '<as:obj_type>' . xmlify($r->type) . '</as:obj_type>' . "\r\n";
		if($r->id)
			$o .= '<id>' . xmlify($r->id) . '</id>' . "\r\n";
		if($r->title)
			$o .= '<title>' . xmlify($r->title) . '</title>' . "\r\n";
		if($r->links) {
			// FIXME !!!
			if(substr($r->link,0,1) === '<') {
				if(strstr($r->link,'&') && (! strstr($r->link,'&amp;')))
					$r->link = str_replace('&','&amp;', $r->link);
				$r->link = preg_replace('/\<link(.*?)\"\>/','<link$1"/>',$r->link);
				$o .= $r->link;
			}					
			else
				$o .= '<link rel="alternate" type="text/html" href="' . xmlify($r->link) . '" />' . "\r\n";
		}
		if($r->content)
			$o .= '<content type="html" >' . xmlify(bbcode($r->content)) . '</content>' . "\r\n";
		$o .= '</as:target>' . "\r\n";
		return $o;
	}

	return '';
}

/* limit_body_size()
 *
 *		The purpose of this function is to apply system message length limits to
 *		imported messages without including any embedded photos in the length
 */

function limit_body_size($body) {

	$maxlen = get_max_import_size();

	// If the length of the body, including the embedded images, is smaller
	// than the maximum, then don't waste time looking for the images
	if($maxlen && (strlen($body) > $maxlen)) {

		$orig_body = $body;
		$new_body = '';
		$textlen = 0;
		$max_found = false;

		$img_start = strpos($orig_body, '[img');
		$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
		$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
		while(($img_st_close !== false) && ($img_end !== false)) {

			$img_st_close++; // make it point to AFTER the closing bracket
			$img_end += $img_start;
			$img_end += strlen('[/img]');

			if(! strcmp(substr($orig_body, $img_start + $img_st_close, 5), 'data:')) {
				// This is an embedded image

				if( ($textlen + $img_start) > $maxlen ) {
					if($textlen < $maxlen) {
						logger('limit_body_size: the limit happens before an embedded image', LOGGER_DEBUG);
						$new_body = $new_body . substr($orig_body, 0, $maxlen - $textlen);
						$textlen = $maxlen;
					}
				}
				else {
					$new_body = $new_body . substr($orig_body, 0, $img_start);
					$textlen += $img_start;
				}

				$new_body = $new_body . substr($orig_body, $img_start, $img_end - $img_start);
			}
			else {

				if( ($textlen + $img_end) > $maxlen ) {
					if($textlen < $maxlen) {
						$new_body = $new_body . substr($orig_body, 0, $maxlen - $textlen);
						$textlen = $maxlen;
					}
				}
				else {
					$new_body = $new_body . substr($orig_body, 0, $img_end);
					$textlen += $img_end;
				}
			}
			$orig_body = substr($orig_body, $img_end);

			if($orig_body === false) // in case the body ends on a closing image tag
				$orig_body = '';

			$img_start = strpos($orig_body, '[img');
			$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
			$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
		}

		if( ($textlen + strlen($orig_body)) > $maxlen) {
			if($textlen < $maxlen) {
				$new_body = $new_body . substr($orig_body, 0, $maxlen - $textlen);
				$textlen = $maxlen;
			}
		}
		else {
			$new_body = $new_body . $orig_body;
			$textlen += strlen($orig_body);
		}

		return $new_body;
	}
	else
		return $body;
}

function title_is_body($title, $body) {

	$title = strip_tags($title);
	$title = trim($title);
	$title = str_replace(array("\n", "\r", "\t", " "), array("","","",""), $title);

	$body = strip_tags($body);
	$body = trim($body);
	$body = str_replace(array("\n", "\r", "\t", " "), array("","","",""), $body);

	if (strlen($title) < strlen($body))
		$body = substr($body, 0, strlen($title));

	if (($title != $body) and (substr($title, -3) == "...")) {
		$pos = strrpos($title, "...");
		if ($pos > 0) {
			$title = substr($title, 0, $pos);
			$body = substr($body, 0, $pos);
		}
	}

	return($title == $body);
}


function get_item_elements($x) {

//	logger('get_item_elements');
	$arr = array();
	$arr['body']         = (($x['body']) ? htmlentities($x['body'],ENT_COMPAT,'UTF-8',false) : '');

	$arr['created']      = datetime_convert('UTC','UTC',$x['created']);
	$arr['edited']       = datetime_convert('UTC','UTC',$x['edited']);

	if($arr['created'] > datetime_convert())
		$arr['created']  = datetime_convert();
	if($arr['edited'] > datetime_convert())
		$arr['edited']   = datetime_convert();

	$arr['expires']      = ((x($x,'expires') && $x['expires']) 
								? datetime_convert('UTC','UTC',$x['expires']) 
								: '0000-00-00 00:00:00');

	$arr['commented']    = ((x($x,'commented') && $x['commented']) 
								? datetime_convert('UTC','UTC',$x['commented']) 
								: $arr['created']);

	$arr['title']        = (($x['title'])          ? htmlentities($x['title'],          ENT_COMPAT,'UTF-8',false) : '');

	if(mb_strlen($arr['title']) > 255)
		$arr['title'] = mb_substr($arr['title'],0,255);


	$arr['app']          = (($x['app'])            ? htmlentities($x['app'],            ENT_COMPAT,'UTF-8',false) : '');
	$arr['mid']          = (($x['message_id'])     ? htmlentities($x['message_id'],     ENT_COMPAT,'UTF-8',false) : '');
	$arr['parent_mid']   = (($x['message_top'])    ? htmlentities($x['message_top'],    ENT_COMPAT,'UTF-8',false) : '');
	$arr['thr_parent']   = (($x['message_parent']) ? htmlentities($x['message_parent'], ENT_COMPAT,'UTF-8',false) : '');

	$arr['plink']        = (($x['permalink'])      ? htmlentities($x['permalink'],      ENT_COMPAT,'UTF-8',false) : '');
	$arr['location']     = (($x['location'])       ? htmlentities($x['location'],       ENT_COMPAT,'UTF-8',false) : '');
	$arr['coord']        = (($x['longlat'])        ? htmlentities($x['longlat'],        ENT_COMPAT,'UTF-8',false) : '');
	$arr['verb']         = (($x['verb'])           ? htmlentities($x['verb'],           ENT_COMPAT,'UTF-8',false) : '');
	$arr['mimetype']     = (($x['mimetype'])       ? htmlentities($x['mimetype'],       ENT_COMPAT,'UTF-8',false) : '');
	$arr['obj_type']     = (($x['object_type'])    ? htmlentities($x['object_type'],    ENT_COMPAT,'UTF-8',false) : '');
	$arr['tgt_type']     = (($x['target_type'])    ? htmlentities($x['target_type'],    ENT_COMPAT,'UTF-8',false) : '');
	$arr['comment_policy'] = (($x['comment_scope']) ? htmlentities($x['comment_scope'],  ENT_COMPAT,'UTF-8',false) : 'contacts');

	$arr['sig']          = (($x['signature']) ? htmlentities($x['signature'],  ENT_COMPAT,'UTF-8',false) : '');

	
	$arr['object']       = activity_sanitise($x['object']);
	$arr['target']       = activity_sanitise($x['target']);

	$arr['attach']       = activity_sanitise($x['attach']);
	$arr['term']         = decode_tags($x['tags']);

	$arr['item_private'] = ((array_key_exists('flags',$x) && is_array($x['flags']) && in_array('private',$x['flags'])) ? 1 : 0);

	$arr['item_flags'] = 0;


	if(array_key_exists('flags',$x) && in_array('deleted',$x['flags']))
		$arr['item_restrict'] = ITEM_DELETED; 

	// Here's the deal - the site might be down or whatever but if there's a new person you've never
	// seen before sending stuff to your stream, we MUST be able to look them up and import their data from their
	// hub and verify that they are legit - or else we're going to toss the post. We only need to do this
	// once, and after that your hub knows them. Sure some info is in the post, but it's only a transit identifier
	// and not enough info to be able to look you up from your hash - which is the only thing stored with the post.

	if(import_author_xchan($x['author']))
		$arr['author_xchan'] = base64url_encode(hash('whirlpool',$x['author']['guid'] . $x['author']['guid_sig'], true));
	else
		return array();

	// save a potentially expensive lookup if author == owner
	if($arr['author_xchan'] === base64url_encode(hash('whirlpool',$x['owner']['guid'] . $x['owner']['guid_sig'], true)))
		$arr['owner_xchan'] = $arr['author_xchan'];
	else {
		if(import_author_xchan($x['owner']))
			$arr['owner_xchan']  = base64url_encode(hash('whirlpool',$x['owner']['guid'] . $x['owner']['guid_sig'], true));
		else
			return array();
	}


	if($arr['sig']) {
		$r = q("select xchan_pubkey from xchan where xchan_hash = '%s' limit 1",
			dbesc($arr['author_xchan'])
		);
		if($r && rsa_verify($x['body'],base64url_decode($arr['sig']),$r[0]['xchan_pubkey']))
			$arr['item_flags'] |= ITEM_VERIFIED;
		else
			logger('get_item_elements: message verification failed.');
	}


	// if it's a private post, encrypt it in the DB.
	// We have to do that here because we need to cleanse the input and prevent bad stuff from getting in,
	// and we need plaintext to do that. 

	if(intval($arr['item_private'])) {
		$arr['item_flags'] = $arr['item_flags'] | ITEM_OBSCURED;
		$key = get_config('system','pubkey');
		if($arr['title'])
			$arr['title'] = json_encode(crypto_encapsulate($arr['title'],$key));
		if($arr['body'])
			$arr['body']  = json_encode(crypto_encapsulate($arr['body'],$key));
	}


	return $arr;

}


function import_author_xchan($x) {

	$r = q("select hubloc_url from hubloc where hubloc_guid = '%s' and hubloc_guid_sig = '%s' and (hubloc_flags & %d) limit 1",
		dbesc($x['guid']),
		dbesc($x['guid_sig']),
		intval(HUBLOC_FLAGS_PRIMARY)
	);

	if($r) {
		logger('import_author_xchan: in cache', LOGGER_DEBUG);
		return true;
	}

	logger('import_author_xchan: entry not in cache - probing: ' . print_r($x,true), LOGGER_DEBUG);

	$them = array('hubloc_url' => $x['url'],'xchan_guid' => $x['guid'], 'xchan_guid_sig' => $x['guid_sig']);
	return zot_refresh($them);
}

function encode_item($item) {
	$x = array();
	$x['type'] = 'activity';

//	logger('encode_item: ' . print_r($item,true));

	$r = q("select channel_r_stream, channel_w_comment from channel where channel_id = %d limit 1",
		intval($item['uid'])
	);

	if($r) {
		$public_scope = $r[0]['channel_r_stream'];
		$comment_scope = $r[0]['channel_w_comment'];
	}
	else {
		$public_scope = 0;
		$comment_scope = 0;
	}

	$scope = map_scope($public_scope);
	$c_scope = map_scope($comment_scope);

	if(array_key_exists('item_flags',$item) && ($item['item_flags'] & ITEM_OBSCURED)) {
		$key = get_config('system','prvkey');
		if($item['title'])
			$item['title'] = crypto_unencapsulate(json_decode_plus($item['title']),$key);
		if($item['body'])
			$item['body'] = crypto_unencapsulate(json_decode_plus($item['body']),$key);
	}

	if($item['item_restrict']  & ITEM_DELETED) {
		$x['message_id'] = $item['mid'];
		$x['created']    = $item['created'];
		$x['flags']      = array('deleted');
		$x['owner']      = encode_item_xchan($item['owner']);
		$x['author']     = encode_item_xchan($item['author']);
		return $x;
	}

	$x['message_id']     = $item['mid'];
	$x['message_top']    = $item['parent_mid'];
	$x['message_parent'] = $item['thr_parent'];
	$x['created']        = $item['created'];
	$x['edited']         = $item['edited'];
	$x['expires']        = $item['expires'];
	$x['commented']      = $item['commented'];
	$x['mimetype']       = $item['mimetype'];
	$x['title']          = $item['title'];
	$x['body']           = $item['body'];
	$x['app']            = $item['app'];
	$x['verb']           = $item['verb'];
	$x['object_type']    = $item['obj_type'];
	$x['target_type']    = $item['tgt_type'];
	$x['permalink']      = $item['plink'];
	$x['location']       = $item['location'];
	$x['longlat']        = $item['coord'];
	$x['signature']      = $item['sig'];

	$x['owner']          = encode_item_xchan($item['owner']);
	$x['author']         = encode_item_xchan($item['author']);
	if($item['object'])
		$x['object']     = json_decode_plus($item['object']);
	if($item['target'])
		$x['target']     = json_decode_plus($item['target']);
	if($item['attach'])
		$x['attach']     = json_decode_plus($item['attach']);
	if($y = encode_item_flags($item))
		$x['flags']      = $y;

	if(! in_array('private',$y))
		$x['public_scope'] = $scope;

	if($item['item_flags'] & ITEM_NOCOMMENT)
		$x['comment_scope'] = 'none';
	else
		$x['comment_scope'] = $c_scope;

	if($item['term'])
		$x['tags']       = encode_item_terms($item['term']);

	logger('encode_item: ' . print_r($x,true));

	return $x;

}


function map_scope($scope) {
	switch($scope) {
		case 0:
			return 'self';
		case PERMS_PUBLIC:
			return 'public';
		case PERMS_NETWORK:
			return 'network: red';
		case PERMS_SITE:
			return 'site: ' . get_app()->get_hostname();
		case PERMS_CONTACTS:
		default:
			return 'contacts';
	}
}	



function encode_item_xchan($xchan) {

	$ret = array();
	$ret['name']     = $xchan['xchan_name'];
	$ret['address']  = $xchan['xchan_addr'];
	$ret['url']      = $xchan['hubloc_url'];
	$ret['photo']    = array('mimetype' => $xchan['xchan_photo_mimetype'], 'src' => $xchan['xchan_photo_m']);
	$ret['guid']     = $xchan['xchan_guid'];
	$ret['guid_sig'] = $xchan['xchan_guid_sig'];
	return $ret;
}

function encode_item_terms($terms) {
	$ret = array();	

	$allowed_export_terms = array( TERM_UNKNOWN, TERM_HASHTAG, TERM_MENTION, TERM_CATEGORY );

	if($terms) {
		foreach($terms as $term) {
			if(in_array($term['type'],$allowed_export_terms))
				$ret[] = array('tag' => $term['term'], 'url' => $term['url'], 'type' => termtype($term['type']));
		}
	}
	return $ret;
}

function termtype($t) {
	$types = array('unknown','hashtag','mention','category','private_category','file','search');
	return(($types[$t]) ? $types[$t] : 'unknown');
}

function decode_tags($t) {

	if($t) {
		$ret = array();
		foreach($t as $x) {
			$tag = array();
			$tag['term'] = htmlentities($x['tag'],  ENT_COMPAT,'UTF-8',false);
			$tag['url']  = htmlentities($x['url'],  ENT_COMPAT,'UTF-8',false);
			switch($x['type']) {
				case 'hashtag':
					$tag['type'] = TERM_HASHTAG;
					break;
				case 'mention':
					$tag['type'] = TERM_MENTION;
					break;
				case 'category':
					$tag['type'] = TERM_CATEGORY;
					break;
				case 'private_category':
					$tag['type'] = TERM_PCATEGORY;
					break;
				case 'file':
					$tag['type'] = TERM_FILE;
					break;
				case 'search':
					$tag['type'] = TERM_SEARCH;
					break;
				default:
				case 'unknown':
					$tag['type'] = TERM_UNKNOWN;
					break;
			}
			$ret[] = $tag;
		}
		return $ret;
	}
	return '';

}

// santise a potentially complex array

function activity_sanitise($arr) {
	if($arr) {
		if(is_array($arr)) {
			$ret = array();
			foreach($arr as $k => $x) {
				if(is_array($x))
					$ret[$k] = activity_sanitise($x);
				else
					$ret[$k] = htmlentities($x, ENT_COMPAT,'UTF-8',false);
			}
			return $ret;
		}
		else {
			return htmlentities($arr, ENT_COMPAT,'UTF-8', false);
		}
	}
	return '';
}

// sanitise a simple linear array

function array_sanitise($arr) {
	if($arr) {
		$ret = array();
		foreach($arr as $x) {
			$ret[] = htmlentities($x, ENT_COMPAT,'UTF-8',false);
		}
		return $ret;
	}
	return '';
}

function encode_item_flags($item) {

//	most of item_flags and item_restrict are local settings which don't apply when transmitted.
//  We may need those for the case of syncing other hub locations which you are attached to.
//  ITEM_DELETED is handled in encode_item directly so we don't need to handle it here. 

	$ret = array();
	if($item['item_flags'] & ITEM_THREAD_TOP)
		$ret[] = 'thread_parent';
	if($item['item_flags'] & ITEM_NSFW)
		$ret[] = 'nsfw';
	if($item['item_private'])
		$ret[] = 'private';
	
	return $ret;
}

function encode_mail($item) {
	$x = array();
	$x['type'] = 'mail';

	if(array_key_exists('mail_flags',$item) && ($item['mail_flags'] & MAIL_OBSCURED)) {
		$key = get_config('system','prvkey');
		if($item['title'])
			$item['title'] = crypto_unencapsulate(json_decode_plus($item['title']),$key);
		if($item['body'])
			$item['body'] = crypto_unencapsulate(json_decode_plus($item['body']),$key);
	}

	$x['message_id']     = $item['mid'];
	$x['message_parent'] = $item['parent_mid'];
	$x['created']        = $item['created'];
	$x['expires']        = $item['expires'];
	$x['title']          = $item['title'];
	$x['body']           = $item['body'];
	$x['from']           = encode_item_xchan($item['from']);
	$x['to']             = encode_item_xchan($item['to']);

	if($item['attach'])
		$x['attach']     = json_decode_plus($item['attach']);

	$x['flags'] = array();

	if($item['mail_flags'] & MAIL_RECALLED) {
		$x['flags'][] = 'recalled';
		$x['title'] = '';
		$x['body']  = '';
	}

	return $x;
}



function get_mail_elements($x) {

	$arr = array();

	$arr['body']         = (($x['body']) ? htmlentities($x['body'], ENT_COMPAT,'UTF-8',false) : '');
	$arr['title']        = (($x['title'])? htmlentities($x['title'],ENT_COMPAT,'UTF-8',false) : '');

	$arr['created']      = datetime_convert('UTC','UTC',$x['created']);
	if((! array_key_exists('expires',$x)) || ($x['expires'] === '0000-00-00 00:00:00'))
		$arr['expires'] = '0000-00-00 00:00:00';
	else
		$arr['expires']      = datetime_convert('UTC','UTC',$x['expires']);

	$arr['mail_flags'] = 0;

	if($x['flags'] && is_array($x['flags'])) {
		if(in_array('recalled',$x['flags'])) {
			$arr['mail_flags'] |= MAIL_RECALLED;
		}
	}

	$key = get_config('system','pubkey');
	$arr['mail_flags'] |= MAIL_OBSCURED;
	$arr['body'] = htmlentities($arr['body'],ENT_COMPAT,'UTF-8',false);
	if($arr['body'])
		$arr['body']  = json_encode(crypto_encapsulate($arr['body'],$key));
	$arr['title'] = htmlentities($arr['title'],ENT_COMPAT,'UTF-8',false);
	if($arr['title'])
		$arr['title'] = json_encode(crypto_encapsulate($arr['title'],$key));

	if($arr['created'] > datetime_convert())
		$arr['created']  = datetime_convert();

	$arr['mid']          = (($x['message_id'])     ? htmlentities($x['message_id'],     ENT_COMPAT,'UTF-8',false) : '');
	$arr['parent_mid']   = (($x['message_parent']) ? htmlentities($x['message_parent'], ENT_COMPAT,'UTF-8',false) : '');

	if($x['attach'])
		$arr['attach'] = activity_sanitise($x['attach']);


	if(import_author_xchan($x['from']))
		$arr['from_xchan'] = base64url_encode(hash('whirlpool',$x['from']['guid'] . $x['from']['guid_sig'], true));
	else
		return array();

	if(import_author_xchan($x['to']))
		$arr['to_xchan']  = base64url_encode(hash('whirlpool',$x['to']['guid'] . $x['to']['guid_sig'], true));
	else
		return array();


	return $arr;

}


function get_profile_elements($x) {

	$arr = array();

	if(import_author_xchan($x['from']))
		$arr['xprof_hash'] = base64url_encode(hash('whirlpool',$x['from']['guid'] . $x['from']['guid_sig'], true));
	else
		return array();

	$arr['desc']         = (($x['title']) ? htmlentities($x['title'],ENT_COMPAT,'UTF-8',false) : '');

	$arr['dob']          = datetime_convert('UTC','UTC',$x['birthday'],'Y-m-d');
	$arr['age']          = (($x['age']) ? intval($x['age']) : 0);

	$arr['gender']       = (($x['gender'])    ? htmlentities($x['gender'],    ENT_COMPAT,'UTF-8',false) : '');
	$arr['marital']      = (($x['marital'])   ? htmlentities($x['marital'],   ENT_COMPAT,'UTF-8',false) : '');
	$arr['sexual']       = (($x['sexual'])    ? htmlentities($x['sexual'],    ENT_COMPAT,'UTF-8',false) : '');
	$arr['locale']       = (($x['locale'])    ? htmlentities($x['locale'],    ENT_COMPAT,'UTF-8',false) : '');
	$arr['region']       = (($x['region'])    ? htmlentities($x['region'],    ENT_COMPAT,'UTF-8',false) : '');
	$arr['postcode']     = (($x['postcode'])  ? htmlentities($x['postcode'],  ENT_COMPAT,'UTF-8',false) : '');
	$arr['country']      = (($x['country'])   ? htmlentities($x['country'],   ENT_COMPAT,'UTF-8',false) : '');

	$arr['keywords']     = (($x['keywords'] && is_array($x['keywords'])) ? array_sanitise($x['keywords']) : array()); 

	return $arr;

}



function get_atom_elements($feed,$item) {


	$best_photo = array();

	$res = array();

	$author = $item->get_author();
	if($author) { 
		$res['author-name'] = unxmlify($author->get_name());
		$res['author-link'] = unxmlify($author->get_link());
	}
	else {
		$res['author-name'] = unxmlify($feed->get_title());
		$res['author-link'] = unxmlify($feed->get_permalink());
	}
	$res['mid'] = unxmlify($item->get_id());
	$res['title'] = unxmlify($item->get_title());
	$res['body'] = unxmlify($item->get_content());
	$res['plink'] = unxmlify($item->get_link(0));

	// removing the content of the title if its identically to the body
	// This helps with auto generated titles e.g. from tumblr

	if (title_is_body($res["title"], $res["body"]))
		$res['title'] = "";

	if($res['plink'])
		$base_url = implode('/', array_slice(explode('/',$res['plink']),0,3));
	else
		$base_url = '';

	// look for a photo. We should check media size and find the best one,
	// but for now let's just find any author photo

	$rawauthor = $item->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10,'author');

	if($rawauthor && $rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link']) {
		$base = $rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'];
		foreach($base as $link) {
			if(!x($res, 'author-avatar') || !$res['author-avatar']) {
				if($link['attribs']['']['rel'] === 'photo' || $link['attribs']['']['rel'] === 'avatar')
					$res['author-avatar'] = unxmlify($link['attribs']['']['href']);
			}
		}
	}

	$rawactor = $item->get_item_tags(NAMESPACE_ACTIVITY, 'actor');

	if($rawactor && activity_match($rawactor[0]['child'][NAMESPACE_ACTIVITY]['obj_type'][0]['data'],ACTIVITY_OBJ_PERSON)) {
		$base = $rawactor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'];
		if($base && count($base)) {
			foreach($base as $link) {
				if($link['attribs']['']['rel'] === 'alternate' && (! $res['author-link']))
					$res['author-link'] = unxmlify($link['attribs']['']['href']);
				if(!x($res, 'author-avatar') || !$res['author-avatar']) {
					if($link['attribs']['']['rel'] === 'avatar' || $link['attribs']['']['rel'] === 'photo')
						$res['author-avatar'] = unxmlify($link['attribs']['']['href']);
				}
			}
		}
	}

	// No photo/profile-link on the item - look at the feed level

	if((! (x($res,'author-link'))) || (! (x($res,'author-avatar')))) {
		$rawauthor = $feed->get_feed_tags(SIMPLEPIE_NAMESPACE_ATOM_10,'author');
		if($rawauthor && $rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link']) {
			$base = $rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'];
			foreach($base as $link) {
				if($link['attribs']['']['rel'] === 'alternate' && (! $res['author-link']))
					$res['author-link'] = unxmlify($link['attribs']['']['href']);
				if(! $res['author-avatar']) {
					if($link['attribs']['']['rel'] === 'photo' || $link['attribs']['']['rel'] === 'avatar')
						$res['author-avatar'] = unxmlify($link['attribs']['']['href']);
				}
			}
		}

		$rawactor = $feed->get_feed_tags(NAMESPACE_ACTIVITY, 'subject');

		if($rawactor && activity_match($rawactor[0]['child'][NAMESPACE_ACTIVITY]['obj_type'][0]['data'],ACTIVITY_OBJ_PERSON)) {
			$base = $rawactor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'];

			if($base && count($base)) {
				foreach($base as $link) {
					if($link['attribs']['']['rel'] === 'alternate' && (! $res['author-link']))
						$res['author-link'] = unxmlify($link['attribs']['']['href']);
					if(! (x($res,'author-avatar'))) {
						if($link['attribs']['']['rel'] === 'avatar' || $link['attribs']['']['rel'] === 'photo')
							$res['author-avatar'] = unxmlify($link['attribs']['']['href']);
					}
				}
			}
		}
	}

	$apps = $item->get_item_tags(NAMESPACE_STATUSNET,'notice_info');
	if($apps && $apps[0]['attribs']['']['source']) {
		$res['app'] = strip_tags(unxmlify($apps[0]['attribs']['']['source']));
	}		   

	/**
	 * If there's a copy of the body content which is guaranteed to have survived mangling in transit, use it.
	 */

	$have_real_body = false;

	$rawenv = $item->get_item_tags(NAMESPACE_DFRN, 'env');
	if($rawenv) {
		$have_real_body = true;
		$res['body'] = $rawenv[0]['data'];
		$res['body'] = str_replace(array(' ',"\t","\r","\n"), array('','','',''),$res['body']);
		// make sure nobody is trying to sneak some html tags by us
		$res['body'] = notags(base64url_decode($res['body']));
	}

	
	$res['body'] = limit_body_size($res['body']);

	// It isn't certain at this point whether our content is plaintext or html and we'd be foolish to trust 
	// the content type. Our own network only emits text normally, though it might have been converted to 
	// html if we used a pubsubhubbub transport. But if we see even one html tag in our text, we will
	// have to assume it is all html and needs to be purified.

	// It doesn't matter all that much security wise - because before this content is used anywhere, we are 
	// going to escape any tags we find regardless, but this lets us import a limited subset of html from 
	// the wild, by sanitising it and converting supported tags to bbcode before we rip out any remaining 
	// html.

	if((strpos($res['body'],'<') !== false) && (strpos($res['body'],'>') !== false)) {

		$res['body'] = reltoabs($res['body'],$base_url);

		$res['body'] = html2bb_video($res['body']);

		$res['body'] = oembed_html2bbcode($res['body']);

		$res['body'] = purify_html($res['body']);

		$res['body'] = @html2bbcode($res['body']);


	}
	elseif(! $have_real_body) {

		// it's not one of our messages and it has no tags
		// so it's probably just text. We'll escape it just to be safe.

		$res['body'] = escape_tags($res['body']);
	}

	$private = $item->get_item_tags(NAMESPACE_DFRN,'private');
	if($private && intval($private[0]['data']) > 0)
		$res['private'] = intval($private[0]['data']);
	else
		$res['private'] = 0;

	$rawlocation = $item->get_item_tags(NAMESPACE_DFRN, 'location');
	if($rawlocation)
		$res['location'] = unxmlify($rawlocation[0]['data']);


	$rawcreated = $item->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10,'published');
	if($rawcreated)
		$res['created'] = unxmlify($rawcreated[0]['data']);


	$rawedited = $item->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10,'updated');
	if($rawedited)
		$res['edited'] = unxmlify($rawedited[0]['data']);

	if((x($res,'edited')) && (! (x($res,'created'))))
		$res['created'] = $res['edited']; 

	if(! $res['created'])
		$res['created'] = $item->get_date('c');

	if(! $res['edited'])
		$res['edited'] = $item->get_date('c');


	// Disallow time travelling posts

	$d1 = strtotime($res['created']);
	$d2 = strtotime($res['edited']);
	$d3 = strtotime('now');

	if($d1 > $d3)
		$res['created'] = datetime_convert();
	if($d2 > $d3)
		$res['edited'] = datetime_convert();

	$rawowner = $item->get_item_tags(NAMESPACE_DFRN, 'owner');
	if($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['name'][0]['data'])
		$res['owner-name'] = unxmlify($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['name'][0]['data']);
	elseif($rawowner[0]['child'][NAMESPACE_DFRN]['name'][0]['data'])
		$res['owner-name'] = unxmlify($rawowner[0]['child'][NAMESPACE_DFRN]['name'][0]['data']);
	if($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['uri'][0]['data'])
		$res['owner-link'] = unxmlify($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['uri'][0]['data']);
	elseif($rawowner[0]['child'][NAMESPACE_DFRN]['uri'][0]['data'])
		$res['owner-link'] = unxmlify($rawowner[0]['child'][NAMESPACE_DFRN]['uri'][0]['data']);

	if($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link']) {
		$base = $rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'];

		foreach($base as $link) {
			if(!x($res, 'owner-avatar') || !$res['owner-avatar']) {
				if($link['attribs']['']['rel'] === 'photo' || $link['attribs']['']['rel'] === 'avatar')
					$res['owner-avatar'] = unxmlify($link['attribs']['']['href']);
			}
		}
	}

	$rawgeo = $item->get_item_tags(NAMESPACE_GEORSS,'point');
	if($rawgeo)
		$res['coord'] = unxmlify($rawgeo[0]['data']);


	$rawverb = $item->get_item_tags(NAMESPACE_ACTIVITY, 'verb');

	// select between supported verbs

	if($rawverb) {
		$res['verb'] = unxmlify($rawverb[0]['data']);
	}

	// translate OStatus unfollow to activity streams if it happened to get selected
		
	if((x($res,'verb')) && ($res['verb'] === 'http://ostatus.org/schema/1.0/unfollow'))
		$res['verb'] = ACTIVITY_UNFOLLOW;

	$cats = $item->get_categories();
	if($cats) {
		$terms = array();
		foreach($cats as $cat) {
			$term = $cat->get_term();
			if(! $term)
				$term = $cat->get_label();
			$scheme = $cat->get_scheme();
			$termurl = '';
			if($scheme && $term && stristr($scheme,'X-DFRN:')) {
				$termtype = ((substr($scheme,7,1) === '#') ? TERM_HASHTAG : TERM_MENTION);
				$termurl = unxmlify(substr($scheme,9));
			}
			else {
				$termtype = TERM_UNKNOWN;
			}
			$termterm = notags(trim(unxmlify($term)));

			if($termterm) {
				$terms = array(
					'otype' => TERM_OBJ_POST,
					'type'  => $termtype,
					'url'   => $termurl,
					'term'  => $termterm,
				);
			}		
		}
		$res['term'] =  implode(',', $tag_arr);
	}

	$attach = $item->get_enclosures();
	if($attach) {
		$att_arr = array();
		foreach($attach as $att) {
			$len   = intval($att->get_length());
			$link  = str_replace(array(',','"'),array('%2D','%22'),notags(trim(unxmlify($att->get_link()))));
			$title = str_replace(array(',','"'),array('%2D','%22'),notags(trim(unxmlify($att->get_title()))));
			$type  = str_replace(array(',','"'),array('%2D','%22'),notags(trim(unxmlify($att->get_type()))));
			if(strpos($type,';'))
				$type = substr($type,0,strpos($type,';'));
			if((! $link) || (strpos($link,'http') !== 0))
				continue;

			if(! $title)
				$title = ' ';
			if(! $type)
				$type = 'application/octet-stream';

			$att_arr[] = '[attach]href="' . $link . '" length="' . $len . '" type="' . $type . '" title="' . $title . '"[/attach]'; 
		}
		$res['attach'] = implode(',', $att_arr);
	}

	$rawobj = $item->get_item_tags(NAMESPACE_ACTIVITY, 'object');

	if($rawobj) {
		$res['object'] = '<object>' . "\n";
		$child = $rawobj[0]['child'];
		if($child[NAMESPACE_ACTIVITY]['obj_type'][0]['data']) {
			$res['obj_type'] = $child[NAMESPACE_ACTIVITY]['obj_type'][0]['data'];
			$res['object'] .= '<type>' . $child[NAMESPACE_ACTIVITY]['obj_type'][0]['data'] . '</type>' . "\n";
		}	
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'id') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['id'][0]['data'])
			$res['object'] .= '<id>' . $child[SIMPLEPIE_NAMESPACE_ATOM_10]['id'][0]['data'] . '</id>' . "\n";
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'link') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['link'])
			$res['object'] .= '<link>' . encode_rel_links($child[SIMPLEPIE_NAMESPACE_ATOM_10]['link']) . '</link>' . "\n";
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'title') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['title'][0]['data'])
			$res['object'] .= '<title>' . $child[SIMPLEPIE_NAMESPACE_ATOM_10]['title'][0]['data'] . '</title>' . "\n";
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'content') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['content'][0]['data']) {
			$body = $child[SIMPLEPIE_NAMESPACE_ATOM_10]['content'][0]['data'];
			if(! $body)
				$body = $child[SIMPLEPIE_NAMESPACE_ATOM_10]['summary'][0]['data'];
			// preserve a copy of the original body content in case we later need to parse out any microformat information, e.g. events
			$res['object'] .= '<orig>' . xmlify($body) . '</orig>' . "\n";
			if((strpos($body,'<') !== false) || (strpos($body,'>') !== false)) {

				$body = purify_html($body);
				$body = html2bbcode($body);

			}

			$res['object'] .= '<content>' . $body . '</content>' . "\n";
		}

		$res['object'] .= '</object>' . "\n";
	}

	$rawobj = $item->get_item_tags(NAMESPACE_ACTIVITY, 'target');

	if($rawobj) {
		$res['target'] = '<target>' . "\n";
		$child = $rawobj[0]['child'];
		if($child[NAMESPACE_ACTIVITY]['obj_type'][0]['data']) {
			$res['target'] .= '<type>' . $child[NAMESPACE_ACTIVITY]['obj_type'][0]['data'] . '</type>' . "\n";
		}	
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'id') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['id'][0]['data'])
			$res['target'] .= '<id>' . $child[SIMPLEPIE_NAMESPACE_ATOM_10]['id'][0]['data'] . '</id>' . "\n";
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'link') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['link'])
			$res['target'] .= '<link>' . encode_rel_links($child[SIMPLEPIE_NAMESPACE_ATOM_10]['link']) . '</link>' . "\n";
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'data') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['title'][0]['data'])
			$res['target'] .= '<title>' . $child[SIMPLEPIE_NAMESPACE_ATOM_10]['title'][0]['data'] . '</title>' . "\n";
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'data') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['content'][0]['data']) {
			$body = $child[SIMPLEPIE_NAMESPACE_ATOM_10]['content'][0]['data'];
			if(! $body)
				$body = $child[SIMPLEPIE_NAMESPACE_ATOM_10]['summary'][0]['data'];
			// preserve a copy of the original body content in case we later need to parse out any microformat information, e.g. events
			$res['target'] .= '<orig>' . xmlify($body) . '</orig>' . "\n";
			if((strpos($body,'<') !== false) || (strpos($body,'>') !== false)) {

				$body = purify_html($body);
				$body = html2bbcode($body);
			}

			$res['target'] .= '<content>' . $body . '</content>' . "\n";
		}

		$res['target'] .= '</target>' . "\n";
	}

	// This is some experimental stuff. By now retweets are shown with "RT:"
	// But: There is data so that the message could be shown similar to native retweets
	// There is some better way to parse this array - but it didn't worked for me.

	$child = $item->feed->data["child"][SIMPLEPIE_NAMESPACE_ATOM_10]["feed"][0]["child"][SIMPLEPIE_NAMESPACE_ATOM_10]["entry"][0]["child"]["http://activitystrea.ms/spec/1.0/"][object][0]["child"];
	if (is_array($child)) {
		$message = $child["http://activitystrea.ms/spec/1.0/"]["object"][0]["child"][SIMPLEPIE_NAMESPACE_ATOM_10]["content"][0]["data"];
		$author = $child[SIMPLEPIE_NAMESPACE_ATOM_10]["author"][0]["child"][SIMPLEPIE_NAMESPACE_ATOM_10];
		$uri = $author["uri"][0]["data"];
		$name = $author["name"][0]["data"];
		$avatar = @array_shift($author["link"][2]["attribs"]);
		$avatar = $avatar["href"];

		if (($name != "") and ($uri != "") and ($avatar != "") and ($message != "")) {
			$res["owner-name"] = $res["author-name"];
			$res["owner-link"] = $res["author-link"];
			$res["owner-avatar"] = $res["author-avatar"];

			$res["author-name"] = $name;
			$res["author-link"] = $uri;
			$res["author-avatar"] = $avatar;

			$res["body"] = html2bbcode($message);
		}
	}

	$arr = array('feed' => $feed, 'item' => $item, 'result' => $res);

	call_hooks('parse_atom', $arr);
	logger('get_atom_elements: ' . print_r($res,true));

	return $res;
}

function encode_rel_links($links) {
	$o = '';
	if(! ((is_array($links)) && (count($links))))
		return $o;
	foreach($links as $link) {
		$o .= '<link ';
		if($link['attribs']['']['rel'])
			$o .= 'rel="' . $link['attribs']['']['rel'] . '" ';
		if($link['attribs']['']['type'])
			$o .= 'type="' . $link['attribs']['']['type'] . '" ';
		if($link['attribs']['']['href'])
			$o .= 'href="' . $link['attribs']['']['href'] . '" ';
		if( (x($link['attribs'],NAMESPACE_MEDIA)) && $link['attribs'][NAMESPACE_MEDIA]['width'])
			$o .= 'media:width="' . $link['attribs'][NAMESPACE_MEDIA]['width'] . '" ';
		if( (x($link['attribs'],NAMESPACE_MEDIA)) && $link['attribs'][NAMESPACE_MEDIA]['height'])
			$o .= 'media:height="' . $link['attribs'][NAMESPACE_MEDIA]['height'] . '" ';
		$o .= ' />' . "\n" ;
	}
	return xmlify($o);
}

function item_store($arr,$allow_exec = false) {

	$ret = array('result' => false, 'item_id' => 0);

	if(! $arr['uid']) {
		logger('item_store: no uid');
		$ret['message'] = 'No uid.';
		return ret;
	}

	$uplinked_comment = false;

	// If a page layout is provided, ensure it exists and belongs to us. 

	if(array_key_exists('layout_mid',$arr) && $arr['layout_mid']) {
		$l = q("select item_restrict from item where mid = '%s' and uid = %d limit 1",
			dbesc($arr['layout_mid']),
			intval($arr['uid'])
		);
		if((! $l) || (! ($l[0]['item_restrict'] & ITEM_PDL)))
			unset($arr['layout_mid']);
	}

	// Don't let anybody set these, either intentionally or accidentally

	if(array_key_exists('id',$arr))
		unset($arr['id']);
	if(array_key_exists('parent',$arr))
		unset($arr['parent']);

	$arr['mimetype']      = ((x($arr,'mimetype'))      ? notags(trim($arr['mimetype']))      : 'text/bbcode');

	if(($arr['mimetype'] == 'application/x-php') && (! $allow_exec)) {
		logger('item_store: php mimetype but allow_exec is denied.');
		$ret['message'] = 'exec denied.';
		return $ret;
	}


	$arr['title']         = ((x($arr,'title'))         ? notags(trim($arr['title']))         : '');
	$arr['body']          = ((x($arr,'body'))          ? trim($arr['body'])                  : '');

	$arr['allow_cid']     = ((x($arr,'allow_cid'))     ? trim($arr['allow_cid'])             : '');
	$arr['allow_gid']     = ((x($arr,'allow_gid'))     ? trim($arr['allow_gid'])             : '');
	$arr['deny_cid']      = ((x($arr,'deny_cid'))      ? trim($arr['deny_cid'])              : '');
	$arr['deny_gid']      = ((x($arr,'deny_gid'))      ? trim($arr['deny_gid'])              : '');
	$arr['item_private']  = ((x($arr,'item_private'))  ? intval($arr['item_private'])        : 0 );
	$arr['item_flags']    = ((x($arr,'item_flags'))    ? intval($arr['item_flags'])          : 0 );

	$arr['title'] = escape_tags($arr['title']);


	// only detect language if we have text content, and if the post is private but not yet
	// obscured, make it so.

	if(! ($arr['item_flags'] & ITEM_OBSCURED)) {

		$arr['lang'] = detect_language($arr['body']);
		// apply the input filter here - if it is obscured it has been filtered already
		$arr['body'] = z_input_filter($arr['uid'],$arr['body'],$arr['mimetype']);


		if(local_user() && (! $arr['sig'])) {
			$channel = get_app()->get_channel();
			if($channel['channel_hash'] === $arr['author_xchan']) {
				$arr['sig'] = base64url_encode(rsa_sign($arr['body'],$channel['channel_prvkey']));
				$arr['item_flags'] |= ITEM_VERIFIED;
			}
		}

		$allowed_languages = get_pconfig($arr['uid'],'system','allowed_languages');
	
		if((is_array($allowed_languages)) && ($arr['lang']) && (! array_key_exists($arr['lang'],$allowed_languages))) {
			$translate = array('item' => $arr, 'from' => $arr['lang'], 'to' => $allowed_languages, 'translated' => false);
			call_hooks('item_translate', $translate);
			if((! $translate['translated']) && (intval(get_pconfig($arr['uid'],'system','reject_disallowed_languages')))) {
				logger('item_store: language ' . $arr['lang'] . ' not accepted for uid ' . $arr['uid']);
				$ret['message'] = 'language not accepted';
				return $ret;
			}
			$arr = $translate['item'];
		}
		if($arr['item_private']) {
			$key = get_config('system','pubkey');
			$arr['item_flags'] = $arr['item_flags'] | ITEM_OBSCURED;
			if($arr['title'])
				$arr['title'] = json_encode(crypto_encapsulate($arr['title'],$key));
			if($arr['body'])
				$arr['body']  = json_encode(crypto_encapsulate($arr['body'],$key));
		}

	}


	if((x($arr,'object')) && is_array($arr['object'])) {
		activity_sanitise($arr['object']);
		$arr['object'] = json_encode($arr['object']);
	}

	if((x($arr,'target')) && is_array($arr['target'])) {
		activity_sanitise($arr['target']);
		$arr['target'] = json_encode($arr['target']);
	}

	if((x($arr,'attach')) && is_array($arr['attach'])) {
		activity_sanitise($arr['attach']);
		$arr['attach'] = json_encode($arr['attach']);
	}

	$arr['aid']           = ((x($arr,'aid'))           ? intval($arr['aid'])                 : 0);
	$arr['mid']           = ((x($arr,'mid'))           ? notags(trim($arr['mid']))           : random_string());
	$arr['author_xchan']  = ((x($arr,'author_xchan'))  ? notags(trim($arr['author_xchan']))  : '');
	$arr['owner_xchan']   = ((x($arr,'owner_xchan'))   ? notags(trim($arr['owner_xchan']))   : '');
	$arr['created']       = ((x($arr,'created') !== false) ? datetime_convert('UTC','UTC',$arr['created']) : datetime_convert());
	$arr['edited']        = ((x($arr,'edited')  !== false) ? datetime_convert('UTC','UTC',$arr['edited'])  : datetime_convert());
	$arr['expires']       = ((x($arr,'expires')  !== false) ? datetime_convert('UTC','UTC',$arr['expires'])  : '0000-00-00 00:00:00');
	$arr['commented']     = ((x($arr,'commented')  !== false) ? datetime_convert('UTC','UTC',$arr['commented'])  : datetime_convert());
	$arr['received']      = datetime_convert();
	$arr['changed']       = datetime_convert();
	$arr['location']      = ((x($arr,'location'))      ? notags(trim($arr['location']))      : '');
	$arr['coord']         = ((x($arr,'coord'))         ? notags(trim($arr['coord']))         : '');
	$arr['parent_mid']    = ((x($arr,'parent_mid'))    ? notags(trim($arr['parent_mid']))    : '');
	$arr['thr_parent']    = ((x($arr,'thr_parent'))    ? notags(trim($arr['thr_parent']))    : $arr['parent_mid']);
	$arr['verb']          = ((x($arr,'verb'))          ? notags(trim($arr['verb']))          : '');
	$arr['obj_type']      = ((x($arr,'obj_type'))      ? notags(trim($arr['obj_type']))      : '');
	$arr['object']        = ((x($arr,'object'))        ? trim($arr['object'])                : '');
	$arr['tgt_type']      = ((x($arr,'tgt_type'))      ? notags(trim($arr['tgt_type']))      : '');
	$arr['target']        = ((x($arr,'target'))        ? trim($arr['target'])                : '');
	$arr['plink']         = ((x($arr,'plink'))         ? notags(trim($arr['plink']))         : '');
	$arr['attach']        = ((x($arr,'attach'))        ? notags(trim($arr['attach']))        : '');
	$arr['app']           = ((x($arr,'app'))           ? notags(trim($arr['app']))           : '');
	$arr['item_restrict'] = ((x($arr,'item_restrict')) ? intval($arr['item_restrict'])       : 0 );

	$arr['comment_policy'] = ((x($arr,'comment_policy')) ? notags(trim($arr['comment_policy']))  : 'contacts' );

	
	$arr['item_flags'] = $arr['item_flags'] | ITEM_UNSEEN;

	if($arr['comment_policy'] == 'none')
		$arr['item_flags'] = $arr['item_flags'] | ITEM_NOCOMMENT;



	// handle time travelers
	// Allow a bit of fudge in case somebody just has a slightly slow/fast clock

	$d1 = new DateTime('now +10 minutes', new DateTimeZone('UTC'));
	$d2 = new DateTime($arr['created'] . '+00:00');
	if($d2 > $d1)
		$arr['item_restrict'] = $arr['item_restrict'] | ITEM_DELAYED_PUBLISH;

	$arr['llink'] = z_root() . '/display/' . $arr['mid'];

	if(! $arr['plink'])
		$arr['plink'] = $arr['llink'];

	if($arr['parent_mid'] === $arr['mid']) {
		$parent_id = 0;
		$parent_deleted = 0;
		$allow_cid = $arr['allow_cid'];
		$allow_gid = $arr['allow_gid'];
		$deny_cid  = $arr['deny_cid'];
		$deny_gid  = $arr['deny_gid'];
		$arr['item_flags'] = $arr['item_flags'] | ITEM_THREAD_TOP;
	}
	else { 

		// find the parent and snarf the item id and ACL's
		// and anything else we need to inherit

		$r = q("SELECT * FROM `item` WHERE `mid` = '%s' AND `uid` = %d ORDER BY `id` ASC LIMIT 1",
			dbesc($arr['parent_mid']),
			intval($arr['uid'])
		);

		if($r) {

			// is the new message multi-level threaded?
			// even though we don't support it now, preserve the info
			// and re-attach to the conversation parent.

			if($r[0]['mid'] != $r[0]['parent_mid']) {
				$arr['parent_mid'] = $r[0]['parent_mid'];
				$z = q("SELECT * FROM `item` WHERE `mid` = '%s' AND `parent_mid` = '%s' AND `uid` = %d 
					ORDER BY `id` ASC LIMIT 1",
					dbesc($r[0]['parent_mid']),
					dbesc($r[0]['parent_mid']),
					intval($arr['uid'])
				);
				if($z && count($z))
					$r = $z;
			}

			$parent_id      = $r[0]['id'];
			$parent_deleted = $r[0]['item_restrict'] & ITEM_DELETED;
			$allow_cid      = $r[0]['allow_cid'];
			$allow_gid      = $r[0]['allow_gid'];
			$deny_cid       = $r[0]['deny_cid'];
			$deny_gid       = $r[0]['deny_gid'];

			if($r[0]['item_flags'] & ITEM_WALL)
				$arr['item_flags'] = $arr['item_flags'] | ITEM_WALL; 


			// An uplinked comment might arrive with a downstream owner.
			// Fix it.

			if($r[0]['owner_xchan'] !== $arr['owner_xchan']) {
				$arr['owner_xchan'] = $r[0]['owner_xchan'];
				$uplinked_comment = true;
			}


			// if the parent is private, force privacy for the entire conversation
			// This differs from the above settings as it subtly allows comments from 
			// email correspondents to be private even if the overall thread is not. 

			if($r[0]['item_private'])
				$arr['item_private'] = $r[0]['item_private'];

			// Edge case. We host a public forum that was originally posted to privately.
			// The original author commented, but as this is a comment, the permissions
			// weren't fixed up so it will still show the comment as private unless we fix it here. 

			if((intval($r[0]['item_flags']) & ITEM_UPLINK) && (! $r[0]['item_private']))
				$arr['item_private'] = 0;
		}
		else {
			logger('item_store: item parent was not found - ignoring item');
			$ret['message'] = 'parent not found.';
			return $ret;
		}
	}

	if($parent_deleted)
		$arr['item_restrict'] = $arr['item_restrict'] | ITEM_DELETED;
	
	
	$r = q("SELECT `id` FROM `item` WHERE `mid` = '%s' AND `uid` = %d LIMIT 1",
		dbesc($arr['mid']),
		intval($arr['uid'])
	);
	if($r) {
		logger('item_store: duplicate item ignored. ' . print_r($arr,true));
		$ret['message'] = 'duplicate post.';
		return $ret;
	}

	call_hooks('post_remote',$arr);

	if(x($arr,'cancel')) {
		logger('item_store: post cancelled by plugin.');
		$ret['message'] = 'cancelled.';
		return $ret;
	}

	// pull out all the taxonomy stuff for separate storage

	$terms = null;
	if(array_key_exists('term',$arr)) {
		$terms = $arr['term'];
		unset($arr['term']);
	}

	logger('item_store: ' . print_r($arr,true), LOGGER_DATA);

	dbesc_array($arr);

	$r = dbq("INSERT INTO `item` (`" 
			. implode("`, `", array_keys($arr)) 
			. "`) VALUES ('" 
			. implode("', '", array_values($arr)) 
			. "')" );

	// find the item we just created

	$r = q("SELECT * FROM `item` WHERE `mid` = '%s' AND `uid` = %d ORDER BY `id` ASC ",
		$arr['mid'],           // already dbesc'd
		intval($arr['uid'])
	);


	if($r && count($r)) {
		$current_post = $r[0]['id'];
		$arr = $r[0];  // This will gives us a fresh copy of what's now in the DB and undo the db escaping, which really messes up the notifications
		logger('item_store: created item ' . $current_post, LOGGER_DEBUG);
	}
	else {
		logger('item_store: could not locate stored item');
		$ret['message'] = 'unable to retrieve.';
		return $ret;
	}
	if(count($r) > 1) {
		logger('item_store: duplicated post occurred. Removing duplicates.');
		q("DELETE FROM `item` WHERE `mid` = '%s' AND `uid` = %d AND `id` != %d ",
			$arr['mid'],
			intval($arr['uid']),
			intval($current_post)
		);
	}

	if((! $parent_id) || ($arr['parent_mid'] === $arr['mid']))	
		$parent_id = $current_post;

 	if(strlen($allow_cid) || strlen($allow_gid) || strlen($deny_cid) || strlen($deny_gid))
		$private = 1;
	else
		$private = $arr['item_private']; 

	// Set parent id - and also make sure to inherit the parent's ACL's.

	$r = q("UPDATE item SET parent = %d, allow_cid = '%s', allow_gid = '%s',
		deny_cid = '%s', deny_gid = '%s', item_private = %d WHERE id = %d LIMIT 1",
		intval($parent_id),
		dbesc($allow_cid),
		dbesc($allow_gid),
		dbesc($deny_cid),
		dbesc($deny_gid),
		intval($private),
		intval($current_post)
	);

	// These are probably redundant now that we've queried the just stored post
	$arr['id']        = $current_post;
	$arr['parent']    = $parent_id;
	$arr['allow_cid'] = $allow_cid;
	$arr['allow_gid'] = $allow_gid;
	$arr['deny_cid']  = $deny_cid;
	$arr['deny_gid']  = $deny_gid;
	$arr['item_private']   = $private;
	
	// Store taxonomy

	if(($terms) && (is_array($terms))) {
		foreach($terms as $t) {
			q("insert into term (uid,oid,otype,type,term,url)
				values(%d,%d,%d,%d,'%s','%s') ",
				intval($arr['uid']),
				intval($current_post),
				intval(TERM_OBJ_POST),
				intval($t['type']),
				dbesc($t['term']),
				dbesc($t['url'])
			);
		}

		$arr['term'] = $terms;
	}	

	call_hooks('post_remote_end',$arr);

	// update the commented timestamp on the parent

	$z = q("select max(created) as commented from item where parent_mid = '%s' and uid = %d ",
		dbesc($arr['parent_mid']),
		intval($arr['uid'])
	);

	q("UPDATE item set commented = '%s', changed = '%s' WHERE id = %d LIMIT 1",
		dbesc(($z) ? $z[0]['commented'] : (datetime_convert())),
		dbesc(datetime_convert()),
		intval($parent_id)
	);


	send_status_notifications($current_post,$arr);

	tag_deliver($arr['uid'],$current_post);
	$ret['success'] = true;
	$ret['item_id'] = $current_post;

	return $ret;
}



function item_store_update($arr,$allow_exec = false) {

	$ret = array('result' => false, 'item_id' => 0);
	if(! intval($arr['uid'])) {
		logger('item_store_update: no uid');
		$ret['message'] = 'no uid.';
		return $ret;
	}
	if(! intval($arr['id'])) {
		logger('item_store_update: no id');
		$ret['message'] = 'no id.';
		return $ret;
	}

	$orig_post_id = $arr['id'];
	$uid = $arr['uid'];

	$orig = q("select * from item where id = %d and uid = %d limit 1",
		intval($orig_post_id),
		intval($uid)
	);
	if(! $orig) {
		logger('item_store_update: original post not found: ' . $orig_post_id);
		$ret['message'] = 'no original';
		return $ret;
	}		

	// override the unseen flag with the original

	if($arr['item_flags'] & ITEM_UNSEEN)
		$arr['item_flags'] = $arr['item_flags'] ^ ITEM_UNSEEN;

	if($orig[0]['item_flags'] & ITEM_VERIFIED)
		$orig[0]['item_flags'] = $orig[0]['item_flags'] ^ ITEM_VERIFIED;

	$arr['item_flags'] = intval($arr['item_flags']) | $orig[0]['item_flags'];
	$arr['item_restrict'] = intval($arr['item_restrict']) | $orig[0]['item_restrict'];

	unset($arr['id']);
	unset($arr['uid']);

	if(array_key_exists('edit',$arr))
		unset($arr['edit']);	
	$arr['mimetype']      = ((x($arr,'mimetype'))      ? notags(trim($arr['mimetype']))      : 'text/bbcode');

	if(($arr['mimetype'] == 'application/x-php') && (! $allow_exec)) {
		logger('item_store: php mimetype but allow_exec is denied.');
		$ret['message'] = 'exec denied.';
		return $ret;
	}

    if(! ($arr['item_flags'] & ITEM_OBSCURED)) {

		$arr['lang'] = detect_language($arr['body']);
        // apply the input filter here - if it is obscured it has been filtered already
        $arr['body'] = z_input_filter($arr['uid'],$arr['body'],$arr['mimetype']);

        if(local_user() && (! $arr['sig'])) {
            $channel = get_app()->get_channel();
            if($channel['channel_hash'] === $arr['author_xchan']) {
                $arr['sig'] = base64url_encode(rsa_sign($arr['body'],$channel['channel_prvkey']));
                $arr['item_flags'] |= ITEM_VERIFIED;
            }
        }

		$allowed_languages = get_pconfig($arr['uid'],'system','allowed_languages');
	
		if((is_array($allowed_languages)) && ($arr['lang']) && (! array_key_exists($arr['lang'],$allowed_languages))) {
			$translate = array('item' => $arr, 'from' => $arr['lang'], 'to' => $allowed_languages, 'translated' => false);
			call_hooks('item_translate', $translate);
			if((! $translate['translated']) && (intval(get_pconfig($arr['uid'],'system','reject_disallowed_languages')))) {
				logger('item_store: language ' . $arr['lang'] . ' not accepted for uid ' . $arr['uid']);
				$ret['message'] = 'language not accepted';
				return $ret;
			}
			$arr = $translate['item'];
		}
		if($arr['item_private']) {
            $key = get_config('system','pubkey');
            $arr['item_flags'] = $arr['item_flags'] | ITEM_OBSCURED;
            if($arr['title'])
                $arr['title'] = json_encode(crypto_encapsulate($arr['title'],$key));
            if($arr['body'])
                $arr['body']  = json_encode(crypto_encapsulate($arr['body'],$key));
        }

	}


	if((x($arr,'object')) && is_array($arr['object'])) {
		activity_sanitise($arr['object']);
		$arr['object'] = json_encode($arr['object']);
	}

	if((x($arr,'target')) && is_array($arr['target'])) {
		activity_sanitise($arr['target']);
		$arr['target'] = json_encode($arr['target']);
	}

	if((x($arr,'attach')) && is_array($arr['attach'])) {
		activity_sanitise($arr['attach']);
		$arr['attach'] = json_encode($arr['attach']);
	}



	unset($arr['aid']);
	unset($arr['mid']);
	unset($arr['parent']);
	unset($arr['parent_mid']);
	unset($arr['created']);
	unset($arr['author_xchan']);
	unset($arr['owner_xchan']);
	unset($arr['thr_parent']);
	unset($arr['llink']);

	$arr['edited']        = ((x($arr,'edited')  !== false) ? datetime_convert('UTC','UTC',$arr['edited'])  : datetime_convert());
	$arr['expires']        = ((x($arr,'expires')  !== false) ? datetime_convert('UTC','UTC',$arr['expires'])  : $orig[0]['expires']);
	$arr['commented']     = $orig[0]['commented'];
	$arr['received']      = datetime_convert();
	$arr['changed']       = datetime_convert();
	$arr['title']         = ((x($arr,'title'))         ? notags(trim($arr['title']))         : '');
	$arr['location']      = ((x($arr,'location'))      ? notags(trim($arr['location']))      : $orig[0]['location']);
	$arr['coord']         = ((x($arr,'coord'))         ? notags(trim($arr['coord']))         : $orig[0]['coord']);
	$arr['verb']          = ((x($arr,'verb'))          ? notags(trim($arr['verb']))          : $orig[0]['verb']);
	$arr['obj_type']      = ((x($arr,'obj_type'))      ? notags(trim($arr['obj_type']))      : $orig[0]['obj_type']);
	$arr['object']        = ((x($arr,'object'))        ? trim($arr['object'])                : $orig[0]['object']);
	$arr['tgt_type']      = ((x($arr,'tgt_type'))      ? notags(trim($arr['tgt_type']))      : $orig[0]['tgt_type']);
	$arr['target']        = ((x($arr,'target'))        ? trim($arr['target'])                : $orig[0]['target']);
	$arr['plink']         = ((x($arr,'plink'))         ? notags(trim($arr['plink']))         : $orig[0]['plink']);
	$arr['allow_cid']     = ((x($arr,'allow_cid'))     ? trim($arr['allow_cid'])             : $orig[0]['allow_cid']);
	$arr['allow_gid']     = ((x($arr,'allow_gid'))     ? trim($arr['allow_gid'])             : $orig[0]['allow_gid']);
	$arr['deny_cid']      = ((x($arr,'deny_cid'))      ? trim($arr['deny_cid'])              : $orig[0]['deny_cid']);
	$arr['deny_gid']      = ((x($arr,'deny_gid'))      ? trim($arr['deny_gid'])              : $orig[0]['deny_gid']);
	$arr['item_private']  = ((x($arr,'item_private'))  ? intval($arr['item_private'])        : $orig[0]['item_private']);
	$arr['body']          = ((x($arr,'body'))          ? trim($arr['body'])                  : '');
	$arr['attach']        = ((x($arr,'attach'))        ? notags(trim($arr['attach']))        : $orig[0]['attach']);
	$arr['app']           = ((x($arr,'app'))           ? notags(trim($arr['app']))           : $orig[0]['app']);
//	$arr['item_restrict'] = ((x($arr,'item_restrict')) ? intval($arr['item_restrict'])       : $orig[0]['item_restrict'] );
//	$arr['item_flags']    = ((x($arr,'item_flags'))    ? intval($arr['item_flags'])          : $orig[0]['item_flags'] );
	
	$arr['sig']           = ((x($arr,'sig'))           ? $arr['sig']                         : '');
	$arr['layout_mid']     = ((x($arr,'layout_mid'))    ? dbesc($arr['layout_mid'])           : $orig[0]['layout_mid'] );

	call_hooks('post_remote_update',$arr);

	if(x($arr,'cancel')) {
		logger('item_store_update: post cancelled by plugin.');
		$ret['message'] = 'cancelled.';
		return $ret;
	}

	// pull out all the taxonomy stuff for separate storage

	$terms = null;
	if(array_key_exists('term',$arr)) {
		$terms = $arr['term'];
		unset($arr['term']);
	}

	dbesc_array($arr);

	logger('item_store_update: ' . print_r($arr,true), LOGGER_DATA);

	$str = '';
		foreach($arr as $k => $v) {
			if($str)
				$str .= ",";
			$str .= " `" . $k . "` = '" . $v . "' ";
		} 

	$r = dbq("update `item` set " . $str . " where id = " . $orig_post_id . " limit 1");

	if($r)
		logger('item_store_update: updated item ' . $orig_post_id, LOGGER_DEBUG);
	else {
		logger('item_store_update: could not update item');
		$ret['message'] = 'DB update failed.';
		return $ret;
	}

	$r = q("delete from term where oid = %d and otype = %d",
		intval($orig_post_id),
		intval(TERM_OBJ_POST)
	);

	if(($terms) && (is_array($terms))) {
		foreach($terms as $t) {
			q("insert into term (uid,oid,otype,type,term,url)
				values(%d,%d,%d,%d,'%s','%s') ",
				intval($uid),
				intval($orig_post_id),
				intval(TERM_OBJ_POST),
				intval($t['type']),
				dbesc($t['term']),
				dbesc($t['url'])
			);
		}

		$arr['term'] = $terms;
	}	

	call_hooks('post_remote_update_end',$arr);

	send_status_notifications($orig_post_id,$arr);

	tag_deliver($uid,$orig_post_id);
	$ret['success'] = true;
	$ret['item_id'] = $orig_post_id;

	return $ret;
}




function send_status_notifications($post_id,$item) {

	$notify = false;
	$parent = 0;

	$r = q("select channel_hash from channel where channel_id = %d limit 1",
		intval($item['uid'])
	);
	if(! $r)
		return;

	// my own post - no notification needed
	if($item['author_xchan'] === $r[0]['channel_hash'])
		return;

	// I'm the owner - notify me

	if($item['owner_hash'] === $r[0]['channel_hash'])
		$notify = true;

	// Was I involved in this conversation?

	$x = q("select * from item where parent_mid = '%s' and uid = %d",
		dbesc($item['parent_mid']),
		intval($item['uid'])
	);
	if($x) {
		foreach($x as $xx) {
			if($xx['author_xchan'] === $r[0]['channel_hash']) {
				$notify = true;
			}
			if($xx['id'] == $xx['parent']) {
				$parent = $xx['parent'];
			}
		}
	}

	if(! $notify)
		return;
	require_once('include/enotify.php');
	notification(array(
		'type'         => NOTIFY_COMMENT,
		'from_xchan'   => $item['author_xchan'],
		'to_xchan'     => $r[0]['channel_hash'],
		'item'         => $item,
		'link'		   => get_app()->get_baseurl() . '/display/' . $item['mid'],
		'verb'         => ACTIVITY_POST,
		'otype'        => 'item',
		'parent'       => $parent,
		'parent_mid'   => $item['parent_mid']
	));
	return;
}






function get_item_contact($item,$contacts) {
	if(! count($contacts) || (! is_array($item)))
		return false;
	foreach($contacts as $contact) {
		if($contact['id'] == $item['contact-id']) {
			return $contact;
			break; // NOTREACHED
		}
	}
	return false;
}


function tag_deliver($uid,$item_id) {

	// Called when we deliver things that might be tagged in ways that require delivery processing.
	// Handles community tagging of posts and also look for mention tags 
	// and sets up a second delivery chain if appropriate

	$a = get_app();

	$mention = false;

	$u = q("select * from channel where channel_id = %d limit 1",
		intval($uid)
	);
	if(! $u)
		return;
		
	$i = q("select * from item where id = %d and uid = %d limit 1",
		intval($item_id),
		intval($uid)
	);
	if(! $i)
		return;

	$i = fetch_post_tags($i);

	$item = $i[0];


	if($item['obj_type'] === ACTIVITY_OBJ_TAGTERM) {

		// We received a community tag activity for a post.
		// See if we are the owner of the parent item and have given permission to tag our posts.
		// If so tag the parent post.
 
		logger('tag_deliver: community tag activity received');

		if(($item['owner_xchan'] === $u[0]['channel_hash']) && (! get_pconfig($u[0]['channel_id'],'system','blocktags'))) {
			$j_tgt = json_decode_plus($item['target']);
			if($j_tgt && $j_tgt['id']) {
				$p = q("select * from item where mid = '%s' and uid = %d limit 1",
					dbesc($j_tgt['id']),
					intval($u[0]['channel_id'])
				);
				if($p) {
					$j_obj = json_decode_plus($item['object']);
					logger('tag_deliver: tag object: ' . print_r($j_obj,true), LOGGER_DATA);
					if($j_obj && $j_obj['id'] && $j_obj['title']) {
						if(is_array($j_obj['link']))
							$taglink = get_rel_link($j_obj['link'],'alternate');
						store_item_tag($u[0]['channel_id'],$p[0]['id'],TERM_OBJ_POST,TERM_HASHTAG,$j_obj['title'],$j_obj['id']);
						proc_run('php','include/notifier.php','edit_post',$p[0]['id']);
					}
				}
			}
		}
		else
			logger('tag_deliver: tag permission denied for ' . $u[0]['channel_address']);
	}


	$union = check_item_source($uid,$item);
	if($union)
		logger('check_item_source returns true');


	// This might be a followup by the original post author to a tagged forum
	// If so setup a second delivery chain

	$r = null;

	if( ! ($item['item_flags'] & ITEM_THREAD_TOP)) {
		$x = q("select * from item where id = parent and parent = %d and uid = %d limit 1",
			intval($item['parent']),
			intval($uid)
		);


		if(($x) && ($x[0]['item_flags'] & ITEM_UPLINK)) {

			logger('tag_deliver: creating second delivery chain for comment to tagged post.');

			// now change this copy of the post to a forum head message and deliver to all the tgroup members
			// also reset all the privacy bits to the forum default permissions

			$private = (($u[0]['allow_cid'] || $u[0]['allow_gid'] || $u[0]['deny_cid'] || $u[0]['deny_gid']) ? 1 : 0);

			$flag_bits = ITEM_WALL|ITEM_ORIGIN;

			// maintain the original source, which will be the original item owner and was stored in source_xchan
			// when we created the delivery fork

			$r = q("update item set source_xchan = '%s' where id = %d limit 1",
				dbesc($x[0]['source_xchan']),
				intval($item_id)
			); 

			$r = q("update item set item_flags = ( item_flags | %d ), owner_xchan = '%s', allow_cid = '%s', allow_gid = '%s', 
				deny_cid = '%s', deny_gid = '%s', item_private = %d  where id = %d limit 1",
				intval($flag_bits),
				dbesc($u[0]['channel_hash']),
				dbesc($u[0]['allow_cid']),
				dbesc($u[0]['allow_gid']),
				dbesc($u[0]['deny_cid']),
				dbesc($u[0]['deny_gid']),
				intval($private),
				intval($item_id)
			);
			if($r)
				proc_run('php','include/notifier.php','tgroup',$item_id);
			else
				logger('tag_deliver: failed to update item');			
		}
	}

	$terms = get_terms_oftype($item['term'],TERM_MENTION);

	if($terms)
		logger('tag_deliver: post mentions: ' . print_r($terms,true), LOGGER_DATA);

	$link = normalise_link($a->get_baseurl() . '/channel/' . $u[0]['channel_address']);

	if($terms) {
		foreach($terms as $term) {
			if(($term['term'] == $u[0]['channel_name']) && link_compare($term['url'],$link)) {			
				$mention = true;
				break;
			}
		}
	}				

	if($mention) {
		logger('tag_deliver: mention found for ' . $u[0]['channel_name']);
		
		$r = q("update item set item_flags = ( item_flags | %d ) where id = %d limit 1",
			intval(ITEM_MENTIONSME),
			intval($item_id)
		);			



		// At this point we've determined that the person receiving this post was mentioned in it or it is a union.
		// Now let's check if this mention was inside a reshare so we don't spam a forum
		// If it's private we may have to unobscure it momentarily so that we can parse it. 

		$body = '';

		if($item['item_flags'] & ITEM_OBSCURED) {
			$key = get_config('system','prvkey');
			if($item['body'])
				$body = crypto_unencapsulate(json_decode_plus($item['body']),$key);
		}
		else
			$body = $item['body'];		

		$body = preg_replace('/\[share(.*?)\[\/share\]/','',$body);

		$pattern = '/@\[zrl\=' . preg_quote($term['url'],'/') . '\]' . preg_quote($u[0]['channel_name'],'/') . '\[\/zrl\]/';

		if(! preg_match($pattern,$body,$matches)) {
			logger('tag_deliver: mention was in a reshare - ignoring');
			return;
		}
	

		// All good. 
		// Send a notification

		require_once('include/enotify.php');
		notification(array(
			'to_xchan'     => $u[0]['channel_hash'],
			'from_xchan'   => $item['author_xchan'],
			'type'         => NOTIFY_TAGSELF,
			'item'         => $item,
			'link'         => $i[0]['llink'],
			'verb'         => ACTIVITY_TAG,
			'otype'        => 'item'
		));


		if(! perm_is_allowed($uid,$item['author_xchan'],'tag_deliver')) {
			logger('tag_delivery denied for uid ' . $uid . ' and xchan ' . $item['author_xchan']);
			return;
		}

	}

	if((! $mention) && (! $union))
		return;


	// tgroup delivery - setup a second delivery chain
	// prevent delivery looping - only proceed
	// if the message originated elsewhere and is a top-level post

	if(($item['item_flags'] & ITEM_WALL) || ($item['item_flags'] & ITEM_ORIGIN) || (!($item['item_flags'] & ITEM_THREAD_TOP)) || ($item['id'] != $item['parent'])) {
		logger('tag_deliver: item was local or a comment. rejected.');
		return;
	}

	logger('tag_deliver: creating second delivery chain.');

	// now change this copy of the post to a forum head message and deliver to all the tgroup members
	// also reset all the privacy bits to the forum default permissions

	$private = (($u[0]['allow_cid'] || $u[0]['allow_gid'] || $u[0]['deny_cid'] || $u[0]['deny_gid']) ? 1 : 0);

	$flag_bits = ITEM_WALL|ITEM_ORIGIN|ITEM_UPLINK;

	// preserve the source

	$r = q("update item set source_xchan = owner_xchan where id = %d limit 1",
		intval($item_id)
	);

	$r = q("update item set item_flags = ( item_flags | %d ), owner_xchan = '%s', allow_cid = '%s', allow_gid = '%s', 
		deny_cid = '%s', deny_gid = '%s', item_private = %d  where id = %d limit 1",
		intval($flag_bits),
		dbesc($u[0]['channel_hash']),
		dbesc($u[0]['allow_cid']),
		dbesc($u[0]['allow_gid']),
		dbesc($u[0]['deny_cid']),
		dbesc($u[0]['deny_gid']),
		intval($private),
		intval($item_id)
	);
	if($r)
		proc_run('php','include/notifier.php','tgroup',$item_id);
	else
		logger('tag_deliver: failed to update item');			

}



function tgroup_check($uid,$item) {

	$a = get_app();

	$mention = false;

	// check that the message originated elsewhere and is a top-level post
	// or is a followup and we have already accepted the top level post

	if($item['mid'] != $item['parent_mid']) {
		$r = q("select id from item where mid = '%s' and uid = %d limit 1",
			dbesc($item['parent_mid']),
			intval($uid)
		);
		if($r)
			return true;
		return false;
	}
	if(! perm_is_allowed($uid,$item['author_xchan'],'tag_deliver'))
		return false;

	$u = q("select * from channel where channel_id = %d limit 1",
		intval($uid)
	);

	if(! $u)
		return false;

	$terms = get_terms_oftype($item['term'],TERM_MENTION);

	if($terms)
		logger('tgroup_check: post mentions: ' . print_r($terms,true), LOGGER_DATA);

	$link = normalise_link($a->get_baseurl() . '/channel/' . $u[0]['channel_address']);

	if($terms) {
		foreach($terms as $term) {
			if(($term['term'] == $u[0]['channel_name']) && link_compare($term['url'],$link)) {			
				$mention = true;
				break;
			}
		}
	}				

	if($mention) {
		logger('tgroup_check: mention found for ' . $u[0]['channel_name']);
	}
	else
		return false;

	// At this point we've determined that the person receiving this post was mentioned in it.
	// Now let's check if this mention was inside a reshare so we don't spam a forum

	$body = preg_replace('/\[share(.*?)\[\/share\]/','',$item['body']);

	$pattern = '/@\[zrl\=' . preg_quote($term['url'],'/') . '\]' . preg_quote($u[0]['channel_name'],'/') . '\[\/zrl\]/';

	if(! preg_match($pattern,$body,$matches)) {
		logger('tgroup_check: mention was in a reshare - ignoring');
		return false;
	}


	return true;

}


/**
 * @function check_item_source($uid,$item)
 * @param $uid
 * @param $item
 *
 * @description
 * Checks to see if this item owner is referenced as a source for this channel and if the post 
 * matches the rules for inclusion in this channel. Returns true if we should create a second delivery
 * chain and false if none of the rules apply, or if the item is private.
 */
 

function check_item_source($uid,$item) {

	if($item['item_private'])
		return false;
	

	$r = q("select * from source where src_channel_id = %d and src_xchan = '%s' limit 1",
		intval($uid),
		dbesc($item['owner_xchan'])
	);

	if(! $r)
		return false;

	$x = q("select abook_their_perms from abook where abook_channel = %d and abook_xchan = '%s' limit 1",
		intval($uid),
		dbesc($item['owner_xchan'])
	);
			
	if(! $x)
		return false;

	if(! ($x[0]['abook_their_perms'] & PERMS_A_REPUBLISH))
		return false;

	if($r[0]['src_channel_xchan'] === $item['owner_xchan'])
		return false;

	if(! $r[0]['src_patt'])
		return true;

	require_once('include/html2plain.php');
	$text = prepare_text($item['body'],$item['mimetype']);
	$text = html2plain($text);

	$tags = ((count($items['term'])) ? $items['term'] : false);

	$words = explode("\n",$r[0]['src_patt']);
	if($words) {
		foreach($words as $word) {
			if(substr($word,0,1) === '#' && $tags) {
				foreach($tags as $t)
					if($t['type'] == TERM_HASHTAG && substr($t,1) === $word)
						return true;
			}
			if(stristr($text,$word) !== false)
				return true;
		}
	}
	return false;
}




function mail_store($arr) {

	if(! $arr['channel_id']) {
		logger('mail_store: no uid');
		return 0;
	}

	if((strpos($arr['body'],'<') !== false) || (strpos($arr['body'],'>') !== false)) 
		$arr['body'] = escape_tags($arr['body']);

	if(array_key_exists('attach',$arr) && is_array($arr['attach']))
		$arr['attach'] = json_encode($arr['attach']);

	$arr['account_id']    = ((x($arr,'account_id'))           ? intval($arr['account_id'])                 : 0);
	$arr['mid']           = ((x($arr,'mid'))           ? notags(trim($arr['mid']))           : random_string());
	$arr['from_xchan']    = ((x($arr,'from_xchan'))  ? notags(trim($arr['from_xchan']))  : '');
	$arr['to_xchan']   = ((x($arr,'to_xchan'))   ? notags(trim($arr['to_xchan']))   : '');
	$arr['created']       = ((x($arr,'created') !== false) ? datetime_convert('UTC','UTC',$arr['created']) : datetime_convert());
	$arr['expires']       = ((x($arr,'expires') !== false) ? datetime_convert('UTC','UTC',$arr['expires']) : '0000-00-00 00:00:00');
	$arr['title']         = ((x($arr,'title'))         ? notags(trim($arr['title']))         : '');
	$arr['parent_mid']    = ((x($arr,'parent_mid'))    ? notags(trim($arr['parent_mid']))    : '');
	$arr['body']          = ((x($arr,'body'))          ? trim($arr['body'])                  : '');

	$arr['mail_flags']    = ((x($arr,'mail_flags'))    ? intval($arr['mail_flags'])          : 0 );
	

	if(! $arr['parent_mid']) {
		logger('mail_store: missing parent');
		$arr['parent_mid'] = $arr['mid'];
	}

	$r = q("SELECT `id` FROM mail WHERE `mid` = '%s' AND channel_id = %d LIMIT 1",
		dbesc($arr['mid']),
		intval($arr['channel_id'])
	);
	if($r) {
		logger('mail_store: duplicate item ignored. ' . print_r($arr,true));
		return 0;
	}

	call_hooks('post_mail',$arr);

	if(x($arr,'cancel')) {
		logger('mail_store: post cancelled by plugin.');
		return 0;
	}

	dbesc_array($arr);

	logger('mail_store: ' . print_r($arr,true), LOGGER_DATA);

	$r = dbq("INSERT INTO mail (`" 
			. implode("`, `", array_keys($arr)) 
			. "`) VALUES ('" 
			. implode("', '", array_values($arr)) 
			. "')" );

	// find the item we just created

	$r = q("SELECT `id` FROM mail WHERE `mid` = '%s' AND `channel_id` = %d ORDER BY `id` ASC ",
		$arr['mid'],           // already dbesc'd
		intval($arr['channel_id'])
	);

	if($r) {
		$current_post = $r[0]['id'];
		logger('mail_store: created item ' . $current_post, LOGGER_DEBUG);
		$arr['id'] = $current_post; // for notification
	}
	else {
		logger('mail_store: could not locate created item');
		return 0;
	}
	if(count($r) > 1) {
		logger('mail_store: duplicated post occurred. Removing duplicates.');
		q("DELETE FROM mail WHERE `mid` = '%s' AND `channel_id` = %d AND `id` != %d ",
			$arr['mid'],
			intval($arr['channel_id']),
			intval($current_post)
		);
	}
	else {
		require_once('include/enotify.php');

		$notif_params = array(
			'from_xchan' => $arr['from_xchan'],
			'to_xchan'   => $arr['to_xchan'],
			'type'       => NOTIFY_MAIL,
			'item'       => $arr,
			'verb'       => ACTIVITY_POST,
		    'otype'      => 'mail'
		);
			
		notification($notif_params);
	}

	call_hooks('post_mail_end',$arr);
	return $current_post;
}






function dfrn_deliver($owner,$contact,$atom, $dissolve = false) {

	$a = get_app();

	$idtosend = $orig_id = (($contact['dfrn_id']) ? $contact['dfrn_id'] : $contact['issued_id']);

	if($contact['duplex'] && $contact['dfrn_id'])
		$idtosend = '0:' . $orig_id;
	if($contact['duplex'] && $contact['issued_id'])
		$idtosend = '1:' . $orig_id;		

	$rino = ((function_exists('mcrypt_encrypt')) ? 1 : 0);

	$rino_enable = get_config('system','rino_encrypt');

	if(! $rino_enable)
		$rino = 0;

//	$ssl_val = intval(get_config('system','ssl_policy'));
//	$ssl_policy = '';

//	switch($ssl_val){
//		case SSL_POLICY_FULL:
//			$ssl_policy = 'full';
//			break;
//		case SSL_POLICY_SELFSIGN:
//			$ssl_policy = 'self';
//			break;			
//		case SSL_POLICY_NONE:
//		default:
//			$ssl_policy = 'none';
//			break;
//	}

	$url = $contact['notify'] . '&dfrn_id=' . $idtosend . '&dfrn_version=' . DFRN_PROTOCOL_VERSION . (($rino) ? '&rino=1' : '');

	logger('dfrn_deliver: ' . $url);

	$xml = fetch_url($url);

	$curl_stat = $a->get_curl_code();
	if(! $curl_stat)
		return(-1); // timed out

	logger('dfrn_deliver: ' . $xml, LOGGER_DATA);

	if(! $xml)
		return 3;

	if(strpos($xml,'<?xml') === false) {
		logger('dfrn_deliver: no valid XML returned');
		logger('dfrn_deliver: returned XML: ' . $xml, LOGGER_DATA);
		return 3;
	}

	$res = parse_xml_string($xml);

	if((intval($res->status) != 0) || (! strlen($res->challenge)) || (! strlen($res->dfrn_id)))
		return (($res->status) ? $res->status : 3);

	$postvars     = array();
	$sent_dfrn_id = hex2bin((string) $res->dfrn_id);
	$challenge    = hex2bin((string) $res->challenge);
	$perm         = (($res->perm) ? $res->perm : null);
	$dfrn_version = (float) (($res->dfrn_version) ? $res->dfrn_version : 2.0);
	$rino_allowed = ((intval($res->rino) === 1) ? 1 : 0);
	$page         = (($owner['page-flags'] == PAGE_COMMUNITY) ? 1 : 0);

	if($owner['page-flags'] == PAGE_PRVGROUP)
		$page = 2;

	$final_dfrn_id = '';

	if($perm) {
		if((($perm == 'rw') && (! intval($contact['writable']))) 
		|| (($perm == 'r') && (intval($contact['writable'])))) {
			q("update contact set writable = %d where id = %d limit 1",
				intval(($perm == 'rw') ? 1 : 0),
				intval($contact['id'])
			);
			$contact['writable'] = (string) 1 - intval($contact['writable']);			
		}
	}

	if(($contact['duplex'] && strlen($contact['pubkey'])) 
		|| ($owner['page-flags'] == PAGE_COMMUNITY && strlen($contact['pubkey']))
		|| ($contact['rel'] == CONTACT_IS_SHARING && strlen($contact['pubkey']))) {
		openssl_public_decrypt($sent_dfrn_id,$final_dfrn_id,$contact['pubkey']);
		openssl_public_decrypt($challenge,$postvars['challenge'],$contact['pubkey']);
	}
	else {
		openssl_private_decrypt($sent_dfrn_id,$final_dfrn_id,$contact['prvkey']);
		openssl_private_decrypt($challenge,$postvars['challenge'],$contact['prvkey']);
	}

	$final_dfrn_id = substr($final_dfrn_id, 0, strpos($final_dfrn_id, '.'));

	if(strpos($final_dfrn_id,':') == 1)
		$final_dfrn_id = substr($final_dfrn_id,2);

	if($final_dfrn_id != $orig_id) {
		logger('dfrn_deliver: wrong dfrn_id.');
		// did not decode properly - cannot trust this site 
		return 3;
	}

	$postvars['dfrn_id']      = $idtosend;
	$postvars['dfrn_version'] = DFRN_PROTOCOL_VERSION;
	if($dissolve)
		$postvars['dissolve'] = '1';


	if((($contact['rel']) && ($contact['rel'] != CONTACT_IS_SHARING) && (! $contact['blocked'])) || ($owner['page-flags'] == PAGE_COMMUNITY)) {
		$postvars['data'] = $atom;
		$postvars['perm'] = 'rw';
	}
	else {
		$postvars['data'] = str_replace('<dfrn:comment-allow>1','<dfrn:comment-allow>0',$atom);
		$postvars['perm'] = 'r';
	}

//	$postvars['ssl_policy'] = $ssl_policy;

	if($page)
		$postvars['page'] = $page;
	
	if($rino && $rino_allowed && (! $dissolve)) {
		$key = substr(random_string(),0,16);
		$data = bin2hex(aes_encrypt($postvars['data'],$key));
		$postvars['data'] = $data;
		logger('rino: sent key = ' . $key, LOGGER_DEBUG);	


		if($dfrn_version >= 2.1) {	
			if(($contact['duplex'] && strlen($contact['pubkey'])) 
				|| ($owner['page-flags'] == PAGE_COMMUNITY && strlen($contact['pubkey']))
				|| ($contact['rel'] == CONTACT_IS_SHARING && strlen($contact['pubkey']))) {

				openssl_public_encrypt($key,$postvars['key'],$contact['pubkey']);
			}
			else {
				openssl_private_encrypt($key,$postvars['key'],$contact['prvkey']);
			}
		}
		else {
			if(($contact['duplex'] && strlen($contact['prvkey'])) || ($owner['page-flags'] == PAGE_COMMUNITY)) {
				openssl_private_encrypt($key,$postvars['key'],$contact['prvkey']);
			}
			else {
				openssl_public_encrypt($key,$postvars['key'],$contact['pubkey']);
			}
		}

		logger('md5 rawkey ' . md5($postvars['key']));

		$postvars['key'] = bin2hex($postvars['key']);
	}

	logger('dfrn_deliver: ' . "SENDING: " . print_r($postvars,true), LOGGER_DATA);

	$xml = post_url($contact['notify'],$postvars);

	logger('dfrn_deliver: ' . "RECEIVED: " . $xml, LOGGER_DATA);

	$curl_stat = $a->get_curl_code();
	if((! $curl_stat) || (! strlen($xml)))
		return(-1); // timed out

	if(($curl_stat == 503) && (stristr($a->get_curl_headers(),'retry-after')))
		return(-1);

	if(strpos($xml,'<?xml') === false) {
		logger('dfrn_deliver: phase 2: no valid XML returned');
		logger('dfrn_deliver: phase 2: returned XML: ' . $xml, LOGGER_DATA);
		return 3;
	}

	if($contact['term_date'] != '0000-00-00 00:00:00') {
		logger("dfrn_deliver: $url back from the dead - removing mark for death");
		require_once('include/Contact.php');
		unmark_for_death($contact);
	}

	$res = parse_xml_string($xml);

	return $res->status; 
}


/**
 *
 * consume_feed - process atom feed and update anything/everything we might need to update
 *
 * $xml = the (atom) feed to consume - RSS isn't as fully supported but may work for simple feeds.
 *
 * $importer = the contact_record (joined to user_record) of the local user who owns this relationship.
 *             It is this person's stuff that is going to be updated.
 * $contact =  the person who is sending us stuff. If not set, we MAY be processing a "follow" activity
 *             from an external network and MAY create an appropriate contact record. Otherwise, we MUST 
 *             have a contact record.
 * $hub = should we find a hub declation in the feed, pass it back to our calling process, who might (or 
 *        might not) try and subscribe to it.
 * $datedir sorts in reverse order
 * $pass - by default ($pass = 0) we cannot guarantee that a parent item has been 
 *      imported prior to its children being seen in the stream unless we are certain
 *      of how the feed is arranged/ordered.
 * With $pass = 1, we only pull parent items out of the stream.
 * With $pass = 2, we only pull children (comments/likes).
 *
 * So running this twice, first with pass 1 and then with pass 2 will do the right
 * thing regardless of feed ordering. This won't be adequate in a fully-threaded
 * model where comments can have sub-threads. That would require some massive sorting
 * to get all the feed items into a mostly linear ordering, and might still require
 * recursion.  
 */

function consume_feed($xml,$importer,&$contact, &$hub, $datedir = 0, $pass = 0) {

	require_once('library/simplepie/simplepie.inc');

	if(! strlen($xml)) {
		logger('consume_feed: empty input');
		return;
	}

	// Want to see this work as a content source for the matrix? 
	// Read this: https://github.com/friendica/red/wiki/Service_Federation
		
	$feed = new SimplePie();
	$feed->set_raw_data($xml);
	if($datedir)
		$feed->enable_order_by_date(true);
	else
		$feed->enable_order_by_date(false);
	$feed->init();

	if($feed->error())
		logger('consume_feed: Error parsing XML: ' . $feed->error());

	$permalink = $feed->get_permalink();

	// Check at the feed level for updated contact name and/or photo


	// process any deleted entries

	$del_entries = $feed->get_feed_tags(NAMESPACE_TOMB, 'deleted-entry');
	if(is_array($del_entries) && count($del_entries) && $pass != 2) {
		foreach($del_entries as $dentry) {
			$deleted = false;
			if(isset($dentry['attribs']['']['ref'])) {
				$mid = $dentry['attribs']['']['ref'];
				$deleted = true;
				if(isset($dentry['attribs']['']['when'])) {
					$when = $dentry['attribs']['']['when'];
					$when = datetime_convert('UTC','UTC', $when, 'Y-m-d H:i:s');
				}
				else
					$when = datetime_convert('UTC','UTC','now','Y-m-d H:i:s');
			}
			if($deleted && is_array($contact)) {
/*				$r = q("SELECT `item`.*, `contact`.`self` FROM `item` left join `contact` on `item`.`contact-id` = `contact`.`id` 
					WHERE `mid` = '%s' AND `item`.`uid` = %d AND `contact-id` = %d AND NOT `item`.`file` LIKE '%%[%%' LIMIT 1",
					dbesc($mid),
					intval($importer['channel_id']),
					intval($contact['id'])
				);
*/
				if(count($r)) {
					$item = $r[0];

					if(! $item['deleted'])
						logger('consume_feed: deleting item ' . $item['id'] . ' mid=' . $item['mid'], LOGGER_DEBUG);

					if($item['mid'] == $item['parent_mid']) {
						$r = q("UPDATE `item` SET item_restrict = (item_restrict | %d), `edited` = '%s', `changed` = '%s',
							`body` = '', `title` = ''
							WHERE `parent_mid` = '%s' AND `uid` = %d",
							intval(ITEM_DELETED),
							dbesc($when),
							dbesc(datetime_convert()),
							dbesc($item['mid']),
							intval($importer['channel_id'])
						);
					}
					else {
						$r = q("UPDATE `item` SET item_restrict = ( item_restrict | %d ), `edited` = '%s', `changed` = '%s',
							`body` = '', `title` = '' 
							WHERE `mid` = '%s' AND `uid` = %d LIMIT 1",
							intval(ITEM_DELETED),
							dbesc($when),
							dbesc(datetime_convert()),
							dbesc($mid),
							intval($importer['channel_id'])
						);
					}
				}	
			}
		}
	}

	// Now process the feed

	if($feed->get_item_quantity()) {

		logger('consume_feed: feed item count = ' . $feed->get_item_quantity());

        // in inverse date order
		if ($datedir)
			$items = array_reverse($feed->get_items());
		else
			$items = $feed->get_items();


		foreach($items as $item) {

			$is_reply = false;
			$item_id = $item->get_id();

logger('consume_feed: processing ' . $item_id);

			$rawthread = $item->get_item_tags( NAMESPACE_THREAD,'in-reply-to');
			if(isset($rawthread[0]['attribs']['']['ref'])) {
				$is_reply = true;
				$parent_mid = $rawthread[0]['attribs']['']['ref'];
			}

			if($is_reply) {

				if($pass == 1)
					continue;


				// Have we seen it? If not, import it.

				$item_id  = $item->get_id();
				$datarray = get_atom_elements($feed,$item);

				if((! x($datarray,'author-name')) && ($contact['network'] != NETWORK_DFRN))
					$datarray['author-name'] = $contact['name'];
				if((! x($datarray,'author-link')) && ($contact['network'] != NETWORK_DFRN))
					$datarray['author-link'] = $contact['url'];
				if((! x($datarray,'author-avatar')) && ($contact['network'] != NETWORK_DFRN))
					$datarray['author-avatar'] = $contact['thumb'];

				if((! x($datarray,'author-name')) || (! x($datarray,'author-link'))) {
					logger('consume_feed: no author information! ' . print_r($datarray,true));
					continue;
				}


				$r = q("SELECT `uid`, `edited`, `body` FROM `item` WHERE `mid` = '%s' AND `uid` = %d LIMIT 1",
					dbesc($item_id),
					intval($importer['channel_id'])
				);

				// Update content if 'updated' changes

				if($r) {
					if((x($datarray,'edited') !== false) && (datetime_convert('UTC','UTC',$datarray['edited']) !== $r[0]['edited'])) {  

						// do not accept (ignore) an earlier edit than one we currently have.
						if(datetime_convert('UTC','UTC',$datarray['edited']) < $r[0]['edited'])
							continue;

						$r = q("UPDATE `item` SET `title` = '%s', `body` = '%s', `edited` = '%s' WHERE `mid` = '%s' AND `uid` = %d LIMIT 1",
							dbesc($datarray['title']),
							dbesc($datarray['body']),
							dbesc(datetime_convert('UTC','UTC',$datarray['edited'])),
							dbesc($item_id),
							intval($importer['channel_id'])
						);
					}

					continue;
				}


				$datarray['parent_mid'] = $parent_mid;
				$datarray['uid'] = $importer['channel_id'];
				$datarray['contact-id'] = $contact['id'];
				if((activity_match($datarray['verb'],ACTIVITY_LIKE)) || (activity_match($datarray['verb'],ACTIVITY_DISLIKE))) {
					$datarray['type'] = 'activity';
					$datarray['gravity'] = GRAVITY_LIKE;
					// only one like or dislike per person
					$r = q("select id from item where uid = %d and `contact-id` = %d and verb ='%s' and deleted = 0 and (`parent_mid` = '%s' OR `thr_parent` = '%s') limit 1",
						intval($datarray['uid']),
						intval($datarray['contact-id']),
						dbesc($datarray['verb']),
						dbesc($parent_mid),
						dbesc($parent_mid)
					);
					if($r && count($r))
						continue; 
				}

				if(($datarray['verb'] === ACTIVITY_TAG) && ($datarray['obj_type'] === ACTIVITY_OBJ_TAGTERM)) {
					$xo = parse_xml_string($datarray['object'],false);
					$xt = parse_xml_string($datarray['target'],false);

					if($xt->type == ACTIVITY_OBJ_NOTE) {
						$r = q("select * from item where `mid` = '%s' AND `uid` = %d limit 1",
							dbesc($xt->id),
							intval($importer['channel_id'])
						);
						if(! count($r))
							continue;

						// extract tag, if not duplicate, add to parent item
						if($xo->id && $xo->content) {
							$newtag = '#[zrl=' . $xo->id . ']'. $xo->content . '[/zrl]';
							if(! (stristr($r[0]['tag'],$newtag))) {
								q("UPDATE item SET tag = '%s' WHERE id = %d LIMIT 1",
									dbesc($r[0]['tag'] . (strlen($r[0]['tag']) ? ',' : '') . $newtag),
									intval($r[0]['id'])
								);
							}
						}
					}
				}

logger('consume_feed: ' . print_r($datarray,true));

//				$xx = item_store($datarray);
				$r = $xx['item_id'];
				continue;
			}

			else {

				// Head post of a conversation. Have we seen it? If not, import it.

				$item_id  = $item->get_id();

				$datarray = get_atom_elements($feed,$item);

				if(is_array($contact)) {
					if((! x($datarray,'author-name')) && ($contact['network'] != NETWORK_DFRN))
						$datarray['author-name'] = $contact['name'];
					if((! x($datarray,'author-link')) && ($contact['network'] != NETWORK_DFRN))
						$datarray['author-link'] = $contact['url'];
					if((! x($datarray,'author-avatar')) && ($contact['network'] != NETWORK_DFRN))
						$datarray['author-avatar'] = $contact['thumb'];
				}

				if((! x($datarray,'author-name')) || (! x($datarray,'author-link'))) {
					logger('consume_feed: no author information! ' . print_r($datarray,true));
					continue;
				}

				// special handling for events

				if((x($datarray,'obj_type')) && ($datarray['obj_type'] === ACTIVITY_OBJ_EVENT)) {
					$ev = bbtoevent($datarray['body']);
					if(x($ev,'desc') && x($ev,'start')) {
						$ev['uid'] = $importer['channel_id'];
						$ev['mid'] = $item_id;
						$ev['edited'] = $datarray['edited'];
						$ev['private'] = $datarray['private'];

						if(is_array($contact))
							$ev['cid'] = $contact['id'];
						$r = q("SELECT * FROM `event` WHERE `mid` = '%s' AND `uid` = %d LIMIT 1",
							dbesc($item_id),
							intval($importer['channel_id'])
						);
						if(count($r))
							$ev['id'] = $r[0]['id'];
//						$xyz = event_store($ev);
						continue;
					}
				}

				$r = q("SELECT `uid`, `edited`, `body` FROM `item` WHERE `mid` = '%s' AND `uid` = %d LIMIT 1",
					dbesc($item_id),
					intval($importer['channel_id'])
				);

				// Update content if 'updated' changes

				if($r) {
					if((x($datarray,'edited') !== false) && (datetime_convert('UTC','UTC',$datarray['edited']) !== $r[0]['edited'])) {  

						// do not accept (ignore) an earlier edit than one we currently have.
						if(datetime_convert('UTC','UTC',$datarray['edited']) < $r[0]['edited'])
							continue;

						$r = q("UPDATE `item` SET `title` = '%s', `body` = '%s', `edited` = '%s' WHERE `mid` = '%s' AND `uid` = %d LIMIT 1",
							dbesc($datarray['title']),
							dbesc($datarray['body']),
							dbesc(datetime_convert('UTC','UTC',$datarray['edited'])),
							dbesc($item_id),
							intval($importer['channel_id'])
						);
					}

					continue;
				}

				if(activity_match($datarray['verb'],ACTIVITY_FOLLOW)) {
					logger('consume-feed: New follower');
					new_follower($importer,$contact,$datarray,$item);
					return;
				}
				if(activity_match($datarray['verb'],ACTIVITY_UNFOLLOW))  {
					lose_follower($importer,$contact,$datarray,$item);
					return;
				}

				if(activity_match($datarray['verb'],ACTIVITY_REQ_FRIEND)) {
					logger('consume-feed: New friend request');
					new_follower($importer,$contact,$datarray,$item,true);
					return;
				}
				if(activity_match($datarray['verb'],ACTIVITY_UNFRIEND))  {
					lose_sharer($importer,$contact,$datarray,$item);
					return;
				}


//				if(! is_array($contact))
//					return;


				// This is my contact on another system, but it's really me.
				// Turn this into a wall post.

				if($contact['remote_self']) {
					$datarray['wall'] = 1;
				}

				$datarray['parent_mid'] = $item_id;
				$datarray['uid'] = $importer['channel_id'];
				$datarray['contact-id'] = $contact['id'];

				if(! link_compare($datarray['owner-link'],$contact['url'])) {
					// The item owner info is not our contact. It's OK and is to be expected if this is a tgroup delivery, 
					// but otherwise there's a possible data mixup on the sender's system.
					// the tgroup delivery code called from item_store will correct it if it's a forum,
					// but we're going to unconditionally correct it here so that the post will always be owned by our contact. 
					logger('consume_feed: Correcting item owner.', LOGGER_DEBUG);
					$datarray['owner-name']   = $contact['name'];
					$datarray['owner-link']   = $contact['url'];
					$datarray['owner-avatar'] = $contact['thumb'];
				}

				// We've allowed "followers" to reach this point so we can decide if they are 
				// posting an @-tag delivery, which followers are allowed to do for certain
				// page types. Now that we've parsed the post, let's check if it is legit. Otherwise ignore it. 

				if(($contact['rel'] == CONTACT_IS_FOLLOWER) && (! tgroup_check($importer['channel_id'],$datarray)))
					continue;

logger('consume_feed: ' . print_r($datarray,true));

//				$xx = item_store($datarray);
				$r = $xx['item_id'];
				continue;

			}
		}
	}


}



function atom_author($tag,$name,$uri,$h,$w,$type,$photo) {
	$o = '';
	if(! $tag)
		return $o;
	$name = xmlify($name);
	$uri = xmlify($uri);
	$h = intval($h);
	$w = intval($w);
	$photo = xmlify($photo);


	$o .= "<$tag>\r\n";
	$o .= "<name>$name</name>\r\n";
	$o .= "<uri>$uri</uri>\r\n";
	$o .= '<link rel="photo"  type="' . $type . '" media:width="' . $w . '" media:height="' . $h . '" href="' . $photo . '" />' . "\r\n";
	$o .= '<link rel="avatar" type="' . $type . '" media:width="' . $w . '" media:height="' . $h . '" href="' . $photo . '" />' . "\r\n";

	call_hooks('atom_author', $o);

	$o .= "</$tag>\r\n";
	return $o;
}

function atom_entry($item,$type,$author,$owner,$comment = false,$cid = 0) {

	$a = get_app();

	if(! $item['parent'])
		return;

	if($item['deleted'])
		return '<at:deleted-entry ref="' . xmlify($item['mid']) . '" when="' . xmlify(datetime_convert('UTC','UTC',$item['edited'] . '+00:00',ATOM_TIME)) . '" />' . "\r\n";


	if($item['allow_cid'] || $item['allow_gid'] || $item['deny_cid'] || $item['deny_gid'])
		$body = fix_private_photos($item['body'],$owner['uid'],$item,$cid);
	else
		$body = $item['body'];


	$o = "\r\n\r\n<entry>\r\n";

	if(is_array($author))
		$o .= atom_author('author',$author['xchan_name'],$author['xchan_url'],80,80,$author['xchan_photo_mimetype'],$author['xchan_photo_m']);
	else
		$o .= atom_author('author',$item['author']['xchan_name'],$item['author']['xchan_url'],80,80,$item['author']['xchan_photo_mimetype'], $item['author']['xchan_photo_m']);

	$o .= atom_author('zot:owner',$item['owner']['xchan_name'],$item['owner']['xchan_url'],80,80,$item['owner']['xchan_photo_mimetype'],$item['owner']['xchan_photo_m']);

	if(($item['parent'] != $item['id']) || ($item['parent_mid'] !== $item['mid']) || (($item['thr_parent'] !== '') && ($item['thr_parent'] !== $item['mid']))) {
		$parent_item = (($item['thr_parent']) ? $item['thr_parent'] : $item['parent_mid']);
		$o .= '<thr:in-reply-to ref="' . xmlify($parent_item) . '" type="text/html" href="' .  xmlify($item['plink']) . '" />' . "\r\n";
	}

	$o .= '<id>' . xmlify($item['mid']) . '</id>' . "\r\n";
	$o .= '<title>' . xmlify($item['title']) . '</title>' . "\r\n";
	$o .= '<published>' . xmlify(datetime_convert('UTC','UTC',$item['created'] . '+00:00',ATOM_TIME)) . '</published>' . "\r\n";
	$o .= '<updated>' . xmlify(datetime_convert('UTC','UTC',$item['edited'] . '+00:00',ATOM_TIME)) . '</updated>' . "\r\n";

	$o .= '<content type="' . $type . '" >' . xmlify(prepare_text($body,$item['mimetype'])) . '</content>' . "\r\n";
	$o .= '<link rel="alternate" type="text/html" href="' . xmlify($item['plink']) . '" />' . "\r\n";

	if($item['location']) {
		$o .= '<zot:location>' . xmlify($item['location']) . '</zot:location>' . "\r\n";
		$o .= '<poco:address><poco:formatted>' . xmlify($item['location']) . '</poco:formatted></poco:address>' . "\r\n";
	}

	if($item['coord'])
		$o .= '<georss:point>' . xmlify($item['coord']) . '</georss:point>' . "\r\n";

	if(($item['item_private']) || strlen($item['allow_cid']) || strlen($item['allow_gid']) || strlen($item['deny_cid']) || strlen($item['deny_gid']))
		$o .= '<zot:private>' . (($item['item_private']) ? $item['item_private'] : 1) . '</zot:private>' . "\r\n";


	if($item['app'])
		$o .= '<statusnet:notice_info local_id="' . $item['id'] . '" source="' . xmlify($item['app']) . '" ></statusnet:notice_info>' . "\r\n";


	$verb = construct_verb($item);
	$o .= '<as:verb>' . xmlify($verb) . '</as:verb>' . "\r\n";
	$actobj = construct_activity_object($item);
	if(strlen($actobj))
		$o .= $actobj;
	$actarg = construct_activity_target($item);
	if(strlen($actarg))
		$o .= $actarg;

	// FIXME
//	$tags = item_getfeedtags($item);
//	if(count($tags)) {
//		foreach($tags as $t) {
//			$o .= '<category scheme="X-DFRN:' . xmlify($t[0]) . ':' . xmlify($t[1]) . '" term="' . xmlify($t[2]) . '" />' . "\r\n";
//		}
//	}

// FIXME
//	$o .= item_getfeedattach($item);

//	$mentioned = get_mentions($item,$tags);
//	if($mentioned)
//		$o .= $mentioned;
	
	call_hooks('atom_entry', $o);

	$o .= '</entry>' . "\r\n";
	
	return $o;
}

function fix_private_photos($s, $uid, $item = null, $cid = 0) {
	$a = get_app();

	logger('fix_private_photos', LOGGER_DEBUG);
	$site = substr($a->get_baseurl(),strpos($a->get_baseurl(),'://'));

	$orig_body = $s;
	$new_body = '';

	$img_start = strpos($orig_body, '[zmg');
	$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
	$img_len = ($img_start !== false ? strpos(substr($orig_body, $img_start + $img_st_close + 1), '[/zmg]') : false);
	while( ($img_st_close !== false) && ($img_len !== false) ) {

		$img_st_close++; // make it point to AFTER the closing bracket
		$image = substr($orig_body, $img_start + $img_st_close, $img_len);

		logger('fix_private_photos: found photo ' . $image, LOGGER_DEBUG);


		if(stristr($image , $site . '/photo/')) {
			// Only embed locally hosted photos
			$replace = false;
			$i = basename($image);
			$x = strpos($i,'-');

			if($x) {
				$res = substr($i,$x+1);
				$i = substr($i,0,$x);
				$r = q("SELECT * FROM `photo` WHERE `resource_id` = '%s' AND `scale` = %d AND `uid` = %d",
					dbesc($i),
					intval($res),
					intval($uid)
				);
				if(count($r)) {

					// Check to see if we should replace this photo link with an embedded image
					// 1. No need to do so if the photo is public
					// 2. If there's a contact-id provided, see if they're in the access list
					//    for the photo. If so, embed it. 
					// 3. Otherwise, if we have an item, see if the item permissions match the photo
					//    permissions, regardless of order but first check to see if they're an exact
					//    match to save some processing overhead.

					if(has_permissions($r[0])) {
						if($cid) {
							$recips = enumerate_permissions($r[0]);
							if(in_array($cid, $recips)) {
								$replace = true;	
							}
						}
						elseif($item) {
							if(compare_permissions($item,$r[0]))
								$replace = true;
						}
					}
					if($replace) {
						$data = $r[0]['data'];
						$type = $r[0]['type'];

						// If a custom width and height were specified, apply before embedding
						if(preg_match("/\[zmg\=([0-9]*)x([0-9]*)\]/is", substr($orig_body, $img_start, $img_st_close), $match)) {
							logger('fix_private_photos: scaling photo', LOGGER_DEBUG);

							$width = intval($match[1]);
							$height = intval($match[2]);

							$ph = photo_factory($data, $type);
							if($ph->is_valid()) {
								$ph->scaleImage(max($width, $height));
								$data = $ph->imageString();
								$type = $ph->getType();
							}
						}

						logger('fix_private_photos: replacing photo', LOGGER_DEBUG);
						$image = 'data:' . $type . ';base64,' . base64_encode($data);
						logger('fix_private_photos: replaced: ' . $image, LOGGER_DATA);
					}
				}
			}
		}	

		$new_body = $new_body . substr($orig_body, 0, $img_start + $img_st_close) . $image . '[/zmg]';
		$orig_body = substr($orig_body, $img_start + $img_st_close + $img_len + strlen('[/zmg]'));
		if($orig_body === false)
			$orig_body = '';

		$img_start = strpos($orig_body, '[zmg');
		$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
		$img_len = ($img_start !== false ? strpos(substr($orig_body, $img_start + $img_st_close + 1), '[/zmg]') : false);
	}

	$new_body = $new_body . $orig_body;

	return($new_body);
}


function has_permissions($obj) {
	if(($obj['allow_cid'] != '') || ($obj['allow_gid'] != '') || ($obj['deny_cid'] != '') || ($obj['deny_gid'] != ''))
		return true;
	return false;
}

function compare_permissions($obj1,$obj2) {
	// first part is easy. Check that these are exactly the same. 
	if(($obj1['allow_cid'] == $obj2['allow_cid'])
		&& ($obj1['allow_gid'] == $obj2['allow_gid'])
		&& ($obj1['deny_cid'] == $obj2['deny_cid'])
		&& ($obj1['deny_gid'] == $obj2['deny_gid']))
		return true;

	// This is harder. Parse all the permissions and compare the resulting set.

	$recipients1 = enumerate_permissions($obj1);
	$recipients2 = enumerate_permissions($obj2);
	sort($recipients1);
	sort($recipients2);
	if($recipients1 == $recipients2)
		return true;
	return false;
}

// returns an array of contact-ids that are allowed to see this object

function enumerate_permissions($obj) {
	require_once('include/group.php');
	$allow_people = expand_acl($obj['allow_cid']);
	$allow_groups = expand_groups(expand_acl($obj['allow_gid']));
	$deny_people  = expand_acl($obj['deny_cid']);
	$deny_groups  = expand_groups(expand_acl($obj['deny_gid']));
	$recipients   = array_unique(array_merge($allow_people,$allow_groups));
	$deny         = array_unique(array_merge($deny_people,$deny_groups));
	$recipients   = array_diff($recipients,$deny);
	return $recipients;
}

function item_getfeedtags($item) {

	$terms = get_terms_oftype($item['term'],array(TERM_HASHTAG,TERM_MENTION));
	$ret = array();

	if(count($terms)) {
		foreach($terms as $term) {
			if($term['type'] == TERM_HASHTAG)
				$ret[] = array('#',$term['url'],$term['term']);
			else
				$ret[] = array('@',$term['url'],$term['term']);
		}
	}
	return $ret;
}

function item_getfeedattach($item) {
	$ret = '';
	$arr = explode(',',$item['attach']);
	if(count($arr)) {
		foreach($arr as $r) {
			$matches = false;
			$cnt = preg_match('|\[attach\]href=\"(.*?)\" length=\"(.*?)\" type=\"(.*?)\" title=\"(.*?)\"\[\/attach\]|',$r,$matches);
			if($cnt) {
				$ret .= '<link rel="enclosure" href="' . xmlify($matches[1]) . '" type="' . xmlify($matches[3]) . '" ';
				if(intval($matches[2]))
					$ret .= 'length="' . intval($matches[2]) . '" ';
				if($matches[4] !== ' ')
					$ret .= 'title="' . xmlify(trim($matches[4])) . '" ';
				$ret .= ' />' . "\r\n";
			}
		}
	}
	return $ret;
}


	
function item_expire($uid,$days) {

	if((! $uid) || ($days < 1))
		return;

	// $expire_network_only = save your own wall posts
	// and just expire conversations started by others

	$expire_network_only = get_pconfig($uid,'expire','network_only');
	$sql_extra = ((intval($expire_network_only)) ? " AND not (item_flags & " . intval(ITEM_WALL) . ") " : "");

	$r = q("SELECT * FROM `item` 
		WHERE `uid` = %d 
		AND `created` < UTC_TIMESTAMP() - INTERVAL %d DAY 
		AND `id` = `parent` 
		$sql_extra
		AND NOT (item_restrict & %d )
		AND NOT (item_restrict & %d )
		AND NOT (item_restrict & %d ) ",
		intval($uid),
		intval($days),
		intval(ITEM_DELETED),
		intval(ITEM_WEBPAGE),
		intval(ITEM_BUILDBLOCK)
	);

	if(! $r)
		return;

	$r = fetch_post_tags($r,true);

	$expire_items = get_pconfig($uid, 'expire','items');
	$expire_items = (($expire_items===false)?1:intval($expire_items)); // default if not set: 1
	
	$expire_notes = get_pconfig($uid, 'expire','notes');
	$expire_notes = (($expire_notes===false)?1:intval($expire_notes)); // default if not set: 1

	$expire_starred = get_pconfig($uid, 'expire','starred');
	$expire_starred = (($expire_starred===false)?1:intval($expire_starred)); // default if not set: 1
	
	$expire_photos = get_pconfig($uid, 'expire','photos');
	$expire_photos = (($expire_photos===false)?0:intval($expire_photos)); // default if not set: 0
 
	logger('expire: # items=' . count($r). "; expire items: $expire_items, expire notes: $expire_notes, expire starred: $expire_starred, expire photos: $expire_photos");

	foreach($r as $item) {



		// don't expire filed items

		$terms = get_terms_oftype($item['term'],TERM_FILE);
		if($terms)
			continue;

		// Only expire posts, not photos and photo comments

		if($expire_photos==0 && ($item['resource_type'] === 'photo'))
			continue;
		if($expire_starred==0 && ($item['item_flags'] & ITEM_STARRED))
			continue;

		drop_item($item['id'],false);
	}

	proc_run('php',"include/notifier.php","expire","$uid");
	
}


function drop_items($items) {
	$uid = 0;

	if(! local_user() && ! remote_user())
		return;

	if(count($items)) {
		foreach($items as $item) {
			$owner = drop_item($item,false);
			if($owner && ! $uid)
				$uid = $owner;
		}
	}

	// multiple threads may have been deleted, send an expire notification

	if($uid)
		proc_run('php',"include/notifier.php","expire","$uid");
}


// Delete item with given item $id. $interactive means we're running interactively, and must check
// permissions to carry out this act. If it is non-interactive, we are deleting something at the
// system's request and do not check permission. This is very important to know. 

function drop_item($id,$interactive = true) {

	$a = get_app();

	// locate item to be deleted

	$r = q("SELECT * FROM item WHERE id = %d LIMIT 1",
		intval($id)
	);

	if(! $r) {
		if(! $interactive)
			return 0;
		notice( t('Item not found.') . EOL);
		goaway($a->get_baseurl() . '/' . $_SESSION['return_url']);
	}

	$item = $r[0];

	$ok_to_delete = false;

	// system deletion
	if(! $interactive)
		$ok_to_delete = true;

	// owner deletion
	if(local_user() && local_user() == $item['uid'])
		$ok_to_delete = true;

	// author deletion
	$observer = $a->get_observer();
	if($observer && $observer['xchan_hash'] && ($observer['xchan_hash'] === $item['author_xchan']))
		$ok_to_delete = true;

	if($ok_to_delete) {

		$notify_id = intval($item['id']);

		$items = q("select * from item where parent = %d and uid = %d",
			intval($item['id']),
			intval($item['uid'])
		);
		if($items) {
			foreach($items as $i)
				delete_item_lowlevel($i);
		}
		else
			delete_item_lowlevel($item);

		if(! $interactive)
			return 1;

		// send the notification upstream/downstream as the case may be
		// only send notifications to others if this is the owner's wall item. 

		if($item['item_flags'] & ITEM_WALL)
			proc_run('php','include/notifier.php','drop',$notify_id);

		goaway($a->get_baseurl() . '/' . $_SESSION['return_url']);

	}
	else {
		if(! $interactive)
			return 0;
		notice( t('Permission denied.') . EOL);
		goaway($a->get_baseurl() . '/' . $_SESSION['return_url']);
	}
	
}

// This function does not check for permission and does not send notifications and does not check recursion.
// It merely destroys all resources associated with an item. 
// Please do not use without a suitable wrapper.

function delete_item_lowlevel($item) {

	$r = q("UPDATE item SET item_restrict = ( item_restrict | %d ), title = '', body = '',
		changed = '%s', edited = '%s'  WHERE id = %d LIMIT 1",
		intval(ITEM_DELETED),
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		intval($item['id'])
	);

	$r = q("delete from term where otype = %d and oid = %d limit 1",
		intval(TERM_OBJ_POST),
		intval($item['id'])
	);

	// If item is a link to a photo resource, nuke all the associated photos 
	// This only applies to photos uploaded from the photos page. Photos inserted into a post do not
	// generate a resource_id and therefore aren't intimately linked to the item. 

	if(strlen($item['resource_id'])) {
		if($item['resource_type'] === 'event') {
			q("delete from event where event_hash = '%s' and uid = %d limit 1",
				dbesc($item['resource_id']),
				intval($item['uid'])
			);				
		}
		elseif($item['resource_type'] === 'photo') {
			q("DELETE FROM `photo` WHERE `resource_id` = '%s' AND `uid` = %d ",
				dbesc($item['resource_id']),
				intval($item['uid'])
			);
		}
	}

	q("delete from item_id where iid = %d and uid = %d limit 1",
		intval($item['id']),
		intval($item['uid'])
	);

	q("delete from term where oid = %d and otype = %d",
		intval($item['id']),
		intval(TERM_OBJ_POST)
	);

// FIXME remove notifications for this item


	return true;
}


function first_post_date($uid,$wall = false) {

	$wall_sql = (($wall) ? sprintf(" and item_flags & %d ", ITEM_WALL) : "" );

	$r = q("select id, created from item
		where item_restrict = %d and uid = %d and id = parent $wall_sql
		order by created asc limit 1",
		intval(ITEM_VISIBLE),
		intval($uid)

	);
	if(count($r)) {
//		logger('first_post_date: ' . $r[0]['id'] . ' ' . $r[0]['created'], LOGGER_DATA);
		return substr(datetime_convert('',date_default_timezone_get(),$r[0]['created']),0,10);
	}
	return false;
}

function posted_dates($uid,$wall) {
	$dnow = datetime_convert('',date_default_timezone_get(),'now','Y-m-d');

	$dthen = first_post_date($uid,$wall);
	if(! $dthen)
		return array();

	// If it's near the end of a long month, backup to the 28th so that in 
	// consecutive loops we'll always get a whole month difference.

	if(intval(substr($dnow,8)) > 28)
		$dnow = substr($dnow,0,8) . '28';
	if(intval(substr($dthen,8)) > 28)
		$dnow = substr($dthen,0,8) . '28';

	$ret = array();
	// Starting with the current month, get the first and last days of every
	// month down to and including the month of the first post
	while(substr($dnow, 0, 7) >= substr($dthen, 0, 7)) {
		$dstart = substr($dnow,0,8) . '01';
		$dend = substr($dnow,0,8) . get_dim(intval($dnow),intval(substr($dnow,5)));
		$start_month = datetime_convert('','',$dstart,'Y-m-d');
		$end_month = datetime_convert('','',$dend,'Y-m-d');
		$str = day_translate(datetime_convert('','',$dnow,'F Y'));
 		$ret[] = array($str,$end_month,$start_month);
		$dnow = datetime_convert('','',$dnow . ' -1 month', 'Y-m-d');
	}
	return $ret;
}


function posted_date_widget($url,$uid,$wall) {
	$o = '';

	if(! feature_enabled($uid,'archives'))
		return $o;

	$ret = posted_dates($uid,$wall);
	if(! count($ret))
		return $o;

	$o = replace_macros(get_markup_template('posted_date_widget.tpl'),array(
		'$title' => t('Archives'),
		'$size' => ((count($ret) > 6) ? 6 : count($ret)),
		'$url' => $url,
		'$dates' => $ret
	));
	return $o;
}


function fetch_post_tags($items,$link = false) {

	$tag_finder = array();
	if($items) {		
		foreach($items as $item) {
			if(is_array($item)) {
				if(array_key_exists('item_id',$item)) {
					if(! in_array($item['item_id'],$tag_finder))
						$tag_finder[] = $item['item_id'];
				}
				else {
					if(! in_array($item['id'],$tag_finder))
						$tag_finder[] = $item['id'];
				}
			}
		}
	}
	$tag_finder_str = implode(', ', $tag_finder);


	if(strlen($tag_finder_str)) {
		$tags = q("select * from term where oid in ( %s ) and otype = %d",
			dbesc($tag_finder_str),
			intval(TERM_OBJ_POST)
		);
	}


	for($x = 0; $x < count($items); $x ++) {
		if($tags) {
			foreach($tags as $t) {
				if(($link) && ($t['type'] == TERM_MENTION))
					$t['url'] = chanlink_url($t['url']);
				if(array_key_exists('item_id',$items[$x])) {
					if($t['oid'] == $items[$x]['item_id']) {
						if(! is_array($items[$x]['term']))
							$items[$x]['term'] = array();
						$items[$x]['term'][] = $t;
					}
				}
				else {
					if($t['oid'] == $items[$x]['id']) {
						if(! is_array($items[$x]['term']))
							$items[$x]['term'] = array();
						$items[$x]['term'][] = $t;
					}
				}
			}
		}
	}

	return $items;
}



function zot_feed($uid,$observer_xchan,$mindate) {


	$result = array();
	$mindate = datetime_convert('UTC','UTC',$mindate);
	if(! $mindate)
		$mindate = '0000-00-00 00:00:00';

	$mindate = dbesc($mindate);

	logger('zot_feed: ' . $uid);

	if(! perm_is_allowed($uid,$observer_xchan,'view_stream')) {
		logger('zot_feed: permission denied.');
		return $result;
	}

	require_once('include/security.php');
	$sql_extra = item_permissions_sql($uid);

	if($mindate != '0000-00-00 00:00:00') {
		$sql_extra .= " and created > '$mindate' ";
		$limit = "";
	}
	else
		$limit = " limit 0, 50 ";

	$items = array();

	$r = q("SELECT item.*, item.id as item_id from item
		WHERE uid = %d AND item_restrict = 0 and id = parent
		AND (item_flags &  %d) 
		$sql_extra ORDER BY created ASC $limit",
		intval($uid),
		intval(ITEM_WALL)
	);
	if($r) {

		$parents_str = ids_to_querystr($r,'id');

		$items = q("SELECT `item`.*, `item`.`id` AS `item_id` FROM `item` 
			WHERE `item`.`uid` = %d AND `item`.`item_restrict` = 0
			AND `item`.`parent` IN ( %s ) ",
			intval($uid),
			dbesc($parents_str)
		);

	}

	if($items) {
		xchan_query($items);
		$items = fetch_post_tags($items);
		require_once('include/conversation.php');
		$items = conv_sort($items,'ascending');

	}
	else
		$items = array();

	foreach($items as $item)
		$result[] = encode_item($item);

	return $result;

}



function items_fetch($arr,$channel = null,$observer_hash = null,$client_mode = CLIENT_MODE_NORMAL,$module = 'network') {

	$result = array('success' => false);

	$a = get_app();

	$sql_extra = '';
	$sql_nets = '';
	$sql_options = '';
	$sql_extra2 = '';
    $sql_extra3 = '';
	$item_uids = ' true ';

	if($channel) {
		$uid = $channel['channel_id'];
		$uidhash = $channel['channel_hash'];
		$item_uids = " item.uid = " . intval($uid) . " ";
	}
	
	if($arr['star'])
		$sql_options .= " and (item_flags & " . intval(ITEM_STARRED) . ") ";

	if($arr['wall'])
		$sql_options .= " and (item_flags & " . intval(ITEM_WALL) . ") ";

	$sql_extra = " AND item.parent IN ( SELECT parent FROM item WHERE (item_flags & " . intval(ITEM_THREAD_TOP) . ") $sql_options ) ";

    if($arr['group'] && $uid) {
        $r = q("SELECT * FROM `group` WHERE id = %d AND uid = %d LIMIT 1",
            intval($arr['group']),
            intval($uid)
        );
        if(! $r) {
			$result['message']  = t('Collection not found.');
			return $result;
        }


		$contact_str = '';
        $contacts = group_get_members($group);
        if($contacts) {
			foreach($contacts as $c) {
				if($contact_str)
					$contact_str .= ',';
            	$contact_str .= "'" . $c['xchan'] . "'";
			}
        }
        else {
			$contact_str = ' 0 ';	
			info( t('Group is empty'));
        }

        $sql_extra = " AND item.parent IN ( SELECT DISTINCT parent FROM item WHERE true $sql_options AND (( author_xchan IN ( $contact_str ) OR owner_xchan in ( $contact_str)) or allow_gid like '" . protect_sprintf('%<' . dbesc($r[0]['hash']) . '>%') . "' ) and id = parent and item_restrict = 0 ) ";

    }
    elseif($arr['cid'] && $uid) {

        $r = q("SELECT abook.*, xchan.* from abook left join xchan on abook_xchan = xchan_hash where abook_id = %d and abook_channel = %d and not ( abook_flags & " . intval(ABOOK_FLAG_BLOCKED) . ") limit 1",
			intval($arr['cid']),
			intval(local_user())
        );
        if($r) {
            $sql_extra = " AND item.parent IN ( SELECT DISTINCT parent FROM item WHERE true $sql_options AND uid = " . intval($arr['uid']) . " AND ( author_xchan = '" . dbesc($r[0]['abook_xchan']) . "' or owner_xchan = '" . dbesc($r[0]['abook_xchan']) . "' ) and item_restrict = 0 ) ";
        }
        else {
			$result['message'] = t('Connection not found.');
			return $result;
        }
    }

    if($arr['datequery']) {
        $sql_extra3 .= protect_sprintf(sprintf(" AND item.created <= '%s' ", dbesc(datetime_convert(date_default_timezone_get(),'',$arr['datequery']))));
    }
    if($arr['datequery2']) {
        $sql_extra3 .= protect_sprintf(sprintf(" AND item.created >= '%s' ", dbesc(datetime_convert(date_default_timezone_get(),'',$arr['datequery2']))));
    }

	if(! array_key_exists('nouveau',$arr)) {
	    $sql_extra2 = " AND item.parent = item.id ";
		$sql_extra3 = '';
	}

	if($arr['search']) {
        if(strpos($arr['search'],'#') === 0)
            $sql_extra .= term_query('item',substr($arr['search'],1),TERM_HASHTAG);
        else
            $sql_extra .= sprintf(" AND item.body like '%s' ",
                dbesc(protect_sprintf('%' . $arr['search'] . '%'))
            );
    }

    if(strlen($arr['file'])) {
        $sql_extra .= term_query('item',$arr['files'],TERM_FILE);
    }

    if($arr['conv'] && $channel) {
        $sql_extra .= sprintf(" AND parent IN (SELECT distinct parent from item where ( author_xchan like '%s' or ( item_flags & %d ))) ",
            dbesc(protect_sprintf($uidhash)),
            intval(ITEM_MENTIONSME)
        );
    }

    if(($client_mode & CLIENT_MODE_UPDATE) && (! ($client_mode & CLIENT_MODE_LOAD))) {

        // only setup pagination on initial page view
        $pager_sql = '';

    }
    else {
        $itemspage = (($channel) ? get_pconfig($uid,'system','itemspage') : 20);
        $a->set_pager_itemspage(((intval($itemspage)) ? $itemspage : 20));
        $pager_sql = sprintf(" LIMIT %d, %d ",intval(get_app()->pager['start']), intval(get_app()->pager['itemspage']));
    }


    if(($arr['cmin'] != 0) || ($arr['cmax'] != 99)) {

        // Not everybody who shows up in the network stream will be in your address book.
        // By default those that aren't are assumed to have closeness = 99; but this isn't
        // recorded anywhere. So if cmax is 99, we'll open the search up to anybody in
        // the stream with a NULL address book entry.

        $sql_nets .= " AND ";

        if($arr['cmax'] == 99)
            $sql_nets .= " ( ";

        $sql_nets .= "( abook.abook_closeness >= " . intval($arr['cmin']) . " ";
        $sql_nets .= " AND abook.abook_closeness <= " . intval($arr['cmax']) . " ) ";
		if($cmax == 99)
            $sql_nets .= " OR abook.abook_closeness IS NULL ) ";
    }

    $simple_update = (($client_mode & CLIENT_MODE_UPDATE) ? " and ( item.item_flags & " . intval(ITEM_UNSEEN) . " ) " : '');
    if($client_mode & CLIENT_MODE_LOAD)
        $simple_update = '';

    $start = dba_timer();

	require_once('include/security.php');
	$sql_extra .= item_permissions_sql($channel['channel_id']);

	if($arr['pages'])
		$item_restrict = " AND (item_restrict & " . ITEM_WEBPAGE . ") ";
	else
		$item_restrict = " AND item_restrict = 0 ";


    if($arr['nouveau'] && ($client_mode & CLIENT_MODELOAD) && $channel) {
        // "New Item View" - show all items unthreaded in reverse created date order

        $items = q("SELECT item.*, item.id AS item_id FROM item
            WHERE $item_uids $item_restrict
            $simple_update
            $sql_extra $sql_nets
            ORDER BY item.received DESC $pager_sql "
        );

        require_once('include/items.php');

        xchan_query($items);

        $items = fetch_post_tags($items,true);
    }
    else {

        // Normal conversation view

        if($arr['order'] === 'post')
			$ordering = "created";
        else
			$ordering = "commented";

        if(($client_mode & CLIENT_MODE_LOAD) || ($client_mode & CLIENT_MODE_NORMAL)) {

            // Fetch a page full of parent items for this page

            $r = q("SELECT distinct item.id AS item_id FROM item
                left join abook on item.author_xchan = abook.abook_xchan
                WHERE $item_uids $item_restrict
                AND item.parent = item.id
                and ((abook.abook_flags & %d) = 0 or abook.abook_flags is null)
                $sql_extra3 $sql_extra $sql_nets
                ORDER BY item.$ordering DESC $pager_sql ",
                intval(ABOOK_FLAG_BLOCKED)
            );

        }
        else {
            // update
            $r = q("SELECT item.parent AS item_id FROM item
                left join abook on item.author_xchan = abook.abook_xchan
                WHERE $item_uids $item_restrict $simple_update
                and ((abook.abook_flags & %d) = 0 or abook.abook_flags is null)
                $sql_extra3 $sql_extra $sql_nets ",
                intval(ABOOK_FLAG_BLOCKED)
            );
        }

        $first = dba_timer();

        // Then fetch all the children of the parents that are on this page

        if($r) {

            $parents_str = ids_to_querystr($r,'item_id');

            $items = q("SELECT item.*, item.id AS item_id FROM item
                WHERE $item_uids $item_restrict
                AND item.parent IN ( %s )
                $sql_extra ",
                dbesc($parents_str)
            );

            $second = dba_timer();

            xchan_query($items);

            $third = dba_timer();

            $items = fetch_post_tags($items,true);

            $fourth = dba_timer();

			require_once('include/conversation.php');
            $items = conv_sort($items,$ordering);

            //logger('items: ' . print_r($items,true));

        }
        else {
            $items = array();
        }

        if($parents_str && $arr['mark_seen'])
            $update_unseen = ' AND parent IN ( ' . dbesc($parents_str) . ' )';
			// FIXME finish mark unseen sql
    }

	return $items;
}
