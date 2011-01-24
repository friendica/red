<?php

/**
 * module: invite.php
 *
 * send email invitations to join social network
 *
 */

function invite_post(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}


	$recips  = ((x($_POST,'recipients')) ? explode("\n",$_POST['recipients']) : array());
	$message = ((x($_POST,'message'))    ? notags(trim($_POST['message']))    : '');

	$total = 0;

	foreach($recips as $recip) {

		$recip = trim($recip);

		if(! valid_email($recip)) {
			notice(  $recip . t(' : ') . t('Not a valid email address.') . EOL);
			continue;
		}

		$res = mail($recip, t('Please join my network on ') . $a->config['sitename'], 
			$message, "From: " . $a->user['email']);
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
		'$default_message' => t('Please join my social network on ') . $a->config['sitename'] . "\r\n" . "\r\n"
			. t('To accept this invitation, please visit:') . "\r\n" . "\r\n" . $a->get_baseurl()
			. "\r\n" . "\r\n" . t('Once you have registered, please connect with me via my profile page at:') 
			. "\r\n" . "\r\n" . $a->get_baseurl() . '/profile/' . $a->user['nickname'] ,
		'$submit' => t('Submit')
	));

	return $o;
}