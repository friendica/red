<?php

require_once('include/crypto.php');

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


function diaspora_decode($importer,$xml) {



	$basedom = parse_xml_string($xml);

	$atom = $basedom->children(NAMESPACE_ATOM1);

	$encrypted_header = json_decode(base64_decode($atom->encrypted_header));
	
	$encrypted_aes_key_bundle = base64_decode($encrypted_header->aes_key);
	$ciphertext = base64_decode($encrypted_header->ciphertext);

	$outer_key_bundle = '';
	openssl_private_decrypt($encrypted_aes_key_bundle,$outer_key_bundle,$importer['prvkey']);

	$j_outer_key_bundle = json_decode($outer_key_bundle);
logger('outer: ' . $j_outer_key_bundle);
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
    $lines = str_split($data,60);
    $data = implode("\n",$lines);


	// stash away some other stuff for later

	$type = $base->data[0]->attributes()->type[0];
	$keyhash = $base->sig[0]->attributes()->keyhash[0];
	$encoding = $base->encoding;
	$alg = $base->alg;

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

	return $inner_decrypted;

}




function diaspora_request($importer,$contact,$xml) {

	$sender_handle = $xml->sender_handle;
	$recipient_handle = $xml->recipient_handle;

	if(! $sender_handle || ! $recipient_handle)
		return;
	
	if($contact && ($contact['rel'] == CONTACT_IS_FOLLOWER || $contact['rel'] == CONTACT_IS_FRIEND)) {
		q("UPDATE `contact` SET `rel` = %d WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval(CONTACT_IS_FRIEND),
			intval($contact['id']),
			intval($importer['uid'])
		);
		// send notification
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
	$contact_record = null;
	if($r) {	
		$r = q("SELECT `id` FROM `contact` 
				WHERE `uid` = %d AND `addr` = '%s' AND `poll` = '%s' LIMIT 1",
				intval($importer['uid']),
				$ret['addr'],
				$ret['poll']
		);
		if(count($r)) 
			$contact_record = $r[0];
	}

	$hash = random_string() . (string) time();   // Generate a confirm_key
	
	if(is_array($contact_record)) {
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

function diaspora_post($importer,$contact,$xml) {

	$guid = notags(unxmlify($xml->guid));
	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));
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
	$datarray['uri'] = $message_id;
	$dattarray['created'] = $datarray['edited'] = datetime_convert('UTC','UTC',$created);
	$dattarray['private'] = $private;
	$dattarray['parent'] = 0;
	$datarray['owner-name'] = $contact['name'];
	$datarray['owner-link'] = $contact['url'];
	$datarray['owner-avatar'] = $contact['thumb'];
	$datarray['author-name'] = $contact['name'];
	$datarray['author-link'] = $contact['url'];
	$datarray['author-avatar'] = $contact['thumb'];

	item_store($datarray);

	return;


}

function diaspora_comment($importer,$contact,$xml) {
	$guid = notags(unxmlify($xml->guid));
	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));
	$message_id = $diaspora_handle . ':' . $guid;
	$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `uri` = '%s' AND `guid` = '%s' LIMIT 1",
		intval($importer['uid']),
		dbesc($message_id),
		dbesc($guid)
	);
	if(count($r))
		return;

	$owner = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 1 LIMIT 1",
		intval($importer['uid'])
	);
	if(! count($owner))
		return;

	$created = unxmlify($xml->created_at);
	$private = ((unxmlify($xml->public) == 'false') ? 1 : 0);

}

function diaspora_like($importer,$contact,$xml) {

	$guid = notags(unxmlify($xml->guid));
	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));
	$message_id = $diaspora_handle . ':' . $guid;
	$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `uri` = '%s' AND `guid` = '%s' LIMIT 1",
		intval($importer['uid']),
		dbesc($message_id),
		dbesc($guid)
	);
	if(count($r))
		return;

	$owner = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 1 LIMIT 1",
		intval($importer['uid'])
	);
	if(! count($owner))
		return;

	$created = unxmlify($xml->created_at);
	$private = ((unxmlify($xml->public) == 'false') ? 1 : 0);

	$uri = item_new_uri($a->get_hostname(),$owner_uid);

	$post_type = (($item['resource-id']) ? t('photo') : t('status'));
	$objtype = (($item['resource-id']) ? ACTIVITY_OBJ_PHOTO : ACTIVITY_OBJ_NOTE ); 
	$link = xmlify('<link rel="alternate" type="text/html" href="' . $a->get_baseurl() . '/display/' . $owner['nickname'] . '/' . $item['id'] . '" />' . "\n") ;
	$body = $item['body'];

	$obj = <<< EOT

	<object>
		<type>$objtype</type>
		<local>1</local>
		<id>{$item['uri']}</id>
		<link>$link</link>
		<title></title>
		<content>$body</content>
	</object>
EOT;
	if($verb === 'like')
		$bodyverb = t('%1$s likes %2$s\'s %3$s');
	if($verb === 'dislike')
		$bodyverb = t('%1$s doesn\'t like %2$s\'s %3$s');

	if(! isset($bodyverb))
			return; 

	$arr = array();

	$arr['uri'] = $uri;
	$arr['uid'] = $owner_uid;
	$arr['contact-id'] = $contact['id'];
	$arr['type'] = 'activity';
	$arr['wall'] = 1;
	$arr['gravity'] = GRAVITY_LIKE;
	$arr['parent'] = $item['id'];
	$arr['parent-uri'] = $item['uri'];
	$arr['owner-name'] = $owner['name'];
	$arr['owner-link'] = $owner['url'];
	$arr['owner-avatar'] = $owner['thumb'];
	$arr['author-name'] = $contact['name'];
	$arr['author-link'] = $contact['url'];
	$arr['author-avatar'] = $contact['thumb'];
	
	$ulink = '[url=' . $contact['url'] . ']' . $contact['name'] . '[/url]';
	$alink = '[url=' . $item['author-link'] . ']' . $item['author-name'] . '[/url]';
	$plink = '[url=' . $a->get_baseurl() . '/display/' . $owner['nickname'] . '/' . $item['id'] . ']' . $post_type . '[/url]';
	$arr['body'] =  sprintf( $bodyverb, $ulink, $alink, $plink );

	$arr['verb'] = $activity;
	$arr['object-type'] = $objtype;
	$arr['object'] = $obj;
	$arr['allow_cid'] = $item['allow_cid'];
	$arr['allow_gid'] = $item['allow_gid'];
	$arr['deny_cid'] = $item['deny_cid'];
	$arr['deny_gid'] = $item['deny_gid'];
	$arr['visible'] = 1;
	$arr['unseen'] = 1;
	$arr['last-child'] = 0;

	$post_id = item_store($arr);	

	if(! $item['visible']) {
		$r = q("UPDATE `item` SET `visible` = 1 WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($item['id']),
			intval($owner_uid)
		);
	}			

	$arr['id'] = $post_id;



}

function diaspora_retraction($importer,$contact,$xml) {

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

	$slap = diaspora_msg_build($msg,$me,$contact,$me['prvkey'],$contact['pubkey']);

	post_url($contact['notify'],$slap, array(
		'Content-type: application/magic-envelope+xml',
		'Content-length: ' . strlen($slap)
	));
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

	$slap = diaspora_msg_build($msg,$owner,$contact,$owner['uprvkey'],$contact['pubkey']);

	post_url($contact['notify'],$slap, array(
		'Content-type: application/magic-envelope+xml',
		'Content-length: ' . strlen($slap)
	));
	$return_code = $a->get_curl_code();
	logger('diaspora_send_status: returns: ' . $return_code);
	return $return_code;
}

