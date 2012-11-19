<?php

require_once("boot.php");
require_once('include/queue_fn.php');
require_once('include/html2plain.php');

/*
 * This file was at one time responsible for doing all deliveries, but this caused
 * big problems on shared hosting systems, where the process might get killed by the 
 * hosting provider and nothing would get delivered. 
 * It now only delivers one message under certain cases, and invokes a queued
 * delivery mechanism (include/deliver.php) to deliver individual contacts at 
 * controlled intervals.
 * This has a much better chance of surviving random processes getting killed
 * by the hosting provider. 
 * A lot of this code is duplicated in include/deliver.php until we have time to go back
 * and re-structure the delivery procedure based on the obstacles that have been thrown at 
 * us by hosting providers. 
 */

/*
 * The notifier is typically called with:
 *
 *		proc_run('php', "include/notifier.php", COMMAND, ITEM_ID);
 *
 * where COMMAND is one of the following:
 *
 *		activity				(in diaspora.php, dfrn_confirm.php, profiles.php)
 *		comment-import			(in diaspora.php, items.php)
 *		comment-new				(in item.php)
 *		drop					(in diaspora.php, items.php, photos.php)
 *		edit_post				(in item.php)
 *		event					(in events.php)
 *		expire					(in items.php)
 *		like					(in like.php, poke.php)
 *		mail					(in message.php)
 *		suggest					(in fsuggest.php)
 *		tag						(in photos.php, poke.php, tagger.php)
 *		tgroup					(in items.php)
 *		wall-new				(in photos.php, item.php)
 *
 * and ITEM_ID is the id of the item in the database that needs to be sent to others.
 *
 * ZOT 
 *       permission_updated     abook_id
 *
 */

require_once('include/cli_startup.php');
require_once('include/zot.php');


