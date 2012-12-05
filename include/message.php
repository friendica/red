<?php


// send a private message
	

function send_message($uid = 0, $recipient='', $body='', $subject='', $replyto=''){ 

	$a = get_app();

	if(! $recipient) return -1;
	
	if(! strlen($subject))
		$subject = t('[no subject]');


	if($uid) {
		$r = q("select * from channel where channel_id = %d limit 1",
			intval($uid)
		);
		if($r)
			$channel = $r[0];
	}
	else {
		$channel = get_app()->get_channel();
	}


	do {
		$dups = false;
		$hash = random_string();

		$uri = $hash . '@' . get_app()->get_hostname();

		$r = q("SELECT `id` FROM mail WHERE `uri` = '%s' LIMIT 1",
			dbesc($uri));
		if(count($r))
			$dups = true;
	} while($dups == true);


	if(! strlen($replyto)) {
		$replyto = $uri;
	}

	

	$r = q("INSERT INTO `mail` (  account_id, channel_id, from_xchan, to_xchan, title, body, uri, parent_uri, created )
		VALUES ( %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s' )",
		intval($channel['channel_account_id']),
		intval(local_user()),
		dbesc($channel['channel_hash']),
		dbesc($recipient),
		dbesc($subject),
		dbesc($body),
		dbesc($uri),
		dbesc($replyto),
		dbesc(datetime_convert())
	);


	$r = q("SELECT * FROM `mail` WHERE uri = '%s' and channel_id = %d LIMIT 1",
		dbesc($uri),
		intval(local_user())
	);
	if(count($r))
		$post_id = $r[0]['id'];


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

	$match = null;

	if(preg_match_all("/\[img\](.*?)\[\/img\]/",$body,$match)) {
		$images = $match[1];
		if(count($images)) {
			foreach($images as $image) {
				if(! stristr($image,$a->get_baseurl() . '/photo/'))
					continue;
				$image_uri = substr($image,strrpos($image,'/') + 1);
				$image_uri = substr($image_uri,0, strpos($image_uri,'-'));
				$r = q("UPDATE `photo` SET `allow_cid` = '%s'
					WHERE `resource_id` = '%s' AND `album` = '%s' AND `uid` = %d ",
					dbesc('<' . $recipient . '>'),
					dbesc($image_uri),
					dbesc( t('Wall Photos')),
					intval(local_user())
				); 
			}
		}
	}
	
	if($post_id) {
		proc_run('php',"include/notifier.php","mail","$post_id");
		return intval($post_id);
	} else {
		return -3;
	}

}


