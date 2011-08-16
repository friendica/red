<?php

/**
 * Diaspora endpoint
 */


require_once('include/salmon.php');
require_once('include/crypto.php');
require_once('include/diaspora.php');


	
function receive_post(&$a) {

	if($a->argc != 3 || $a->argv[1] !== 'users')
		http_status_exit(500);

	$guid = $a->argv[2];

	$r = q("SELECT * FROM `user` WHERE `guid` = '%s' LIMIT 1",
		dbesc($guid)
	);
	if(! count($r))
		http_status_exit(500);

	$importer = $r[0];

	// I really don't know why we need urldecode - PHP should be doing this for us.
	// It is an application/x-www-form-urlencoded

	$xml = urldecode($_POST['xml']);

	logger('mod-diaspora: new salmon ' . $xml, LOGGER_DATA);

	if(! $xml)
		http_status_exit(500);

	$msg = diaspora_decode($importer,$xml);

	logger('mod-diaspora: decoded msg: ' . $msg, LOGGER_DATA);

	if(! $msg)
		http_status_exit(500);


	$parsed_xml = parse_xml_string($msg,false);

	$xmlbase = $parsed_xml->post;

	// If we reached this point, the message is good. 
	// Now let's figure out if the author is allowed to send us stuff.

	$r = q("SELECT * FROM `contact` WHERE `network` = 'dspr' AND ( `url` = '%s' OR `alias` = '%s') 
		AND `uid` = %d LIMIT 1",
		dbesc($author_link),
		dbesc($author_link),
		intval($importer['uid'])
	);
	if(! count($r)) {
		logger('mod-diaspora: Author unknown to us.');
	}	

	// is this a follower? Or have we ignored the person?
	// If so we can not accept this post.
	// However we will accept a sharing e.g. friend request
	// or a retraction of same.


	$allow_blocked = (($xmlbase->request || ($xmlbase->retraction && $xmlbase->retraction->type == 'Person')) ? true : false);

	if((count($r)) 
		&& (($r[0]['rel'] == CONTACT_IS_FOLLOWER) || ($r[0]['blocked']) || ($r[0]['readonly'])) 
		&& (! $allow_blocked)) {
			logger('mod-diaspora: Ignoring this author.');
			http_status_exit(202);
			// NOTREACHED
	}

	require_once('include/items.php');

	$contact = ((count($r)) ? $r[0] : null);

	if($xmlbase->request) {
		diaspora_request($importer,$contact,$xmlbase->request);
	}
	elseif($xmlbase->status_message) {
		diaspora_post($importer,$contact,$xmlbase->status_message);
	}
	elseif($xmlbase->comment) {
		diaspora_comment($importer,$contact,$xmlbase->comment);
	}
	elseif($xmlbase->like) {
		diaspora_like($importer,$contact,$xmlbase->like);
	}
	elseif($xmlbase->retraction) {
		diaspora_retraction($importer,$contact,$xmlbase->retraction);
	}
	else {
		logger('mod-diaspora: unknown message type: ' . print_r($xmlbase,true));
	}

	http_status_exit(200);
	// NOTREACHED
}

