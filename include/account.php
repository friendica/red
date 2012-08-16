<?php

require_once('include/config.php');
require_once('include/network.php');
require_once('include/plugin.php');
require_once('include/text.php');
require_once('include/language.php');
require_once('include/datetime.php');


function check_account_email($email) {

	$result = array('error' => false, 'message' => '');

	// Caution: empty email isn't counted as an error in this function. Check emptiness separately. 

	if(! strlen($email))
		return $result;

	if((! valid_email($email)) || (! validate_email($email)))
		$result['message'] .= t('Not a valid email address') . EOL;
	elseif(! allowed_email($email))
		$result['message'] = t('Your email domain is not among those allowed on this site');
	else {	
		$r = q("select account_email from account where account_email = '%s' limit 1",
			dbesc($email)
		);
		if(count($r)) {
			$result['message'] .= t('Your email address is already registered at this site.');
		}
	}
	if($result['message'])
		$result['error'] = true;

	return $result;
}



function create_account($arr) {

	// Required: { email, password }

	$result = array('success' => false, 'email' => '', 'password' => '', 'message' => '');

	$using_invites = get_config('system','invitation_only');
	$num_invites   = get_config('system','number_invites');

	$invite_id  = ((x($arr,'invite_id'))  ? notags(trim($arr['invite_id']))  : '');
	$email      = ((x($arr,'email'))      ? notags(trim($arr['email']))      : '');
	$password   = ((x($arr,'password'))   ? trim($arr['password'])           : '');
	$password2  = ((x($arr,'password2'))  ? trim($arr['password2'])          : '');
	$parent     = ((x($arr,'parent'))     ? intval($arr['parent'])           : 0 );
	$flags      = ((x($arr,'account_flags')) ? intval($arr['account_flags']) : ACCOUNT_OK);

	if($using_invites) {
		if(! $invite_id) {
			$result['message'] .= t('An invitation is required.') . EOL;
			return $result;
		}
		$r = q("select * from register where `hash` = '%s' limit 1", dbesc($invite_id));
		if(! results($r)) {
			$result['message'] .= t('Invitation could not be verified.') . EOL;
			return $result;
		}
	} 

	if((! x($email)) || (! x($password))) {
		notice( t('Please enter the required information.') . EOL );
		return;
	}

	$email_result = check_account_email($email);

	if(! $email_result['error']) {
		$result['message'] = $email_result['message'];
		return $result;
	}

	$password_encoded = hash('whirlpool',$password);

	$r = q("INSERT INTO account 
			( account_parent,  account_password, account_email, account_language, 
			  account_created, account_flags,    account_roles, account_expires, 
			  account_service_class )
		VALUES ( %d, '%s', '%s', '%s', '%s', %d, %d, '%s', '%s' )",
		intval($parent),
		dbesc($password_encoded),
		dbesc($email),
		dbesc(get_best_language()),
		dbesc(datetime_convert()),
		dbesc($flags),
		dbesc(0),
		dbesc($expires),
		dbesc($default_service_class)

	);
	if(! $r) {
		logger('create_account: DB INSERT failed.');
		$result['message'] = t('Failed to store account information.');
		return($result);
	}

	$result['success'] = true;

	$result['email']    = $email;
	$result['password'] = $password;
	return $result;

}
