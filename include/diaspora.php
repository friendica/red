<?php

require_once('include/crypto.php');
require_once('include/items.php');
require_once('include/bb2diaspora.php');
require_once('include/contact_selectors.php');

function diaspora_dispatch($importer,$msg) {

	$parsed_xml = parse_xml_string($msg['message'],false);

	$xmlbase = $parsed_xml->post;

	if($xmlbase->request) {
		diaspora_request($importer,$xmlbase->request);
	}
	elseif($xmlbase->status_message) {
		diaspora_post($importer,$xmlbase->status_message);
	}
	elseif($xmlbase->comment) {
		diaspora_comment($importer,$xmlbase->comment,$msg);
	}
	elseif($xmlbase->like) {
		diaspora_like($importer,$xmlbase->like,$msg);
	}
	elseif($xmlbase->retraction) {
		diaspora_retraction($importer,$xmlbase->retraction,$msg);
	}
	elseif($xmlbase->photo) {
		diaspora_photo($importer,$xmlbase->photo,$msg);
	}
	else {
		logger('diaspora_dispatch: unknown message type: ' . print_r($xmlbase,true));
	}
	return;
}

function diaspora_get_contact_by_handle($uid,$handle) {
	$r = q("SELECT * FROM `contact` WHERE `network` = '%s' AND `uid` = %d AND `addr` = '%s' LIMIT 1",
		dbesc(NETWORK_DIASPORA),
		intval($uid),
		dbesc($handle)
	);
	if($r && count($r))
		return $r[0];
	return false;
}

function find_diaspora_person_by_handle($handle) {
	$r = q("select * from fcontact where network = '%s' and addr = '%s' limit 1",
		dbesc(NETWORK_DIASPORA),
		dbesc($handle)
	);
	if(count($r)) {
		// update record occasionally so it doesn't get stale
		$d = strtotime($r[0]['updated'] . ' +00:00');
		if($d < strtotime('now - 14 days')) {
			q("delete from fcontact where id = %d limit 1",
				intval($r[0]['id'])
			);
		}
		else
			return $r[0];
	}
	require_once('include/Scrape.php');
	$r = probe_url($handle, PROBE_DIASPORA);
	if((count($r)) && ($r['network'] === NETWORK_DIASPORA)) {
		add_fcontact($r);
		return ($r);
	}
	return false;
}


function get_diaspora_key($uri) {
	logger('Fetching diaspora key for: ' . $uri);

	$r = find_diaspora_person_by_handle($uri);
	if($r)
		return $r['pubkey'];
	return '';
}


function diaspora_msg_build($msg,$user,$contact,$prvkey,$pubkey) {
	$a = get_app();

	logger('diaspora_msg_build: ' . $msg, LOGGER_DATA);

	$inner_aes_key = random_string(32);
	$b_inner_aes_key = base64_encode($inner_aes_key);
	$inner_iv = random_string(16);
	$b_inner_iv = base64_encode($inner_iv);

	$outer_aes_key = random_string(32);
	$b_outer_aes_key = base64_encode($outer_aes_key);
	$outer_iv = random_string(16);
	$b_outer_iv = base64_encode($outer_iv);
	
	$handle = 'acct:' . $user['nickname'] . '@' . substr($a->get_baseurl(), strpos($a->get_baseurl(),'://') + 3);

	$padded_data = pkcs5_pad($msg,16);
	$inner_encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $inner_aes_key, $padded_data, MCRYPT_MODE_CBC, $inner_iv);

	$b64_data = base64_encode($inner_encrypted);


	$b64url_data = base64url_encode($b64_data);
	$b64url_stripped = str_replace(array("\n","\r"," ","\t"),array('','','',''),$b64url_data);
    $lines = str_split($b64url_stripped,60);
    $data = implode("\n",$lines);
	$data = $data . (($data[-1] != "\n") ? "\n" : '') ;
	$type = 'application/atom+xml';
	$encoding = 'base64url';
	$alg = 'RSA-SHA256';

	$signable_data = $data  . '.' . base64url_encode($type) . "\n" . '.' 
		. base64url_encode($encoding) . "\n" . '.' . base64url_encode($alg) . "\n";

	$signature = rsa_sign($signable_data,$prvkey);
	$sig = base64url_encode($signature);

