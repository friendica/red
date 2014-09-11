<?php

require_once('include/acl_selectors.php');
require_once('include/message.php');
require_once('include/zot.php');
require_once("include/bbcode.php");
require_once('include/Contact.php');


function mail_post(&$a) {

	if(! local_user())
		return;

	$replyto   = ((x($_REQUEST,'replyto'))      ? notags(trim($_REQUEST['replyto']))      : '');
	$subject   = ((x($_REQUEST,'subject'))      ? notags(trim($_REQUEST['subject']))      : '');
	$body      = ((x($_REQUEST,'body'))         ? escape_tags(trim($_REQUEST['body']))    : '');
	$recipient = ((x($_REQUEST,'messageto'))    ? notags(trim($_REQUEST['messageto']))    : '');
	$rstr      = ((x($_REQUEST,'messagerecip')) ? notags(trim($_REQUEST['messagerecip'])) : '');
	$expires   = ((x($_REQUEST,'expires')) ? datetime_convert(date_default_timezone_get(),'UTC', $_REQUEST['expires']) : NULL_DATE);

	// If we have a raw string for a recipient which hasn't been auto-filled,
	// it means they probably aren't in our address book, hence we don't know
	// if we have permission to send them private messages.
	// finger them and find out before we try and send it.

	if(! $recipient) {
		$channel = $a->get_channel();

		$ret = zot_finger($rstr,$channel);

		if(! $ret['success']) {
			notice( t('Unable to lookup recipient.') . EOL);
			return;
		} 
		$j = json_decode($ret['body'],true);

		logger('message_post: lookup: ' . $url . ' ' . print_r($j,true));

		if(! ($j['success'] && $j['guid'])) {
			notice( t('Unable to communicate with requested channel.'));
			return;
		}

		$x = import_xchan($j);

		if(! $x['success']) {
			notice( t('Cannot verify requested channel.'));
			return;
		}

		$recipient = $x['hash'];

		$their_perms = 0;

		$global_perms = get_perms();

		if($j['permissions']['data']) {
			$permissions = crypto_unencapsulate($j['permissions'],$channel['channel_prvkey']);
			if($permissions)
				$permissions = json_decode($permissions);
			logger('decrypted permissions: ' . print_r($permissions,true), LOGGER_DATA);
		}
		else
			$permissions = $j['permissions'];

		foreach($permissions as $k => $v) {
			if($v) {
				$their_perms = $their_perms | intval($global_perms[$k][1]);
			}
		}

		if(! ($their_perms & PERMS_W_MAIL)) {
 			notice( t('Selected channel has private message restrictions. Send failed.'));
			return;
		}
	}

//	if(feature_enabled(local_user(),'richtext')) {
//		$body = fix_mce_lf($body);
//	}

	if(! $recipient) {
		notice('No recipient found.');
		$a->argc = 2;
		$a->argv[1] = 'new';
		return;
	}

	// We have a local_user, let send_message use the session channel and save a lookup
	
	$ret = send_message(0, $recipient, $body, $subject, $replyto, $expires);

	if(! $ret['success']) {
		notice($ret['message']);
	}

	goaway(z_root() . '/message');
		
}

