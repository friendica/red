<?php

require_once('include/crypto.php');
require_once('include/items.php');
require_once('include/bb2diaspora.php');
require_once('include/contact_selectors.php');
//require_once('include/queue_fn.php');
//require_once('include/lock.php');

function diaspora_dispatch_public($msg) {

	$enabled = intval(get_config('system','diaspora_enabled'));
	if(! $enabled) {
		logger('mod-diaspora: disabled');
		return;
	}

	$sys_disabled = true;

	if(! get_config('system','disable_discover_tab')) {
		$sys_disabled = get_config('system','disable_diaspora_discover_tab');
	}
	$sys = (($sys_disabled) ? null : get_sys_channel());

	// find everybody following or allowing this author

	$r = q("SELECT * from channel where channel_id in ( SELECT abook_channel from abook left join xchan on abook_xchan = xchan_hash WHERE xchan_network like '%%diaspora%%' and xchan_addr = '%s' )",
		dbesc($msg['author'])
	);

	// also need to look for those following public streams

	if($r) {
		foreach($r as $rr) {
			logger('diaspora_public: delivering to: ' . $rr['channel_name'] . ' (' . $rr['channel_address'] . ') ');
			diaspora_dispatch($rr,$msg);
		}
	}
	else {
		if(! $sys)
			logger('diaspora_public: no subscribers');
	}

	if($sys) {
		$sys['system'] = true;
		logger('diaspora_public: delivering to sys.');
		diaspora_dispatch($sys,$msg);
	}
}



function diaspora_dispatch($importer,$msg) {

	$ret = 0;

	if(! array_key_exists('system',$importer))
		$importer['system'] = false;

	$enabled = intval(get_config('system','diaspora_enabled'));
	if(! $enabled) {
		logger('mod-diaspora: disabled');
		return;
	}

	// php doesn't like dashes in variable names

	$msg['message'] = str_replace(
			array('<activity_streams-photo>','</activity_streams-photo>'),
			array('<asphoto>','</asphoto>'),
			$msg['message']);


	$parsed_xml = parse_xml_string($msg['message'],false);

	$xmlbase = $parsed_xml->post;

//	logger('diaspora_dispatch: ' . print_r($xmlbase,true), LOGGER_DATA);


	if($xmlbase->request) {
		$ret = diaspora_request($importer,$xmlbase->request);
	}
	elseif($xmlbase->status_message) {
		$ret = diaspora_post($importer,$xmlbase->status_message,$msg);
	}
	elseif($xmlbase->profile) {
		$ret = diaspora_profile($importer,$xmlbase->profile,$msg);
	}
	elseif($xmlbase->comment) {
		$ret = diaspora_comment($importer,$xmlbase->comment,$msg);
	}
	elseif($xmlbase->like) {
		$ret = diaspora_like($importer,$xmlbase->like,$msg);
	}
	elseif($xmlbase->asphoto) {
		$ret = diaspora_asphoto($importer,$xmlbase->asphoto,$msg);
	}
	elseif($xmlbase->reshare) {
		$ret = diaspora_reshare($importer,$xmlbase->reshare,$msg);
	}
	elseif($xmlbase->retraction) {
		$ret = diaspora_retraction($importer,$xmlbase->retraction,$msg);
	}
	elseif($xmlbase->signed_retraction) {
		$ret = diaspora_signed_retraction($importer,$xmlbase->signed_retraction,$msg);
	}
	elseif($xmlbase->relayable_retraction) {
		$ret = diaspora_signed_retraction($importer,$xmlbase->relayable_retraction,$msg);
	}
	elseif($xmlbase->photo) {
		$ret = diaspora_photo($importer,$xmlbase->photo,$msg);
	}
	elseif($xmlbase->conversation) {
		$ret = diaspora_conversation($importer,$xmlbase->conversation,$msg);
	}
	elseif($xmlbase->message) {
		$ret = diaspora_message($importer,$xmlbase->message,$msg);
	}
	else {
		logger('diaspora_dispatch: unknown message type: ' . print_r($xmlbase,true));
	}
	return $ret;
}


function diaspora_is_blacklisted($s) {

	$bl1 = get_config('system','blacklisted_sites');
	if(is_array($bl1) && $bl1) {
		foreach($bl1 as $bl) {
			if($bl && strpos($s,$bl) !== false) {
				logger('diaspora_is_blacklisted: blacklisted ' . $s);
				return true;
			}
		}
	}
	return false;
}

function diaspora_process_outbound($arr) {

/*

	We are passed the following array from the notifier, providing everything we need to make delivery decisions.

			diaspora_process_outbound(array(
				'channel' => $channel,
				'env_recips' => $env_recips,
				'recipients' => $recipients,
				'item' => $item,
				'target_item' => $target_item,
				'hub' => $hub,
				'top_level_post' => $top_level_post,
				'private' => $private,
				'followup' => $followup,
				'relay_to_owner' => $relay_to_owner,
				'uplink' => $uplink,
				'cmd' => $cmd,
				'expire' =>	$expire,
				'mail' => $mail,
				'location' => $location,
				'fsuggest' => $fsuggest,
				'normal_mode' => $normal_mode,
				'packet_type' => $packet_type,
				'walltowall' => $walltowall,
			));
*/


	if($arr['location'])
		return;


	$target_item = $arr['target_item'];

	if($target_item && array_key_exists('item_flags',$target_item) && ($target_item['item_flags'] & ITEM_OBSCURED)) {
		$key = get_config('system','prvkey');
		if($target_item['title'])
			$target_item['title'] = crypto_unencapsulate(json_decode($target_item['title'],true),$key);
		if($target_item['body'])
			$target_item['body'] = crypto_unencapsulate(json_decode($target_item['body'],true),$key);
	}



	if($arr['env_recips']) {
		$hashes = array();

		// re-explode the recipients, but only for this hub/pod

		foreach($arr['env_recips'] as $recip)
			$hashes[] = "'" . $recip['hash'] . "'";

		$r = q("select * from xchan left join hubloc on xchan_hash = hubloc_hash where hubloc_url = '%s' 
			and xchan_hash in (" . implode(',', $hashes) . ") and xchan_network in ('diaspora', 'friendica-over-diaspora') ",
			dbesc($arr['hub']['hubloc_url'])
		);

		if(! $r) {
			logger('diaspora_process_outbound: no recipients');
			return; 
		}

		foreach($r as $contact) {
	
			if($arr['mail']) {
				diaspora_send_mail($arr['item'],$arr['channel'],$contact);
				continue;
			}

			if(! $arr['normal_mode'])
				continue;

			// special handling for followup to public post
			// all other public posts processed as public batches further below

			if((! $arr['private']) && ($arr['followup'])) {
				diaspora_send_followup($target_item,$arr['channel'],$contact, true);
				continue;
			}

			if(! $contact['xchan_pubkey'])
				continue;

			if(($target_item['item_restrict'] & ITEM_DELETED) 
				&& (($target_item['mid'] === $target_item['parent_mid']) || $arr['followup'])) {
				// send both top-level retractions and relayable retractions for owner to relay
				diaspora_send_retraction($target_item,$arr['channel'],$contact);
				continue;
			}
			elseif($arr['followup']) {
				// send comments and likes to owner to relay
				diaspora_send_followup($target_item,$arr['channel'],$contact);
				continue;
			}

			elseif($target_item['mid'] !== $target_item['parent_mid']) {
				// we are the relay - send comments, likes and relayable_retractions
				// (of comments and likes) to our conversants
				diaspora_send_relay($target_item,$arr['channel'],$contact);
				continue;
			}
			elseif($arr['top_level_post']) {
				diaspora_send_status($target_item,$arr['channel'],$contact);
				continue;
			}
		}
	}
	else {
		// public message

		$contact = $arr['hub'];

		if(($target_item['deleted']) 
			&& ($target_item['mid'] === $target_item['parent_mod'])) {
			// top-level retraction
			logger('delivery: diaspora retract: ' . $loc);
			diaspora_send_retraction($target_item,$arr['channel'],$contact,true);
			return;
		}
		elseif($target_item['mid'] !== $target_item['parent_mid']) {
			// we are the relay - send comments, likes and relayable_retractions to our conversants
			logger('delivery: diaspora relay: ' . $loc);
			diaspora_send_relay($target_item,$arr['channel'],$contact,true);
			return;
		}
		elseif($arr['top_level_post']) {
			logger('delivery: diaspora status: ' . $loc);
			diaspora_send_status($target_item,$arr['channel'],$contact,true);
			return;
		}

	}

}


function diaspora_handle_from_contact($contact_hash) {

	logger("diaspora_handle_from_contact: contact id is " . $contact_hash, LOGGER_DEBUG);

	$r = q("SELECT xchan_addr from xchan where xchan_hash = '%s' limit 1",
		dbesc($contact_hash)
	);
	if($r) {
		return $r[0]['xchan_addr'];
	}
	return false;
}

function diaspora_get_contact_by_handle($uid,$handle) {

	if(diaspora_is_blacklisted($handle))
		return false;
	require_once('include/identity.php');

	$sys = get_sys_channel();
	if(($sys) && ($sys['channel_id'] == $uid)) {
		$r = q("SELECT * FROM xchan where xchan_addr = '%s' limit 1",
			dbesc($handle)
		);
	}
	else {
		$r = q("SELECT * FROM abook left join xchan on xchan_hash = abook_xchan where xchan_addr = '%s' and abook_channel = %d limit 1",
			dbesc($handle),
			intval($uid)
		);
	}

	return (($r) ? $r[0] : false);
}

function find_diaspora_person_by_handle($handle) {

	$person = false;

	if(diaspora_is_blacklisted($handle))
		return false;

	$r = q("select * from xchan where xchan_addr = '%s' limit 1",
		dbesc($handle)
	);
	if($r) {
		$person = $r[0];
		logger('find_diaspora_person_by handle: in cache ' . print_r($r,true), LOGGER_DATA);
	}

	if(! $person) {

		// try webfinger. Make sure to distinguish between diaspora, 
		// redmatrix w/diaspora protocol and friendica w/diaspora protocol.

		$result = discover_by_webbie($handle);
		if($result) {
			$r = q("select * from xchan where xchan_addr = '%s' limit 1",
				dbesc($handle)
			);
			if($r) {
				$person = $r[0];
				logger('find_diaspora_person_by handle: discovered ' . print_r($r,true), LOGGER_DATA);
			}
		}
	}

	return $person;
}


function get_diaspora_key($handle) {
	logger('Fetching diaspora key for: ' . $handle, LOGGER_DEBUG);
	$r = find_diaspora_person_by_handle($handle);
	return(($r) ? $r['xchan_pubkey'] : '');
}


function diaspora_pubmsg_build($msg,$channel,$contact,$prvkey,$pubkey) {

	$a = get_app();

	logger('diaspora_pubmsg_build: ' . $msg, LOGGER_DATA);

    $handle = $channel['channel_address'] . '@' . get_app()->get_hostname();


	$b64url_data = base64url_encode($msg,false);

	$data = str_replace(array("\n","\r"," ","\t"),array('','','',''),$b64url_data);

	$type = 'application/xml';
	$encoding = 'base64url';
	$alg = 'RSA-SHA256';

	$signable_data = $data  . '.' . base64url_encode($type,false) . '.'
		. base64url_encode($encoding,false) . '.' . base64url_encode($alg,false) ;

	$signature = rsa_sign($signable_data,$prvkey);
	$sig = base64url_encode($signature,false);

$magic_env = <<< EOT
<?xml version='1.0' encoding='UTF-8'?>
<diaspora xmlns="https://joindiaspora.com/protocol" xmlns:me="http://salmon-protocol.org/ns/magic-env" >
  <header>
	<author_id>$handle</author_id>
  </header>
  <me:env>
	<me:encoding>base64url</me:encoding>
	<me:alg>RSA-SHA256</me:alg>
	<me:data type="application/xml">$data</me:data>
	<me:sig>$sig</me:sig>
  </me:env>
</diaspora>
EOT;

	logger('diaspora_pubmsg_build: magic_env: ' . $magic_env, LOGGER_DATA);
	return $magic_env;

}




