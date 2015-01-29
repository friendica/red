<?php

function removeme_post(&$a) {

	if(! local_channel())
		return;

	if(x($_SESSION,'submanage') && intval($_SESSION['submanage']))
		return;

	if((! x($_POST,'qxz_password')) || (! strlen(trim($_POST['qxz_password']))))
		return;

	if((! x($_POST,'verify')) || (! strlen(trim($_POST['verify']))))
		return;

	if($_POST['verify'] !== $_SESSION['remove_account_verify'])
		return;


	$account = $a->get_account();

	if(! account_verify_password($account['account_email'],$_POST['qxz_password']))
		return;

	if($account['account_password_changed'] != NULL_DATE) {
		$d1 = datetime_convert('UTC','UTC','now - 48 hours');
		if($account['account_password_changed'] > d1) {
			notice( t('Channel removals are not allowed within 48 hours of changing the account password.') . EOL);
			return;
		}
	}

	require_once('include/Contact.php');

	$global_remove = intval($_POST['global']);

	channel_remove(local_channel(),1 - $global_remove,true);

}



function removeme_content(&$a) {

	if(! local_channel())
		goaway(z_root());

	$hash = random_string();

	$_SESSION['remove_account_verify'] = $hash;

	$tpl = get_markup_template('removeme.tpl');
	$o .= replace_macros($tpl, array(
		'$basedir' => $a->get_baseurl(),
		'$hash' => $hash,
		'$title' => t('Remove This Channel'),
		'$desc' => t('This will completely remove this channel from the network. Once this has been done it is not recoverable.'),
		'$passwd' => t('Please enter your password for verification:'),
		'$global' => array('global', t('Remove this channel and all its clones from the network'), false, t('By default only the instance of the channel located on this hub will be removed from the network')),
		'$submit' => t('Remove Channel')
	));

	return $o;		

}