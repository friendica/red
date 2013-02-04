<?php

require_once('include/items.php');

// Note: the code in 'item_extract_images' and 'item_redir_and_replace_images'
// is identical to the code in mod/message.php for 'item_extract_images' and
// 'item_redir_and_replace_images'

if(! function_exists('item_extract_images')) {
function item_extract_images($body) {

	$saved_image = array();
	$orig_body = $body;
	$new_body = '';

	$cnt = 0;
	$img_start = strpos($orig_body, '[img');
	$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
	$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
	while(($img_st_close !== false) && ($img_end !== false)) {

		$img_st_close++; // make it point to AFTER the closing bracket
		$img_end += $img_start;

		if(! strcmp(substr($orig_body, $img_start + $img_st_close, 5), 'data:')) {
			// This is an embedded image

			$saved_image[$cnt] = substr($orig_body, $img_start + $img_st_close, $img_end - ($img_start + $img_st_close));
			$new_body = $new_body . substr($orig_body, 0, $img_start) . '[!#saved_image' . $cnt . '#!]';

			$cnt++;
		}
		else
			$new_body = $new_body . substr($orig_body, 0, $img_end + strlen('[/img]'));

		$orig_body = substr($orig_body, $img_end + strlen('[/img]'));

		if($orig_body === false) // in case the body ends on a closing image tag
			$orig_body = '';

		$img_start = strpos($orig_body, '[img');
		$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
		$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
	}

	$new_body = $new_body . $orig_body;

	return array('body' => $new_body, 'images' => $saved_image);
}}

if(! function_exists('item_redir_and_replace_images')) {
function item_redir_and_replace_images($body, $images, $cid) {

	$origbody = $body;
	$newbody = '';

	$observer = get_app()->get_observer();
	$obhash = (($observer) ? $observer['xchan_hash'] : '');
	$obaddr = (($observer) ? $observer['xchan_addr'] : '');

	for($i = 0; $i < count($images); $i++) {
		$search = '/\[url\=(.*?)\]\[!#saved_image' . $i . '#!\]\[\/url\]' . '/is';
		$replace = '[url=' . magiclink_url($obhash,$obaddr,'$1') . '][!#saved_image' . $i . '#!][/url]' ;

		$img_end = strpos($origbody, '[!#saved_image' . $i . '#!][/url]') + strlen('[!#saved_image' . $i . '#!][/url]');
		$process_part = substr($origbody, 0, $img_end);
		$origbody = substr($origbody, $img_end);

		$process_part = preg_replace($search, $replace, $process_part);
		$newbody = $newbody . $process_part;
	}
	$newbody = $newbody . $origbody;

	$cnt = 0;
	foreach($images as $image) {
		// We're depending on the property of 'foreach' (specified on the PHP website) that
		// it loops over the array starting from the first element and going sequentially
		// to the last element
		$newbody = str_replace('[!#saved_image' . $cnt . '#!]', '[img]' . $image . '[/img]', $newbody);
		$cnt++;
	}

	return $newbody;
}}



/**
 * Render actions localized
 */