$decrypted_header = <<< EOT
<decrypted_header>
  <iv>$b_inner_iv</iv>
  <aes_key>$b_inner_aes_key</aes_key>
  <author>
    <name>{$user['username']}</name>
    <uri>$handle</uri>
  </author>
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
<entry xmlns='http://www.w3.org/2005/Atom'>
  $encrypted_header
  <me:env xmlns:me="http://salmon-protocol.org/ns/magic-env">
    <me:encoding>base64url</me:encoding>
    <me:alg>RSA-SHA256</me:alg>
    <me:data type="application/atom+xml">$data</me:data>
    <me:sig>$sig</me:sig>
  </me:env>
</entry>
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

	$basedom = parse_xml_string($xml);

	$atom = $basedom->children(NAMESPACE_ATOM1);

	// Diaspora devs: This is kind of sucky - 'encrypted_header' does not belong in the atom namespace

	$encrypted_header = json_decode(base64_decode($atom->encrypted_header));
	
	$encrypted_aes_key_bundle = base64_decode($encrypted_header->aes_key);
	$ciphertext = base64_decode($encrypted_header->ciphertext);

	$outer_key_bundle = '';
	openssl_private_decrypt($encrypted_aes_key_bundle,$outer_key_bundle,$importer['prvkey']);

	$j_outer_key_bundle = json_decode($outer_key_bundle);

	$outer_iv = base64_decode($j_outer_key_bundle->iv);
	$outer_key = base64_decode($j_outer_key_bundle->key);

	$decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $outer_key, $ciphertext, MCRYPT_MODE_CBC, $outer_iv);


	$decrypted = pkcs5_unpad($decrypted);

	/**
	 * $decrypted now contains something like
	 *
	 *  <decrypted_header>
	 *     <iv>8e+G2+ET8l5BPuW0sVTnQw==</iv>
	 *     <aes_key>UvSMb4puPeB14STkcDWq+4QE302Edu15oaprAQSkLKU=</aes_key>
	 *     <author>
	 *       <name>Ryan Hughes</name>
	 *       <uri>acct:galaxor@diaspora.pirateship.org</uri>
	 *     </author>
	 *  </decrypted_header>
	 */

	logger('decrypted: ' . $decrypted, LOGGER_DEBUG);
	$idom = parse_xml_string($decrypted,false);

	$inner_iv = base64_decode($idom->iv);
	$inner_aes_key = base64_decode($idom->aes_key);

	$author_link = str_replace('acct:','',$idom->author->uri);

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

	// Add back the 60 char linefeeds

	// This completely violates the entire principle of salmon magic signatures,
	// which was to have a message signing format that was completely ambivalent to linefeeds 
	// and transport whitespace mangling, and base64 wrapping rules. Guess what? PHP and Ruby 
	// use different linelengths for base64 output. 

    $lines = str_split($data,60);
    $data = implode("\n",$lines);


	// stash away some other stuff for later

	$type = $base->data[0]->attributes()->type[0];
	$keyhash = $base->sig[0]->attributes()->keyhash[0];
	$encoding = $base->encoding;
	$alg = $base->alg;

	// I can't even begin to tell you how sucky this is. Please read the spec.

	$signed_data = $data  . (($data[-1] != "\n") ? "\n" : '') . '.' . base64url_encode($type) . "\n" . '.' . base64url_encode($encoding) . "\n" . '.' . base64url_encode($alg) . "\n";


	// decode the data
	$data = base64url_decode($data);

	// Now pull out the inner encrypted blob

	$inner_encrypted = base64_decode($data);

	$inner_decrypted = 
	$inner_decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $inner_aes_key, $inner_encrypted, MCRYPT_MODE_CBC, $inner_iv);

	$inner_decrypted = pkcs5_unpad($inner_decrypted);

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

	

