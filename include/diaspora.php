<?php

require_once('include/crypto.php');
require_once('include/items.php');

function get_diaspora_key($uri) {
	$key = '';

	logger('Fetching diaspora key for: ' . $uri);

	$arr = lrdd($uri);

	if(is_array($arr)) {
		foreach($arr as $a) {
			if($a['@attributes']['rel'] === 'diaspora-public-key') {
				$key = base64_decode($a['@attributes']['href']);
			}
		}
	}
	else {
		return '';
	}

	if($key)
		return rsatopem($key);
	return '';
}


function diaspora_base_message($type,$data) {

	$tpl = get_markup_template('diaspora_' . $type . '.tpl');
	if(! $tpl) 
		return '';
	return replace_macros($tpl,$data);

}


function diaspora_msg_build($msg,$user,$contact,$prvkey,$pubkey) {
	$a = get_app();

	$inner_aes_key = random_string(32);
	$b_inner_aes_key = base64_encode($inner_aes_key);
	$inner_iv = random_string(32);
	$b_inner_iv = base64_encode($inner_iv);

	$outer_aes_key = random_string(32);
	$b_outer_aes_key = base64_encode($outer_aes_key);
	$outer_iv = random_string(32);
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
	$encrypted_header_json_object = json_encode(array('aes_key' => base64_encode($encrypted_outer_key_bundle), 
		'ciphertext' => base64_encode($ciphertext)));
	$encrypted_header = '<encrypted_header>' . base64_encode($encrypted_header_json_object) . '</encrypted_header>';

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

	logger('decrypted: ' . $decrypted);
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

	// Diaspora devs: This completely violates the entire principle of salmon magic signatures,
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

	// Diaspora devs: I can't even begin to tell you how sucky this is. Read the freaking spec.

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
	// *** or look it up locally ***

	logger('mod-diaspora: Fetching key for ' . $author_link );

	// Get diaspora public key (pkcs#1) and convert to pkcs#8

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

function find_person_by_handle($handle) {
		// we don't care about the uid, we just want to save an expensive webfinger probe
		$r = q("select * from contact where network = '%s' and addr = '%s' LIMIT 1",
			dbesc(NETWORK_DIASPORA),
			dbesc($handle)
		);
		if(count($r))
			return $r[0];
		$r = probe_url($handle);
		// need to cached this, perhaps in fcontact
		if(count($r))
			return ($r);
		return false;
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
			q("UPDATE `contact` SET `rel` = %d WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval(CONTACT_IS_FRIEND),
				intval($contact['id']),
				intval($importer['uid'])
			);
		}
		// send notification?
		return;
	}
	
	require_once('include/Scrape.php');
	$ret = probe_url($sender_handle);

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
		$ret = q("INSERT INTO `intro` ( `uid`, `contact-id`, `blocked`, `knowyou`, `note`, `hash`, `datetime`,`blocked`)
			VALUES ( %d, %d, 1, %d, '%s', '%s', '%s', 0 )",
			intval($importer['uid']),
			intval($contact_record['id']),
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
	if(count($r))
		return;

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

	$body = unxmlify($xml->raw_message);

	require_once('library/HTMLPurifier.auto.php');
	require_once('include/html2bbcode.php');

	$maxlen = get_max_import_size();
	if($maxlen && (strlen($body) > $maxlen))
		$body = substr($body,0, $maxlen);

	if((strpos($body,'<') !== false) || (strpos($body,'>') !== false)) {

		$body = preg_replace('#<object[^>]+>.+?' . 'http://www.youtube.com/((?:v|cp)/[A-Za-z0-9\-_=]+).+?</object>#s',
			'[youtube]$1[/youtube]', $body);

		$body = preg_replace('#<iframe[^>].+?' . 'http://www.youtube.com/embed/([A-Za-z0-9\-_=]+).+?</iframe>#s',
			'[youtube]$1[/youtube]', $body);

		$body = oembed_html2bbcode($body);

		$config = HTMLPurifier_Config::createDefault();
		$config->set('Cache.DefinitionImpl', null);
		$purifier = new HTMLPurifier($config);
		$body = $purifier->purify($body);

		$body = html2bbcode($body);
	}

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
	if(! $contact)
		return;

	if(($contact['rel'] == CONTACT_IS_FOLLOWER) || ($contact['blocked']) || ($contact['readonly'])) { 
		logger('diaspora_comment: Ignoring this author.');
		http_status_exit(202);
		// NOTREACHED
	}

	$r = q("SELECT * FROM `item` WHERE `uid` = %d AND `guid` = '%s' LIMIT 1",
		intval($importer['uid']),
		dbesc($parent_guid)
	);
	if(! count($r)) {
		logger('diaspora_comment: parent item not found: ' . $guid);
		return;
	}
	$parent_item = $r[0];

	$author_signed_data = $guid . ';' . $parent_guid . ';' . $text . ';' . $diaspora_handle;

	$author_signature = base64_decode($author_signature);

	if(stricmp($diaspora_handle,$msg['author']) == 0) {
		$person = $contact;
		$key = $msg['key'];
	}
	else {
		$person = find_person_by_handle($diaspora_handle);	

		if(is_array($person) && x($person,'pubkey'))
			$key = $person['pubkey'];
		else {
			logger('diaspora_comment: unable to find author details');
			return;
		}
	}

	if(! rsa_verify($author_signed_data,$author_signature,$key)) {
		logger('diaspora_comment: verification failed.');
		return;
	}

	if($parent_author_signature) {
		$owner_signed_data = $guid . ';' . $parent_guid . ';' . $text . ';' . $msg['author'];

		$parent_author_signature = base64_decode($parent_author_signature);

		$key = $msg['key'];

		if(! rsa_verify($owner_signed_data,$parent_author_signature,$key)) {
			logger('diaspora_comment: owner verification failed.');
			return;
		}
	}

	// Phew! Everything checks out. Now create an item.

	require_once('library/HTMLPurifier.auto.php');
	require_once('include/html2bbcode.php');

	$body = $text;

	$maxlen = get_max_import_size();
	if($maxlen && (strlen($body) > $maxlen))
		$body = substr($body,0, $maxlen);

	if((strpos($body,'<') !== false) || (strpos($body,'>') !== false)) {

		$body = preg_replace('#<object[^>]+>.+?' . 'http://www.youtube.com/((?:v|cp)/[A-Za-z0-9\-_=]+).+?</object>#s',
			'[youtube]$1[/youtube]', $body);

		$body = preg_replace('#<iframe[^>].+?' . 'http://www.youtube.com/embed/([A-Za-z0-9\-_=]+).+?</iframe>#s',
			'[youtube]$1[/youtube]', $body);

		$body = oembed_html2bbcode($body);

		$config = HTMLPurifier_Config::createDefault();
		$config->set('Cache.DefinitionImpl', null);
		$purifier = new HTMLPurifier($config);
		$body = $purifier->purify($body);

		$body = html2bbcode($body);
	}

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

	item_store($datarray);

	return;

}