function localize_item(&$item){

	$extracted = item_extract_images($item['body']);
	if($extracted['images'])
		$item['body'] = item_redir_and_replace_images($extracted['body'], $extracted['images'], $item['contact-id']);

	if (activity_match($item['verb'],ACTIVITY_LIKE) || activity_match($item['verb'],ACTIVITY_DISLIKE)){

		$obj= json_decode($item['object'],true);
		
		if($obj['author'] && $obj['author']['link'])
			$author_link = get_rel_link($obj['author']['link'],'alternate');
		else
			$author_link = '';

		$author_name = (($obj['author'] && $obj['author']['name']) ? $obj['author']['name'] : '');

		$item_url = get_rel_link($obj['link'],'alternate');


		// If we couldn't parse something useful, don't bother translating.
		// We need something better than zid here, probably magic_link(), but it needs writing

		if($author_link && $author_name && $item_url) {
			
			$author	 = '[url=' . chanlink_url($item['author']['xchan_url']) . ']' . $item['author']['xchan_name'] . '[/url]';
			$objauthor =  '[url=' . chanlink_url($author_link) . ']' . $author_name . '[/url]';
		
			switch($obj->type) {
				case ACTIVITY_OBJ_PHOTO:
					$post_type = t('photo');
					break;
				case ACTIVITY_OBJ_EVENT:
					$post_type = t('event');
					break;
				case ACTIVITY_OBJ_NOTE:
				default:
					if(! ($item_flags & ITEM_THREAD_TOP))
						$post_type = t('comment');
					else
						$post_type = t('status');
					break;
			}

			$plink = '[url=' . zid($item_url) . ']' . $post_type . '[/url]';

			if(activity_match($item['verb'],ACTIVITY_LIKE)) {
				$bodyverb = t('%1$s likes %2$s\'s %3$s');
			}
			elseif(activity_match($item['verb'],ACTIVITY_DISLIKE)) {
				$bodyverb = t('%1$s doesn\'t like %2$s\'s %3$s');
			}
			$item['body'] = $item['localize'] = sprintf($bodyverb, $author, $objauthor, $plink);

		}

	}

	if (activity_match($item['verb'],ACTIVITY_FRIEND)) {

		if ($item['obj_type']=="" || $item['obj_type']!== ACTIVITY_OBJ_PERSON) return;

		$Aname = $item['author']['xchan_name'];
		$Alink = $item['author']['xchan_url'];


		$obj= json_decode($item['object'],true);
		
		$Blink = $Bphoto = '';

		if($obj['link']) {
			$Blink  = get_rel_link($obj['link'],'alternate');
			$Bphoto = get_rel_link($obj['link'],'photo');
		}
		$Bname = $obj['title'];


		$A = '[url=' . chanlink_url($Alink) . ']' . $Aname . '[/url]';
		$B = '[url=' . chanlink_url($Blink) . ']' . $Bname . '[/url]';
		if ($Bphoto!="") $Bphoto = '[url=' . chanlink_url($Blink) . '][img=80x80]' . $Bphoto . '[/img][/url]';

		$item['body'] = $item['localize'] = sprintf( t('%1$s is now connected with %2$s'), $A, $B);
		$item['body'] .= "\n\n\n" . $Bphoto;
	}

	if (stristr($item['verb'],ACTIVITY_POKE)) {
		$verb = urldecode(substr($item['verb'],strpos($item['verb'],'#')+1));
		if(! $verb)
			return;
		if ($item['obj_type']=="" || $item['obj_type']!== ACTIVITY_OBJ_PERSON) return;

		$Aname = $item['author']['xchan_name'];
		$Alink = $item['author']['xchan_url'];


		$obj= json_decode($item['object'],true);
		
		$Blink = $Bphoto = '';

		if($obj['link']) {
			$Blink  = get_rel_link($obj['link'],'alternate');
			$Bphoto = get_rel_link($obj['link'],'photo');
		}
		$Bname = $obj['title'];

		$A = '[url=' . chanlink_url($Alink) . ']' . $Aname . '[/url]';
		$B = '[url=' . chanlink_url($Blink) . ']' . $Bname . '[/url]';
		if ($Bphoto!="") $Bphoto = '[url=' . chanlink_url($Blink) . '][img=80x80]' . $Bphoto . '[/img][/url]';

		// we can't have a translation string with three positions but no distinguishable text
		// So here is the translate string.

		$txt = t('%1$s poked %2$s');

		// now translate the verb

		$txt = str_replace( t('poked'), t($verb), $txt);

		// then do the sprintf on the translation string

		$item['body'] = $item['localize'] = sprintf($txt, $A, $B);
		$item['body'] .= "\n\n\n" . $Bphoto;

	}
	if (stristr($item['verb'],ACTIVITY_MOOD)) {
		$verb = urldecode(substr($item['verb'],strpos($item['verb'],'#')+1));
		if(! $verb)
			return;

		$Aname = $item['author']['xchan_name'];
		$Alink = $item['author']['xchan_url'];

		$A = '[url=' . chanlink_url($Alink) . ']' . $Aname . '[/url]';
		
		$txt = t('%1$s is currently %2$s');

		$item['body'] = sprintf($txt, $A, t($verb));
	}
/*
// FIXME store parent item as object or target
// (and update to json storage)

 	if (activity_match($item['verb'],ACTIVITY_TAG)) {
		$r = q("SELECT * from `item`,`contact` WHERE 
		`item`.`contact-id`=`contact`.`id` AND `item`.`uri`='%s';",
		 dbesc($item['parent_uri']));
		if(count($r)==0) return;
		$obj=$r[0];
		
		$author	 = '[url=' . zid($item['author-link']) . ']' . $item['author-name'] . '[/url]';
		$objauthor =  '[url=' . zid($obj['author-link']) . ']' . $obj['author-name'] . '[/url]';
		
		switch($obj['verb']){
			case ACTIVITY_POST:
				switch ($obj['obj_type']){
					case ACTIVITY_OBJ_EVENT:
						$post_type = t('event');
						break;
					default:
						$post_type = t('status');
				}
				break;
			default:
				if($obj['resource_id']){
					$post_type = t('photo');
					$m=array(); preg_match("/\[url=([^]]*)\]/", $obj['body'], $m);
					$rr['plink'] = $m[1];
				} else {
					$post_type = t('status');
				}
		}
		$plink = '[url=' . $obj['plink'] . ']' . $post_type . '[/url]';

		$parsedobj = parse_xml_string($xmlhead.$item['object']);

		$tag = sprintf('#[url=%s]%s[/url]', $parsedobj->id, $parsedobj->content);
		$item['body'] = sprintf( t('%1$s tagged %2$s\'s %3$s with %4$s'), $author, $objauthor, $plink, $tag );

	}

	if (activity_match($item['verb'],ACTIVITY_FAVORITE)){

		if ($item['obj_type']== "")
			return;

		$Aname = $item['author']['xchan_name'];
		$Alink = $item['author']['xchan_url'];

		$xmlhead="<"."?xml version='1.0' encoding='UTF-8' ?".">";

		$obj = parse_xml_string($xmlhead.$item['object']);
		if(strlen($obj->id)) {
			$r = q("select * from item where uri = '%s' and uid = %d limit 1",
					dbesc($obj->id),
					intval($item['uid'])
			);
			if(count($r) && $r[0]['plink']) {
				$target = $r[0];
				$Bname = $target['author-name'];
				$Blink = $target['author-link'];
				$A = '[url=' . zid($Alink) . ']' . $Aname . '[/url]';
				$B = '[url=' . zid($Blink) . ']' . $Bname . '[/url]';
				$P = '[url=' . $target['plink'] . ']' . t('post/item') . '[/url]';
				$item['body'] = sprintf( t('%1$s marked %2$s\'s %3$s as favorite'), $A, $B, $P)."\n";

			}
		}
	}
*/

	$matches = null;
	if(preg_match_all('/@\[url=(.*?)\]/is',$item['body'],$matches,PREG_SET_ORDER)) {
		foreach($matches as $mtch) {
			if(! strpos($mtch[1],'zid='))
				$item['body'] = str_replace($mtch[0],'@[url=' . zid($mtch[1]). ']',$item['body']);
		}
	}

	// add zid's to public images
	if(preg_match_all('/\[url=(.*?)\/photos\/(.*?)\/image\/(.*?)\]\[img(.*?)\]h(.*?)\[\/img\]\[\/url\]/is',$item['body'],$matches,PREG_SET_ORDER)) {
		foreach($matches as $mtch) {
				$item['body'] = str_replace($mtch[0],'[url=' . zid( $mtch[1] . '/photos/' . $mtch[2] . '/image/' . $mtch[3]) . '][img' . $mtch[4] . ']h' . $mtch[5]  . '[/img][/url]',$item['body']);
		}
	}

	// add sparkle links to appropriate permalinks

//	$x = stristr($item['plink'],'/display/');
//	if($x) {
//		$sparkle = false;
//		$y = best_link_url($item,$sparkle,true);
	//	if($sparkle)
//			$item['plink'] = $y . '?f=&url=' . $item['plink'];
//	} 
}

