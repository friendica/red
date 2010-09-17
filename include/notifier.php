<?php

	$debugging = true;

	require_once("boot.php");

	$a = new App;

	@include(".htconfig.php");
	require_once("dba.php");
	$db = new dba($db_host, $db_user, $db_pass, $db_data);
		unset($db_host, $db_user, $db_pass, $db_data);

	require_once("session.php");
	require_once("datetime.php");
	require_once('include/items.php');

	if($argc < 3)
		exit;

	$a->set_baseurl(get_config('system','url'));

	$cmd = $argv[1];

	switch($cmd) {

		case 'mail':
		default:
			$item_id = intval($argv[2]);
			if(! $item_id)
				killme();
			break;
	}

	if($debugging)
		dbg(3);

	$recipients = array();

	if($cmd == 'mail') {

		$message = q("SELECT * FROM `mail` WHERE `id` = %d LIMIT 1",
				intval($item_id)
		);
		if(! count($message))
			killme();
		$uid = $message[0]['uid'];
		$recipients[] = $message[0]['contact-id'];
		$item = $message[0];

	}
	else {
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
	}

	$r = q("SELECT `contact`.*, `user`.`nickname` 
		FROM `contact` LEFT JOIN `user` ON `user`.`uid` = `contact`.`uid` 
		WHERE `contact`.`uid` = %d AND `contact`.`self` = 1 LIMIT 1",
		intval($uid)
	);

	if(count($r))
		$owner = $r[0];
	else
		killme();

	if($cmd != 'mail') {

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

		$tomb_template = file_get_contents('view/atom_tomb.tpl');
		$item_template = file_get_contents('view/atom_item.tpl');
		$cmnt_template = file_get_contents('view/atom_cmnt.tpl');
	}

	$feed_template = file_get_contents('view/atom_feed.tpl');
	$mail_template = file_get_contents('view/atom_mail.tpl');

	$atom = '';


	$atom .= replace_macros($feed_template, array(
			'$feed_id'      => xmlify($a->get_baseurl() . '/profile/' . $owner['nickname'] ),
			'$feed_title'   => xmlify($owner['name']),
			'$feed_updated' => xmlify(datetime_convert('UTC', 'UTC', $updated . '+00:00' , ATOM_TIME)) ,
			'$name'         => xmlify($owner['name']),
			'$profile_page' => xmlify($owner['url']),
			'$photo'        => xmlify($owner['photo']),
			'$thumb'        => xmlify($owner['thumb']),
			'$picdate'      => xmlify(datetime_convert('UTC','UTC',$owner['avatar-date'] . '+00:00' , ATOM_TIME)) ,
			'$uridate'      => xmlify(datetime_convert('UTC','UTC',$owner['uri-date']    . '+00:00' , ATOM_TIME)) ,
			'$namdate'      => xmlify(datetime_convert('UTC','UTC',$owner['name-date']   . '+00:00' , ATOM_TIME))
	));

	if($cmd == 'mail') {
		$atom .= replace_macros($mail_template, array(
			'$name'         => xmlify($owner['name']),
			'$profile_page' => xmlify($owner['url']),
			'$thumb'        => xmlify($owner['thumb']),
			'$item_id'      => xmlify($item['uri']),
			'$subject'      => xmlify($item['title']),
			'$created'      => xmlify(datetime_convert('UTC', 'UTC', $item['created'] . '+00:00' , ATOM_TIME)),
			'$content'      => xmlify($item['body']),
			'$parent_id'    => xmlify($item['parent-uri'])
		));
	}
	else {


		if($followup) {
			foreach($items as $item) {

				$verb = construct_verb($item);
				$actobj = construct_activity($item);

				if($item['id'] == $item_id) {
					$atom .= replace_macros($cmnt_template, array(
						'$name'               => xmlify($owner['name']),
						'$profile_page'       => xmlify($owner['url']),
						'$thumb'              => xmlify($owner['thumb']),
						'$owner_name'         => xmlify($item['owner-name']),
						'$owner_profile_page' => xmlify($item['owner-link']),
						'$owner_thumb'        => xmlify($item['owner-avatar']),
						'$item_id'            => xmlify($item['uri']),
						'$title'              => xmlify($item['title']),
						'$published'          => xmlify(datetime_convert('UTC', 'UTC', $item['created'] . '+00:00' , ATOM_TIME)),
						'$updated'            => xmlify(datetime_convert('UTC', 'UTC', $item['edited'] . '+00:00' , ATOM_TIME)),
						'$location'           => xmlify($item['location']),
						'$type'               => 'text',
						'$verb'               => xmlify($verb),
						'$actobj'             => $actobj,
						'$content'            => xmlify($item['body']),
						'$parent_id'          => xmlify($item['parent-uri']),
						'$comment_allow'      => 0
					));
				}
			}
		}
		else {
			foreach($items as $item) {
				if($item['deleted']) {
					$atom .= replace_macros($tomb_template, array(
						'$id' => xmlify($item['uri']),
						'$updated' => xmlify(datetime_convert('UTC', 'UTC', $item['edited'] . '+00:00' , ATOM_TIME))
					));
				}
				else {
					foreach($contacts as $contact) {
						if($item['contact-id'] == $contact['id']) {

							$verb = construct_verb($item);
							$actobj = construct_activity($item);

							if($item['parent'] == $item['id']) {
								$atom .= replace_macros($item_template, array(
									'$name'               => xmlify($contact['name']),
									'$profile_page'       => xmlify($contact['url']),
									'$thumb'              => xmlify($contact['thumb']),
									'$owner_name'         => xmlify($item['owner-name']),
									'$owner_profile_page' => xmlify($item['owner-link']),
									'$owner_thumb'        => xmlify($item['owner-avatar']),
									'$item_id'            => xmlify($item['uri']),
									'$title'              => xmlify($item['title']),
									'$published'          => xmlify(datetime_convert('UTC', 'UTC', $item['created'] . '+00:00' , ATOM_TIME)),
									'$updated'            => xmlify(datetime_convert('UTC', 'UTC', $item['edited'] . '+00:00' , ATOM_TIME)),
									'$location'           => xmlify($item['location']),
									'$type'               => 'text',
									'$verb'               => xmlify($verb),
									'$actobj'             => $actobj,
									'$content'            => xmlify($item['body']),
									'$comment_allow'      => (($item['last-child']) ? 1 : 0)
								));
							}
							else {
								$atom .= replace_macros($cmnt_template, array(
									'$name'          => xmlify($contact['name']),
									'$profile_page'  => xmlify($contact['url']),
									'$thumb'         => xmlify($contact['thumb']),
									'$item_id'       => xmlify($item['uri']),
									'$title'         => xmlify($item['title']),
									'$published'     => xmlify(datetime_convert('UTC', 'UTC', $item['created'] . '+00:00' , ATOM_TIME)),
									'$updated'       => xmlify(datetime_convert('UTC', 'UTC', $item['edited'] . '+00:00' , ATOM_TIME)),
									'$content'       => xmlify($item['body']),
									'$location'      => xmlify($item['location']),
									'$type'          => 'text',
									'$verb'          => xmlify($verb),
									'$actobj'        => $actobj,
									'$parent_id'     => xmlify($item['parent-uri']),
									'$comment_allow' => (($item['last-child']) ? 1 : 0)
								));
							}
						}
					}
				}
			}
		}
	}
	$atom .= "</feed>\r\n";

	if($debugging)
		echo $atom;

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

		if((! strlen($rr['dfrn-id'])) && (! $rr['duplex']))
			continue;


		$idtosend = $orig_id = (($rr['dfrn-id']) ? $rr['dfrn-id'] : $rr['issued-id']);

		if($rr['duplex'] && $rr['dfrn-id'])
			$idtosend = '0:' . $orig_id;
		if($rr['duplex'] && $rr['issued-id'])
			$idtosend = '1:' . $orig_id;		

		$url = $rr['notify'] . '?dfrn_id=' . $idtosend;

		if($debugging)
			echo "URL: $url";

		$xml = fetch_url($url);

		if($debugging)
			echo $xml;

		if(! $xml)
			continue;

		$res = simplexml_load_string($xml);

		if((intval($res->status) != 0) || (! strlen($res->challenge)) || (! strlen($res->dfrn_id)))
			continue;

		$postvars     = array();

		$sent_dfrn_id = hex2bin($res->dfrn_id);
		$challenge    = hex2bin($res->challenge);

		$final_dfrn_id = '';

		if($rr['duplex'] && strlen($rr['prvkey'])) {
			openssl_private_decrypt($sent_dfrn_id,$final_dfrn_id,$rr['prvkey']);
			openssl_private_decrypt($challenge,$postvars['challenge'],$rr['prvkey']);
		}
		else {
			openssl_public_decrypt($sent_dfrn_id,$final_dfrn_id,$rr['pubkey']);
			openssl_public_decrypt($challenge,$postvars['challenge'],$rr['pubkey']);
		}

		$final_dfrn_id = substr($final_dfrn_id, 0, strpos($final_dfrn_id, '.'));

		if(strpos($final_dfrn_id,':') == 1)
			$final_dfrn_id = substr($final_dfrn_id,2);

		if($final_dfrn_id != $orig_id) {
			// did not decode properly - cannot trust this site 
			continue;
		}

		$postvars['dfrn_id'] = $idtosend;

		if(($rr['rel']) && ($rr['rel'] != REL_FAN) && (! $rr['blocked']) && (! $rr['readonly'])) {
			$postvars['data'] = $atom;
		}
		else {
			$postvars['data'] = $atom_nowrite;
		}

		$xml = post_url($rr['notify'],$postvars);

		if($debugging)
			echo $xml;

		$res = simplexml_load_string($xml);

		// Currently there is no retry attempt for failed mail delivery.
		// We need to handle this in the UI, report the non-deliverables and try again
 
		if(($cmd == 'mail') && (intval($res->status) == 0)) {

			$r = q("UPDATE `mail` SET `delivered` = 1 WHERE `id` = %d LIMIT 1",
				intval($item_id)
			);
		}
	}

	killme();