function diaspora_msg_build($msg,$channel,$contact,$prvkey,$pubkey,$public = false) {
	$a = get_app();

	if($public)
		return diaspora_pubmsg_build($msg,$channel,$contact,$prvkey,$pubkey);

	logger('diaspora_msg_build: ' . $msg, LOGGER_DATA);

	// without a public key nothing will work

	if(! $pubkey) {
		logger('diaspora_msg_build: pubkey missing: contact id: ' . $contact['abook_id']);
		return '';
	}

	$inner_aes_key = random_string(32);
	$b_inner_aes_key = base64_encode($inner_aes_key);
	$inner_iv = random_string(16);
	$b_inner_iv = base64_encode($inner_iv);

	$outer_aes_key = random_string(32);
	$b_outer_aes_key = base64_encode($outer_aes_key);
	$outer_iv = random_string(16);
	$b_outer_iv = base64_encode($outer_iv);

    $handle = $channel['channel_address'] . '@' . get_app()->get_hostname();

	$padded_data = pkcs5_pad($msg,16);
	$inner_encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $inner_aes_key, $padded_data, MCRYPT_MODE_CBC, $inner_iv);

	$b64_data = base64_encode($inner_encrypted);


	$b64url_data = base64url_encode($b64_data,false);
	$data = str_replace(array("\n","\r"," ","\t"),array('','','',''),$b64url_data);

	$type = 'application/xml';
	$encoding = 'base64url';
	$alg = 'RSA-SHA256';

	$signable_data = $data  . '.' . base64url_encode($type,false) . '.'
		. base64url_encode($encoding,false) . '.' . base64url_encode($alg,false) ;

	logger('diaspora_msg_build: signable_data: ' . $signable_data, LOGGER_DATA);

	$signature = rsa_sign($signable_data,$prvkey);
	$sig = base64url_encode($signature,false);

$decrypted_header = <<< EOT
<decrypted_header>
  <iv>$b_inner_iv</iv>
  <aes_key>$b_inner_aes_key</aes_key>
  <author_id>$handle</author_id>
</decrypted_header>
EOT;

	$decrypted_header = pkcs5_pad($decrypted_header,16);

	$ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $outer_aes_key, $decrypted_header, MCRYPT_MODE_CBC, $outer_iv);

	$outer_json = json_encode(array('iv' => $b_outer_iv,'key' => $b_outer_aes_key));

	$encrypted_outer_key_bundle = '';
	openssl_public_encrypt($outer_json,$encrypted_outer_key_bundle,$pubkey);

	$b64_encrypted_outer_key_bundle = base64_encode($encrypted_outer_key_bundle);

	logger('outer_bundle: ' . $b64_encrypted_outer_key_bundle . ' key: ' . $pubkey, LOGGER_DATA);

	$encrypted_header_json_object = json_encode(array('aes_key' => base64_encode($encrypted_outer_key_bundle), 
		'ciphertext' => base64_encode($ciphertext)));
	$cipher_json = base64_encode($encrypted_header_json_object);

	$encrypted_header = '<encrypted_header>' . $cipher_json . '</encrypted_header>';

$magic_env = <<< EOT
<?xml version='1.0' encoding='UTF-8'?>
<diaspora xmlns="https://joindiaspora.com/protocol" xmlns:me="http://salmon-protocol.org/ns/magic-env" >
  $encrypted_header
  <me:env>
	<me:encoding>base64url</me:encoding>
	<me:alg>RSA-SHA256</me:alg>
	<me:data type="application/xml">$data</me:data>
	<me:sig>$sig</me:sig>
  </me:env>
</diaspora>
EOT;

	logger('diaspora_msg_build: magic_env: ' . $magic_env, LOGGER_DATA);
	return $magic_env;

}

/**
 *
 * diaspora_decode($importer,$xml)
 *   array $importer -> from user table
 *   string $xml -> urldecoded Diaspora salmon 
 *
 * Returns array
 * 'message' -> decoded Diaspora XML message
 * 'author' -> author diaspora handle
 * 'key' -> author public key (converted to pkcs#8)
 *
 * Author and key are used elsewhere to save a lookup for verifying replies and likes
 */


function diaspora_decode($importer,$xml) {

	$public = false;
	$basedom = parse_xml_string($xml);

	$children = $basedom->children('https://joindiaspora.com/protocol');

	if($children->header) {
		$public = true;
		$author_link = str_replace('acct:','',$children->header->author_id);
	}
	else {

		$encrypted_header = json_decode(base64_decode($children->encrypted_header));

		$encrypted_aes_key_bundle = base64_decode($encrypted_header->aes_key);
		$ciphertext = base64_decode($encrypted_header->ciphertext);

		$outer_key_bundle = '';
		openssl_private_decrypt($encrypted_aes_key_bundle,$outer_key_bundle,$importer['channel_prvkey']);

		$j_outer_key_bundle = json_decode($outer_key_bundle);

		$outer_iv = base64_decode($j_outer_key_bundle->iv);
		$outer_key = base64_decode($j_outer_key_bundle->key);

		$decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $outer_key, $ciphertext, MCRYPT_MODE_CBC, $outer_iv);


		$decrypted = pkcs5_unpad($decrypted);

		/**
		 * $decrypted now contains something like
		 *
		 *  <decrypted_header>
		 *	 <iv>8e+G2+ET8l5BPuW0sVTnQw==</iv>
		 *	 <aes_key>UvSMb4puPeB14STkcDWq+4QE302Edu15oaprAQSkLKU=</aes_key>

***** OBSOLETE

		 *	 <author>
		 *	   <name>Ryan Hughes</name>
		 *	   <uri>acct:galaxor@diaspora.pirateship.org</uri>
		 *	 </author>

***** CURRENT

		 *	 <author_id>galaxor@diaspora.priateship.org</author_id>

***** END DIFFS

		 *  </decrypted_header>
		 */

		logger('decrypted: ' . $decrypted, LOGGER_DATA);
		$idom = parse_xml_string($decrypted,false);

		$inner_iv = base64_decode($idom->iv);
		$inner_aes_key = base64_decode($idom->aes_key);

		$author_link = str_replace('acct:','',$idom->author_id);

	}

	$dom = $basedom->children(NAMESPACE_SALMON_ME);

	// figure out where in the DOM tree our data is hiding

	if($dom->provenance->data)
		$base = $dom->provenance;
	elseif($dom->env->data)
		$base = $dom->env;
	elseif($dom->data)
		$base = $dom;

	if(! $base) {
		logger('mod-diaspora: unable to locate salmon data in xml ');
		http_status_exit(400);
	}


	// Stash the signature away for now. We have to find their key or it won't be good for anything.
	$signature = base64url_decode($base->sig);

	// unpack the  data

	// strip whitespace so our data element will return to one big base64 blob
	$data = str_replace(array(" ","\t","\r","\n"),array("","","",""),$base->data);


	// stash away some other stuff for later

	$type = $base->data[0]->attributes()->type[0];
	$keyhash = $base->sig[0]->attributes()->keyhash[0];
	$encoding = $base->encoding;
	$alg = $base->alg;

	$signed_data = $data  . '.' . base64url_encode($type,false) . '.' . base64url_encode($encoding,false) . '.' . base64url_encode($alg,false);


	// decode the data
	$data = base64url_decode($data);


	if($public) {
		$inner_decrypted = $data;
	}
	else {

		// Decode the encrypted blob

		$inner_encrypted = base64_decode($data);
		$inner_decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $inner_aes_key, $inner_encrypted, MCRYPT_MODE_CBC, $inner_iv);
		$inner_decrypted = pkcs5_unpad($inner_decrypted);
	}

	if(! $author_link) {
		logger('mod-diaspora: Could not retrieve author URI.');
		http_status_exit(400);
	}

	// Once we have the author URI, go to the web and try to find their public key
	// (first this will look it up locally if it is in the fcontact cache)
	// This will also convert diaspora public key from pkcs#1 to pkcs#8

	logger('mod-diaspora: Fetching key for ' . $author_link );
 	$key = get_diaspora_key($author_link);

	if(! $key) {
		logger('mod-diaspora: Could not retrieve author key.');
		http_status_exit(400);
	}

	$verify = rsa_verify($signed_data,$signature,$key);

	if(! $verify) {
		logger('mod-diaspora: Message did not verify. Discarding.');
		http_status_exit(400);
	}

	logger('mod-diaspora: Message verified.');

	return array('message' => $inner_decrypted, 'author' => $author_link, 'key' => $key);

}


/* sender is now sharing with recipient */

function diaspora_request($importer,$xml) {

	$a = get_app();

	$sender_handle = unxmlify($xml->sender_handle);
	$recipient_handle = unxmlify($xml->recipient_handle);

	if(! $sender_handle || ! $recipient_handle)
		return;


	// Do we already have an abook record? 

	$contact = diaspora_get_contact_by_handle($importer['channel_id'],$sender_handle);

	if($contact && $contact['abook_id']) {

		// perhaps we were already sharing with this person. Now they're sharing with us.
		// That makes us friends. Maybe.

		// Please note some of these permissions such as PERMS_R_PAGES are impossible for Disapora.
		// They cannot authenticate to our system.

		$newperms = PERMS_R_STREAM|PERMS_R_PROFILE|PERMS_R_PHOTOS|PERMS_R_ABOOK|PERMS_W_STREAM|PERMS_W_COMMENT|PERMS_W_MAIL|PERMS_W_CHAT|PERMS_R_STORAGE|PERMS_R_PAGES;

		$r = q("update abook set abook_their_perms = %d where abook_id = %d and abook_channel = %d",
			intval($newperms),
			intval($contact['abook_id']),
			intval($importer['channel_id'])
		);

		return;
	}

	$ret = find_diaspora_person_by_handle($sender_handle);

	if((! $ret) || (! strstr($ret['xchan_network'],'diaspora'))) {
		logger('diaspora_request: Cannot resolve diaspora handle ' . $sender_handle . ' for ' . $recipient_handle);
		return;
	}

	$role = get_pconfig($channel['channel_id'],'system','permissions_role');
	if($role) {
		$x = get_role_perms($role);
		if($x['perms_auto'])
		$default_perms = $x['perms_accept'];
	}
	if(! $default_perms)
		$default_perms = intval(get_pconfig($importer['channel_id'],'system','autoperms'));
				
	$their_perms = PERMS_R_STREAM|PERMS_R_PROFILE|PERMS_R_PHOTOS|PERMS_R_ABOOK|PERMS_W_STREAM|PERMS_W_COMMENT|PERMS_W_MAIL|PERMS_W_CHAT|PERMS_R_STORAGE|PERMS_R_PAGES;


	$closeness = get_pconfig($importer['channel_id'],'system','new_abook_closeness');
	if($closeness === false)
		$closeness = 80;


	$r = q("insert into abook ( abook_account, abook_channel, abook_xchan, abook_my_perms, abook_their_perms, abook_closeness, abook_rating, abook_created, abook_updated, abook_connected, abook_dob, abook_flags) values ( %d, %d, '%s', %d, %d, %d, %d, '%s', '%s', '%s', '%s', %d )",
		intval($importer['channel_account_id']),
		intval($importer['channel_id']),
		dbesc($ret['xchan_hash']),
		intval($default_perms),
		intval($their_perms),
		intval($closeness),
		intval(0),
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		dbesc(NULL_DATE),
		intval(($default_perms) ? 0 : ABOOK_FLAG_PENDING)
	);
		

	if($r) {
		logger("New Diaspora introduction received for {$importer['channel_name']}");

		$new_connection = q("select * from abook left join xchan on abook_xchan = xchan_hash left join hubloc on hubloc_hash = xchan_hash where abook_channel = %d and abook_xchan = '%s' order by abook_created desc limit 1",
			intval($importer['channel_id']),
			dbesc($ret['xchan_hash'])
		);
		if($new_connection) {
			require_once('include/enotify.php');
			notification(array(
				'type'	     => NOTIFY_INTRO,
				'from_xchan'   => $ret['xchan_hash'],
				'to_xchan'     => $importer['channel_hash'],
				'link'         => z_root() . '/connedit/' . $new_connection[0]['abook_id'],
			));

			if($default_perms) {
				// Send back a sharing notification to them
				diaspora_share($importer,$new_connection[0]);
		
			}
		}
	}

	// find the abook record we just created

	$contact_record = diaspora_get_contact_by_handle($importer['channel_id'],$sender_handle);

	if(! $contact_record) {
		logger('diaspora_request: unable to locate newly created contact record.');
		return;
	}

	/** If there is a default group for this channel, add this member to it */

	if($importer['channel_default_group']) {
		require_once('include/group.php');
		$g = group_rec_byhash($importer['channel_id'],$importer['channel_default_group']);
		if($g)
			group_add_member($importer['channel_id'],'',$contact_record['xchan_hash'],$g['id']);
	}

	return;
}