/**
 * Count the total of comments on this item and its desendants
 */
function count_descendants($item) {

	$total = count($item['children']);

  	if($total > 0) {
  		foreach($item['children'] as $child) {
 			if(! visible_activity($child))
  				$total --;
  			$total += count_descendants($child);
  		}
  	}

	return $total;
}

function visible_activity($item) {

	if(activity_match($item['verb'],ACTIVITY_LIKE) || activity_match($item['verb'],ACTIVITY_DISLIKE))
		return false;
	return true;
}

/**
 * "Render" a conversation or list of items for HTML display.
 * There are two major forms of display:
 *      - Sequential or unthreaded ("New Item View" or search results)
 *      - conversation view
 * The $mode parameter decides between the various renderings and also
 * figures out how to determine page owner and other contextual items
 * that are based on unique features of the calling module.
 *
 */

if(!function_exists('conversation')) {
function conversation(&$a, $items, $mode, $update, $page_mode = 'traditional') {

	$tstart = dba_timer();

	require_once('bbcode.php');

	$ssl_state = ((local_user()) ? true : false);

	$profile_owner = 0;
	$page_writeable      = false;
	$live_update_div = '';

	$preview = (($page_mode === 'preview') ? true : false);
	$previewing = (($preview) ? ' preview ' : '');

	if($mode === 'network') {
		$profile_owner = local_user();
		$page_writeable = true;

       if(!$update) {
            // The special div is needed for liveUpdate to kick in for this page.
            // We only launch liveUpdate if you aren't filtering in some incompatible
            // way and also you aren't writing a comment (discovered in javascript).

            $live_update_div = '<div id="live-network"></div>' . "\r\n"
                . "<script> var profile_uid = " . $_SESSION['uid']
                . "; var netargs = '" . substr($a->cmd,8)
                . '?f='
                . ((x($_GET,'cid'))    ? '&cid='    . $_GET['cid']    : '')
                . ((x($_GET,'search')) ? '&search=' . $_GET['search'] : '')
                . ((x($_GET,'star'))   ? '&star='   . $_GET['star']   : '')
                . ((x($_GET,'order'))  ? '&order='  . $_GET['order']  : '')
                . ((x($_GET,'bmark'))  ? '&bmark='  . $_GET['bmark']  : '')
                . ((x($_GET,'liked'))  ? '&liked='  . $_GET['liked']  : '')
                . ((x($_GET,'conv'))   ? '&conv='   . $_GET['conv']   : '')
                . ((x($_GET,'spam'))   ? '&spam='   . $_GET['spam']   : '')
                . ((x($_GET,'nets'))   ? '&nets='   . $_GET['nets']   : '')
                . ((x($_GET,'cmin'))   ? '&cmin='   . $_GET['cmin']   : '')
                . ((x($_GET,'cmax'))   ? '&cmax='   . $_GET['cmax']   : '')
                . ((x($_GET,'file'))   ? '&file='   . $_GET['file']   : '')

                . "'; var profile_page = " . $a->pager['page'] . "; </script>\r\n";
        }


	}

	elseif($mode === 'channel') {
		$profile_owner = $a->profile['profile_uid'];
		$page_writeable = ($profile_owner == local_user());

        if(!$update) {
            $tab = notags(trim($_GET['tab']));
            if($tab === 'posts') {
                // This is ugly, but we can't pass the profile_uid through the session to the ajax updater,
                // because browser prefetching might change it on us. We have to deliver it with the page.

                $live_update_div = '<div id="live-channel"></div>' . "\r\n"
                    . "<script> var profile_uid = " . $a->profile['profile_uid']
                    . "; var netargs = '?f='; var profile_page = " . $a->pager['page'] . "; </script>\r\n";
            }
        }

	}

	elseif($mode === 'display') {
		$profile_owner = $a->profile['uid'];
		$page_writeable = ($profile_owner == local_user());

	      $live_update_div = '<div id="live-display"></div>' . "\r\n";

	}

	elseif($mode === 'page') {
		$profile_owner = $a->profile['uid'];
		$page_writeable = ($profile_owner == local_user());
		$live_update_div = '<div id="live-page"></div>' . "\r\n";
	}


    else if($mode === 'search') {
        $live_update_div = '<div id="live-search"></div>' . "\r\n";
    }


	$page_dropping = ((local_user() && local_user() == $profile_owner) ? true : false);

	if(! feature_enabled($profile_owner,'multi_delete'))
		$page_dropping = false;


	$channel = $a->get_channel();
	$observer = $a->get_observer();		

	if($update)
		$return_url = $_SESSION['return_url'];
	else
		$return_url = $_SESSION['return_url'] = $a->query_string;

	load_contact_links(local_user());

	$cb = array('items' => $items, 'mode' => $mode, 'update' => $update, 'preview' => $preview);
	call_hooks('conversation_start',$cb);

	$items = $cb['items'];

	$cmnt_tpl    = get_markup_template('comment_item.tpl');
	$hide_comments_tpl = get_markup_template('hide_comments.tpl');

	$alike = array();
	$dlike = array();


	// array with html for each thread (parent+comments)
	$threads = array();
	$threadsid = -1;

	$page_template = get_markup_template("conversation.tpl");

	if($items) {

		if($mode === 'network-new' || $mode === 'search' || $mode === 'community') {

			// "New Item View" on network page or search page results
			// - just loop through the items and format them minimally for display

			//$tpl = get_markup_template('search_item.tpl');
			$tpl = 'search_item.tpl';

			foreach($items as $item) {
				$threadsid++;

				$comment     = '';
				$owner_url   = '';
				$owner_photo = '';
				$owner_name  = '';
				$sparkle     = '';

				if($mode === 'search' || $mode === 'community') {
					if(((activity_match($item['verb'],ACTIVITY_LIKE)) || (activity_match($item['verb'],ACTIVITY_DISLIKE))) 
						&& ($item['id'] != $item['parent']))
						continue;
					$nickname = $item['nickname'];
				}
				else
					$nickname = $a->user['nickname'];
				
				$profile_name   = ((strlen($item['author-name']))   ? $item['author-name']   : $item['name']);
				if($item['author-link'] && (! $item['author-name']))
					$profile_name = $item['author-link'];

				

				$tags=array();
				$hashtags = array();
				$mentions = array();
				foreach(explode(',',$item['tag']) as $tag){
					$tag = trim($tag);
					if ($tag!="") {
						$t = bbcode($tag);
						$tags[] = $t;
						if($t[0] == '#')
							$hashtags[] = $t;
						elseif($t[0] == '@')
							$mentions[] = $t;
					}
				}

				$sp = false;
				$profile_link = best_link_url($item,$sp);
				if($sp)
					$sparkle = ' sparkle';
				else
					$profile_link = zid($profile_link);

				$normalised = normalise_link((strlen($item['author-link'])) ? $item['author-link'] : $item['url']);
				if(x($a->contacts,$normalised))
					$profile_avatar = $a->contacts[$normalised]['thumb'];
				else
					$profile_avatar = ((strlen($item['author-avatar'])) ? $a->get_cached_avatar_image($item['author-avatar']) : $item['thumb']);

				$profile_name = $item['author']['xchan_name'];
				$profile_link = $item['author']['xchan_url'];
				$profile_avatar = $item['author']['xchan_photo_m'];


				$location = format_location($item);

				localize_item($item);
				if($mode === 'network-new')
					$dropping = true;
				else
					$dropping = false;


				$drop = array(
					'pagedropping' => $page_dropping,
					'dropping' => $dropping,
					'select' => t('Select'), 
					'delete' => t('Delete'),
				);

				$star = false;
				$isstarred = "unstarred";
				
				$lock = false;
				$likebuttons = false;
				$shareable = false;

				$tags=array();
				$terms = get_terms_oftype($item['term'],array(TERM_HASHTAG,TERM_MENTION,TERM_UNKNOWN));
				if(count($terms))
					foreach($terms as $tag)
						$tags[] = format_term_for_display($tag);


				$body = prepare_body($item,true);

				//$tmp_item = replace_macros($tpl,array(
				$tmp_item = array(
					'template' => $tpl,
					'tags' => $tags,
					'id' => (($preview) ? 'P0' : $item['item_id']),
					'linktitle' => sprintf( t('View %s\'s profile @ %s'), $profile_name, $profile_url),
					'profile_url' => $profile_link,
					'item_photo_menu' => item_photo_menu($item),
					'name' => $profile_name,
					'sparkle' => $sparkle,
					'lock' => $lock,
					'thumb' => $profile_avatar,
					'title' => $item['title'],
					'body' => $body,
					'tags' => $tags,
					'hashtags' => $hashtags,
					'mentions' => $mentions,
					'txt_cats' => t('Categories:'),
                    'txt_folders' => t('Filed under:'),
                    'has_cats' => ((count($categories)) ? 'true' : ''),
                    'has_folders' => ((count($folders)) ? 'true' : ''),
                    'categories' => $categories,
                    'folders' => $folders,

					'text' => strip_tags($body),
					'ago' => relative_date($item['created']),
					'app' => $item['app'],
					'str_app' => sprintf( t(' from %s'), $item['app']),
					'localtime' => datetime_convert('UTC', date_default_timezone_get(), $item['created'], 'c'),
					'location' => $location,
					'indent' => '',
					'owner_name' => $owner_name,
					'owner_url' => $owner_url,
					'owner_photo' => $owner_photo,
					'plink' => get_plink($item),
					'edpost' => false,
					'isstarred' => $isstarred,
					'star' => $star,
					'drop' => $drop,
					'vote' => $likebuttons,
					'like' => '',
					'dislike' => '',
					'comment' => '',
					'conv' => (($preview) ? '' : array('href'=> $a->get_baseurl($ssl_state) . '/display/' . $nickname . '/' . $item['id'], 'title'=> t('View in context'))),
					'previewing' => $previewing,
					'wait' => t('Please wait'),
					'thread_level' => 1,
				);

				$arr = array('item' => $item, 'output' => $tmp_item);
				call_hooks('display_item', $arr);

				$threads[$threadsid]['id'] = $item['item_id'];
				$threads[$threadsid]['items'] = array($arr['output']);

			}

		}
		else
		{
			// Normal View

            require_once('include/ConversationObject.php');
            require_once('include/ItemObject.php');

            $conv = new Conversation($mode, $preview);

            // get all the topmost parents
            // this shouldn't be needed, as we should have only them in our array
            // But for now, this array respects the old style, just in case

            $threads = array();
            foreach($items as $item) {

                // Can we put this after the visibility check?
                like_puller($a,$item,$alike,'like');

				if(feature_enabled($profile_owner,'dislike'))
	                like_puller($a,$item,$dlike,'dislike');

                // Only add what is visible
                if($item['network'] === NETWORK_MAIL && local_user() != $item['uid']) {
                    continue;
                }
                if(! visible_activity($item)) {
                    continue;
                }


				

                $item['pagedrop'] = $page_dropping;

				

                if($item['id'] == $item['parent']) {
                    $item_object = new Item($item);
                    $conv->add_thread($item_object);
                }
            }

            $threads = $conv->get_template_data($alike, $dlike);
            if(!$threads) {
                logger('[ERROR] conversation : Failed to get template data.', LOGGER_DEBUG);
                $threads = array();
            }

        }
    }


	if($page_mode === 'traditional' || $page_mode === 'preview') {
		$page_template = get_markup_template("threaded_conversation.tpl");
	}
	elseif($update) {
		$page_template = get_markup_template("convobj.tpl");
	}
	else {
		$page_template = get_markup_template("conv_frame.tpl");
		$threads = null;
	}

	if($page_mode === 'preview')
		logger('preview: ' . print_r($threads,true));


//	logger('nouveau: ' . print_r($threads,true));


    $o = replace_macros($page_template, array(
        '$baseurl' => $a->get_baseurl($ssl_state),
        '$live_update' => $live_update_div,
        '$remove' => t('remove'),
        '$mode' => $mode,
        '$user' => $a->user,
        '$threads' => $threads,
		'$wait' => t('Loading...'),
        '$dropping' => ($page_dropping?t('Delete Selected Items'):False),
    ));

	if($page_mode === 'preview')
		logger('preview: ' . $o);

    return $o;


}}


