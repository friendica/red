<?php

require_once('include/acl_selectors.php');
require_once('include/message.php');
require_once('include/zot.php');
require_once("include/bbcode.php");
require_once('include/Contact.php');


function message_content(&$a) {

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

	if((argc() == 3) && (argv(1) === 'dropconv')) {
		if(! intval(argv(2)))
			return;
		$cmd = argv(1);
		$r = private_messages_drop(local_user(), argv(2), true);
		if($r)
			info( t('Conversation removed.') . EOL );
		goaway($a->get_baseurl(true) . '/message' );
	}
	if(argc() == 1) {

		// list messages

		$o .= $header;

		// private_messages_list() can do other more complicated stuff, for now keep it simple


		$r = private_messages_list(local_user(), '', $a->pager['start'], $a->pager['itemspage']);

		if(! $r) {
			info( t('No messages.') . EOL);
			return $o;
		}

		$tpl = get_markup_template('mail_list.tpl');
		foreach($r as $rr) {
			
			$o .= replace_macros($tpl, array(
				'$id' => $rr['id'],
				'$from_name' => $rr['from']['xchan_name'],
				'$from_url' =>  chanlink_hash($rr['from_xchan']),
				'$from_photo' => $rr['from']['xchan_photo_s'],
				'$to_name' => $rr['to']['xchan_name'],
				'$to_url' =>  chanlink_hash($rr['to_xchan']),
				'$to_photo' => $rr['to']['xchan_photo_s'],
				'$subject' => (($rr['seen']) ? $rr['title'] : '<strong>' . $rr['title'] . '</strong>'),
				'$delete' => t('Delete message'),
				'$body' => smilies(bbcode($rr['body'])),
				'$date' => datetime_convert('UTC',date_default_timezone_get(),$rr['created'], t('D, d M Y - g:i A')),
				'$seen' => $rr['seen']
			));
		}
		$o .= alt_pager($a,count($r));	
		return $o;
	}


}