function diaspora_post($importer,$xml,$msg) {

	$a = get_app();
	$guid = notags(unxmlify($xml->guid));
	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));
	$app = notags(xmlify($xml->provider_display_name));


	if($diaspora_handle != $msg['author']) {
		logger('diaspora_post: Potential forgery. Message handle is not the same as envelope sender.');
		return 202;
	}

	$contact = diaspora_get_contact_by_handle($importer['channel_id'],$diaspora_handle);
	if(! $contact)
		return;



	if(! $app) {
		if(strstr($contact['xchan_network'],'friendica'))
			$app = 'Friendica';
		else
			$app = 'Diaspora';
	}


	if((! $importer['system']) && (! perm_is_allowed($importer['channel_id'],$contact['xchan_hash'],'send_stream'))) {
		logger('diaspora_post: Ignoring this author.');
		return 202;
	}

	$search_guid = ((strlen($guid) == 64) ? $guid . '%' : $guid);

	$r = q("SELECT id FROM item WHERE uid = %d AND mid like '%s' LIMIT 1",
		intval($importer['channel_id']),
		dbesc($search_guid)
	);

	if($r) {
		// check dates if post editing is implemented
		logger('diaspora_post: message exists: ' . $guid);
		return;
	}

	$created = unxmlify($xml->created_at);
	$private = ((unxmlify($xml->public) == 'false') ? 1 : 0);

	$body = diaspora2bb($xml->raw_message);

	if($xml->photo) {
		$body = '[img]' . $xml->photo->remote_photo_path . $xml->photo->remote_photo_name . '[/img]' . "\n\n" . $body;
		$body = scale_external_images($body);
	}

	$maxlen = get_max_import_size();

	if($maxlen && mb_strlen($body) > $maxlen) {
		$body = mb_substr($body,0,$maxlen,'UTF-8');
		logger('message length exceeds max_import_size: truncated');
	}

//WTF? FIXME
	// Add OEmbed and other information to the body
//	$body = add_page_info_to_body($body, false, true);

	$datarray = array();

	
	// Look for tags and linkify them
	$results = linkify_tags(get_app(), $body, $importer['channel_id']);

	if($results) {
		$datarray['term'] = array();
		foreach($results as $result) {
			$success = $result['success'];
			if($success['replaced']) {
				$datarray['term'][] = array(
					'uid'   => $importer['channel_id'],
					'type'  => $success['termtype'],
					'otype' => TERM_OBJ_POST,
					'term'  => $success['term'],
					'url'   => $success['url']
				);
			}
		}
	}

	$plink = service_plink($contact,$guid);


	$datarray['uid'] = $importer['channel_id'];

	$datarray['verb'] = ACTIVITY_POST;
	$datarray['mid'] = $datarray['parent_mid'] = $guid;

	$datarray['changed'] = $datarray['created'] = $datarray['edited'] = datetime_convert('UTC','UTC',$created);
	$datarray['item_private'] = $private;

	$datarray['plink'] = $plink;

	$datarray['author_xchan'] = $contact['xchan_hash'];
	$datarray['owner_xchan']  = $contact['xchan_hash'];

	$datarray['body'] = $body;

	$datarray['app']  = $app;

	$datarray['item_flags'] = ITEM_THREAD_TOP;
	$datarray['item_unseen'] = 1;

	$result = item_store($datarray);
	return;

}


function get_diaspora_reshare_xml($url,$recurse = 0) {

	$x = z_fetch_url($url);
	if(! $x['success'])
		$x = z_fetch_url(str_replace('https://','http://',$url));
	if(! $x['success']) {
		logger('get_diaspora_reshare_xml: unable to fetch source url ' . $url);
		return;
	}
	logger('get_diaspora_reshare_xml: source: ' . $x['body'], LOGGER_DEBUG);

	$source_xml = parse_xml_string($x['body'],false);

	if(! $source_xml) {
		logger('get_diaspora_reshare_xml: unparseable result from ' . $url);
		return '';
	}

	if($source_xml->post->status_message) {
		return $source_xml;
	}

	// see if it's a reshare of a reshare

	if($source_xml->post->reshare)
		$xml = $source_xml->post->reshare;
	else 
		return false;

	if($xml->root_diaspora_id && $xml->root_guid && $recurse < 15) {
		$orig_author = notags(unxmlify($xml->root_diaspora_id));
		$orig_guid = notags(unxmlify($xml->root_guid));
		$source_url = 'https://' . substr($orig_author,strpos($orig_author,'@')+1) . '/p/' . $orig_guid . '.xml';
		$y = get_diaspora_reshare_xml($source_url,$recurse+1);
		if($y)
			return $y;
	}
	return false;
}



function diaspora_reshare($importer,$xml,$msg) {

	logger('diaspora_reshare: init: ' . print_r($xml,true), LOGGER_DATA);

	$a = get_app();
	$guid = notags(unxmlify($xml->guid));
	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));


	if($diaspora_handle != $msg['author']) {
		logger('diaspora_post: Potential forgery. Message handle is not the same as envelope sender.');
		return 202;
	}

	$contact = diaspora_get_contact_by_handle($importer['channel_id'],$diaspora_handle);
	if(! $contact)
		return;

	if((! $importer['system']) && (! perm_is_allowed($importer['channel_id'],$contact['xchan_hash'],'send_stream'))) {
		logger('diaspora_reshare: Ignoring this author: ' . $diaspora_handle . ' ' . print_r($xml,true));
		return 202;
	}

	$search_guid = ((strlen($guid) == 64) ? $guid . '%' : $guid);
	$r = q("SELECT id FROM item WHERE uid = %d AND mid like '%s' LIMIT 1",
		intval($importer['channel_id']),
		dbesc($search_guid)
	);
	if($r) {
		logger('diaspora_reshare: message exists: ' . $guid);
		return;
	}

	$orig_author = notags(unxmlify($xml->root_diaspora_id));
	$orig_guid = notags(unxmlify($xml->root_guid));

	$source_url = 'https://' . substr($orig_author,strpos($orig_author,'@')+1) . '/p/' . $orig_guid . '.xml';
	$orig_url = 'https://'.substr($orig_author,strpos($orig_author,'@')+1).'/posts/'.$orig_guid;

	$source_xml = get_diaspora_reshare_xml($source_url);

	if($source_xml->post->status_message) {
		$body = diaspora2bb($source_xml->post->status_message->raw_message);

		$orig_author = notags(unxmlify($source_xml->post->status_message->diaspora_handle));
		$orig_guid = notags(unxmlify($source_xml->post->status_message->guid));


		// Checking for embedded pictures
		if($source_xml->post->status_message->photo->remote_photo_path &&
			$source_xml->post->status_message->photo->remote_photo_name) {

			$remote_photo_path = notags(unxmlify($source_xml->post->status_message->photo->remote_photo_path));
			$remote_photo_name = notags(unxmlify($source_xml->post->status_message->photo->remote_photo_name));

			$body = '[img]'.$remote_photo_path.$remote_photo_name.'[/img]'."\n".$body;

			logger('diaspora_reshare: embedded picture link found: '.$body, LOGGER_DEBUG);
		}

		$body = scale_external_images($body);

		// Add OEmbed and other information to the body
//		$body = add_page_info_to_body($body, false, true);
	}
	else {
		// Maybe it is a reshare of a photo that will be delivered at a later time (testing)
		logger('diaspora_reshare: no reshare content found: ' . print_r($source_xml,true));
		$body = "";
		//return;
	}

	$maxlen = get_max_import_size();

	if($maxlen && mb_strlen($body) > $maxlen) {
		$body = mb_substr($body,0,$maxlen,'UTF-8');
		logger('message length exceeds max_import_size: truncated');
	}

	$person = find_diaspora_person_by_handle($orig_author);

	if($person) {
		$orig_author_name = $person['xchan_name'];
		$orig_author_link = $person['xchan_url'];
		$orig_author_photo = $person['xchan_photo_m'];
	}


	$created = unxmlify($xml->created_at);
	$private = ((unxmlify($xml->public) == 'false') ? 1 : 0);

	$datarray = array();

	// Look for tags and linkify them
	$results = linkify_tags(get_app(), $body, $importer['channel_id']);

	if($results) {
		$datarray['term'] = array();
		foreach($results as $result) {
			$success = $result['success'];
			if($success['replaced']) {
				$datarray['term'][] = array(
					'uid'   => $importer['channel_id'],
					'type'  => $success['termtype'],
					'otype' => TERM_OBJ_POST,
					'term'  => $success['term'],
					'url'   => $success['url']
				);
			}
		}
	}

	$newbody = "[share author='" . urlencode($orig_author_name) 
		. "' profile='" . $orig_author_link 
		. "' avatar='" . $orig_author_photo 
		. "' link='" . $orig_url
		. "' posted='" . datetime_convert('UTC','UTC',unxmlify($source_xml->post->status_message->created_at))
		. "' message_id='" . unxmlify($source_xml->post->status_message->guid)
 		. "']" . $body . "[/share]";


	$plink = service_plink($contact,$guid);

	$datarray['uid'] = $importer['channel_id'];
	$datarray['mid'] = $datarray['parent_mid'] = $guid;
	$datarray['changed'] = $datarray['created'] = $datarray['edited'] = datetime_convert('UTC','UTC',$created);
	$datarray['item_private'] = $private;
	$datarray['plink'] = $plink;
	$datarray['owner_xchan'] = $contact['xchan_hash'];
	$datarray['author_xchan'] = $contact['xchan_hash'];

	$datarray['body'] = $newbody;
	$datarray['app']  = 'Diaspora';


	$result = item_store($datarray);

	return;

}