function best_link_url($item) {

	$a = get_app();

	$best_url = '';
	$sparkle  = false;

	$clean_url = normalise_link($item['author-link']);

	if((local_user()) && (local_user() == $item['uid'])) {
		if(isset($a->contacts) && x($a->contacts,$clean_url)) {
			if($a->contacts[$clean_url]['network'] === NETWORK_DFRN) {
				$best_url = $a->get_baseurl($ssl_state) . '/redir/' . $a->contacts[$clean_url]['id'];
				$sparkle = true;
			}
			else
				$best_url = $a->contacts[$clean_url]['url'];
		}
	}
	if(! $best_url) {
		if(strlen($item['author-link']))
			$best_url = $item['author-link'];
		else
			$best_url = $item['url'];
	}

	return $best_url;
}


if(! function_exists('item_photo_menu')){
function item_photo_menu($item){
	$a = get_app();
	$contact = null;

	$ssl_state = false;

	if(local_user()) {
		$ssl_state = true;
		if(! count($a->contacts))
			load_contact_links(local_user());
		$channel = $a->get_channel();
		$channel_hash = (($channel) ? $channel['channel_hash'] : '');
	}

	$sub_link="";
	$poke_link="";
	$contact_url="";
	$pm_url="";

	if((local_user()) && local_user() == $item['uid'] && $item['parent'] == $item['id'] 
		&& $channel && ($channel_hash != $item['author_xchan'])) {
		$sub_link = 'javascript:dosubthread(' . $item['id'] . '); return false;';
	}

    $profile_link = z_root() . "/chanview/?f=&hash=" . $item['author_xchan'];
	$pm_url = $a->get_baseurl($ssl_state) . '/message/new/?f=&hash=' . $item['author_xchan'];

	if($a->contacts && array_key_exists($item['author_xchan'],$a->contacts))
		$contact = $a->contacts[$item['author_xchan']];

	if($contact) {
		$poke_link = $a->get_baseurl($ssl_state) . '/poke/?f=&c=' . $contact['abook_id'];
		$contact_url = $a->get_baseurl($ssl_state) . '/connections/' . $contact['abook_id'];
		$posts_link = $a->get_baseurl($ssl_state) . '/network/?cid=' . $contact['abook_id'];

		$clean_url = normalise_link($item['author-link']);

	}

	$menu = Array(
		t("Follow Thread") => $sub_link,
		t("View Status") => $status_link,
		t("View Profile") => $profile_link,
		t("View Photos") => $photos_link,
		t("Network Posts") => $posts_link,
		t("Edit Contact") => $contact_url,
		t("Send PM") => $pm_url,
		t("Poke") => $poke_link
	);


	$args = array('item' => $item, 'menu' => $menu);

	call_hooks('item_photo_menu', $args);

	$menu = $args['menu'];

	$o = "";
	foreach($menu as $k=>$v){
		if(strpos($v,'javascript:') === 0) {
			$v = substr($v,11);
			$o .= "<li><a href=\"#\" onclick=\"$v\">$k</a></li>\n";
		}
		elseif ($v!="") $o .= "<li><a href=\"$v\">$k</a></li>\n";
	}
	return $o;
}}

