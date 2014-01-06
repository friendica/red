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

	public $channel_name = '';
	public $channel_id = 0;
	public $channel_hash = '';
	public $observer = '';

	public $owner_id;

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
				$this->channel_name = $r[0]['channel_address'];
				$this->channel_id = $r[0]['channel_id'];
				$this->channel_hash = $this->observer = $r[0]['channel_hash'];
				return true;
			}
		}
		$r = q("select * from channel where channel_address = '%s' limit 1",
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
						$this->currentUser = $r[0]['channel_address'];
						$this->channel_name = $r[0]['channel_address'];
						$this->channel_id = $r[0]['channel_id'];
						$this->channel_hash = $this->observer = $r[0]['channel_hash'];
            			return true;
        			}
    			}
			}
		}
	    logger('(DAV) RedBasicAuth: password failed for ' . $username);
    	return false;
	}

	function setCurrentUser($name) {
		$this->currentUser = $name;
	}


}


function cloud_init(&$a) {

	if(! get_config('system','enable_cloud'))
		killme();

	require_once('include/reddav.php');

	$auth = new RedBasicAuth();

	$ob_hash = get_observer_hash();

	if($ob_hash) {
		if(local_user()) {
			$channel = $a->get_channel();
			$auth->setCurrentUser($channel['channel_address']);
			$auth->channel_name = $channel['channel_address'];
			$auth->channel_id = $channel['channel_id'];
			$auth->channel_hash = $channel['channel_hash'];
		}	
		$auth->observer = $ob_hash;
	}	


	$rootDirectory = new RedDirectory('/',$auth);
	$server = new DAV\Server($rootDirectory);
	$lockBackend = new DAV\Locks\Backend\File('store/data/locks');
	$lockPlugin = new DAV\Locks\Plugin($lockBackend);

	$server->addPlugin($lockPlugin);


	if(! $auth->observer)
		$auth->Authenticate($server,'Red Matrix');

	$browser = new DAV\Browser\Plugin();
	$server->addPlugin($browser);


	// All we need to do now, is to fire up the server
	$server->exec();

	killme();
}