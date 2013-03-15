<?php

/**
 *
 * This is the POST destination for most all locally posted
 * text stuff. This function handles status, wall-to-wall status, 
 * local comments, and remote coments that are posted on this site 
 * (as opposed to being delivered in a feed).
 * Also processed here are posts and comments coming through the 
 * statusnet/twitter API. 
 * All of these become an "item" which is our basic unit of 
 * information.
 * Posts that originate externally or do not fall into the above 
 * posting categories go through item_store() instead of this function. 
 *
 */  

require_once('include/crypto.php');
require_once('include/enotify.php');
require_once('include/items.php');
require_once('include/attach.php');

function item_post(&$a) {


	// This will change. Figure out who the observer is and whether or not
	// they have permission to post here. Else ignore the post.

	if((! local_user()) && (! remote_user()) && (! x($_REQUEST,'commenter')))
		return;

	require_once('include/security.php');

	$uid = local_user();

	if(x($_REQUEST,'dropitems')) {
		require_once('include/items.php');
		$arr_drop = explode(',',$_REQUEST['dropitems']);
		drop_items($arr_drop);
		$json = array('success' => 1);
		echo json_encode($json);
		killme();
	}

	call_hooks('post_local_start', $_REQUEST);

	logger('postvars ' . print_r($_REQUEST,true), LOGGER_DATA);

	$api_source = ((x($_REQUEST,'api_source') && $_REQUEST['api_source']) ? true : false);

	// 'origin' (if non-zero) indicates that this network is where the message originated,
	// for the purpose of relaying comments to other conversation members. 
	// If using the API from a device (leaf node) you must set origin to 1 (default) or leave unset.
	// If the API is used from another network with its own distribution
	// and deliveries, you may wish to set origin to 0 or false and allow the other 
	// network to relay comments.

	// If you are unsure, it is prudent (and important) to leave it unset.   

	$origin = (($api_source && array_key_exists('origin',$_REQUEST)) ? intval($_REQUEST['origin']) : 1);
	$owner_hash = null;


	$profile_uid = ((x($_REQUEST,'profile_uid')) ? intval($_REQUEST['profile_uid'])   : 0);
	$post_id     = ((x($_REQUEST,'post_id'))     ? intval($_REQUEST['post_id'])       : 0);
	$app         = ((x($_REQUEST,'source'))      ? strip_tags($_REQUEST['source'])    : '');
	$return_path = ((x($_REQUEST,'return'))      ? $_REQUEST['return']                : '');
	$preview     = ((x($_REQUEST,'preview'))     ? intval($_REQUEST['preview'])       : 0);
	$categories  = ((x($_REQUEST,'category'))    ? escape_tags($_REQUEST['category']) : '');
	$webpage     = ((x($_REQUEST,'webpage'))     ? intval($_REQUEST['webpage'])       : 0);
	$pagetitle   = ((x($_REQUEST,'pagetitle'))   ? escape_tags($_REQUEST['pagetitle']): '');

	if($pagetitle) {
		require_once('library/urlify/URLify.php');
		$pagetitle = strtolower(URLify::transliterate($pagetitle));
	}

	/**
	 * Is this a reply to something?
	 */

	$parent = ((x($_REQUEST,'parent')) ? intval($_REQUEST['parent']) : 0);
	$parent_uri = ((x($_REQUEST,'parent_uri')) ? trim($_REQUEST['parent_uri']) : '');

	$parent_item = null;
	$parent_contact = null;
	$thr_parent = '';
	$parid = 0;
	$r = false;

	if($parent || $parent_uri) {

		if(! x($_REQUEST,'type'))
			$_REQUEST['type'] = 'net-comment';

		if($parent) {
			$r = q("SELECT * FROM `item` WHERE `id` = %d LIMIT 1",
				intval($parent)
			);
		}
		elseif($parent_uri && local_user()) {
			// This is coming from an API source, and we are logged in
			$r = q("SELECT * FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
				dbesc($parent_uri),
				intval(local_user())
			);
		}
		// if this isn't the real parent of the conversation, find it
		if($r !== false && count($r)) {
			$parid = $r[0]['parent'];
			$parent_uri = $r[0]['uri'];
			if($r[0]['id'] != $r[0]['parent']) {
				$r = q("SELECT * FROM `item` WHERE `id` = `parent` AND `parent` = %d LIMIT 1",
					intval($parid)
				);
			}
		}

		if(($r === false) || (! count($r))) {
			notice( t('Unable to locate original post.') . EOL);
			if(x($_REQUEST,'return')) 
				goaway($a->get_baseurl() . "/" . $return_path );
			killme();
		}
		$parent_item = $r[0];
		$parent = $r[0]['id'];

		// multi-level threading - preserve the info but re-parent to our single level threading
		//if(($parid) && ($parid != $parent))
			$thr_parent = $parent_uri;

		if($parent_item['contact-id'] && $uid) {
			$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($parent_item['contact-id']),
				intval($uid)
			);
			if(count($r))
				$parent_contact = $r[0];
		}
	}

	if($parent) logger('mod_item: item_post parent=' . $parent);

	$observer = $a->get_observer();

	if(! perm_is_allowed($profile_uid,$observer['xchan_hash'],(($parent) ? 'post_comments' : 'post_wall'))) {
		notice( t('Permission denied.') . EOL) ;
		if(x($_REQUEST,'return')) 
			goaway($a->get_baseurl() . "/" . $return_path );
		killme();
	}


	// is this an edited post?

	$orig_post = null;

	if($post_id) {
		$i = q("SELECT * FROM `item` WHERE `uid` = %d AND `id` = %d LIMIT 1",
			intval($profile_uid),
			intval($post_id)
		);
		if(! count($i))
			killme();
		$orig_post = $i[0];
	}

	$channel = null;

	if(local_user() && local_user() == $profile_uid) {
		$channel = $a->get_channel();
	}
	else {
		$r = q("SELECT channel.*, account.* FROM channel left join account on channel.channel_account_id = account.account_id 
			where channel.channel_id = %d LIMIT 1",
			intval($profile_uid)
		);
		if(count($r))
			$channel = $r[0];
	}

	if(! $channel) {
		logger("mod_item: no channel.");
		if(x($_REQUEST,'return')) 
			goaway($a->get_baseurl() . "/" . $return_path );
		killme();
	}

	$owner_xchan = null;

	$r = q("select * from xchan where xchan_hash = '%s' limit 1",
		dbesc($channel['channel_hash'])
	);
	if($r && count($r)) {
		$owner_xchan = $r[0];
	}
	else {
		logger("mod_item: no owner.");
		if(x($_REQUEST,'return')) 
			goaway($a->get_baseurl() . "/" . $return_path );
		killme();
	}
		

	if($orig_post) {
		$str_group_allow   = $orig_post['allow_gid'];
		$str_contact_allow = $orig_post['allow_cid'];
		$str_group_deny    = $orig_post['deny_gid'];
		$str_contact_deny  = $orig_post['deny_cid'];
		$location          = $orig_post['location'];
		$coord             = $orig_post['coord'];
		$verb              = $orig_post['verb'];
		$app			   = $orig_post['app'];
		$title             = escape_tags(trim($_REQUEST['title']));
		$body              = escape_tags(trim($_REQUEST['body']));
		$private           = $orig_post['item_private'];

	}
	else {

		// if coming from the API and no privacy settings are set, 
		// use the user default permissions - as they won't have
		// been supplied via a form.

		if(($api_source) 
			&& (! array_key_exists('contact_allow',$_REQUEST))
			&& (! array_key_exists('group_allow',$_REQUEST))
			&& (! array_key_exists('contact_deny',$_REQUEST))
			&& (! array_key_exists('group_deny',$_REQUEST))) {
			$str_group_allow   = $channel['channel_allow_gid'];
			$str_contact_allow = $channel['channel_allow_cid'];
			$str_group_deny    = $channel['channel_deny_gid'];
			$str_contact_deny  = $channel['channel_deny_cid'];
		}
		else {

			// use the posted permissions

			$str_group_allow   = perms2str($_REQUEST['group_allow']);
			$str_contact_allow = perms2str($_REQUEST['contact_allow']);
			$str_group_deny    = perms2str($_REQUEST['group_deny']);
			$str_contact_deny  = perms2str($_REQUEST['contact_deny']);
		}

		$location          = notags(trim($_REQUEST['location']));
		$coord             = notags(trim($_REQUEST['coord']));
		$verb              = notags(trim($_REQUEST['verb']));
		$title             = escape_tags(trim($_REQUEST['title']));
		$body              = escape_tags(trim($_REQUEST['body']));

		$private = ( 
				(  strlen($str_group_allow) 
				|| strlen($str_contact_allow) 
				|| strlen($str_group_deny) 
				|| strlen($str_contact_deny)
		) ? 1 : 0);

		// If this is a comment, set the permissions from the parent.

		if($parent_item) {
			$private = 0;

			if(($parent_item['item_private']) 
				|| strlen($parent_item['allow_cid']) 
				|| strlen($parent_item['allow_gid']) 
				|| strlen($parent_item['deny_cid']) 
				|| strlen($parent_item['deny_gid'])) {
				$private = (($parent_item['item_private']) ? $parent_item['item_private'] : 1);
			}

			$str_contact_allow = $parent_item['allow_cid'];
			$str_group_allow   = $parent_item['allow_gid'];
			$str_contact_deny  = $parent_item['deny_cid'];
			$str_group_deny    = $parent_item['deny_gid'];
			$owner_hash        = $parent_item['owner_xchan'];
		}
	
		if(! strlen($body)) {
			if($preview)
				killme();
			info( t('Empty post discarded.') . EOL );
			if(x($_REQUEST,'return')) 
				goaway($a->get_baseurl() . "/" . $return_path );
			killme();
		}
	}

	$expires = '0000-00-00 00:00:00';

	if(feature_enabled($profile_uid,'expire')) {
		// expire_quantity, e.g. '3'
		// expire_units, e.g. days, weeks, months
		if(x($_REQUEST,'expire_quantity') && (x($_REQUEST,'expire_units'))) {
			$expire = datetime_convert('UTC','UTC', 'now + ' . $_REQUEST['expire_quantity'] . ' ' . $_REQUEST['expire_units']);
			if($expires <= datetime_convert())
				$expires = '0000-00-00 00:00:00';
		}
	}



	$post_type = notags(trim($_REQUEST['type']));

	$content_type = notags(trim($_REQUEST['content_type']));
	if(! $content_type)
		$content_type = 'text/bbcode';


