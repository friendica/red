<?php

if(! function_exists('dfrn_request_init')) {
function dfrn_request_init(&$a) {

	if($_SESSION['authenticated']) {
		// choose which page to show (could be remote auth)

	}

	if($a->argc > 1)
		$which = $a->argv[1];

	require_once('mod/profile.php');
	profile_init($a,$which);

	return;
}}


if(! function_exists('dfrn_request_post')) {
function dfrn_request_post(&$a) {

	if(($a->argc != 2) || (! count($a->profile)))
		return;


	if($_POST['cancel']) {
		goaway($a->get_baseurl());
	} 

	// callback to local site after remote request and local confirm

	if((x($_POST,'localconfirm')) && ($_POST['localconfirm'] == 1) 
		&& (x($_SESSION,'authenticated')) && (x($_SESSION,'uid'))
		&& ($_SESSION['uid'] == $a->argv[1]) && (x($_POST,'dfrn_url'))) {

		$dfrn_url = notags(trim($_POST['dfrn_url']));
		$aes_allow = (((x($_POST,'aes_allow')) && ($_POST['aes_allow'] == 1)) ? 1 : 0);
		$confirm_key = ((x($_POST,'confirm_key')) ? $_POST['confirm_key'] : "");
		$failed = false;

		require_once('Scrape.php');

		if(x($dfrn_url)) {

			$parms = scrape_dfrn($dfrn_url);

			if(! count($parms)) {
				$_SESSION['sysmsg'] .= 'URL is not valid or does not contain profile information.' . EOL ;
				$failed = true;
			}
			else {
				if(! x($parms,'fn'))
					$_SESSION['sysmsg'] .= 'Warning: DFRN profile has no identifiable owner name.' . EOL ;
				if(! x($parms,'photo'))
					$_SESSION['sysmsg'] .= 'Warning: DFRN profile has no profile photo.' . EOL ;
				$invalid = validate_dfrn($parms);		
				if($invalid) {
					echo $invalid . ' required DFRN parameter' 
						. (($invalid == 1) ? " was " : "s were " )
						. "not found at the given URL" . '<br />';

					$failed = true;
				}
			}
		}
		if(! $failed) {

			$dfrn_request = $parms['dfrn-request'];
			/////////////////////////
			dbesc_array($parms);
			////////////////////////

			$r = q("INSERT INTO `contact` ( `uid`, `created`,`url`, `name`, `photo`, `site-pubkey`,
				`request`, `confirm`, `notify`, `poll`, `aes_allow`) 
				VALUES ( %d, '%s', '%s', '%s' , '%s', '%s', '%s', '%s', '%s', '%s', %d)",
				intval($_SESSION['uid']),
				datetime_convert(),
				dbesc($dfrn_url),
				$parms['fn'],
				$parms['photo'],
				$parms['key'],
				$parms['dfrn-request'],
				$parms['dfrn-confirm'],
				$parms['dfrn-notify'],
				$parms['dfrn-poll'],
				intval($aes_allow)
			);
			if($r === false)
				$_SESSION['sysmsg'] .= "Failed to create contact." . EOL;
			else
				$_SESSION['sysmsg'] .= "Introduction complete.";

			// Allow the blocked remote notification to complete

			if(strlen($dfrn_request) && strlen($confirm_key))
				$s = fetch_url($dfrn_request . '?confirm_key=' . $confirm_key);

			goaway($dfrn_url);
		}
	}


	// we are operating as a remote site and an introduction was requested of us.
	// Scrape the originating DFRN-URL for everything we need. Create a contact record
	// and an introduction to show our user next time he/she logs in.
	// Finally redirect back to the originator so that their site can record the request.
	// If our user confirms the request, a record of it will need to exist on the 
	// originator's site in order for the confirmation process to complete.. 

	if($a->profile['nickname'])
		$tailname = $a->profile['nickname'];
	else
		$tailname = $a->profile['uid'];

	$uid = $a->profile['uid'];

	$failed = false;

	require_once('Scrape.php');

	if( x($_POST,'dfrn_url')) {

		$url = trim($_POST['dfrn_url']);
		if(x($url)) {
			$parms = scrape_dfrn($url);

			if(! count($parms)) {
				$_SESSION['sysmsg'] .= 'URL is not valid or does not contain profile information.' . EOL ;
				$failed = true;
			}
			else {
				if(! x($parms,'fn'))
					$_SESSION['sysmsg'] .= 'Warning: DFRN profile has no identifiable owner name.' . EOL ;
				if(! x($parms,'photo'))
					$_SESSION['sysmsg'] .= 'Warning: DFRN profile has no profile photo.' . EOL ;
				$invalid = validate_dfrn($parms);		
				if($invalid) {
					echo $invalid . ' required DFRN parameter' 
						. (($invalid == 1) ? " was " : "s were " )
						. "not found at the given URL" . '<br />';

					$failed = true;
				}
			}
		}

		$ret = q("SELECT `url` FROM `contact` WHERE `url` = '%s'", dbesc($url));
		if($ret !== false && count($ret)) {
			$_SESSION['sysmsg'] .= 'You have already introduced yourself here.' . EOL;
			$failed = true;
		}


		if(! $failed) {

			$parms['url'] = $url;
			$parms['issued-id'] = random_string();

			/////////////////////////
			dbesc_array($parms);
			////////////////////////

		$ret = q("INSERT INTO `contact` ( `uid`, `created`, `url`, `name`, `issued-id`, `photo`, `site-pubkey`,
			`request`, `confirm`, `notify`, `poll`, `visible` )
			VALUES ( %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d )",
			intval($uid),
			datetime_convert(),
			$parms['url'],
			$parms['fn'],
			$parms['issued-id'],
			$parms['photo'],
			$parms['key'],
			$parms['dfrn-request'],
			$parms['dfrn-confirm'],
			$parms['dfrn-notify'],
			$parms['dfrn-poll'],
			((x($_POST,'visible')) ? 1 : 0 )
			);
	
		}
		if($ret === false) {
			$_SESSION['sysmsg'] .= 'Failed to create contact record.' . EOL;
			return;
		}

		$ret = q("SELECT `id` FROM `contact` 
			WHERE `uid` = '%s' AND `url` = '%s' AND `issued-id` = '%s' 
			LIMIT 1",
			intval($uid),
			$parms['url'],
			$parms['issued-id']
			);

		if(($ret !== NULL)  && (count($ret)))
			$contact_id = $ret[0]['id'];

		$hash = random_string() . (string) time();   // Generate a confirm_key

		if($contact_id) {
			$ret = q("INSERT INTO `intro` ( `uid`, `contact-id`, `blocked`, `knowyou`, `note`, `hash`, `datetime`)
			VALUES ( %d, %d, 1, %d, '%s', '%s', '%s' )",
			intval($uid),
			intval($contact_id),
			((x($_POST,'knowyou') && ($_POST['knowyou'] == 1)) ? 1 : 0),
			dbesc(trim($_POST['dfrn-request-message'])),
			dbesc($hash),
			dbesc(datetime_convert())
			);
		}
		
	
		// TODO: send an email notification if our user wants one

		if(! $failed) 
			$_SESSION['sysmsg'] .= "Your introduction has been sent." . EOL;

		// "Homecoming" - send the requestor back to their site to record the introduction.

		$dfrn_url = bin2hex($a->get_baseurl() . "/profile/$tailname");
		$aes_allow = ((function_exists('openssl_encrypt')) ? 1 : 0);

		goaway($parms['dfrn-request'] . "?dfrn_url=$dfrn_url" . '&confirm_key=' . $hash . (($aes_allow) ? "&aes_allow=1" : ""));

	}

}}

