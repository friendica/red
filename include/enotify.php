<?php

function notification($params) {

	logger('notification: entry', LOGGER_DEBUG);

	$a = get_app();
	$banner = t('Friendica Notification');
	$product = FRIENDICA_PLATFORM;
	$siteurl = z_path();
	$thanks = t('Thank You,');
	$sitename = get_config('config','sitename');
	$site_admin = sprintf( t('%s Administrator'), $sitename);

	$sender_name = $product;
	$sender_email = t('noreply') . '@' . $a->get_hostname();

	if(array_key_exists('item',$params)) {
		$title = $params['item']['title'];
		$body = $params['item']['body'];
	}
	else {
		$title = $body = '';
	}

	if($params['type'] == NOTIFY_MAIL) {

		$subject = 	sprintf( t('New mail received at %s'),$sitename);

		$preamble = sprintf( t('%s sent you a new private message at %s.'),$params['source_name'],$sitename);
		$epreamble = sprintf( t('%s sent you a private message.'),'[url=' . $params['source_link'] . ']' . $params['source_name'] . '[/url]');
		$sitelink = t('Please visit %s to view and/or reply to your private messages.');
		$tsitelink = sprintf( $sitelink, $siteurl . '/message' );
		$hsitelink = sprintf( $sitelink, '<a href="' . $siteurl . '/message">' . $sitename . '</a>');
		$itemlink = $siteurl . '/message';
	}

	if($params['type'] == NOTIFY_COMMENT) {

		$subject = sprintf( t('%s commented on an item at %s'), $params['source_name'], $sitename);
		$preamble = sprintf( t('%s commented on an item/conversation you have been following.'), $params['source_name']); 
		$epreamble = sprintf( t('%s commented on an item/conversation you have been following.'), '[url=' . $params['source_link'] . ']' . $params['source_name'] . '[/url]'); 

		$sitelink = t('Please visit %s to view and/or reply to the conversation.');
		$tsitelink = sprintf( $sitelink, $siteurl );
		$hsitelink = sprintf( $sitelink, '<a href="' . $siteurl . '">' . $sitename . '</a>');
		$itemlink =  $params['link'];
	}

	if($params['type'] == NOTIFY_WALL) {
		$preamble = $subject =	sprintf( t('%s posted to your profile wall at %s') , $params['source_name'], $sitename);
		$epreamble = sprintf( t('%s posted to your profile wall') , '[url=' . $params['source_link'] . ']' . $params['source_name'] . '[/url]'); 
		
		$sitelink = t('Please visit %s to view and/or reply to the conversation.');
		$tsitelink = sprintf( $sitelink, $siteurl );
		$hsitelink = sprintf( $sitelink, '<a href="' . $siteurl . '">' . $sitename . '</a>');
		$itemlink =  $params['link'];
	}

	if($params['type'] == NOTIFY_TAGSELF) {
		$preamble = $subject =	sprintf( t('%s tagged you at %s') , $params['source_name'], $sitename);
		$epreamble = sprintf( t('%s tagged you') , '[url=' . $params['source_link'] . ']' . $params['source_name'] . '[/url]'); 

		$sitelink = t('Please visit %s to view and/or reply to the conversation.');
		$tsitelink = sprintf( $sitelink, $siteurl );
		$hsitelink = sprintf( $sitelink, '<a href="' . $siteurl . '">' . $sitename . '</a>');
		$itemlink =  $params['link'];
	}

	if($params['type'] == NOTIFY_TAGSHARE) {
		$preamble = $subject =	sprintf( t('%s tagged your post at %s') , $params['source_name'], $sitename);
		$epreamble = sprintf( t('%s tagged your post') , '[url=' . $params['source_link'] . ']' . $params['source_name'] . '[/url]'); 

		$sitelink = t('Please visit %s to view and/or reply to the conversation.');
		$tsitelink = sprintf( $sitelink, $siteurl );
		$hsitelink = sprintf( $sitelink, '<a href="' . $siteurl . '">' . $sitename . '</a>');
		$itemlink =  $params['link'];
	}

	if($params['type'] == NOTIFY_INTRO) {
		$subject = sprintf( t('Introduction received at %s'), $sitename);
		$preamble = sprintf( t('You\'ve received an introduction from \'%s\' at %s'), $params['source_name'], $sitename); 
		$epreamble = sprintf( t('You\'ve received an introduction from %s'), '[url=' . $params['source_link'] . ']' . $params['source_name'] . '[/url]'); 
		$body = sprintf( t('You may visit their profile at %s'),$params['source_link']);

		$sitelink = t('Please visit %s to approve or reject the introduction.');
		$tsitelink = sprintf( $sitelink, $siteurl );
		$hsitelink = sprintf( $sitelink, '<a href="' . $siteurl . '">' . $sitename . '</a>');
		$itemlink =  $params['link'];
	}

	if($params['type'] == NOTIFY_SUGGEST) {
		$subject = sprintf( t('Friend suggestion received at %s'), $sitename);
		$preamble = sprintf( t('You\'ve received a friend suggestion from \'%s\' at %s'), $params['source_name'], $sitename); 
		$epreamble = sprintf( t('You\'ve received a friend suggestion for %s from %s'),
			'[url=' . $params['item']['url'] . ']' . $params['item']['name'] . '[/url]', 
			'[url=' . $params['source_link'] . ']' . $params['source_name'] . '[/url]'); 
		$body = t('Name:') . ' ' . $params['item']['name'] . "\n";
		$body .= t('Photo:') . ' ' . $params['item']['photo'] . "\n";
		$body .= sprintf( t('You may visit their profile at %s'),$params['item']['url']);

		$sitelink = t('Please visit %s to approve or reject the suggestion.');
		$tsitelink = sprintf( $sitelink, $siteurl );
		$hsitelink = sprintf( $sitelink, '<a href="' . $siteurl . '">' . $sitename . '</a>');
		$itemlink =  $params['link'];
	}

	if($params['type'] == NOTIFY_CONFIRM) {

	}

	// from here on everything is in the recipients language

	push_lang($params['language']);

	require_once('include/html2bbcode.php');	

	// create notification entry in DB

	$r = q("insert into notify (name,url,photo,date,msg,uid,link,type,verb,otype)
		values('%s','%s','%s','%s','%s',%d,'%s',%d,'%s','%s')",
		dbesc($params['source_name']),
		dbesc($params['source_link']),
		dbesc($params['source_photo']),
		dbesc(datetime_convert()),
		dbesc($epreamble),
		intval($params['uid']),
		dbesc($itemlink),
		intval($params['type']),
		dbesc($params['verb']),
		dbesc($params['otype'])
	);

	// send email notification if notification preferences permit

	require_once('bbcode.php');
	if(intval($params['notify_flags']) & intval($params['type'])) {

		logger('notification: sending notification email');


		$textversion = strip_tags(html_entity_decode(bbcode(stripslashes(str_replace(array("\\r\\n", "\\r", "\\n"), "\n",
			$body))),ENT_QUOTES,'UTF-8'));
		$htmlversion = html_entity_decode(bbcode(stripslashes(str_replace(array("\\r\\n", "\\r","\\n\\n" ,"\\n"), 
			"<br />\n",$body))));

		// load the template for private message notifications
		$tpl = get_markup_template('email_notify_html.tpl');
		$email_html_body = replace_macros($tpl,array(
			'$banner'       => $banner,
			'$product'      => $product,
			'$preamble'     => $preamble,
			'$sitename'     => $sitename,
			'$siteurl'      => $siteurl,
			'$source_name'  => $params['source_name'],
			'$source_link'  => $params['source_link'],
			'$source_photo' => $params['source_photo'],
			'$username'     => $params['to_name'],
			'$hsitelink'    => $hsitelink,
			'$itemlink'     => '<a href="' . $itemlink . '">' . $itemlink . '</a>',
			'$thanks'       => $thanks,
			'$site_admin'   => $site_admin,
			'$title'		=> stripslashes($title),
			'$htmlversion'	=> $htmlversion,	
		));
		
		// load the template for private message notifications
		$tpl = get_markup_template('email_notify_text.tpl');
		$email_text_body = replace_macros($tpl,array(
			'$banner'       => $banner,
			'$product'      => $product,
			'$preamble'     => $preamble,
			'$sitename'     => $sitename,
			'$siteurl'      => $siteurl,
			'$source_name'  => $params['source_name'],
			'$source_link'  => $params['source_link'],
			'$source_photo' => $params['source_photo'],
			'$username'     => $params['to_name'],
			'$tsitelink'    => $tsitelink,
			'$itemlink'     => $itemlink,
			'$thanks'       => $thanks,
			'$site_admin'   => $site_admin,
			'$title'		=> stripslashes($title),
			'$textversion'	=> $textversion,	
		));

//		logger('text: ' . $email_text_body);

		// use the EmailNotification library to send the message

		enotify::send(array(
			'fromName' => $sender_name,
			'fromEmail' => $sender_email,
			'replyTo' => $sender_email,
			'toEmail' => $params['to_email'],
			'messageSubject' => $subject,
			'htmlVersion' => $email_html_body,
			'textVersion' => $email_text_body
		));
	}

	pop_lang();

}

