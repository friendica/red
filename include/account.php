<?php /** @file */

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

function check_account_admin($arr) {
	if(is_site_admin())
		return true;
	$admin_mail = trim(get_config('system','admin_email'));
	if(strlen($admin_email) && $admin_email === trim($arr['email']))
		return true;
	return false;
}

function account_total() {
	$r = q("select account_id from account where 1");
	if(is_array($r))
		return count($r);
	return false;
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
	$roles       = ((x($arr,'account_roles')) ? intval($arr['account_roles'])      : 0 );
	$expires     = ((x($arr,'expires'))       ? intval($arr['expires'])            : '0000-00-00 00:00:00');
	
	$default_service_class = get_config('system','default_service_class');
	if($default_service_class === false)
		$default_service_class = '';

	if((! x($email)) || (! x($password))) {
		$result['message'] = t('Please enter the required information.');
		return $result;
	}

	// prevent form hackery

	if($roles & ACCOUNT_ROLE_ADMIN) {
		$admin_result = check_account_admin($arr);
		if(! $admin_result) {
			$roles = 0;
		}
	}

	// allow the admin_email account to be admin, but only if it's the first account.

	$c = account_total();
	if((c === 0) && (check_account_admin($arr)))
		$roles |= ACCOUNT_ROLE_ADMIN;


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
			( account_parent,  account_salt,  account_password, account_email,   account_language, 
			  account_created, account_flags, account_roles,    account_expires, account_service_class )
		VALUES ( %d, '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', '%s' )",
		intval($parent),
		dbesc($salt),
		dbesc($password_encoded),
		dbesc($email),
		dbesc(get_best_language()),
		dbesc(datetime_convert()),
		dbesc($flags),
		dbesc($roles),
		dbesc($expires),
		dbesc($default_service_class)

	);
	if(! $r) {
		logger('create_account: DB INSERT failed.');
		$result['message'] = t('Failed to store account information.');
		return($result);
	}

	$r = q("select * from account where account_email = '%s' and account_password = '%s' limit 1",
		dbesc($email),
		dbesc($password_encoded)
	);
	if($r && count($r)) {
		$result['account'] = $r[0];
	}
	else {	
		logger('create_account: could not retrieve newly created account');
	}

	// Set the parent record to the current record_id if no parent was provided

	if(! $parent) {
		$r = q("update account set account_parent = %d where account_id = %d limit 1",
			intval($result['account']['account_id']),
			intval($result['account']['account_id'])
		);
		if(! $r) {
			logger('create_account: failed to set parent');
		}
		$result['account']['parent'] = $result['account']['account_id'];
	}

	$result['success']  = true;
	$result['email']    = $email;
	$result['password'] = $password;
	return $result;

}



function send_reg_approval_email($arr) {

	$r = q("select * from account where account_roles & " . intval(ACCOUNT_ROLE_ADMIN));
	if(! ($r && count($r)))
		return false;

	$admins = array();

	foreach($r as $rr) {
		if(strlen($rr['account_email'])) {
			$admins[] = array('email' => $rr['account_email'], 'lang' => $rr['account_lang']);
		}
	}

	if(! count($admins))
		return false;

	$hash = random_string();

	$r = q("INSERT INTO register ( hash, created, uid, password, language ) VALUES ( '%s', '%s', %d, '%s', '%s' ) ",
		dbesc($hash),
		dbesc(datetime_convert()),
		intval($arr['account']['account_id']),
		dbesc(''),
		dbesc($arr['account']['account_language'])
	);

	$ip = $_SERVER['REMOTE_ADDR'];

	$details = (($ip) ? $ip . ' [' . gethostbyaddr($ip) . ']' : '[unknown or stealth IP]');


	$delivered = 0;

	foreach($admins as $admin) {
		if(strlen($admin['lang']))
			push_lang($admin['lang']);
		else
			push_lang('en');

		$email_msg = replace_macros(get_intltext_template('register_verify_eml.tpl'), array(
			'$sitename' => get_config('system','sitename'),
			'$siteurl'  =>  z_root(),
			'$email'    => $arr['email'],
			'$uid'      => $arr['account']['account_id'],
			'$hash'     => $hash,
			'$details'  => $details
		 ));

		$res = mail($admin['email'], sprintf( t('Registration request at %s'), get_config('system','sitename')),
			$email_msg,
			'From: ' . t('Administrator') . '@' . get_app()->get_hostname() . "\n"
			. 'Content-type: text/plain; charset=UTF-8' . "\n"
			. 'Content-transfer-encoding: 8bit' 
		);

		if($res)
			$delivered ++;
		else
			logger('send_reg_approval_email: failed to ' . $admin['email'] . 'account_id: ' . $arr['account']['account_id']);

		pop_lang();
	}

	return($delivered ? true : false);
}

