<?php
/**
 * Name: LDAP Authenticate
 * Description: Authenticate a user against an LDAP directory
 * Version: 1.0
 * Author: Mike Macgirvin <http://macgirvin.com/profile/mike>
 */
 
/**
 * Friendika addon
 * 
 * Module: LDAP Authenticate
 *
 * Authenticate a user against an LDAP directory
 * Useful for Windows Active Directory and other LDAP-based organisations
 * to maintain a single password across the organisation.
 *
 * Optionally authenticates only if a member of a given group in the directory.
 *
 * The person must have registered with Friendika using the normal registration 
 * procedures in order to have a Friendika user record, contact, and profile.
 *
 * Note when using with Windows Active Directory: you may need to set TLS_CACERT in your site
 * ldap.conf file to the signing cert for your LDAP server. 
 * 
 * The required configuration options for this module may be set in the .htconfig.php file
 * e.g.:
 *
 * $a->config['ldapauth']['ldap_server'] = 'host.example.com';
 * ...etc.
 *
 */



function ldapauth_install() {
	register_hook('authenticate', 'addon/ldapauth/ldapauth.php', 'ldapauth_hook_authenticate');
}


function ldapauth_uninstall() {
	unregister_hook('authenticate', 'addon/ldapauth/ldapauth.php', 'ldapauth_hook_authenticate');
}


function ldapauth_hook_authenticate($a,&$b) {
	if(ldapauth_authenticate($b['username'],$b['password'])) {
		$results = q("SELECT * FROM `user` WHERE `nickname` = '%s' AND `blocked` = 0 AND `verified` = 1 LIMIT 1",
				dbesc($b['username'])
		);
		if(count($results)) {
				$b['user_record'] = $results[0];
				$b['authenticated'] = 1;
		}
	}
	return;
}


function ldapauth_authenticate($username,$password) {

    $ldap_server   = get_config('ldapauth','ldap_server');
    $ldap_binddn   = get_config('ldapauth','ldap_binddn');
    $ldap_bindpw   = get_config('ldapauth','ldap_bindpw');
    $ldap_searchdn = get_config('ldapauth','ldap_searchdn');
    $ldap_userattr = get_config('ldapauth','ldap_userattr');
    $ldap_group    = get_config('ldapauth','ldap_group');

    if(! ((strlen($password))
            && (function_exists('ldap_connect'))
            && (strlen($ldap_server))))
            return false;

    $connect = @ldap_connect($ldap_server);

    if(! $connect)
        return false;

    @ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION,3);
    @ldap_set_option($connect, LDAP_OPT_REFERRALS, 0);
    if((@ldap_bind($connect,$ldap_binddn,$ldap_bindpw)) === false) {
        return false;
    }

    $res = @ldap_search($connect,$ldap_searchdn, $ldap_userattr . '=' . $username);

    if(! $res) {
        return false;
    }

    $id = @ldap_first_entry($connect,$res);

    if(! $id) {
        return false;
    }

    $dn = @ldap_get_dn($connect,$id);

    if(! @ldap_bind($connect,$dn,$password))
        return false;

    if(! strlen($ldap_group))
        return true;

    $r = @ldap_compare($connect,$ldap_group,'member',$dn);
    if ($r === -1) {
        $err = @ldap_error($connect);
        $eno = @ldap_errno($connect);
        @ldap_close($connect);

        if ($eno === 32) {
            logger("ldapauth: access control group Does Not Exist");
            return false;
        }
        elseif ($eno === 16) {
            logger('ldapauth: membership attribute does not exist in access control group');
            return false;
        }
        else {
            logger('ldapauth: error: ' . $err);
            return false;
        }
    }
    elseif ($r === false) {
        @ldap_close($connect);
        return false;
    }

    return true;
}
