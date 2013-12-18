<?php

require_once('include/zot.php');
require_once('mod/magic.php');

function authtest_content(&$a) {


	$auth_success = false;
	$o .= '<h3>Magic-Auth Diagnostic</h3>';

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return $o;
	}

	$o .= '<form action="authtest" method="get">';
	$o .= 'Target URL: <input type="text" style="width: 250px;" name="dest" value="' . $_GET['dest'] .'" />';
	$o .= '<input type="submit" name="submit" value="Submit" /></form>'; 

	$o .= '<br /><br />';

	if(x($_GET,'dest')) {
		$_REQUEST['test'] = 1;
		$x = magic_init($a);
		$o .= 'Local Setup returns: ' . print_r($x,true);



		if($x['url']) {
			$z = z_fetch_url($x['url'] . '&test=1');
			if($z['success']) {
				$j = json_decode($z['body'],true);
				if(! $j)
					$o .= 'json_decode failure from remote site. ' . print_r($z['body'],true);
				$o .= 'Remote site responded: ' . print_r($j,true);
				if(strpos($j,'Authentication Success'))
					$auth_success = true;
			}
			else {
				$o .= 'fetch url failure.' . print_r($z,true);
			}
		}

		if(! $auth_success)
			$o .= 'Authentication Failed!' . EOL;
	}

	return str_replace("\n",'<br />',$o);
}