function send_verification_email($email,$password) {

	$email_msg = replace_macros(get_intltext_template('register_open_eml.tpl'), array(
		'$sitename' => get_config('config','sitename'),
		'$siteurl' =>  z_root(),
		'$email'    => $email,
		'$password' => t('your registration password'),
	));

	$res = mail($email, sprintf( t('Registration details for %s'), get_config('system','sitename')),
		$email_msg, 
		'From: ' . t('Administrator') . '@' . get_app()->get_hostname() . "\n"
		. 'Content-type: text/plain; charset=UTF-8' . "\n"
		. 'Content-transfer-encoding: 8bit' 
	);
	return($res ? true : false);
}


function user_allow($hash) {

	$a = get_app();

	$ret = array('success' => false);

	$register = q("SELECT * FROM `register` WHERE `hash` = '%s' LIMIT 1",
		dbesc($hash)
	);

	if(! $register)
		return $ret;

	$account = q("SELECT * FROM account WHERE account_id = %d LIMIT 1",
		intval($register[0]['uid'])
	);
	
	if(! $account)
		return $ret;

	$r = q("DELETE FROM register WHERE hash = '%s' LIMIT 1",
		dbesc($register[0]['hash'])
	);

	$r = q("update account set account_flags = (account_flags ^ %d) where (account_flags & %d) and account_id = %d limit 1",
		intval(ACCOUNT_BLOCKED),
		intval(ACCOUNT_BLOCKED),
		intval($register[0]['uid'])
	);
	$r = q("update account set account_flags = (account_flags ^ %d) where (account_flags & %d) and account_id = %d limit 1",
		intval(ACCOUNT_PENDING),
		intval(ACCOUNT_PENDING),
		intval($register[0]['uid'])
	);
	
	push_lang($register[0]['language']);

	$email_tpl = get_intltext_template("register_open_eml.tpl");
	$email_tpl = replace_macros($email_tpl, array(
			'$sitename' => get_config('system','sitename'),
			'$siteurl' =>  z_root(),
			'$username' => $account[0]['account_email'],
			'$email' => $account[0]['account_email'],
			'$password' => '',
			'$uid' => $account[0]['account_id']
	));

	$res = mail($account[0]['account_email'], sprintf( t('Registration details for %s'), get_config('system','sitename')),
		$email_tpl,
			'From: ' . t('Administrator') . '@' . $_SERVER['SERVER_NAME'] . "\n"
			. 'Content-type: text/plain; charset=UTF-8' . "\n"
			. 'Content-transfer-encoding: 8bit' );

	pop_lang();

	if($res) {
		info( t('Account approved.') . EOL );
		return true;
	}	

}


// This does not have to go through user_remove() and save the nickname
// permanently against re-registration, as the person was not yet
// allowed to have friends on this system

function user_deny($hash) {

	$register = q("SELECT * FROM register WHERE hash = '%s' LIMIT 1",
		dbesc($hash)
	);

	if(! count($register))
		return false;

	$user = q("SELECT account_id FROM account WHERE account_id = %d LIMIT 1",
		intval($register[0]['uid'])
	);
	
	if(! $user)
		return false;

	$r = q("DELETE FROM account WHERE account_id = %d LIMIT 1",
		intval($register[0]['uid'])
	);

	$r = q("DELETE FROM `register` WHERE id = %d LIMIT 1",
		dbesc($register[0]['id'])
	);
	notice( sprintf(t('Registration revoked for %s'), $account[0]['account_email']) . EOL);
	return true;
	
}
