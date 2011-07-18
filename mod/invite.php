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

	if(get_config('system','invitation_only')) {
		$invonly = true;
		$x = get_pconfig(local_user(),'system','invites_remaining');
		if((! $x) && (! is_site_admin()))
			return;
	}

	foreach($recips as $recip) {

		$recip = trim($recip);

		if(! valid_email($recip)) {
			notice(  sprintf( t('%s : Not a valid email address.'), $recip) . EOL);
			continue;
		}
		
		if($invonly && ($x || is_site_admin())) {
			$code = autoname(8) . srand(1000,9999);
			$nmessage = str_replace('$invite_code',$code,$message);

			$r = q("INSERT INTO `register` (`hash`,`created`) VALUES ('%s', '%s') ",
				dbesc($code),
				dbesc(datetime_convert())
			);

			if(! is_site_admin()) {
				$x --;
				if($x >= 0)
					set_pconfig(local_user(),'system','invites_remaining',$x);
				else
					return;
			}
		}
		else
			$nmessage = $message;

		$res = mail($recip, sprintf( t('Please join my network on %s'), $a->config['sitename']), 
			$nmessage, 
			"From: " . $a->user['email'] . "\n"
			. 'Content-type: text/plain; charset=UTF-8' . "\n"
			. 'Content-transfer-encoding: 8bit' );

		if($res) {
			$total ++;
		}
		else {
			notice( sprintf( t('%s : Message delivery failed.'), $recip) . EOL);
		}

	}
	notice( sprintf( tt("%d message sent.", "%d messages sent.", $total) , $total) . EOL);
	return;
}


function invite_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$tpl = get_markup_template('invite.tpl');
	$invonly = false;

	if(get_config('system','invitation_only')) {
		$invonly = true;
		$x = get_pconfig(local_user(),'system','invites_remaining');
		if((! $x) && (! is_site_admin())) {
			notice( t('You have no more invitations available') . EOL);
			return '';
		}
	}			


	$o = replace_macros($tpl, array(
		'$invite' => t('Send invitations'),
		'$addr_text' => t('Enter email addresses, one per line:'),
		'$msg_text' => t('Your message:'),
		'$default_message' => sprintf(t('Please join my social network on %s'), $a->config['sitename']) . "\r\n" . "\r\n"
			. t('To accept this invitation, please visit:') . "\r\n" . "\r\n" . $a->get_baseurl()
			. "\r\n" . "\r\n" . (($invonly) ? t('You will need to supply this invitation code: $invite_code') . "\r\n" . "\r\n" : '') .t('Once you have registered, please connect with me via my profile page at:') 
			. "\r\n" . "\r\n" . $a->get_baseurl() . '/profile/' . $a->user['nickname'] ,
		'$submit' => t('Submit')
	));

	return $o;
}