<?php /** @file */

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
 *
 * The basic flow is:
 *   Identify the type of message
 *   Collect any information that needs to be sent
 *   Convert it into a suitable generic format for sending
 *   Figure out who the recipients are and if we need to relay 
 *       through a conversation owner
 *   Once we know what recipients are involved, collect a list of 
 *       destination sites
 *   Build and store a queue item for each unique site and invoke
 *       a delivery process for each site or a small number of sites (1-3)
 *       and add a slight delay between each delivery invocation if desired (usually)
 *   
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
 *       permission_update      abook_id
 *       refresh_all            channel_id
 *       purge_all              channel_id
 *       expire                 channel_id
 *       relay					item_id (item was relayed to owner, we will deliver it as owner)
 *       location               channel_id
 *       request                channel_id            xchan_hash             message_id
 *       rating                 xlink_id
 *
 */

require_once('include/cli_startup.php');
require_once('include/zot.php');
require_once('include/queue_fn.php');

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

	require_once('include/identity.php');
	$sys = get_sys_channel();

	if($cmd == 'permission_update') {
		// Get the recipient	
		$r = q("select abook.*, hubloc.* from abook 
			left join hubloc on hubloc_hash = abook_xchan
			where abook_id = %d and not ( abook_flags & %d )>0 limit 1",
			intval($item_id),
			intval(ABOOK_FLAG_SELF)
		);
		if($r) {
			// Get the sender
			$s = q("select * from channel left join xchan on channel_hash = xchan_hash where channel_id = %d limit 1",
				intval($r[0]['abook_channel'])
			);
			if($s) {
				if($r[0]['hubloc_network'] === 'diaspora' || $r[0]['hubloc_network'] === 'friendica-over-diaspora') {
					require_once('include/diaspora.php');
					diaspora_share($s[0],$r[0]);
				}
				else {
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

		return;
	}	


	$expire = false;
	$request = false;
	$mail = false;
	$fsuggest = false;
	$top_level = false;
	$location  = false;
	$recipients = array();
	$url_recipients = array();
	$normal_mode = true;
	$packet_type = 'undefined';

	if($cmd === 'mail') {
		$normal_mode = false;
		$mail = true;
		$private = true;
		$message = q("SELECT * FROM `mail` WHERE `id` = %d LIMIT 1",
				intval($item_id)
		);
		if(! $message) {
			return;
		}
		xchan_mail_query($message[0]);
		$uid = $message[0]['channel_id'];
		$recipients[] = $message[0]['from_xchan']; // include clones
		$recipients[] = $message[0]['to_xchan'];
		$item = $message[0];

		$encoded_item = encode_mail($item);

		$s = q("select * from channel where channel_id = %d limit 1",
			intval($item['channel_id'])
		);
		if($s)
			$channel = $s[0];

	}
	elseif($cmd === 'request') {
		$channel_id = $item_id;
		$xchan = $argv[3];
		$request_message_id = $argv[4];

		$s = q("select * from channel where channel_id = %d limit 1",
			intval($channel_id)
		);
		if($s)
			$channel = $s[0];

		$private = true;
		$recipients[] = $xchan;
		$packet_type = 'request';
		$normal_mode = false;
	}
	elseif($cmd === 'expire') {

		// FIXME
		// This will require a special zot packet containing a list of item message_id's to be expired. 
		// This packet will be public, since we cannot selectively deliver here. 
		// We need the handling on this end to create the array, and the handling on the remote end
		// to verify permissions (for each item) and process it. Until this is complete, the expire feature will be disabled.
 
		return;

		$normal_mode = false;
		$expire = true;
		$items = q("SELECT * FROM item WHERE uid = %d AND ( item_flags & %d )>0
			AND ( item_restrict & %d )>0 AND `changed` > %s - INTERVAL %s",
			intval($item_id),
			intval(ITEM_WALL),
			intval(ITEM_DELETED),
			db_utcnow(), db_quoteinterval('10 MINUTE')
		);
		$uid = $item_id;
		$item_id = 0;
		if(! $items)
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
	elseif($cmd === 'refresh_all') {
		logger('notifier: refresh_all: ' . $item_id);
		$s = q("select * from channel where channel_id = %d limit 1",
			intval($item_id)
		);
		if($s)
			$channel = $s[0];
		$uid = $item_id;
		$recipients = array();
		$r = q("select abook_xchan from abook where abook_channel = %d",
			intval($item_id)
		);
		if($r) {
			foreach($r as $rr) {
				$recipients[] = $rr['abook_xchan'];
			}
		}
		$private = false;
		$packet_type = 'refresh';
	}
	elseif($cmd === 'location') {
		logger('notifier: location: ' . $item_id);
		$s = q("select * from channel where channel_id = %d limit 1",
			intval($item_id)
		);
		if($s)
			$channel = $s[0];
		$uid = $item_id;
		$recipients = array();
		$r = q("select abook_xchan from abook where abook_channel = %d",
			intval($item_id)
		);
		if($r) {
			foreach($r as $rr) {
				$recipients[] = $rr['abook_xchan'];
			}
		}

		$encoded_item = array('locations' => zot_encode_locations($channel),'type' => 'location', 'encoding' => 'zot');
		$target_item = array('aid' => $channel['channel_account_id'],'uid' => $channel['channel_id']);
		$private = false;
		$packet_type = 'location';
		$location = true;
	}
	elseif($cmd === 'purge_all') {
		logger('notifier: purge_all: ' . $item_id);
		$s = q("select * from channel where channel_id = %d limit 1",
			intval($item_id)
		);
		if($s)
			$channel = $s[0];
		$uid = $item_id;
		$recipients = array();
		$r = q("select abook_xchan from abook where abook_channel = %d",
			intval($item_id)
		);
		if($r) {
			foreach($r as $rr) {
				$recipients[] = $rr['abook_xchan'];
			}
		}
		$private = false;
		$packet_type = 'purge';
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

		if($target_item['item_restrict'] & ITEM_DELETED)
			logger('notifier: target item ITEM_DELETED', LOGGER_DEBUG);

		$unforwardable = ITEM_UNPUBLISHED|ITEM_DELAYED_PUBLISH|ITEM_WEBPAGE|ITEM_BUILDBLOCK|ITEM_PDL;
		if($target_item['item_restrict'] & $unforwardable) {
			logger('notifier: target item not forwardable: flags ' . $target_item['item_restrict'], LOGGER_DEBUG);
			return;
		}

		$s = q("select * from channel where channel_id = %d limit 1",
			intval($target_item['uid'])
		);
		if($s)
			$channel = $s[0];

		if($channel['channel_hash'] !== $target_item['author_xchan'] && $channel['channel_hash'] !== $target_item['owner_xchan']) {
			logger("notifier: Sending channel {$channel['channel_hash']} is not owner {$target_item['owner_xchan']} or author {$target_item['author_xchan']}");
			return;
		}


		if($target_item['id'] == $target_item['parent']) {
			$parent_item = $target_item;
			$top_level_post = true;
		}
		else {
			// fetch the parent item
			$r = q("SELECT * from item where id = %d order by id asc",
				intval($target_item['parent'])
			);

			if(! $r)
				return;
			xchan_query($r);
			$r = fetch_post_tags($r);
		
			$parent_item = $r[0];
			$top_level_post = false;
		}

		// avoid looping of discover items 12/4/2014

		if($sys && $parent_item['uid'] == $sys['channel_id'])
			return;

		$encoded_item = encode_item($target_item);
		
		$relay_to_owner = (((! $top_level_post) && ($target_item['item_flags'] & ITEM_ORIGIN)) ? true : false);

		$uplink = false;

		// $cmd === 'relay' indicates the owner is sending it to the original recipients
		// don't allow the item in the relay command to relay to owner under any circumstances, it will loop

		logger('notifier: relay_to_owner: ' . (($relay_to_owner) ? 'true' : 'false'), LOGGER_DATA);
		logger('notifier: top_level_post: ' . (($top_level_post) ? 'true' : 'false'), LOGGER_DATA);
		logger('notifier: target_item_flags: ' . $target_item['item_flags'] . ' ' . (($target_item['item_flags'] & ITEM_ORIGIN ) ? 'true' : 'false'), LOGGER_DATA);

		// tag_deliver'd post which needs to be sent back to the original author

		if(($cmd === 'uplink') && ($parent_item['item_flags'] & ITEM_UPLINK) && (! $top_level_post)) {
			logger('notifier: uplink');			
			$uplink = true;
		} 

		if(($relay_to_owner || $uplink) && ($cmd !== 'relay')) {
			logger('notifier: followup relay', LOGGER_DEBUG);
			$recipients = array(($uplink) ? $parent_item['source_xchan'] : $parent_item['owner_xchan']);
			$private = true;
			if(! $encoded_item['flags'])
				$encoded_item['flags'] = array();
			$encoded_item['flags'][] = 'relay';
		}
		else {
			logger('notifier: normal distribution', LOGGER_DEBUG);
			if($cmd === 'relay')
				logger('notifier: owner relay');

			// if our parent is a tag_delivery recipient, uplink to the original author causing
			// a delivery fork. 

			if(($parent_item['item_flags'] & ITEM_UPLINK) && (! $top_level_post) && ($cmd !== 'uplink')) {
				logger('notifier: uplinking this item');
				proc_run('php','include/notifier.php','uplink',$item_id);
			}

			$private = false;
			$recipients = collect_recipients($parent_item,$private);

			// FIXME add any additional recipients such as mentions, etc.

			// don't send deletions onward for other people's stuff
			// TODO verify this is needed - copied logic from same place in old code

			if(($target_item['item_restrict'] & ITEM_DELETED) && (!($target_item['item_flags'] & ITEM_WALL))) {
				logger('notifier: ignoring delete notification for non-wall item');
				return;
			}
		}

	}

	$walltowall = (($top_level_post && $channel['xchan_hash'] === $target_item['author_xchan']) ? true : false); 

	// Generic delivery section, we have an encoded item and recipients
	// Now start the delivery process

	$x = $encoded_item;
	$x['title'] = 'private';
	$x['body'] = 'private';
	logger('notifier: encoded item: ' . print_r($x,true), LOGGER_DATA);

	stringify_array_elms($recipients);
	if(! $recipients)
		return;

//	logger('notifier: recipients: ' . print_r($recipients,true));

	$env_recips = (($private) ? array() : null);

	$details = q("select xchan_hash, xchan_instance_url, xchan_network, xchan_addr, xchan_guid, xchan_guid_sig from xchan where xchan_hash in (" . implode(',',$recipients) . ")");

	$recip_list = array();

	if($details) {
		foreach($details as $d) {

			// If the recipient is federated from a traditional network they won't be able to 
			// handle nomadic identity. If we're publishing from a site that they aren't
			// directly connected with, ignore them.

			// FIXME: make sure we run through a notifier loop on the hub they're connected
			// with if this post comes in from a different hub - so that we will deliver to them.

			// On the down side, these channels will stop working if the hub they connected with
			// goes down permanently, as they are (doh) not nomadic. 

			if(($d['xchan_instance_url']) && ($d['xchan_instance_url'] != z_root()))
				continue; 


			$recip_list[] = $d['xchan_addr'] . ' (' . $d['xchan_hash'] . ')'; 
			if($private)
				$env_recips[] = array('guid' => $d['xchan_guid'],'guid_sig' => $d['xchan_guid_sig'],'hash' => $d['xchan_hash']);
		}
	}

	if(($private) && (! $env_recips)) {
		// shouldn't happen
		logger('notifier: private message with no envelope recipients.' . print_r($argv,true));
	}
	
	logger('notifier: recipients (may be delivered to more if public): ' . print_r($recip_list,true), LOGGER_DEBUG);
	

	// Now we have collected recipients (except for external mentions, FIXME)
	// Let's reduce this to a set of hubs.

	logger('notifier: hub choice: ' . intval($relay_to_owner) . ' ' . intval($private) . ' ' . $cmd, LOGGER_DEBUG);

	if($relay_to_owner && (! $private) && ($cmd !== 'relay')) {

		// If sending a followup to the post owner, only send it to one channel clone - to avoid race conditions.
		// In this case we'll pick the most recently contacted hub, as their primary might be down and the most
		// recently contacted has the best chance of being alive.

		// For private posts or uplinks we have to do things differently as only the sending clone will have the recipient list. 
		// We have to send to all clone channels of the owner to find out who has the definitive list. Posts with 
		// item_private set (but no ACL list) will return empty recipients (except for the sender and owner) in 
		// collect_recipients() above. The end result is we should get only one delivery per delivery chain if we 
		// aren't the owner or author.  


		$r = q("select hubloc_guid, hubloc_url, hubloc_sitekey, hubloc_network, hubloc_flags, hubloc_callback, hubloc_host from hubloc 
			where hubloc_hash in (" . implode(',',$recipients) . ") order by hubloc_connected desc limit 1");
	} 
	else {
		$r = q("select hubloc_guid, hubloc_url, hubloc_sitekey, hubloc_network, hubloc_flags, hubloc_callback, hubloc_host from hubloc 
			where hubloc_hash in (" . implode(',',$recipients) . ") and not (hubloc_flags & %d) > 0  and not (hubloc_status & %d) > 0",
			intval(HUBLOC_FLAGS_DELETED),
			intval(HUBLOC_OFFLINE)
		);		
	} 

	if(! $r) {
		logger('notifier: no hubs');
		return;
	}

	$hubs = $r;


	/**
	 * Reduce the hubs to those that are unique. For zot hubs, we need to verify uniqueness by the sitekey, since it may have been 
	 * a re-install which has not yet been detected and pruned.
	 * For other networks which don't have or require sitekeys, we'll have to use the URL
	 */


	$hublist = array(); // this provides an easily printable list for the logs
	$dhubs   = array(); // delivery hubs where we store our resulting unique array
	$keys    = array(); // array of keys to check uniquness for zot hubs
	$urls    = array(); // array of urls to check uniqueness of hubs from other networks


	foreach($hubs as $hub) {
		if($hub['hubloc_network'] == 'zot') {
			if(! in_array($hub['hubloc_sitekey'],$keys)) {
				$hublist[] = $hub['hubloc_host'];
				$dhubs[] = $hub;
				$keys[] = $hub['hubloc_sitekey'];
			}
		}
		else {
			if(! in_array($hub['hubloc_url'],$urls)) {
				$hublist[] = $hub['hubloc_host'];
				$dhubs[] = $hub;
				$urls[] = $hub['hubloc_url'];
			}
		}
	}

	logger('notifier: will notify/deliver to these hubs: ' . print_r($hublist,true), LOGGER_DEBUG);
			 
	$interval = ((get_config('system','delivery_interval') !== false) 
			? intval(get_config('system','delivery_interval')) : 2 );

	$deliveries_per_process = intval(get_config('system','delivery_batch_count'));

	if($deliveries_per_process <= 0)
		$deliveries_per_process = 1;

	$deliver = array();

	foreach($dhubs as $hub) {

		if(defined('DIASPORA_RELIABILITY_EMULATION')) {
			$cointoss = mt_rand(0,2);
			if($cointoss == 2) {
				continue;
			}
		}


		if($hub['hubloc_network'] === 'diaspora' || $hub['hubloc_network'] === 'friendica-over-diaspora') {
			if(! get_config('system','diaspora_enabled'))
				continue;

			require_once('include/diaspora.php');

			diaspora_process_outbound(array(
				'channel' => $channel,
				'env_recips' => $env_recips,
				'recipients' => $recipients,
				'item' => $item,
				'target_item' => $target_item,
				'hub' => $hub,
				'top_level_post' => $top_level_post,
				'private' => $private,
				'followup' => $followup,
				'relay_to_owner' => $relay_to_owner,
				'uplink' => $uplink,
				'cmd' => $cmd,
				'expire' =>	$expire,
				'mail' => $mail,
				'location' => $location,
				'fsuggest' => $fsuggest,
				'request' => $request,
				'normal_mode' => $normal_mode,
				'packet_type' => $packet_type,
				'walltowall' => $walltowall
			));

			continue;

		}


		// default: zot protocol


		$hash = random_string();
		if($packet_type === 'refresh' || $packet_type === 'purge') {
			$n = zot_build_packet($channel,$packet_type);
			q("insert into outq ( outq_hash, outq_account, outq_channel, outq_driver, outq_posturl, outq_async, outq_created, outq_updated, outq_notify, outq_msg ) values ( '%s', %d, %d, '%s', '%s', %d, '%s', '%s', '%s', '%s' )",
				dbesc($hash),
				intval($channel['channel_account_id']),
				intval($channel['channel_id']),
				dbesc('zot'),
				dbesc($hub['hubloc_callback']),
				intval(1),
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				dbesc($n),
				dbesc('')
			);
		}
		elseif($packet_type === 'request') {
			$n = zot_build_packet($channel,'request',$env_recips,$hub['hubloc_sitekey'],$hash,array('message_id' => $request_message_id));
			q("insert into outq ( outq_hash, outq_account, outq_channel, outq_driver, outq_posturl, outq_async, outq_created, outq_updated, outq_notify, outq_msg ) values ( '%s', %d, %d, '%s', '%s', %d, '%s', '%s', '%s', '%s' )",
				dbesc($hash),
				intval($channel['channel_account_id']),
				intval($channel['channel_id']),
				dbesc('zot'),
				dbesc($hub['hubloc_callback']),
				intval(1),
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				dbesc($n),
				dbesc('')
			);
		}
		else {
			$n = zot_build_packet($channel,'notify',$env_recips,(($private) ? $hub['hubloc_sitekey'] : null),$hash);
			q("insert into outq ( outq_hash, outq_account, outq_channel, outq_driver, outq_posturl, outq_async, outq_created, outq_updated, outq_notify, outq_msg ) values ( '%s', %d, %d, '%s', '%s', %d, '%s', '%s', '%s', '%s' )",
				dbesc($hash),
				intval($target_item['aid']),
				intval($target_item['uid']),
				dbesc('zot'),
				dbesc($hub['hubloc_callback']),
				intval(1),
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				dbesc($n),
				dbesc(json_encode($encoded_item))
			);
		}
		$deliver[] = $hash;

		if(count($deliver) >= $deliveries_per_process) {
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

	logger('notifier: basic loop complete.', LOGGER_DEBUG);
	
	if($normal_mode)
		call_hooks('notifier_normal',$target_item);


	call_hooks('notifier_end',$target_item);

	logger('notifer: complete.');
	return;

}


if (array_search(__file__,get_included_files())===0){
  notifier_run($argv,$argc);
  killme();
}
