<?php

require_once('include/config.php');
require_once('include/network.php');
require_once('include/plugin.php');
require_once('include/text.php');
require_once('include/language.php');
require_once('include/datetime.php');


function check_account_email($email) {

	$result = array('error' => false, 'message' => '');

	// Caution: empty email isn't counted as an error in this function. 
	// Check for empty value separately. 

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

	$arr = array('email' => $email, 'result' => $result);
	call_hooks('check_account_email', $arr);

	return $arr['result'];
}

function check_account_password($password) {
	$result = array('error' => false, 'message' => '');

	// The only validation we perform by default is pure Javascript to 
	// check minimum length and that both entered passwords match.
	// Use hooked functions to perform complexity requirement checks. 

	$arr = array('password' => $password, 'result' => $result);
	call_hooks('check_account_password', $arr);

	return $arr['result'];

}

function check_account_invite($invite_code) {
	$result = array('error' => false, 'message' => '');

	$using_invites = get_config('system','invitation_only');

	if($using_invites) {
		if(! $invite_code) {
			$result['message'] .= t('An invitation is required.') . EOL;
		}
		$r = q("select * from register where `hash` = '%s' limit 1", dbesc($invite_code));
		if(! results($r)) {
			$result['message'] .= t('Invitation could not be verified.') . EOL;
		}
	}
	if(strlen($result['message']))
		$result['error'] = true;

	$arr = array('invite_code' => $invite_code, 'result' => $result);
	call_hooks('check_account_invite', $arr);

	return $arr['result'];

}


function create_account($arr) {

	// Required: { email, password }

	$result = array('success' => false, 'email' => '', 'password' => '', 'message' => '');

	$invite_code = ((x($arr,'invite_code'))   ? notags(trim($arr['invite_code']))  : '');
	$email       = ((x($arr,'email'))         ? notags(trim($arr['email']))        : '');
	$password    = ((x($arr,'password'))      ? trim($arr['password'])             : '');
	$password2   = ((x($arr,'password2'))     ? trim($arr['password2'])            : '');
	$parent      = ((x($arr,'parent'))        ? intval($arr['parent'])             : 0 );
	$flags       = ((x($arr,'account_flags')) ? intval($arr['account_flags'])      : ACCOUNT_OK);

	if((! x($email)) || (! x($password))) {
		$result['message'] = t('Please enter the required information.');
		return $result;
	}

	$invite_result = check_account_invite($invite_code);
	if($invite_result['error']) {
		$result['message'] = $invite_result['message'];
		return $result;
	}


	$email_result = check_account_email($email);

	if($email_result['error']) {
		$result['message'] = $email_result['message'];
		return $result;
	}

	$password_result = check_account_password($password);

	if($password_result['error']) {
		$result['message'] = $password_result['message'];
		return $result;
	}

	$salt = random_string(32);
	$password_encoded = hash('whirlpool', $salt . $password);

	$r = q("INSERT INTO account 
			( account_parent,  account_salt, account_password, account_email, account_language, 
			  account_created, account_flags,    account_roles, account_expires, 
			  account_service_class )
		VALUES ( %d, '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', '%s' )",
		intval($parent),
		dbesc($salt),
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

	$r = q("select * from account where account_email = '%s' and password = '%s' limit 1",
		dbesc($email),
		dbesc($password_encoded)
	);
	if($r && count($r)) {
		$result['account'] = $r[0];
	}
	else {	
		logger('create_account: could not retrieve newly created account');
	}

	$result['success'] = true;

	$result['email']    = $email;
	$result['password'] = $password;
	return $result;

}

/**
 * Verify login credentials
 *
 * Returns account record on success, null on failure
 *
 */

function account_verify_password($email,$pass) {
	$r = q("select * from account where email = '%s'",
		dbesc($email)
	);
	if(! ($r && count($r)))
		return null;
	foreach($r as $record) {
		if(hash('whirlpool',$record['account_salt'] . $pass) === $record['account_password']) {
			return $record;
		}
	}
	return null;
}


