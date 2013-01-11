<?php

require_once('include/bbcode.php');
require_once('include/oembed.php');
require_once('include/crypto.php');
require_once('include/Photo.php');



function collect_recipients($item,&$private) {

	require_once('include/group.php');

	if($item['allow_cid'] || $item['allow_gid'] || $item['deny_cid'] || $item['deny_gid']) {
		$allow_people = expand_acl($item['allow_cid']);
		$allow_groups = expand_groups(expand_acl($item['allow_gid']));
		$deny_people  = expand_acl($item['deny_cid']);
		$deny_groups  = expand_groups(expand_acl($item['deny_gid']));

		$recipients = array_unique(array_merge($allow_people,$allow_groups));
		$deny = array_unique(array_merge($deny_people,$deny_groups));
		$recipients = array_diff($recipients,$deny);
		$private = true;
	}
	else {
		$recipients = array();
		$r = q("select * from abook where abook_channel = %d and not (abook_flags & %d) and not (abook_flags & %d)",
			intval($item['uid']),
			intval(ABOOK_FLAG_SELF),
			intval(ABOOK_FLAG_PENDING)
		);
		if($r) {
			foreach($r as $rr) {
				// FIXME check permissions of each
				$recipients[] = $rr['abook_xchan'];
			}
		}
		$private = false;
	}
	return $recipients;
}


function get_public_feed($channel,$params) {

	$type      = 'xml';
	$begin     = '0000-00-00 00:00:00';
	$end       = '';
	$start     = 0;
	$records   = 40;
	$direction = 'desc';

	if(is_array($params)) {
		$type      = ((x($params,'type'))      ? $params['type']      : $type);
		$begin     = ((x($params,'begin'))     ? $params['begin']     : $begin);
		$end       = ((x($params,'end'))       ? $params['end']       : $end);
		$start     = ((x($params,'start'))     ? $params['start']     : $start);
		$records   = ((x($params,'records'))   ? $params['records']   : $records);
		$direction = ((x($params,'direction')) ? $params['direction'] : $direction);
	}
		
	switch($type) {
		case 'json':
			header("Content-type: application/atom+json");
			break;
		case 'xml':
		default:
			header("Content-type: application/atom+xml");
			break;
	}





}

function get_feed_for(&$a, $dfrn_id, $owner_nick, $last_update, $direction = 0) {


	$sitefeed    = ((strlen($owner_nick)) ? false : true); // not yet implemented, need to rewrite huge chunks of following logic
	$public_feed = (($dfrn_id) ? false : true);
	$starred     = false;   // not yet implemented, possible security issues
	$converse    = false;

	if($public_feed && $a->argc > 2) {
		for($x = 2; $x < $a->argc; $x++) {
			if($a->argv[$x] == 'converse')
				$converse = true;
			if($a->argv[$x] == 'starred')
				$starred = true;
			if($a->argv[$x] === 'category' && $a->argc > ($x + 1) && strlen($a->argv[$x+1]))
				$category = $a->argv[$x+1];
		}
	}

	

	// default permissions - anonymous user

	$sql_extra = " AND `allow_cid` = '' AND `allow_gid` = '' AND `deny_cid`  = '' AND `deny_gid`  = '' ";

	$r = q("SELECT `contact`.*, `user`.`uid` AS `user_uid`, `user`.`nickname`, `user`.`timezone`, `user`.`page-flags`
		FROM `contact` LEFT JOIN `user` ON `user`.`uid` = `contact`.`uid`
		WHERE `contact`.`self` = 1 AND `user`.`nickname` = '%s' LIMIT 1",
		dbesc($owner_nick)
	);

	if(! count($r))
		killme();

	$owner = $r[0];
	$owner_id = $owner['user_uid'];
	$owner_nick = $owner['nickname'];

	$birthday = feed_birthday($owner_id,$owner['timezone']);

	if(! $public_feed) {

		$sql_extra = '';
		switch($direction) {
			case (-1):
				$sql_extra = sprintf(" AND `issued_id` = '%s' ", dbesc($dfrn_id));
				$my_id = $dfrn_id;
				break;
			case 0:
				$sql_extra = sprintf(" AND `issued_id` = '%s' AND `duplex` = 1 ", dbesc($dfrn_id));
				$my_id = '1:' . $dfrn_id;
				break;
			case 1:
				$sql_extra = sprintf(" AND `dfrn_id` = '%s' AND `duplex` = 1 ", dbesc($dfrn_id));
				$my_id = '0:' . $dfrn_id;
				break;
			default:
				return false;
				break; // NOTREACHED
		}

		$r = q("SELECT * FROM `contact` WHERE `blocked` = 0 AND `pending` = 0 AND `contact`.`uid` = %d $sql_extra LIMIT 1",
			intval($owner_id)
		);

		if(! count($r))
			killme();

		$contact = $r[0];
		require_once('include/security.php');
		$groups = init_groups_visitor($contact['id']);

		if(count($groups)) {
			for($x = 0; $x < count($groups); $x ++) 
				$groups[$x] = '<' . intval($groups[$x]) . '>' ;
			$gs = implode('|', $groups);
		}
		else
			$gs = '<<>>' ; // Impossible to match 

		$sql_extra = sprintf(" 
			AND ( `allow_cid` = '' OR     `allow_cid` REGEXP '<%d>' ) 
			AND ( `deny_cid`  = '' OR NOT `deny_cid`  REGEXP '<%d>' ) 
			AND ( `allow_gid` = '' OR     `allow_gid` REGEXP '%s' )
			AND ( `deny_gid`  = '' OR NOT `deny_gid`  REGEXP '%s') 
		",
			intval($contact['id']),
			intval($contact['id']),
			dbesc($gs),
			dbesc($gs)
		);
	}

	if($public_feed)
		$sort = 'DESC';
	else
		$sort = 'ASC';

	if(! strlen($last_update))
		$last_update = 'now -30 days';

	if(isset($category)) {
		$sql_extra .= file_tag_file_query('item',$category,'category');
	}

	if($public_feed) {
		if(! $converse)
			$sql_extra .= " AND `contact`.`self` = 1 ";
	}

	$check_date = datetime_convert('UTC','UTC',$last_update,'Y-m-d H:i:s');

	$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
		`contact`.`name`, `contact`.`network`, `contact`.`photo`, `contact`.`url`, 
		`contact`.`name_date`, `contact`.`uri_date`, `contact`.`avatar_date`,
		`contact`.`thumb`, `contact`.`dfrn_id`, `contact`.`self`, 
		`contact`.`id` AS `contact-id`, `contact`.`uid` AS `contact-uid`,
		`sign`.`signed_text`, `sign`.`signature`, `sign`.`signer`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		LEFT JOIN `sign` ON `sign`.`iid` = `item`.`id`
		WHERE `item`.`uid` = %d AND `item`.`visible` = 1 and `item`.`moderated` = 0 AND `item`.`parent` != 0 
		AND `item`.`wall` = 1 AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
		AND ( `item`.`edited` > '%s' OR `item`.`changed` > '%s' )
		$sql_extra
		ORDER BY `parent` %s, `created` ASC LIMIT 0, 300",
		intval($owner_id),
		dbesc($check_date),
		dbesc($check_date),
		dbesc($sort)
	);

	// Will check further below if this actually returned results.
	// We will provide an empty feed if that is the case.

	$items = $r;
	$items = fetch_post_tags($items);

	$feed_template = get_markup_template(($dfrn_id) ? 'atom_feed_dfrn.tpl' : 'atom_feed.tpl');

	$atom = '';

	$hubxml = feed_hublinks();

	$salmon = feed_salmonlinks($owner_nick);

	$atom .= replace_macros($feed_template, array(
		'$version'      => xmlify(FRIENDICA_VERSION),
		'$feed_id'      => xmlify($a->get_baseurl() . '/channel/' . $owner_nick),
		'$feed_title'   => xmlify($owner['name']),
		'$feed_updated' => xmlify(datetime_convert('UTC', 'UTC', 'now' , ATOM_TIME)) ,
		'$hub'          => $hubxml,
		'$salmon'       => $salmon,
		'$name'         => xmlify($owner['name']),
		'$profile_page' => xmlify($owner['url']),
		'$photo'        => xmlify($owner['photo']),
		'$thumb'        => xmlify($owner['thumb']),
		'$picdate'      => xmlify(datetime_convert('UTC','UTC',$owner['avatar_date'] . '+00:00' , ATOM_TIME)) ,
		'$uridate'      => xmlify(datetime_convert('UTC','UTC',$owner['uri_date']    . '+00:00' , ATOM_TIME)) ,
		'$namdate'      => xmlify(datetime_convert('UTC','UTC',$owner['name_date']   . '+00:00' , ATOM_TIME)) , 
		'$birthday'     => ((strlen($birthday)) ? '<dfrn:birthday>' . xmlify($birthday) . '</dfrn:birthday>' : ''),
		'$community'    => (($owner['page-flags'] == PAGE_COMMUNITY) ? '<dfrn:community>1</dfrn:community>' : '')
	));

	call_hooks('atom_feed', $atom);

	if(! count($items)) {

		call_hooks('atom_feed_end', $atom);

		$atom .= '</feed>' . "\r\n";
		return $atom;
	}

	foreach($items as $item) {

		// prevent private email from leaking.
		if($item['network'] === NETWORK_MAIL)
			continue;

		// public feeds get html, our own nodes use bbcode

		if($public_feed) {
			$type = 'html';
			// catch any email that's in a public conversation and make sure it doesn't leak
			if($item['private'])
				continue;
		}
		else {
			$type = 'text';
		}

		$atom .= atom_entry($item,$type,null,$owner,true);
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
		$r = parse_xml_string($item['object'],false);


		if(! $r)
			return '';
		if($r->type)
			$o .= '<as:obj_type>' . xmlify($r->type) . '</as:obj_type>' . "\r\n";
		if($r->id)
			$o .= '<id>' . xmlify($r->id) . '</id>' . "\r\n";
		if($r->title)
			$o .= '<title>' . xmlify($r->title) . '</title>' . "\r\n";
		if($r->link) {
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
		$r = parse_xml_string($item['target'],false);
		if(! $r)
			return '';
		if($r->type)
			$o .= '<as:obj_type>' . xmlify($r->type) . '</as:obj_type>' . "\r\n";
		if($r->id)
			$o .= '<id>' . xmlify($r->id) . '</id>' . "\r\n";
		if($r->title)
			$o .= '<title>' . xmlify($r->title) . '</title>' . "\r\n";
		if($r->link) {
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
if(! function_exists('limit_body_size')) {
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
}}

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

	$arr = array();

	$arr['body']         = (($x['body']) ? htmlentities($x['body'],ENT_COMPAT,'UTF-8') : '');

	$arr['created']      = datetime_convert('UTC','UTC',$x['created']);
	$arr['edited']       = datetime_convert('UTC','UTC',$x['edited']);
	$arr['expires']      = ((x($x,'expires') && $x['expires']) 
								? datetime_convert('UTC','UTC',$x['expires']) 
								: '0000-00-00 00:00:00');

	if($arr['created'] > datetime_convert())
		$arr['created']  = datetime_convert();
	if($arr['edited'] > datetime_convert())
		$arr['edited']   = datetime_convert();

	$arr['title']        = (($x['title'])    ? htmlentities($x['title'],    ENT_COMPAT,'UTF-8') : '');
	$arr['app']          = (($x['app'])      ? htmlentities($x['app'],      ENT_COMPAT,'UTF-8') : '');
	$arr['uri']          = (($x['message_id'])      ? htmlentities($x['message_id'],      ENT_COMPAT,'UTF-8') : '');
	$arr['parent_uri']   = (($x['message_top']) ? htmlentities($x['message_top'], ENT_COMPAT,'UTF-8') : '');
	$arr['thr_parent']   = (($x['message_parent']) ? htmlentities($x['message_parent'], ENT_COMPAT,'UTF-8') : '');

	$arr['plink']        = (($x['permalink'])    ? htmlentities($x['permalink'],    ENT_COMPAT,'UTF-8') : '');
	$arr['location']     = (($x['location']) ? htmlentities($x['location'], ENT_COMPAT,'UTF-8') : '');
	$arr['coord']        = (($x['longlat'])    ? htmlentities($x['longlat'],    ENT_COMPAT,'UTF-8') : '');
	$arr['verb']         = (($x['verb'])     ? htmlentities($x['verb'],     ENT_COMPAT,'UTF-8') : '');
	$arr['obj_type']     = (($x['object_type'])  ? htmlentities($x['object_type'],  ENT_COMPAT,'UTF-8') : '');
	$arr['tgt_type']     = (($x['target_type'])  ? htmlentities($x['target_type'],  ENT_COMPAT,'UTF-8') : '');

	$arr['object']       = activity_sanitise($x['object']);
	$arr['target']       = activity_sanitise($x['target']);

	$arr['attach']       = activity_sanitise($x['attach']);
	$arr['term']         = decode_tags($x['tags']);

	$arr['item_private'] = ((array_key_exists('flags',$x) && is_array($x['flags']) && in_array('private',$x['flags'])) ? 1 : 0);

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

	if(import_author_xchan($x['owner']))
		$arr['owner_xchan']  = base64url_encode(hash('whirlpool',$x['owner']['guid'] . $x['owner']['guid_sig'], true));
	else
		return array();


	return $arr;

}


function import_author_xchan($x) {
	$r = q("select hubloc_url from hubloc where hubloc_guid = '%s' and hubloc_guid_sig = '%s' and (hubloc_flags & %d) limit 1",
		dbesc($x['guid']),
		dbesc($x['guid_sig']),
		intval(HUBLOG_FLAGS_PRIMARY)
	);
	if($r)
		return true;
	$them = array('hubloc_url' => $x['url'],'xchan_guid' => $x['guid'], 'xchan_guid_sig' => $x['guid_sig']);
	return zot_refresh($them);
}

function encode_item($item) {
	$x = array();
	$x['type'] = 'activity';

	logger('encode_item: ' . print_r($item,true));

	if($item['item_restrict']  & ITEM_DELETED) {
		$x['message_id'] = $item['uri'];
		$x['flags'] = array('deleted');
		return $x;
	}

	$x['message_id']     = $item['uri'];
	$x['message_top']    = $item['parent_uri'];
	$x['message_parent'] = $item['thr_parent'];
	$x['created']        = $item['created'];
	$x['edited']         = $item['edited'];
	$x['expires']        = $item['expires'];
	$x['title']          = $item['title'];
	$x['body']           = $item['body'];
	$x['app']            = $item['app'];
	$x['verb']           = $item['verb'];
	$x['object_type']    = $item['obj_type'];
	$x['target_type']    = $item['tgt_type'];
	$x['permalink']      = $item['plink'];
	$x['location']       = $item['location'];
	$x['longlat']        = $item['coord'];

	$x['owner']          = encode_item_xchan($item['owner']);
	$x['author']         = encode_item_xchan($item['author']);
	if($item['object'])
		$x['object']     = json_decode($item['object'],true);
	if($item['target'])
		$x['target']     = json_decode($item['target'],true);
	if($item['attach'])
		$x['attach']     = json_decode($item['attach'],true);
	if($y = encode_item_flags($item))
		$x['flags']      = $y;
	if($item['term'])
		$x['tags']       = encode_item_terms($item['term']);

	return $x;

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
			$tag['term'] = htmlentities($x['term'],  ENT_COMPAT,'UTF-8');
			$tag['url']  = htmlentities($x['url'],  ENT_COMPAT,'UTF-8');
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
		$ret = array();
		foreach($arr as $k => $x) {
			if(is_array($x))
				$ret[$k] = activity_sanitise($x);
			else
				$ret[$k] = htmlentities($x, ENT_COMPAT,'UTF-8');
		}
		return $ret;
	}
	return '';
}

