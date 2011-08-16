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

	if($xmlbase->request) {
		diaspora_request($importer,$xmlbase->request);
	}
	elseif($xmlbase->status_message) {
		diaspora_post($importer,$xmlbase->status_message);
	}
	elseif($xmlbase->comment) {
		diaspora_comment($importer,$xmlbase->comment);
	}
	elseif($xmlbase->like) {
		diaspora_like($importer,$xmlbase->like);
	}
	elseif($xmlbase->retraction) {
		diaspora_retraction($importer,$xmlbase->retraction);
	}
	else {
		logger('mod-diaspora: unknown message type: ' . print_r($xmlbase,true));
	}

	http_status_exit(200);
	// NOTREACHED
}