// BBCODE alert: the following functions assume bbcode input
// and will require alternatives for alternative content-types (text/html, text/markdown, text/plain, etc.)
// we may need virtual or template classes to implement the possible alternatives

	// Work around doubled linefeeds in Tinymce 3.5b2
	// First figure out if it's a status post that would've been
	// created using tinymce. Otherwise leave it alone. 

	$plaintext = ((feature_enabled($profile_uid,'richtext')) ? false : true);
	if((! $parent) && (! $api_source) && (! $plaintext)) {
		$body = fix_mce_lf($body);
	}


	/**
	 *
	 * When a photo was uploaded into the message using the (profile wall) ajax 
	 * uploader, The permissions are initially set to disallow anybody but the
	 * owner from seeing it. This is because the permissions may not yet have been
	 * set for the post. If it's private, the photo permissions should be set
	 * appropriately. But we didn't know the final permissions on the post until
	 * now. So now we'll look for links of uploaded messages that are in the
	 * post and set them to the same permissions as the post itself.
	 *
	 */

	if(! $preview) {
		fix_attached_photo_permissions($profile_uid,$owner_xchan['xchan_hash'],$body,
		$str_contact_allow,$str_group_allow,$str_contact_deny,$str_group_deny);

		fix_attached_file_permissions($channel,$observer['xchan_hash'],$body,
		$str_contact_allow,$str_group_allow,$str_contact_deny,$str_group_deny);

	}



	$body = bb_translate_video($body);

	/**
	 * Fold multi-line [code] sequences
	 */

	$body = preg_replace('/\[\/code\]\s*\[code\]/ism',"\n",$body); 

	$body = scale_external_images($body,false);

	/**
	 * Look for any tags and linkify them
	 */

	$str_tags = '';
	$inform   = '';
	$post_tags = array();

	$tags = get_tags($body);

	$tagged = array();

	$private_forum = false;

	if(count($tags)) {
		foreach($tags as $tag) {

			// If we already tagged 'Robert Johnson', don't try and tag 'Robert'.
			// Robert Johnson should be first in the $tags array

			$fullnametagged = false;
			for($x = 0; $x < count($tagged); $x ++) {
				if(stristr($tagged[$x],$tag . ' ')) {
					$fullnametagged = true;
					break;
				}
			}
			if($fullnametagged)
				continue;

			$success = handle_tag($a, $body, $inform, $str_tags, (local_user()) ? local_user() : $profile_uid , $tag); 
			logger('handle_tag: ' . print_r($success,tue));

			if($success['replaced']) {
				$tagged[] = $tag;
				$post_tags[] = array(
					'uid'   => $profile_uid, 
					'type'  => $success['termtype'],
					'otype' => TERM_OBJ_POST,
					'term'  => $success['term'],
					'url'   => $success['url']
				); 				
			}
			if(is_array($success['contact']) && intval($success['contact']['prv'])) {
				$private_forum = true;
				$private_id = $success['contact']['id'];
			}
		}
	}


