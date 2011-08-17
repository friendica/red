<?php

require_once('include/acl_selectors.php');

function message_post(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$replyto   = ((x($_POST,'replyto'))   ? notags(trim($_POST['replyto']))   : '');
	$subject   = ((x($_POST,'subject'))   ? notags(trim($_POST['subject']))   : '');
	$body      = ((x($_POST,'body'))      ? escape_tags(trim($_POST['body'])) : '');
	$recipient = ((x($_POST,'messageto')) ? intval($_POST['messageto'])       : 0 );

	if(! $recipient) {
		notice( t('No recipient selected.') . EOL );
		return;
	}

	if(! strlen($subject))
		$subject = t('[no subject]');

	$me = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 1 LIMIT 1",
		intval(local_user())
	);
	$contact = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($recipient),
			intval(local_user())
	);

	if(! (count($me) && (count($contact)))) {
		notice( t('Unable to locate contact information.') . EOL );
		return;
	}

	$hash = random_string();
 	$uri = 'urn:X-dfrn:' . $a->get_baseurl() . ':' . local_user() . ':' . $hash ;

	if(! strlen($replyto))
		$replyto = $uri;

	$r = q("INSERT INTO `mail` ( `uid`, `from-name`, `from-photo`, `from-url`, 
		`contact-id`, `title`, `body`, `seen`, `replied`, `uri`, `parent-uri`, `created`)
		VALUES ( %d, '%s', '%s', '%s', %d, '%s', '%s', %d, %d, '%s', '%s', '%s' )",
		intval(local_user()),
		dbesc($me[0]['name']),
		dbesc($me[0]['thumb']),
		dbesc($me[0]['url']),
		intval($recipient),
		dbesc($subject),
		dbesc($body),
		1,
		0,
		dbesc($uri),
		dbesc($replyto),
		datetime_convert()
	);
	$r = q("SELECT * FROM `mail` WHERE `uri` = '%s' and `uid` = %d LIMIT 1",
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
					WHERE `resource-id` = '%s' AND `album` = '%s' AND `uid` = %d ",
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
		info( t('Message sent.') . EOL );
	}
	else {
		notice( t('Message could not be sent.') . EOL );
	}
	return;
}