function diaspora_request($importer,$xml) {

	$sender_handle = unxmlify($xml->sender_handle);
	$recipient_handle = unxmlify($xml->recipient_handle);

	if(! $sender_handle || ! $recipient_handle)
		return;
	 
	$contact = diaspora_get_contact_by_handle($importer['uid'],$sender_handle);


	if($contact) {

		// perhaps we were already sharing with this person. Now they're sharing with us.
		// That makes us friends.

		if($contact['rel'] == CONTACT_IS_FOLLOWER) {
			q("UPDATE `contact` SET `rel` = %d, `writable` = 1 WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval(CONTACT_IS_FRIEND),
				intval($contact['id']),
				intval($importer['uid'])
			);
		}
		// send notification?
		return;
	}
	
	$ret = find_diaspora_person_by_handle($sender_handle);


	if((! count($ret)) || ($ret['network'] != NETWORK_DIASPORA)) {
		logger('diaspora_request: Cannot resolve diaspora handle ' . $sender_handle . ' for ' . $recipient_handle);
		return;
	}

	$r = q("INSERT INTO `contact` (`uid`, `network`,`addr`,`created`,`url`,`name`,`nick`,`photo`,`pubkey`,`notify`,`poll`,`blocked`,`priority`)
		VALUES ( %d, '%s', '%s', '%s','%s','%s','%s','%s','%s','%s','%s',%d,%d) ",
		intval($importer['uid']),
		dbesc($ret['network']),
		dbesc($ret['addr']),
		datetime_convert(),
		dbesc($ret['url']),
		dbesc($ret['name']),
		dbesc($ret['nick']),
		dbesc($ret['photo']),
		dbesc($ret['pubkey']),
		dbesc($ret['notify']),
		dbesc($ret['poll']),
		1,
		2
	);
		 
	// find the contact record we just created

	$contact_record = diaspora_get_contact_by_handle($importer['uid'],$sender_handle);

	$hash = random_string() . (string) time();   // Generate a confirm_key
	
	if($contact_record) {
		$ret = q("INSERT INTO `intro` ( `uid`, `contact-id`, `blocked`, `knowyou`, `note`, `hash`, `datetime` )
			VALUES ( %d, %d, %d, %d, '%s', '%s', '%s' )",
			intval($importer['uid']),
			intval($contact_record['id']),
			0,
			0,
			dbesc( t('Sharing notification from Diaspora network')),
			dbesc($hash),
			dbesc(datetime_convert())
		);
	}

	return;
}

function diaspora_post($importer,$xml) {

	$guid = notags(unxmlify($xml->guid));
	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));

	$contact = diaspora_get_contact_by_handle($importer['uid'],$diaspora_handle);
	if(! $contact)
		return;

	if(($contact['rel'] == CONTACT_IS_FOLLOWER) || ($contact['blocked']) || ($contact['readonly'])) { 
		logger('diaspora_post: Ignoring this author.');
		http_status_exit(202);
		// NOTREACHED
	}

	$message_id = $diaspora_handle . ':' . $guid;
	$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `uri` = '%s' AND `guid` = '%s' LIMIT 1",
		intval($importer['uid']),
		dbesc($message_id),
		dbesc($guid)
	);
	if(count($r)) {
		logger('diaspora_post: message exists: ' . $guid);
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

	$body = diaspora2bb($xml->raw_message);

	$datarray = array();
	$datarray['uid'] = $importer['uid'];
	$datarray['contact-id'] = $contact['id'];
	$datarray['wall'] = 0;
	$datarray['guid'] = $guid;
	$datarray['uri'] = $datarray['parent-uri'] = $message_id;
	$datarray['created'] = $datarray['edited'] = datetime_convert('UTC','UTC',$created);
	$datarray['private'] = $private;
	$datarray['parent'] = 0;
	$datarray['owner-name'] = $contact['name'];
	$datarray['owner-link'] = $contact['url'];
	$datarray['owner-avatar'] = $contact['thumb'];
	$datarray['author-name'] = $contact['name'];
	$datarray['author-link'] = $contact['url'];
	$datarray['author-avatar'] = $contact['thumb'];
	$datarray['body'] = $body;
	$datarray['app']  = 'Diaspora';

	item_store($datarray);

	return;

}