// sanitise a simple linear array

function array_sanitise($arr) {
	if($arr) {
		$ret = array();
		foreach($arr as $x) {
			$ret[] = htmlentities($x, ENT_COMPAT,'UTF-8');
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

	logger('encode_mail: ' . print_r($item,true));

	$x['message_id']     = $item['uri'];
	$x['message_parent'] = $item['parent_uri'];
	$x['created']        = $item['created'];
	$x['title']          = $item['title'];
	$x['body']           = $item['body'];
	$x['from']           = encode_item_xchan($item['from']);
	$x['to']             = encode_item_xchan($item['to']);

	return $x;
}



function get_mail_elements($x) {

	$arr = array();

	$arr['body']         = (($x['body']) ? htmlentities($x['body'],ENT_COMPAT,'UTF-8') : '');

	$arr['created']      = datetime_convert('UTC','UTC',$x['created']);

	if($arr['created'] > datetime_convert())
		$arr['created']  = datetime_convert();

	$arr['title']        = (($x['title'])    ? htmlentities($x['title'],    ENT_COMPAT,'UTF-8') : '');
	$arr['uri']          = (($x['message_id'])      ? htmlentities($x['message_id'],      ENT_COMPAT,'UTF-8') : '');
	$arr['parent_uri']   = (($x['message_parent']) ? htmlentities($x['message_parent'], ENT_COMPAT,'UTF-8') : '');


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

	$arr['desc']         = (($x['title']) ? htmlentities($x['title'],ENT_COMPAT,'UTF-8') : '');

	$arr['dob']          = datetime_convert('UTC','UTC',$x['birthday'],'Y-m-d');

	$arr['gender']       = (($x['gender'])    ? htmlentities($x['gender'],    ENT_COMPAT,'UTF-8') : '');
	$arr['marital']      = (($x['marital'])   ? htmlentities($x['marital'],   ENT_COMPAT,'UTF-8') : '');
	$arr['sexual']       = (($x['sexual'])    ? htmlentities($x['sexual'],    ENT_COMPAT,'UTF-8') : '');
	$arr['locale']       = (($x['locale'])    ? htmlentities($x['locale'],    ENT_COMPAT,'UTF-8') : '');
	$arr['region']       = (($x['region'])    ? htmlentities($x['region'],    ENT_COMPAT,'UTF-8') : '');
	$arr['postcode']     = (($x['postcode'])  ? htmlentities($x['postcode'],  ENT_COMPAT,'UTF-8') : '');
	$arr['country']      = (($x['country'])   ? htmlentities($x['country'],   ENT_COMPAT,'UTF-8') : '');

	$arr['keywords']     = (($x['keywords'] && is_array($x['keywords'])) ? array_sanitise($x['keywords']) : array()); 

	return $arr;

}



function get_atom_elements($feed,$item) {

	require_once('library/HTMLPurifier.auto.php');
	require_once('include/html2bbcode.php');

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
	$res['uri'] = unxmlify($item->get_id());
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

		$config = HTMLPurifier_Config::createDefault();
		$config->set('Cache.DefinitionImpl', null);

		// we shouldn't need a whitelist, because the bbcode converter
		// will strip out any unsupported tags.

		$purifier = new HTMLPurifier($config);
		$res['body'] = $purifier->purify($res['body']);

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

				$body = html2bb_video($body);

				$config = HTMLPurifier_Config::createDefault();
				$config->set('Cache.DefinitionImpl', null);

				$purifier = new HTMLPurifier($config);
				$body = $purifier->purify($body);
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

				$body = html2bb_video($body);

				$config = HTMLPurifier_Config::createDefault();
				$config->set('Cache.DefinitionImpl', null);

				$purifier = new HTMLPurifier($config);
				$body = $purifier->purify($body);
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

	//if (($res["title"] != "") or (strpos($res["body"], "RT @") > 0)) {
	//if (strpos($res["body"], "RT @") !== false) {
	//	$debugfile = tempnam("/home/ike/log", "item-res2-");
	//	file_put_contents($debugfile, serialize($arr));
	//}

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

function item_store($arr,$force_parent = false) {

	if(! $arr['uid']) {
		logger('item_store: no uid');
		return 0;
	}

	$arr['lang'] = detect_language($arr['body']);

	$allowed_languages = get_pconfig($arr['uid'],'system','allowed_languages');
	
	if((is_array($allowed_languages)) && ($arr['lang']) && (! array_key_exists($arr['lang'],$allowed_languages))) {
		$translate = array('item' => $arr, 'from' => $arr['lang'], 'to' => $allowed_languages, 'translated' => false);
		call_hooks('item_translate', $translate);
		if((! $translate['translated']) && (intval(get_pconfig($arr['uid'],'system','reject_disallowed_languages')))) {
			logger('item_store: language ' . $arr['lang'] . ' not accepted for uid ' . $arr['uid']);
			return;
		}
		$arr = $translate['item'];
	}

	// Shouldn't happen but we want to make absolutely sure it doesn't leak from a plugin.

	if((strpos($arr['body'],'<') !== false) || (strpos($arr['body'],'>') !== false)) 
		$arr['body'] = escape_tags($arr['body']);

	if((x($arr,'object')) && is_array($arr['object'])) {
		activity_sanitise($arr['object']);
		$arr['object'] = json_encode($arr['object']);
	}

	if((x($arr,'target')) && is_array($arr['target'])) {
		activity_sanitise($arr['target']);
		$arr['target'] = json_encode($arr['target']);
	}

	$arr['aid']           = ((x($arr,'aid'))           ? intval($arr['aid'])                 : 0);
	$arr['uri']           = ((x($arr,'uri'))           ? notags(trim($arr['uri']))           : random_string());
	$arr['author_xchan']  = ((x($arr,'author_xchan'))  ? notags(trim($arr['author_xchan']))  : '');
	$arr['owner_xchan']   = ((x($arr,'owner_xchan'))   ? notags(trim($arr['owner_xchan']))   : '');
	$arr['created']       = ((x($arr,'created') !== false) ? datetime_convert('UTC','UTC',$arr['created']) : datetime_convert());
	$arr['edited']        = ((x($arr,'edited')  !== false) ? datetime_convert('UTC','UTC',$arr['edited'])  : datetime_convert());
	$arr['expires']        = ((x($arr,'expires')  !== false) ? datetime_convert('UTC','UTC',$arr['expires'])  : '0000-00-00 00:00:00');
	$arr['commented']     = datetime_convert();
	$arr['received']      = datetime_convert();
	$arr['changed']       = datetime_convert();
	$arr['title']         = ((x($arr,'title'))         ? notags(trim($arr['title']))         : '');
	$arr['location']      = ((x($arr,'location'))      ? notags(trim($arr['location']))      : '');
	$arr['coord']         = ((x($arr,'coord'))         ? notags(trim($arr['coord']))         : '');
	$arr['parent_uri']    = ((x($arr,'parent_uri'))    ? notags(trim($arr['parent_uri']))    : '');
	$arr['verb']          = ((x($arr,'verb'))          ? notags(trim($arr['verb']))          : '');
	$arr['obj_type']      = ((x($arr,'obj_type'))      ? notags(trim($arr['obj_type']))      : '');
	$arr['object']        = ((x($arr,'object'))        ? trim($arr['object'])                : '');
	$arr['tgt_type']      = ((x($arr,'tgt_type'))      ? notags(trim($arr['tgt_type']))      : '');
	$arr['target']        = ((x($arr,'target'))        ? trim($arr['target'])                : '');
	$arr['plink']         = ((x($arr,'plink'))         ? notags(trim($arr['plink']))         : '');
	$arr['allow_cid']     = ((x($arr,'allow_cid'))     ? trim($arr['allow_cid'])             : '');
	$arr['allow_gid']     = ((x($arr,'allow_gid'))     ? trim($arr['allow_gid'])             : '');
	$arr['deny_cid']      = ((x($arr,'deny_cid'))      ? trim($arr['deny_cid'])              : '');
	$arr['deny_gid']      = ((x($arr,'deny_gid'))      ? trim($arr['deny_gid'])              : '');
	$arr['item_private']  = ((x($arr,'item_private'))  ? intval($arr['item_private'])        : 0 );
	$arr['body']          = ((x($arr,'body'))          ? trim($arr['body'])                  : '');
	$arr['attach']        = ((x($arr,'attach'))        ? notags(trim($arr['attach']))        : '');
	$arr['app']           = ((x($arr,'app'))           ? notags(trim($arr['app']))           : '');
	$arr['item_restrict'] = ((x($arr,'item_restrict')) ? intval($arr['item_restrict'])       : 0 );
	$arr['item_flags']    = ((x($arr,'item_flags'))    ? intval($arr['item_flags'])          : 0 );
	
	$arr['item_flags'] = $arr['item_flags'] | ITEM_UNSEEN;
	
	$arr['thr_parent'] = $arr['parent_uri'];

	$arr['llink'] = z_root() . '/display/' . $arr['uri'];

	if(! $arr['plink'])
		$arr['plink'] = $arr['llink'];

	if($arr['parent_uri'] === $arr['uri']) {
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

		$r = q("SELECT * FROM `item` WHERE `uri` = '%s' AND `uid` = %d ORDER BY `id` ASC LIMIT 1",
			dbesc($arr['parent_uri']),
			intval($arr['uid'])
		);

		if(count($r)) {

			// is the new message multi-level threaded?
			// even though we don't support it now, preserve the info
			// and re-attach to the conversation parent.

			if($r[0]['uri'] != $r[0]['parent_uri']) {
				$arr['parent_uri'] = $r[0]['parent_uri'];
				$z = q("SELECT * FROM `item` WHERE `uri` = '%s' AND `parent_uri` = '%s' AND `uid` = %d 
					ORDER BY `id` ASC LIMIT 1",
					dbesc($r[0]['parent_uri']),
					dbesc($r[0]['parent_uri']),
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

			// Allow one to see reply tweets from status.net even when
			// we don't have or can't see the original post.

			if($force_parent) {
				logger('item_store: $force_parent=true, reply converted to top-level post.');
				$parent_id = 0;
				$arr['parent_uri'] = $arr['uri'];
				$arr['flags'] = $arr['flags'] | ITEM_THREAD_TOP;
			}
			else {
				logger('item_store: item parent was not found - ignoring item');
				return 0;
			}
			
			$parent_deleted = 0;
		}
	}

	if($parent_deleted)
		$arr['item_restrict'] = $arr['item_restrict'] | ITEM_DELETED;
	
	
	$r = q("SELECT `id` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
		dbesc($arr['uri']),
		intval($arr['uid'])
	);
	if($r) {
		logger('item-store: duplicate item ignored. ' . print_r($arr,true));
		return 0;
	}

	call_hooks('post_remote',$arr);

	if(x($arr,'cancel')) {
		logger('item_store: post cancelled by plugin.');
		return 0;
	}

	// pull out all the taxonomy stuff for separate storage

	$terms = null;
	if(array_key_exists('term',$arr)) {
		$terms = $arr['term'];
		unset($arr['term']);
	}

	dbesc_array($arr);

	logger('item_store: ' . print_r($arr,true), LOGGER_DATA);

	$r = dbq("INSERT INTO `item` (`" 
			. implode("`, `", array_keys($arr)) 
			. "`) VALUES ('" 
			. implode("', '", array_values($arr)) 
			. "')" );

	// find the item we just created

	$r = q("SELECT `id` FROM `item` WHERE `uri` = '%s' AND `uid` = %d ORDER BY `id` ASC ",
		$arr['uri'],           // already dbesc'd
		intval($arr['uid'])
	);

	if($r && count($r)) {
		$current_post = $r[0]['id'];
		logger('item_store: created item ' . $current_post, LOGGER_DEBUG);
	}
	else {
		logger('item_store: could not locate created item');
		return 0;
	}
	if(count($r) > 1) {
		logger('item_store: duplicated post occurred. Removing duplicates.');
		q("DELETE FROM `item` WHERE `uri` = '%s' AND `uid` = %d AND `id` != %d ",
			$arr['uri'],
			intval($arr['uid']),
			intval($current_post)
		);
	}

	if((! $parent_id) || ($arr['parent_uri'] === $arr['uri']))	
		$parent_id = $current_post;

 	if(strlen($allow_cid) || strlen($allow_gid) || strlen($deny_cid) || strlen($deny_gid))
		$private = 1;
	else
		$private = $arr['private']; 

	// Set parent id - and also make sure to inherit the parent's ACL's.

	$r = q("UPDATE `item` SET `parent` = %d, `allow_cid` = '%s', `allow_gid` = '%s',
		`deny_cid` = '%s', `deny_gid` = '%s', `item_private` = %d WHERE `id` = %d LIMIT 1",
		intval($parent_id),
		dbesc($allow_cid),
		dbesc($allow_gid),
		dbesc($deny_cid),
		dbesc($deny_gid),
		intval($private),
		intval($current_post)
	);

	$arr['id']        = $current_post;
	$arr['parent']    = $parent_id;
	$arr['allow_cid'] = $allow_cid;
	$arr['allow_gid'] = $allow_gid;
	$arr['deny_cid']  = $deny_cid;
	$arr['deny_gid']  = $deny_gid;
	$arr['private']   = $private;
	
	// Store taxonomy
	
	if(($terms) && (is_array($terms))) {
		foreach($terms as $t) {
			q("insert into term (uid,oid,otype,type,term,url)
				values(%d,%d,%d,%d,'%s','%s') ",
				intval($arr['uid']),
				intval($current_post),
				intval($t['otype']),
				intval($t['type']),
				dbesc($t['term']),
				dbesc($t['url'])
			);
		}

		$arr['term'] = $terms;
	}	

	call_hooks('post_remote_end',$arr);

	// update the commented timestamp on the parent

	q("UPDATE `item` set `commented` = '%s', `changed` = '%s' WHERE `id` = %d LIMIT 1",
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		intval($parent_id)
	);

	tag_deliver($arr['uid'],$current_post);

	return $current_post;
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

	// look for mention tags and setup a second delivery chain for forum/community posts if appropriate

	$a = get_app();

	$mention = false;

	$u = q("select * from channel where channel_id = %d limit 1",
		intval($uid)
	);
	if(! count($u))
		return;

		
		// fixme - look for permissions allowing tag delivery
		
	$community_page = (($u[0]['page-flags'] == PAGE_COMMUNITY) ? true : false);
	$prvgroup = (($u[0]['page-flags'] == PAGE_PRVGROUP) ? true : false);


	$i = q("select * from item where id = %d and uid = %d limit 1",
		intval($item_id),
		intval($uid)
	);
	if(! count($i))
		return;

	$item = $i[0];

	$link = normalise_link($a->get_baseurl() . '/channel/' . $u[0]['nickname']);

	$body = preg_replace("/\[share\](.*?)\[\/share\]/ism", '', $item['body']);
	
	$cnt = preg_match_all('/[\@\!]\[url\=(.*?)\](.*?)\[\/url\]/ism',$body,$matches,PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			if(link_compare($link,$mtch[1])) {
				$mention = true;
				logger('tag_deliver: mention found: ' . $mtch[2]);
			}
		}
	}

	if(! $mention)
		return;

	// send a notification

	// use a local photo if we have one

	$r = q("select thumb from contact where uid = %d and nurl = '%s' limit 1",
		intval($u[0]['uid']),
		dbesc(normalise_link($item['author-link']))
	);
	$photo = (($r && count($r)) ? $r[0]['thumb'] : $item['author-avatar']);

// fixme for channels

	require_once('include/enotify.php');
	notification(array(
		'type'         => NOTIFY_TAGSELF,
		'notify_flags' => $u[0]['notify-flags'],
		'language'     => $u[0]['language'],
		'to_name'      => $u[0]['username'],
		'to_email'     => $u[0]['email'],
		'uid'          => $u[0]['uid'],
		'item'         => $item,
		'link'         => $a->get_baseurl() . '/display/' . $u[0]['nickname'] . '/' . $item['id'],
		'source_name'  => $item['author-name'],
		'source_link'  => $item['author-link'],
		'source_photo' => $photo,
		'verb'         => ACTIVITY_TAG,
		'otype'        => 'item'
	));

	if((! $community_page) && (! $prvgroup))
		return;


	// tgroup delivery - setup a second delivery chain
	// prevent delivery looping - only proceed
	// if the message originated elsewhere and is a top-level post

	if(($item['wall']) || ($item['origin']) || ($item['id'] != $item['parent']))
		return;

	// now change this copy of the post to a forum head message and deliver to all the tgroup members


	$c = q("select name, url, thumb from contact where self = 1 and uid = %d limit 1",
		intval($u[0]['uid'])
	);
	if(! count($c))
		return;

	// also reset all the privacy bits to the forum default permissions

	$private = ($u[0]['allow_cid'] || $u[0]['allow_gid'] || $u[0]['deny_cid'] || $u[0]['deny_gid']) ? 1 : 0;

	$forum_mode = (($prvgroup) ? 2 : 1);

	q("update item set wall = 1, origin = 1, forum_mode = %d, `owner-name` = '%s', `owner-link` = '%s', `owner-avatar` = '%s', 
		`private` = %d, `allow_cid` = '%s', `allow_gid` = '%s', `deny_cid` = '%s', `deny_gid` = '%s'  where id = %d limit 1",
		intval($forum_mode),
		dbesc($c[0]['name']),
		dbesc($c[0]['url']),
		dbesc($c[0]['thumb']),
		intval($private),
		dbesc($u[0]['allow_cid']),
		dbesc($u[0]['allow_gid']),
		dbesc($u[0]['deny_cid']),
		dbesc($u[0]['deny_gid']),
		intval($item_id)
	);

	proc_run('php','include/notifier.php','tgroup',$item_id);			

}



function tgroup_check($uid,$item) {

	$a = get_app();

	$mention = false;

	// check that the message originated elsewhere and is a top-level post

	if(($item['wall']) || ($item['origin']) || ($item['uri'] != $item['parent-uri']))
		return false;


	$u = q("select * from user where uid = %d limit 1",
		intval($uid)
	);
	if(! count($u))
		return false;

	$community_page = (($u[0]['page-flags'] == PAGE_COMMUNITY) ? true : false);
	$prvgroup = (($u[0]['page-flags'] == PAGE_PRVGROUP) ? true : false);


	$link = normalise_link($a->get_baseurl() . '/channel/' . $u[0]['nickname']);

	// Diaspora uses their own hardwired link URL in @-tags
	// instead of the one we supply with webfinger

	$dlink = normalise_link($a->get_baseurl() . '/u/' . $u[0]['nickname']);

	$body = preg_replace("/\[share\](.*?)\[\/share\]/ism", '', $item['body']);
	
	$cnt = preg_match_all('/[\@\!]\[url\=(.*?)\](.*?)\[\/url\]/ism',$body,$matches,PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			if(link_compare($link,$mtch[1]) || link_compare($dlink,$mtch[1])) {
				$mention = true;
				logger('tgroup_check: mention found: ' . $mtch[2]);
			}
		}
	}

	if(! $mention)
		return false;

	if((! $community_page) && (! $prvgroup))
		return false;



	return true;

}


function mail_store($arr) {

	if(! $arr['channel_id']) {
		logger('mail_store: no uid');
		return 0;
	}

	if((strpos($arr['body'],'<') !== false) || (strpos($arr['body'],'>') !== false)) 
		$arr['body'] = escape_tags($arr['body']);

	$arr['account_id']    = ((x($arr,'account_id'))           ? intval($arr['account_id'])                 : 0);
	$arr['uri']           = ((x($arr,'uri'))           ? notags(trim($arr['uri']))           : random_string());
	$arr['from_xchan']    = ((x($arr,'from_xchan'))  ? notags(trim($arr['from_xchan']))  : '');
	$arr['to_xchan']   = ((x($arr,'to_xchan'))   ? notags(trim($arr['to_xchan']))   : '');
	$arr['created']       = ((x($arr,'created') !== false) ? datetime_convert('UTC','UTC',$arr['created']) : datetime_convert());
	$arr['title']         = ((x($arr,'title'))         ? notags(trim($arr['title']))         : '');
	$arr['parent_uri']    = ((x($arr,'parent_uri'))    ? notags(trim($arr['parent_uri']))    : '');
	$arr['body']          = ((x($arr,'body'))          ? trim($arr['body'])                  : '');
	$arr['mail_flags']    = ((x($arr,'mail_flags'))    ? intval($arr['mail_flags'])          : 0 );
	

	if(! $arr['parent_uri']) {
		logger('mail_store: missing parent');
		$arr['parent_uri'] = $arr['uri'];
	}

	$r = q("SELECT `id` FROM mail WHERE `uri` = '%s' AND channel_id = %d LIMIT 1",
		dbesc($arr['uri']),
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

	$r = q("SELECT `id` FROM mail WHERE `uri` = '%s' AND `channel_id` = %d ORDER BY `id` ASC ",
		$arr['uri'],           // already dbesc'd
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
		q("DELETE FROM mail WHERE `uri` = '%s' AND `channel_id` = %d AND `id` != %d ",
			$arr['uri'],
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

	$ssl_val = intval(get_config('system','ssl_policy'));
	$ssl_policy = '';

	switch($ssl_val){
		case SSL_POLICY_FULL:
			$ssl_policy = 'full';
			break;
		case SSL_POLICY_SELFSIGN:
			$ssl_policy = 'self';
			break;			
		case SSL_POLICY_NONE:
		default:
			$ssl_policy = 'none';
			break;
	}

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

	$postvars['ssl_policy'] = $ssl_policy;

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

	$name_updated  = '';
	$new_name = '';
	$photo_timestamp = '';
	$photo_url = '';
	$birthday = '';

	$hubs = $feed->get_links('hub');
	logger('consume_feed: hubs: ' . print_r($hubs,true), LOGGER_DATA);

	if(count($hubs))
		$hub = implode(',', $hubs);

	$rawtags = $feed->get_feed_tags( NAMESPACE_DFRN, 'owner');
	if(! $rawtags)
		$rawtags = $feed->get_feed_tags( SIMPLEPIE_NAMESPACE_ATOM_10, 'author');
	if($rawtags) {
		$elems = $rawtags[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10];
		if($elems['name'][0]['attribs'][NAMESPACE_DFRN]['updated']) {
			$name_updated = $elems['name'][0]['attribs'][NAMESPACE_DFRN]['updated'];
			$new_name = $elems['name'][0]['data'];
		} 
		if((x($elems,'link')) && ($elems['link'][0]['attribs']['']['rel'] === 'photo') && ($elems['link'][0]['attribs'][NAMESPACE_DFRN]['updated'])) {
			$photo_timestamp = datetime_convert('UTC','UTC',$elems['link'][0]['attribs'][NAMESPACE_DFRN]['updated']);
			$photo_url = $elems['link'][0]['attribs']['']['href'];
		}

		if((x($rawtags[0]['child'], NAMESPACE_DFRN)) && (x($rawtags[0]['child'][NAMESPACE_DFRN],'birthday'))) {
			$birthday = datetime_convert('UTC','UTC', $rawtags[0]['child'][NAMESPACE_DFRN]['birthday'][0]['data']);
		}
	}

	if((is_array($contact)) && ($photo_timestamp) && (strlen($photo_url)) && ($photo_timestamp > $contact['avatar_date'])) {
		logger('consume_feed: Updating photo for ' . $contact['name']);
		require_once("Photo.php");
		$photo_failure = false;
		$have_photo = false;

		$r = q("SELECT `resource_id` FROM `photo` WHERE `contact-id` = %d AND `uid` = %d LIMIT 1",
			intval($contact['id']),
			intval($contact['uid'])
		);
		if(count($r)) {
			$resource_id = $r[0]['resource_id'];
			$have_photo = true;
		}
		else {
			$resource_id = photo_new_resource();
		}
			
		$img_str = fetch_url($photo_url,true);
		// guess mimetype from headers or filename
		$type = guess_image_type($photo_url,true);
		
		
		$img = new Photo($img_str, $type);
		if($img->is_valid()) {
			if($have_photo) {
				q("DELETE FROM `photo` WHERE `resource_id` = '%s' AND `contact-id` = %d AND `uid` = %d",
					dbesc($resource_id),
					intval($contact['id']),
					intval($contact['uid'])
				);
			}
				
			$img->scaleImageSquare(175);
				
			$hash = $resource_id;
			$r = $img->store($contact['uid'], $contact['id'], $hash, basename($photo_url), 'Contact Photos', 4);
				
			$img->scaleImage(80);
			$r = $img->store($contact['uid'], $contact['id'], $hash, basename($photo_url), 'Contact Photos', 5);

			$img->scaleImage(48);
			$r = $img->store($contact['uid'], $contact['id'], $hash, basename($photo_url), 'Contact Photos', 6);

			$a = get_app();

			q("UPDATE `contact` SET `avatar_date` = '%s', `photo` = '%s', `thumb` = '%s', `micro` = '%s'  
				WHERE `uid` = %d AND `id` = %d LIMIT 1",
				dbesc(datetime_convert()),
				dbesc($a->get_baseurl() . '/photo/' . $hash . '-4.'.$img->getExt()),
				dbesc($a->get_baseurl() . '/photo/' . $hash . '-5.'.$img->getExt()),
				dbesc($a->get_baseurl() . '/photo/' . $hash . '-6.'.$img->getExt()),
				intval($contact['uid']),
				intval($contact['id'])
			);
		}
	}

	if((is_array($contact)) && ($name_updated) && (strlen($new_name)) && ($name_updated > $contact['name_date'])) {
		$r = q("select * from contact where uid = %d and id = %d limit 1",
			intval($contact['uid']),
			intval($contact['id'])
		);

		$x = q("UPDATE `contact` SET `name` = '%s', `name_date` = '%s' WHERE `uid` = %d AND `id` = %d LIMIT 1",
			dbesc(notags(trim($new_name))),
			dbesc(datetime_convert()),
			intval($contact['uid']),
			intval($contact['id'])
		);

		// do our best to update the name on content items

		if(count($r)) {
			q("update item set `author-name` = '%s' where `author-name` = '%s' and `author-link` = '%s' and uid = %d",
				dbesc(notags(trim($new_name))),
				dbesc($r[0]['name']),
				dbesc($r[0]['url']),
				intval($contact['uid'])
			);
		}
	}

	if(strlen($birthday)) {
		if(substr($birthday,0,4) != $contact['bdyear']) {
			logger('consume_feed: updating birthday: ' . $birthday);

			/**
			 *
			 * Add new birthday event for this person
			 *
			 * $bdtext is just a readable placeholder in case the event is shared
			 * with others. We will replace it during presentation to our $importer
			 * to contain a sparkle link and perhaps a photo. 
			 *
			 */
			 
			$bdtext = sprintf( t('%s\'s birthday'), $contact['name']);
			$bdtext2 = sprintf( t('Happy Birthday %s'), ' [url=' . $contact['url'] . ']' . $contact['name'] . '[/url]' ) ;


			$r = q("INSERT INTO `event` (`uid`,`cid`,`created`,`edited`,`start`,`finish`,`summary`,`desc`,`type`)
				VALUES ( %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) ",
				intval($contact['uid']),
			 	intval($contact['id']),
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				dbesc(datetime_convert('UTC','UTC', $birthday)),
				dbesc(datetime_convert('UTC','UTC', $birthday . ' + 1 day ')),
				dbesc($bdtext),
				dbesc($bdtext2),
				dbesc('birthday')
			);
			

			// update bdyear

			q("UPDATE `contact` SET `bdyear` = '%s' WHERE `uid` = %d AND `id` = %d LIMIT 1",
				dbesc(substr($birthday,0,4)),
				intval($contact['uid']),
				intval($contact['id'])
			);

			// This function is called twice without reloading the contact
			// Make sure we only create one event. This is why &$contact 
			// is a reference var in this function

			$contact['bdyear'] = substr($birthday,0,4);
		}

	}

	$community_page = 0;
	$rawtags = $feed->get_feed_tags( NAMESPACE_DFRN, 'community');
	if($rawtags) {
		$community_page = intval($rawtags[0]['data']);
	}
	if(is_array($contact) && intval($contact['forum']) != $community_page) {
		q("update contact set forum = %d where id = %d limit 1",
			intval($community_page),
			intval($contact['id'])
		);
		$contact['forum'] = (string) $community_page;
	}


	// process any deleted entries

	$del_entries = $feed->get_feed_tags(NAMESPACE_TOMB, 'deleted-entry');
	if(is_array($del_entries) && count($del_entries) && $pass != 2) {
		foreach($del_entries as $dentry) {
			$deleted = false;
			if(isset($dentry['attribs']['']['ref'])) {
				$uri = $dentry['attribs']['']['ref'];
				$deleted = true;
				if(isset($dentry['attribs']['']['when'])) {
					$when = $dentry['attribs']['']['when'];
					$when = datetime_convert('UTC','UTC', $when, 'Y-m-d H:i:s');
				}
				else
					$when = datetime_convert('UTC','UTC','now','Y-m-d H:i:s');
			}
			if($deleted && is_array($contact)) {
				$r = q("SELECT `item`.*, `contact`.`self` FROM `item` left join `contact` on `item`.`contact-id` = `contact`.`id` 
					WHERE `uri` = '%s' AND `item`.`uid` = %d AND `contact-id` = %d AND NOT `item`.`file` LIKE '%%[%%' LIMIT 1",
					dbesc($uri),
					intval($importer['uid']),
					intval($contact['id'])
				);
				if(count($r)) {
					$item = $r[0];

					if(! $item['deleted'])
						logger('consume_feed: deleting item ' . $item['id'] . ' uri=' . $item['uri'], LOGGER_DEBUG);

					if(($item['verb'] === ACTIVITY_TAG) && ($item['obj_type'] === ACTIVITY_OBJ_TAGTERM)) {
						$xo = parse_xml_string($item['object'],false);
						$xt = parse_xml_string($item['target'],false);
						if($xt->type === ACTIVITY_OBJ_NOTE) {
							$i = q("select * from `item` where uri = '%s' and uid = %d limit 1",
								dbesc($xt->id),
								intval($importer['importer_uid'])
							);
							if(count($i)) {

								// For tags, the owner cannot remove the tag on the author's copy of the post.

								$owner_remove = (($item['contact-id'] == $i[0]['contact-id']) ? true: false);
								$author_remove = (($item['origin'] && $item['self']) ? true : false);
								$author_copy = (($item['origin']) ? true : false);

								if($owner_remove && $author_copy)
									continue;
								if($author_remove || $owner_remove) {
									$tags = explode(',',$i[0]['tag']);
									$newtags = array();
									if(count($tags)) {
										foreach($tags as $tag)
											if(trim($tag) !== trim($xo->body))
												$newtags[] = trim($tag);
									}
									q("update item set tag = '%s' where id = %d limit 1",
										dbesc(implode(',',$newtags)),
										intval($i[0]['id'])
									);
								}
							}
						}
					}

					if($item['uri'] == $item['parent_uri']) {
						$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s',
							`body` = '', `title` = ''
							WHERE `parent_uri` = '%s' AND `uid` = %d",
							dbesc($when),
							dbesc(datetime_convert()),
							dbesc($item['uri']),
							intval($importer['uid'])
						);
					}
					else {
						$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s',
							`body` = '', `title` = '' 
							WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
							dbesc($when),
							dbesc(datetime_convert()),
							dbesc($uri),
							intval($importer['uid'])
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
			$rawthread = $item->get_item_tags( NAMESPACE_THREAD,'in-reply-to');
			if(isset($rawthread[0]['attribs']['']['ref'])) {
				$is_reply = true;
				$parent_uri = $rawthread[0]['attribs']['']['ref'];
			}

			if(($is_reply) && is_array($contact)) {

				if($pass == 1)
					continue;

				// not allowed to post

				if($contact['rel'] == CONTACT_IS_FOLLOWER)
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


				$r = q("SELECT `uid`, `edited`, `body` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
					dbesc($item_id),
					intval($importer['uid'])
				);

				// Update content if 'updated' changes

				if(count($r)) {
					if((x($datarray,'edited') !== false) && (datetime_convert('UTC','UTC',$datarray['edited']) !== $r[0]['edited'])) {  

						// do not accept (ignore) an earlier edit than one we currently have.
						if(datetime_convert('UTC','UTC',$datarray['edited']) < $r[0]['edited'])
							continue;

						$r = q("UPDATE `item` SET `title` = '%s', `body` = '%s', `edited` = '%s' WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
							dbesc($datarray['title']),
							dbesc($datarray['body']),
							dbesc(datetime_convert('UTC','UTC',$datarray['edited'])),
							dbesc($item_id),
							intval($importer['uid'])
						);
					}

					continue;
				}


				$datarray['parent_uri'] = $parent_uri;
				$datarray['uid'] = $importer['uid'];
				$datarray['contact-id'] = $contact['id'];
				if((activity_match($datarray['verb'],ACTIVITY_LIKE)) || (activity_match($datarray['verb'],ACTIVITY_DISLIKE))) {
					$datarray['type'] = 'activity';
					$datarray['gravity'] = GRAVITY_LIKE;
					// only one like or dislike per person
					$r = q("select id from item where uid = %d and `contact-id` = %d and verb ='%s' and deleted = 0 and (`parent_uri` = '%s' OR `thr_parent` = '%s') limit 1",
						intval($datarray['uid']),
						intval($datarray['contact-id']),
						dbesc($datarray['verb']),
						dbesc($parent_uri),
						dbesc($parent_uri)
					);
					if($r && count($r))
						continue; 
				}

				if(($datarray['verb'] === ACTIVITY_TAG) && ($datarray['obj_type'] === ACTIVITY_OBJ_TAGTERM)) {
					$xo = parse_xml_string($datarray['object'],false);
					$xt = parse_xml_string($datarray['target'],false);

					if($xt->type == ACTIVITY_OBJ_NOTE) {
						$r = q("select * from item where `uri` = '%s' AND `uid` = %d limit 1",
							dbesc($xt->id),
							intval($importer['importer_uid'])
						);
						if(! count($r))
							continue;

						// extract tag, if not duplicate, add to parent item
						if($xo->id && $xo->content) {
							$newtag = '#[url=' . $xo->id . ']'. $xo->content . '[/url]';
							if(! (stristr($r[0]['tag'],$newtag))) {
								q("UPDATE item SET tag = '%s' WHERE id = %d LIMIT 1",
									dbesc($r[0]['tag'] . (strlen($r[0]['tag']) ? ',' : '') . $newtag),
									intval($r[0]['id'])
								);
							}
						}
					}
				}

				$r = item_store($datarray,$force_parent);
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
						$ev['uid'] = $importer['uid'];
						$ev['uri'] = $item_id;
						$ev['edited'] = $datarray['edited'];
						$ev['private'] = $datarray['private'];

						if(is_array($contact))
							$ev['cid'] = $contact['id'];
						$r = q("SELECT * FROM `event` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
							dbesc($item_id),
							intval($importer['uid'])
						);
						if(count($r))
							$ev['id'] = $r[0]['id'];
						$xyz = event_store($ev);
						continue;
					}
				}

				$r = q("SELECT `uid`, `edited`, `body` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
					dbesc($item_id),
					intval($importer['uid'])
				);

				// Update content if 'updated' changes

				if(count($r)) {
					if((x($datarray,'edited') !== false) && (datetime_convert('UTC','UTC',$datarray['edited']) !== $r[0]['edited'])) {  

						// do not accept (ignore) an earlier edit than one we currently have.
						if(datetime_convert('UTC','UTC',$datarray['edited']) < $r[0]['edited'])
							continue;

						$r = q("UPDATE `item` SET `title` = '%s', `body` = '%s', `edited` = '%s' WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
							dbesc($datarray['title']),
							dbesc($datarray['body']),
							dbesc(datetime_convert('UTC','UTC',$datarray['edited'])),
							dbesc($item_id),
							intval($importer['uid'])
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


				if(! is_array($contact))
					return;


				// This is my contact on another system, but it's really me.
				// Turn this into a wall post.

				if($contact['remote_self']) {
					$datarray['wall'] = 1;
				}

				$datarray['parent_uri'] = $item_id;
				$datarray['uid'] = $importer['uid'];
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

				if(($contact['rel'] == CONTACT_IS_FOLLOWER) && (! tgroup_check($importer['uid'],$datarray)))
					continue;


				$r = item_store($datarray);
				continue;

			}
		}
	}
}

function local_delivery($importer,$data) {

	$a = get_app();

	if($importer['readonly']) {
		// We aren't receiving stuff from this person. But we will quietly ignore them
		// rather than a blatant "go away" message.
		logger('local_delivery: ignoring');
		return 0;
		//NOTREACHED
	}

	// Consume notification feed. This may differ from consuming a public feed in several ways
	// - might contain email or friend suggestions
	// - might contain remote followup to our message
	//		- in which case we need to accept it and then notify other conversants
	// - we may need to send various email notifications

	$feed = new SimplePie();
	$feed->set_raw_data($data);
	$feed->enable_order_by_date(false);
	$feed->init();


	if($feed->error())
		logger('local_delivery: Error parsing XML: ' . $feed->error());


	// Check at the feed level for updated contact name and/or photo

	$name_updated  = '';
	$new_name = '';
	$photo_timestamp = '';
	$photo_url = '';


	$rawtags = $feed->get_feed_tags( NAMESPACE_DFRN, 'owner');
	if(! $rawtags)
		$rawtags = $feed->get_feed_tags( SIMPLEPIE_NAMESPACE_ATOM_10, 'author');
	if($rawtags) {
		$elems = $rawtags[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10];
		if($elems['name'][0]['attribs'][NAMESPACE_DFRN]['updated']) {
			$name_updated = $elems['name'][0]['attribs'][NAMESPACE_DFRN]['updated'];
			$new_name = $elems['name'][0]['data'];
		} 
		if((x($elems,'link')) && ($elems['link'][0]['attribs']['']['rel'] === 'photo') && ($elems['link'][0]['attribs'][NAMESPACE_DFRN]['updated'])) {
			$photo_timestamp = datetime_convert('UTC','UTC',$elems['link'][0]['attribs'][NAMESPACE_DFRN]['updated']);
			$photo_url = $elems['link'][0]['attribs']['']['href'];
		}
	}

	if(($photo_timestamp) && (strlen($photo_url)) && ($photo_timestamp > $importer['avatar_date'])) {
		logger('local_delivery: Updating photo for ' . $importer['name']);
		require_once("Photo.php");
		$photo_failure = false;
		$have_photo = false;

		$r = q("SELECT `resource_id` FROM `photo` WHERE `contact-id` = %d AND `uid` = %d LIMIT 1",
			intval($importer['id']),
			intval($importer['importer_uid'])
		);
		if(count($r)) {
			$resource_id = $r[0]['resource_id'];
			$have_photo = true;
		}
		else {
			$resource_id = photo_new_resource();
		}
			
		$img_str = fetch_url($photo_url,true);
		// guess mimetype from headers or filename
		$type = guess_image_type($photo_url,true);
		
		
		$img = new Photo($img_str, $type);
		if($img->is_valid()) {
			if($have_photo) {
				q("DELETE FROM `photo` WHERE `resource_id` = '%s' AND `contact-id` = %d AND `uid` = %d",
					dbesc($resource_id),
					intval($importer['id']),
					intval($importer['importer_uid'])
				);
			}
				
			$img->scaleImageSquare(175);
				
			$hash = $resource_id;
			$r = $img->store($importer['importer_uid'], $importer['id'], $hash, basename($photo_url), 'Contact Photos', 4);
				
			$img->scaleImage(80);
			$r = $img->store($importer['importer_uid'], $importer['id'], $hash, basename($photo_url), 'Contact Photos', 5);

			$img->scaleImage(48);
			$r = $img->store($importer['importer_uid'], $importer['id'], $hash, basename($photo_url), 'Contact Photos', 6);

			$a = get_app();

			q("UPDATE `contact` SET `avatar_date` = '%s', `photo` = '%s', `thumb` = '%s', `micro` = '%s'  
				WHERE `uid` = %d AND `id` = %d LIMIT 1",
				dbesc(datetime_convert()),
				dbesc($a->get_baseurl() . '/photo/' . $hash . '-4.'.$img->getExt()),
				dbesc($a->get_baseurl() . '/photo/' . $hash . '-5.'.$img->getExt()),
				dbesc($a->get_baseurl() . '/photo/' . $hash . '-6.'.$img->getExt()),
				intval($importer['importer_uid']),
				intval($importer['id'])
			);
		}
	}

	if(($name_updated) && (strlen($new_name)) && ($name_updated > $importer['name_date'])) {
		$r = q("select * from contact where uid = %d and id = %d limit 1",
			intval($importer['importer_uid']),
			intval($importer['id'])
		);

		$x = q("UPDATE `contact` SET `name` = '%s', `name_date` = '%s' WHERE `uid` = %d AND `id` = %d LIMIT 1",
			dbesc(notags(trim($new_name))),
			dbesc(datetime_convert()),
			intval($importer['importer_uid']),
			intval($importer['id'])
		);

		// do our best to update the name on content items

		if(count($r)) {
			q("update item set `author-name` = '%s' where `author-name` = '%s' and `author-link` = '%s' and uid = %d",
				dbesc(notags(trim($new_name))),
				dbesc($r[0]['name']),
				dbesc($r[0]['url']),
				intval($importer['importer_uid'])
			);
		}
	}


/*
	// Currently unsupported - needs a lot of work
	$reloc = $feed->get_feed_tags( NAMESPACE_DFRN, 'relocate' );
	if(isset($reloc[0]['child'][NAMESPACE_DFRN])) {
		$base = $reloc[0]['child'][NAMESPACE_DFRN];
		$newloc = array();
		$newloc['uid'] = $importer['importer_uid'];
		$newloc['cid'] = $importer['id'];
		$newloc['name'] = notags(unxmlify($base['name'][0]['data']));
		$newloc['photo'] = notags(unxmlify($base['photo'][0]['data']));
		$newloc['url'] = notags(unxmlify($base['url'][0]['data']));
		$newloc['request'] = notags(unxmlify($base['request'][0]['data']));
		$newloc['confirm'] = notags(unxmlify($base['confirm'][0]['data']));
		$newloc['notify'] = notags(unxmlify($base['notify'][0]['data']));
		$newloc['poll'] = notags(unxmlify($base['poll'][0]['data']));
		$newloc['site_pubkey'] = notags(unxmlify($base['site_pubkey'][0]['data']));
		$newloc['pubkey'] = notags(unxmlify($base['pubkey'][0]['data']));
		$newloc['prvkey'] = notags(unxmlify($base['prvkey'][0]['data']));
		
		// TODO
		// merge with current record, current contents have priority
		// update record, set url-updated
		// update profile photos
		// schedule a scan?

	}
*/

	// handle friend suggestion notification

	$sugg = $feed->get_feed_tags( NAMESPACE_DFRN, 'suggest' );
	if(isset($sugg[0]['child'][NAMESPACE_DFRN])) {
		$base = $sugg[0]['child'][NAMESPACE_DFRN];
		$fsugg = array();
		$fsugg['uid'] = $importer['importer_uid'];
		$fsugg['cid'] = $importer['id'];
		$fsugg['name'] = notags(unxmlify($base['name'][0]['data']));
		$fsugg['photo'] = notags(unxmlify($base['photo'][0]['data']));
		$fsugg['url'] = notags(unxmlify($base['url'][0]['data']));
		$fsugg['request'] = notags(unxmlify($base['request'][0]['data']));
		$fsugg['body'] = escape_tags(unxmlify($base['note'][0]['data']));

		// Does our member already have a friend matching this description?

		$r = q("SELECT * FROM `contact` WHERE `name` = '%s' AND `nurl` = '%s' AND `uid` = %d LIMIT 1",
			dbesc($fsugg['name']),
			dbesc(normalise_link($fsugg['url'])),
			intval($fsugg['uid'])
		);
		if(count($r))
			return 0;

		// Do we already have an fcontact record for this person?

		$fid = 0;
		$r = q("SELECT * FROM `fcontact` WHERE `url` = '%s' AND `name` = '%s' AND `request` = '%s' LIMIT 1",
			dbesc($fsugg['url']),
			dbesc($fsugg['name']),
			dbesc($fsugg['request'])
		);
		if(count($r)) {
			$fid = $r[0]['id'];

			// OK, we do. Do we already have an introduction for this person ?
			$r = q("select id from intro where uid = %d and fid = %d limit 1",
				intval($fsugg['uid']),
				intval($fid)
			);
			if(count($r))
				return 0;
		}
		if(! $fid)
			$r = q("INSERT INTO `fcontact` ( `name`,`url`,`photo`,`request` ) VALUES ( '%s', '%s', '%s', '%s' ) ",
			dbesc($fsugg['name']),
			dbesc($fsugg['url']),
			dbesc($fsugg['photo']),
			dbesc($fsugg['request'])
		);
		$r = q("SELECT * FROM `fcontact` WHERE `url` = '%s' AND `name` = '%s' AND `request` = '%s' LIMIT 1",
			dbesc($fsugg['url']),
			dbesc($fsugg['name']),
			dbesc($fsugg['request'])
		);
		if(count($r)) {
			$fid = $r[0]['id'];
		}
		// database record did not get created. Quietly give up.
		else
			return 0;


		$hash = random_string();
 
		$r = q("INSERT INTO `intro` ( `uid`, `fid`, `contact-id`, `note`, `hash`, `datetime`, `blocked` )
			VALUES( %d, %d, %d, '%s', '%s', '%s', %d )",
			intval($fsugg['uid']),
			intval($fid),
			intval($fsugg['cid']),
			dbesc($fsugg['body']),
			dbesc($hash),
			dbesc(datetime_convert()),
			intval(0)
		);

		notification(array(
			'type'         => NOTIFY_SUGGEST,
			'notify_flags' => $importer['notify-flags'],
			'language'     => $importer['language'],
			'to_name'      => $importer['username'],
			'to_email'     => $importer['email'],
			'uid'          => $importer['importer_uid'],
			'item'         => $fsugg,
			'link'         => $a->get_baseurl() . '/notifications/intros',
			'source_name'  => $importer['name'],
			'source_link'  => $importer['url'],
			'source_photo' => $importer['photo'],
			'verb'         => ACTIVITY_REQ_FRIEND,
			'otype'        => 'intro'
		));

		return 0;
	}

	$ismail = false;

	$rawmail = $feed->get_feed_tags( NAMESPACE_DFRN, 'mail' );
	if(isset($rawmail[0]['child'][NAMESPACE_DFRN])) {

		logger('local_delivery: private message received');

		$ismail = true;
		$base = $rawmail[0]['child'][NAMESPACE_DFRN];

		$msg = array();
		$msg['uid'] = $importer['importer_uid'];
		$msg['from-name'] = notags(unxmlify($base['sender'][0]['child'][NAMESPACE_DFRN]['name'][0]['data']));
		$msg['from-photo'] = notags(unxmlify($base['sender'][0]['child'][NAMESPACE_DFRN]['avatar'][0]['data']));
		$msg['from-url'] = notags(unxmlify($base['sender'][0]['child'][NAMESPACE_DFRN]['uri'][0]['data']));
		$msg['contact-id'] = $importer['id'];
		$msg['title'] = notags(unxmlify($base['subject'][0]['data']));
		$msg['body'] = escape_tags(unxmlify($base['content'][0]['data']));
		$msg['seen'] = 0;
		$msg['replied'] = 0;
		$msg['uri'] = notags(unxmlify($base['id'][0]['data']));
		$msg['parent_uri'] = notags(unxmlify($base['in-reply-to'][0]['data']));
		$msg['created'] = datetime_convert(notags(unxmlify('UTC','UTC',$base['sentdate'][0]['data'])));
		
		dbesc_array($msg);

		$r = dbq("INSERT INTO `mail` (`" . implode("`, `", array_keys($msg)) 
			. "`) VALUES ('" . implode("', '", array_values($msg)) . "')" );

		// send notifications.

		require_once('include/enotify.php');

		$notif_params = array(
			'type' => NOTIFY_MAIL,
			'notify_flags' => $importer['notify-flags'],
			'language' => $importer['language'],
			'to_name' => $importer['username'],
			'to_email' => $importer['email'],
			'uid' => $importer['importer_uid'],
			'item' => $msg,
			'source_name' => $msg['from-name'],
			'source_link' => $importer['url'],
			'source_photo' => $importer['thumb'],
			'verb' => ACTIVITY_POST,
			'otype' => 'mail'
		);
			
		notification($notif_params);
		return 0;

		// NOTREACHED
	}	

	$community_page = 0;
	$rawtags = $feed->get_feed_tags( NAMESPACE_DFRN, 'community');
	if($rawtags) {
		$community_page = intval($rawtags[0]['data']);
	}
	if(intval($importer['forum']) != $community_page) {
		q("update contact set forum = %d where id = %d limit 1",
			intval($community_page),
			intval($importer['id'])
		);
		$importer['forum'] = (string) $community_page;
	}
	
	logger('local_delivery: feed item count = ' . $feed->get_item_quantity());

	// process any deleted entries

	$del_entries = $feed->get_feed_tags(NAMESPACE_TOMB, 'deleted-entry');
	if(is_array($del_entries) && count($del_entries)) {
		foreach($del_entries as $dentry) {
			$deleted = false;
			if(isset($dentry['attribs']['']['ref'])) {
				$uri = $dentry['attribs']['']['ref'];
				$deleted = true;
				if(isset($dentry['attribs']['']['when'])) {
					$when = $dentry['attribs']['']['when'];
					$when = datetime_convert('UTC','UTC', $when, 'Y-m-d H:i:s');
				}
				else
					$when = datetime_convert('UTC','UTC','now','Y-m-d H:i:s');
			}
			if($deleted) {

				// check for relayed deletes to our conversation

				$is_reply = false;		
				$r = q("select * from item where uri = '%s' and uid = %d limit 1",
					dbesc($uri),
					intval($importer['importer_uid'])
				);
				if(count($r)) {
					$parent_uri = $r[0]['parent_uri'];
					if($r[0]['id'] != $r[0]['parent'])
						$is_reply = true;
				}				

				if($is_reply) {
					$community = false;

					if($importer['page-flags'] == PAGE_COMMUNITY || $importer['page-flags'] == PAGE_PRVGROUP ) {
						$sql_extra = '';
						$community = true;
						logger('local_delivery: possible community delete');
					}
					else
						$sql_extra = " and contact.self = 1 and item.wall = 1 ";
 
					// was the top-level post for this reply written by somebody on this site? 
					// Specifically, the recipient? 

					$is_a_remote_delete = false;

					$r = q("select `item`.`id`, `item`.`uri`, `item`.`forum_mode`,`item`.`origin`,`item`.`wall`, 
						`contact`.`name`, `contact`.`url`, `contact`.`thumb` from `item` 
						LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id` 
						WHERE `item`.`uri` = '%s' AND (`item`.`parent_uri` = '%s' or `item`.`thr_parent` = '%s')
						AND `item`.`uid` = %d 
						$sql_extra
						LIMIT 1",
						dbesc($parent_uri),
						dbesc($parent_uri),
						dbesc($parent_uri),
						intval($importer['importer_uid'])
					);
					if($r && count($r))
						$is_a_remote_delete = true;

					// Does this have the characteristics of a community or private group comment?
					// If it's a reply to a wall post on a community/prvgroup page it's a 
					// valid community comment. Also forum_mode makes it valid for sure. 
					// If neither, it's not.

					if($is_a_remote_delete && $community) {
						if((! $r[0]['forum_mode']) && (! $r[0]['wall'])) {
							$is_a_remote_delete = false;
							logger('local_delivery: not a community delete');
						}
					}

					if($is_a_remote_delete) {
						logger('local_delivery: received remote delete');
					}
				}

				$r = q("SELECT `item`.*, `contact`.`self` FROM `item` left join contact on `item`.`contact-id` = `contact`.`id`
					WHERE `uri` = '%s' AND `item`.`uid` = %d AND `contact-id` = %d AND NOT `item`.`file` LIKE '%%[%%' LIMIT 1",
					dbesc($uri),
					intval($importer['importer_uid']),
					intval($importer['id'])
				);

				if(count($r)) {
					$item = $r[0];

					if($item['deleted'])
						continue;

					logger('local_delivery: deleting item ' . $item['id'] . ' uri=' . $item['uri'], LOGGER_DEBUG);

					if(($item['verb'] === ACTIVITY_TAG) && ($item['obj_type'] === ACTIVITY_OBJ_TAGTERM)) {
						$xo = parse_xml_string($item['object'],false);
						$xt = parse_xml_string($item['target'],false);

						if($xt->type === ACTIVITY_OBJ_NOTE) {
							$i = q("select * from `item` where uri = '%s' and uid = %d limit 1",
								dbesc($xt->id),
								intval($importer['importer_uid'])
							);
							if(count($i)) {

								// For tags, the owner cannot remove the tag on the author's copy of the post.
								
								$owner_remove = (($item['contact-id'] == $i[0]['contact-id']) ? true: false);
								$author_remove = (($item['origin'] && $item['self']) ? true : false);
								$author_copy = (($item['origin']) ? true : false); 

								if($owner_remove && $author_copy)
									continue;
								if($author_remove || $owner_remove) {								
//FIXME
									$tags = explode(',',$i[0]['tag']);
									$newtags = array();
									if(count($tags)) {
										foreach($tags as $tag)
											if(trim($tag) !== trim($xo->body))
												$newtags[] = trim($tag);
									}
									q("update item set tag = '%s' where id = %d limit 1",
										dbesc(implode(',',$newtags)),
										intval($i[0]['id'])
									);
								}
							}
						}
					}

					if($item['uri'] == $item['parent_uri']) {
						$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s',
							`body` = '', `title` = ''
							WHERE `parent_uri` = '%s' AND `uid` = %d",
							dbesc($when),
							dbesc(datetime_convert()),
							dbesc($item['uri']),
							intval($importer['importer_uid'])
						);
					}
					else {
						$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s',
							`body` = '', `title` = ''
							WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
							dbesc($when),
							dbesc(datetime_convert()),
							dbesc($uri),
							intval($importer['importer_uid'])
						);

						// if this is a relayed delete, propagate it to other recipients

						if($is_a_remote_delete)
							proc_run('php',"include/notifier.php","drop",$item['id']);
					}
				}
			}
		}
	}


	foreach($feed->get_items() as $item) {

		$is_reply = false;		
		$item_id = $item->get_id();
		$rawthread = $item->get_item_tags( NAMESPACE_THREAD, 'in-reply-to');
		if(isset($rawthread[0]['attribs']['']['ref'])) {
			$is_reply = true;
			$parent_uri = $rawthread[0]['attribs']['']['ref'];
		}

		if($is_reply) {
			$community = false;

			if($importer['page-flags'] == PAGE_COMMUNITY || $importer['page-flags'] == PAGE_PRVGROUP ) {
				$sql_extra = '';
				$community = true;
				logger('local_delivery: possible community reply');
			}
			else
				$sql_extra = " and contact.self = 1 and item.wall = 1 ";
 
			// was the top-level post for this reply written by somebody on this site? 
			// Specifically, the recipient? 

			$is_a_remote_comment = false;

			// POSSIBLE CLEANUP --> Why select so many fields when only forum_mode and wall are used?
			$r = q("select `item`.`id`, `item`.`uri`, `item`.`forum_mode`,`item`.`origin`,`item`.`wall`, 
				`contact`.`name`, `contact`.`url`, `contact`.`thumb` from `item` 
				LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id` 
				WHERE `item`.`uri` = '%s' AND (`item`.`parent_uri` = '%s' or `item`.`thr_parent` = '%s')
				AND `item`.`uid` = %d 
				$sql_extra
				LIMIT 1",
				dbesc($parent_uri),
				dbesc($parent_uri),
				dbesc($parent_uri),
				intval($importer['importer_uid'])
			);
			if($r && count($r))
				$is_a_remote_comment = true;			

			// Does this have the characteristics of a community or private group comment?
			// If it's a reply to a wall post on a community/prvgroup page it's a 
			// valid community comment. Also forum_mode makes it valid for sure. 
			// If neither, it's not.

			if($is_a_remote_comment && $community) {
				if((! $r[0]['forum_mode']) && (! $r[0]['wall'])) {
					$is_a_remote_comment = false;
					logger('local_delivery: not a community reply');
				}
			}

			if($is_a_remote_comment) {
				logger('local_delivery: received remote comment');
				$is_like = false;
				// remote reply to our post. Import and then notify everybody else.

				$datarray = get_atom_elements($feed,$item);

				$r = q("SELECT `id`, `uid`, `edited`, `body`  FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
					dbesc($item_id),
					intval($importer['importer_uid'])
				);

				// Update content if 'updated' changes

				if(count($r)) {
					$iid = $r[0]['id'];
					if((x($datarray,'edited') !== false) && (datetime_convert('UTC','UTC',$datarray['edited']) !== $r[0]['edited'])) {
					
						// do not accept (ignore) an earlier edit than one we currently have.
						if(datetime_convert('UTC','UTC',$datarray['edited']) < $r[0]['edited'])
							continue;
  
						logger('received updated comment' , LOGGER_DEBUG);
						$r = q("UPDATE `item` SET `title` = '%s', `body` = '%s', `edited` = '%s' WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
							dbesc($datarray['title']),
							dbesc($datarray['body']),
							dbesc(datetime_convert('UTC','UTC',$datarray['edited'])),
							dbesc($item_id),
							intval($importer['importer_uid'])
						);

						proc_run('php',"include/notifier.php","comment-import",$iid);

					}

					continue;
				}



				$own = q("select name,url,thumb from contact where uid = %d and self = 1 limit 1",
					intval($importer['importer_uid'])
				);


				$datarray['type'] = 'remote-comment';
				$datarray['wall'] = 1;
				$datarray['parent_uri'] = $parent_uri;
				$datarray['uid'] = $importer['importer_uid'];
				$datarray['owner-name'] = $own[0]['name'];
				$datarray['owner-link'] = $own[0]['url'];
				$datarray['owner-avatar'] = $own[0]['thumb'];
				$datarray['contact-id'] = $importer['id'];

				if(($datarray['verb'] === ACTIVITY_LIKE) || ($datarray['verb'] === ACTIVITY_DISLIKE)) {
					$is_like = true;
					$datarray['type'] = 'activity';
					$datarray['gravity'] = GRAVITY_LIKE;

					// only one like or dislike per person
					$r = q("select id from item where uid = %d and `contact-id` = %d and verb = '%s' and (`thr_parent` = '%s' or `parent_uri` = '%s') and deleted = 0 limit 1",
						intval($datarray['uid']),
						intval($datarray['contact-id']),
						dbesc($datarray['verb']),
						dbesc($datarray['parent_uri']),
						dbesc($datarray['parent_uri'])
		
					);
					if($r && count($r))
						continue; 
				}

				if(($datarray['verb'] === ACTIVITY_TAG) && ($datarray['obj_type'] === ACTIVITY_OBJ_TAGTERM)) {
					
					$xo = parse_xml_string($datarray['object'],false);
					$xt = parse_xml_string($datarray['target'],false);

					if(($xt->type == ACTIVITY_OBJ_NOTE) && ($xt->id)) {

						// fetch the parent item

						$tagp = q("select * from item where uri = '%s' and uid = %d limit 1",
							dbesc($xt->id),
							intval($importer['importer_uid'])
						);
						if(! count($tagp))
							continue;	

						// extract tag, if not duplicate, and this user allows tags, add to parent item						
//FIXME
						if($xo->id && $xo->content) {
							$newtag = '#[url=' . $xo->id . ']'. $xo->content . '[/url]';
							if(! (stristr($tagp[0]['tag'],$newtag))) {
								$i = q("SELECT `blocktags` FROM `user` where `uid` = %d LIMIT 1",
									intval($importer['importer_uid'])
								);
								if(count($i) && ! intval($i[0]['blocktags'])) {
									q("UPDATE item SET tag = '%s', `edited` = '%s' WHERE id = %d LIMIT 1",
										dbesc($tagp[0]['tag'] . (strlen($tagp[0]['tag']) ? ',' : '') . $newtag),
										intval($tagp[0]['id']),
										dbesc(datetime_convert())
									);
								}
							}
						}													
					}
				}


				$posted_id = item_store($datarray);
				$parent = 0;

				if($posted_id) {
					$r = q("SELECT `parent`, `parent_uri` FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
						intval($posted_id),
						intval($importer['importer_uid'])
					);
					if(count($r)) {
						$parent = $r[0]['parent'];
						$parent_uri = $r[0]['parent_uri'];
					}
			
					if(! $is_like) {
						$r1 = q("UPDATE `item` SET `changed` = '%s' WHERE `uid` = %d AND `parent` = %d",
							dbesc(datetime_convert()),
							intval($importer['importer_uid']),
							intval($r[0]['parent'])
						);

						$r2 = q("UPDATE `item` SET `changed` = '%s' WHERE `uid` = %d AND `id` = %d LIMIT 1",
							dbesc(datetime_convert()),
							intval($importer['importer_uid']),
							intval($posted_id)
						);
					}

					if($posted_id && $parent) {

						proc_run('php',"include/notifier.php","comment-import","$posted_id");
					
						if((! $is_like) && (! $importer['self'])) {

							require_once('include/enotify.php');

							notification(array(
								'type'         => NOTIFY_COMMENT,
								'notify_flags' => $importer['notify-flags'],
								'language'     => $importer['language'],
								'to_name'      => $importer['username'],
								'to_email'     => $importer['email'],
								'uid'          => $importer['importer_uid'],
								'item'         => $datarray,
								'link'		   => $a->get_baseurl() . '/display/' . $importer['nickname'] . '/' . $posted_id,
								'source_name'  => stripslashes($datarray['author-name']),
								'source_link'  => $datarray['author-link'],
								'source_photo' => ((link_compare($datarray['author-link'],$importer['url'])) 
									? $importer['thumb'] : $datarray['author-avatar']),
								'verb'         => ACTIVITY_POST,
								'otype'        => 'item',
								'parent'       => $parent,
								'parent_uri'   => $parent_uri,
							));

						}
					}

					return 0;
					// NOTREACHED
				}
			}
			else {

				// regular comment that is part of this total conversation. Have we seen it? If not, import it.

				$item_id  = $item->get_id();
				$datarray = get_atom_elements($feed,$item);

 				if($importer['rel'] == CONTACT_IS_FOLLOWER)
 					continue;
 

				$r = q("SELECT `uid`, `edited`, `body` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
					dbesc($item_id),
					intval($importer['importer_uid'])
				);

				// Update content if 'updated' changes

				if(count($r)) {
					if((x($datarray,'edited') !== false) && (datetime_convert('UTC','UTC',$datarray['edited']) !== $r[0]['edited'])) {  

						// do not accept (ignore) an earlier edit than one we currently have.
						if(datetime_convert('UTC','UTC',$datarray['edited']) < $r[0]['edited'])
							continue;

						$r = q("UPDATE `item` SET `title` = '%s', `body` = '%s', `edited` = '%s' WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
							dbesc($datarray['title']),
							dbesc($datarray['body']),
							dbesc(datetime_convert('UTC','UTC',$datarray['edited'])),
							dbesc($item_id),
							intval($importer['importer_uid'])
						);
					}

					continue;
				}

				$datarray['parent_uri'] = $parent_uri;
				$datarray['uid'] = $importer['importer_uid'];
				$datarray['contact-id'] = $importer['id'];
				if(($datarray['verb'] == ACTIVITY_LIKE) || ($datarray['verb'] == ACTIVITY_DISLIKE)) {
					$datarray['type'] = 'activity';
					$datarray['gravity'] = GRAVITY_LIKE;
					// only one like or dislike per person
					$r = q("select id from item where uid = %d and `contact-id` = %d and verb ='%s' and deleted = 0 and (`parent_uri` = '%s' OR `thr_parent` = '%s') limit 1",
						intval($datarray['uid']),
						intval($datarray['contact-id']),
						dbesc($datarray['verb']),
						dbesc($parent_uri),
						dbesc($parent_uri)
					);
					if($r && count($r))
						continue; 

				}

				if(($datarray['verb'] === ACTIVITY_TAG) && ($datarray['obj_type'] === ACTIVITY_OBJ_TAGTERM)) {

					$xo = parse_xml_string($datarray['object'],false);
					$xt = parse_xml_string($datarray['target'],false);

					if($xt->type == ACTIVITY_OBJ_NOTE) {
						$r = q("select * from item where `uri` = '%s' AND `uid` = %d limit 1",
							dbesc($xt->id),
							intval($importer['importer_uid'])
						);
						if(! count($r))
							continue;				

						// extract tag, if not duplicate, add to parent item						
						if($xo->content) {
							if(! (stristr($r[0]['tag'],trim($xo->content)))) {
								q("UPDATE item SET tag = '%s' WHERE id = %d LIMIT 1",
									dbesc($r[0]['tag'] . (strlen($r[0]['tag']) ? ',' : '') . '#[url=' . $xo->id . ']'. $xo->content . '[/url]'),
									intval($r[0]['id'])
								);
							}
						}													
					}
				}

				$posted_id = item_store($datarray);

				// find out if our user is involved in this conversation and wants to be notified.
			
				if(!x($datarray['type']) || $datarray['type'] != 'activity') {

					$myconv = q("SELECT `author-link`, `author-avatar`, `parent` FROM `item` WHERE `parent_uri` = '%s' AND `uid` = %d AND `parent` != 0 AND `deleted` = 0",
						dbesc($parent_uri),
						intval($importer['importer_uid'])
					);

					if(count($myconv)) {
						$importer_url = $a->get_baseurl() . '/channel/' . $importer['nickname'];

						// first make sure this isn't our own post coming back to us from a wall-to-wall event
						if(! link_compare($datarray['author-link'],$importer_url)) {

							
							foreach($myconv as $conv) {

								// now if we find a match, it means we're in this conversation
	
								if(! link_compare($conv['author-link'],$importer_url))
									continue;

								require_once('include/enotify.php');
								
								$conv_parent = $conv['parent'];

								notification(array(
									'type'         => NOTIFY_COMMENT,
									'notify_flags' => $importer['notify-flags'],
									'language'     => $importer['language'],
									'to_name'      => $importer['username'],
									'to_email'     => $importer['email'],
									'uid'          => $importer['importer_uid'],
									'item'         => $datarray,
									'link'		   => $a->get_baseurl() . '/display/' . $importer['nickname'] . '/' . $posted_id,
									'source_name'  => stripslashes($datarray['author-name']),
									'source_link'  => $datarray['author-link'],
									'source_photo' => ((link_compare($datarray['author-link'],$importer['url'])) 
										? $importer['thumb'] : $datarray['author-avatar']),
									'verb'         => ACTIVITY_POST,
									'otype'        => 'item',
									'parent'       => $conv_parent,
									'parent_uri'   => $parent_uri

								));

								// only send one notification
								break;
							}
						}
					}
				}
				continue;
			}
		}

		else {

			// Head post of a conversation. Have we seen it? If not, import it.


			$item_id  = $item->get_id();
			$datarray = get_atom_elements($feed,$item);

			if((x($datarray,'obj_type')) && ($datarray['obj_type'] === ACTIVITY_OBJ_EVENT)) {
				$ev = bbtoevent($datarray['body']);
				if(x($ev,'desc') && x($ev,'start')) {
					$ev['cid'] = $importer['id'];
					$ev['uid'] = $importer['uid'];
					$ev['uri'] = $item_id;
					$ev['edited'] = $datarray['edited'];
					$ev['private'] = $datarray['private'];

					$r = q("SELECT * FROM `event` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
						dbesc($item_id),
						intval($importer['uid'])
					);
					if(count($r))
						$ev['id'] = $r[0]['id'];
					$xyz = event_store($ev);
					continue;
				}
			}

			$r = q("SELECT `uid`, `edited`, `body` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
				dbesc($item_id),
				intval($importer['importer_uid'])
			);

			// Update content if 'updated' changes

			if(count($r)) {
				if((x($datarray,'edited') !== false) && (datetime_convert('UTC','UTC',$datarray['edited']) !== $r[0]['edited'])) {  

					// do not accept (ignore) an earlier edit than one we currently have.
					if(datetime_convert('UTC','UTC',$datarray['edited']) < $r[0]['edited'])
						continue;

					$r = q("UPDATE `item` SET `title` = '%s', `body` = '%s', `edited` = '%s' WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
						dbesc($datarray['title']),
						dbesc($datarray['body']),
						dbesc(datetime_convert('UTC','UTC',$datarray['edited'])),
						dbesc($item_id),
						intval($importer['importer_uid'])
					);
				}

				continue;
			}

			// This is my contact on another system, but it's really me.
			// Turn this into a wall post.

			if($importer['remote_self'])
				$datarray['wall'] = 1;

			$datarray['parent_uri'] = $item_id;
			$datarray['uid'] = $importer['importer_uid'];
			$datarray['contact-id'] = $importer['id'];


			if(! link_compare($datarray['owner-link'],$importer['url'])) {
				// The item owner info is not our contact. It's OK and is to be expected if this is a tgroup delivery, 
				// but otherwise there's a possible data mixup on the sender's system.
				// the tgroup delivery code called from item_store will correct it if it's a forum,
				// but we're going to unconditionally correct it here so that the post will always be owned by our contact. 
				logger('local_delivery: Correcting item owner.', LOGGER_DEBUG);
				$datarray['owner-name']   = $importer['senderName'];
				$datarray['owner-link']   = $importer['url'];
				$datarray['owner-avatar'] = $importer['thumb'];
			}

			if(($importer['rel'] == CONTACT_IS_FOLLOWER) && (! tgroup_check($importer['importer_uid'],$datarray)))
				continue;

			$posted_id = item_store($datarray);

			if(stristr($datarray['verb'],ACTIVITY_POKE)) {
				$verb = urldecode(substr($datarray['verb'],strpos($datarray['verb'],'#')+1));
				if(! $verb)
					continue;
				$xo = parse_xml_string($datarray['object'],false);

				if(($xo->type == ACTIVITY_OBJ_PERSON) && ($xo->id)) {

					// somebody was poked/prodded. Was it me?

					$links = parse_xml_string("<links>".unxmlify($xo->link)."</links>",false);

			        foreach($links->link as $l) {
            			$atts = $l->attributes();
            			switch($atts['rel']) {
                			case "alternate": 
								$Blink = $atts['href'];
								break;
							default:
								break;
			            }
        			}
					if($Blink && link_compare($Blink,$a->get_baseurl() . '/channel/' . $importer['nickname'])) {

						// send a notification
						require_once('include/enotify.php');
								
						notification(array(
							'type'         => NOTIFY_POKE,
							'notify_flags' => $importer['notify-flags'],
							'language'     => $importer['language'],
							'to_name'      => $importer['username'],
							'to_email'     => $importer['email'],
							'uid'          => $importer['importer_uid'],
							'item'         => $datarray,
							'link'		   => $a->get_baseurl() . '/display/' . $importer['nickname'] . '/' . $posted_id,
							'source_name'  => stripslashes($datarray['author-name']),
							'source_link'  => $datarray['author-link'],
							'source_photo' => ((link_compare($datarray['author-link'],$importer['url'])) 
								? $importer['thumb'] : $datarray['author-avatar']),
							'verb'         => $datarray['verb'],
							'otype'        => 'person',
							'activity'     => $verb,

						));
					}
				}
			}			

			continue;
		}
	}

	return 0;
	// NOTREACHED

}


function new_follower($importer,$contact,$datarray,$item,$sharing = false) {
	$url = notags(trim($datarray['author-link']));
	$name = notags(trim($datarray['author-name']));
	$photo = notags(trim($datarray['author-avatar']));

	$rawtag = $item->get_item_tags(NAMESPACE_ACTIVITY,'actor');
	if($rawtag && $rawtag[0]['child'][NAMESPACE_POCO]['preferredUsername'][0]['data'])
		$nick = $rawtag[0]['child'][NAMESPACE_POCO]['preferredUsername'][0]['data'];

	if(is_array($contact)) {
		if(($contact['network'] == NETWORK_OSTATUS && $contact['rel'] == CONTACT_IS_SHARING)
			|| ($sharing && $contact['rel'] == CONTACT_IS_FOLLOWER)) {
			$r = q("UPDATE `contact` SET `rel` = %d WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval(CONTACT_IS_FRIEND),
				intval($contact['id']),
				intval($importer['uid'])
			);
		}
		// send email notification to owner?
	}
	else {
	
		// create contact record

		$r = q("INSERT INTO `contact` ( `uid`, `created`, `url`, `nurl`, `name`, `nick`, `photo`, `network`, `rel`, 
			`blocked`, `readonly`, `pending`, `writable` )
			VALUES ( %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, 0, 0, 1, 1 ) ",
			intval($importer['uid']),
			dbesc(datetime_convert()),
			dbesc($url),
			dbesc(normalise_link($url)),
			dbesc($name),
			dbesc($nick),
			dbesc($photo),
			dbesc(($sharing) ? NETWORK_ZOT : NETWORK_OSTATUS),
			intval(($sharing) ? CONTACT_IS_SHARING : CONTACT_IS_FOLLOWER)
		);
		$r = q("SELECT `id` FROM `contact` WHERE `uid` = %d AND `url` = '%s' AND `pending` = 1 LIMIT 1",
				intval($importer['uid']),
				dbesc($url)
		);
		if(count($r))
				$contact_record = $r[0];

		// create notification	
		$hash = random_string();

		if(is_array($contact_record)) {
			$ret = q("INSERT INTO `intro` ( `uid`, `contact-id`, `blocked`, `knowyou`, `hash`, `datetime`)
				VALUES ( %d, %d, 0, 0, '%s', '%s' )",
				intval($importer['uid']),
				intval($contact_record['id']),
				dbesc($hash),
				dbesc(datetime_convert())
			);
		}
		$r = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
			intval($importer['uid'])
		);
		$a = get_app();
		if(count($r)) {

			if(intval($r[0]['def_gid'])) {
				require_once('include/group.php');
				group_add_member($r[0]['uid'],'',$contact_record['id'],$r[0]['def_gid']);
			}

			if(($r[0]['notify-flags'] & NOTIFY_INTRO) && ($r[0]['page-flags'] == PAGE_NORMAL)) {

				$email_tpl = get_intltext_template('follow_notify_eml.tpl');
				$email = replace_macros($email_tpl, array(
					'$requestor' => ((strlen($name)) ? $name : t('[Name Withheld]')),
					'$url' => $url,
					'$myname' => $r[0]['username'],
					'$siteurl' => $a->get_baseurl(),
					'$sitename' => $a->config['sitename']
				));

				$res = mail($r[0]['email'], 
					(($sharing) ? t('A new person is sharing with you at ') : t("You have a new follower at ")) . $a->config['sitename'],
					$email,
					'From: ' . t('Administrator') . '@' . $_SERVER['SERVER_NAME'] . "\n"
					. 'Content-type: text/plain; charset=UTF-8' . "\n"
					. 'Content-transfer-encoding: 8bit' );
			
			}
		}
	}
}

function lose_follower($importer,$contact,$datarray,$item) {

	if(($contact['rel'] == CONTACT_IS_FRIEND) || ($contact['rel'] == CONTACT_IS_SHARING)) {
		q("UPDATE `contact` SET `rel` = %d WHERE `id` = %d LIMIT 1",
			intval(CONTACT_IS_SHARING),
			intval($contact['id'])
		);
	}
	else {
		contact_remove($contact['id']);
	}
}

function lose_sharer($importer,$contact,$datarray,$item) {

	if(($contact['rel'] == CONTACT_IS_FRIEND) || ($contact['rel'] == CONTACT_IS_FOLLOWER)) {
		q("UPDATE `contact` SET `rel` = %d WHERE `id` = %d LIMIT 1",
			intval(CONTACT_IS_FOLLOWER),
			intval($contact['id'])
		);
	}
	else {
		contact_remove($contact['id']);
	}
}


function atom_author($tag,$name,$uri,$h,$w,$photo) {
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
	$o .= '<link rel="photo"  type="image/jpeg" media:width="' . $w . '" media:height="' . $h . '" href="' . $photo . '" />' . "\r\n";
	$o .= '<link rel="avatar" type="image/jpeg" media:width="' . $w . '" media:height="' . $h . '" href="' . $photo . '" />' . "\r\n";

	call_hooks('atom_author', $o);

	$o .= "</$tag>\r\n";
	return $o;
}

function atom_entry($item,$type,$author,$owner,$comment = false,$cid = 0) {

	$a = get_app();

	if(! $item['parent'])
		return;

	if($item['deleted'])
		return '<at:deleted-entry ref="' . xmlify($item['uri']) . '" when="' . xmlify(datetime_convert('UTC','UTC',$item['edited'] . '+00:00',ATOM_TIME)) . '" />' . "\r\n";


	if($item['allow_cid'] || $item['allow_gid'] || $item['deny_cid'] || $item['deny_gid'])
		$body = fix_private_photos($item['body'],$owner['uid'],$item,$cid);
	else
		$body = $item['body'];


	$o = "\r\n\r\n<entry>\r\n";

	if(is_array($author))
		$o .= atom_author('author',$author['name'],$author['url'],80,80,$author['thumb']);
	else
		$o .= atom_author('author',(($item['author-name']) ? $item['author-name'] : $item['name']),(($item['author-link']) ? $item['author-link'] : $item['url']),80,80,(($item['author-avatar']) ? $item['author-avatar'] : $item['thumb']));
	if(strlen($item['owner-name']))
		$o .= atom_author('dfrn:owner',$item['owner-name'],$item['owner-link'],80,80,$item['owner-avatar']);

	if(($item['parent'] != $item['id']) || ($item['parent_uri'] !== $item['uri']) || (($item['thr_parent'] !== '') && ($item['thr_parent'] !== $item['uri']))) {
		$parent_item = (($item['thr_parent']) ? $item['thr_parent'] : $item['parent_uri']);
		$o .= '<thr:in-reply-to ref="' . xmlify($parent_item) . '" type="text/html" href="' .  xmlify($a->get_baseurl() . '/display/' . $owner['nickname'] . '/' . $item['parent']) . '" />' . "\r\n";
	}

	$o .= '<id>' . xmlify($item['uri']) . '</id>' . "\r\n";
	$o .= '<title>' . xmlify($item['title']) . '</title>' . "\r\n";
	$o .= '<published>' . xmlify(datetime_convert('UTC','UTC',$item['created'] . '+00:00',ATOM_TIME)) . '</published>' . "\r\n";
	$o .= '<updated>' . xmlify(datetime_convert('UTC','UTC',$item['edited'] . '+00:00',ATOM_TIME)) . '</updated>' . "\r\n";
	$o .= '<dfrn:env>' . base64url_encode($body, true) . '</dfrn:env>' . "\r\n";
	$o .= '<content type="' . $type . '" >' . xmlify((($type === 'html') ? bbcode($body) : $body)) . '</content>' . "\r\n";
	$o .= '<link rel="alternate" type="text/html" href="' . xmlify($a->get_baseurl() . '/display/' . $owner['nickname'] . '/' . $item['id']) . '" />' . "\r\n";

	if($item['location']) {
		$o .= '<dfrn:location>' . xmlify($item['location']) . '</dfrn:location>' . "\r\n";
		$o .= '<poco:address><poco:formatted>' . xmlify($item['location']) . '</poco:formatted></poco:address>' . "\r\n";
	}

	if($item['coord'])
		$o .= '<georss:point>' . xmlify($item['coord']) . '</georss:point>' . "\r\n";

	if(($item['private']) || strlen($item['allow_cid']) || strlen($item['allow_gid']) || strlen($item['deny_cid']) || strlen($item['deny_gid']))
		$o .= '<dfrn:private>' . (($item['private']) ? $item['private'] : 1) . '</dfrn:private>' . "\r\n";


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

	$tags = item_getfeedtags($item);
	if(count($tags)) {
		foreach($tags as $t) {
			$o .= '<category scheme="X-DFRN:' . xmlify($t[0]) . ':' . xmlify($t[1]) . '" term="' . xmlify($t[2]) . '" />' . "\r\n";
		}
	}

	$o .= item_getfeedattach($item);

	$mentioned = get_mentions($item,$tags);
	if($mentioned)
		$o .= $mentioned;
	
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

	$img_start = strpos($orig_body, '[img');
	$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
	$img_len = ($img_start !== false ? strpos(substr($orig_body, $img_start + $img_st_close + 1), '[/img]') : false);
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
						if(preg_match("/\[img\=([0-9]*)x([0-9]*)\]/is", substr($orig_body, $img_start, $img_st_close), $match)) {
							logger('fix_private_photos: scaling photo', LOGGER_DEBUG);

							$width = intval($match[1]);
							$height = intval($match[2]);

							$ph = new Photo($data, $type);
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

		$new_body = $new_body . substr($orig_body, 0, $img_start + $img_st_close) . $image . '[/img]';
		$orig_body = substr($orig_body, $img_start + $img_st_close + $img_len + strlen('[/img]'));
		if($orig_body === false)
			$orig_body = '';

		$img_start = strpos($orig_body, '[img');
		$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
		$img_len = ($img_start !== false ? strpos(substr($orig_body, $img_start + $img_st_close + 1), '[/img]') : false);
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
	$sql_extra = ((intval($expire_network_only)) ? " AND wall = 0 " : "");

	$r = q("SELECT * FROM `item` 
		WHERE `uid` = %d 
		AND `created` < UTC_TIMESTAMP() - INTERVAL %d DAY 
		AND `id` = `parent` 
		$sql_extra
		AND `deleted` = 0",
		intval($uid),
		intval($days)
	);

	if(! count($r))
		return;

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

		if(strpos($item['file'],'[') !== false)
			continue;

		// Only expire posts, not photos and photo comments

		if($expire_photos==0 && strlen($item['resource_id']))
			continue;
		if($expire_starred==0 && intval($item['starred']))
			continue;
		if($expire_notes==0 && $item['type']=='note')
			continue;
		if($expire_items==0 && $item['type']!='note')
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


function drop_item($id,$interactive = true) {

	$a = get_app();

	// locate item to be deleted

	$r = q("SELECT * FROM `item` WHERE `id` = %d LIMIT 1",
		intval($id)
	);

	if(! count($r)) {
		if(! $interactive)
			return 0;
		notice( t('Item not found.') . EOL);
		goaway($a->get_baseurl() . '/' . $_SESSION['return_url']);
	}

	$item = $r[0];

	$owner = $item['uid'];

	$cid = 0;

	// check if logged in user is either the author or owner of this item

	if(is_array($_SESSION['remote'])) {
		foreach($_SESSION['remote'] as $visitor) {
			if($visitor['uid'] == $item['uid'] && $visitor['cid'] == $item['contact-id']) {
				$cid = $visitor['cid'];
				break;
			}
		}
	}


	if((local_user() == $item['uid']) || ($cid) || (! $interactive)) {

		// delete the item

		$r = q("UPDATE `item` SET `deleted` = 1, `title` = '', `body` = '', `edited` = '%s', `changed` = '%s' WHERE `id` = %d LIMIT 1",
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			intval($item['id'])
		);

		// clean up categories and tags so they don't end up as orphans

		$matches = false;
		$cnt = preg_match_all('/<(.*?)>/',$item['file'],$matches,PREG_SET_ORDER);
		if($cnt) {
			foreach($matches as $mtch) {
				file_tag_unsave_file($item['uid'],$item['id'],$mtch[1],true);
			}
		}

		$matches = false;

		$cnt = preg_match_all('/\[(.*?)\]/',$item['file'],$matches,PREG_SET_ORDER);
		if($cnt) {
			foreach($matches as $mtch) {
				file_tag_unsave_file($item['uid'],$item['id'],$mtch[1],false);
			}
		}

		// If item is a link to a photo resource, nuke all the associated photos 
		// (visitors will not have photo resources)
		// This only applies to photos uploaded from the photos page. Photos inserted into a post do not
		// generate a resource_id and therefore aren't intimately linked to the item. 

		if(strlen($item['resource_id'])) {
			q("DELETE FROM `photo` WHERE `resource_id` = '%s' AND `uid` = %d ",
				dbesc($item['resource_id']),
				intval($item['uid'])
			);
			// ignore the result
		}

		// If item is a link to an event, nuke the event record.

		if(intval($item['event-id'])) {
			q("DELETE FROM `event` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($item['event-id']),
				intval($item['uid'])
			);
			// ignore the result
		}

		// clean up item_id and sign meta-data tables

		$r = q("DELETE FROM item_id where iid in (select id from item where parent = %d and uid = %d)",
			intval($item['id']),
			intval($item['uid'])
		);

		$r = q("DELETE FROM sign where iid in (select id from item where parent = %d and uid = %d)",
			intval($item['id']),
			intval($item['uid'])
		);

		// If it's the parent of a comment thread, kill all the kids

		if($item['uri'] == $item['parent_uri']) {
			$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s', `body` = '' , `title` = ''
				WHERE `parent_uri` = '%s' AND `uid` = %d ",
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				dbesc($item['parent_uri']),
				intval($item['uid'])
			);
			// ignore the result
		}

		$drop_id = intval($item['id']);

		// send the notification upstream/downstream as the case may be

		if(! $interactive)
			return $owner;

		proc_run('php',"include/notifier.php","drop","$drop_id");
		goaway($a->get_baseurl() . '/' . $_SESSION['return_url']);
		//NOTREACHED
	}
	else {
		if(! $interactive)
			return 0;
		notice( t('Permission denied.') . EOL);
		goaway($a->get_baseurl() . '/' . $_SESSION['return_url']);
		//NOTREACHED
	}
	
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


function fetch_post_tags($items) {

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



function zot_feed($uid,$observer,$mindate) {

	$result = array();
	$mindate = datetime_convert('UTC','UTC',$mindate);
	if(! $mindate)
		$mindate = '0000-00-00 00:00:00';

	if(! perm_is_allowed($uid,$observer,'view_stream')) {
		return $result;
	}

// FIXME
	$sql_extra = item_permissions_sql($uid,$remote_contact,$groups);

	if($mindate != '0000-00-00 00:00:00')
		$sql_extra .= " and created > '$mindate' ";


// FIXME
	// We probably should use two queries and pick up total conversations.
	// For now get a chunk of raw posts in ascending created order so that 
	// hopefully the parent is imported before we see the kids. 
	// This will fail if there are more than $limit kids and you didn't 
	// receive the parent via direct delivery

	$limit = 200;

	$items = q("SELECT item.* from item
		WHERE uid = %d AND item_restrict = 0
		AND (item_flags &  %d) 
		$sql_extra ORDER BY created ASC limit 0, $limit",
		intval($uid),
		intval(ITEM_WALL)
	);
	if($items) {
		xchan_query($items);
		$items = fetch_post_tags($items);
	} else {
		$items = array();
	}

	foreach($items as $item)
		$result[] = encode_item($item);

	return $result;

}