//	logger('post_tags: ' . print_r($post_tags,true));

	if(($private_forum) && (! $parent) && (! $private)) {
		// we tagged a private forum in a top level post and the message was public.
		// Restrict it.
		$private = 1;
		$str_contact_allow = '<' . $private_id . '>'; 
	}

	$attachments = '';
	$match = false;

	if(preg_match_all('/(\[attachment\](.*?)\[\/attachment\])/',$body,$match)) {
		$attachments = array();
		foreach($match[2] as $mtch) {
			$hash = substr($mtch,0,strpos($mtch,','));
			$rev = intval(substr($mtch,strpos($mtch,',')));
			$r = attach_by_hash_nodata($hash,$rev);
			if($r['success']) {
				$attachments[] = array(
					'href'     => $a->get_baseurl() . '/attach/' . $r['data']['hash'],
					'length'   =>  $r['data']['filesize'],
					'type'     => $r['data']['filetype'],
					'title'    => urlencode($r['data']['filename']),
					'revision' => $r['data']['revision']
				);
			}
			$body = str_replace($match[1],'',$body);
		}
	}

// BBCODE end alert


	if(strlen($categories)) {
		$cats = explode(',',$categories);
		foreach($cats as $cat) {
			$post_tags[] = array(
				'uid'   => $profile_uid, 
				'type'  => TERM_CATEGORY,
				'otype' => TERM_OBJ_POST,
				'term'  => trim($cat),
				'url'   => ''
			); 				
		}
	}


	$item_flags = ITEM_UNSEEN;
	$item_restrict = ITEM_VISIBLE;
	
	if($post_type === 'wall' || $post_type === 'wall-comment')
		$item_flags = $item_flags | ITEM_WALL;

	if($origin)
		$item_flags = $item_flags | ITEM_ORIGIN;

	if($moderated)
		$item_restrict = $item_restrict | ITEM_MODERATED;

	if($webpage)
		$item_restrict = $item_restrict | ITEM_WEBPAGE;

		
		
	if(! strlen($verb))
		$verb = ACTIVITY_POST ;

	$notify_type = (($parent) ? 'comment-new' : 'wall-new' );

	$uri = item_message_id();
	$parent_uri = $uri;
	if($parent_item)
		$parent_uri = $parent_item['uri'];

	// Fallback so that we alway have a thr_parent

	if(!$thr_parent)
		$thr_parent = $uri;

	$datarray = array();

	if(! $parent) {
		$datarray['parent_uri'] = $uri;
		$item_flags = $item_flags | ITEM_THREAD_TOP;
	}
	
	$datarray['aid']           = $channel['channel_account_id'];
	$datarray['uid']           = $profile_uid;

	$datarray['owner_xchan']   = (($owner_hash) ? $owner_hash : $owner_xchan['xchan_hash']);
	$datarray['author_xchan']  = $observer['xchan_hash'];
	$datarray['created']       = datetime_convert();
	$datarray['edited']        = datetime_convert();
	$datarray['expires']       = $expires;
	$datarray['commented']     = datetime_convert();
	$datarray['received']      = datetime_convert();
	$datarray['changed']       = datetime_convert();
	$datarray['uri']           = $uri;
	$datarray['parent_uri']    = $parent_uri;
	$datarray['mimetype']      = $content_type;
	$datarray['title']         = $title;
	$datarray['body']          = $body;
	$datarray['app']           = $app;
	$datarray['location']      = $location;
	$datarray['coord']         = $coord;
	$datarray['inform']        = $inform;
	$datarray['verb']          = $verb;
	$datarray['allow_cid']     = $str_contact_allow;
	$datarray['allow_gid']     = $str_group_allow;
	$datarray['deny_cid']      = $str_contact_deny;
	$datarray['deny_gid']      = $str_group_deny;
	$datarray['item_private']  = $private;
	$datarray['attach']        = $attachments;
	$datarray['thr_parent']    = $thr_parent;
	$datarray['postopts']      = '';
	$datarray['item_restrict'] = $item_restrict;
	$datarray['item_flags']    = $item_flags;


	// preview mode - prepare the body for display and send it via json

	if($preview) {
		require_once('include/conversation.php');

		$datarray['owner'] = $owner_xchan;
		$datarray['author'] = $observer;
		$o = conversation($a,array($datarray),'search',false,'preview');
		logger('preview: ' . $o, LOGGER_DEBUG);
		echo json_encode(array('preview' => $o));
		killme();
	}

	call_hooks('post_local',$datarray);

	if(x($datarray,'cancel')) {
		logger('mod_item: post cancelled by plugin.');
		if($return_path) {
			goaway($a->get_baseurl() . "/" . $return_path);
		}

		$json = array('cancel' => 1);
		if(x($_REQUEST,'jsreload') && strlen($_REQUEST['jsreload']))
			$json['reload'] = $a->get_baseurl() . '/' . $_REQUEST['jsreload'];

		echo json_encode($json);
		killme();
	}


	if($orig_post) {
		$r = q("UPDATE `item` SET `title` = '%s', `body` = '%s', `attach` = '%s', `edited` = '%s' WHERE `id` = %d AND `uid` = %d LIMIT 1",
			dbesc($datarray['title']),
			dbesc($datarray['body']),
			dbesc($datarray['attach']),
			dbesc(datetime_convert()),
			intval($post_id),
			intval($profile_uid)
		);

		// remove taxonomy items for this post - we'll recreate them

		q("delete from term where otype = %d and oid = %d and type in (%d, %d, %d, %d) ",
			intval(TERM_OBJ_POST),
			intval($post_id),
			intval(TERM_UNKNOWN),
			intval(TERM_HASHTAG),
			intval(TERM_MENTION),
			intval(TERM_CATEGORY)
		);


		proc_run('php', "include/notifier.php", 'edit_post', $post_id);
		if((x($_REQUEST,'return')) && strlen($return_path)) {
			logger('return: ' . $return_path);
			goaway($a->get_baseurl() . "/" . $return_path );
		}
		killme();
	}
	else
		$post_id = 0;


	$post_id = item_store($datarray);

	if($post_id) {
		logger('mod_item: saved item ' . $post_id);

		if(count($post_tags)) {
			foreach($post_tags as $tag) {
				if(strlen(trim($tag['term']))) {
					q("insert into term (uid,oid,otype,type,term,url) values (%d,%d,%d,%d,'%s','%s')",
						intval($tag['uid']),
						intval($post_id),
						intval($tag['otype']),
						intval($tag['type']),
						dbesc(trim($tag['term'])),
						dbesc(trim($tag['url']))
					);
				}
			}
		}

		if($parent) {

			$r = q("UPDATE `item` SET `changed` = '%s' WHERE `parent` = %d ",
				dbesc(datetime_convert()),
				intval($parent)
			);

			// Inherit ACL's from the parent item.

			$r = q("UPDATE `item` SET `allow_cid` = '%s', `allow_gid` = '%s', `deny_cid` = '%s', `deny_gid` = '%s', `item_private` = %d
				WHERE `id` = %d LIMIT 1",
				dbesc($parent_item['allow_cid']),
				dbesc($parent_item['allow_gid']),
				dbesc($parent_item['deny_cid']),
				dbesc($parent_item['deny_gid']),
				intval($parent_item['private']),
				intval($post_id)
			);

			if($datarray['owner_xchan'] != $datarray['author_xchan']) {
				notification(array(
					'type'         => NOTIFY_COMMENT,
					'from_xchan'   => $datarray['author_xchan'],
					'to_xchan'     => $datarray['owner_xchan'],
					'item'         => $datarray,
					'link'		   => $a->get_baseurl() . '/display/' . $datarray['uri'],
					'verb'         => ACTIVITY_POST,
					'otype'        => 'item',
					'parent'       => $parent,
					'parent_uri'   => $parent_item['uri']
				));
			
			}

		}
		else {
			$parent = $post_id;

			if($datarray['owner_xchan'] != $datarray['author_xchan']) {
				notification(array(
					'type'         => NOTIFY_WALL,
					'from_xchan'   => $datarray['author_xchan'],
					'to_xchan'     => $datarray['owner_xchan'],
					'item'         => $datarray,
					'link'		   => $a->get_baseurl() . '/display/' . $datarray['uri'],
					'verb'         => ACTIVITY_POST,
					'otype'        => 'item'
				));
			}
		}

		// fallback so that parent always gets set to non-zero.

		if(! $parent)
			$parent = $post_id;

		$r = q("UPDATE `item` SET `parent` = %d, `parent_uri` = '%s', `changed` = '%s'
			WHERE `id` = %d LIMIT 1",
			intval($parent),
			dbesc(($parent == $post_id) ? $uri : $parent_item['uri']),
			dbesc(datetime_convert()),
			intval($post_id)
		);

		// photo comments turn the corresponding item visible to the profile wall
		// This way we don't see every picture in your new photo album posted to your wall at once.
		// They will show up as people comment on them.

// fixme set item visible as well

		if($parent_item['item_restrict'] & ITEM_HIDDEN) {
			$r = q("UPDATE `item` SET `item_restrict` = %d WHERE `id` = %d LIMIT 1",
				intval($parent_item['item_restrict'] - ITEM_HIDDEN),
				intval($parent_item['id'])
			);
		}
	}
	else {
		logger('mod_item: unable to retrieve post that was just stored.');
		notice( t('System error. Post not saved.') . EOL);
		goaway($a->get_baseurl() . "/" . $return_path );
		// NOTREACHED
	}

	// update the commented timestamp on the parent

	q("UPDATE `item` set `commented` = '%s', `changed` = '%s' WHERE `id` = %d LIMIT 1",
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		intval($parent)
	);

	if($webpage) {

		// store page info as an alternate message_id so we can access it via 
		//    https://sitename/page/$channelname/$pagetitle
		// if no pagetitle was given or it couldn't be transliterated into a url, use the first 
		// sixteen bytes of the uri - which makes the link portable and not quite as daunting
		// as the entire uri. If it were the post_id the link would be less portable.
		// We should have the ability to edit this and arrange pages into menus via the pages module 

		q("insert into item_id ( iid, uid, sid, service ) values ( %d, %d, '%s','%s' )",
			intval($post_id),
			intval($channel['channel_id']),
			dbesc(($pagetitle) ? $pagetitle : substr($uri,0,16)),
			dbesc('WEBPAGE')
		);
	}

	$datarray['id']    = $post_id;
	$datarray['plink'] = $a->get_baseurl() . '/display/' . $channel['channel_address'] . '/' . $post_id;

	call_hooks('post_local_end', $datarray);

	proc_run('php', 'include/notifier.php', $notify_type, $post_id);

	logger('post_complete');

	// figure out how to return, depending on from whence we came

	if($api_source)
		return;

	if($return_path) {
		goaway($a->get_baseurl() . "/" . $return_path);
	}

	$json = array('success' => 1);
	if(x($_REQUEST,'jsreload') && strlen($_REQUEST['jsreload']))
		$json['reload'] = $a->get_baseurl() . '/' . $_REQUEST['jsreload'];

	logger('post_json: ' . print_r($json,true), LOGGER_DEBUG);

	echo json_encode($json);
	killme();
	// NOTREACHED
}