function diaspora_comment($importer,$xml,$msg) {

	$guid = notags(unxmlify($xml->guid));
	$parent_guid = notags(unxmlify($xml->parent_guid));
	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));
	$target_type = notags(unxmlify($xml->target_type));
	$text = unxmlify($xml->text);
	$author_signature = notags(unxmlify($xml->author_signature));

	$parent_author_signature = (($xml->parent_author_signature) ? notags(unxmlify($xml->parent_author_signature)) : '');

	$text = $xml->text;

	$contact = diaspora_get_contact_by_handle($importer['uid'],$msg['author']);
	if(! $contact) {
		logger('diaspora_comment: cannot find contact: ' . $msg['author']);
		return;
	}

	if(($contact['rel'] == CONTACT_IS_FOLLOWER) || ($contact['blocked']) || ($contact['readonly'])) { 
		logger('diaspora_comment: Ignoring this author.');
		http_status_exit(202);
		// NOTREACHED
	}

	$r = q("SELECT * FROM `item` WHERE `uid` = %d AND `guid` = '%s' LIMIT 1",
		intval($importer['uid']),
		dbesc($guid)
	);
	if(count($r)) {
		logger('diaspora_comment: our comment just got relayed back to us (or there was a guid collision) : ' . $guid);
		return;
	}

	$r = q("SELECT * FROM `item` WHERE `uid` = %d AND `guid` = '%s' LIMIT 1",
		intval($importer['uid']),
		dbesc($parent_guid)
	);
	if(! count($r)) {
		logger('diaspora_comment: parent item not found: parent: ' . $parent_guid . ' item: ' . $guid);
		return;
	}
	$parent_item = $r[0];

	$author_signed_data = $guid . ';' . $parent_guid . ';' . $text . ';' . $diaspora_handle;

	$author_signature = base64_decode($author_signature);

	if(strcasecmp($diaspora_handle,$msg['author']) == 0) {
		$person = $contact;
		$key = $msg['key'];
	}
	else {
		$person = find_diaspora_person_by_handle($diaspora_handle);	

		if(is_array($person) && x($person,'pubkey'))
			$key = $person['pubkey'];
		else {
			logger('diaspora_comment: unable to find author details');
			return;
		}
	}

	if(! rsa_verify($author_signed_data,$author_signature,$key,'sha')) {
		logger('diaspora_comment: verification failed.');
		return;
	}


	if($parent_author_signature) {
		$owner_signed_data = $guid . ';' . $parent_guid . ';' . $text . ';' . $diaspora_handle;

		$parent_author_signature = base64_decode($parent_author_signature);

		$key = $msg['key'];

		if(! rsa_verify($owner_signed_data,$parent_author_signature,$key,'sha')) {
			logger('diaspora_comment: owner verification failed.');
			return;
		}
	}

	// Phew! Everything checks out. Now create an item.

	$body = diaspora2bb($text);

	$message_id = $diaspora_handle . ':' . $guid;

	$datarray = array();
	$datarray['uid'] = $importer['uid'];
	$datarray['contact-id'] = $contact['id'];
	$datarray['wall'] = $parent_item['wall'];
	$datarray['gravity'] = GRAVITY_COMMENT;
	$datarray['guid'] = $guid;
	$datarray['uri'] = $message_id;
	$datarray['parent-uri'] = $parent_item['uri'];

	// No timestamps for comments? OK, we'll the use current time.
	$datarray['created'] = $datarray['edited'] = datetime_convert();
	$datarray['private'] = $parent_item['private'];

	$datarray['owner-name'] = $contact['name'];
	$datarray['owner-link'] = $contact['url'];
	$datarray['owner-avatar'] = $contact['thumb'];

	$datarray['author-name'] = $person['name'];
	$datarray['author-link'] = $person['url'];
	$datarray['author-avatar'] = ((x($person,'thumb')) ? $person['thumb'] : $person['photo']);
	$datarray['body'] = $body;
	$datarray['app']  = 'Diaspora';

	$message_id = item_store($datarray);

	if(! $parent_author_signature) {
		q("insert into sign (`iid`,`signed_text`,`signature`,`signer`) values (%d,'%s','%s','%s') ",
			intval($message_id),
			dbesc($author_signed_data),
			dbesc(base64_encode($author_signature)),
			dbesc($diaspora_handle)
		);

		// if the message isn't already being relayed, notify others
		// the existence of parent_author_signature means the parent_author or owner
		// is already relaying.

		proc_run('php','include/notifier.php','comment',$message_id);
	}
	return;
}

