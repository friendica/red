<?php /** @file */

require_once('include/items.php');

// Note: the code in 'item_extract_images' and 'item_redir_and_replace_images'
// is identical to the code in mod/message.php for 'item_extract_images' and
// 'item_redir_and_replace_images'


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
}


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
}



/**
 * Render actions localized
 */

function localize_item(&$item){

	if (activity_match($item['verb'],ACTIVITY_LIKE) || activity_match($item['verb'],ACTIVITY_DISLIKE)){
	
		if(! $item['object'])
			return;
	
		$obj = json_decode_plus($item['object']);
		if((! $obj) && ($item['object'])) {
			logger('localize_item: failed to decode object: ' . print_r($item['object'],true));
		}
		
		if($obj['author'] && $obj['author']['link'])
			$author_link = get_rel_link($obj['author']['link'],'alternate');
		else
			$author_link = '';

		$author_name = (($obj['author'] && $obj['author']['name']) ? $obj['author']['name'] : '');

		$item_url = get_rel_link($obj['link'],'alternate');

		$Bphoto = '';

		switch($obj['type']) {
			case ACTIVITY_OBJ_PHOTO:
				$post_type = t('photo');
				break;
			case ACTIVITY_OBJ_EVENT:
				$post_type = t('event');
				break;
			case ACTIVITY_OBJ_PERSON:
				$post_type = t('channel');
				$author_name = $obj['title'];
				if($obj['link']) {
					$author_link  = get_rel_link($obj['link'],'alternate');
					$Bphoto = get_rel_link($obj['link'],'photo');
				}
				break;
			case ACTIVITY_OBJ_THING:
				$post_type = $obj['title'];
				if($obj['owner']) {
					if(array_key_exists('name',$obj['owner']))
						$obj['owner']['name'];
					if(array_key_exists('link',$obj['owner']))
						$author_link = get_rel_link($obj['owner']['link'],'alternate');
				}
				if($obj['link']) {
					$Bphoto = get_rel_link($obj['link'],'photo');
				}
				break;

			case ACTIVITY_OBJ_NOTE:
			default:
				$post_type = t('status');
				if($obj['mid'] != $obj['parent_mid'])
					$post_type = t('comment');
				break;
		}

		// If we couldn't parse something useful, don't bother translating.
		// We need something better than zid here, probably magic_link(), but it needs writing

		if($author_link && $author_name && $item_url) {
			$author	 = '[zrl=' . chanlink_url($item['author']['xchan_url']) . ']' . $item['author']['xchan_name'] . '[/zrl]';
			$objauthor =  '[zrl=' . chanlink_url($author_link) . ']' . $author_name . '[/zrl]';
		
			$plink = '[zrl=' . zid($item_url) . ']' . $post_type . '[/zrl]';

			if(activity_match($item['verb'],ACTIVITY_LIKE)) {
				$bodyverb = t('%1$s likes %2$s\'s %3$s');
			}
			elseif(activity_match($item['verb'],ACTIVITY_DISLIKE)) {
				$bodyverb = t('%1$s doesn\'t like %2$s\'s %3$s');
			}
			$item['body'] = $item['localize'] = sprintf($bodyverb, $author, $objauthor, $plink);
			if($Bphoto != "") 
				$item['body'] .= "\n\n\n" . '[zrl=' . chanlink_url($author_link) . '][zmg=80x80]' . $Bphoto . '[/zmg][/zrl]';

		}
		else {
			logger('localize_item like failed: link ' . $author_link . ' name ' . $author_name . ' url ' . $item_url);
		}

	}

	if (activity_match($item['verb'],ACTIVITY_FRIEND)) {


//		if ($item['obj_type']=="" || $item['obj_type']!== ACTIVITY_OBJ_PERSON) return;

		$Aname = $item['author']['xchan_name'];
		$Alink = $item['author']['xchan_url'];


		$obj= json_decode_plus($item['object']);
		
		$Blink = $Bphoto = '';

		if($obj['link']) {
			$Blink  = get_rel_link($obj['link'],'alternate');
			$Bphoto = get_rel_link($obj['link'],'photo');
		}
		$Bname = $obj['title'];


		$A = '[zrl=' . chanlink_url($Alink) . ']' . $Aname . '[/zrl]';
		$B = '[zrl=' . chanlink_url($Blink) . ']' . $Bname . '[/zrl]';
		if ($Bphoto!="") $Bphoto = '[zrl=' . chanlink_url($Blink) . '][zmg=80x80]' . $Bphoto . '[/zmg][/zrl]';

		$item['body'] = $item['localize'] = sprintf( t('%1$s is now connected with %2$s'), $A, $B);
		$item['body'] .= "\n\n\n" . $Bphoto;
	}

	if (stristr($item['verb'],ACTIVITY_POKE)) {

		// FIXME for obscured private posts, until then leave untranslated
		return;

		$verb = urldecode(substr($item['verb'],strpos($item['verb'],'#')+1));
		if(! $verb)
			return;
		if ($item['obj_type']=="" || $item['obj_type']!== ACTIVITY_OBJ_PERSON) return;

		$Aname = $item['author']['xchan_name'];
		$Alink = $item['author']['xchan_url'];


		$obj= json_decode_plus($item['object']);
		
		$Blink = $Bphoto = '';

		if($obj['link']) {
			$Blink  = get_rel_link($obj['link'],'alternate');
			$Bphoto = get_rel_link($obj['link'],'photo');
		}
		$Bname = $obj['title'];

		$A = '[zrl=' . chanlink_url($Alink) . ']' . $Aname . '[/zrl]';
		$B = '[zrl=' . chanlink_url($Blink) . ']' . $Bname . '[/zrl]';
		if ($Bphoto!="") $Bphoto = '[zrl=' . chanlink_url($Blink) . '][zmg=80x80]' . $Bphoto . '[/zmg][/zrl]';

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

		$A = '[zrl=' . chanlink_url($Alink) . ']' . $Aname . '[/zrl]';
		
		$txt = t('%1$s is %2$s','mood');

		$item['body'] = sprintf($txt, $A, t($verb));
	}
/*
// FIXME store parent item as object or target
// (and update to json storage)

 	if (activity_match($item['verb'],ACTIVITY_TAG)) {
		$r = q("SELECT * from `item`,`contact` WHERE 
		`item`.`contact-id`=`contact`.`id` AND `item`.`mid`='%s';",
		 dbesc($item['parent_mid']));
		if(count($r)==0) return;
		$obj=$r[0];
		
		$author	 = '[zrl=' . zid($item['author-link']) . ']' . $item['author-name'] . '[/zrl]';
		$objauthor =  '[zrl=' . zid($obj['author-link']) . ']' . $obj['author-name'] . '[/zrl]';
		
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
					$m=array(); preg_match("/\[[zu]rl=([^]]*)\]/", $obj['body'], $m);
					$rr['plink'] = $m[1];
				} else {
					$post_type = t('status');
				}
		}
		$plink = '[zrl=' . $obj['plink'] . ']' . $post_type . '[/zrl]';

		$parsedobj = parse_xml_string($xmlhead.$item['object']);

		$tag = sprintf('#[zrl=%s]%s[/zrl]', $parsedobj->id, $parsedobj->content);
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
			$r = q("select * from item where mid = '%s' and uid = %d limit 1",
					dbesc($obj->id),
					intval($item['uid'])
			);
			if(count($r) && $r[0]['plink']) {
				$target = $r[0];
				$Bname = $target['author-name'];
				$Blink = $target['author-link'];
				$A = '[zrl=' . zid($Alink) . ']' . $Aname . '[/zrl]';
				$B = '[zrl=' . zid($Blink) . ']' . $Bname . '[/zrl]';
				$P = '[zrl=' . $target['plink'] . ']' . t('post/item') . '[/zrl]';
				$item['body'] = sprintf( t('%1$s marked %2$s\'s %3$s as favorite'), $A, $B, $P)."\n";

			}
		}
	}
*/

/*
	$matches = null;
	if(strpos($item['body'],'[zrl') !== false) {
		if(preg_match_all('/@\[zrl=(.*?)\]/is',$item['body'],$matches,PREG_SET_ORDER)) {
			foreach($matches as $mtch) {
				if(! strpos($mtch[1],'zid='))
					$item['body'] = str_replace($mtch[0],'@[zrl=' . zid($mtch[1]). ']',$item['body']);
			}
		}
	}

	if(strpos($item['body'],'[zmg') !== false) {
		// add zid's to public images
		if(preg_match_all('/\[zrl=(.*?)\/photos\/(.*?)\/image\/(.*?)\]\[zmg(.*?)\]h(.*?)\[\/zmg\]\[\/zrl\]/is',$item['body'],$matches,PREG_SET_ORDER)) {
			foreach($matches as $mtch) {
				$item['body'] = str_replace($mtch[0],'[zrl=' . zid( $mtch[1] . '/photos/' . $mtch[2] . '/image/' . $mtch[3]) . '][zmg' . $mtch[4] . ']h' . $mtch[5]  . '[/zmg][/zrl]',$item['body']);
			}
		}
	}
*/
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

	// likes can apply to other things besides posts. Check if they are post children, in which case we handle them specially

	if((activity_match($item['verb'],ACTIVITY_LIKE) || activity_match($item['verb'],ACTIVITY_DISLIKE)) && ($item['mid'] != $item['parent_mid']))
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


function conversation(&$a, $items, $mode, $update, $page_mode = 'traditional', $prepared_item = '') {

	$tstart = dba_timer();
	$t0 = $t1 = $t2 = $t3 = $t4 = $t5 = $t6 = null;
	$content_html = '';
	$o = '';

	require_once('bbcode.php');

	$ssl_state = ((local_user()) ? true : false);

	if(local_user())
		load_pconfig(local_user(),'');

	$arr_blocked = null;

	if(local_user()) {
		$str_blocked = get_pconfig(local_user(),'system','blocked');
		if($str_blocked) {
			$arr_blocked = explode(',',$str_blocked);
			for($x = 0; $x < count($arr_blocked); $x ++)
				$arr_blocked[$x] = trim($arr_blocked[$x]);
		}

	}


	$profile_owner = 0;
	$page_writeable      = false;
	$live_update_div = '';

	$preview = (($page_mode === 'preview') ? true : false);
	$previewing = (($preview) ? ' preview ' : '');

	if($mode === 'network') {

		$t1 = dba_timer();

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
                . ((x($_GET,'uri'))    ? '&uri='    . $_GET['uri']   : '')

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
		$profile_owner = local_user();
		$page_writeable = false;

	      $live_update_div = '<div id="live-display"></div>' . "\r\n";

	}

	elseif($mode === 'page') {
		$profile_owner = $a->profile['uid'];
		$page_writeable = ($profile_owner == local_user());
		$live_update_div = '<div id="live-page"></div>' . "\r\n";
	}


    elseif($mode === 'search') {
        $live_update_div = '<div id="live-search"></div>' . "\r\n";
    }
	elseif($mode === 'photos') {
		$profile_onwer = $a->profile['profile_uid'];
		$page_writeable = ($profile_owner == local_user());
		$live_update_div = '<div id="live-photos"></div>' . "\r\n";
		// for photos we've already formatted the top-level item (the photo)
		$content_html = $a->data['photo_html'];
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

				if($arr_blocked) {
					$blocked = false;
					foreach($arr_blocked as $b) {
						if(($b) && ($item['author_xchan'] == $b)) {
							$blocked = true;
							break;
						}
					}
					if($blocked)
						continue;
				}

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
				$isstarred = "unstarred icon-star-empty";
				
				$lock = (($item['item_private'] || strlen($item['allow_cid']) || strlen($item['allow_gid']) || strlen($item['deny_cid']) || strlen($item['deny_gid']))
					? t('Private Message')
					: false
				);

				$likebuttons = false;
				$shareable = false;

				$verified = (($item['item_flags'] & ITEM_VERIFIED) ? t('Message is verified') : '');
				$unverified = '';



				$tags=array();
				$terms = get_terms_oftype($item['term'],array(TERM_HASHTAG,TERM_MENTION,TERM_UNKNOWN));
				if(count($terms))
					foreach($terms as $tag)
						$tags[] = format_term_for_display($tag);


				$body = prepare_body($item,true);

				//$tmp_item = replace_macros($tpl,array(
				$tmp_item = array(
					'template' => $tpl,
					'toplevel' => 'toplevel_item',
					'mode' => $mode,
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
					'verified' => $verified,
					'unverified' => $unverified,
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
					'isotime' => datetime_convert('UTC', date_default_timezone_get(), $item['created'], 'c'),
					'localtime' => datetime_convert('UTC', date_default_timezone_get(), $item['created'], 'r'),
					'editedtime' => (($item['edited'] != $item['created']) ? sprintf( t('last edited: %s'), datetime_convert('UTC', date_default_timezone_get(), $item['edited'], 'r')) : ''),
					'expiretime' => (($item['expires'] !== '0000-00-00 00:00:00') ? sprintf( t('Expires: %s'), datetime_convert('UTC', date_default_timezone_get(), $item['expires'], 'r')):''),
					'location' => $location,
					'indent' => '',
					'owner_name' => $owner_name,
					'owner_url' => $owner_url,
					'owner_photo' => $owner_photo,
					'plink' => get_plink($item,false),
					'edpost' => false,
					'isstarred' => $isstarred,
					'star' => $star,
					'drop' => $drop,
					'vote' => $likebuttons,
					'like' => '',
					'dislike' => '',
					'comment' => '',
					'conv' => (($preview) ? '' : array('href'=> z_root() . '/display/' . $item['mid'], 'title'=> t('View in context'))),
					'previewing' => $previewing,
					'wait' => t('Please wait'),
					'thread_level' => 1,
				);

				$arr = array('item' => $item, 'output' => $tmp_item);
				call_hooks('display_item', $arr);

//				$threads[$threadsid]['id'] = $item['item_id'];
				$threads[] = $arr['output'];

			}

		}
		else
		{

			// Normal View
//			logger('conv: items: ' . print_r($items,true));

            require_once('include/ConversationObject.php');
            require_once('include/ItemObject.php');

            $conv = new Conversation($mode, $preview, $prepared_item);

			// In the display mode we don't have a profile owner. 

			if($mode === 'display' && $items)
				$conv->set_profile_owner($items[0]['uid']);


            // get all the topmost parents
            // this shouldn't be needed, as we should have only them in our array
            // But for now, this array respects the old style, just in case

            $threads = array();
            foreach($items as $item) {

				// Check for any blocked authors

				if($arr_blocked) {
					$blocked = false;
					foreach($arr_blocked as $b) {
						if(($b) && ($item['author_xchan'] == $b)) {
							$blocked = true;
							break;
						}
					}
					if($blocked)
						continue;
				}
							
				// Check all the kids too

				if($arr_blocked && $item['children']) {
					for($d = 0; $d < count($item['children']); $d ++) {
						foreach($arr_blocked as $b) {
							if(($b) && ($item['children'][$d]['author_xchan'] == $b))
								$item['children'][$d]['author_blocked'] = true;
						}
					}
				}



                like_puller($a,$item,$alike,'like');

				if(feature_enabled($profile_owner,'dislike'))
	                like_puller($a,$item,$dlike,'dislike');

                if(! visible_activity($item)) {
                    continue;
                }

                $item['pagedrop'] = $page_dropping;

                if($item['id'] == $item['parent']) {
//					$tx1 = dba_timer();
                    $item_object = new Item($item);
                    $conv->add_thread($item_object);
					if($page_mode === 'list') 
						$item_object->set_template('conv_list.tpl');

//					$tx2 = dba_timer();
//					if($mode === 'network')
//						profiler($tx1,$tx2,'add thread ' . $item['id']);
                }
            }
			$t2 = dba_timer();
            $threads = $conv->get_template_data($alike, $dlike);
            if(!$threads) {
                logger('[ERROR] conversation : Failed to get template data.', LOGGER_DEBUG);
                $threads = array();
            }
			$t3 = dba_timer();
			if($mode === 'network') {
				profiler($t1,$t2,'Conversation prepare');
				profiler($t2,$t3,'Conversation get_template');
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

//  Do not un-comment if smarty3 is in use
//	logger('page_template: ' . $page_template);

//	logger('nouveau: ' . print_r($threads,true));


    $o .= replace_macros($page_template, array(
        '$baseurl' => $a->get_baseurl($ssl_state),
		'$photo_item' => $content_html,
        '$live_update' => $live_update_div,
        '$remove' => t('remove'),
        '$mode' => $mode,
        '$user' => $a->user,
        '$threads' => $threads,
		'$wait' => t('Loading...'),
        '$dropping' => ($page_dropping?t('Delete Selected Items'):False),
    ));

	if($mode === 'network') {
		$t4 = dba_timer();
		profiler($t3,$t4,'conversation template');
	}

	if($page_mode === 'preview')
		logger('preview: ' . $o);

    return $o;


}


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



function item_photo_menu($item){
	$a = get_app();
	$contact = null;

	$ssl_state = false;

	$sub_link="";
	$poke_link="";
	$contact_url="";
	$pm_url="";
	$vsrc_link = "";

	if(local_user()) {
		$ssl_state = true;
		if(! count($a->contacts))
			load_contact_links(local_user());
		$channel = $a->get_channel();
		$channel_hash = (($channel) ? $channel['channel_hash'] : '');
	}

	if((local_user()) && local_user() == $item['uid']) {
		$vsrc_link = $a->get_baseurl() . '/viewsrc/' . $item['id'];
		if($item['parent'] == $item['id'] && $channel && ($channel_hash != $item['author_xchan'])) {
			$sub_link = 'javascript:dosubthread(' . $item['id'] . '); return false;';
		}
	}

    $profile_link = chanlink_hash($item['author_xchan']);
	$pm_url = $a->get_baseurl($ssl_state) . '/mail/new/?f=&hash=' . $item['author_xchan'];

	if($a->contacts && array_key_exists($item['author_xchan'],$a->contacts))
		$contact = $a->contacts[$item['author_xchan']];

	if($contact) {
		$poke_link = $a->get_baseurl($ssl_state) . '/poke/?f=&c=' . $contact['abook_id'];
		$contact_url = $a->get_baseurl($ssl_state) . '/connedit/' . $contact['abook_id'];
		$posts_link = $a->get_baseurl($ssl_state) . '/network/?cid=' . $contact['abook_id'];

		$clean_url = normalise_link($item['author-link']);

	}

	$menu = Array(
		t("View Source") => $vsrc_link,
		t("Follow Thread") => $sub_link,
		t("View Status") => $status_link,
		t("View Profile") => $profile_link,
		t("View Photos") => $photos_link,
		t("Matrix Activity") => $posts_link,
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
}


function like_puller($a,$item,&$arr,$mode) {

	$url = '';
	$sparkle = '';
	$verb = (($mode === 'like') ? ACTIVITY_LIKE : ACTIVITY_DISLIKE);

	if((activity_match($item['verb'],$verb)) && ($item['id'] != $item['parent'])) {
		$url = chanlink_url($item['author']['xchan_url']);

		if(! $item['thr_parent'])
			$item['thr_parent'] = $item['parent_mid'];

		if(! ((isset($arr[$item['thr_parent'] . '-l'])) && (is_array($arr[$item['thr_parent'] . '-l']))))
			$arr[$item['thr_parent'] . '-l'] = array();
		if(! isset($arr[$item['thr_parent']]))
			$arr[$item['thr_parent']] = 1;
		else
			$arr[$item['thr_parent']] ++;
		$arr[$item['thr_parent'] . '-l'][] = '<a href="'. $url . '">' . $item['author']['xchan_name'] . '</a>';
	}
	return;
}

// Format the like/dislike text for a profile item
// $cnt = number of people who like/dislike the item
// $arr = array of pre-linked names of likers/dislikers
// $type = one of 'like, 'dislike'
// $id  = item id
// returns formatted text


function format_like($cnt,$arr,$type,$id) {
	$o = '';
	if($cnt == 1)
		$o .= (($type === 'like') ? sprintf( t('%s likes this.'), $arr[0]) : sprintf( t('%s doesn\'t like this.'), $arr[0])) . EOL ;
	else {
		$spanatts = 'class="fakelink" onclick="openClose(\'' . $type . 'list-' . $id . '\');"';
		$o .= (($type === 'like') ?
					sprintf( tt('<span  %1$s>%2$d people</span> like this.','<span  %1$s>%2$d people</span> like this.',$cnt), $spanatts, $cnt)
					 :
					sprintf( tt('<span  %1$s>%2$d people</span> don\'t like this.','<span  %1$s>%2$d people</span> don\'t like this.',$cnt), $spanatts, $cnt) );
		$o .= EOL ;
		$total = count($arr);
		if($total >= MAX_LIKERS)
			$arr = array_slice($arr, 0, MAX_LIKERS - 1);
		if($total < MAX_LIKERS)
			$arr[count($arr)-1] = t('and') . ' ' . $arr[count($arr)-1];
		$str = implode(', ', $arr);
		if($total >= MAX_LIKERS)
			$str .= sprintf( tt(', and %d other people',', and %d other people',$total - MAX_LIKERS), $total - MAX_LIKERS );
		$str = (($type === 'like') ? sprintf( t('%s like this.'), $str) : sprintf( t('%s don\'t like this.'), $str));
		$o .= "\t" . '<div id="' . $type . 'list-' . $id . '" style="display: none;" >' . $str . '</div>';
	}
	return $o;
}


function status_editor($a,$x,$popup=false) {

	$o = '';

	$geotag = (($x['allow_location']) ? replace_macros(get_markup_template('jot_geotag.tpl'), array()) : '');

	$plaintext = true;

	if(feature_enabled(local_user(),'richtext'))
		$plaintext = false;

	$mimeselect = '';
	if(array_key_exists('mimetype',$x) && $x['mimetype']) {
		if($x['mimetype'] != 'text/bbcode')
			$plaintext = true;
 		if($x['mimetype'] === 'choose') {
			$mimeselect = mimetype_select($x['profile_uid']);
		}
		else
			$mimeselect = '<input type="hidden" name="mimetype" value="' . $x['mimetype'] . '" />'; 			
	}

	$layoutselect = '';
	if(array_key_exists('layout',$x) && $x['layout']) {
 		if($x['layout'] === 'choose') {
			$layoutselect = layout_select($x['profile_uid']);
		}
		else
			$layoutselect = '<input type="hidden" name="layout_mid" value="' . $x['layout'] . '" />'; 			
	}
	

	if(array_key_exists('channel_select',$x) && $x['channel_select']) {
		require_once('include/identity.php');
		$id_select = identity_selector();
	}
	else
		$id_select = '';


	$webpage = ((x($x,'webpage')) ? $x['webpage'] : '');

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
		'$whereareu' => t('Where are you right now?'),
		'$expireswhen' => t('Expires YYYY-MM-DD HH:MM')
	));


	$tpl = get_markup_template("jot.tpl");

	$jotplugins = '';
	$jotnets = '';


	$preview = ((feature_enabled($x['profile_uid'],'preview')) ? t('Preview') : '');
	if(x($x,'nopreview'))
		$preview = '';

	$cipher = get_pconfig($x['profile_uid'],'system','default_cipher');
	if(! $cipher)
		$cipher = 'aes256';

	call_hooks('jot_tool', $jotplugins);
	call_hooks('jot_networks', $jotnets);

	$o .= replace_macros($tpl,array(
		'$return_path' => ((x($x,'return_path')) ? $x['return_path'] : $a->query_string),
		'$action' =>  $a->get_baseurl(true) . '/item',
		'$share' => (x($x,'button') ? $x['button'] : t('Share')),
		'$webpage' => $webpage,
		'$placeholdpagetitle' => ((x($x,'ptlabel')) ? $x['ptlabel'] : t('Page link title')),
		'$pagetitle' => (x($x,'pagetitle') ? $x['pagetitle'] : ''),		
		'$id_select' => $id_select,
		'$id_seltext' => t('Post as'),
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
		'$title' => ((x($x,'title')) ? htmlspecialchars($x['title'], ENT_COMPAT,'UTF-8') : ''),
		'$placeholdertitle' => t('Set title'),
		'$catsenabled' => ((feature_enabled($x['profile_uid'],'categories') && (! $webpage)) ? 'categories' : ''),
		'$category' => "",
		'$placeholdercategory' => t('Categories (comma-separated list)'),
		'$wait' => t('Please wait'),
		'$permset' => t('Permission settings'),
		'$shortpermset' => t('permissions'),
		'$ptyp' => (($notes_cid) ? 'note' : 'wall'),
		'$content' => ((x($x,'body')) ? htmlspecialchars($x['body'], ENT_COMPAT,'UTF-8') : ''),
		'$post_id' => '',
		'$baseurl' => $a->get_baseurl(true),
		'$defloc' => $x['default_location'],
		'$visitor' => $x['visitor'],
		'$public' => t('Public post'),
		'$jotnets' => $jotnets,
		'$emtitle' => t('Example: bob@example.com, mary@example.com'),
		'$lockstate' => $x['lockstate'],
		'$acl' => $x['acl'],
		'$mimeselect' => $mimeselect,
		'$layoutselect' => $layoutselect,
		'$showacl' => ((array_key_exists('showacl',$x)) ? $x['showacl'] : true),
		'$bang' => $x['bang'],
		'$profile_uid' => $x['profile_uid'],
		'$preview' => $preview,
		'$source' => ((x($x,'source')) ? $x['source'] : ''),
		'$jotplugins' => $jotplugins,
		'$defexpire' => '',
		'$feature_expire' => ((feature_enabled($x['profile_uid'],'content_expire') && (! $webpage)) ? true : false),
		'$expires' => t('Set expiration date'),
		'$feature_encrypt' => ((feature_enabled($x['profile_uid'],'content_encrypt') && (! $webpage)) ? true : false),
		'$encrypt' => t('Encrypt text'),
		'$cipher' => $cipher,
		'$expiryModalOK' => t('OK'),
		'$expiryModalCANCEL' => t('Cancel')
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
				// Fallback to parent_mid if thr_parent is not set
				$thr_parent = $item['thr_parent'];
				if($thr_parent == '')
					$thr_parent = $item['parent_mid'];
				
				if($thr_parent == $parent['mid']) {
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
	elseif(stristr($order,'ascending'))
		usort($parents,'sort_thr_created_rev');


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
			$location .= '&nbsp;<span class="smalltext">(' . $coord . ')</span>';
		else
			$location = '<span class="smalltext">' . $coord . '</span>';
	}
	return $location;
}



function prepare_page($item) {
	$a = get_app();
	$naked = ((get_pconfig($item['uid'],'system','nakedpage')) ? 1 : 0);
	$observer = $a->get_observer();
	//240 chars is the longest we can have before we start hitting problems with suhosin sites
	$preview = substr(urlencode($item['body']), 0, 240);
	$link = z_root() . '/' . $a->cmd;
	if(array_key_exists('webpage',$a->layout) && array_key_exists('authored',$a->layout['webpage'])) {
		if($a->layout['webpage']['authored'] === 'none')
			$naked = 1;
		// ... other possible options
	}
	return replace_macros(get_markup_template('page_display.tpl'),array(
		'$author' => (($naked) ? '' : $item['author']['xchan_name']),
		'$auth_url' => (($naked) ? '' : zid($item['author']['xchan_url'])),
		'$date' => (($naked) ? '' : datetime_convert('UTC',date_default_timezone_get(),$item['created'],'Y-m-d H:i')),
		'$title' => smilies(bbcode($item['title'])),
		'$body' => prepare_body($item,true),
		'$preview' => $preview,
		'$link' => $link,
	));
}


function network_tabs() {
	$a = get_app();
	$no_active='';
	$starred_active = '';
	$new_active = '';
	$all_active = '';
	$search_active = '';
	$conv_active = '';
	$spam_active = '';
	$postord_active = '';
	$public_active = '';

	if(x($_GET,'new')) {
		$new_active = 'active';
	}
	
	if(x($_GET,'search')) {
		$search_active = 'active';
	}
	
	if(x($_GET,'star')) {
		$starred_active = 'active';
	}
	
	if(x($_GET,'conv')) {
		$conv_active = 'active';
	}

	if(x($_GET,'spam')) {
		$spam_active = 'active';
	}

	if(x($_GET,'fh')) {
		$public_active = 'active';
	}

	
	
	if (($new_active == '') 
		&& ($starred_active == '') 
		&& ($conv_active == '')
		&& ($search_active == '')
		&& ($spam_active == '')
		&& ($public_active == '')) {
			$no_active = 'active';
	}

	if ($no_active=='active' && x($_GET,'order')) {
		switch($_GET['order']){
		 case 'post': $postord_active = 'active'; $no_active=''; break;
		 case 'comment' : $all_active = 'active'; $no_active=''; break;
		}
	}
	
	if ($no_active=='active') $all_active='active';

	$cmd = $a->cmd;

	// tabs
	$tabs = array();

	if(! get_config('system','disable_discover_tab')) {
		$tabs[] = array(
			'label' => t('Discover'),
			'url'=>$a->get_baseurl(true) . '/' . $cmd . '?f=&fh=1' . ((x($_GET,'cid')) ? '&cid=' . $_GET['cid'] : '') . ((x($_GET,'gid')) ? '&gid=' . $_GET['gid'] : ''),
			'sel'=> $public_active,
			'title'=> t('Imported public streams'),
		);
	}

	$tabs[] = array(
		'label' => t('Commented Order'),
		'url'=>$a->get_baseurl(true) . '/' . $cmd . '?f=&order=comment' . ((x($_GET,'cid')) ? '&cid=' . $_GET['cid'] : '') . ((x($_GET,'gid')) ? '&gid=' . $_GET['gid'] : ''), 
		'sel'=>$all_active,
		'title'=> t('Sort by Comment Date'),
	);
	
	$tabs[] = array(
		'label' => t('Posted Order'),
		'url'=>$a->get_baseurl(true) . '/' . $cmd . '?f=&order=post' . ((x($_GET,'cid')) ? '&cid=' . $_GET['cid'] : '') . ((x($_GET,'gid')) ? '&gid=' . $_GET['gid'] : ''), 
		'sel'=>$postord_active,
		'title' => t('Sort by Post Date'),
	);

	if(feature_enabled(local_user(),'personal_tab')) {
		$tabs[] = array(
			'label' => t('Personal'),
			'url' => $a->get_baseurl(true) . '/' . $cmd . '?f=' . ((x($_GET,'cid')) ? '&cid=' . $_GET['cid'] : '') . '&conv=1',
			'sel' => $conv_active,
			'title' => t('Posts that mention or involve you'),
		);
	}

	if(feature_enabled(local_user(),'new_tab')) { 
		$tabs[] = array(
			'label' => t('New'),
			'url' => $a->get_baseurl(true) . '/' . $cmd . '?f=' . ((x($_GET,'cid')) ? '&cid=' . $_GET['cid'] : '') . '&new=1' . ((x($_GET,'gid')) ? '&gid=' . $_GET['gid'] : ''),
			'sel' => $new_active,
			'title' => t('Activity Stream - by date'),
		);
	}

	if(feature_enabled(local_user(),'star_posts')) {
		$tabs[] = array(
			'label' => t('Starred'),
			'url'=>$a->get_baseurl(true) . '/' . $cmd . ((x($_GET,'cid')) ? '/?f=&cid=' . $_GET['cid'] : '') . '&star=1',
			'sel'=>$starred_active,
			'title' => t('Favourite Posts'),
		);
	}
	// Not yet implemented

	if(feature_enabled(local_user(),'spam_filter')) {
		$tabs[] = array(
			'label' => t('Spam'),
			'url'=>$a->get_baseurl(true) . '/network?f=&spam=1',
			'sel'=> $spam_active,
			'title' => t('Posts flagged as SPAM'),
		);
	}	

	$arr = array('tabs' => $tabs);
	call_hooks('network_tabs', $arr);

	$tpl = get_markup_template('common_tabs.tpl');

	return replace_macros($tpl,array('$tabs' => $arr['tabs']));

}



function profile_tabs($a, $is_owner=False, $nickname=Null){
	//echo "<pre>"; var_dump($a->user); killme();

		
	$channel = $a->get_channel();

	if (is_null($nickname))
		$nickname  = $channel['channel_address'];

	$uid = (($a->profile['profile_uid']) ? $a->profile['profile_uid'] : local_user());
		
	if(x($_GET,'tab'))
		$tab = notags(trim($_GET['tab']));
	
	$url = $a->get_baseurl() . '/channel/' . $nickname;
	$pr  = $a->get_baseurl() . '/profile/' . $nickname;

	$tabs = array(
		array(
			'label' => t('Channel'),
			'url'   => $url,
			'sel'   => ((argv(0) == 'channel') ? 'active' : ''),
			'title' => t('Status Messages and Posts'),
			'id'    => 'status-tab',
		),
	);

	$p = get_all_perms($uid,get_observer_hash());

	if($p['view_profile']) {
		$tabs[] = array(
			'label' => t('About'),
			'url' 	=> $pr,
			'sel'	=> ((argv(0) == 'profile') ? 'active' : ''),
			'title' => t('Profile Details'),
			'id'    => 'profile-tab',
		);
	}
	if($p['view_photos']) {
		$tabs[] = array(
			'label' => t('Photos'),
			'url'	=> $a->get_baseurl() . '/photos/' . $nickname,
			'sel'	=> ((argv(0) == 'photos') ? 'active' : ''),
			'title' => t('Photo Albums'),
			'id'    => 'photo-tab',
		);
	}
	if($p['view_storage']) {
		$tabs[] = array(
			'label' => t('Files'),
			'url'	=> $a->get_baseurl() . '/cloud/' . $nickname . ((get_observer_hash()) ? '' : '?f=&davguest=1'),
			'sel'	=> ((argv(0) == 'cloud') ? 'active' : ''),
			'title' => t('Files and Storage'),
			'id'    => 'files-tab',
		);
	}

	require_once('include/chat.php');
	$chats = chatroom_list($uid);
	$subdued = ((count($chats)) ? '' : ' subdued');
	$tabs[] = array(
		'label' => t('Chatrooms'),
		'url'	=> $a->get_baseurl() . '/chat/' . $nickname,
		'sel' 	=> ((argv(0) == 'chat') ? 'active' . $subdued : '' . $subdued),
		'title' => t('Chatrooms'),
		'id'    => 'chat-tab',
	);


	if($is_owner) {
		$tabs[] = array(
			'label' => t('Events'),
			'url'	=> $a->get_baseurl() . '/events',
			'sel' 	=> ((argv(0) == 'events') ? 'active' : ''),
			'title' => t('Events and Calendar'),
			'id'    => 'events-tab',
		);

		$tabs[] = array(
			'label' => t('Bookmarks'),
			'url'	=> $a->get_baseurl() . '/bookmarks',
			'sel' 	=> ((argv(0) == 'bookmarks') ? 'active' : ''),
			'title' => t('Saved Bookmarks'),
			'id'    => 'bookmarks-tab',
		);
	}


	if($is_owner && feature_enabled($uid,'webpages')) {
		$tabs[] = array(
			'label' => t('Webpages'),
			'url'	=> $a->get_baseurl() . '/webpages/' . $nickname,
			'sel' 	=> ((argv(0) == 'webpages') ? 'active' : ''),
			'title' => t('Manage Webpages'),
			'id'    => 'webpages-tab',
		);
	}

	else {
		// FIXME
		// we probably need a listing of events that were created by 
		// this channel and are visible to the observer


	}


	$arr = array('is_owner' => $is_owner, 'nickname' => $nickname, 'tab' => (($tab) ? $tab : false), 'tabs' => $tabs);
	call_hooks('profile_tabs', $arr);
	
	$tpl = get_markup_template('common_tabs.tpl');

	return replace_macros($tpl,array('$tabs' => $arr['tabs']));
}
