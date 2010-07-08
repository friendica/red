<?php

echo getcwd();
require_once("boot.php");

$a = new App;

@include(".htconfig.php");
require_once("dba.php");
$db = new dba($db_host, $db_user, $db_pass, $db_data, $install);
	unset($db_host, $db_user, $db_pass, $db_data);

require_once("session.php");
require_once("datetime.php");

if(($argc != 2) || (! intval($argv[1])))
	exit;

	$is_parent = false;
	$item_id = $argv[1];

	$r = q("SELECT `item`.*,  `contact`.*,`item`.`id` AS `item_id` FROM `item` LEFT JOIN `contact` ON `item`.`contact-id` = `contact`.`id` 
		WHERE `item`.`id` = %d LIMIT 1",
		intval($item_id)
	);
	if(! count($r))
		killme();

	$item = $r[0];

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

	$commenters = array(); 

	$r = q("SELECT `contact-id` FROM `item` WHERE `hash` = '%s' AND `id` != %d AND `id` != %d",
		dbesc($item['hash']),
		intval($item['id']),
		intval($item['parent'])
	);
	if(count($r)) {
		foreach($r as $rr) {
			if($rr['contact-id'] != $item['contact-id'])
				$commenters[] = $rr['contact-id'];
		}
	}

	$tpl = file_get_contents('view/atomic.tpl');
	
	$atom = replace_macros($tpl, array(
		'$feed_id' => $a->get_baseurl(),
		'$feed_title' => 'Wall Item',
		'$feed_updated' => datetime_convert('UTC','UTC',$item['edited'] . '+00:00' ,'Y-m-d\Th:i:s\Z') ,
		'$name' => $item['name'],
		'$profile_page' => $item['url'],
		'$thumb' => $item['thumb'],
		'$item_id' => $item['hash'] . '-' . $item['id'],
		'$title' => '',
		'$link' => $a->get_baseurl() . '/item/' . $item['id'],
		'$updated' => datetime_convert('UTC','UTC',$item['edited'] . '+00:00' ,'Y-m-d\Th:i:s\Z'),
		'$summary' => '',
		'$content' => $item['body']
	));

print_r($atom);
	// atomify

	// expand list of recipients

	// grab the contact records

	// foreach recipient

	// if no dfrn-id continue

	// fetch_url dfrn-notify

	// decrypt challenge

	// post result

	// continue

		killme();