function diaspora_photo($importer,$xml,$msg) {

	$remote_photo_path = notags(unxmlify($xml->remote_photo_path));

	$remote_photo_name = notags(unxmlify($xml->remote_photo_name));

	$status_message_guid = notags(unxmlify($xml->status_message_guid));

	$guid = notags(unxmlify($xml->guid));

	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));

	$public = notags(unxmlify($xml->public));

	$created_at = notags(unxmlify($xml_created_at));


	$contact = diaspora_get_contact_by_handle($importer['uid'],$msg['author']);
	if(! $contact)
		return;

	if(($contact['rel'] == CONTACT_IS_FOLLOWER) || ($contact['blocked']) || ($contact['readonly'])) { 
		logger('diaspora_photo: Ignoring this author.');
		http_status_exit(202);
		// NOTREACHED
	}

	$r = q("SELECT * FROM `item` WHERE `uid` = %d AND `guid` = '%s' LIMIT 1",
		intval($importer['uid']),
		dbesc($status_message_guid)
	);
	if(! count($r)) {
		logger('diaspora_photo: parent item not found: parent: ' . $parent_guid . ' item: ' . $guid);
		return;
	}
	$parent_item = $r[0];

	$link_text = '[img]' . $remote_photo_path . $remote_photo_name . '[/img]' . "\n";

	$r = q("update item set `body` = '%s' where `id` = %d and `uid` = %d limit 1",
		dbesc($link_text . $parent_item['body']),
		intval($parent_item['id']),
		intval($parent_item['uid'])
	);

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

	if($target_type !== 'Post')
		return;

	$contact = diaspora_get_contact_by_handle($importer['uid'],$msg['author']);
	if(! $contact) {
		logger('diaspora_like: cannot find contact: ' . $msg['author']);
		return;
	}

	if(($contact['rel'] == CONTACT_IS_FOLLOWER) || ($contact['blocked']) || ($contact['readonly'])) { 
		logger('diaspora_like: Ignoring this author.');
		http_status_exit(202);
		// NOTREACHED
	}

	$r = q("SELECT * FROM `item` WHERE `uid` = %d AND `guid` = '%s' LIMIT 1",
		intval($importer['uid']),
		dbesc($parent_guid)
	);
	if(! count($r)) {
		logger('diaspora_like: parent item not found: ' . $guid);
		return;
	}

	$parent_item = $r[0];

	$r = q("SELECT * FROM `item` WHERE `uid` = %d AND `guid` = '%s' LIMIT 1",
		intval($importer['uid']),
		dbesc($guid)
	);
	if(count($r)) {
		if($positive === 'true') {
			logger('diaspora_like: duplicate like: ' . $guid);
			return;
		} 
		if($positive === 'false') {
			q("UPDATE `item` SET `deleted` = 1 WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($r[0]['id']),
				intval($importer['uid'])
			);
			// FIXME
			//  send notification via proc_run()
			return;
		}
	}
	if($positive === 'false') {
		logger('diaspora_like: unlike received with no corresponding like');
		return;	
	}

	$author_signed_data = $guid . ';' . $target_type . ';' . $parent_guid . ';' . $positive . ';' . $diaspora_handle;

	$author_signature = base64_decode($author_signature);

	if(strcasecmp($diaspora_handle,$msg['author']) == 0) {
		$person = $contact;
		$key = $msg['key'];
	}
	else {
		$person = find_diaspora_person_by_handle($diaspora_handle);	
		if(is_array($person) && x($person,'pubkey'))
			$key = $person['pubkey'];
		else {
			logger('diaspora_like: unable to find author details');
			return;
		}
	}

	if(! rsa_verify($author_signed_data,$author_signature,$key,'sha')) {
		logger('diaspora_like: verification failed.');
		return;
	}

	if($parent_author_signature) {
//		$owner_signed_data = $guid . ';' . $parent_guid . ';' . $target_type . ';' . $positive . ';' . $msg['author'];
		$owner_signed_data = $guid . ';' . $parent_guid . ';' . $target_type . ';' . $positive . ';' . $diaspora_handle;

		$parent_author_signature = base64_decode($parent_author_signature);

		$key = $msg['key'];

		if(! rsa_verify($owner_signed_data,$parent_author_signature,$key,'sha')) {
			logger('diaspora_like: owner verification failed.');
			return;
		}
	}

	// Phew! Everything checks out. Now create an item.

	$uri = $diaspora_handle . ':' . $guid;

	$activity = ACTIVITY_LIKE;
	$post_type = (($parent_item['resource-id']) ? t('photo') : t('status'));
	$objtype = (($parent_item['resource-id']) ? ACTIVITY_OBJ_PHOTO : ACTIVITY_OBJ_NOTE ); 
	$link = xmlify('<link rel="alternate" type="text/html" href="' . $a->get_baseurl() . '/display/' . $importer['nickname'] . '/' . $parent_item['id'] . '" />' . "\n") ;
	$body = $parent_item['body'];

	$obj = <<< EOT

	<object>
		<type>$objtype</type>
		<local>1</local>
		<id>{$parent_item['uri']}</id>
		<link>$link</link>
		<title></title>
		<content>$body</content>
	</object>
