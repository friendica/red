<?php

require_once("boot.php");

$a = new App;

@include(".htconfig.php");
require_once("dba.php");
$db = new dba($db_host, $db_user, $db_pass, $db_data, $install);
	unset($db_host, $db_user, $db_pass, $db_data);

require_once("session.php");
require_once("datetime.php");

// FIXME - generalise for other content, probably create a notify queue in 
// the db with type and recipient list

if($argc < 3)
	exit;

	$baseurl = trim(hex2bin($argv[1]));

	$cmd = $argv[2];

	switch($cmd) {

		default:
			$item_id = intval($argv[3]);
			if(! $item_id)
				killme();
			break;
	}


	$is_parent = false;

	$recipients = array();

	// fetch requested item

	$r = q("SELECT `item`.*,  `contact`.*,`item`.`id` AS `item_id` FROM `item` LEFT JOIN `contact` ON `item`.`contact-id` = `contact`.`id` 
		WHERE `item`.`id` = %d LIMIT 1",
		intval($item_id)
	);
	if(! count($r))
		killme();

	$item = $r[0];

	$recipients[] = $item['contact-id'];

	if($item['parent'] == $item['id']) {
		$is_parent = true;
	}
	else {
		$r = q("SELECT * FROM `item` WHERE `id` = %d LIMIT 1",
			intval($item['parent'])
		);
		if(count($r))
			$parent = $r[0];
	}

	if(is_array($parent))
		$recipients[] = $parent['contact-id'];

	$r = q("SELECT `contact-id` FROM `item` WHERE `hash` = '%s' AND `id` != %d AND `id` != %d",
		dbesc($item['hash']),
		intval($item['id']),
		intval($item['parent'])
	);
	if(count($r)) {
		foreach($r as $rr) {
			if($rr['contact-id'] != $item['contact-id'])
				$recipients[] = $rr['contact-id'];
		}
	}

	$tpl = file_get_contents('view/atomic.tpl');

	// FIXME should dump the entire conversation

	$atom = replace_macros($tpl, array(
		'$feed_id' => xmlify($baseurl),
		'$feed_title' => xmlify('Wall Item'),
		'$feed_updated' => xmlify(datetime_convert('UTC','UTC',$item['edited'] . '+00:00' ,'Y-m-d\Th:i:s\Z')) ,
		'$name' => xmlify($item['name']),
		'$profile_page' => xmlify($item['url']),
		'$thumb' => xmlify($item['thumb']),
		'$item_id' => xmlify($item['hash'] . '-' . $item['id']),
		'$title' => xmlify(''),
		'$link' => xmlify($baseurl . '/item/' . $item['id']),
		'$updated' => xmlify(datetime_convert('UTC','UTC',$item['edited'] . '+00:00' ,'Y-m-d\Th:i:s\Z')),
		'$summary' => xmlify(''),
		'$content' => xmlify($item['body'])
	));

print_r($atom);
	// atomify

	// expand list of recipients

dbg(3);


	$recipients = array_unique($recipients);
print_r($recipients);
	$recip_str = implode(', ', $recipients);

	$r = q("SELECT * FROM `contact` WHERE `id` IN ( %s ) ",
		dbesc($recip_str)
	);
	if(! count($r))
		killme();

	// delivery loop

	foreach($r as $rr) {
		if($rr['self'])
			continue;

		if(! strlen($rr['dfrn-id']))
			continue;
		$url = $rr['notify'] . '?dfrn_id=' . $rr['dfrn-id'];
print_r($url);
		$xml = fetch_url($url);
echo $xml;

print_r($xml);
		if(! $xml)
			continue;

		$res = simplexml_load_string($xml);
print_r($res);
var_dump($res);

		if((intval($res->status) != 0) || (! strlen($res->challenge)) || ($res->dfrn_id != $rr['dfrn-id']))
			continue;

		$postvars = array();

		$postvars['dfrn_id'] = $rr['dfrn-id'];
		$challenge = hex2bin($res->challenge);
echo "dfrn-id:" . $res->dfrn_id . "\r\n";
echo "challenge:" . $res->challenge . "\r\n";
echo "pubkey:" . $rr['pubkey'] . "\r\n";

		openssl_public_decrypt($challenge,$postvars['challenge'],$rr['pubkey']);

		$postvars['data'] = $atom;

print_r($postvars);
		$xml = fetch_url($url,$postvars);

				
	}

	killme();

