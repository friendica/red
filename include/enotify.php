<?php


		// send email notification if requested.
/*
		$notif_params = array(
			'type' => NOTIFY_MAIL,
			'notify_flags' => $importer['notify-flags'],
			'language' => $importer['language'],
			'to_name' => $importer['username'],
			'to_email' => $importer['email'],
			'item' => $msg,
			'source_name' => $msg['from-name'],
			'source_link' => $importer['url'],
			'source_photo' => $importer['thumb'],
		);
*/			
		//notification($notif_params);


function notification($params) {

	logger('notification: entry');

	$a = get_app();
	$banner = t('Friendica Notification');
	$product = FRIENDICA_PLATFORM;
	$siteurl = z_path();
	$thanks = t('Thank You,');
	$sitename = get_config('config','sitename');
	$site_admin = sprintf( t('%s Administrator'), $sitename);

	$sender_name = t('Administrator');
	$sender_email = t('noreply') . '@' . $a->get_hostname();

	$title = $params['item']['title'];
	$body = $params['item']['body'];

	if($params['type'] == NOTIFY_MAIL) {

		logger('notification: email');

		$subject = 	sprintf( t('New mail received at %s'),$sitename);

		$preamble = sprintf( t('%s sent you a new private message at %s.'),$params['source_name'],$sitename);

		$sitelink = t('Please visit %s to view and/or reply to your private messages.');
		$tsitelink = sprintf( $sitelink, $siteurl . '/message' );
		$hsitelink = sprintf( $sitelink, '<a href="' . $siteurl . '/message">' . $sitename . '</a>');
	}


	// send email notification if notification preferences permit

	require_once('bbcode.php');
	if(intval($params['notify_flags']) & intval($params['type'])) {

		logger('notification: sending email');

		push_lang($params['language']);

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
			'$source_name'  => $parama['source_name'],
			'$source_link'  => $params['source_link'],
			'$source_photo' => $params['source_photo'],
			'$username'     => $params['to_name'],
			'$hsitelink'    => $hsitelink,
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
			'$source_name'  => $parama['source_name'],
			'$source_link'  => $params['source_link'],
			'$source_photo' => $params['source_photo'],
			'$username'     => $params['to_name'],
			'$tsitelink'    => $tsitelink,
			'$thanks'       => $thanks,
			'$site_admin'   => $site_admin,
			'$title'		=> stripslashes($title),
			'$textversion'	=> $textversion,	
		));

		logger('text: ' . $email_text_body);

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
		pop_lang();
	}
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

		logger('enotify: send: ' . print_r($params,true));
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
			"Reply-To: {$params['replyTo']}\n" .
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
		logger("sendTextHtmlEmail: returns " . $res, LOGGER_DEBUG);
	}
}
?>