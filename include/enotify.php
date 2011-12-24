<?php


		// send email notification if requested.
/*
		$notif_params = array(
			'type' => NOTIFY_MAIL,
			'notify_flags' => $importer['notify_flags'],
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

		$subject = 	sprintf( t('New mail received at %s'),$sitename);

		$new_email = sprintf( t('%s sent you a new private message at %s.'),$params['source_name'],$sitename);
		$email_visit = t('Please visit %s to view and/or reply to your private messages.');
		$email_tlink = sprintf( $email_visit, $siteurl . '/message' );
		$email_hlink = sprintf( $email_visit, '<a href="' . $siteurl . '/message">' . $sitename . '</a>');

	}


	// send email notification if notification preferences permit

	require_once('bbcode.php');
	if(intval($params['notify-flags']) & intval($params['type'])) {

		push_lang($params['language']);

		$msg['notificationfromname']	= $sender_name;
		$msg['notificationfromemail']	= $sender_email;

		$msg['textversion']
				= strip_tags(html_entity_decode(bbcode(stripslashes(str_replace(array("\\r\\n", "\\r", "\\n"), "\n",
					$body))),ENT_QUOTES,'UTF-8'));
		$msg['htmlversion']	
				= html_entity_decode(bbcode(stripslashes(str_replace(array("\\r\\n", "\\r","\\n\\n" ,"\\n"), 
						"<br />\n",$body))));

		// load the template for private message notifications
		$tpl = get_intltext_template('mail_received_html_body_eml.tpl');
		$email_html_body_tpl = replace_macros($tpl,array(
			'$username'     => $importer['username'],
			'$siteName'		=> $a->config['sitename'],			// name of this site
			'$siteurl'		=> $a->get_baseurl(),				// descriptive url of this site
			'$thumb'		=> $importer['thumb'],				// thumbnail url for sender icon
			'$email'		=> $importer['email'],				// email address to send to
			'$url'			=> $importer['url'],				// full url for the site
			'$from'			=> $msg['from-name'],				// name of the person sending the message
			'$title'		=> stripslashes($msg['title']),			// subject of the message
			'$htmlversion'	=> $msg['htmlversion'],					// html version of the message
			'$mimeboundary'	=> $msg['mimeboundary'],				// mime message divider
			'$hostname'		=> $a->get_hostname()				// name of this host
		));
		
		// load the template for private message notifications
		$tpl = get_intltext_template('mail_received_text_body_eml.tpl');
		$email_text_body_tpl = replace_macros($tpl,array(
			'$username'     => $importer['username'],
			'$siteName'		=> $a->config['sitename'],			// name of this site
			'$siteurl'		=> $a->get_baseurl(),				// descriptive url of this site
			'$thumb'		=> $importer['thumb'],				// thumbnail url for sender icon
			'$email'		=> $importer['email'],				// email address to send to
			'$url'			=> $importer['url'],				// full url for the site
			'$from'			=> $msg['from-name'],				// name of the person sending the message
			'$title'		=> stripslashes($msg['title']),			// subject of the message
			'$textversion'	=> $msg['textversion'],					// text version of the message
			'$mimeboundary'	=> $msg['mimeboundary'],				// mime message divider
			'$hostname'		=> $a->get_hostname()				// name of this host
		));

		// use the EmailNotification library to send the message
		require_once("include/EmailNotification.php");
		EmailNotification::sendTextHtmlEmail(
			$msg['notificationfromname'],
			$msg['notificationfromemail'],
			$msg['notificationfromemail'],
			$importer['email'],
			$subject,
			$email_html_body_tpl,
			$email_text_body_tpl
		);
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
			$toEmail,	 									// send to address
			$messageSubject,								// subject
			$multipartMessageBody,	 						// message body
			$messageHeader									// message headers
		);
		logger("sendTextHtmlEmail: returns " . $res, LOGGER_DEBUG);
	}
}
?>