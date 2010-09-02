<?php

if(! function_exists('dfrn_request_init')) {
function dfrn_request_init(&$a) {

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


	// We've introduced ourself to another cell, then have been returned to our own cell
	// to confirm the request, and then we've clicked submit (perhaps after logging in). 
	// That brings us here:

	if((x($_POST,'localconfirm')) && ($_POST['localconfirm'] == 1)) {

		// Ensure this is a valid request
 
		if(local_user() && ($a->user['nickname'] == $a->argv[1]) && (x($_POST,'dfrn_url'))) {


			$dfrn_url = notags(trim($_POST['dfrn_url']));
			$aes_allow = (((x($_POST,'aes_allow')) && ($_POST['aes_allow'] == 1)) ? 1 : 0);
			$confirm_key = ((x($_POST,'confirm_key')) ? $_POST['confirm_key'] : "");

			$contact_record = null;
	
			if(x($dfrn_url)) {
	
				$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `url` = '%s' LIMIT 1",
					intval($_SESSION['uid']),
					dbesc($dfrn_url)
				);
	
				if(count($r)) {
					if(strlen($r[0]['dfrn-id'])) {
						notice( t("This introduction has already been accepted.") . EOL );
						return;
					}
					else
						$contact_record = $r[0];
				}
	
				if(is_array($contact_record)) {
					$r = q("UPDATE `contact` SET `ret-aes` = %d WHERE `id` = %d LIMIT 1",
						intval($aes_allow),
						intval($contact_record['id'])
					);
				}
				else {
	
					require_once('Scrape.php');
	
	
					$parms = scrape_dfrn($dfrn_url);
	
					if(! count($parms)) {
						notice( t('Profile location is not valid or does not contain profile information.') . EOL );
						return;
					}
					else {
						if(! x($parms,'fn'))
							notice( t('Warning: profile location has no identifiable owner name.') . EOL );
						if(! x($parms,'photo'))
							notice( t('Warning: profile location has no profile photo.') . EOL );
						$invalid = validate_dfrn($parms);		
						if($invalid) {
							notice( $invalid . t(' required parameter') 
								. (($invalid == 1) ? t(" was ") : t("s were ") )
								. t("not found at the given location.") . EOL ) ;
							return;
						}
					}



					$dfrn_request = $parms['dfrn-request'];

					dbesc_array($parms);


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
				}

				if($r) {
					notice( t("Introduction complete.") . EOL);
				}

				// Allow the blocked remote notification to complete

				if(is_array($contact_record))
					$dfrn_request = $contact_record['request'];

				if(strlen($dfrn_request) && strlen($confirm_key))
					$s = fetch_url($dfrn_request . '?confirm_key=' . $confirm_key);
					// ignore reply
				goaway($dfrn_url);
				return; // NOTREACHED

			}

		}

 		// invalid/bogus request

		notice( t("Unrecoverable protocol error.") . EOL );
		goaway($a->get_baseurl());
		return; // NOTREACHED
	}

	// Otherwise:

	// We are the requestee. A person from a remote cell has made an introduction 
	// on our profile web page and clicked submit. We will use their DFRN-URL to 
	// figure out how to contact their cell.  

	// Scrape the originating DFRN-URL for everything we need. Create a contact record
	// and an introduction to show our user next time he/she logs in.
	// Finally redirect back to the requestor so that their site can record the request.
	// If our user (the requestee) later confirms this request, a record of it will need 
	// to exist on the requestor's cell in order for the confirmation process to complete.. 

	// It's possible that neither the requestor or the requestee are logged in at the moment,
	// and the requestor does not yet have any credentials to the requestee profile.

	// Who is the requestee? We've already loaded their profile which means their nickname should be
	// in $a->argv[1] and we should have their complete info in $a->profile.

	if(! (is_array($a->profile) && count($a->profile))) {
		notice( t('Profile unavailable.') . EOL);
		return;
	}

	$nickname = $a->profile['nickname'];
	$notify_flags = $a->profile['notify-flags'];
	$uid = $a->profile['uid'];

	$contact_record = null;
	$failed = false;
	$parms = null;


	if( x($_POST,'dfrn_url')) {

		$url = trim($_POST['dfrn_url']);
		if(! strlen($url)) {
			notice( t("Invalid locator") . EOL );
			return;
		}

		// Canonicalise email-style profile locator

		$url = webfinger($url);

		if(! strlen($url)) {
			notice( t("Unable to resolve your name at the provided location.") . EOL);			
			return;
		}

		$ret = q("SELECT * FROM `contact` WHERE `uid` = %d AND `url` = '%s' LIMIT 1", 
			intval($uid),
			dbesc($url)
		);

		if(count($ret)) {
			if(strlen($ret[0]['issued-id'])) {
				notice( t('You have already introduced yourself here.') . EOL );
				return;
			}
			else {
				$contact_record = $ret[0];
				$parms = array('dfrn-request' => $ret[0]['request']);
			}
		}
		$issued_id = random_string();

		if(is_array($contact_record)) {
			// There is a contact record but no issued-id, so this
			// is a reciprocal introduction from a known contact
			$r = q("UPDATE `contact` SET `issued-id` = '%s' WHERE `id` = %d LIMIT 1",
				dbesc($issued_id),
				intval($contact_record['id'])
			);
		}
		else {
	
			require_once('Scrape.php');

			$parms = scrape_dfrn($url);

			if(! count($parms)) {
				notice( t('Profile location is not valid or does not contain profile information.') . EOL );
				killme();
			}
			else {
				if(! x($parms,'fn'))
					notice( t('Warning: profile location has no identifiable owner name.') . EOL );
				if(! x($parms,'photo'))
					notice( t('Warning: profile location has no profile photo.') . EOL );
				$invalid = validate_dfrn($parms);		
				if($invalid) {
					notice( $invalid . t(' required parameter') 
						. (($invalid == 1) ? t(" was ") : t("s were ") )
						. t("not found at the given location.") . EOL ) ;

					return;
				}
			}


			$parms['url'] = $url;
			$parms['issued-id'] = $issued_id;


			dbesc_array($parms);
			$r = q("INSERT INTO `contact` ( `uid`, `created`, `url`, `name`, `issued-id`, `photo`, `site-pubkey`,
				`request`, `confirm`, `notify`, `poll` )
				VALUES ( %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )",
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
				$parms['dfrn-poll']
			);

			// find the contact record we just created
			if($r) {	
				$r = q("SELECT `id` FROM `contact` 
					WHERE `uid` = %d AND `url` = '%s' AND `issued-id` = '%s' LIMIT 1",
					intval($uid),
					$parms['url'],
					$parms['issued-id']
				);
				if(count($r)) 
					$contact_record = $r[0];
			}
	
		}
		if($r === false) {
			notice( 'Failed to update contact record.' . EOL );
			return;
		}

		$hash = random_string() . (string) time();   // Generate a confirm_key

		if(is_array($contact_record)) {
			$ret = q("INSERT INTO `intro` ( `uid`, `contact-id`, `blocked`, `knowyou`, `note`, `hash`, `datetime`)
				VALUES ( %d, %d, 1, %d, '%s', '%s', '%s' )",
				intval($uid),
				intval($contact_record['id']),
				((x($_POST,'knowyou') && ($_POST['knowyou'] == 1)) ? 1 : 0),
				dbesc(notags(trim($_POST['dfrn-request-message']))),
				dbesc($hash),
				dbesc(datetime_convert())
			);
		}
	

		// This notice will only be seen by the requestor if  the requestor and requestee are on the same server.

		if(! $failed) 
			notice( t("Your introduction has been sent.") . EOL );

		// "Homecoming" - send the requestor back to their site to record the introduction.

		$dfrn_url = bin2hex($a->get_baseurl() . "/profile/$nickname");
		$aes_allow = ((function_exists('openssl_encrypt')) ? 1 : 0);

		goaway($parms['dfrn-request'] . "?dfrn_url=$dfrn_url" . '&confirm_key=' . $hash . (($aes_allow) ? "&aes_allow=1" : ""));
		return; // NOTREACHED

	}
	return;
}}




