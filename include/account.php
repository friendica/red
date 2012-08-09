<?php

require_once('include/config.php');
require_once('include/network.php');
require_once('include/plugin.php');
require_once('include/text.php');
require_once('include/language.php');
require_once('include/datetime.php');

function create_account($arr) {

	// Required: { email, password }

	$a = get_app();
	$result = array('success' => false, 'user' => null, 'password' => '', 'message' => '');

	$using_invites = get_config('system','invitation_only');
	$num_invites   = get_config('system','number_invites');


	$invite_id  = ((x($arr,'invite_id'))  ? notags(trim($arr['invite_id']))  : '');
	$email      = ((x($arr,'email'))      ? notags(trim($arr['email']))      : '');
	$password   = ((x($arr,'password'))   ? trim($arr['password'])           : '');
	$password2  = ((x($arr,'password2'))  ? trim($arr['password2'])          : '');
	$parent     = ((x($arr,'parent'))     ? intval($arr['parent'])           : 0 );

	$blocked    = ((x($arr,'blocked'))    ? intval($arr['blocked'])  : 0);
	$verified   = ((x($arr,'verified'))   ? intval($arr['verified']) : 0);

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

	if(! allowed_email($email))
		$result['message'] .= t('Your email domain is not among those allowed on this site.') . EOL;

	if((! valid_email($email)) || (! validate_email($email)))
		$result['message'] .= t('Not a valid email address.') . EOL;


	if(strlen($result['message'])) {
		return $result;
	}


	$password_encoded = hash('whirlpool',$password);

	$result['password'] = $new_password;


	$r = q("INSERT INTO account 
			( account_parent,  account_password, account_email, account_language, 
			  account_created, account_flags,    account_roles, account_expires, 
			  account_service_class )
		VALUES ( %d, '%s', '%s', '%s', '%s', %d, %d, '%s', '%s' )",
		intval($parent),
		dbesc($password_encoded),
		dbesc($email),
		dbesc($a->language),
		dbesc(datetime_convert()),
		dbesc($flags),
		dbesc(0),
		dbesc($expires),
		dbesc($default_service_class)

	);

	$result['success'] = true;
	return $result;

}