EOT;
	$bodyverb = t('%1$s likes %2$s\'s %3$s');

	$arr = array();

	$arr['uri'] = $uri;
	$arr['uid'] = $importer['uid'];
	$arr['guid'] = $guid;
	$arr['contact-id'] = $contact['id'];
	$arr['type'] = 'activity';
	$arr['wall'] = $parent_item['wall'];
	$arr['gravity'] = GRAVITY_LIKE;
	$arr['parent'] = $parent_item['id'];
	$arr['parent-uri'] = $parent_item['uri'];

	$arr['owner-name'] = $contact['name'];
	$arr['owner-link'] = $contact['url'];
	$arr['owner-avatar'] = $contact['thumb'];

	$arr['author-name'] = $person['name'];
	$arr['author-link'] = $person['url'];
	$arr['author-avatar'] = ((x($person,'thumb')) ? $person['thumb'] : $person['photo']);
	
	$ulink = '[url=' . $contact['url'] . ']' . $contact['name'] . '[/url]';
	$alink = '[url=' . $parent_item['author-link'] . ']' . $parent_item['author-name'] . '[/url]';
	$plink = '[url=' . $a->get_baseurl() . '/display/' . $importer['nickname'] . '/' . $parent_item['id'] . ']' . $post_type . '[/url]';
	$arr['body'] =  sprintf( $bodyverb, $ulink, $alink, $plink );

	$arr['app']  = 'Diaspora';

	$arr['private'] = $parent_item['private'];
	$arr['verb'] = $activity;
	$arr['object-type'] = $objtype;
	$arr['object'] = $obj;
	$arr['visible'] = 1;
	$arr['unseen'] = 1;
	$arr['last-child'] = 0;

	$message_id = item_store($arr);

	if(! $parent_author_signature) {
		q("insert into sign (`iid`,`signed_text`,`signature`,`signer`) values (%d,'%s','%s','%s') ",
			intval($message_id),
			dbesc($author_signed_data),
			dbesc(base64_encode($author_signature)),
			dbesc($diaspora_handle)
		);
	}

	// if the message isn't already being relayed, notify others
	// the existence of parent_author_signature means the parent_author or owner
	// is already relaying.

	if(! $parent_author_signature)
		proc_run('php','include/notifier.php','comment',$message_id);

	return;
}

function diaspora_retraction($importer,$xml) {

	$guid = notags(unxmlify($xml->guid));
	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));
	$type = notags(unxmlify($xml->type));

	$contact = diaspora_get_contact_by_handle($importer['uid'],$diaspora_handle);
	if(! $contact)
		return;

	if($type === 'Person') {
		contact_remove($contact['id']);
	}
	elseif($type === 'Post') {
		$r = q("select * from item where guid = '%s' and uid = %d limit 1",
			dbesc('guid'),
			intval($importer['uid'])
		);
		if(count($r)) {
			if(link_compare($r[0]['author-link'],$contact['url'])) {
				q("update item set `deleted` = 1, `changed` = '%s' where `id` = %d limit 1",
					dbesc(datetime_convert()),			
					intval($r[0]['id'])
				);
			}
		}
	}

	http_exit_status(202);
	// NOTREACHED
}

function diaspora_share($me,$contact) {
	$a = get_app();
	$myaddr = $me['nickname'] . '@' .  substr($a->get_baseurl(), strpos($a->get_baseurl(),'://') + 3);
	$theiraddr = $contact['addr'];

	$tpl = get_markup_template('diaspora_share.tpl');
	$msg = replace_macros($tpl, array(
		'$sender' => $myaddr,
		'$recipient' => $theiraddr
	));

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$me,$contact,$me['prvkey'],$contact['pubkey'])));

	return(diaspora_transmit($owner,$contact,$slap));
}

function diaspora_unshare($me,$contact) {

	$a = get_app();
	$myaddr = $me['nickname'] . '@' .  substr($a->get_baseurl(), strpos($a->get_baseurl(),'://') + 3);

	$tpl = get_markup_template('diaspora_retract.tpl');
	$msg = replace_macros($tpl, array(
		'$guid'   => $me['guid'],
		'$type'   => 'Person',
		'$handle' => $myaddr
	));

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$me,$contact,$me['prvkey'],$contact['pubkey'])));

	return(diaspora_transmit($owner,$contact,$slap));

}



