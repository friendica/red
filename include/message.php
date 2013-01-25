<?php

/* Private Message backend API */


// send a private message
	

function send_message($uid = 0, $recipient='', $body='', $subject='', $replyto=''){ 

	$ret = array('success' => false);

	$a = get_app();

	if(! $recipient) {
		$ret['message'] = t('No recipient provided.');
		return $ret;
	}
	
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

	if(! $channel) {
		$ret['message'] = t('Unable to determine sender.');
		return $ret;
	}

	// generate a unique message_id

	do {
		$dups = false;
		$hash = random_string();

		$uri = $hash . '@' . get_app()->get_hostname();

		$r = q("SELECT id FROM mail WHERE uri = '%s' LIMIT 1",
			dbesc($uri));
		if(count($r))
			$dups = true;
	} while($dups == true);


	if(! strlen($replyto)) {
		$replyto = $uri;
	}

	
	$r = q("INSERT INTO mail ( account_id, channel_id, from_xchan, to_xchan, title, body, uri, parent_uri, created )
		VALUES ( %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s' )",
		intval($channel['channel_account_id']),
		intval($channel['channel_id']),
		dbesc($channel['channel_hash']),
		dbesc($recipient),
		dbesc($subject),
		dbesc($body),
		dbesc($uri),
		dbesc($replyto),
		dbesc(datetime_convert())
	);

	// verify the save

	$r = q("SELECT * FROM mail WHERE uri = '%s' and channel_id = %d LIMIT 1",
		dbesc($uri),
		intval($channel['channel_id'])
	);
	if(count($r))
		$post_id = $r[0]['id'];
	else {
		$ret['message'] = t('Stored post could not be verified.');
		return $ret;
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

	$match = null;

	if(preg_match_all("/\[img\](.*?)\[\/img\]/",$body,$match)) {
		$images = $match[1];
		if(count($images)) {
			foreach($images as $image) {
				if(! stristr($image,$a->get_baseurl() . '/photo/'))
					continue;
				$image_uri = substr($image,strrpos($image,'/') + 1);
				$image_uri = substr($image_uri,0, strpos($image_uri,'-'));
				$r = q("UPDATE photo SET allow_cid = '%s' WHERE resource_id = '%s' AND uid = %d and allow_cid = '%s'",
					dbesc('<' . $recipient . '>'),
					dbesc($image_uri),
					intval($channel['channel_id']),
					dbesc('<' . $channel['channel_hash'] . '>')
				); 
			}
		}
	}
	
	proc_run('php','include/notifier.php','mail',$post_id);

	$ret['success'] = true;
	$ret['message_item'] = intval($post_id);
	return;

}

function private_messages_list($uid, $mailbox = '', $start = 0, $numitems = 0) {

	$where = '';
	$limit = '';

	if($numitems)
		$limit = " LIMIT " . intval($start) . ", " . intval($numitems);
		
	if($mailbox !== '') {
		$x = q("select channel_hash from channel where channel_id = %d limit 1",
			intval($uid)
		);
		if(! $x)
			return array();
		if($mailbox === 'inbox')
			$where = " and sender_xchan != '" . dbesc($x[0]['channel_hash']) . "' ";
		elseif($mailbox === 'outbox')
			$where = " and sender_xchan  = '" . dbesc($x[0]['channel_hash']) . "' ";
	}

	// For different orderings, consider applying usort on the results. We thought of doing that
	// inside this function or having some preset sorts, but don't wish to limit app developers. 
		
	$r = q("SELECT * from mail WHERE channel_id = %d $where order by created desc $limit",
		intval(local_user())
	);
	if(! $r) {
		return array();
	}

	$chans = array();
	foreach($r as $rr) {
		$s = "'" . dbesc(trim($rr['from_xchan'])) . "'";
		if(! in_array($s,$chans))
			$chans[] = $s;
		$s = "'" . dbesc(trim($rr['to_xchan'])) . "'";
		if(! in_array($s,$chans))
			$chans[] = $s;
 	}

	$c = q("select * from xchan where xchan_hash in (" . implode(',',$chans) . ")");

	foreach($r as $k => $rr) {
		$r[$k]['from'] = find_xchan_in_array($rr['from_xchan'],$c);
		$r[$k]['to']   = find_xchan_in_array($rr['to_xchan'],$c);
		$r[$k]['seen'] = (($rr['mail_flags'] & MAIL_SEEN) ? 1 : 0);
	}

	return $r;
}



function private_messages_fetch_message($channel_id, $messageitem_id, $updateseen = false) {

	$messages = q("select * from mail where id = %d and channel_id = %d order by created asc",
		dbesc($messageitem_id),
		intval($channel_id)
	);

	if(! $messages)
		return array();

	$chans = array();
	foreach($messages as $rr) {
		$s = "'" . dbesc(trim($rr['from_xchan'])) . "'";
		if(! in_array($s,$chans))
			$chans[] = $s;
		$s = "'" . dbesc(trim($rr['to_xchan'])) . "'";
		if(! in_array($s,$chans))
			$chans[] = $s;
	}

	$c = q("select * from xchan where xchan_hash in (" . implode(',',$chans) . ")");

	foreach($messages as $k => $message) {
		$messages[$k]['from'] = find_xchan_in_array($message['from_xchan'],$c);
		$messages[$k]['to']   = find_xchan_in_array($message['to_xchan'],$c);
	}

	if($updateseen) {
		$r = q("UPDATE `mail` SET mail_flags = (mail_flags ^ %d) where not (mail_flags & %d) and id = %d AND channel_id = %d",
			intval(MAIL_SEEN),
			intval(MAIL_SEEN),
			dbesc($messageitem_id),
			intval($channel_id)
		);
	}

	return $messages;

}


function private_messages_drop($channel_id, $messageitem_id, $drop_conversation = false) {

	if($drop_conversation) {
		// find the parent_id
		$p = q("SELECT parent_uri FROM mail WHERE id = %d AND channel_id = %d LIMIT 1",
			intval($messageitem_id),
			intval($channel_id)
		);
		if($p) {
			$r = q("DELETE FROM mail WHERE parent_uri = '%s' AND channel_id = %d ",
				dbesc($p[0]['parent_uri']),
				intval($channel_id)
			);
			if($r)
				return true;
		}
	}
	else {
		$r = q("DELETE FROM mail WHERE id = %d AND channel_id = %d LIMIT 1",
			intval($messageitem_id),
			intval($channel_id)
		);
		if($r)
			return true;
	}
	return false;
}


function private_messages_fetch_conversation($channel_id, $messageitem_id, $updateseen = false) {

	// find the parent_uri of the message being requested

	$r = q("SELECT parent_uri from mail WHERE channel_id = %d and id = %d limit 1",
		intval($channel_id),
		intval($messageitem_id)
	);

	if(! $r) 
		return array();

	$messages = q("select * from mail where parent_uri = '%s' and channel_id = %d order by created asc",
		dbesc($r[0]['parent_uri']),
		intval($channel_id)
	);

	if(! $messages)
		return array();

	$chans = array();
	foreach($messages as $rr) {
		$s = "'" . dbesc(trim($rr['from_xchan'])) . "'";
		if(! in_array($s,$chans))
			$chans[] = $s;
		$s = "'" . dbesc(trim($rr['to_xchan'])) . "'";
		if(! in_array($s,$chans))
			$chans[] = $s;
	}


	$c = q("select * from xchan where xchan_hash in (" . implode(',',$chans) . ")");

	foreach($messages as $k => $message) {
		$messages[$k]['from'] = find_xchan_in_array($message['from_xchan'],$c);
		$messages[$k]['to']   = find_xchan_in_array($message['to_xchan'],$c);
	}


	if($updateseen) {
		$r = q("UPDATE `mail` SET mail_flags = (mail_flags ^ %d) where not (mail_flags & %d) and parent_uri = '%s' AND channel_id = %d",
			intval(MAIL_SEEN),
			intval(MAIL_SEEN),
			dbesc($r[0]['parent_uri']),
			intval($channel_id)
		);
	}
	
	return $messages;

}