function diaspora_asphoto($importer,$xml,$msg) {
	logger('diaspora_asphoto called');

	$a = get_app();
	$guid = notags(unxmlify($xml->guid));
	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));

	if($diaspora_handle != $msg['author']) {
		logger('diaspora_post: Potential forgery. Message handle is not the same as envelope sender.');
		return 202;
	}

	$contact = diaspora_get_contact_by_handle($importer['channel_id'],$diaspora_handle);
	if(! $contact)
		return;

	if((! $importer['system']) && (! perm_is_allowed($importer['channel_id'],$contact['xchan_hash'],'send_stream'))) {
		logger('diaspora_asphoto: Ignoring this author.');
		return 202;
	}

	$message_id = $diaspora_handle . ':' . $guid;
	$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `uri` = '%s' AND `guid` = '%s' LIMIT 1",
		intval($importer['channel_id']),
		dbesc($message_id),
		dbesc($guid)
	);
	if(count($r)) {
		logger('diaspora_asphoto: message exists: ' . $guid);
		return;
	}

	// allocate a guid on our system - we aren't fixing any collisions.
	// we're ignoring them

	$g = q("select * from guid where guid = '%s' limit 1",
		dbesc($guid)
	);
	if(! count($g)) {
		q("insert into guid ( guid ) values ( '%s' )",
			dbesc($guid)
		);
	}

	$created = unxmlify($xml->created_at);
	$private = ((unxmlify($xml->public) == 'false') ? 1 : 0);

	if(strlen($xml->objectId) && ($xml->objectId != 0) && ($xml->image_url)) {
		$body = '[url=' . notags(unxmlify($xml->image_url)) . '][img]' . notags(unxmlify($xml->objectId)) . '[/img][/url]' . "\n";
		$body = scale_external_images($body,false);
	}
	elseif($xml->image_url) {
		$body = '[img]' . notags(unxmlify($xml->image_url)) . '[/img]' . "\n";
		$body = scale_external_images($body);
	}
	else {
		logger('diaspora_asphoto: no photo url found.');
		return;
	}

	$plink = service_plink($contact,$guid);

	$datarray = array();

	$datarray['uid'] = $importer['channel_id'];
	$datarray['contact-id'] = $contact['id'];
	$datarray['wall'] = 0;
	$datarray['network']  = NETWORK_DIASPORA;
	$datarray['guid'] = $guid;
	$datarray['uri'] = $datarray['parent-uri'] = $message_id;
	$datarray['changed'] = $datarray['created'] = $datarray['edited'] = datetime_convert('UTC','UTC',$created);
	$datarray['private'] = $private;
	$datarray['parent'] = 0;
	$datarray['plink'] = $plink;
	$datarray['owner-name'] = $contact['name'];
	$datarray['owner-link'] = $contact['url'];
	//$datarray['owner-avatar'] = $contact['thumb'];
	$datarray['owner-avatar'] = ((x($contact,'thumb')) ? $contact['thumb'] : $contact['photo']);
	$datarray['author-name'] = $contact['name'];
	$datarray['author-link'] = $contact['url'];
	$datarray['author-avatar'] = $contact['thumb'];
	$datarray['body'] = $body;

	$datarray['app']  = 'Diaspora/Cubbi.es';

	$message_id = item_store($datarray);

	//if($message_id) {
	//	q("update item set plink = '%s' where id = %d",
	//		dbesc($a->get_baseurl() . '/display/' . $importer['nickname'] . '/' . $message_id),
	//		intval($message_id)
	//	);
	//}

	return;

}






function diaspora_comment($importer,$xml,$msg) {

	$a = get_app();
	$guid = notags(unxmlify($xml->guid));
	$parent_guid = notags(unxmlify($xml->parent_guid));
	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));
	$target_type = notags(unxmlify($xml->target_type));
	$text = unxmlify($xml->text);
	$author_signature = notags(unxmlify($xml->author_signature));

	$parent_author_signature = (($xml->parent_author_signature) ? notags(unxmlify($xml->parent_author_signature)) : '');

	$contact = diaspora_get_contact_by_handle($importer['channel_id'],$msg['author']);
	if(! $contact) {
		logger('diaspora_comment: cannot find contact: ' . $msg['author']);
		return;
	}


	
	$pubcomment = get_pconfig($importer['channel_id'],'system','diaspora_public_comments');

	// by default comments on public posts are allowed from anybody on Diaspora. That is their policy.
	// Once this setting is set to something we'll track your preference and it will over-ride the default. 

	if($pubcomment === false)
		$pubcomment = 1;

	// Friendica is currently truncating guids at 64 chars
	$search_guid = $parent_guid;
	if(strlen($parent_guid) == 64)
		$search_guid = $parent_guid . '%';

	$r = q("SELECT * FROM item WHERE uid = %d AND mid LIKE '%s' LIMIT 1",
		intval($importer['channel_id']),
		dbesc($search_guid)
	);
	if(! $r) {
		logger('diaspora_comment: parent item not found: parent: ' . $parent_guid . ' item: ' . $guid);
		return;
	}

	$parent_item = $r[0];

	if(intval($parent_item['item_private']))
		$pubcomment = 0;	

	// So basically if something arrives at the sys channel it's by definition public and we allow it.
	// If $pubcomment and the parent was public, we allow it.
	// In all other cases, honour the permissions for this Diaspora connection

	if((! $importer['system']) && (! $pubcomment) && (! perm_is_allowed($importer['channel_id'],$contact['xchan_hash'],'post_comments'))) {
		logger('diaspora_comment: Ignoring this author.');
		return 202;
	}

	$search_guid = $guid;
	if(strlen($guid) == 64)
		$search_guid = $guid . '%';


	$r = q("SELECT * FROM item WHERE uid = %d AND mid like '%s' LIMIT 1",
		intval($importer['channel_id']),
		dbesc($search_guid)
	);
	if($r) {
		logger('diaspora_comment: our comment just got relayed back to us (or there was a guid collision) : ' . $guid);
		return;
	}



	/* How Diaspora performs comment signature checking:

	   - If an item has been sent by the comment author to the top-level post owner to relay on
	     to the rest of the contacts on the top-level post, the top-level post owner should check
	     the author_signature, then create a parent_author_signature before relaying the comment on
	   - If an item has been relayed on by the top-level post owner, the contacts who receive it
	     check only the parent_author_signature. Basically, they trust that the top-level post
	     owner has already verified the authenticity of anything he/she sends out
	   - In either case, the signature that get checked is the signature created by the person
	     who sent the psuedo-salmon
	*/

	$signed_data = $guid . ';' . $parent_guid . ';' . $text . ';' . $diaspora_handle;
	$key = $msg['key'];

	if($parent_author_signature) {
		// If a parent_author_signature exists, then we've received the comment
		// relayed from the top-level post owner. There's no need to check the
		// author_signature if the parent_author_signature is valid

		$parent_author_signature = base64_decode($parent_author_signature);

		if(! rsa_verify($signed_data,$parent_author_signature,$key,'sha256')) {
			logger('diaspora_comment: top-level owner verification failed.');
			return;
		}
	}
	else {
		// If there's no parent_author_signature, then we've received the comment
		// from the comment creator. In that case, the person is commenting on
		// our post, so he/she must be a contact of ours and his/her public key
		// should be in $msg['key']

		$author_signature = base64_decode($author_signature);

		if(! rsa_verify($signed_data,$author_signature,$key,'sha256')) {
			logger('diaspora_comment: comment author verification failed.');
			return;
		}
	}

	// Phew! Everything checks out. Now create an item.

	// Find the original comment author information.
	// We need this to make sure we display the comment author
	// information (name and avatar) correctly.

	if(strcasecmp($diaspora_handle,$msg['author']) == 0)
		$person = $contact;
	else {
		$person = find_diaspora_person_by_handle($diaspora_handle);

		if(! is_array($person)) {
			logger('diaspora_comment: unable to find author details');
			return;
		}
	}


	$body = diaspora2bb($text);


	$maxlen = get_max_import_size();

	if($maxlen && mb_strlen($body) > $maxlen) {
		$body = mb_substr($body,0,$maxlen,'UTF-8');
		logger('message length exceeds max_import_size: truncated');
	}


	$datarray = array();

	// Look for tags and linkify them
	$results = linkify_tags(get_app(), $body, $importer['channel_id']);

	if($results) {
		$datarray['term'] = array();
		foreach($results as $result) {
			$success = $result['success'];
			if($success['replaced']) {
				$datarray['term'][] = array(
					'uid'   => $importer['channel_id'],
					'type'  => $success['termtype'],
					'otype' => TERM_OBJ_POST,
					'term'  => $success['term'],
					'url'   => $success['url']
				);
			}
		}
	}

	$datarray['uid'] = $importer['channel_id'];
	$datarray['verb'] = ACTIVITY_POST;
	$datarray['mid'] = $guid;
	$datarray['parent_mid'] = $parent_item['mid'];

	// set the route to that of the parent so downstream hubs won't reject it.
	$datarray['route'] = $parent_item['route'];

	// No timestamps for comments? OK, we'll the use current time.
	$datarray['changed'] = $datarray['created'] = $datarray['edited'] = datetime_convert();
	$datarray['item_private'] = $parent_item['item_private'];

	$datarray['owner_xchan'] = $parent_item['owner_xchan'];
	$datarray['author_xchan'] = $person['xchan_hash'];

	$datarray['body'] = $body;

	if(strstr($person['xchan_network'],'friendica'))
		$app = 'Friendica';
	else
		$app = 'Diaspora';

	$datarray['app']  = $app;
	
	if(! $parent_author_signature) {
		$key = get_config('system','pubkey');
		$x = array('signer' => $diaspora_handle, 'body' => $text, 
			'signed_text' => $signed_data, 'signature' => base64_encode($author_signature));
		$datarray['diaspora_meta'] = json_encode(crypto_encapsulate(json_encode($x),$key));
	}

	$result = item_store($datarray);

	if($result && $result['success'])
		$message_id = $result['item_id'];

	if(($parent_item['item_flags'] & ITEM_ORIGIN) && (! $parent_author_signature)) {
		// if the message isn't already being relayed, notify others
		// the existence of parent_author_signature means the parent_author or owner
		// is already relaying.

		proc_run('php','include/notifier.php','comment-import',$message_id);
	}

	if($result['item_id']) {
		$r = q("select * from item where id = %d limit 1",
			intval($result['item_id'])
		);
		if($r)
			send_status_notifications($result['item_id'],$r[0]);
	}

	return;
}