function diaspora_send_status($item,$owner,$contact) {

	$a = get_app();
	$myaddr = $owner['nickname'] . '@' . substr($a->get_baseurl(), strpos($a->get_baseurl(),'://') + 3);
	$theiraddr = $contact['addr'];

	$images = array();

	$body = $item['body'];

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
			$body = str_replace($detail['str'],t('link'),$body);
		}
	}	

	$body = xmlify(html_entity_decode(bb2diaspora($body)));

	$public = (($item['private']) ? 'false' : 'true');

	require_once('include/datetime.php');
	$created = datetime_convert('UTC','UTC',$item['created'],'Y-m-d H:i:s \U\T\C');

	$tpl = get_markup_template('diaspora_post.tpl');
	$msg = replace_macros($tpl, array(
		'$body' => $body,
		'$guid' => $item['guid'],
		'$handle' => xmlify($myaddr),
		'$public' => $public,
		'$created' => $created
	));

	logger('diaspora_send_status: ' . $owner['username'] . ' -> ' . $contact['name'] . ' base message: ' . $msg, LOGGER_DATA);

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['uprvkey'],$contact['pubkey'])));

	$return_code = diaspora_transmit($owner,$contact,$slap);

	if(count($images)) {
		diaspora_send_images($item,$owner,$contact,$images);
	}

	return $return_code;
}


function diaspora_send_images($item,$owner,$contact,$images) {
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

		$r = q("select * from photo where `resource-id` = '%s' and `uid` = %d limit 1",
			dbesc($resource),
			intval($owner['uid'])
		);
		if(! count($r))
			continue;
		$public = (($r[0]['allow_cid'] || $r[0]['allow_gid'] || $r[0]['deny_cid'] || $r[0]['deny_gid']) ? 'false' : 'true' );
		$msg = replace_macros($tpl,array(		
			'$path' => xmlify($image['path']),
			'$filename' => xmlify($image['file']),
			'$msg_guid' => xmlify($image['guid']),
			'$guid' => xmlify($r[0]['guid']),
			'$handle' => xmlify($image['handle']),
			'$public' => xmlify($public),
			'$created_at' => xmlify(datetime_convert('UTC','UTC',$r[0]['created'],'Y-m-d H:i:s \U\T\C'))
		));


		logger('diaspora_send_photo: base message: ' . $msg, LOGGER_DATA);
		$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['uprvkey'],$contact['pubkey'])));

		diaspora_transmit($owner,$contact,$slap);
	}

}

function diaspora_send_followup($item,$owner,$contact) {

	$a = get_app();
	$myaddr = $owner['nickname'] . '@' .  substr($a->get_baseurl(), strpos($a->get_baseurl(),'://') + 3);
	$theiraddr = $contact['addr'];

	$p = q("select guid from item where parent = %d limit 1",
		$item['parent']
	);
	if(count($p))
		$parent_guid = $p[0]['guid'];
	else
		return;

	if($item['verb'] === ACTIVITY_LIKE) {
		$tpl = get_markup_template('diaspora_like.tpl');
		$like = true;
		$target_type = 'Post';
		$positive = (($item['deleted']) ? 'false' : 'true');
	}
	else {
		$tpl = get_markup_template('diaspora_comment.tpl');
		$like = false;
	}

	$text = html_entity_decode(bb2diaspora($item['body']));

	// sign it

	if($like)
		$signed_text = $item['guid'] . ';' . $target_type . ';' . $parent_guid . ';' . $positive . ';' . $myaddr;
	else
		$signed_text = $item['guid'] . ';' . $parent_guid . ';' . $text . ';' . $myaddr;

	$authorsig = base64_encode(rsa_sign($signed_text,$owner['uprvkey'],'sha'));

	$msg = replace_macros($tpl,array(
		'$guid' => xmlify($item['guid']),
		'$parent_guid' => xmlify($parent_guid),
		'$target_type' =>xmlify($target_type),
		'$authorsig' => xmlify($authorsig),
		'$body' => xmlify($text),
		'$positive' => xmlify($positive),
		'$handle' => xmlify($myaddr)
	));

	logger('diaspora_followup: base message: ' . $msg, LOGGER_DATA);

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['uprvkey'],$contact['pubkey'])));

	return(diaspora_transmit($owner,$contact,$slap));
}


