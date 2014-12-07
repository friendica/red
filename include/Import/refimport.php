<?php

require_once('include/html2bbcode.php');
require_once('include/hubloc.php');

// Sample module for importing conversation data from Reflection CMS. Some preparation was used to 
// dump relevant posts, categories and comments into individual JSON files, and also JSON dump of 
// the user table to search for avatars. Importation was also batched in sets of 20 posts per page
// visit so as to survive shared hosting process limits. This provides some clues as how to handle 
// WordPress imports, which use a somewhat similar DB structure. The batching and individual files
// might not be needed in VPS environments. As such this could be considered an extreme test case, but
// the importation was successful in all regards using this code. The module URL was visited repeatedly
// with a browser until all the posts had been imported.     


define('REDMATRIX_IMPORTCHANNEL','mike');
define('REFLECT_EXPORTUSERNAME','mike');
define('REFLECT_BLOGNAME','Diary and Other Rantings');
define('REFLECT_BASEURL','http://example.com/');
define('REFLECT_USERFILE','user.json');

// set to true if you need to process everything again
define('REFLECT_OVERWRITE',false);

// we'll only process a small number of posts at a time on a shared host.

define('REFLECT_MAXPERRUN',30);

function reflect_get_channel() {

	// this will be the channel_address or nickname of the red channel

	$c = q("select * from channel left join xchan on channel_hash = xchan_hash 
		where channel_address = '%s' limit 1",
		dbesc(REDMATRIX_IMPORTCHANNEL)
	);
	return $c[0];
}


function refimport_content(&$a) {

	$channel = reflect_get_channel();

	// load the user file. We need that to find the commenter's avatars

	$u = file_get_contents(REFLECT_USERFILE);
	if($u) {
		$users = json_decode($u,true);
	}

	$ignored = 0;
	$processed = 0;

	$files = glob('article/*');
	if(! $files) 
		return;

	foreach($files as $f) {
		$s = file_get_contents($f);
		$j = json_decode($s,true);

		if(! $j)
			continue;

		$arr = array();

		// see if this article was already processed
		$r = q("select * from item where mid = '%s' and uid = %d limit 1",
			dbesc($j['guid']),
			intval($channel['channel_id'])
		);
		if($r) {
			if(REFLECT_OVERWRITE)
				$arr['id'] = $r[0]['id'];
			else {
				$ignored ++;
				rename($f,str_replace('article','done',$f));
				continue;
			}
		}

		$arr['uid'] = $channel['channel_account_id'];
		$arr['aid'] = $channel['channel_id'];
		$arr['mid'] = $arr['parent_mid'] = $j['guid'];
		$arr['created'] = $j['created'];
		$arr['edited'] = $j['edited'];
		$arr['author_xchan'] = $channel['channel_hash'];
		$arr['owner_xchan'] = $channel['channel_hash'];
		$arr['app'] = REFLECT_BLOGNAME;
		$arr['item_flags'] = ITEM_ORIGIN|ITEM_WALL|ITEM_THREAD_TOP;
		$arr['verb'] = ACTIVITY_POST;

		// this is an assumption
		$arr['comment_policy'] = 'contacts';


		// import content. In this case the content is XHTML.

		$arr['title'] = html2bbcode($j['title']);
		$arr['title'] = htmlspecialchars($arr['title'],ENT_COMPAT,'UTF-8',false);


		$arr['body'] = html2bbcode($j['body']);
		$arr['body'] = htmlspecialchars($arr['body'],ENT_COMPAT,'UTF-8',false);


		// convert relative urls to other posts on that service to absolute url on our service.
		$arr['body'] = preg_replace_callback("/\[url\=\/+article\/(.*?)\](.*?)\[url\]/",'reflect_article_callback',$arr['body']);

		// also import any photos
		$arr['body'] = preg_replace_callback("/\[img(.*?)\](.*?)\[\/img\]/",'reflect_photo_callback',$arr['body']);


		// add categories

		if($j['taxonomy'] && is_array($j['taxonomy']) && count($j['taxonomy'])) {
			$arr['term'] = array();
			foreach($j['taxonomy'] as $tax) {
				$arr['term'][] = array(
					'uid'   => $channel['channel_id'],
					'type'  => TERM_CATEGORY,
					'otype' => TERM_OBJ_POST,
					'term'  => trim($tax['name']),
            	    'url'   => $channel['xchan_url'] . '?f=&cat=' . urlencode(trim($tax['name']))
				);
			}
		}

		// store the item

		if($arr['id'])
			item_store_update($arr);
		else
			item_store($arr);

		// if there are any comments, process them
		// $comment['registered'] is somebody with an account on the system. Others are mostly anonymous

		if($j['comments']) {
			foreach($j['comments'] as $comment) {
				$user = (($comment['registered']) ? reflect_find_user($users,$comment['author']) : null);
				reflect_comment_store($channel,$arr,$comment,$user);
			}
		}
		$processed ++;

		if(REFLECT_MAXPERRUN && $processed > REFLECT_MAXPERRUN)
			break;
	}
	return 'processed: ' . $processed . EOL . 'completed: ' . $ignored . EOL;

}