function notifier_run($argv, $argc){

	cli_startup();

	$a = get_app();

	require_once("session.php");
	require_once("datetime.php");
	require_once('include/items.php');
	require_once('include/bbcode.php');

	if($argc < 3)
		return;


	logger('notifier: invoked: ' . print_r($argv,true), LOGGER_DEBUG);

	$cmd = $argv[1];

	$item_id = $argv[2];

	$extra = (($argc > 3) ? $argv[3] : null);

	if(! $item_id)
		return;

	if($cmd == 'permission_update') {
		// Get the recipient	
		$r = q("select abook.*, hubloc.* from abook 
			left join hubloc on hubloc_hash = abook_xchan
			where abook_id = %d and not ( abook_flags & %d ) limit 1",
			intval($item_id),
			intval(ABOOK_FLAG_SELF)
		);
		if($r) {
			// Get the sender
			$s = q("select * from channel where channel_id = %d limit 1",
				intval($r[0]['abook_channel'])
			);
			if($s) {

				// send a refresh message to each hub they have registered here	
				$h = q("select * from hubloc where hubloc_hash = '%s'",
					dbesc($r[0]['hubloc_hash'])
				);
				if($h) {
					foreach($h as $hh) {
						$data = zot_build_packet($s[0],'refresh',array(array(
							'guid' => $hh['hubloc_guid'],
							'guid_sig' => $hh['hubloc_guid_sig'],
							'url' => $hh['hubloc_url'])
						));
						if($data) {
							$result = zot_zot($hh['hubloc_callback'],$data);
// zot_queue_item is not yet written
//							if(! $result['success'])
//								zot_queue_item();

						}	
					}
				}
			}
		}
	}	


	$expire = false;
	$mail = false;
	$fsuggest = false;
	$top_level = false;
	$recipients = array();
	$url_recipients = array();
	$normal_mode = true;

	if($cmd === 'mail') {
		$normal_mode = false;
		$mail = true;
		$message = q("SELECT * FROM `mail` WHERE `id` = %d LIMIT 1",
				intval($item_id)
		);
		if(! count($message)){
			return;
		}
		$uid = $message[0]['uid'];
		$recipients[] = $message[0]['contact-id'];
		$item = $message[0];

	}
	elseif($cmd === 'expire') {
		$normal_mode = false;
		$expire = true;
		$items = q("SELECT * FROM `item` WHERE `uid` = %d AND `wall` = 1 
			AND `deleted` = 1 AND `changed` > UTC_TIMESTAMP() - INTERVAL 10 MINUTE",
			intval($item_id)
		);
		$uid = $item_id;
		$item_id = 0;
		if(! count($items))
			return;
	}
	elseif($cmd === 'suggest') {
		$normal_mode = false;
		$fsuggest = true;

		$suggest = q("SELECT * FROM `fsuggest` WHERE `id` = %d LIMIT 1",
			intval($item_id)
		);
		if(! count($suggest))
			return;
		$uid = $suggest[0]['uid'];
		$recipients[] = $suggest[0]['cid'];
		$item = $suggest[0];
	}
	else {

		// Normal items

		// Fetch the target item

		$r = q("SELECT * FROM item WHERE id = %d and parent != 0 LIMIT 1",
			intval($item_id)
		);

		if(! $r)
			return;

		xchan_query($r);
		$r = fetch_post_tags($r);
		
		$target_item = $r[0];
		
		$s = q("select * from channel where channel_id = %d limit 1",
			intval($target_item['uid'])
		);

		if($target_item['id'] == $target_item['parent']) {
			$parent_item = $target_item;
			$top_level_post = true;
		}
		else {
			// fetch the parent item
			$r = q("SELECT * from item where id = %d order by id asc",
				intval($parent_id)
			);
			if(! $r)
				return;
			xchan_query($r);
			$r = fetch_post_tags($r);
		
			$parent_item = $r[0];
			$top_level_post = false;
		}

		$encoded_item = encode_item($target_item);
		
		$relay_to_owner = (((! $top_level_post) && ($target_item['item_flags'] & ITEM_ORIGIN)) ? true : false);
		if($relay_to_owner) {
			logger('notifier: followup relay', LOGGER_DEBUG);
			$recipients = array($parent_item['owner_xchan']);
			if(! $encoded_item['flags'])
				$encoded_item['flags'] = array();
			$encoded_item['flags'][] = 'relay';
		}
		else {
			logger('notifier: normal distribution', LOGGER_DEBUG);
			$recipients = collect_recipients($parent_item);

			// FIXME add any additional recipients such as mentions, etc.


			// don't send deletions onward for other people's stuff
			// TODO verify this is needed - copied logic from same place in old code

			if(($target_item['item_restrict'] & ITEM_DELETED) && (!($target_item['item_flags'] & ITEM_WALL))) {
				logger('notifier: ignoring delete notification for non-wall item');
				return;
			}


		}

		logger('notifier: encoded item: ' . print_r($encoded_item,true));

		stringify_array_elms($recipients);
		if(! $recipients)
			return;
		logger('notifier: recipients: ' . print_r($recipients,true));

		$r = q("select distinct(hubloc_callback),hubloc_host,hubloc_sitekey from hubloc 
			where hubloc_hash in (" . implode(',',$recipients) . ") group by hubloc_callback");
		if(! $r) {
			logger('notifier: no hubs');
			return;
		}
		$hubs = $r;
		if(! $hubs)
			return;
			 
		$interval = ((get_config('system','delivery_interval') !== false) 
				? intval(get_config('system','delivery_interval')) : 2 );

		$deliveries_per_process = intval(get_config('system','delivery_batch_count'));

		if($deliveries_per_process <= 0)
			$deliveries_per_process = 1;

		$deliver = array();
		$current_count = 0;

		foreach($hubs as $hub) {
			$n = zot_build_packet($channel,'notify',null,null);
			$hash = random_string();
			q("insert into outq ( outq_hash, outq_account, outq_channel, outq_posturl, outq_created, outq_updated, outq_notify, outq_msg ) values ( '%s', %d, %d, '%s', '%s', '%s', '%s', '%s' )",
				dbesc($hash),
				intval($target_item['aid']),
				intval($target_item['uid']),
				dbesc($hub['hubloc_callback']),
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				dbesc($n),
				dbesc(json_encode($encoded_item))
			);
			$deliver[] = $hash;
			$current_count ++;
			if($current_count >= $deliveries_per_process) {
				proc_run('php','include/deliver.php',$deliver);
				$deliver = array();
				if($interval)
					@time_sleep_until(microtime(true) + (float) $interval);
			}
		}

		// catch any stragglers

		if(count($deliver)) {
			proc_run('php','include/deliver.php',$deliver);
		}
	
	}

	if($normal_mode)
		call_hooks('notifier_normal',$target_item);

	call_hooks('notifier_end',$target_item);

	return;

}


if (array_search(__file__,get_included_files())===0){
  notifier_run($argv,$argc);
  killme();
}