if(! function_exists('dfrn_request_content')) {
function dfrn_request_content(&$a) {

	

	if(($a->argc != 2) || (! count($a->profile)))
		return "";

	$a->page['template'] = 'profile';

	// "Homecoming". Make sure we're logged in to this site as the correct user. Then offer a confirm button
	// to send us to the post section to record the introduction.

	if(x($_GET,'dfrn_url')) {

		if(! x($_SESSION,'authenticated')) {
			$_SESSION['sysmsg'] .= "Please login to confirm introduction." . EOL;
			return login();
		}

		// Edge case, but can easily happen in the wild. This person is authenticated, 
		// but not as the person who needs to deal with this request.

		if (($_SESSION['uid'] != $a->argv[1]) && ($a->user['nickname'] != $a->argv[1])) {
			$_SESSION['sysmsg'] .= "Incorrect identity currently logged in. Please login to <strong>this</strong> profile." . EOL;
			return login();
		}

		$dfrn_url = notags(trim(pack("H*" , $_GET['dfrn_url'])));
		$aes_allow = (((x($_GET,'aes_allow')) && ($_GET['aes_allow'] == 1)) ? 1 : 0);
		$confirm_key = (x($_GET,'confirm_key') ? $_GET['confirm_key'] : "");
		$o .= file_get_contents("view/dfrn_req_confirm.tpl");
		$o  = replace_macros($o,array(
			'$dfrn_url' => $dfrn_url,
			'$aes_allow' => (($aes_allow) ? '<input type="hidden" name="aes_allow" value="1" />' : "" ),
			'$confirm_key' => $confirm_key,
			'$username' => $a->user['username'], 
			'$uid' => $_SESSION['uid'],
			'dfrn_rawurl' => $_GET['dfrn_url']
			));
		return $o;

	}
	else {
		// safe to send our user their introduction
		if((x($_GET,'confirm_key')) && strlen($_GET['confirm_key'])) {
			$r = q("UPDATE `intro` SET `blocked` = 0 WHERE `hash` = '%s' LIMIT 1",
				dbesc($_GET['confirm_key'])
			);
			return;
		}


	// Outside request. Display our user's introduction form. 


	$o = file_get_contents("view/dfrn_request.tpl");
	$o = replace_macros($o,array('$uid' => $a->profile['uid']));
	return $o;
	}
}}