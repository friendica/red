<?php

/**
 * Diaspora endpoint
 */


require_once('include/salmon.php');
require_once('include/certfns.php');
require_once('include/diaspora.php');


	
function receive_post(&$a) {

	if($a->argc != 3 || $a->argv[1] !== 'users')
		receive_return(500);

	$guid = $a->argv[2];

	$r = q("SELECT * FROM `user` WHERE `guid` = '%s' LIMIT 1",
		dbesc($guid)
	);
	if(! count($r))
		receive_return(500);

	$importer = $r[0];

	$xml = $_POST['xml'];

	logger('mod-diaspora: new salmon ' . $xml, LOGGER_DATA);

	if(! $xml)
		receive_return(500);

	$msg = diaspora_decode($importer,$xml);
	if(! $msg)
		receive_return(500);

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

	if((count($r)) && (($r[0]['readonly']) || ($r[0]['rel'] == CONTACT_IS_FOLLOWER) || ($r[0]['blocked']))) {
		logger('mod-diaspora: Ignoring this author.');
		receive_return(202);
		// NOTREACHED
	}

	require_once('include/items.php');

	// Placeholder for hub discovery. We shouldn't find any hubs
	// since we supplied the fake feed header - and it doesn't have any.

	$hub = '';

	/**
	 *
	 * anti-spam measure: consume_feed will accept a follow activity from 
	 * this person (and nothing else) if there is no existing contact record.
	 *
	 */

	$contact_rec = ((count($r)) ? $r[0] : null);


	receive_return(200);




}

