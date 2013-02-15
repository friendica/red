<?php

// Import a channel, either by direct file upload or via
// connection to original server. 


function import_post(&$a) {


	$data     = null;
	$seize    = ((x($_REQUEST,'make_primary')) ? intval($_REQUEST['make_primary']) : 0);

	$src      = $_FILES['userfile']['tmp_name'];
	$filename = basename($_FILES['userfile']['name']);
	$filesize = intval($_FILES['userfile']['size']);
	$filetype = $_FILES['userfile']['type'];


	if($src) {
		if($filesize) {
			$data = @file_get_contents($src);
		}
	}

	if(! $src) {
		$old_address = ((x($_REQUEST,'old_address')) ? $_REQUEST['old_address'] : '');
		if(! $old_address) {
			logger('mod_import: nothing to import.');
			notice( t('Nothing to import.') . EOL);
			return;
		}

		$email    = ((x($_REQUEST,'email'))    ? $_REQUEST['email']    : '');
		$password = ((x($_REQUEST,'password')) ? $_REQUEST['password'] : '');

		$channelname = substr($old_address,0,strpos($old_address,'@'));
		$servername  = substr($old_address,strpos($old_address,'@')+1);

		$scheme = 'https://';
		$api_path = '/api/export/basic?f=&channel=' . $channelname;
		$binary = false;
		$redirects = 0;
		$opts = array('http_auth' => $email . ':' . $password);
		$url = $scheme . $servername . $api_path;
		$ret = z_fetch_url($url, $binary, $redirects, $opts);
		if(! $ret['success'])
			$ret = z_fetch_url('http://' . $servername . $api_path, $binary, $redirects, $opts);
		if($ret['success'])
			$data = $ret['body'];
		else
			notice( t('Unable to download data from old server') . EOL);

	}

	if(! $data) {
		logger('mod_import: empty file.');
		notice( t('Imported file is empty.') . EOL);
		return;
	}


//	logger('import: data: ' . print_r($data,true));

//	print_r($data);

	// import channel
	
	// import contacts





	if($seize) {
		// notify old server that it is no longer primary.
		
	}


	// send out refresh requests

}


function import_content(&$a) {

/*
 * Pass in a channel name and desired channel_address
 * Check this for validity and duplication
 * The page template should have a place to change it and check again
 */


$o .= <<< EOT

<form action="import" method="post" >
Old Address <input type="text" name="old_address" /><br />
Login <input type="text" name="email" /><br />
Password <input type="password" name="password" /><br />
<input type="submit" name="submit" value="submit" />
</form>

EOT;

return $o;
}