function mail_content(&$a) {

	$o = '';
	nav_set_selected('messages');

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return login();
	}

	$channel = $a->get_channel();
	head_set_icon($channel['xchan_photo_s']);

	$cipher = get_pconfig(local_user(),'system','default_cipher');
	if(! $cipher)
		$cipher = 'aes256';

	$tpl = get_markup_template('mail_head.tpl');
	$header = replace_macros($tpl, array(
		'$messages' => t('Messages'),
		'$tab_content' => $tab_content
	));

	if((argc() == 3) && (argv(1) === 'drop')) {
		if(! intval(argv(2)))
			return;
		$cmd = argv(1);

		$r = private_messages_drop(local_user(), argv(2));
		if($r) {
			info( t('Message deleted.') . EOL );
		}
		goaway($a->get_baseurl(true) . '/message' );
	}

	if((argc() == 3) && (argv(1) === 'recall')) {
		if(! intval(argv(2)))
			return;
		$cmd = argv(1);
		$r = q("update mail set mail_flags = mail_flags | %d where id = %d and channel_id = %d limit 1",
			intval(MAIL_RECALLED),
			intval(argv(2)),
			intval(local_user())
		);
		proc_run('php','include/notifier.php','mail',intval(argv(2)));

		if($r) {
				info( t('Message recalled.') . EOL );
		}
		goaway($a->get_baseurl(true) . '/message' );

	}

	if((argc() > 1) && (argv(1) === 'new')) {
		
		$o .= $header;
		
		$plaintext = true;

		$tpl = get_markup_template('msg-header.tpl');

		$a->page['htmlhead'] .= replace_macros($tpl, array(
			'$baseurl' => $a->get_baseurl(true),
			'$editselect' => (($plaintext) ? 'none' : '/(profile-jot-text|prvmail-text)/'),
			'$nickname' => $channel['channel_address'],
			'$linkurl' => t('Please enter a link URL:'),
			'$expireswhen' => t('Expires YYYY-MM-DD HH:MM')
		));
	
		$preselect = (isset($a->argv[2])?array($a->argv[2]):false);
		$prename = $preurl = $preid = '';
			
		if(x($_REQUEST,'hash')) {
			$r = q("select abook.*, xchan.* from abook left join xchan on abook_xchan = xchan_hash
				where abook_channel = %d and abook_xchan = '%s' limit 1",
				intval(local_user()),
				dbesc($_REQUEST['hash'])
			);
			if($r) {
				$prename = $r[0]['xchan_name'];
				$preurl = $r[0]['xchan_url'];
				$preid = $r[0]['abook_id'];
				$preselect = array($preid);
			}
		}


		if($preselect) {
			$r = q("select abook.*, xchan.* from abook left join xchan on abook_xchan = xchan_hash
				where abook_channel = %d and abook_id = %d limit 1",
				intval(local_user()),
				intval(argv(2))
			);
			if($r) {
				$prename = $r[0]['xchan_name'];
				$preurl = $r[0]['xchan_url'];
				$preid = $r[0]['abook_id'];
			}
		}	 

		$prefill = (($preselect) ? $prename  : '');

		if(! $prefill) {
			if(array_key_exists('to',$_REQUEST))
				$prefill = $_REQUEST['to'];
		}

		// the ugly select box
		
		$select = contact_select('messageto','message-to-select', $preselect, 4, true, false, false, 10);

		$tpl = get_markup_template('prv_message.tpl');
		$o .= replace_macros($tpl,array(
			'$header' => t('Send Private Message'),
			'$to' => t('To:'),
			'$showinputs' => 'true', 
			'$prefill' => $prefill,
			'$autocomp' => $autocomp,
			'$preid' => $preid,
			'$subject' => t('Subject:'),
			'$subjtxt' => ((x($_REQUEST,'subject')) ? strip_tags($_REQUEST['subject']) : ''),
			'$text' => ((x($_REQUEST,'body')) ? htmlspecialchars($_REQUEST['body'], ENT_COMPAT, 'UTF-8') : ''),
			'$readonly' => '',
			'$yourmessage' => t('Your message:'),
			'$select' => $select,
			'$parent' => '',
			'$upload' => t('Upload photo'),
			'$attach' => t('Attach file'),
			'$insert' => t('Insert web link'),
			'$wait' => t('Please wait'),
			'$submit' => t('Send'),
			'$defexpire' => '',
			'$feature_expire' => ((feature_enabled(local_user(),'content_expire')) ? true : false),
			'$expires' => t('Set expiration date'),
			'$feature_encrypt' => ((feature_enabled(local_user(),'content_encrypt')) ? true : false),
			'$encrypt' => t('Encrypt text'),
			'$cipher' => $cipher,


		));

		return $o;
	}


	if((argc() > 1) && (intval(argv(1)))) {

		$o .= $header;

		$plaintext = true;

//		if( local_user() && feature_enabled(local_user(),'richtext') )
//			$plaintext = false;

		$messages = private_messages_fetch_conversation(local_user(), argv(1), true);

		if(! $messages) {
			info( t('Message not found.') . EOL);
			return $o;
		}

		if($messages[0]['to_xchan'] === $channel['channel_hash'])
			$a->poi = $messages[0]['from'];
		else
			$a->poi = $messages[0]['to'];

//		require_once('include/Contact.php');

//		$a->set_widget('mail_conversant',vcard_from_xchan($a->poi,$get_observer_hash,'mail'));


		$tpl = get_markup_template('msg-header.tpl');
	
		$a->page['htmlhead'] .= replace_macros($tpl, array(
			'$nickname' => $channel['channel_addr'],
			'$baseurl' => $a->get_baseurl(true),
			'$editselect' => (($plaintext) ? 'none' : '/(profile-jot-text|prvmail-text)/'),
			'$linkurl' => t('Please enter a link URL:'),
			'$expireswhen' => t('Expires YYYY-MM-DD HH:MM')
		));


		$mails = array();
		$seen = 0;
		$unknown = false;

		foreach($messages as $message) {

			$s = theme_attachments($message);

			$mails[] = array(
				'id' => $message['id'],
				'from_name' => $message['from']['xchan_name'],
				'from_url' =>  chanlink_hash($message['from_xchan']),
				'from_photo' => $message['from']['xchan_photo_m'],
				'to_name' => $message['to']['xchan_name'],
				'to_url' =>  chanlink_hash($message['to_xchan']),
				'to_photo' => $message['to']['xchan_photo_m'],
				'subject' => $message['title'],
				'body' => smilies(bbcode($message['body']) . $s),
				'delete' => t('Delete message'),
				'recall' => t('Recall message'),
				'can_recall' => (($channel['channel_hash'] == $message['from_xchan']) ? true : false),
				'is_recalled' => (($message['mail_flags'] & MAIL_RECALLED) ? t('Message has been recalled.') : ''),
				'date' => datetime_convert('UTC',date_default_timezone_get(),$message['created'],'D, d M Y - g:i A'),
			);
				
			$seen = $message['seen'];

		}

		$recp = (($message['from_xchan'] === $channel['channel_hash']) ? 'to' : 'from');

// FIXME - move this HTML to template

		$select = $message[$recp]['xchan_name'] . '<input type="hidden" name="messageto" value="' . $message[$recp]['xchan_hash'] . '" />';
		$parent = '<input type="hidden" name="replyto" value="' . $message['parent_mid'] . '" />';

		$tpl = get_markup_template('mail_display.tpl');
		$o = replace_macros($tpl, array(
			'$prvmsg_header' => t('Private Conversation'),
			'$thread_id' => $a->argv[1],
			'$thread_subject' => $message['title'],
			'$thread_seen' => $seen,
			'$delete' =>  t('Delete conversation'),
			'$canreply' => (($unknown) ? false : '1'),
			'$unknown_text' => t("No secure communications available. You <strong>may</strong> be able to respond from the sender's profile page."),			
			'$mails' => $mails,
			
			// reply
			'$header' => t('Send Reply'),
			'$to' => t('To:'),
			'$showinputs' => '',
			'$subject' => t('Subject:'),
			'$subjtxt' => $message['title'],
			'$readonly' => ' readonly="readonly" style="background: #BBBBBB;" ',
			'$yourmessage' => t('Your message:'),
			'$text' => '',
			'$select' => $select,
			'$parent' => $parent,
			'$upload' => t('Upload photo'),
			'$attach' => t('Attach file'),
			'$insert' => t('Insert web link'),
			'$submit' => t('Submit'),
			'$wait' => t('Please wait'),
			'$defexpire' => '',
			'$feature_expire' => ((feature_enabled(local_user(),'content_expire')) ? true : false),
			'$expires' => t('Set expiration date'),
			'$feature_encrypt' => ((feature_enabled(local_user(),'content_encrypt')) ? true : false),
			'$encrypt' => t('Encrypt text'),
			'$cipher' => $cipher,

		));

		return $o;
	}

}