function diaspora_conversation($importer,$xml,$msg) {

	$a = get_app();

	$guid = notags(unxmlify($xml->guid));
	$subject = notags(unxmlify($xml->subject));
	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));
	$participant_handles = notags(unxmlify($xml->participant_handles));
	$created_at = datetime_convert('UTC','UTC',notags(unxmlify($xml->created_at)));

	$parent_uri = $diaspora_handle . ':' . $guid;
 
	$messages = $xml->message;

	if(! count($messages)) {
		logger('diaspora_conversation: empty conversation');
		return;
	}

	$contact = diaspora_get_contact_by_handle($importer['channel_id'],$msg['author']);
	if(! $contact) {
		logger('diaspora_conversation: cannot find contact: ' . $msg['author']);
		return;
	}


	if(! perm_is_allowed($importer['channel_id'],$contact['xchan_hash'],'post_mail')) {
		logger('diaspora_conversation: Ignoring this author.');
		return 202;
	}

	$conversation = null;

	$c = q("select * from conv where uid = %d and guid = '%s' limit 1",
		intval($importer['channel_id']),
		dbesc($guid)
	);
	if(count($c))
		$conversation = $c[0];
	else {
		$r = q("insert into conv (uid,guid,creator,created,updated,subject,recips) values(%d, '%s', '%s', '%s', '%s', '%s', '%s') ",
			intval($importer['channel_id']),
			dbesc($guid),
			dbesc($diaspora_handle),
			dbesc(datetime_convert('UTC','UTC',$created_at)),
			dbesc(datetime_convert()),
			dbesc($subject),
			dbesc($participant_handles)
		);
		if($r)
			$c = q("select * from conv where uid = %d and guid = '%s' limit 1",
			intval($importer['channel_id']),
			dbesc($guid)
		);
		if(count($c))
			$conversation = $c[0];
	}
	if(! $conversation) {
		logger('diaspora_conversation: unable to create conversation.');
		return;
	}

	foreach($messages as $mesg) {

		$reply = 0;

		$msg_guid = notags(unxmlify($mesg->guid));
		$msg_parent_guid = notags(unxmlify($mesg->parent_guid));
		$msg_parent_author_signature = notags(unxmlify($mesg->parent_author_signature));
		$msg_author_signature = notags(unxmlify($mesg->author_signature));
		$msg_text = unxmlify($mesg->text);
		$msg_created_at = datetime_convert('UTC','UTC',notags(unxmlify($mesg->created_at)));
		$msg_diaspora_handle = notags(unxmlify($mesg->diaspora_handle));
		$msg_conversation_guid = notags(unxmlify($mesg->conversation_guid));
		if($msg_conversation_guid != $guid) {
			logger('diaspora_conversation: message conversation guid does not belong to the current conversation. ' . $xml);
			continue;
		}

		$body = diaspora2bb($msg_text);


		$maxlen = get_max_import_size();

		if($maxlen && mb_strlen($body) > $maxlen) {
			$body = mb_substr($body,0,$maxlen,'UTF-8');
			logger('message length exceeds max_import_size: truncated');
		}


		$author_signed_data = $msg_guid . ';' . $msg_parent_guid . ';' . $msg_text . ';' . unxmlify($mesg->created_at) . ';' . $msg_diaspora_handle . ';' . $msg_conversation_guid;

		$author_signature = base64_decode($msg_author_signature);

		if(strcasecmp($msg_diaspora_handle,$msg['author']) == 0) {
			$person = $contact;
			$key = $msg['key'];
		}
		else {
			$person = find_diaspora_person_by_handle($msg_diaspora_handle);	

			if(is_array($person) && x($person,'xchan_pubkey'))
				$key = $person['xchan_pubkey'];
			else {
				logger('diaspora_conversation: unable to find author details');
				continue;
			}
		}

		if(! rsa_verify($author_signed_data,$author_signature,$key,'sha256')) {
			logger('diaspora_conversation: verification failed.');
			continue;
		}

		if($msg_parent_author_signature) {
			$owner_signed_data = $msg_guid . ';' . $msg_parent_guid . ';' . $msg_text . ';' . unxmlify($mesg->created_at) . ';' . $msg_diaspora_handle . ';' . $msg_conversation_guid;

			$parent_author_signature = base64_decode($msg_parent_author_signature);

			$key = $msg['key'];

			if(! rsa_verify($owner_signed_data,$parent_author_signature,$key,'sha256')) {
				logger('diaspora_conversation: owner verification failed.');
				continue;
			}
		}

		$r = q("select id from mail where mid = '%s' limit 1",
			dbesc($message_id)
		);
		if(count($r)) {
			logger('diaspora_conversation: duplicate message already delivered.', LOGGER_DEBUG);
			continue;
		}

		$key = get_config('system','pubkey');
		if($subject)
			$subject = json_encode(crypto_encapsulate($subject,$key));
		if($body)
			$body  = json_encode(crypto_encapsulate($body,$key));

		q("insert into mail ( `channel_id`, `convid`, `from_xchan`,`to_xchan`,`title`,`body`,`mail_flags`,`mid`,`parent_mid`,`created`) values ( %d, %d, '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s')",
			intval($importer['channel_id']),
			intval($conversation['id']),
			dbesc($person['xchan_hash']),
			dbesc($importer['channel_hash']),
			dbesc($subject),
			dbesc($body),
			intval(MAIL_OBSCURED),
			dbesc($msg_guid),
			dbesc($parent_uri),
			dbesc($msg_created_at)
		);

		q("update conv set updated = '%s' where id = %d",
			dbesc(datetime_convert()),
			intval($conversation['id'])
		);

		require_once('include/enotify.php');
/******
//FIXME

		notification(array(
			'type' => NOTIFY_MAIL,
			'notify_flags' => $importer['notify-flags'],
			'language' => $importer['language'],
			'to_name' => $importer['username'],
			'to_email' => $importer['email'],
			'uid' =>$importer['importer_uid'],
			'item' => array('subject' => $subject, 'body' => $body),
			'source_name' => $person['name'],
			'source_link' => $person['url'],
			'source_photo' => $person['thumb'],
			'verb' => ACTIVITY_POST,
			'otype' => 'mail'
		));
*******/

	}

	return;
}

function diaspora_message($importer,$xml,$msg) {

	$a = get_app();

	$msg_guid = notags(unxmlify($xml->guid));
	$msg_parent_guid = notags(unxmlify($xml->parent_guid));
	$msg_parent_author_signature = notags(unxmlify($xml->parent_author_signature));
	$msg_author_signature = notags(unxmlify($xml->author_signature));
	$msg_text = unxmlify($xml->text);
	$msg_created_at = datetime_convert('UTC','UTC',notags(unxmlify($xml->created_at)));
	$msg_diaspora_handle = notags(unxmlify($xml->diaspora_handle));
	$msg_conversation_guid = notags(unxmlify($xml->conversation_guid));

	$parent_uri = $msg_parent_guid;
 
	$contact = diaspora_get_contact_by_handle($importer['channel_id'],$msg_diaspora_handle);
	if(! $contact) {
		logger('diaspora_message: cannot find contact: ' . $msg_diaspora_handle);
		return;
	}

	if(($contact['rel'] == CONTACT_IS_FOLLOWER) || ($contact['blocked']) || ($contact['readonly'])) { 
		logger('diaspora_message: Ignoring this author.');
		return 202;
	}

	$conversation = null;

	$c = q("select * from conv where uid = %d and guid = '%s' limit 1",
		intval($importer['channel_id']),
		dbesc($msg_conversation_guid)
	);
	if($c)
		$conversation = $c[0];
	else {
		logger('diaspora_message: conversation not available.');
		return;
	}

	$reply = 0;

	$subject = $conversation['subject']; 
	$body = diaspora2bb($msg_text);


	$maxlen = get_max_import_size();

	if($maxlen && mb_strlen($body) > $maxlen) {
		$body = mb_substr($body,0,$maxlen,'UTF-8');
		logger('message length exceeds max_import_size: truncated');
	}



	$message_id = $msg_diaspora_handle . ':' . $msg_guid;

	$author_signed_data = $msg_guid . ';' . $msg_parent_guid . ';' . $msg_text . ';' . unxmlify($xml->created_at) . ';' . $msg_diaspora_handle . ';' . $msg_conversation_guid;


	$author_signature = base64_decode($msg_author_signature);

	$person = find_diaspora_person_by_handle($msg_diaspora_handle);	
	if(is_array($person) && x($person,'xchan_pubkey'))
		$key = $person['xchan_pubkey'];
	else {
		logger('diaspora_message: unable to find author details');
		return;
	}

	if(! rsa_verify($author_signed_data,$author_signature,$key,'sha256')) {
		logger('diaspora_message: verification failed.');
		return;
	}

	$r = q("select id from mail where mid = '%s' and channel_id = %d limit 1",
		dbesc($message_id),
		intval($importer['channel_id'])
	);
	if($r) {
		logger('diaspora_message: duplicate message already delivered.', LOGGER_DEBUG);
		return;
	}

	$key = get_config('system','pubkey');
	if($subject)
		$subject = json_encode(crypto_encapsulate($subject,$key));
	if($body)
		$body  = json_encode(crypto_encapsulate($body,$key));

	q("insert into mail ( `channel_id`, `convid`, `from_xchan`,`to_xchan`,`title`,`body`,`mail_flags`,`mid`,`parent_mid`,`created`) values ( %d, %d, '%s', '%s', '%s', '%s', '%d','%s','%s','%s')",
		intval($importer['channel_id']),
		intval($conversation['id']),
		dbesc($person['xchan_hash']),
		dbesc($importer['xchan_hash']),
		dbesc($subject),
		dbesc($body),
		intval(MAIL_OBSCURED),
		dbesc($msg_guid),
		dbesc($parent_uri),
		dbesc($msg_created_at)
	);

	q("update conv set updated = '%s' where id = %d",
		dbesc(datetime_convert()),
		intval($conversation['id'])
	);

	return;
}


function diaspora_photo($importer,$xml,$msg) {

	$a = get_app();

	logger('diaspora_photo: init',LOGGER_DEBUG);

	$remote_photo_path = notags(unxmlify($xml->remote_photo_path));

	$remote_photo_name = notags(unxmlify($xml->remote_photo_name));

	$status_message_guid = notags(unxmlify($xml->status_message_guid));

	$guid = notags(unxmlify($xml->guid));

	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));

	$public = notags(unxmlify($xml->public));

	$created_at = notags(unxmlify($xml_created_at));

	logger('diaspora_photo: status_message_guid: ' . $status_message_guid, LOGGER_DEBUG);

	$contact = diaspora_get_contact_by_handle($importer['channel_id'],$msg['author']);
	if(! $contact) {
		logger('diaspora_photo: contact record not found: ' . $msg['author'] . ' handle: ' . $diaspora_handle);
		return;
	}

	if((! $importer['system']) && (! perm_is_allowed($importer['channel_id'],$contact['xchan_hash'],'send_stream'))) {
		logger('diaspora_photo: Ignoring this author.');
		return 202;
	}

	$r = q("SELECT * FROM `item` WHERE `uid` = %d AND `mid` = '%s' LIMIT 1",
		intval($importer['channel_id']),
		dbesc($status_message_guid)
	);
	if(! $r) {
		logger('diaspora_photo: attempt = ' . $attempt . '; status message not found: ' . $status_message_guid . ' for photo: ' . $guid);
		return;
	}

//	$parent_item = $r[0];

//	$link_text = '[img]' . $remote_photo_path . $remote_photo_name . '[/img]' . "\n";

//	$link_text = scale_external_images($link_text, true,
//									   array($remote_photo_name, 'scaled_full_' . $remote_photo_name));

//	if(strpos($parent_item['body'],$link_text) === false) {
//		$r = q("update item set `body` = '%s', `visible` = 1 where `id` = %d and `uid` = %d",
//			dbesc($link_text . $parent_item['body']),
//			intval($parent_item['id']),
//			intval($parent_item['uid'])
//		);
//	}

	return;
}




function diaspora_like($importer,$xml,$msg) {

	$a = get_app();
	$guid = notags(unxmlify($xml->guid));
	$parent_guid = notags(unxmlify($xml->parent_guid));
	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));
	$target_type = notags(unxmlify($xml->target_type));
	$positive = notags(unxmlify($xml->positive));
	$author_signature = notags(unxmlify($xml->author_signature));

	$parent_author_signature = (($xml->parent_author_signature) ? notags(unxmlify($xml->parent_author_signature)) : '');

	// likes on comments not supported here and likes on photos not supported by Diaspora

