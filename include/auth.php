<?php /** @file */


require_once('include/security.php');

function nuke_session() {

	new_cookie(0);

	unset($_SESSION['authenticated']);
	unset($_SESSION['account_id']);
	unset($_SESSION['uid']);
	unset($_SESSION['visitor_id']);
	unset($_SESSION['administrator']);
	unset($_SESSION['cid']);
	unset($_SESSION['theme']);
	unset($_SESSION['mobile_theme']);
	unset($_SESSION['show_mobile']);
	unset($_SESSION['page_flags']);
	unset($_SESSION['submanage']);
	unset($_SESSION['my_url']);
	unset($_SESSION['my_address']);
	unset($_SESSION['addr']);
	unset($_SESSION['return_url']);
	unset($_SESSION['remote_service_class']);
	unset($_SESSION['remote_hub']);
}

/**
 * Verify login credentials
 *
 * Returns account record on success, null on failure
 *
 */

function account_verify_password($email,$pass) {

	$r = q("select * from account where account_email = '%s'",
		dbesc($email)
	);
	if(! ($r && count($r)))
		return null;
	foreach($r as $record) {
		if(($record['account_flags'] == ACCOUNT_OK) || ($record['account_flags'] == ACCOUNT_UNVERIFIED)
			&& (hash('whirlpool',$record['account_salt'] . $pass) === $record['account_password'])) {
			logger('password verified for ' . $email);
			return $record;
		}
	}
	$error = 'password failed for ' . $email;
	logger($error);
	// Also log failed logins to a separate auth log to reduce overhead for server side intrusion prevention
	$authlog = get_config('system', 'authlog');
	if ($authlog)
		@file_put_contents($authlog, datetime_convert() . ':' . session_id() . ' ' . $error . "\n", FILE_APPEND);

	return null;
}


// login/logout 