if(! function_exists('like_puller')) {
function like_puller($a,$item,&$arr,$mode) {

	$url = '';
	$sparkle = '';
	$verb = (($mode === 'like') ? ACTIVITY_LIKE : ACTIVITY_DISLIKE);

	if((activity_match($item['verb'],$verb)) && ($item['id'] != $item['parent'])) {
		$url = $item['author']['xchan_url'];
		if((local_user()) && (local_user() == $item['uid']) && ($item['network'] === 'dfrn') && (! $item['self']) && (link_compare($item['author-link'],$item['url']))) {
			$url = $a->get_baseurl(true) . '/redir/' . $item['contact-id'];
			$sparkle = ' class="sparkle" ';
		}
		else
			$url = zid($url);

		if(! $item['thr_parent'])
			$item['thr_parent'] = $item['parent_uri'];

		if(! ((isset($arr[$item['thr_parent'] . '-l'])) && (is_array($arr[$item['thr_parent'] . '-l']))))
			$arr[$item['thr_parent'] . '-l'] = array();
		if(! isset($arr[$item['thr_parent']]))
			$arr[$item['thr_parent']] = 1;
		else
			$arr[$item['thr_parent']] ++;
		$arr[$item['thr_parent'] . '-l'][] = '<a href="'. $url . '"'. $sparkle .'>' . $item['author']['xchan_name'] . '</a>';
	}
	return;
}}

