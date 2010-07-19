<?php

print_r($argv);
require_once("boot.php");

$a = new App;

@include(".htconfig.php");
require_once("dba.php");
$db = new dba($db_host, $db_user, $db_pass, $db_data, $install);
	unset($db_host, $db_user, $db_pass, $db_data);

require_once("session.php");
require_once("datetime.php");

if($argc < 3)
	exit;
dbg(3);
	$baseurl = $argv[1];
	$a->set_baseurl($argv[1]);

	$cmd = $argv[2];

	switch($cmd) {

		default:
			$item_id = intval($argv[3]);
			if(! $item_id)
				killme();
			break;
	}


	$recipients = array();

	// find ancestors

	$r = q("SELECT `parent`, `uid`, `edited` FROM `item` WHERE `id` = %d LIMIT 1",
		intval($item_id)
	);
	if(! count($r))
		killme();

	$parent = $r[0]['parent'];
	$uid = $r[0]['uid'];
	$updated = $r[0]['edited'];

	$items = q("SELECT * FROM `item` WHERE `parent` = %d ORDER BY `id` ASC",
		intval($parent)
	);

	if(! count($items))
		killme();

	$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 1 LIMIT 1",
		intval($uid)
	);

	if(count($r))
		$owner = $r[0];
	else
		killme();


	require_once('include/group.php');

	$parent = $items[0];

	if($parent['type'] == 'remote') {
		// local followup to remote post
		$followup = true;
		$conversant_str = dbesc($parent['contact-id']);
	}
	else {
		$followup = false;

		$allow_people = expand_acl($parent['allow_cid']);
		$allow_groups = expand_groups(expand_acl($parent['allow_gid']));
		$deny_people = expand_acl($parent['deny_cid']);
		$deny_groups = expand_groups(expand_acl($parent['deny_gid']));

		$conversants = array();

		foreach($items as $item) {
			$recipients[] = $item['contact-id'];
			$conversants[] = $item['contact-id'];
		}

		$conversants = array_unique($conversants,SORT_NUMERIC);


		$recipients = array_unique(array_merge($recipients,$allow_people,$allow_groups),SORT_NUMERIC);
		$deny = array_unique(array_merge($deny_people,$deny_groups),SORT_NUMERIC);
		$recipients = array_diff($recipients,$deny);
	
		$conversant_str = dbesc(implode(', ',$conversants));
	}

	$r = q("SELECT * FROM `contact` WHERE `id` IN ( $conversant_str ) AND `blocked` = 0 AND `pending` = 0");

	if( ! count($r))
		killme();

	$contacts = $r;


	$feed_template = file_get_contents('view/atom_feed.tpl');
	$tomb_template = file_get_contents('view/atom_tomb.tpl');
	$item_template = file_get_contents('view/atom_item.tpl');
	$cmnt_template = file_get_contents('view/atom_cmnt.tpl');

	$atom = '';


	$atom .= replace_macros($feed_template, array(
			'$feed_id' => xmlify($a->get_baseurl()),
			'$feed_title' => xmlify($owner['name']),
			'$feed_updated' => xmlify(datetime_convert('UTC', 'UTC', 
				$updated . '+00:00' , 'Y-m-d\TH:i:s\Z')) ,
			'$name' => xmlify($owner['name']),
			'$profile_page' => xmlify($owner['url']),
			'$photo' => xmlify($owner['photo'])
	));

	if($followup) {
		foreach($items as $item) {
			if($item['id'] == $item_id) {
				$atom .= replace_macros($cmnt_template, array(
					'$name' => xmlify($owner['name']),
					'$profile_page' => xmlify($owner['url']),
					'$thumb' => xmlify($owner['thumb']),
					'$item_id' => xmlify($item['uri']),
					'$title' => xmlify($item['title']),
					'$published' => xmlify(datetime_convert('UTC', 'UTC', 
						$item['created'] . '+00:00' , 'Y-m-d\TH:i:s\Z')),
					'$updated' => xmlify(datetime_convert('UTC', 'UTC', 
						$item['edited'] . '+00:00' , 'Y-m-d\TH:i:s\Z')),
					'$content' =>xmlify($item['body']),
					'$parent_id' => xmlify($item['parent-uri']),
					'$comment_allow' => 0
				));
			}
		}
	}
	else {
		foreach($items as $item) {
			if($item['deleted']) {
				$atom .= replace_macros($tomb_template, array(
					'$id' => xmlify($item['uri']),
					'$updated' => xmlify(datetime_convert('UTC', 'UTC', 
						$item['edited'] . '+00:00' , 'Y-m-d\TH:i:s\Z'))
				));
			}
			else {
				foreach($contacts as $contact) {
					if($item['contact-id'] == $contact['id']) {
						if($item['parent'] == $item['id']) {
							$atom .= replace_macros($item_template, array(
								'$name' => xmlify($contact['name']),
								'$profile_page' => xmlify($contact['url']),
								'$thumb' => xmlify($contact['thumb']),
								'$owner_name' => xmlify($item['owner-name']),
								'$owner_profile_page' => xmlify($item['owner-link']),
								'$owner_thumb' => xmlify($item['owner-avatar']),
								'$item_id' => xmlify($item['uri']),
								'$title' => xmlify($contact['name']),
								'$published' => xmlify(datetime_convert('UTC', 'UTC', 
									$item['created'] . '+00:00' , 'Y-m-d\TH:i:s\Z')),
								'$updated' => xmlify(datetime_convert('UTC', 'UTC', 
									$item['edited'] . '+00:00' , 'Y-m-d\TH:i:s\Z')),
								'$content' =>xmlify($item['body']),
								'$comment_allow' => (($item['last-child'] && strlen($contact['dfrn-id'])) ? 1 : 0)
							));
						}
						else {
							$atom .= replace_macros($cmnt_template, array(
								'$name' => xmlify($contact['name']),
								'$profile_page' => xmlify($contact['url']),
								'$thumb' => xmlify($contact['thumb']),
								'$item_id' => xmlify($item['uri']),
								'$title' => xmlify($item['title']),
								'$published' => xmlify(datetime_convert('UTC', 'UTC', 
									$item['created'] . '+00:00' , 'Y-m-d\TH:i:s\Z')),
								'$updated' => xmlify(datetime_convert('UTC', 'UTC', 
									$item['edited'] . '+00:00' , 'Y-m-d\TH:i:s\Z')),
								'$content' =>xmlify($item['body']),
								'$parent_id' => xmlify($item['parent-uri']),
								'$comment_allow' => (($item['last-child']) ? 1 : 0)
							));
						}
					}
				}
			}
		}
	}
	$atom .= "</feed>\r\n";

	// create a clone of this feed but with comments disabled to send to those who can't respond. 

	$atom_nowrite = str_replace('<dfrn:comment-allow>1','<dfrn:comment-allow>0',$atom);


	if($followup)
		$recip_str = $parent['contact-id'];
	else
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

		$xml = fetch_url($url);
echo $xml;
		if(! $xml)
			continue;

		$res = simplexml_load_string($xml);

		if((intval($res->status) != 0) || (! strlen($res->challenge)) || ($res->dfrn_id != $rr['dfrn-id']))
			continue;

		$postvars = array();

		$postvars['dfrn_id'] = $rr['dfrn-id'];
		$challenge = hex2bin($res->challenge);

		openssl_public_decrypt($challenge,$postvars['challenge'],$rr['pubkey']);

		if(strlen($rr['dfrn-id']) && (! $rr['blocked']))
			$postvars['data'] = $atom;
		else
			$postvars['data'] = $atom_nowrite;

		$xml = post_url($rr['notify'],$postvars);
echo $xml;
	}

	killme();

