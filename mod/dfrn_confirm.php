<?php



function dfrn_confirm_post(&$a) {
	
	if($a->argc > 1)
		$node = $a->argv[1];

	if(x($_POST,'source_url')) {

	// We are processing an external confirmation to an introduction created by our user.

		$public_key = $_POST['public_key'];
		$dfrn_id = $_POST['dfrn_id'];
		$source_url = $_POST['source_url'];
		$aes_key = $_POST['aes_key'];

		if(intval($node)) 
			$r = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
				intval($node));
		else
			$r = q("SELECT * FROM `user` WHERE `nickname` = '%s' LIMIT 1",
				dbesc($node));

		if(! count($r)) {
			xml_status(3); // failure
		}

		$my_prvkey = $r[0]['prvkey'];
		$local_uid = $r[0]['uid'];

		$decrypted_source_url = "";

		openssl_private_decrypt($source_url,$decrypted_source_url,$my_prvkey);


		$ret = q("SELECT * FROM `contact` WHERE `url` = '%s' AND `uid` = %d LIMIT 1",
			dbesc($decrypted_source_url),
			intval($local_uid));

		if(! count($ret)) {
			// this is either a bogus confirmation or we deleted the original introduction.
			xml_status(3); 
		}

		// Decrypt all this stuff we just received

		$foreign_pubkey = $ret[0]['site-pubkey'];
		$dfrn_record = $ret[0]['id'];
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
			intval($local_uid));
		if(count($r))
			xml_status(1); // Birthday paradox - duplicate dfrn-id

		$r = q("UPDATE `contact` SET `dfrn-id` = '%s', `pubkey` = '%s' WHERE `id` = %d LIMIT 1",
			dbesc($decrypted_dfrn_id),
			dbesc($dfrn_pubkey),
			intval($dfrn_record));
		if($r) {

			// We're good but now we have to scrape the profile photo and send notifications.

			require_once("Photo.php");

			$photo_failure = false;

			$r = q("SELECT `photo` FROM `contact` WHERE `id` = %d LIMIT 1",
				intval($dfrn_record));
			if(count($r)) {

				$filename = basename($r[0]['photo']);
				$img_str = fetch_url($r[0]['photo'],true);
				$img = new Photo($img_str);
				if($img) {

					$img->scaleImageSquare(175);
					
					$hash = hash('md5',uniqid(mt_rand(),true));

					$r = q("INSERT INTO `photo` ( `uid`, `resource-id`, `created`, `edited`, `filename`,
                                		`height`, `width`, `data`, `scale` )
                                		VALUES ( %d, '%s', '%s', '%s', '%s', %d, %d, '%s', 4 )",
						intval($local_uid),
                                		dbesc($hash),
                                		datetime_convert(),
                                		datetime_convert(),
                                		dbesc(basename($r[0]['photo'])),
                                		intval($img->getHeight()),
						intval($img->getWidth()),
						dbesc($img->imageString())
					);
					if($r === false)
						$photo_failure = true;
					$img->scaleImage(80);
					$r =  q("INSERT INTO `photo` ( `uid`, `resource-id`, `created`, `edited`, `filename`,
                                                `height`, `width`, `data`, `scale` )
                                                VALUES ( %d, '%s', '%s', '%s', '%s', %d, %d, '%s', 5 )",
                                                intval($local_uid),
                                                dbesc($hash),
                                                datetime_convert(),
                                                datetime_convert(),
                                                dbesc(basename($r[0]['photo'])),
                                                intval($img->getHeight()),
                                                intval($img->getWidth()),
                                                dbesc($img->imageString())
                                        );
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

			$r = q("UPDATE `contact` SET `photo` = '%s', `thumb` = '%s', `blocked` = 0 WHERE `id` = %d LIMIT 1",
				dbesc($photo),
				dbesc($thumb),
				intval($dfrn_record)
			);
			if($r === false)
				$_SESSION['sysmsg'] .= "Unable to set contact photo info." . EOL;

			// Otherwise everything seems to have worked and we are almost done. Yay!
			// Send an email notification

			$r = q("SELECT * FROM `contact` LEFT JOIN `user` ON `contact`.`uid` = `user`.`uid`
				WHERE `contact`.`id` = %d LIMIT 1",
				intval($dfrn_record));
			
			$tpl = file_get_contents('view/intro_complete_eml.tpl');
			
			$email_tpl = replace_macros($tpl, array(
                                '$sitename' => $a->config['sitename'],
                                '$siteurl' =>  $a->get_baseurl(),
                                '$username' => $r[0]['username'],
                                '$email' => $r[0]['email'],
				'$fn' => $r[0]['name'],
				'$dfrn_url' => $r[0]['url'],
                                '$uid' => $newuid ));


                	$res = mail($r[0]['email'],"Introduction accepted at {$a->config['sitename']}",
				$email_tpl,"From: Administrator@{$_SERVER[SERVER_NAME]}");
			if(!$res) {
				$_SESSION['sysmsg'] .= "Email notification failed." . EOL;
			}
			xml_status(0); // Success

			return; // NOTREACHED

		}
		else
			xml_status(2);	// Hopefully temporary problem that can be retried.

		return; // NOTREACHED

	////////////////////// End of this scenario ///////////////////////////////////////////////
	}
	else {

	// We are processing a local confirmation initiated on this system by our user to an external introduction.

		$uid = $_SESSION['uid'];

		if(! $uid) {
			$_SESSION['sysmsg'] = 'Unauthorised.';
			return;
		}	
	
		$dfrn_id = ((x($_POST,'dfrn_id')) ? notags(trim($_POST['dfrn_id'])) : "");
		$intro_id = intval($_POST['intro_id']);

		$r = q("SELECT * FROM `contact` WHERE `issued-id` = '%s' AND `uid` = %d LIMIT 1",
				dbesc($dfrn_id),
				intval($uid)
				);

		if((! $r) || (! count($r))) {
			$_SESSION['sysmsg'] = 'Node does not exist.' . EOL ;
			return;
		}

		$contact_id = $r[0]['id'];
		$site_pubkey = $r[0]['site-pubkey'];
		$dfrn_confirm = $r[0]['confirm'];
		$aes_allow = $r[0]['aes_allow'];

		$res=openssl_pkey_new(array(
        		'digest_alg' => 'whirlpool',
        		'private_key_bits' => 4096,
			'encrypt_key' => false ));


		$private_key = '';

		openssl_pkey_export($res, $private_key);


		$pubkey = openssl_pkey_get_details($res);
		$public_key = $pubkey["key"];

		$r = q("UPDATE `contact` SET `pubkey` = '%s', `prvkey` = '%s' WHERE `id` = %d AND `uid` = %d LIMIT 1",
			dbesc($public_key),
			dbesc($private_key),
			intval($contact_id),
			intval($uid) 
			);


		$params = array();

		$src_aes_key = random_string();
		$result = "";

		openssl_private_encrypt($dfrn_id,$result,$a->user['prvkey']);

		$params['dfrn_id'] = $result;
		$params['public_key'] = $public_key;


		openssl_public_encrypt($_SESSION['my_url'], $params['source_url'], $site_pubkey);

		if($aes_allow && function_exists('openssl_encrypt')) {
			openssl_public_encrypt($src_aes_key, $params['aes_key'], $site_pubkey);
			$params['public_key'] = openssl_encrypt($public_key,'AES-256-CBC',$src_aes_key);
		}

		$res = post_url($dfrn_confirm,$params);

// uncomment the following two lines and comment the following xml/status lines
// to debug the remote confirmation section (when both confirmations 
// and responses originate on this system)

// echo $res;
// $status = 0;

		$xml = simplexml_load_string($res);
		$status = (int) $xml->status;
		switch($status) {
			case 0:
				$_SESSION['sysmsg'] .= "Confirmation completed successfully" . EOL;
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
				$_SESSION['sysmsg'] .= "Temporary failure. Please wait and try again." . EOL;
				break;


			case 3:
				$_SESSION['sysmsg'] .= "Introduction failed or was revoked. Cannot complete." . EOL;
				break;
		}

		if(($status == 0 || $status == 3) && ($intro_id)) {

			//delete the notification

			$r = q("DELETE FROM `intro` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($intro_id),
				intval($uid)
			);
			
		}
		if($status != 0) 
			return;
		

		require_once("Photo.php");

		$photo_failure = false;

		$r = q("SELECT `photo` FROM `contact` WHERE `id` = %d LIMIT 1",
			intval($contact_id));
		if(count($r)) {

			$filename = basename($r[0]['photo']);
			$img_str = fetch_url($r[0]['photo'],true);
			$img = new Photo($img_str);
			if($img) {

				$img->scaleImageSquare(175);
					
				$hash = hash('md5',uniqid(mt_rand(),true));

				$r = q("INSERT INTO `photo` ( `uid`, `resource-id`, `created`, `edited`, `filename`,
                               		`height`, `width`, `data`, `scale` )
                               		VALUES ( %d, '%s', '%s', '%s', '%s', %d, %d, '%s', 4 )",
					intval($local_uid),
                               		dbesc($hash),
                               		datetime_convert(),
                               		datetime_convert(),
                               		dbesc(basename($r[0]['photo'])),
                               		intval($img->getHeight()),
					intval($img->getWidth()),
					dbesc($img->imageString())
				);
				if($r === false)
					$photo_failure = true;
				$img->scaleImage(80);
				$r =  q("INSERT INTO `photo` ( `uid`, `resource-id`, `created`, `edited`, `filename`,
					`height`, `width`, `data`, `scale` )
                                         VALUES ( %d, '%s', '%s', '%s', '%s', %d, %d, '%s', 5 )",
                                         intval($local_uid),
                                         dbesc($hash),
                                         datetime_convert(),
                                         datetime_convert(),
                                         dbesc(basename($r[0]['photo'])),
                                         intval($img->getHeight()),
                                         intval($img->getWidth()),
                                         dbesc($img->imageString())
                                );
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

		$r = q("UPDATE `contact` SET `photo` = '%s', `thumb` = '%s', `blocked` = 0 WHERE `id` = %d LIMIT 1",
			dbesc($photo),
			dbesc($thumb),
			intval($contact_id)
		);
		if($r === false)
			$_SESSION['sysmsg'] .= "Unable to set contact photo info." . EOL;
	}

	return;
}