function reflect_article_callback($matches) {
	return '[zrl=' . z_root() . '/display/'. $matches[1] . ']' . $matches[2] . '[/zrl]';
}

function reflect_photo_callback($matches) {

	if(strpos($matches[2],'http') !== false)
		return $matches[0];

	$prefix = REFLECT_BASEURL;
	$x = z_fetch_url($prefix.$matches[2],true);

	$hash = basename($matches[2]);

	if($x['success']) {
		$channel = reflect_get_channel();
		require_once('include/photos.php');
		$p = photo_upload($channel,$channel,
			array('data' => $x['body'],
				'resource_id' => str_replace('-','',$hash),
				'filename' => $hash . '.jpg',
				'type' => 'image/jpeg',
				'not_visible' => true
			)
		);

		if($p['success'])
			$newlink = $p['resource_id'] . '-0.jpg';
	
				
		// import photo and locate the link for it.
		return '[zmg]' . z_root() . '/photo/' . $newlink . '[/zmg]';

	}		
	// no replacement. Leave it alone.
	return $matches[0];
}

function reflect_find_user($users,$name) {
	if($users) {
		foreach($users as $x) {
			if($x['name'] === $name) {
				return $x;
			}
		}
	}

	return false;

}

function reflect_comment_store($channel,$post,$comment,$user) {

	// if the commenter was the channel owner, use their redmatrix xchan

	if($comment['author'] === REFLECT_EXPORTUSERNAME && $comment['registered'])
		$hash = $channel['xchan_hash'];
	else {
		// we need a unique hash for the commenter. We don't know how many may have supplied
		// http://yahoo.com as their URL, so we'll use their avatar guid if they have one. 
		// anonymous folks may get more than one xchan_hash if they commented more than once.

		$hash = (($comment['registered'] && $user) ? $user['avatar'] : '');
		if(! $hash)
			$hash = random_string() . '.unknown';

		// create an xchan for them which will also import their profile photo
		// they will have a network type 'unknown'.

		$x = array(
			'hash' => $hash,
			'guid' => $hash,
			'url' => (($comment['url']) ? $comment['url'] : z_root()),
			'photo' => (($user) ? REFLECT_BASEURL . $user['avatar'] : z_root() . '/' . get_default_profile_photo()),
			'name' => $comment['author']
		);
		xchan_store($x);

	}

	$arr = array();

	$r = q("select * from item where mid = '%s' and uid = %d limit 1",
		dbesc($comment['guid']),
		intval($channel['channel_id'])
	);
	if($r) {
		if(REFLECT_OVERWRITE)
			$arr['id'] = $r[0]['id'];
		else
			return;
	}

	// this is a lot like storing the post except for subtle differences, like parent_mid, flags, author_xchan,
	// and we don't have a comment edited field so use creation date

	$arr['uid'] = $channel['channel_account_id'];
	$arr['aid'] = $channel['channel_id'];
	$arr['mid'] = $comment['guid'];
	$arr['parent_mid'] = $post['mid'];
	$arr['created'] = $comment['created'];
	$arr['edited'] = $comment['created'];
	$arr['author_xchan'] = $hash;
	$arr['owner_xchan'] = $channel['channel_hash'];
	$arr['item_flags'] = ITEM_ORIGIN|ITEM_WALL;
	$arr['verb'] = ACTIVITY_POST;
	$arr['comment_policy'] = 'contacts';


	$arr['title'] = html2bbcode($comment['title']);
	$arr['title'] = htmlspecialchars($arr['title'],ENT_COMPAT,'UTF-8',false);


	$arr['body'] = html2bbcode($comment['body']);
	$arr['body'] = htmlspecialchars($arr['body'],ENT_COMPAT,'UTF-8',false);
	$arr['body'] = preg_replace_callback("/\[url\=\/+article\/(.*?)\](.*?)\[url\]/",'reflect_article_callback',$arr['body']);
	$arr['body'] = preg_replace_callback("/\[img(.*?)\](.*?)\[\/img\]/",'reflect_photo_callback',$arr['body']);

	// logger('comment: ' . print_r($arr,true));

	if($arr['id'])
		item_store_update($arr);
	else
		item_store($arr);

}
