<?php

// There are two possible entry points. Both are called via POST.

function dfrn_confirm_post(&$a) {

	if($a->argc > 1)
		$node = $a->argv[1];

		// Main entry point. Our user received a friend request notification (perhaps 
		// from another site) and clicked 'Accept'. $POST['source_url'] is not set.
		// They will perform the following:

	if(! x($_POST,'source_url')) {

		$uid = get_uid();

		if(! $uid) {
			notice( t('Permission denied.') . EOL );
			return;
		}	

		// These come from the friend request notification form.
	
		$dfrn_id  = ((x($_POST,'dfrn_id')) ? notags(trim($_POST['dfrn_id'])) : "");
		$intro_id = intval($_POST['intro_id']);
		$duplex   = intval($_POST['duplex']);


		// The other person will have been issued an ID when they first requested friendship.
		// Locate their record. At this time, their record will have both pending and blocked set to 1. 

		$r = q("SELECT * FROM `contact` WHERE `issued-id` = '%s' AND `uid` = %d LIMIT 1",
				dbesc($dfrn_id),
				intval($uid)
		);

		if(! count($r)) {
			notice( t('Contact not found.') . EOL );
			return;
		}

		$contact_id   = $r[0]['id'];
		$relation     = $r[0]['rel'];
		$site_pubkey  = $r[0]['site-pubkey'];
		$dfrn_confirm = $r[0]['confirm'];
		$aes_allow    = $r[0]['aes_allow'];


		// Generate a key pair for all further communications with this person.
		// We have a keypair for every contact, and a site key for unknown people.
		// This provides a means to carry on relationships with other people if 
		// any single key is compromised. It is a robust key. We're much more 
		// worried about key leakage than anybody cracking it.  

		$res = openssl_pkey_new(array(
        		'digest_alg' => 'whirlpool',
        		'private_key_bits' => 4096,
			'encrypt_key' => false )
		);


		$private_key = '';

		openssl_pkey_export($res, $private_key);

		$pubkey = openssl_pkey_get_details($res);
		$public_key = $pubkey["key"];

		// Save the private key. Send them the public key.

		$r = q("UPDATE `contact` SET `prvkey` = '%s' WHERE `id` = %d AND `uid` = %d LIMIT 1",
			dbesc($private_key),
			intval($contact_id),
			intval($uid) 
		);


		$params = array();

		// Per the protocol document, we will verify both ends by encrypting the dfrn_id with our 
		// site private key (person on the other end can decrypt it with our site public key).
		// Then encrypt our profile URL with the other person's site public key. They can decrypt
		// it with their site private key. If the decryption on the other end fails for either
		// item, it indicates tampering or key failure on at least one site and we will not be 
		// able to provide a secure communication pathway.

		// If other site is willing to accept full encryption, (aes_allow is 1 AND we have php5.3 
		// or later) then we encrypt the personal public key we send them using AES-256-CBC and a 
		// random key which is encrypted with their site public key.  

		$src_aes_key = random_string();

		$result = '';
		openssl_private_encrypt($dfrn_id,$result,$a->user['prvkey']);

		$params['dfrn_id'] = bin2hex($result);
		$params['public_key'] = $public_key;


		$my_url = $a->get_baseurl() . '/profile/' . $a->user['nickname'];

		openssl_public_encrypt($my_url, $params['source_url'], $site_pubkey);
		$params['source_url'] = bin2hex($params['source_url']);

		if($aes_allow && function_exists('openssl_encrypt')) {
			openssl_public_encrypt($src_aes_key, $params['aes_key'], $site_pubkey);
			$params['aes_key'] = bin2hex($params['aes_key']);
			$params['public_key'] = bin2hex(openssl_encrypt($public_key,'AES-256-CBC',$src_aes_key));
		}

		$params['dfrn_version'] = '2.0';
		if($duplex == 1)
			$params['duplex'] = 1;

		// POST all this stuff to the other site.

		$res = post_url($dfrn_confirm,$params);

		// Now figure out what they responded. Try to be robust if the remote site is 
		// having difficulty and throwing up errors of some kind. 

		$leading_junk = substr($res,0,strpos($res,'<?xml'));

		$res = substr($res,strpos($res,'<?xml'));
		if(! strlen($res)) {

				// No XML at all, this exchange is messed up really bad.
				// We shouldn't proceed, because the xml parser might choke,
				// and $status is going to be zero, which indicates success.
				// We can hardly call this a success.  

			notice( t('Response from remote site was not understood.') . EOL);
			return;
		}

		if(strlen($leading_junk) && get_config('system','debugging')) {

				// This might be more common. Mixed error text and some XML.
				// If we're configured for debugging, show the text. Proceed in either case.

			notice( t('Unexpected response from remote site: ') . EOL . $leading_junk . EOL );
		}

		$xml = simplexml_load_string($res);
		$status = (int) $xml->status;
		$message = unxmlify($xml->message);   // human readable text of what may have gone wrong.
		switch($status) {
			case 0:
				notice( t("Confirmation completed successfully.") . EOL);
				if(strlen($message))
					notice( t('Remote site reported: ') . $message . EOL);
				break;
			case 1:
				// birthday paradox - generate new dfrn-id and fall through.
				$new_dfrn_id = random_string();
				$r = q("UPDATE contact SET `issued-id` = '%s' WHERE `id` = %d AND `uid` = %d LIMIT 1",
					dbesc($new_dfrn_id),
					intval($contact_id),
					intval($uid) 
				);

			case 2:
				notice( t("Temporary failure. Please wait and try again.") . EOL);
				if(strlen($message))
					notice( t('Remote site reported: ') . $message . EOL);
				break;


			case 3:
				notice( t("Introduction failed or was revoked.") . EOL);
				if(strlen($message))
					notice( t('Remote site reported: ') . $message . EOL);
				break;
			}

		if(($status == 0) && ($intro_id)) {

			// Success. Delete the notification.

			$r = q("DELETE FROM `intro` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($intro_id),
				intval($uid)
			);
			
		}

		if($status != 0) 
			return;
		
		// We have now established a relationship with the other site.
		// Let's make our own personal copy of their profile photo so we don't have
		// to always load it from their site.

		require_once("Photo.php");

		$photo_failure = false;

		$r = q("SELECT `photo` FROM `contact` WHERE `id` = %d LIMIT 1",
			intval($contact_id));
		if(count($r)) {

			$filename = basename($r[0]['photo']);
			$img_str = fetch_url($r[0]['photo'],true);
			$img = new Photo($img_str);
			if($img->is_valid()) {

				$img->scaleImageSquare(175);
					
				$hash = photo_new_resource();

				$r = $img->store($uid, $contact_id, $hash, $filename, t('Contact Photos'), 4 );

				if($r === false)
					$photo_failure = true;

				$img->scaleImage(80);

				$r = $img->store($uid, $contact_id, $hash, $filename, t('Contact Photos'), 5 );

				if($r === false)
					$photo_failure = true;

				$photo = $a->get_baseurl() . '/photo/' . $hash . '-4.jpg';
				$thumb = $a->get_baseurl() . '/photo/' . $hash . '-5.jpg';
			}
			else
				$photo_failure = true;
		}
		else
			$photo_failure = true;

		if($photo_failure) {
			$photo = $a->get_baseurl() . '/images/default-profile.jpg';
			$thumb = $a->get_baseurl() . '/images/default-profile-sm.jpg';
		}

		$new_relation = REL_VIP;
		if(($relation == REL_FAN) || ($duplex))
			$new_relation = REL_BUD;

		$r = q("UPDATE `contact` SET `photo` = '%s', 
			`thumb` = '%s', 
			`rel` = %d, 
			`name-date` = '%s', 
			`uri-date` = '%s', 
			`avatar-date` = '%s', 
			`blocked` = 0, 
			`pending` = 0,
			`duplex` = %d,
			`network` = 'dfrn' WHERE `id` = %d LIMIT 1
		",
			dbesc($photo),
			dbesc($thumb),
			intval($new_relation),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			intval($duplex),
			intval($contact_id)
		);
		if($r === false)
			notice( t('Unable to set contact photo.') . EOL);


		// Let's send our user to the contact editor in case they want to
		// do anything special with this new friend.
 
		goaway($a->get_baseurl() . '/contacts/' . intval($contact_id));
		return;  //NOTREACHED
	}



	// End of first scenario. [Local confirmation of remote friend request].



	// Begin scenario two. This is the remote response to the above scenario.
	// This will take place on the site that originally initiated the friend request.
	// In the section above where the confirming party makes a POST and 
	// retrieves xml status information, they are communicating with the following code.

	if(x($_POST,'source_url')) {

		// We are processing an external confirmation to an introduction created by our user.

		$public_key = $_POST['public_key'];
		$dfrn_id    = hex2bin($_POST['dfrn_id']);
		$source_url = hex2bin($_POST['source_url']);
		$aes_key    = $_POST['aes_key'];
		$duplex     = $_POST['duplex'];
		$version_id = $_POST['dfrn_version'];


		// If $aes_key is set, both of these items require unpacking from the hex transport encoding.

		if(x($aes_key)) {
			$aes_key = hex2bin($aes_key);
			$public_key = hex2bin($public_key);
		}

		// Find our user's account

		$r = q("SELECT * FROM `user` WHERE `nickname` = '%s' LIMIT 1",
			dbesc($node));

		if(! count($r)) {
			$message = t('No user record found for ') . '\'' . $node . '\'';
			xml_status(3,$message); // failure
			// NOTREACHED
		}

		$my_prvkey = $r[0]['prvkey'];
		$local_uid = $r[0]['uid'];


		if(! strstr($my_prvkey,'BEGIN RSA PRIVATE KEY')) {
			$message = t('Our site encryption key is apparently messed up.');
			xml_status(3,$message);
		}

		// verify everything

		$decrypted_source_url = "";
		openssl_private_decrypt($source_url,$decrypted_source_url,$my_prvkey);


		if(! strlen($decrypted_source_url)) {
			$message = t('Empty site URL was provided or URL could not be decrypted by us.');
			xml_status(3,$message);
			// NOTREACHED
		}

		$ret = q("SELECT * FROM `contact` WHERE `url` = '%s' AND `uid` = %d LIMIT 1",
			dbesc($decrypted_source_url),
			intval($local_uid)
		);

		if(! count($ret)) {
			// this is either a bogus confirmation (?) or we deleted the original introduction.
			$message = t('Contact record was not found for you on our site.');
			xml_status(3,$message);
			return; // NOTREACHED 
		}

		$relation = $ret[0]['rel'];

		// Decrypt all this stuff we just received

		$foreign_pubkey = $ret[0]['site-pubkey'];
		$dfrn_record    = $ret[0]['id'];

		$decrypted_dfrn_id = "";
		openssl_public_decrypt($dfrn_id,$decrypted_dfrn_id,$foreign_pubkey);

		if(strlen($aes_key)) {
			$decrypted_aes_key = "";
			openssl_private_decrypt($aes_key,$decrypted_aes_key,$my_prvkey);
			$dfrn_pubkey = openssl_decrypt($public_key,'AES-256-CBC',$decrypted_aes_key);
		}
		else {
			$dfrn_pubkey = $public_key;
		}

		$r = q("SELECT * FROM `contact` WHERE `dfrn-id` = '%s' LIMIT 1",
			dbesc($decrypted_dfrn_id),
			intval($local_uid)
		);
		if(count($r)) {
			$message = t('The ID provided by your system is a duplicate on our system. It should work if you try again.');
			xml_status(1,$message); // Birthday paradox - duplicate dfrn-id
			// NOTREACHED
		}

		$r = q("UPDATE `contact` SET `dfrn-id` = '%s', `pubkey` = '%s' WHERE `id` = %d LIMIT 1",
			dbesc($decrypted_dfrn_id),
			dbesc($dfrn_pubkey),
			intval($dfrn_record)
		);
		if(! count($r)) {
			$message = t('Unable to set your contact credentials on our system.');
			xml_status(3,$message);
		}

		// We're good but now we have to scrape the profile photo and send notifications.

		require_once("Photo.php");

		$photo_failure = false;

		$r = q("SELECT `photo` FROM `contact` WHERE `id` = %d LIMIT 1",
			intval($dfrn_record));
		if(count($r)) {

			$filename = basename($r[0]['photo']);
			$img_str = fetch_url($r[0]['photo'],true);
			$img = new Photo($img_str);
			if($img->is_valid()) {

				$img->scaleImageSquare(175);
					
				$hash = photo_new_resource();

				$r = $img->store($local_uid, $dfrn_record, $hash, $filename, t('Contact Photos') , 4);

				if($r === false)
					$photo_failure = true;
					
				$img->scaleImage(80);
				$r = $img->store($local_uid, $dfrn_record, $hash, $filename, t('Contact Photos') , 5);

				if($r === false)
					$photo_failure = true;

				$photo = $a->get_baseurl() . '/photo/' . $hash . '-4.jpg';
				$thumb = $a->get_baseurl() . '/photo/' . $hash . '-5.jpg';	
			}
			else
				$photo_failure = true;
		}
		else
			$photo_failure = true;

		if($photo_failure) {
			$photo = $a->get_baseurl() . '/images/default-profile.jpg';
			$thumb = $a->get_baseurl() . '/images/default-profile-sm.jpg';
		}

		$new_relation = REL_FAN;
		if(($relation == REL_VIP) || ($duplex))
			$new_relation = REL_BUD;

		$r = q("UPDATE `contact` SET 
			`photo` = '%s', 
			`thumb` = '%s', 
			`rel` = %d, 
			`name-date` = '%s', 
			`uri-date` = '%s', 
			`avatar-date` = '%s', 
			`blocked` = 0, 
			`pending` = 0,
			`duplex` = %d, 
			`network` = 'dfrn' WHERE `id` = %d LIMIT 1
		",
			dbesc($photo),
			dbesc($thumb),
			intval($new_relation),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			intval($duplex),
			intval($dfrn_record)
		);
		if($r === false) { // indicates schema is messed up or total db failure
			$message = t('Unable to update your contact profile details on our system');
			xml_status(3,$message);
		}

		// Otherwise everything seems to have worked and we are almost done. Yay!
		// Send an email notification

		$r = q("SELECT * FROM `contact` LEFT JOIN `user` ON `contact`.`uid` = `user`.`uid`
			WHERE `contact`.`id` = %d LIMIT 1",
			intval($dfrn_record)
		);
		if((count($r)) && ($r[0]['notify-flags'] & NOTIFY_CONFIRM)) {

			$tpl = (($new_relation == REL_BUD) 
				? load_view_file('view/friend_complete_eml.tpl')
				: load_view_file('view/intro_complete_eml.tpl'));
		
			$email_tpl = replace_macros($tpl, array(
				'$sitename' => $a->config['sitename'],
				'$siteurl' =>  $a->get_baseurl(),
				'$username' => $r[0]['username'],
				'$email' => $r[0]['email'],
				'$fn' => $r[0]['name'],
				'$dfrn_url' => $r[0]['url'],
				'$uid' => $newuid )
			);
	
			$res = mail($r[0]['email'], t("Connection accepted at ") . $a->config['sitename'],
				$email_tpl, 'From: ' . t('Administrator') . '@' . $_SERVER[SERVER_NAME] );
			if(!$res) {
				notice( t("Email notification failed.") . EOL );
			}
		}
		xml_status(0); // Success
		return; // NOTREACHED

			////////////////////// End of this scenario ///////////////////////////////////////////////
	}

	// somebody arrived here by mistake or they are fishing. Send them to the homepage.

	goaway($a->get_baseurl());
	// NOTREACHED

}