require_once('include/email.php');

class enotify {
	/**
	 * Send a multipart/alternative message with Text and HTML versions
	 *
	 * @param fromName			name of the sender
	 * @param fromEmail			email fo the sender
	 * @param replyTo			replyTo address to direct responses
	 * @param toEmail			destination email address
	 * @param messageSubject	subject of the message
	 * @param htmlVersion		html version of the message
	 * @param textVersion		text only version of the message
	 */
	static public function send($params) {

		$fromName = email_header_encode($params['fromName'],'UTF-8'); 
		$messageSubject = email_header_encode($params['messageSubject'],'UTF-8');
		
		// generate a mime boundary
		$mimeBoundary   =rand(0,9)."-"
				.rand(10000000000,9999999999)."-"
				.rand(10000000000,9999999999)."=:"
				.rand(10000,99999);

		// generate a multipart/alternative message header
		$messageHeader =
			"From: {$params['fromName']} <{$params['fromEmail']}>\n" . 
			"Reply-To: {$params['fromName']} <{$params['replyTo']}>\n" .
			"MIME-Version: 1.0\n" .
			"Content-Type: multipart/alternative; boundary=\"{$mimeBoundary}\"";

		// assemble the final multipart message body with the text and html types included
		$textBody	=	chunk_split(base64_encode($params['textVersion']));
		$htmlBody	=	chunk_split(base64_encode($params['htmlVersion']));
		$multipartMessageBody =
			"--" . $mimeBoundary . "\n" .					// plain text section
			"Content-Type: text/plain; charset=UTF-8\n" .
			"Content-Transfer-Encoding: base64\n\n" .
			$textBody . "\n" .
			"--" . $mimeBoundary . "\n" .					// text/html section
			"Content-Type: text/html; charset=UTF-8\n" .
			"Content-Transfer-Encoding: base64\n\n" .
			$htmlBody . "\n" .
			"--" . $mimeBoundary . "--\n";					// message ending

		// send the message
		$res = mail(
			$params['toEmail'],	 									// send to address
			$params['messageSubject'],								// subject
			$multipartMessageBody,	 						// message body
			$messageHeader									// message headers
		);
		logger("notification: enotify::send returns " . $res, LOGGER_DEBUG);
	}
}
?>