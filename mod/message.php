<?php

require_once('include/acl_selectors.php');
require_once('include/message.php');
require_once('include/zot.php');


function message_aside(&$a) {

	$a->set_widget('newmessage',replace_macros(get_markup_template('message_side.tpl'), array(
		'$tabs'=> array(),
		'$new'=>array(
			'label' => t('New Message'),
			'url' => $a->get_baseurl(true) . '/message/new',
			'sel'=> (argv(1) == 'new'),
		)
	)));

}

function message_post(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$replyto   = ((x($_REQUEST,'replyto'))      ? notags(trim($_REQUEST['replyto']))      : '');
	$subject   = ((x($_REQUEST,'subject'))      ? notags(trim($_REQUEST['subject']))      : '');
	$body      = ((x($_REQUEST,'body'))         ? escape_tags(trim($_REQUEST['body']))    : '');
	$recipient = ((x($_REQUEST,'messageto'))    ? notags(trim($_REQUEST['messageto']))    : '');
	$rstr      = ((x($_REQUEST,'messagerecip')) ? notags(trim($_REQUEST['messagerecip'])) : '');

	if(! $recipient) {
		$channel = $a->get_channel();

		$ret = zot_finger($rstr,$channel);

		if(! $ret) {
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
			$permissions = aes_unencapsulate($j['permissions'],$channel['channel_prvkey']);
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


	if(feature_enabled(local_user(),'richtext')) {
		$body = fix_mce_lf($body);
	}
	
	$ret = send_message(local_user(), $recipient, $body, $subject, $replyto);
	$norecip = false;

	switch($ret){
		case -1:
			notice( t('No recipient selected.') . EOL );
			$norecip = true;
			break;
		case -2:
			notice( t('Unable to locate contact information.') . EOL );
			break;
		case -3:
			notice( t('Message could not be sent.') . EOL );
			break;
		case -4:
			notice( t('Message collection failure.') . EOL );
			break;
		default:
			info( t('Message sent.') . EOL );
	}

	// fake it to go back to the input form if no recipient listed

	if($norecip) {
		$a->argc = 2;
		$a->argv[1] = 'new';
	}

}

// Note: the code in 'item_extract_images' and 'item_redir_and_replace_images'
// is identical to the code in include/conversation.php
if(! function_exists('item_extract_images')) {
function item_extract_images($body) {

	$saved_image = array();
	$orig_body = $body;
	$new_body = '';

	$cnt = 0;
	$img_start = strpos($orig_body, '[img');
	$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
	$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
	while(($img_st_close !== false) && ($img_end !== false)) {

		$img_st_close++; // make it point to AFTER the closing bracket
		$img_end += $img_start;

		if(! strcmp(substr($orig_body, $img_start + $img_st_close, 5), 'data:')) {
			// This is an embedded image

			$saved_image[$cnt] = substr($orig_body, $img_start + $img_st_close, $img_end - ($img_start + $img_st_close));
			$new_body = $new_body . substr($orig_body, 0, $img_start) . '[!#saved_image' . $cnt . '#!]';

			$cnt++;
		}
		else
			$new_body = $new_body . substr($orig_body, 0, $img_end + strlen('[/img]'));

		$orig_body = substr($orig_body, $img_end + strlen('[/img]'));

		if($orig_body === false) // in case the body ends on a closing image tag
			$orig_body = '';

		$img_start = strpos($orig_body, '[img');
		$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
		$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
	}

	$new_body = $new_body . $orig_body;

	return array('body' => $new_body, 'images' => $saved_image);
}}

if(! function_exists('item_redir_and_replace_images')) {
function item_redir_and_replace_images($body, $images, $cid) {

	$origbody = $body;
	$newbody = '';

	for($i = 0; $i < count($images); $i++) {
		$search = '/\[url\=(.*?)\]\[!#saved_image' . $i . '#!\]\[\/url\]' . '/is';
		$replace = '[url=' . z_path() . '/redir/' . $cid 
		           . '?f=1&url=' . '$1' . '][!#saved_image' . $i . '#!][/url]' ;

		$img_end = strpos($origbody, '[!#saved_image' . $i . '#!][/url]') + strlen('[!#saved_image' . $i . '#!][/url]');
		$process_part = substr($origbody, 0, $img_end);
		$origbody = substr($origbody, $img_end);

		$process_part = preg_replace($search, $replace, $process_part);
		$newbody = $newbody . $process_part;
	}
	$newbody = $newbody . $origbody;

	$cnt = 0;
	foreach($images as $image) {
		// We're depending on the property of 'foreach' (specified on the PHP website) that
		// it loops over the array starting from the first element and going sequentially
		// to the last element
		$newbody = str_replace('[!#saved_image' . $cnt . '#!]', '[img]' . $image . '[/img]', $newbody);
		$cnt++;
	}

	return $newbody;
}}



function message_content(&$a) {

	$o = '';
	nav_set_selected('messages');

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$myprofile = $a->get_baseurl(true) . '/channel/' . $a->user['nickname'];

	$tpl = get_markup_template('mail_head.tpl');
	$header = replace_macros($tpl, array(
		'$messages' => t('Messages'),
		'$tab_content' => $tab_content
	));


	if((argc() == 3) && (argv(1) === 'drop' || argv(1) === 'dropconv')) {
		if(! intval(argv(2)))
			return;
		$cmd = argv(1);
		if($cmd === 'drop') {
			$r = q("DELETE FROM `mail` WHERE `id` = %d AND channel_id = %d LIMIT 1",
				intval(argv(2)),
				intval(local_user())
			);
			if($r) {
				info( t('Message deleted.') . EOL );
			}
			goaway($a->get_baseurl(true) . '/message' );
		}
		else {
			$r = q("SELECT `parent_uri` FROM `mail` WHERE `id` = %d AND channel_id = %d LIMIT 1",
				intval(argv(2)),
				intval(local_user())
			);
			if(count($r)) {
				$parent = $r[0]['parent_uri'];


				$r = q("DELETE FROM `mail` WHERE `parent_uri` = '%s' AND channel_id = %d ",
					dbesc($parent),
					intval(local_user())
				);

				if($r)
					info( t('Conversation removed.') . EOL );
			} 
			goaway($a->get_baseurl(true) . '/message' );
		}	
	
	}

	$channel = $a->get_channel();

	if((argc() > 1) && ($a->argv[1] === 'new')) {
		
		$o .= $header;
		
		$plaintext = false;
		if(intval(get_pconfig(local_user(),'system','plaintext')))
			$plaintext = true;
		if(! feature_enabled(local_user(),'richtext'))
			$plaintext = true;

		$tpl = get_markup_template('msg-header.tpl');

		$a->page['htmlhead'] .= replace_macros($tpl, array(
			'$baseurl' => $a->get_baseurl(true),
			'$editselect' => (($plaintext) ? 'none' : '/(profile-jot-text|prvmail-text)/'),
			'$nickname' => $channel['channel_addr'],
			'$linkurl' => t('Please enter a link URL:')
		));
	
		$preselect = (isset($a->argv[2])?array($a->argv[2]):false);
			

		$prename = $preurl = $preid = '';

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
			'$text' => ((x($_REQUEST,'body')) ? escape_tags(htmlspecialchars($_REQUEST['body'])) : ''),
			'$readonly' => '',
			'$yourmessage' => t('Your message:'),
			'$select' => $select,
			'$parent' => '',
			'$upload' => t('Upload photo'),
			'$insert' => t('Insert web link'),
			'$wait' => t('Please wait'),
			'$submit' => t('Submit')
		));

		return $o;
	}

	if(argc() == 1) {

		// list messages

		$o .= $header;

		
		$r = q("SELECT count(*) AS `total` FROM `mail` 
			WHERE channel_id = %d",
			intval(local_user())
		);
		if($r)
			$a->set_pager_total($r[0]['total']);

		$r = q("SELECT * from mail WHERE channel_id = %d order by created desc limit %d, %d",
			intval(local_user()),
			intval($a->pager['start']),
			intval($a->pager['itemspage'])
		);
		if(! $r) {
			info( t('No messages.') . EOL);
			return $o;
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
		
		$tpl = get_markup_template('mail_list.tpl');
		foreach($r as $rr) {
			$rr['from'] = find_xchan_in_array($rr['from_xchan'],$c);
			$rr['to']   = find_xchan_in_array($rr['to_xchan'],$c);
			$rr['seen'] = (($rr['mail_flags'] & MAIL_SEEN) ? 1 : "");

			if($a->get_template_engine() === 'internal') {
				$from_name_e = template_escape($rr['from']['xchan_name']);
				$subject_e = template_escape((($rr['seen']) ? $rr['title'] : '<strong>' . $rr['title'] . '</strong>'));
				$body_e = template_escape($rr['body']);
				$to_name_e = template_escape($rr['to']['xchan_name']);
			}
			else {
				$from_name_e = $rr['from']['xchan_name'];
				$subject_e = (($rr['seen']) ? $rr['title'] : '<strong>' . $rr['title'] . '</strong>');
				$body_e = $rr['body'];
				$to_name_e = $rr['to']['xchan_name'];
			}
			
			$o .= replace_macros($tpl, array(
				'$id' => $rr['id'],
				'$from_name' => template_escape($rr['from']['xchan_name']),
				'$from_url' =>  z_root() . '/chanview/?f=&hash=' . $rr['from_xchan'],
				'$from_photo' => $rr['from']['xchan_photo_s'],
				'$to_name' => template_escape($rr['to']['xchan_name']),
				'$to_url' =>  z_root() . '/chanview/?f=&hash=' . $rr['to_xchan'],
				'$to_photo' => $rr['to']['xchan_photo_s'],
				'$subject' => template_escape((($rr['seen']) ? $rr['title'] : '<strong>' . $rr['title'] . '</strong>')),
				'$delete' => t('Delete message'),
				'$body' => template_escape($rr['body']),
				'$date' => datetime_convert('UTC',date_default_timezone_get(),$rr['created'], t('D, d M Y - g:i A')),
				'$seen' => $rr['seen']
			));
		}
		$o .= paginate($a);	
		return $o;
	}

	if((argc() > 1) && (intval(argv(1)))) {

		$o .= $header;

		$plaintext = true;
		if( local_user() && feature_enabled(local_user(),'richtext') )
			$plaintext = false;

		$r = q("SELECT parent_uri from mail WHERE channel_id = %d and id = %d limit 1",
			intval(local_user()),
			intval(argv(1))
		);

		if(! $r) {
			info( t('Message not found.') . EOL);
			return $o;
		}

		$messages = q("select * from mail where parent_uri = '%s' and channel_id = %d order by created asc",
			dbesc($r[0]['parent_uri']),
			intval(local_user())
		);

		if(! $messages) {
			info( t('Message not found.') . EOL);
			return $o;
		}

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

		$r = q("UPDATE `mail` SET mail_flags = (mail_flags ^ %d) where not (mail_flags & %d) and parent_uri = '%s' AND channel_id = %d",
			intval(MAIL_SEEN),
			intval(MAIL_SEEN),
			dbesc($r[0]['parent_uri']),
			intval(local_user())
		);

		require_once("include/bbcode.php");

		$tpl = get_markup_template('msg-header.tpl');
	
		$a->page['htmlhead'] .= replace_macros($tpl, array(
			'$nickname' => $channel['channel_addr'],
			'$baseurl' => $a->get_baseurl(true),
			'$editselect' => (($plaintext) ? 'none' : '/(profile-jot-text|prvmail-text)/'),
			'$linkurl' => t('Please enter a link URL:')
		));


		$mails = array();
		$seen = 0;
		$unknown = false;

		foreach($messages as $message) {
			$message['from'] = find_xchan_in_array($message['from_xchan'],$c);
			$message['to']   = find_xchan_in_array($message['to_xchan'],$c);


logger('message: ' . print_r($message,true));

//			$extracted = item_extract_images($message['body']);
//			if($extracted['images'])
//				$message['body'] = item_redir_and_replace_images($extracted['body'], $extracted['images'], $message['contact-id']);

			if($a->get_template_engine() === 'internal') {
				$from_name_e = template_escape($message['from']['xchan_name']);
				$subject_e = template_escape($message['title']);
				$body_e = template_escape(smilies(bbcode($message['body'])));
				$to_name_e = template_escape($message['to']['xchan_name']);
			}
			else {
				$from_name_e = $message['from']['xchan_name'];
				$subject_e = $message['title'];
				$body_e = smilies(bbcode($message['body']));
				$to_name_e = $message['to']['xchan_name'];
			}

			$mails[] = array(
				'id' => $message['id'],
				'from_name' => $from_name_e,
				'from_url' =>  z_root() . '/chanview/?f=&hash=' . $message['from_xchan'],
				'from_photo' => $message['from']['xchan_photo_m'],
				'to_name' => $to_name_e,
				'to_url' =>  z_root() . '/chanview/?f=&hash=' . $message['to_xchan'],
				'to_photo' => $message['to']['xchan_photo_m'],
				'subject' => $subject_e,
				'body' => $body_e,
				'delete' => t('Delete message'),
				'date' => datetime_convert('UTC',date_default_timezone_get(),$message['created'],'D, d M Y - g:i A'),
			);
				
			$seen = $message['seen'];

		}

		logger('mails: ' . print_r($mails,true));

		$recp = (($message['from_xchan'] === $channel['channel_hash']) ? 'to' : 'from');

		$select = $message[$recp]['xchan_name'] . '<input type="hidden" name="messageto" value="' . $message[$recp]['xchan_hash'] . '" />';
		$parent = '<input type="hidden" name="replyto" value="' . $message['parent_uri'] . '" />';

		if($a->get_template_engine() === 'internal') {
			$subjtxt_e = template_escape($message['title']);
		}
		else {
			$subjtxt_e = $message['title'];
		}

		$tpl = get_markup_template('mail_display.tpl');
		$o = replace_macros($tpl, array(
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
			'$subjtxt' => $subjtxt_e,
			'$readonly' => ' readonly="readonly" style="background: #BBBBBB;" ',
			'$yourmessage' => t('Your message:'),
			'$text' => '',
			'$select' => $select,
			'$parent' => $parent,
			'$upload' => t('Upload photo'),
			'$insert' => t('Insert web link'),
			'$submit' => t('Submit'),
			'$wait' => t('Please wait')

		));

		return $o;
	}

}