// Format the like/dislike text for a profile item
// $cnt = number of people who like/dislike the item
// $arr = array of pre-linked names of likers/dislikers
// $type = one of 'like, 'dislike'
// $id  = item id
// returns formatted text

if(! function_exists('format_like')) {
function format_like($cnt,$arr,$type,$id) {
	$o = '';
	if($cnt == 1)
		$o .= (($type === 'like') ? sprintf( t('%s likes this.'), $arr[0]) : sprintf( t('%s doesn\'t like this.'), $arr[0])) . EOL ;
	else {
		$spanatts = 'class="fakelink" onclick="openClose(\'' . $type . 'list-' . $id . '\');"';
		$o .= (($type === 'like') ?
					sprintf( t('<span  %1$s>%2$d people</span> like this.'), $spanatts, $cnt)
					 :
					sprintf( t('<span  %1$s>%2$d people</span> don\'t like this.'), $spanatts, $cnt) );
		$o .= EOL ;
		$total = count($arr);
		if($total >= MAX_LIKERS)
			$arr = array_slice($arr, 0, MAX_LIKERS - 1);
		if($total < MAX_LIKERS)
			$arr[count($arr)-1] = t('and') . ' ' . $arr[count($arr)-1];
		$str = implode(', ', $arr);
		if($total >= MAX_LIKERS)
			$str .= sprintf( t(', and %d other people'), $total - MAX_LIKERS );
		$str = (($type === 'like') ? sprintf( t('%s like this.'), $str) : sprintf( t('%s don\'t like this.'), $str));
		$o .= "\t" . '<div id="' . $type . 'list-' . $id . '" style="display: none;" >' . $str . '</div>';
	}
	return $o;
}}


