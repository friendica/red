<?php


function invite_post(&$a) {
	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}


	$recips = explode("\n",$_POST['recipients']);
	$message = $_POST['message'];

	$total = 0;

	foreach($recips as $recip) {

		$recip = trim($recip);

		if(!eregi('[A-Za-z0-9._%-]+@[A-Za-z0-9._%-]+\.[A-Za-z]{2,6}', $recip)) {
	                notice(  $recip . t(' : ') . t('Not a valid email address.') . EOL);
			continue;
		}

                $res = mail($recip, t('Please join my network on ') . $a->config['sitename'], $message, "From: " . $a->user['email']);
		if($res) {
			$total ++;
		}
		else {
			notice( $recip . t(' : ') . t('Message delivery failed.') . EOL);
		}

	}
	notice( $total . t(' messages sent.') . EOL);
	return;
}


function invite_content(&$a) {
	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$tpl = load_view_file('view/invite.tpl');
	
	$o = replace_macros($tpl, array(
		'$invite' => t('Send invitations'),
		'$addr_text' => t('Enter email addresses, one per line:'),
		'$msg_text' => t('Your message:'),
		'$default_message' => t('Please join my social network on ') . $a->config['sitename'] . t("\r\n") . t("\r\n")
			. t('To accept this invitation, please visit:') . t("\r\n") . t("\r\n") . $a->get_baseurl()
			. t("\r\n") . t("\r\n") . t('Once you have registered, please make an introduction via my profile page at:') 
			. t("\r\n") . t("\r\n") . $a->get_baseurl() . '/profile/' . $a->user['nickname'] ,
		'$submit' => t('Submit')
	));

	return $o;
}