function item_content(&$a) {

	if((! local_user()) && (! remote_user()))
		return;

	require_once('include/security.php');

	if(($a->argc == 3) && ($a->argv[1] === 'drop') && intval($a->argv[2])) {
		require_once('include/items.php');
		drop_item($a->argv[2]);
	}
}

/**
 * This function removes the tag $tag from the text $body and replaces it with 
 * the appropiate link. 
 * 
 * @param unknown_type $body the text to replace the tag in
 * @param unknown_type $inform a comma-seperated string containing everybody to inform
 * @param unknown_type $str_tags string to add the tag to
 * @param unknown_type $profile_uid
 * @param unknown_type $tag the tag to replace
 *
 * @return boolean true if replaced, false if not replaced
 */
function handle_tag($a, &$body, &$inform, &$str_tags, $profile_uid, $tag) {

	$replaced = false;
	$r = null;

	$termtype = ((strpos($tag,'#') === 0) ? TERM_HASHTAG : TERM_UNKNOWN);
	$termtype = ((strpos($tag,'@') === 0) ? TERM_MENTION : $termtype);

	//is it a hash tag? 
	if(strpos($tag,'#') === 0) {
		//if the tag is replaced...
		if(strpos($tag,'[url='))
			//...do nothing
			return $replaced;
		//base tag has the tags name only
		$basetag = str_replace('_',' ',substr($tag,1));
		//create text for link
		$url = $a->get_baseurl() . '/search?tag=' . rawurlencode($basetag);
		$newtag = '#[url=' . $a->get_baseurl() . '/search?tag=' . rawurlencode($basetag) . ']' . $basetag . '[/url]';
		//replace tag by the link
		$body = str_replace($tag, $newtag, $body);
		$replaced = true;

		//is the link already in str_tags?
		if(! stristr($str_tags,$newtag)) {
			//append or set str_tags
			if(strlen($str_tags))
				$str_tags .= ',';
			$str_tags .= $newtag;
		}
		return array('replaced' => $replaced, 'termtype' => $termtype, 'term' => $basetag, 'url' => $url, 'contact' => $r[0]);	
	}
	//is it a person tag? 
	if(strpos($tag,'@') === 0) {
		//is it already replaced? 
		if(strpos($tag,'[url='))
			return $replaced;
		$stat = false;
		//get the person's name
		$name = substr($tag,1);
		//is it a link or a full dfrn address? 
		if((strpos($name,'@')) || (strpos($name,'http://'))) {
			$newname = $name;
			//get the profile links
			$links = @lrdd($name);
			if(count($links)) {
				//for all links, collect how is to inform and how's profile is to link
				foreach($links as $link) {
					if($link['@attributes']['rel'] === 'http://webfinger.net/rel/profile-page')
						$profile = $link['@attributes']['href'];
					if($link['@attributes']['rel'] === 'salmon') {
						if(strlen($inform))
							$inform .= ',';
						$inform .= 'url:' . str_replace(',','%2c',$link['@attributes']['href']);
					}
				}
			}
		} else { //if it is a name rather than an address
			$newname = $name;
			$alias = '';
			$tagcid = 0;
			//is it some generated name?
			if(strrpos($newname,'+')) {
				//get the id
				$tagcid = intval(substr($newname,strrpos($newname,'+') + 1));
				//remove the next word from tag's name
				if(strpos($name,' ')) {
					$name = substr($name,0,strpos($name,' '));
				}
			}
			if($tagcid) { //if there was an id

				//select contact with that id from the logged in user's contact list
				$r = q("SELECT * FROM abook left join xchan on abook_xchan = xchan_hash 
					WHERE abook_id = %d AND abook_channel = %d LIMIT 1",
						intval($tagcid),
						intval($profile_uid)
				);

			}
			else {
				$newname = str_replace('_',' ',$name);

				//select someone from this user's contacts by name
				$r = q("SELECT * FROM abook left join xchan on abook_xchan - xchan_hash  
					WHERE xchan_name = '%s' AND abook_channel = %d LIMIT 1",
						dbesc($newname),
						intval($profile_uid)
				);

				if(! $r) {
					//select someone by attag or nick and the name passed in
/*					$r = q("SELECT * FROM `contact` WHERE `attag` = '%s' OR `nick` = '%s' AND `uid` = %d ORDER BY `attag` DESC LIMIT 1",
							dbesc($name),
							dbesc($name),
							intval($profile_uid)
					);
*/				}
			}
/*			} elseif(strstr($name,'_') || strstr($name,' ')) { //no id
				//get the real name
				$newname = str_replace('_',' ',$name);
				//select someone from this user's contacts by name
				$r = q("SELECT * FROM `contact` WHERE `name` = '%s' AND `uid` = %d LIMIT 1",
						dbesc($newname),
						intval($profile_uid)
				);
			} else {
				//select someone by attag or nick and the name passed in
				$r = q("SELECT * FROM `contact` WHERE `attag` = '%s' OR `nick` = '%s' AND `uid` = %d ORDER BY `attag` DESC LIMIT 1",
						dbesc($name),
						dbesc($name),
						intval($profile_uid)
				);
			}*/
			//$r is set, if someone could be selected
			if($r) {
				$profile = $r[0]['xchan_url'];
				$newname = $r[0]['xchan_name'];
				//add person's id to $inform
				if(strlen($inform))
					$inform .= ',';
				$inform .= 'cid:' . $r[0]['id'];
			}
		}
		//if there is an url for this persons profile
		if(isset($profile)) {
			$replaced = true;
			//create profile link
			$profile = str_replace(',','%2c',$profile);
			$url = $profile;
			$newtag = '@[url=' . $profile . ']' . $newname	. '[/url]';
			$body = str_replace('@' . $name, $newtag, $body);
			//append tag to str_tags
			if(! stristr($str_tags,$newtag)) {
				if(strlen($str_tags))
					$str_tags .= ',';
				$str_tags .= $newtag;
			}
		}
	}

	return array('replaced' => $replaced, 'termtype' => $termtype, 'term' => $newname, 'url' => $url, 'contact' => $r[0]);	
}



