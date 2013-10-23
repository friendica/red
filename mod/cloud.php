<?php

	// This module is currently !!!HIGHLY EXPERIMENTAL!!!
	// You should think twice before running this on a production server
	// as security mechanisms are not yet implemented and those that
	// are implemented probably don't work.

	// DAV mounts will probably fail if you don't use SSL, because some platforms refuse to send
	// basic auth over non-encrypted connections.
	// One could use digest auth - but then one has to calculate the A1 digest and store it for
	// all acounts. We aren't doing that. We have a stored password already. We don't need another
	// one. The login unfortunately is the channel nickname (webbie) as we have no way of passing 
	// the destination channel to DAV. You should be able to login with your account credentials 
	// and be directed to your default channel. 

	// This interface does not yet support Red stored files. Consider any content in your "store" 
	// directory to be throw-away until advised otherwise.

	if(! get_config('system','enable_cloud'))
		killme();


	use Sabre\DAV;

    require_once('vendor/autoload.php');


	// workaround for HTTP-auth in CGI mode
	if(x($_SERVER,'REDIRECT_REMOTE_USER')) {
	 	$userpass = base64_decode(substr($_SERVER["REDIRECT_REMOTE_USER"],6)) ;
		if(strlen($userpass)) {
		 	list($name, $password) = explode(':', $userpass);
			$_SERVER['PHP_AUTH_USER'] = $name;
			$_SERVER['PHP_AUTH_PW'] = $password;
		}
	}

	if(x($_SERVER,'HTTP_AUTHORIZATION')) {
	 	$userpass = base64_decode(substr($_SERVER["HTTP_AUTHORIZATION"],6)) ;
		if(strlen($userpass)) {
		 	list($name, $password) = explode(':', $userpass);
			$_SERVER['PHP_AUTH_USER'] = $name;
			$_SERVER['PHP_AUTH_PW'] = $password;
		}
	}



class RedBasicAuth extends Sabre\DAV\Auth\Backend\AbstractBasic {

    protected function validateUserPass($username, $password) {
		require_once('include/auth.php');
		$record = account_verify_password($email,$pass);
		if($record && $record['account_default_channel']) {
			$r = q("select * from channel where channel_account_id = %d and channel_id = %d limit 1",
				intval($record['account_id']),
				intval($record['account_default_channel'])
			);
			if($r) {
				$this->currentUser = $r[0]['channel_address'];
				return true;
			}
		}
		$r = q("select channel_account_id from channel where channel_address = '%s' limit 1",
			dbesc($username)
		);
		if($r) {
			$x = q("select * from account where account_id = %d limit 1",
				intval($r[0]['channel_account_id'])
			);
			if($x) {
			    foreach($x as $record) {
			        if(($record['account_flags'] == ACCOUNT_OK) || ($record['account_flags'] == ACCOUNT_UNVERIFIED)
            		&& (hash('whirlpool',$record['account_salt'] . $password) === $record['account_password'])) {
			            logger('(DAV) RedBasicAuth: password verified for ' . $username);
            			return true;
        			}
    			}
			}
		}
	    logger('(DAV) RedBasicAuth: password failed for ' . $username);
    	return false;
	}
}


function cloud_init() {


	$rootDirectory = new DAV\FS\Directory('store');
	$server = new DAV\Server($rootDirectory);
	$lockBackend = new DAV\Locks\Backend\File('store/data/locks');
	$lockPlugin = new DAV\Locks\Plugin($lockBackend);

	$server->addPlugin($lockPlugin);

	$auth = new RedBasicAuth();

	$auth->Authenticate($server,'Red Matrix');


	// All we need to do now, is to fire up the server
	$server->exec();

	exit;

}