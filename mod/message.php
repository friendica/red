<?php

require_once('view/acl_selectors.php');

function message_init(&$a) {


}

function message_post(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$replyto = notags(trim($_POST['replyto']));
	$recipient = intval($_POST['messageto']);
	$subject = notags(trim($_POST['subject']));
	$body = escape_tags(trim($_POST['body']));

	if(! $recipient) {
		notice( t('No recipient selected.') . EOL );
		return;
	}

	$me = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 1 LIMIT 1",
		intval($_SESSION['uid'])
	);
	$contact = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($recipient),
			intval($_SESSION['uid'])
	);

	if(! (count($me) && (count($contact)))) {
		notice( t('Unable to locate contact information.') . EOL );
		return;
	}

	$hash = random_string();
 	$uri = 'urn:X-dfrn:' . $a->get_baseurl() . ':' . $_SESSION['uid'] . ':' . $hash ;

	if(! strlen($replyto))
		$replyto = $uri;

	$r = q("INSERT INTO `mail` ( `uid`, `from-name`, `from-photo`, `from-url`, 
		`contact-id`, `title`, `body`, `delivered`, `seen`, `replied`, `uri`, `parent-uri`, `created`)
		VALUES ( %d, '%s', '%s', '%s', %d, '%s', '%s', %d, %d, %d, '%s', '%s', '%s' )",
		intval($_SESSION['uid']),
		dbesc($me[0]['name']),
		dbesc($me[0]['thumb']),
		dbesc($me[0]['url']),
		intval($recipient),
		dbesc($subject),
		dbesc($body),
		0,
		0,
		0,
		dbesc($uri),
		dbesc($replyto),
		datetime_convert()
	);
	$r = q("SELECT * FROM `mail` WHERE `uri` = '%s' and `uid` = %d LIMIT 1",
		dbesc($uri),
		intval($_SESSION['uid'])
	);
	if(count($r))
		$post_id = $r[0]['id'];

	$url = $a->get_baseurl();

	if($post_id) {
		proc_close(proc_open("php include/notifier.php \"$url\" \"mail\" \"$post_id\" > mail.log &",
			array(),$foo));
		notice( t('Message sent.') . EOL );
	}
	else {
		notice( t('Message could not be sent.') . EOL );
	}
	return;

}

function message_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$myprofile = $a->get_baseurl() . '/profile/' . $a->user['nickname'];

	if(($a->argc > 1) && ($a->argv[1] == 'new')) {
		
		$tpl = file_get_contents('view/jot-header.tpl');
	
		$a->page['htmlhead'] .= replace_macros($tpl, array('$baseurl' => $a->get_baseurl()));

		$select .= contact_select('messageto','message-to-select', false, 4, true);
		$tpl = file_get_contents('view/prv_message.tpl');
		$o = replace_macros($tpl,array(
			'$header' => t('Send Private Message'),
			'$to' => t('To:'),
			'$subject' => t('Subject:'),
			'$yourmessage' => t('Your message:'),
			'$select' => $select,
			'$upload' => t('Upload photo'),
			'$insert' => t('Insert web link'),
			'$wait' => t('Please wait')

		));

		return $o;
	}

	if($a->argc == 1) {

		$r = q("SELECT count(*) AS `total` FROM `mail` 
			WHERE `mail`.`uid` = %d AND `from-url` != '%s' ",
			intval($_SESSION['uid']),
			dbesc($myprofile)
		);
		if(count($r))
			$a->set_pager_total($r[0]['total']);
	
		$r = q("SELECT `mail`.*, `contact`.`name`, `contact`.`url`, `contact`.`thumb` 
			FROM `mail` LEFT JOIN `contact` ON `mail`.`contact-id` = `contact`.`id` 
			WHERE `mail`.`uid` = %d AND `from-url` != '%s' LIMIT %d , %d ",
			intval($_SESSION['uid']),
			dbesc($myprofile),
			intval($a->pager['start']),
			intval($a->pager['itemspage'])
		);
		if(! count($r)) {
			notice( t('No messages.') . EOL);
			return;
		}

		$tpl = file_get_contents('view/mail_list.tpl');
		foreach($r as $rr) {
			$o .= replace_macros($tpl, array(
				'$id' => $rr['id'],
				'$from_name' =>$rr['from-name'],
				'$from_url' => $a->get_baseurl() . '/redir/' . $rr['contact-id'],
				'$from_photo' => $rr['from-photo'],
				'$subject' => (($rr['seen']) ? $rr['title'] : '<strong>' . $rr['title'] . '</strong>'),
				'$to_name' => $rr['name'],
				'$date' => datetime_convert('UTC',date_default_timezone_get(),$rr['created'],'D, d M Y - g:i A')
			));
		}
		$o .= paginate($a);	
		return $o;
	}

}