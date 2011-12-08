<?php




function notify_setup($type,$params) {

	$a = get_app();
	$banner = t('Friendica Notification');
	$product = FRIENDICA_PLATFORM;
	$siteurl = z_path();
	$thanks = t('Thank You,');
	$sitename = get_config('config','sitename');
	$site_admin = sprintf( t('%s Administrator'), $sitename);

	$sender_name = t('Administrator');
	$sender_email = t('noreply') . '@' . $a->get_hostname(),

	if($type === NOTIFICATION_MAIL) {
		$new_email = sprintf( t('%s sent you a new private message at %s.'),$params['from'],$sitename);
		$email_visit = t('Please visit %s to view and/or reply to your private messages.');
		$email_tlink = sprintf( $email_visit, $siteurl . '/message' );
		$email_hlink = sprintf( $email_visit, '<a href="' . $siteurl . '/message">' . $sitename . '</a>');
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
		$messageSubject = email_header_encode(params['messageSubject'],'UTF-8');
		
		
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