function status_editor($a,$x,$popup=false) {

	$o = '';

	$geotag = (($x['allow_location']) ? replace_macros(get_markup_template('jot_geotag.tpl'), array()) : '');

	$plaintext = true;
	if(feature_enabled(local_user(),'richtext'))
		$plaintext = false;

	$tpl = get_markup_template('jot-header.tpl');
	
	$a->page['htmlhead'] .= replace_macros($tpl, array(
		'$newpost' => 'true',
		'$baseurl' => $a->get_baseurl(true),
		'$editselect' => (($plaintext) ? 'none' : '/(profile-jot-text|prvmail-text)/'),
		'$geotag' => $geotag,
		'$nickname' => $x['nickname'],
		'$ispublic' => t('Visible to <strong>everybody</strong>'),
		'$linkurl' => t('Please enter a link URL:'),
		'$vidurl' => t("Please enter a video link/URL:"),
		'$audurl' => t("Please enter an audio link/URL:"),
		'$term' => t('Tag term:'),
		'$fileas' => t('Save to Folder:'),
		'$whereareu' => t('Where are you right now?')
	));


	$tpl = get_markup_template("jot.tpl");

	$jotplugins = '';
	$jotnets = '';

	call_hooks('jot_tool', $jotplugins);
	call_hooks('jot_networks', $jotnets);

	$o .= replace_macros($tpl,array(
		'$return_path' => $a->query_string,
		'$action' =>  $a->get_baseurl(true) . '/item',
		'$share' => (x($x,'button') ? $x['button'] : t('Share')),
		'$upload' => t('Upload photo'),
		'$shortupload' => t('upload photo'),
		'$attach' => t('Attach file'),
		'$shortattach' => t('attach file'),
		'$weblink' => t('Insert web link'),
		'$shortweblink' => t('web link'),
		'$video' => t('Insert video link'),
		'$shortvideo' => t('video link'),
		'$audio' => t('Insert audio link'),
		'$shortaudio' => t('audio link'),
		'$setloc' => t('Set your location'),
		'$shortsetloc' => t('set location'),
		'$noloc' => t('Clear browser location'),
		'$shortnoloc' => t('clear location'),
		'$title' => "",
		'$placeholdertitle' => t('Set title'),
		'$catsenabled' => ((feature_enabled($x['profile_uid'],'categories')) ? 'categories' : ''),
		'$category' => "",
		'$placeholdercategory' => t('Categories (comma-separated list)'),
		'$wait' => t('Please wait'),
		'$permset' => t('Permission settings'),
		'$shortpermset' => t('permissions'),
		'$ptyp' => (($notes_cid) ? 'note' : 'wall'),
		'$content' => '',
		'$post_id' => '',
		'$baseurl' => $a->get_baseurl(true),
		'$defloc' => $x['default_location'],
		'$visitor' => $x['visitor'],
		'$pvisit' => (($notes_cid) ? 'none' : $x['visitor']),
		'$public' => t('Public post'),
		'$jotnets' => $jotnets,
		'$emtitle' => t('Example: bob@example.com, mary@example.com'),
		'$lockstate' => $x['lockstate'],
		'$acl' => $x['acl'],
		'$bang' => $x['bang'],
		'$profile_uid' => $x['profile_uid'],
		'$preview' => ((feature_enabled($x['profile_uid'],'preview')) ? t('Preview') : ''),
		'$sourceapp' => t($a->sourcename),
		'$jotplugins' => $jotplugins,
	));


	if ($popup==true){
		$o = '<div id="jot-popup" style="display: none;">'.$o.'</div>';

	}

	return $o;
}