if((isset($_SESSION)) && (x($_SESSION,'authenticated')) && ((! (x($_POST,'auth-params'))) || ($_POST['auth-params'] !== 'login'))) {

	if(((x($_POST,'auth-params')) && ($_POST['auth-params'] === 'logout')) || ($a->module === 'logout')) {
	
		// process logout request
		$args = array('channel_id' => local_user());
		call_hooks('logging_out', $args);
		nuke_session();
		info( t('Logged out.') . EOL);
		goaway(z_root());
	}

	if(x($_SESSION,'visitor_id') && (! x($_SESSION,'uid'))) {
		// if our authenticated guest is allowed to take control of the admin channel, make it so.
		$admins = get_config('system','remote_admin');
		if($admins && is_array($admins) && in_array($_SESSION['visitor_id'],$admins)) {
			$x = q("select * from account where account_email = '%s' and account_email != '' and ( account_flags & %d ) limit 1",
				dbesc(get_config('system','admin_email')),
				intval(ACCOUNT_ROLE_ADMIN)
			);
			if($x) {
				new_cookie(60*60*24); // one day
				$_SESSION['last_login_date'] = datetime_convert();
				unset($_SESSION['visitor_id']); // no longer a visitor
				authenticate_success($x[0], true, true);
			}
		}

		$r = q("select * from xchan left join hubloc on xchan_hash = hubloc_hash where xchan_hash = '%s' limit 1",
			dbesc($_SESSION['visitor_id'])
		);
		if($r) {
			get_app()->set_observer($r[0]);
		}
		else {
			unset($_SESSION['visitor_id']);
			unset($_SESSION['authenticated']);
		}
		$a->set_groups(init_groups_visitor($_SESSION['visitor_id']));
	}

	if(x($_SESSION,'uid') || x($_SESSION,'account_id')) {

		// already logged in user returning

		$check = get_config('system','paranoia');
		// extra paranoia - if the IP changed, log them out
		if($check && ($_SESSION['addr'] != $_SERVER['REMOTE_ADDR'])) {
			logger('Session address changed. Paranoid setting in effect, blocking session. ' 
				. $_SESSION['addr'] . ' != ' . $_SERVER['REMOTE_ADDR']);
			nuke_session();
			goaway(z_root());
		}

		$r = q("select * from account where account_id = %d limit 1",
			intval($_SESSION['account_id'])
		);

		if(($r) && (($r[0]['account_flags'] == ACCOUNT_OK) || ($r[0]['account_flags'] == ACCOUNT_UNVERIFIED))) {
			get_app()->account = $r[0];
			$login_refresh = false;
			if(! x($_SESSION,'last_login_date')) {
				$_SESSION['last_login_date'] = datetime_convert('UTC','UTC');
			}
			if(strcmp(datetime_convert('UTC','UTC','now - 12 hours'), $_SESSION['last_login_date']) > 0 ) {
				$_SESSION['last_login_date'] = datetime_convert();
				$login_refresh = true;
        	}
        	authenticate_success($r[0], false, false, false, $login_refresh);
		}
		else {
			$_SESSION['account_id'] = 0;
			nuke_session();
			goaway(z_root());
		}

	}

}
else {

	if(isset($_SESSION)) {
		nuke_session();
	}

	if((x($_POST,'password')) && strlen($_POST['password']))
		$encrypted = hash('whirlpool',trim($_POST['password']));

	if((x($_POST,'auth-params')) && $_POST['auth-params'] === 'login') {

		$record = null;

		$addon_auth = array(
			'username' => trim($_POST['username']), 
			'password' => trim($_POST['password']),
			'authenticated' => 0,
			'user_record' => null
		);

		/**
		 *
		 * A plugin indicates successful login by setting 'authenticated' to non-zero value and returning a user record
		 * Plugins should never set 'authenticated' except to indicate success - as hooks may be chained
		 * and later plugins should not interfere with an earlier one that succeeded.
		 *
		 */

		call_hooks('authenticate', $addon_auth);

		if(($addon_auth['authenticated']) && (count($addon_auth['user_record']))) {
			$record = $addon_auth['user_record'];
		}
		else {

			$record = get_app()->account = account_verify_password($_POST['username'],$_POST['password']);

			if(get_app()->account) {
				$_SESSION['account_id'] = get_app()->account['account_id'];
			}
			else {
				notice( t('Failed authentication') . EOL);
			}

			logger('authenticate: ' . print_r(get_app()->account,true));

		}

		if((! $record) || (! count($record))) {
			$error = 'authenticate: failed login attempt: ' . notags(trim($_POST['username'])) . ' from IP ' . $_SERVER['REMOTE_ADDR'];
			logger($error); 
			// Also log failed logins to a separate auth log to reduce overhead for server side intrusion prevention
		        $authlog = get_config('system', 'authlog');
		        if ($authlog)
		        @file_put_contents($authlog, datetime_convert() . ':' . session_id() . ' ' . $error . "\n", FILE_APPEND);

			notice( t('Login failed.') . EOL );
			goaway(z_root());
  		}

		// If the user specified to remember the authentication, then change the cookie
		// to expire after one year (the default is when the browser is closed).
		// If the user did not specify to remember, change the cookie to expire when the
		// browser is closed. The reason this is necessary is because if the user
		// specifies to remember, then logs out and logs back in without specifying to
		// remember, the old "remember" cookie may remain and prevent the session from
		// expiring when the browser is closed.
		//
		// It seems like I should be able to test for the old cookie, but for some reason when
		// I read the lifetime value from session_get_cookie_params(), I always get '0'
		// (i.e. expire when the browser is closed), even when there's a time expiration
		// on the cookie

		if($_POST['remember']) {
			new_cookie(31449600); // one year
		}
		else {
			new_cookie(0); // 0 means delete on browser exit
		}

		// if we haven't failed up this point, log them in.

		$_SESSION['last_login_date'] = datetime_convert();
		authenticate_success($record, true, true);
	}
}


function match_openid($authid) {
	$r = q("select * from pconfig where cat = 'system' and k = 'openid' and v = '%s' limit 1",
		dbesc($authid)
	);
	if($r)
		return $r[0]['uid'];
	return false;
}					