//	if($target_type !== 'Post')
//		return;

	$contact = diaspora_get_contact_by_handle($importer['channel_id'],$msg['author']);
	if(! $contact) {
		logger('diaspora_like: cannot find contact: ' . $msg['author'] . ' for channel ' . $importer['channel_name']);
		return;
	}


	if((! $importer['system']) && (! perm_is_allowed($importer['channel_id'],$contact['xchan_hash'],'post_comments'))) {
		logger('diaspora_like: Ignoring this author.');
		return 202;
	}

	$r = q("SELECT * FROM `item` WHERE `uid` = %d AND `mid` = '%s' LIMIT 1",
		intval($importer['channel_id']),
		dbesc($parent_guid)
	);
	if(! count($r)) {
		logger('diaspora_like: parent item not found: ' . $guid);
		return;
	}

	$parent_item = $r[0];

	$r = q("SELECT * FROM `item` WHERE `uid` = %d AND `mid` = '%s' LIMIT 1",
		intval($importer['channel_id']),
		dbesc($guid)
	);
	if(count($r)) {
		if($positive === 'true') {
			logger('diaspora_like: duplicate like: ' . $guid);
			return;
		}
		// Note: I don't think "Like" objects with positive = "false" are ever actually used
		// It looks like "RelayableRetractions" are used for "unlike" instead
		if($positive === 'false') {
			logger('diaspora_like: received a like with positive set to "false"...ignoring');
			// perhaps call drop_item()
			// FIXME--actually don't unless it turns out that Diaspora does indeed send out "false" likes
			//  send notification via proc_run()
			return;
		}
	}

	$i = q("select * from xchan where xchan_hash = '%s' limit 1",
		dbesc($parent_item['author_xchan'])
	);
	if($i)
		$item_author = $i[0];

	// Note: I don't think "Like" objects with positive = "false" are ever actually used
	// It looks like "RelayableRetractions" are used for "unlike" instead
	if($positive === 'false') {
		logger('diaspora_like: received a like with positive set to "false"');
		logger('diaspora_like: unlike received with no corresponding like...ignoring');
		return;	
	}


	/* How Diaspora performs "like" signature checking:

	   - If an item has been sent by the like author to the top-level post owner to relay on
	     to the rest of the contacts on the top-level post, the top-level post owner should check
	     the author_signature, then create a parent_author_signature before relaying the like on
	   - If an item has been relayed on by the top-level post owner, the contacts who receive it
	     check only the parent_author_signature. Basically, they trust that the top-level post
	     owner has already verified the authenticity of anything he/she sends out
	   - In either case, the signature that get checked is the signature created by the person
	     who sent the salmon
	*/

	// 2014-09-10 let's try this: signatures are failing. I'll try and make a signable string from
	// the parameters in the order they were presented in the post. This is how D* creates the signable string.


	$signed_data = $positive . ';' . $guid . ';' . $target_type . ';' . $parent_guid . ';' . $diaspora_handle;

	$key = $msg['key'];

	if($parent_author_signature) {
		// If a parent_author_signature exists, then we've received the like
		// relayed from the top-level post owner. There's no need to check the
		// author_signature if the parent_author_signature is valid

		$parent_author_signature = base64_decode($parent_author_signature);

		if(! rsa_verify($signed_data,$parent_author_signature,$key,'sha256')) {
			if (intval(get_config('system','ignore_diaspora_like_signature')))
				logger('diaspora_like: top-level owner verification failed. Proceeding anyway.');
			else {
				logger('diaspora_like: top-level owner verification failed.');
				return;
			}
		}
	}
	else {
		// If there's no parent_author_signature, then we've received the like
		// from the like creator. In that case, the person is "like"ing
		// our post, so he/she must be a contact of ours and his/her public key
		// should be in $msg['key']

		$author_signature = base64_decode($author_signature);

		if(! rsa_verify($signed_data,$author_signature,$key,'sha256')) {
			if (intval(get_config('system','ignore_diaspora_like_signature')))
				logger('diaspora_like: like creator verification failed. Proceeding anyway');
			else {
				logger('diaspora_like: like creator verification failed.');
				return;
			}
		}
	}
	
	logger('diaspora_like: signature check complete.',LOGGER_DEBUG);

	// Phew! Everything checks out. Now create an item.

	// Find the original comment author information.
	// We need this to make sure we display the comment author
	// information (name and avatar) correctly.
	if(strcasecmp($diaspora_handle,$msg['author']) == 0)
		$person = $contact;
	else {
		$person = find_diaspora_person_by_handle($diaspora_handle);

		if(! is_array($person)) {
			logger('diaspora_like: unable to find author details');
			return;
		}
	}

	$uri = $diaspora_handle . ':' . $guid;

	$activity = ACTIVITY_LIKE;

	$post_type = (($parent_item['resource_type'] === 'photo') ? t('photo') : t('status'));

	$links = array(array('rel' => 'alternate','type' => 'text/html', 'href' => $parent_item['plink']));
	$objtype = (($parent_item['resource_type'] === 'photo') ? ACTIVITY_OBJ_PHOTO : ACTIVITY_OBJ_NOTE );

	$body = $parent_item['body'];


	$object = json_encode(array(
		'type'    => $post_type,
		'id'	  => $parent_item['mid'],
		'parent'  => (($parent_item['thr_parent']) ? $parent_item['thr_parent'] : $parent_item['parent_mid']),
		'link'	=> $links,
		'title'   => $parent_item['title'],
		'content' => $parent_item['body'],
		'created' => $parent_item['created'],
		'edited'  => $parent_item['edited'],
		'author'  => array(
			'name'	 => $item_author['xchan_name'],
			'address'  => $item_author['xchan_addr'],
			'guid'	 => $item_author['xchan_guid'],
			'guid_sig' => $item_author['xchan_guid_sig'],
			'link'	 => array(
				array('rel' => 'alternate', 'type' => 'text/html', 'href' => $item_author['xchan_url']),
				array('rel' => 'photo', 'type' => $item_author['xchan_photo_mimetype'], 'href' => $item_author['xchan_photo_m'])),
			),
		));


	$bodyverb = t('%1$s likes %2$s\'s %3$s');

	$arr = array();

	$arr['uid'] = $importer['channel_id'];
	$arr['aid'] = $importer['channel_account_id'];
	$arr['mid'] = $guid;
	$arr['parent_mid'] = $parent_item['mid'];
	$arr['owner_xchan'] = $parent_item['owner_xchan'];
	$arr['author_xchan'] = $person['xchan_hash'];

	$ulink = '[url=' . $contact['url'] . ']' . $contact['name'] . '[/url]';
	$alink = '[url=' . $parent_item['author-link'] . ']' . $parent_item['author-name'] . '[/url]';
	$plink = '[url='. z_root() .'/display/'.$guid.']'.$post_type.'[/url]';
	$arr['body'] =  sprintf( $bodyverb, $ulink, $alink, $plink );

	$arr['app']  = 'Diaspora';

	// set the route to that of the parent so downstream hubs won't reject it.
	$arr['route'] = $parent_item['route'];

	$arr['item_private'] = $parent_item['item_private'];
	$arr['verb'] = $activity;
	$arr['obj_type'] = $objtype;
	$arr['object'] = $object;

	if(! $parent_author_signature) {
		$key = get_config('system','pubkey');
		$x = array('signer' => $diaspora_handle, 'body' => $text, 
			'signed_text' => $signed_data, 'signature' => base64_encode($author_signature));
		$arr['diaspora_meta'] = json_encode(crypto_encapsulate(json_encode($x),$key));
	}

	$x = item_store($arr);

	if($x)
		$message_id = $x['item_id'];

	// if the message isn't already being relayed, notify others
	// the existence of parent_author_signature means the parent_author or owner
	// is already relaying. The parent_item['origin'] indicates the message was created on our system

	if(($parent_item['item_flags'] & ITEM_ORIGIN) && (! $parent_author_signature))
		proc_run('php','include/notifier.php','comment-import',$message_id);

	return;
}

function diaspora_retraction($importer,$xml) {


	$guid = notags(unxmlify($xml->guid));
	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));
	$type = notags(unxmlify($xml->type));

	$contact = diaspora_get_contact_by_handle($importer['channel_id'],$diaspora_handle);
	if(! $contact)
		return;

	if($type === 'Person') {
		require_once('include/Contact.php');
		contact_remove($importer['channel_id'],$contact['abook_id']);
	}
	elseif($type === 'Post') {
		$r = q("select * from item where mid = '%s' and uid = %d limit 1",
			dbesc('guid'),
			intval($importer['channel_id'])
		);
		if(count($r)) {
			if(link_compare($r[0]['author_xchan'],$contact['xchan_hash'])) {
				drop_item($r[0]['id'],false);
			}
		}
	}

	return 202;
	// NOTREACHED
}

function diaspora_signed_retraction($importer,$xml,$msg) {


	$guid = notags(unxmlify($xml->target_guid));
	$diaspora_handle = notags(unxmlify($xml->sender_handle));
	$type = notags(unxmlify($xml->target_type));
	$sig = notags(unxmlify($xml->target_author_signature));

	$parent_author_signature = (($xml->parent_author_signature) ? notags(unxmlify($xml->parent_author_signature)) : '');

	$contact = diaspora_get_contact_by_handle($importer['channel_id'],$diaspora_handle);
	if(! $contact) {
		logger('diaspora_signed_retraction: no contact ' . $diaspora_handle . ' for ' . $importer['channel_id']);
		return;
	}


	$signed_data = $guid . ';' . $type ;
	$key = $msg['key'];

	/* How Diaspora performs relayable_retraction signature checking:

	   - If an item has been sent by the item author to the top-level post owner to relay on
	     to the rest of the contacts on the top-level post, the top-level post owner checks
	     the author_signature, then creates a parent_author_signature before relaying the item on
	   - If an item has been relayed on by the top-level post owner, the contacts who receive it
	     check only the parent_author_signature. Basically, they trust that the top-level post
	     owner has already verified the authenticity of anything he/she sends out
	   - In either case, the signature that get checked is the signature created by the person
	     who sent the salmon
	*/

	if($parent_author_signature) {

		$parent_author_signature = base64_decode($parent_author_signature);

		if(! rsa_verify($signed_data,$parent_author_signature,$key,'sha256')) {
			logger('diaspora_signed_retraction: top-level post owner verification failed');
			return;
		}

	}
	else {

		$sig_decode = base64_decode($sig);

		if(! rsa_verify($signed_data,$sig_decode,$key,'sha256')) {
			logger('diaspora_signed_retraction: retraction owner verification failed.' . print_r($msg,true));
			return;
		}
	}

	if($type === 'StatusMessage' || $type === 'Comment' || $type === 'Like') {
		$r = q("select * from item where mid = '%s' and uid = %d limit 1",
			dbesc($guid),
			intval($importer['channel_id'])
		);
		if($r) {
			if($r[0]['author_xchan'] == $contact['xchan_hash']) {

				drop_item($r[0]['id'],false, DROPITEM_PHASE1);

				// Now check if the retraction needs to be relayed by us
				//
				// The first item in the `item` table with the parent id is the parent. However, MySQL doesn't always
				// return the items ordered by `item`.`id`, in which case the wrong item is chosen as the parent.
				// The only item with `parent` and `id` as the parent id is the parent item.
				$p = q("select item_flags from item where parent = %d and id = %d limit 1",
					$r[0]['parent'],
					$r[0]['parent']
				);
				if($p) {
					if(($p[0]['item_flags'] & ITEM_ORIGIN) && (! $parent_author_signature)) {
// FIXME so we can relay this
//						q("insert into sign (`retract_iid`,`signed_text`,`signature`,`signer`) values (%d,'%s','%s','%s') ",
//							$r[0]['id'],
//							dbesc($signed_data),
//							dbesc($sig),
//							dbesc($diaspora_handle)
//						);

						// the existence of parent_author_signature would have meant the parent_author or owner
						// is already relaying.
						logger('diaspora_signed_retraction: relaying relayable_retraction');

						proc_run('php','include/notifier.php','drop',$r[0]['id']);
					}
				}
			}
		}
	}
	else
		logger('diaspora_signed_retraction: unknown type: ' . $type);

	return 202;
	// NOTREACHED
}