function diaspora_like($importer,$xml,$msg) {

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
	if(! $contact)
		return;

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

	$r = q("SELECT * FROM `item` WHERE `uid` = %d AND `guid` = '$s' LIMIT 1",
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

	$author_signed_data = $guid . ';' . $parent_guid . ';' . $target_type . ';' . $positive . ';' . $diaspora_handle;

	$author_signature = base64_decode($author_signature);

	if(stricmp($diaspora_handle,$msg['author']) == 0) {
		$person = $contact;
		$key = $msg['key'];
	}
	else {
		$person = find_person_by_handle($diaspora_handle);	
		if(is_array($person) && x($person,'pubkey'))
			$key = $person['pubkey'];
		else {
			logger('diaspora_comment: unable to find author details');
			return;
		}
	}

	if(! rsa_verify($author_signed_data,$author_signature,$key)) {
		logger('diaspora_like: verification failed.');
		return;
	}

	if($parent_author_signature) {
		$owner_signed_data = $guid . ';' . $parent_guid . ';' . $target_type . ';' . $positive . ';' . $msg['author'];

		$parent_author_signature = base64_decode($parent_author_signature);

		$key = $msg['key'];

		if(! rsa_verify($owner_signed_data,$parent_author_signature,$key)) {
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
	$arr['contact-id'] = $contact['id'];
	$arr['type'] = 'activity';
	$arr['wall'] = $parent_item['wall'];
	$arr['gravity'] = GRAVITY_LIKE;
	$arr['parent'] = $parent_item['id'];
	$arr['parent-uri'] = $parent_item['uri'];

	$datarray['owner-name'] = $contact['name'];
	$datarray['owner-link'] = $contact['url'];
	$datarray['owner-avatar'] = $contact['thumb'];

	$datarray['author-name'] = $person['name'];
	$datarray['author-link'] = $person['url'];
	$datarray['author-avatar'] = ((x($person,'thumb')) ? $person['thumb'] : $person['photo']);
	
	$ulink = '[url=' . $contact['url'] . ']' . $contact['name'] . '[/url]';
	$alink = '[url=' . $parent_item['author-link'] . ']' . $parent_item['author-name'] . '[/url]';
	$plink = '[url=' . $a->get_baseurl() . '/display/' . $importer['nickname'] . '/' . $parent_item['id'] . ']' . $post_type . '[/url]';
	$arr['body'] =  sprintf( $bodyverb, $ulink, $alink, $plink );

	$arr['private'] = $parent_item['private'];
	$arr['verb'] = $activity;
	$arr['object-type'] = $objtype;
	$arr['object'] = $obj;
	$arr['visible'] = 1;
	$arr['unseen'] = 1;
	$arr['last-child'] = 0;

	$post_id = item_store($arr);	


	// FIXME send notification


}

function diaspora_retraction($importer,$xml) {

	$guid = notags(unxmlify($xml->guid));
	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));

	$contact = diaspora_get_contact_by_handle($importer['uid'],$diaspora_handle);
	if(! $contact)
		return;

//	if(($contact['rel'] == CONTACT_IS_FOLLOWER) || ($contact['blocked']) || ($contact['readonly'])) { 
//		logger('diaspora_retraction: Ignoring this author.');
//		http_status_exit(202);
//		// NOTREACHED
//	}



}

function diaspora_share($me,$contact) {
	$a = get_app();
	$myaddr = $me['nickname'] . '@' .  substr($a->get_baseurl(), strpos($a->get_baseurl(),'://') + 3);
	$theiraddr = $contact['addr'];

	$tpl = get_markup_template('diaspora_share.tpl');
	$msg = replace_macros($tpl, array(
		'$sender' => myaddr,
		'$recipient' => $theiraddr
	));

	$slap = 'xml=' . urlencode(diaspora_msg_build($msg,$me,$contact,$me['prvkey'],$contact['pubkey']));

	post_url($contact['notify'],$slap);
	$return_code = $a->get_curl_code();
	return $return_code;
}

function diaspora_send_status($item,$owner,$contact) {

	$a = get_app();
	$myaddr = $owner['nickname'] . '@' .  substr($a->get_baseurl(), strpos($a->get_baseurl(),'://') + 3);
	$theiraddr = $contact['addr'];
	require_once('include/bbcode.php');

	$body = xmlify(bbcode($item['body']));
	$public = (($item['private']) ? 'false' : 'true');

	require_once('include/datetime.php');
	$created = datetime_convert('UTC','UTC',$item['created'],'Y-m-d h:i:s \U\T\C');

	$tpl = get_markup_template('diaspora_post.tpl');
	$msg = replace_macros($tpl, array(
		'$body' => $body,
		'$guid' => $item['guid'],
		'$handle' => xmlify($myaddr),
		'$public' => $public,
		'$created' => $created
	));

	logger('diaspora_send_status: base message: ' . $msg, LOGGER_DATA);

	$slap = 'xml=' . urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['uprvkey'],$contact['pubkey']));

	post_url($contact['notify'],$slap);
	$return_code = $a->get_curl_code();
	logger('diaspora_send_status: returns: ' . $return_code);
	return $return_code;
}