if(! function_exists('dfrn_request_content')) {
function dfrn_request_content(&$a) {

	

	if(($a->argc != 2) || (! count($a->profile)))
		return "";

	$a->page['template'] = 'profile';

	// "Homecoming". Make sure we're logged in to this site as the correct user. Then offer a confirm button
	// to send us to the post section to record the introduction.

	if(x($_GET,'dfrn_url')) {

		if(! local_user()) {
			notice( t("Please login to confirm introduction.") . EOL );
			return login();
		}

		// Edge case, but can easily happen in the wild. This person is authenticated, 
		// but not as the person who needs to deal with this request.

		if ($a->user['nickname'] != $a->argv[1]) {
			notice( t("Incorrect identity currently logged in. Please login to <strong>this</strong> profile.") . EOL);
			return login();
		}

		$dfrn_url = notags(trim(hex2bin($_GET['dfrn_url'])));
		$aes_allow = (((x($_GET,'aes_allow')) && ($_GET['aes_allow'] == 1)) ? 1 : 0);
		$confirm_key = (x($_GET,'confirm_key') ? $_GET['confirm_key'] : "");
		$o .= file_get_contents("view/dfrn_req_confirm.tpl");
		$o  = replace_macros($o,array(
			'$dfrn_url' => $dfrn_url,
			'$aes_allow' => (($aes_allow) ? '<input type="hidden" name="aes_allow" value="1" />' : "" ),
			'$confirm_key' => $confirm_key,
			'$username' => $a->user['username'], 
			'$uid' => $_SESSION['uid'],
			'$nickname' => $a->user['nickname'],
			'dfrn_rawurl' => $_GET['dfrn_url']
			));
		return $o;

	}
	elseif((x($_GET,'confirm_key')) && strlen($_GET['confirm_key'])) { 

		// we are the requestee and it is now safe to send our user their introduction,
		// We could just unblock it, but first we have to jump through a few hoops to 
		// send an email, or even to find out if we need to send an email. 

		$intro = q("SELECT * FROM `intro` WHERE `hash` = '%s' LIMIT 1",
			dbesc($_GET['confirm_key'])
		);

		if(count($intro)) {

			$r = q("SELECT `contact`.*, `user`.* FROM `contact` LEFT JOIN `user` ON `contact`.`uid` = `user`.`uid`
				WHERE `contact`.`id` = %d LIMIT 1",
				intval($intro[0]['contact-id'])
			);
			if(count($r)) {

				if($r[0]['notify-flags'] & NOTIFY_INTRO) {
					$email_tpl = file_get_contents('view/request_notify_eml.tpl');
					$email = replace_macros($email_tpl, array(
						'$requestor' => ((strlen(stripslashes($r[0]['name']))) ? stripslashes($r[0]['name']) : t('[Name Withheld]')),
						'$url' => stripslashes($r[0]['url']),
						'$myname' => $r[0]['username'],
						'$siteurl' => $a->get_baseurl(),
						'$sitename' => $a->config['sitename']
					));
					$res = mail($r[0]['email'], 
						t("Introduction received at ") . $a->config['sitename'],
						$email,
						t('From: Administrator@') . $_SERVER[SERVER_NAME] );
					// This is a redundant notification - no point throwing errors if it fails.
				}
			}

			$r = q("UPDATE `intro` SET `blocked` = 0 WHERE `hash` = '%s' LIMIT 1",
				dbesc($_GET['confirm_key'])
			);

		}
		killme();
		return; // NOTREACHED
	}
	else {

		// Normal web request. Display our user's introduction form. 

		$o = file_get_contents("view/dfrn_request.tpl");
		$o = replace_macros($o,array('$nickname' => $a->argv[1]));
		return $o;
	}

	return; // Somebody is fishing.
}}