function diaspora_profile($importer,$xml,$msg) {

	$a = get_app();
	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));


	if($diaspora_handle != $msg['author']) {
		logger('diaspora_post: Potential forgery. Message handle is not the same as envelope sender.');
		return 202;
	}

	$contact = diaspora_get_contact_by_handle($importer['channel_id'],$diaspora_handle);
	if(! $contact)
		return;

	if($contact['blocked']) {
		logger('diaspora_post: Ignoring this author.');
		return 202;
	}

	$name = unxmlify($xml->first_name) . ((strlen($xml->last_name)) ? ' ' . unxmlify($xml->last_name) : '');
	$image_url = unxmlify($xml->image_url);
	$birthday = unxmlify($xml->birthday);


	$handle_parts = explode("@", $diaspora_handle);
	if($name === '') {
		$name = $handle_parts[0];
	}
		 
	if( preg_match("|^https?://|", $image_url) === 0) {
		$image_url = "http://" . $handle_parts[1] . $image_url;
	}

	require_once('include/photo/photo_driver.php');

	$images = import_profile_photo($image_url,$contact['xchan_hash']);
	
	// Generic birthday. We don't know the timezone. The year is irrelevant. 

	$birthday = str_replace('1000','1901',$birthday);

	$birthday = datetime_convert('UTC','UTC',$birthday,'Y-m-d');

	// this is to prevent multiple birthday notifications in a single year
	// if we already have a stored birthday and the 'm-d' part hasn't changed, preserve the entry, which will preserve the notify year

	if(substr($birthday,5) === substr($contact['bd'],5))
		$birthday = $contact['bd'];

	$r = q("update xchan set xchan_name = '%s', xchan_name_date = '%s', xchan_photo_l = '%s', xchan_photo_m = '%s', xchan_photo_s = '%s', xchan_photo_mimetype = '%s' where xchan_hash = '%s' ",
		dbesc($name),
		dbesc(datetime_convert()),
		dbesc($images[0]),
		dbesc($images[1]),
		dbesc($images[2]),
		dbesc($images[3]),
		dbesc(datetime_convert()),
		intval($contact['xchan_hash'])
	); 

	return;

}

function diaspora_share($owner,$contact) {
	$a = get_app();
	$myaddr = $owner['channel_address'] . '@' . substr($a->get_baseurl(), strpos($a->get_baseurl(),'://') + 3);

	if(! array_key_exists('xchan_hash',$contact)) {
		$c = q("select * from xchan left join hubloc on xchan_hash = hubloc_hash where xchan_hash = '%s' limit 1",
			dbesc($contact['hubloc_hash'])
		);
		if(! $c) {
			logger('diaspora_share: ' . $contact['hubloc_hash']  . ' not found.');
			return;
		}
		$contact = $c[0];
	}

	$theiraddr = $contact['xchan_addr'];

	$tpl = get_markup_template('diaspora_share.tpl');
	$msg = replace_macros($tpl, array(
		'$sender' => $myaddr,
		'$recipient' => $theiraddr
	));

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['channel_prvkey'],$contact['xchan_pubkey'])));
	return(diaspora_transmit($owner,$contact,$slap, false));
}

function diaspora_unshare($owner,$contact) {

	$a = get_app();
	$myaddr = $owner['channel_address'] . '@' .  substr($a->get_baseurl(), strpos($a->get_baseurl(),'://') + 3);

	$tpl = get_markup_template('diaspora_retract.tpl');
	$msg = replace_macros($tpl, array(
		'$guid'   => $owner['channel_guid'],
		'$type'   => 'Person',
		'$handle' => $myaddr
	));

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['channel_prvkey'],$contact['xchan_pubkey'])));

	return(diaspora_transmit($owner,$contact,$slap, false));
}


function diaspora_send_status($item,$owner,$contact,$public_batch = false) {

	$a = get_app();
	$myaddr = $owner['channel_address'] . '@' . substr($a->get_baseurl(), strpos($a->get_baseurl(),'://') + 3);

	$images = array();

	$title = $item['title'];
	$body = bb2diaspora_itembody($item,true);

/*
	// We're trying to match Diaspora's split message/photo protocol but
	// all the photos are displayed on D* as links and not img's - even
	// though we're sending pretty much precisely what they send us when
	// doing the same operation.  
	// Commented out for now, we'll use bb2diaspora to convert photos to markdown
	// which seems to get through intact.

	$cnt = preg_match_all('|\[img\](.*?)\[\/img\]|',$body,$matches,PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$detail = array();
			$detail['str'] = $mtch[0];
			$detail['path'] = dirname($mtch[1]) . '/';
			$detail['file'] = basename($mtch[1]);
			$detail['guid'] = $item['guid'];
			$detail['handle'] = $myaddr;
			$images[] = $detail;
			$body = str_replace($detail['str'],$mtch[1],$body);
		}
	}
*/


	$public = (($item['item_private']) ? 'false' : 'true');

	require_once('include/datetime.php');
	$created = datetime_convert('UTC','UTC',$item['created'],'Y-m-d H:i:s \U\T\C');

	// Detect a share element and do a reshare
	// see: https://github.com/Raven24/diaspora-federation/blob/master/lib/diaspora-federation/entities/reshare.rb
	if (!$item['item_private'] AND ($ret = diaspora_is_reshare($item["body"]))) {
		$tpl = get_markup_template('diaspora_reshare.tpl');
		$msg = replace_macros($tpl, array(
			'$root_handle' => xmlify($ret['root_handle']),
			'$root_guid' => $ret['root_guid'],
			'$guid' => $item['mid'],
			'$handle' => xmlify($myaddr),
			'$public' => $public,
			'$created' => $created,
			'$provider' => (($item['app']) ? $item['app'] : 'redmatrix')
		));
	} else {
		$tpl = get_markup_template('diaspora_post.tpl');
		$msg = replace_macros($tpl, array(
			'$body' => xmlify($body),
			'$guid' => $item['mid'],
			'$handle' => xmlify($myaddr),
			'$public' => $public,
			'$created' => $created,
			'$provider' => (($item['app']) ? $item['app'] : 'redmatrix')
		));
	}

	logger('diaspora_send_status: '.$owner['channel_name'].' -> '.$contact['xchan_name'].' base message: ' . $msg, LOGGER_DATA);

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['channel_prvkey'],$contact['xchan_pubkey'],$public_batch)));

	$return_code = diaspora_transmit($owner,$contact,$slap,$public_batch);

//	logger('diaspora_send_status: guid: '.$item['mid'].' result '.$return_code, LOGGER_DEBUG);

	if(count($images)) {
		diaspora_send_images($item,$owner,$contact,$images,$public_batch);
	}

	return $return_code;
}

function diaspora_is_reshare($body) {
	
	$body = trim($body);

	// Skip if it isn't a pure repeated messages
	// Does it start with a share?
	if(strpos($body, "[share") > 0)
		return(false);

	// Does it end with a share?
	if(strlen($body) > (strrpos($body, "[/share]") + 8))
		return(false);

	$attributes = preg_replace("/\[share(.*?)\]\s?(.*?)\s?\[\/share\]\s?/ism","$1",$body);
	// Skip if there is no shared message in there
	if ($body == $attributes)
		return(false);

	$profile = "";
	preg_match("/profile='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$profile = $matches[1];

	preg_match('/profile="(.*?)"/ism', $attributes, $matches);
	if ($matches[1] != "")
		$profile = $matches[1];

	$ret= array();

	$ret["root_handle"] = preg_replace("=https?://(.*)/u/(.*)=ism", "$2@$1", $profile);
	if (($ret["root_handle"] == $profile) OR ($ret["root_handle"] == ""))
		return(false);

	$link = "";
	preg_match("/link='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$link = $matches[1];

	preg_match('/link="(.*?)"/ism', $attributes, $matches);
	if ($matches[1] != "")
		$link = $matches[1];

	$ret["root_guid"] = preg_replace("=https?://(.*)/posts/(.*)=ism", "$2", $link);
	if (($ret["root_guid"] == $link) OR ($ret["root_guid"] == ""))
		return(false);

	return($ret);
}

function diaspora_send_images($item,$owner,$contact,$images,$public_batch = false) {
	$a = get_app();
	if(! count($images))
		return;
	$mysite = substr($a->get_baseurl(),strpos($a->get_baseurl(),'://') + 3) . '/photo';

	$tpl = get_markup_template('diaspora_photo.tpl');
	foreach($images as $image) {
		if(! stristr($image['path'],$mysite))
			continue;
		$resource = str_replace('.jpg','',$image['file']);
		$resource = substr($resource,0,strpos($resource,'-'));

		$r = q("select * from photo where `resource_id` = '%s' and `uid` = %d limit 1",
			dbesc($resource),
			intval($owner['uid'])
		);
		if(! $r)
			continue;
		$public = (($r[0]['allow_cid'] || $r[0]['allow_gid'] || $r[0]['deny_cid'] || $r[0]['deny_gid']) ? 'false' : 'true' );
		$msg = replace_macros($tpl,array(
			'$path' => xmlify($image['path']),
			'$filename' => xmlify($image['file']),
			'$msg_guid' => xmlify($image['guid']),
			'$guid' => xmlify($r[0]['resource_id']),
			'$handle' => xmlify($image['handle']),
			'$public' => xmlify($public),
			'$created_at' => xmlify(datetime_convert('UTC','UTC',$r[0]['created'],'Y-m-d H:i:s \U\T\C'))
		));


		logger('diaspora_send_photo: base message: ' . $msg, LOGGER_DATA);
		$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['channel_prvkey'],$contact['xchan_pubkey'],$public_batch)));

		diaspora_transmit($owner,$contact,$slap,$public_batch);
	}

}

function diaspora_send_followup($item,$owner,$contact,$public_batch = false) {

	$a = get_app();
	$myaddr = $owner['channel_address'] . '@' . get_app()->get_hostname();
	$theiraddr = $contact['xchan_addr'];

	// Diaspora doesn't support threaded comments, but some
	// versions of Diaspora (i.e. Diaspora-pistos) support
	// likes on comments
	if(($item['verb'] === ACTIVITY_LIKE || $item['verb'] === ACTIVITY_DISLIKE) && $item['thr_parent']) {
		$p = q("select mid, parent_mid from item where mid = '%s' limit 1",
			dbesc($item['thr_parent'])
		);
	}
	else {
		// The first item in the `item` table with the parent id is the parent. However, MySQL doesn't always
		// return the items ordered by `item`.`id`, in which case the wrong item is chosen as the parent.
		// The only item with `parent` and `id` as the parent id is the parent item.
		$p = q("select * from item where parent = %d and id = %d limit 1",
			intval($item['parent']),
			intval($item['parent'])
		);
	}
	if($p)
		$parent = $p[0];
	else
		return;


	if(($item['verb'] === ACTIVITY_LIKE) && ($parent['mid'] === $parent['parent_mid'])) {
		$tpl = get_markup_template('diaspora_like.tpl');
		$like = true;
		$target_type = 'Post';
		$positive = 'true';

		if(($item_['item_restrict'] & ITEM_DELETED))
			logger('diaspora_send_followup: received deleted "like". Those should go to diaspora_send_retraction');
	}
	else {
		$tpl = get_markup_template('diaspora_comment.tpl');
		$like = false;
	}

	if($item['diaspora_meta'] && ! $like) {
		$diaspora_meta = json_decode($item['diaspora_meta'],true);
		if($diaspora_meta) {
			if(array_key_exists('iv',$diaspora_meta)) {
				$key = get_config('system','prvkey');
				$meta = json_decode(crypto_unencapsulate($diaspora_meta,$key),true);
			}
			else
				$meta = $diaspora_meta;
		}
		$signed_text = $meta['signed_text'];
		$authorsig = $meta['signature'];
		$signer = $meta['signer'];
		$text = $meta['body'];
	}
	else {
		$text = bb2diaspora_itembody($item);

		// sign it

		if($like)
			$signed_text = $item['mid'] . ';' . $target_type . ';' . $parent['mid'] . ';' . $positive . ';' . $myaddr;
		else
			$signed_text = $item['mid'] . ';' . $parent['mid'] . ';' . $text . ';' . $myaddr;

		$authorsig = base64_encode(rsa_sign($signed_text,$owner['channel_prvkey'],'sha256'));

	}

	$msg = replace_macros($tpl,array(
		'$guid' => xmlify($item['mid']),
		'$parent_guid' => xmlify($parent['mid']),
		'$target_type' =>xmlify($target_type),
		'$authorsig' => xmlify($authorsig),
		'$body' => xmlify($text),
		'$positive' => xmlify($positive),
		'$handle' => xmlify($myaddr)
	));

	logger('diaspora_followup: base message: ' . $msg, LOGGER_DATA);

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['channel_prvkey'],$contact['xchan_pubkey'],$public_batch)));


	return(diaspora_transmit($owner,$contact,$slap,$public_batch));
}


