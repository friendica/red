<?php /** @file */

require_once('include/config.php');
require_once('include/network.php');
require_once('include/plugin.php');
require_once('include/text.php');
require_once('include/language.php');
require_once('include/datetime.php');
require_once('include/crypto.php');


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
		if($r) {
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
	$admin_email = trim(get_config('system','admin_email'));
	if(strlen($admin_email) && $admin_email === trim($arr['email']))
		return true;
	return false;
}

function account_total() {
	$r = q("select account_id from account where true");
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
	$expires     = ((x($arr,'expires'))       ? intval($arr['expires'])            : NULL_DATE);
	
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
	if(($c === 0) && (check_account_admin($arr)))
		$roles |= ACCOUNT_ROLE_ADMIN;

    // Ensure that there is a host keypair.

    if((! get_config('system','pubkey')) && (! get_config('system','prvkey'))) {
        $hostkey = new_keypair(4096);
        set_config('system','pubkey',$hostkey['pubkey']);
        set_config('system','prvkey',$hostkey['prvkey']);
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
		$r = q("update account set account_parent = %d where account_id = %d",
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



function verify_email_address($arr) {

	$hash = random_string();

	$r = q("INSERT INTO register ( hash, created, uid, password, language ) VALUES ( '%s', '%s', %d, '%s', '%s' ) ",
		dbesc($hash),
		dbesc(datetime_convert()),
		intval($arr['account']['account_id']),
		dbesc('verify'),
		dbesc($arr['account']['account_language'])
	);

	$email_msg = replace_macros(get_intltext_template('register_verify_member.tpl'), array(
		'$sitename' => get_config('system','sitename'),
		'$siteurl'  =>  z_root(),
		'$email'    => $arr['email'],
		'$uid'      => $arr['account']['account_id'],
		'$hash'     => $hash,
		'$details'  => $details
	 ));

	$res = mail($arr['email'], email_header_encode(sprintf( t('Registration confirmation for %s'), get_config('system','sitename'))),
		$email_msg,
		'From: ' . 'Administrator' . '@' . get_app()->get_hostname() . "\n"
		. 'Content-type: text/plain; charset=UTF-8' . "\n"
		. 'Content-transfer-encoding: 8bit' 
	);

	if($res)
		$delivered ++;
	else
		logger('send_reg_approval_email: failed to ' . $admin['email'] . 'account_id: ' . $arr['account']['account_id']);

	return $res;

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
		'$sitename' => get_config('system','sitename'),
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

	$r = q("DELETE FROM register WHERE hash = '%s'",
		dbesc($register[0]['hash'])
	);

	$r = q("update account set account_flags = (account_flags & ~%d) where (account_flags & %d)>0 and account_id = %d",
		intval(ACCOUNT_BLOCKED),
		intval(ACCOUNT_BLOCKED),
		intval($register[0]['uid'])
	);
	$r = q("update account set account_flags = (account_flags & ~%d) where (account_flags & %d)>0 and account_id = %d",
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

	$account = q("SELECT account_id, account_email FROM account WHERE account_id = %d LIMIT 1",
		intval($register[0]['uid'])
	);
	
	if(! $account)
		return false;

	$r = q("DELETE FROM account WHERE account_id = %d",
		intval($register[0]['uid'])
	);

	$r = q("DELETE FROM `register` WHERE id = %d",
		dbesc($register[0]['id'])
	);
	notice( sprintf(t('Registration revoked for %s'), $account[0]['account_email']) . EOL);
	return true;
	
}


function user_approve($hash) {

	$a = get_app();

	$ret = array('success' => false);

	$register = q("SELECT * FROM `register` WHERE `hash` = '%s' and password = 'verify' LIMIT 1",
		dbesc($hash)
	);

	if(! $register)
		return $ret;

	$account = q("SELECT * FROM account WHERE account_id = %d LIMIT 1",
		intval($register[0]['uid'])
	);
	
	if(! $account)
		return $ret;

	$r = q("DELETE FROM register WHERE hash = '%s' and password = 'verify'",
		dbesc($register[0]['hash'])
	);

	$r = q("update account set account_flags = (account_flags & ~%d) where (account_flags & %d)>0 and account_id = %d",
		intval(ACCOUNT_BLOCKED),
		intval(ACCOUNT_BLOCKED),
		intval($register[0]['uid'])
	);
	$r = q("update account set account_flags = (account_flags & ~%d) where (account_flags & %d)>0 and account_id = %d",
		intval(ACCOUNT_PENDING),
		intval(ACCOUNT_PENDING),
		intval($register[0]['uid'])
	);
	$r = q("update account set account_flags = (account_flags & ~%d) where (account_flags & %d)>0 and account_id = %d",
		intval(ACCOUNT_UNVERIFIED),
		intval(ACCOUNT_UNVERIFIED),
		intval($register[0]['uid'])
	);
	
	info( t('Account verified. Please login.') . EOL );

	return true;

}






/**
 * @function downgrade_accounts()
 *    Checks for accounts that have past their expiration date.
 * If the account has a service class which is not the site default, 
 * the service class is reset to the site default and expiration reset to never.
 * If the account has no service class it is expired and subsequently disabled.
 * called from include/poller.php as a scheduled task.
 *
 * Reclaiming resources which are no longer within the service class limits is
 * not the job of this function, but this can be implemented by plugin if desired. 
 * Default behaviour is to stop allowing additional resources to be consumed. 
 */
 

function downgrade_accounts() {

	$r = q("select * from account where not ( account_flags & %d )>0 
		and account_expires != '%s' 
		and account_expires < %s ",
		intval(ACCOUNT_EXPIRED),
		dbesc(NULL_DATE),
		db_getfunc('UTC_TIMESTAMP')
	);

	if(! $r)
		return;

	$basic = get_config('system','default_service_class');


	foreach($r as $rr) {

		if(($basic) && ($rr['account_service_class']) && ($rr['account_service_class'] != $basic)) {
			$x = q("UPDATE account set account_service_class = '%s', account_expires = '%s'
				where account_id = %d",
				dbesc($basic),
				dbesc(NULL_DATE),
				intval($rr['account_id'])
			);
			$ret = array('account' => $rr);
			call_hooks('account_downgrade', $ret );
			logger('downgrade_accounts: Account id ' . $rr['account_id'] . ' downgraded.');
		}
		else {
			$x = q("UPDATE account SET account_flags = (account_flags | %d) where account_id = %d",
				intval(ACCOUNT_EXPIRED),
				intval($rr['account_id'])
			);
			$ret = array('account' => $rr);
			call_hooks('account_downgrade', $ret);
			logger('downgrade_accounts: Account id ' . $rr['account_id'] . ' expired.');
		}
	}
}



// check service_class restrictions. If there are no service_classes defined, everything is allowed.
// if $usage is supplied, we check against a maximum count and return true if the current usage is 
// less than the subscriber plan allows. Otherwise we return boolean true or false if the property
// is allowed (or not) in this subscriber plan. An unset property for this service plan means 
// the property is allowed, so it is only necessary to provide negative properties for each plan, 
// or what the subscriber is not allowed to do. 


function service_class_allows($uid,$property,$usage = false) {
	$a = get_app();
	if($uid == local_channel()) {
		$service_class = $a->account['account_service_class'];
	}
	else {
		$r = q("select account_service_class as service_class 
				from channel c, account a 
				where c.channel_account_id=a.account_id and c.channel_id= %d limit 1",
			intval($uid)
		);
		if($r !== false and count($r)) {
			$service_class = $r[0]['service_class'];
		}
	}
	if(! x($service_class))
		return true; // everything is allowed

	$arr = get_config('service_class',$service_class);
	if(! is_array($arr) || (! count($arr)))
		return true;

	if($usage === false)
		return ((x($arr[$property])) ? (bool) $arr[$property] : true);
	else {
		if(! array_key_exists($property,$arr))
			return true;
		return (((intval($usage)) < intval($arr[$property])) ? true : false);
	}
}

// like service_class_allows but queries by account rather than channel
function account_service_class_allows($aid,$property,$usage = false) {
	$a = get_app();
	$r = q("select account_service_class as service_class from account where account_id = %d limit 1",
		intval($aid)
	);
	if($r !== false and count($r)) {
		$service_class = $r[0]['service_class'];
	}

	if(! x($service_class))
		return true; // everything is allowed

	$arr = get_config('service_class',$service_class);
	if(! is_array($arr) || (! count($arr)))
		return true;

	if($usage === false)
		return ((x($arr[$property])) ? (bool) $arr[$property] : true);
	else {
		if(! array_key_exists($property,$arr))
			return true;
		return (((intval($usage)) < intval($arr[$property])) ? true : false);
	}
}


function service_class_fetch($uid,$property) {
	$a = get_app();
	if($uid == local_channel()) {
		$service_class = $a->account['account_service_class'];
	}
	else {
		$r = q("select account_service_class as service_class 
				from channel c, account a 
				where c.channel_account_id=a.account_id and c.channel_id= %d limit 1",
				intval($uid)
		);
		if($r !== false and count($r)) {
			$service_class = $r[0]['service_class'];
		}
	}
	if(! x($service_class))
		return false; // everything is allowed

	$arr = get_config('service_class',$service_class);

	if(! is_array($arr) || (! count($arr)))
		return false;

	return((array_key_exists($property,$arr)) ? $arr[$property] : false);
}

// like service_class_fetch but queries by account rather than channel

function account_service_class_fetch($aid,$property) {

	$r = q("select account_service_class as service_class from account where account_id = %d limit 1",
		intval($aid)
	);
	if($r !== false && count($r)) {
		$service_class = $r[0]['service_class'];
	}

	if(! x($service_class))
		return false; // everything is allowed

	$arr = get_config('service_class',$service_class);

	if(! is_array($arr) || (! count($arr)))
		return false;

	return((array_key_exists($property,$arr)) ? $arr[$property] : false);
}


function upgrade_link($bbcode = false) {
	$l = get_config('service_class','upgrade_link');
	if(! $l)
		return '';
	if($bbcode)
		$t = sprintf('[zrl=%s]' . t('Click here to upgrade.') . '[/zrl]', $l);
	else
		$t = sprintf('<a href="%s">' . t('Click here to upgrade.') . '</div>', $l);
	return $t;
}

function upgrade_message($bbcode = false) {
	$x = upgrade_link($bbcode);
	return t('This action exceeds the limits set by your subscription plan.') . (($x) ? ' ' . $x : '') ;
}

function upgrade_bool_message($bbcode = false) {
	$x = upgrade_link($bbcode);
	return t('This action is not available under your subscription plan.') . (($x) ? ' ' . $x : '') ;
}