function fix_attached_photo_permissions($uid,$xchan_hash,$body,
		$str_contact_allow,$str_group_allow,$str_contact_deny,$str_group_deny) {

	$match = null;

	if(preg_match_all("/\[img\](.*?)\[\/img\]/",$body,$match)) {
		$images = $match[1];
		if($images) {
			foreach($images as $image) {
				if(! stristr($image,get_app()->get_baseurl() . '/photo/'))
					continue;
				$image_uri = substr($image,strrpos($image,'/') + 1);
				$image_uri = substr($image_uri,0, strpos($image_uri,'-'));
				if(! strlen($image_uri))
					continue;
				$srch = '<' . $xchan_hash . '>';

				$r = q("SELECT id FROM photo 
					WHERE allow_cid = '%s' AND allow_gid = '' AND deny_cid = '' AND deny_gid = ''
					AND resource_id = '%s' AND uid = %d LIMIT 1",
					dbesc($srch),
					dbesc($image_uri),
					intval($uid)
				);

				if($r) {
					$r = q("UPDATE photo SET allow_cid = '%s', allow_gid = '%s', deny_cid = '%s', deny_gid = '%s'
						WHERE resource_id = '%s' AND uid = %d AND album = '%s' ",
						dbesc($str_contact_allow),
						dbesc($str_group_allow),
						dbesc($str_contact_deny),
						dbesc($str_group_deny),
						dbesc($image_uri),
						intval($uid),
						dbesc( t('Wall Photos'))
					);

					// also update the linked item (which is probably invisible)

					$r = q("select id from item
						WHERE allow_cid = '%s' AND allow_gid = '' AND deny_cid = '' AND deny_gid = ''
						AND resource_id = '%s' and resource_type = 'photo' AND uid = %d LIMIT 1",
						dbesc($srch),
						dbesc($image_uri),
						intval($uid)
					);
					if($r) {
						$r = q("UPDATE item SET allow_cid = '%s', allow_gid = '%s', deny_cid = '%s', deny_gid = '%s'
							WHERE id = %d AND uid = %d limit 1",
							dbesc($str_contact_allow),
							dbesc($str_group_allow),
							dbesc($str_contact_deny),
							dbesc($str_group_deny),
							intval($r[0]['id']),
							intval($uid)
						);
					}
				}
			}
		}
	}
}


function fix_attached_file_permissions($channel,$observer_hash,$body,
		$str_contact_allow,$str_group_allow,$str_contact_deny,$str_group_deny) {

	$match = false;

	if(preg_match_all("/\[attachment\](.*?)\[\/attachment\]/",$body,$match)) {
		$attaches = $match[1];
		if($attaches) {
			foreach($attaches as $attach) {
				$hash = substr($attach,0,strpos($attach,','));
				$rev = intval(substr($attach,strpos($attach,',')));
				attach_store($channel,$observer_hash,$options = 'update', array(
					'hash'      => $hash,
					'revision'  => $rev,
					'allow_cid' => $str_contact_allow,
					'allow_gid'  => $str_group_allow,
					'deny_cid'  => $str_contact_deny,
					'deny_gid'  => $str_group_deny
				));
			}
		}
	}
}
