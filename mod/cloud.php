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

	if(! is_dir('store'))
		mkdir('store',STORAGE_DEFAULT_PERMISSIONS,false);

	$which = null;
	if(argc() > 1)
		$which = argv(1);

	$profile = 0;
	$channel = $a->get_channel();

	$a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . $a->get_baseurl() . '/feed/' . $which .'" />' . "\r\n" ;

	if($which)
		profile_load($a,$which,$profile);




	$auth = new RedBasicAuth();

	$ob_hash = get_observer_hash();

	if($ob_hash) {
		if(local_user()) {
			$channel = $a->get_channel();
			$auth->setCurrentUser($channel['channel_address']);
			$auth->channel_name = $channel['channel_address'];
			$auth->channel_id = $channel['channel_id'];
			$auth->channel_hash = $channel['channel_hash'];
			if($channel['channel_timezone'])
				$auth->timezone = $channel['channel_timezone'];
		}	
		$auth->observer = $ob_hash;
	}	


	$_SERVER['QUERY_STRING'] = str_replace(array('?f=','&f='),array('',''),$_SERVER['QUERY_STRING']);
	$_SERVER['QUERY_STRING'] = preg_replace('/[\?&]zid=(.*?)([\?&]|$)/ism','',$_SERVER['QUERY_STRING']);

	$_SERVER['REQUEST_URI'] = str_replace(array('?f=','&f='),array('',''),$_SERVER['REQUEST_URI']);
	$_SERVER['REQUEST_URI'] = preg_replace('/[\?&]zid=(.*?)([\?&]|$)/ism','',$_SERVER['REQUEST_URI']);

	$rootDirectory = new RedDirectory('/',$auth);
	$server = new DAV\Server($rootDirectory);
	$lockBackend = new DAV\Locks\Backend\File('store/[data]/locks');
	$lockPlugin = new DAV\Locks\Plugin($lockBackend);

	$server->addPlugin($lockPlugin);

	// The next section of code allows us to bypass prompting for http-auth if a FILE is being accessed anonymously and permissions
	// allow this. This way one can create hotlinks to public media files in their cloud and anonymous viewers won't get asked to login.
	// If a DIRECTORY is accessed or there are permission issues accessing the file and we aren't previously authenticated via zot, 
	// prompt for HTTP-auth. This will be the default case for mounting a DAV directory. 

	// FIXME - we may require one more hack here; to allow an unauthenticated guest to view your file collection (e.g. a DIRECTORY) from 
	// the web browser interface without prompting for password, but still requiring one for unauthenticated folks using DAV. We may be 
	// able to do this with a special $_GET request var and a cookie.  

	$isapublic_file = false;

	if((! $auth->observer) && ($_SERVER['REQUEST_METHOD'] === 'GET')) {
		try { 
			$x = RedFileData('/' . $a->cmd,$auth);
			if($x instanceof RedFile)
				$isapublic_file = true;
		}
		catch  ( Exception $e ) {
			$isapublic_file = false;
		}
	}

	if((! $auth->observer) && (! $isapublic_file)) {
		try {
			$auth->Authenticate($server, t('Red Matrix - Guests: Username: {your email address}, Password: +++'));
		}
		catch ( Exception $e) {
			logger('mod_cloud: auth exception' .  $e->getMessage());
			http_status_exit($e->getHTTPCode(),$e->getMessage());
		}
	}

//	$browser = new DAV\Browser\Plugin();

	$browser = new RedBrowser($auth);

	$auth->setBrowserPlugin($browser);


	$server->addPlugin($browser);


	// All we need to do now, is to fire up the server
	$server->exec();

	killme();
}