function diaspora_send_relay($item,$owner,$contact,$public_batch = false) {


	$a = get_app();
	$myaddr = $owner['channel_address'] . '@' . get_app()->get_hostname();

	$text = bb2diaspora_itembody($item);

	$body = $text;

	// Diaspora doesn't support threaded comments, but some
	// versions of Diaspora (i.e. Diaspora-pistos) support
	// likes on comments

	if($item['verb'] === ACTIVITY_LIKE && $item['thr_parent']) {
		$p = q("select * from item where mid = '%s' limit 1",
				dbesc($item['thr_parent'])
		);
	}
	else {
		// The first item in the `item` table with the parent id is the parent. However, MySQL doesn't always
		// return the items ordered by `item`.`id`, in which case the wrong item is chosen as the parent.
		// The only item with `parent` and `id` as the parent id is the parent item.
		$p = q("select * from item where parent = %d and id = %d limit 1",
			   intval($item['parent']),
			   intval($item['parent'])
			  );
	}

	if($p)
		$parent = $p[0];
	else {
		logger('diaspora_send_relay: no parent');
		return;
	}

	$like = false;
	$relay_retract = false;
	$sql_sign_id = 'iid';

	if( $item['item_restrict'] & ITEM_DELETED) {
		$relay_retract = true;

		$target_type = ( ($item['verb'] === ACTIVITY_LIKE) ? 'Like' : 'Comment');

		$sql_sign_id = 'retract_iid';
		$tpl = get_markup_template('diaspora_relayable_retraction.tpl');
	}
	elseif($item['verb'] === ACTIVITY_LIKE) {
		$like = true;

		$target_type = ( $parent['mid'] === $parent['parent_mid']  ? 'Post' : 'Comment');
//		$positive = (($item['item_restrict'] & ITEM_DELETED) ? 'false' : 'true');
		$positive = 'true';

		$tpl = get_markup_template('diaspora_like_relay.tpl');
	}
	else { // item is a comment
		$tpl = get_markup_template('diaspora_comment_relay.tpl');
	}

	$diaspora_meta = (($item['diaspora_meta']) ? json_decode($item['diaspora_meta'],true) : '');
	if($diaspora_meta) {
		if(array_key_exists('iv',$diaspora_meta)) {
			$key = get_config('system','prvkey');
			$meta = json_decode(crypto_unencapsulate($diaspora_meta,$key),true);
		}
		else
			$meta = $diaspora_meta;
		$sender_signed_text = $meta['signed_text'];
		$authorsig = $meta['signature'];
		$handle = $meta['signer'];
		$text = $meta['body'];
	}
	else
		logger('diaspora_send_relay: original author signature not found');

	/* Since the author signature is only checked by the parent, not by the relay recipients,
	 * I think it may not be necessary for us to do so much work to preserve all the original
	 * signatures. The important thing that Diaspora DOES need is the original creator's handle.
	 * Let's just generate that and forget about all the original author signature stuff.
	 *
	 * Note: this might be more of an problem if we want to support likes on comments for older
	 * versions of Diaspora (diaspora-pistos), but since there are a number of problems with
	 * doing that, let's ignore it for now.
	 *
	 *
	 */
// bug - nomadic identity may/will affect diaspora_handle_from_contact
	if(! $handle) {
		if($item['author_xchan'] === $owner['channel_hash']) 
			$handle = $owner['channel_address'] . '@' . substr($a->get_baseurl(), strpos($a->get_baseurl(),'://') + 3);
		else
			$handle = diaspora_handle_from_contact($item['author_xchan']);
	}
	if(! $handle) {
		logger('diaspora_send_relay: no handle');
		return;
	}

	if(! $sender_signed_text) {
		if($relay_retract)
			$sender_signed_text = $item['mid'] . ';' . $target_type;
		elseif($like)
			$sender_signed_text = $positive . ';' . $item['mid'] . ';' . $target_type . ';' . $parent['mid'] . ';' . $handle;
		else
			$sender_signed_text = $item['mid'] . ';' . $parent['mid'] . ';' . $text . ';' . $handle;
	}

	// Sign the relayable with the top-level owner's signature
	//
	// We'll use the $sender_signed_text that we just created, instead of the $signed_text
	// stored in the database, because that provides the best chance that Diaspora will
	// be able to reconstruct the signed text the same way we did. This is particularly a
	// concern for the comment, whose signed text includes the text of the comment. The
	// smallest change in the text of the comment, including removing whitespace, will
	// make the signature verification fail. Since we translate from BB code to Diaspora's
	// markup at the top of this function, which is AFTER we placed the original $signed_text
	// in the database, it's hazardous to trust the original $signed_text.

	$parentauthorsig = base64_encode(rsa_sign($sender_signed_text,$owner['channel_prvkey'],'sha256'));

	if(! $text)
		logger('diaspora_send_relay: no text');

	$msg = replace_macros($tpl,array(
		'$guid' => xmlify($item['mid']),
		'$parent_guid' => xmlify($parent['mid']),
		'$target_type' =>xmlify($target_type),
		'$authorsig' => xmlify($authorsig),
		'$parentsig' => xmlify($parentauthorsig),
		'$body' => xmlify($text),
		'$positive' => xmlify($positive),
		'$handle' => xmlify($handle)
	));

	logger('diaspora_send_relay: base message: ' . $msg, LOGGER_DATA);

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['channel_prvkey'],$contact['xchan_pubkey'],$public_batch)));

	return(diaspora_transmit($owner,$contact,$slap,$public_batch));

}



function diaspora_send_retraction($item,$owner,$contact,$public_batch = false) {

	$a = get_app();
	$myaddr = $owner['channel_address'] . '@' .  get_app()->get_hostname();

	// Check whether the retraction is for a top-level post or whether it's a relayable
	if( $item['mid'] !== $item['parent_mid'] ) {

		$tpl = get_markup_template('diaspora_relay_retraction.tpl');
		$target_type = (($item['verb'] === ACTIVITY_LIKE) ? 'Like' : 'Comment');
	}
	else {
		
		$tpl = get_markup_template('diaspora_signed_retract.tpl');
		$target_type = 'StatusMessage';
	}

	$signed_text = $item['mid'] . ';' . $target_type;

	$msg = replace_macros($tpl, array(
		'$guid'   => xmlify($item['mid']),
		'$type'   => xmlify($target_type),
		'$handle' => xmlify($myaddr),
		'$signature' => xmlify(base64_encode(rsa_sign($signed_text,$owner['channel_prvkey'],'sha256')))
	));

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['channel_prvkey'],$contact['xchan_pubkey'],$public_batch)));

	return(diaspora_transmit($owner,$contact,$slap,$public_batch));
}

function diaspora_send_mail($item,$owner,$contact) {

	$a = get_app();
	$myaddr = $owner['channel_address'] . '@' .  get_app()->get_hostname();

	$r = q("select * from conv where id = %d and uid = %d limit 1",
		intval($item['convid']),
		intval($item['channel_id'])
	);

	if(! count($r)) {
		logger('diaspora_send_mail: conversation not found.');
		return;
	}
	$cnv = $r[0];

	$conv = array(
		'guid' => xmlify($cnv['guid']),
		'subject' => xmlify($cnv['subject']),
		'created_at' => xmlify(datetime_convert('UTC','UTC',$cnv['created'],'Y-m-d H:i:s \U\T\C')),
		'diaspora_handle' => xmlify($cnv['creator']),
		'participant_handles' => xmlify($cnv['recips'])
	);

	if(array_key_exists('mail_flags',$item) && ($item['mail_flags'] & MAIL_OBSCURED)) {
		$key = get_config('system','prvkey');
//		if($item['title'])
//			$item['title'] = crypto_unencapsulate(json_decode_plus($item['title']),$key);
		if($item['body'])
			$item['body'] = crypto_unencapsulate(json_decode_plus($item['body']),$key);
	}


	$body = bb2diaspora($item['body']);
	$created = datetime_convert('UTC','UTC',$item['created'],'Y-m-d H:i:s \U\T\C');
 
	$signed_text =  $item['mid'] . ';' . $cnv['guid'] . ';' . $body .  ';' 
		. $created . ';' . $myaddr . ';' . $cnv['guid'];

	$sig = base64_encode(rsa_sign($signed_text,$owner['channel_prvkey'],'sha256'));

	$msg = array(
		'guid' => xmlify($item['mid']),
		'parent_guid' => xmlify($cnv['guid']),
		'parent_author_signature' => (($item['reply']) ? null : xmlify($sig)),
		'author_signature' => xmlify($sig),
		'text' => xmlify($body),
		'created_at' => xmlify($created),
		'diaspora_handle' => xmlify($myaddr),
		'conversation_guid' => xmlify($cnv['guid'])
	);

	if($item['reply']) {
		$tpl = get_markup_template('diaspora_message.tpl');
		$xmsg = replace_macros($tpl, array('$msg' => $msg));
	}
	else {
		$conv['messages'] = array($msg);
		$tpl = get_markup_template('diaspora_conversation.tpl');
		$xmsg = replace_macros($tpl, array('$conv' => $conv));
	}

	logger('diaspora_conversation: ' . print_r($xmsg,true), LOGGER_DATA);

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($xmsg,$owner,$contact,$owner['channel_prvkey'],$contact['xchan_pubkey'],false)));

	return(diaspora_transmit($owner,$contact,$slap,false));


}

function diaspora_transmit($owner,$contact,$slap,$public_batch,$queue_run=false) {

	$enabled = intval(get_config('system','diaspora_enabled'));
	if(! $enabled) {
		return 200;
	}

	if($public_batch)
		$dest_url = $contact['hubloc_callback'] . '/public';
	else
		$dest_url = $contact['hubloc_callback'] . '/users/' . $contact['hubloc_guid'];

	logger('diaspora_transmit: URL: ' . $dest_url, LOGGER_DEBUG);	

	if(intval(get_config('system','diaspora_test')))
		return 200;

	$a = get_app();
	$logid = random_string(4);

	logger('diaspora_transmit: ' . $logid . ' ' . $dest_url, LOGGER_DEBUG);

	$hash = random_string();

	$interval = ((get_config('system','delivery_interval') !== false) 
		? intval(get_config('system','delivery_interval')) : 2 );

	q("insert into outq ( outq_hash, outq_account, outq_channel, outq_driver, outq_posturl, outq_async, outq_created, outq_updated, outq_notify, outq_msg ) values ( '%s', %d, %d, '%s', '%s', %d, '%s', '%s', '%s', '%s' )",
		dbesc($hash),
		intval($owner['account_id']),
		intval($owner['channel_id']),
		dbesc('post'),
		dbesc($dest_url),
		intval(1),
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		dbesc(''),
		dbesc($slap)
	);

	proc_run('php','include/deliver.php',$hash);
	if($interval)
		@time_sleep_until(microtime(true) + (float) $interval);

}
