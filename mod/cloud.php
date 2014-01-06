<?php

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





function cloud_init(&$a) {

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

//	$browser = new DAV\Browser\Plugin();

	$browser = new RedBrowser($auth);

	$auth->setBrowserPlugin($browser);

	$server->addPlugin($browser);


	// All we need to do now, is to fire up the server
	$server->exec();

	killme();
}