function diaspora_send_relay($item,$owner,$contact) {


	$a = get_app();
	$myaddr = $owner['nickname'] . '@' .  substr($a->get_baseurl(), strpos($a->get_baseurl(),'://') + 3);
	$theiraddr = $contact['addr'];


	$p = q("select guid from item where parent = %d limit 1",
		$item['parent']
	);
	if(count($p))
		$parent_guid = $p[0]['guid'];
	else
		return;

	if($item['verb'] === ACTIVITY_LIKE) {
		$tpl = get_markup_template('diaspora_like_relay.tpl');
		$like = true;
		$target_type = 'Post';
		$positive = (($item['deleted']) ? 'false' : 'true');
	}
	else {
		$tpl = get_markup_template('diaspora_comment_relay.tpl');
		$like = false;
	}

	$body = $item['body'];

	$text = html_entity_decode(bb2diaspora($body));

	// fetch the original signature	if somebody sent the post to us to relay
	// If we are relaying for a reply originating on our own account, there wasn't a 'send to relay'
	// action. It wasn't needed. In that case create the original signature and the 
	// owner (parent author) signature
	// comments from other networks will be relayed under our name, with a brief 
	// preamble to describe what's happening and noting the real author

	$r = q("select * from sign where iid = %d limit 1",
		intval($item['id'])
	);
	if(count($r)) { 
		$orig_sign = $r[0];
		$signed_text = $orig_sign['signed_text'];
		$authorsig = $orig_sign['signature'];
		$handle = $orig_sign['signer'];
	}
	else {

		$itemcontact = q("select * from contact where `id` = %d limit 1",
			intval($item['contact-id'])
		);
		if(count($itemcontact)) {
			if(! $itemcontact[0]['self']) {
				$prefix = sprintf( t('[Relayed] Comment authored by %s from network %s'),
					'['. $item['author-name'] . ']' . '(' . $item['author-link'] . ')',  
					network_to_name($itemcontact['network'])) . "\n";
				$body = $prefix . $body;
			}
		}
		else {

			if($like)
				$signed_text = $item['guid'] . ';' . $target_type . ';' . $parent_guid . ';' . $positive . ';' . $myaddr;
			else
				$signed_text = $item['guid'] . ';' . $parent_guid . ';' . $text . ';' . $myaddr;

			$authorsig = base64_encode(rsa_sign($signed_text,$owner['uprvkey'],'sha'));

			q("insert into sign (`iid`,`signed_text`,`signature`,`signer`) values (%d,'%s','%s','%s') ",
				intval($item['id']),
				dbesc($signed_text),
				dbesc(base64_encode($authorsig)),
				dbesc($myaddr)
			);
			$handle = $myaddr;
		}
	}

	// sign it

	$parentauthorsig = base64_encode(rsa_sign($signed_text,$owner['uprvkey'],'sha'));

	$msg = replace_macros($tpl,array(
		'$guid' => xmlify($item['guid']),
		'$parent_guid' => xmlify($parent_guid),
		'$target_type' =>xmlify($target_type),
		'$authorsig' => xmlify($orig_sign['signature']),
		'$parentsig' => xmlify($parentauthorsig),
		'$body' => xmlify($text),
		'$positive' => xmlify($positive),
		'$handle' => xmlify($handle)
	));

	logger('diaspora_relay_comment: base message: ' . $msg, LOGGER_DATA);

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['uprvkey'],$contact['pubkey'])));

	return(diaspora_transmit($owner,$contact,$slap));

}



function diaspora_send_retraction($item,$owner,$contact) {

	$a = get_app();
	$myaddr = $owner['nickname'] . '@' .  substr($a->get_baseurl(), strpos($a->get_baseurl(),'://') + 3);

	$tpl = get_markup_template('diaspora_retract.tpl');
	$msg = replace_macros($tpl, array(
		'$guid'   => $item['guid'],
		'$type'   => 'Post',
		'$handle' => $myaddr
	));

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['uprvkey'],$contact['pubkey'])));

	return(diaspora_transmit($owner,$contact,$slap));
}



function diaspora_transmit($owner,$contact,$slap) {

	$a = get_app();

	post_url($contact['notify'] . '/',$slap);
	$return_code = $a->get_curl_code();
	logger('diaspora_transmit: returns: ' . $return_code);

	if(! $return_code) {
		logger('diaspora_transmit: queue message');
		// queue message for redelivery
		q("INSERT INTO `queue` ( `cid`, `created`, `last`, `content`)
			VALUES ( %d, '%s', '%s', '%s') ",
			intval($contact['id']),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			dbesc($slap)
		);
	}

	return(($return_code) ? $return_code : (-1));
}