function get_item_children($arr, $parent) {
	$children = array();
	foreach($arr as $item) {
		if($item['id'] != $item['parent']) {
			if(get_config('system','thread_allow')) {
				// Fallback to parent_uri if thr_parent is not set
				$thr_parent = $item['thr_parent'];
				if($thr_parent == '')
					$thr_parent = $item['parent_uri'];
				
				if($thr_parent == $parent['uri']) {
					$item['children'] = get_item_children($arr, $item);
					$children[] = $item;
				}
			}
			else if($item['parent'] == $parent['id']) {
				$children[] = $item;
			}
		}
	}
	return $children;
}

function sort_item_children($items) {
	$result = $items;
	usort($result,'sort_thr_created_rev');
	foreach($result as $k => $i) {
		if(count($result[$k]['children'])) {
			$result[$k]['children'] = sort_item_children($result[$k]['children']);
		}
	}
	return $result;
}

function add_children_to_list($children, &$arr) {
	foreach($children as $y) {
		$arr[] = $y;
		if(count($y['children']))
			add_children_to_list($y['children'], $arr);
	}
}

function conv_sort($arr,$order) {

	if((!(is_array($arr) && count($arr))))
		return array();

	$parents = array();
	$children = array();

	foreach($arr as $x)
		if($x['id'] == $x['parent'])
				$parents[] = $x;

	if(stristr($order,'created'))
		usort($parents,'sort_thr_created');
	elseif(stristr($order,'commented'))
		usort($parents,'sort_thr_commented');

	if(count($parents))
		foreach($parents as $i=>$_x)
			$parents[$i]['children'] = get_item_children($arr, $_x);

	if(count($parents)) {
		foreach($parents as $k => $v) {
			if(count($parents[$k]['children'])) {
				$parents[$k]['children'] = sort_item_children($parents[$k]['children']);
			}
		}
	}

	$ret = array();
	if(count($parents)) {
		foreach($parents as $x) {
			$ret[] = $x;
			if(count($x['children']))
				add_children_to_list($x['children'], $ret);
		}
	}

	return $ret;
}


function sort_thr_created($a,$b) {
	return strcmp($b['created'],$a['created']);
}

function sort_thr_created_rev($a,$b) {
	return strcmp($a['created'],$b['created']);
}

function sort_thr_commented($a,$b) {
	return strcmp($b['commented'],$a['commented']);
}

function find_thread_parent_index($arr,$x) {
	foreach($arr as $k => $v)
		if($v['id'] == $x['parent'])
			return $k;
	return false;
}

function format_location($item) {

	if(strpos($item['location'],'#') === 0) {
		$location = substr($item['location'],1);
		$location = ((strpos($location,'[') !== false) ? bbcode($location) : $location);
	}
	else {
		$locate = array('location' => $item['location'], 'coord' => $item['coord'], 'html' => '');
		call_hooks('render_location',$locate);
		$location = ((strlen($locate['html'])) ? $locate['html'] : render_location_default($locate));
	}
	return $location;
}

function render_location_default($item) {

	$location = $item['location'];
	$coord = $item['coord'];

	if($coord) {
		if($location)
			$location .= '<br /><span class="smalltext">(' . $coord . ')</span>';
		else
			$location = '<span class="smalltext">' . $coord . '</span>';
	}
	return $location;
}



function prepare_page($item) {
	return replace_macros(get_markup_template('page_display.tpl'),array(
		'$author' => $item['author']['xchan_name'],
		'$auth_url' => $item['author']['xchan_url'],
		'$date' => datetime_convert('UTC',date_default_timezone_get(),$item['created'],'Y-m-d H:i'),
		'$title' => smilies(bbcode($item['title'])),
		'$body' => smilies(bbcode($item['body']))
	));
}

