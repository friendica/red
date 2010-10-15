<?php

function hub_return($valid,$body) {
	
	if($valid) {
		header($_SERVER["SERVER_PROTOCOL"] . ' 200 ' . 'OK');
		echo $body;
		killme();
	}
	else {
		header($_SERVER["SERVER_PROTOCOL"] . ' 404 ' . 'Not Found');
		killme();
	}

	// NOTREACHED
}

// when receiving an XML feed, always return OK

function hub_post_return() {
	
	header($_SERVER["SERVER_PROTOCOL"] . ' 200 ' . 'OK');
	killme();

}



function pubsub_init(&$a) {

	$nick       = (($a->argc > 1) ? notags(trim($a->argv[1])) : '');
	$contact_id = (($a->argc > 2) ? intval($a->argv[2]) : 0);

	if($_SERVER['REQUEST_METHOD'] === 'GET') {

		$hub_mode = notags(trim($_GET['hub_mode']));
		$hub_topic = notags(trim($_GET['hub_topic']));
		$hub_challenge = notags(trim($_GET['hub_challenge']));
		$hub_lease = notags(trim($_GET['hub_lease_seconds']));
		$hub_verify = notags(trim($_GET['hub_verify_token']));

		$debugging = get_config('system','debugging');
		if($debugging) {
			file_put_contents('pubsub.out', 'Pubsubhubbub subscription called from ' . $_SERVER['REMOTE_ADDR'] . ' at ' . datetime_convert() . "\n" . print_r($_GET,true), FILE_APPEND);
		}

		$subscribe = (($hub_mode === 'subscribe') ? 1 : 0);

		$r = q("SELECT * FROM `user` WHERE `nickname` = '%s' LIMIT 1",
			dbesc($nick)
		);
		if(! count($r))
			hub_return(false, '');


		$owner = $r[0];

		$sql_extra = ((strlen($hub_verify)) ? sprintf(" AND `hub-verify` = '%s' ", dbesc($hub_verify)) : '');

		$r = q("SELECT * FROM `contact` WHERE `poll` = '%s' AND `id` = %d AND `uid` = %d AND `blocked` = 0 $sql_extra LIMIT 1",
			dbesc($hub_topic),
			intval($contact_id),
			intval($owner['uid'])
		);
		if(! count($r))
			hub_return(false, '');

		$contact = $r[0];

		// We must initiate an unsubscribe request with a verify_token. 
		// Don't allow outsiders to unsubscribe us.

		if(($hub_mode === 'unsubscribe') && (! strlen($hub_verify))) 
			hub_return(false, '');

		$r = q("UPDATE `contact` SET `subhub` = %d WHERE `id` = %d LIMIT 1",
			intval($subscribe),
			intval($contact['id'])
		);

 		hub_return(true, $hub_challenge);
		
	}
}


function pubsub_post(&$a) {

	$xml = file_get_contents('php://input');

	$debugging = get_config('system','debugging');
	$remote_host = 'Pubsub feed arrived from ' . $_SERVER['REMOTE_ADDR'] . ' at ' . datetime_convert() . ' for ' .  $a->cmd . "\n\n";
	if($debugging)
		file_put_contents('pubsub.out', $remote_host . $xml, FILE_APPEND);

	$nick       = (($a->argc > 1) ? notags(trim($a->argv[1])) : '');
	$contact_id = (($a->argc > 2) ? intval($a->argv[2]) : 0);

	$r = q("SELECT * FROM `user` WHERE `nickname` = '%s' LIMIT 1",
		dbesc($nick)
	);
	if(! count($r))
		hub_post_return();

	$importer = $r[0];

	$r = q("SELECT * FROM `contact` WHERE `subhub` = 1 AND `id` = %d AND `uid` = %d AND `blocked` = 0 LIMIT 1",
		intval($contact_id),
		intval($importer['uid'])
	);
	if(! count($r))
		hub_post_return();

	$contact = $r[0];

	$feedhub = '';

	require_once('include/items.php');

	consume_feed($xml,$importer,$contact,$feedhub);

	hub_post_return();

}