function message_content(&$a) {

	$o = '';
	nav_set_selected('messages');

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$myprofile = $a->get_baseurl() . '/profile/' . $a->user['nickname'];


	$tpl = get_markup_template('mail_head.tpl');
	$header = replace_macros($tpl, array(
		'$messages' => t('Messages'),
		'$inbox' => t('Inbox'),
		'$outbox' => t('Outbox'),
		'$new' => t('New Message')
	));


	if(($a->argc == 3) && ($a->argv[1] === 'drop' || $a->argv[1] === 'dropconv')) {
		if(! intval($a->argv[2]))
			return;
		$cmd = $a->argv[1];
		if($cmd === 'drop') {
			$r = q("DELETE FROM `mail` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($a->argv[2]),
				intval(local_user())
			);
			if($r) {
				info( t('Message deleted.') . EOL );
			}
			goaway($a->get_baseurl() . '/message' );
		}
		else {
			$r = q("SELECT `parent-uri` FROM `mail` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($a->argv[2]),
				intval(local_user())
			);
			if(count($r)) {
				$parent = $r[0]['parent-uri'];
				$r = q("DELETE FROM `mail` WHERE `parent-uri` = '%s' AND `uid` = %d ",
					dbesc($parent),
					intval(local_user())
				);
				if($r)
					info( t('Conversation removed.') . EOL );
			} 
			goaway($a->get_baseurl() . '/message' );
		}	
	
	}

	if(($a->argc > 1) && ($a->argv[1] === 'new')) {
		
		$tpl = get_markup_template('msg-header.tpl');

		$a->page['htmlhead'] .= replace_macros($tpl, array(
			'$baseurl' => $a->get_baseurl(),
			'$nickname' => $a->user['nickname'],
			'$linkurl' => t('Please enter a link URL:')
		));
	
		$preselect = (isset($a->argv[2])?array($a->argv[2]):false);
	
		$select = contact_select('messageto','message-to-select', $preselect, 4, true);
		$tpl = get_markup_template('prv_message.tpl');
		$o .= replace_macros($tpl,array(
			'$header' => t('Send Private Message'),
			'$to' => t('To:'),
			'$subject' => t('Subject:'),
			'$subjtxt' => '',
			'$readonly' => '',
			'$yourmessage' => t('Your message:'),
			'$select' => $select,
			'$parent' => '',
			'$upload' => t('Upload photo'),
			'$insert' => t('Insert web link'),
			'$wait' => t('Please wait')

		));

		return $o;
	}

	if(($a->argc == 1) || ($a->argc == 2 && $a->argv[1] === 'sent')) {

		$o .= $header;
		
		if($a->argc == 2)
			$eq = '='; // I'm not going to bother escaping this.
		else
			$eq = '!='; // or this.

		$r = q("SELECT count(*) AS `total` FROM `mail` 
			WHERE `mail`.`uid` = %d AND `from-url` $eq '%s' GROUP BY `parent-uri` ORDER BY `created` DESC",
			intval(local_user()),
			dbesc($myprofile)
		);
		if(count($r))
			$a->set_pager_total($r[0]['total']);
	
		$r = q("SELECT max(`mail`.`created`) AS `mailcreated`, min(`mail`.`seen`) AS `mailseen`, 
			`mail`.* , `contact`.`name`, `contact`.`url`, `contact`.`thumb` 
			FROM `mail` LEFT JOIN `contact` ON `mail`.`contact-id` = `contact`.`id` 
			WHERE `mail`.`uid` = %d AND `from-url` $eq '%s' GROUP BY `parent-uri` ORDER BY `created` DESC  LIMIT %d , %d ",
			intval(local_user()),
			dbesc($myprofile),
			intval($a->pager['start']),
			intval($a->pager['itemspage'])
		);
		if(! count($r)) {
			info( t('No messages.') . EOL);
			return $o;
		}

		$tpl = get_markup_template('mail_list.tpl');
		foreach($r as $rr) {
			$o .= replace_macros($tpl, array(
				'$id' => $rr['id'],
				'$from_name' =>$rr['from-name'],
				'$from_url' => $a->get_baseurl() . '/redir/' . $rr['contact-id'],
				'$sparkle' => ' sparkle',
				'$from_photo' => $rr['thumb'],
				'$subject' => (($rr['mailseen']) ? $rr['title'] : '<strong>' . $rr['title'] . '</strong>'),
				'$delete' => t('Delete conversation'),
				'$body' => $rr['body'],
				'$to_name' => $rr['name'],
				'$date' => datetime_convert('UTC',date_default_timezone_get(),$rr['mailcreated'], t('D, d M Y - g:i A'))
			));
		}
		$o .= paginate($a);	
		return $o;
	}

	if(($a->argc > 1) && (intval($a->argv[1]))) {

		$o .= $header;

		$r = q("SELECT `mail`.*, `contact`.`name`, `contact`.`url`, `contact`.`thumb` 
			FROM `mail` LEFT JOIN `contact` ON `mail`.`contact-id` = `contact`.`id` 
			WHERE `mail`.`uid` = %d AND `mail`.`id` = %d LIMIT 1",
			intval(local_user()),
			intval($a->argv[1])
		);
		if(count($r)) { 
			$contact_id = $r[0]['contact-id'];
			$messages = q("SELECT `mail`.*, `contact`.`name`, `contact`.`url`, `contact`.`thumb` 
				FROM `mail` LEFT JOIN `contact` ON `mail`.`contact-id` = `contact`.`id` 
				WHERE `mail`.`uid` = %d AND `mail`.`parent-uri` = '%s' ORDER BY `mail`.`created` ASC",
				intval(local_user()),
				dbesc($r[0]['parent-uri'])
			);
		}
		if(! count($messages)) {
			notice( t('Message not available.') . EOL );
			return $o;
		}

		$r = q("UPDATE `mail` SET `seen` = 1 WHERE `parent-uri` = '%s' AND `uid` = %d",
			dbesc($r[0]['parent-uri']),
			intval(local_user())
		);

		require_once("include/bbcode.php");

		$tpl = get_markup_template('msg-header.tpl');
	
		$a->page['htmlhead'] .= replace_macros($tpl, array(
			'$nickname' => $a->user['nickname'],
			'$baseurl' => $a->get_baseurl()
		));


		$tpl = get_markup_template('mail_conv.tpl');
		foreach($messages as $message) {
			if($message['from-url'] == $myprofile) {
				$from_url = $myprofile;
				$sparkle = '';
			}
			else {
				$from_url = $a->get_baseurl() . '/redir/' . $message['contact-id'];
				$sparkle = ' sparkle';
			}
			$o .= replace_macros($tpl, array(
				'$id' => $message['id'],
				'$from_name' =>$message['from-name'],
				'$from_url' => $from_url,
				'$sparkle' => $sparkle,
				'$from_photo' => $message['from-photo'],
				'$subject' => $message['title'],
				'$body' => smilies(bbcode($message['body'])),
				'$delete' => t('Delete message'),
				'$to_name' => $message['name'],
				'$date' => datetime_convert('UTC',date_default_timezone_get(),$message['created'],'D, d M Y - g:i A')
			));
				
		}
		$select = $message['name'] . '<input type="hidden" name="messageto" value="' . $contact_id . '" />';
		$parent = '<input type="hidden" name="replyto" value="' . $message['parent-uri'] . '" />';
		$tpl = get_markup_template('prv_message.tpl');
		$o .= replace_macros($tpl,array(
			'$header' => t('Send Reply'),
			'$to' => t('To:'),
			'$subject' => t('Subject:'),
			'$subjtxt' => $message['title'],
			'$readonly' => ' readonly="readonly" style="background: #BBBBBB;" ',
			'$yourmessage' => t('Your message:'),
			'$select' => $select,
			'$parent' => $parent,
			'$upload' => t('Upload photo'),
			'$insert' => t('Insert web link'),
			'$wait' => t('Please wait')

		));

		return $o;
	}

}
