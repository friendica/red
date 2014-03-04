<?php /** @file */

require_once('include/crypto.php');
require_once('include/items.php');

/**
 * Red implementation of zot protocol. 
 *
 * https://github.com/friendica/red/wiki/zot
 * https://github.com/friendica/red/wiki/Zot---A-High-Level-Overview
 *
 */


/**
 *
 * @function zot_new_uid($channel_nick)
 *
 *    Generates a unique string for use as a zot guid using our DNS-based url, the channel nickname and some entropy.
 *    The entropy ensures uniqueness against re-installs where the same URL and nickname are chosen.
 *    NOTE: zot doesn't require this to be unique. Internally we use a whirlpool hash of this guid and the signature
 *    of this guid signed with the channel private key. This can be verified and should make the probability of 
 *    collision of the verified result negligible within the constraints of our immediate universe.  
 *
 * @param string channel_nickname = unique nickname of controlling entity
 *
 * @returns string
 *
 */

function zot_new_uid($channel_nick) {
	$rawstr = z_root() . '/' . $channel_nick . '.' . mt_rand();
	return(base64url_encode(hash('whirlpool',$rawstr,true),true));
}


/**
 * @function zot_get_hublocs($hash)
 *     Given a zot hash, return all distinct hubs. 
 *     This function is used in building the zot discovery packet
 *     and therefore should only be used by channels which are defined
 *     on this hub
 * @param string $hash - xchan_hash
 * @retuns array of hubloc (hub location structures)
 *    hubloc_id          int
 *    hubloc_guid        char(255)
 *	  hubloc_guid_sig    text
 *    hubloc_hash        char(255)
 *    hubloc_addr        char(255)
 *    hubloc_flags       int
 *    hubloc_status      int
 *    hubloc_url         char(255)
 *    hubloc_url_sig     text
 *	  hubloc_host        char(255)
 *    hubloc_callback    char(255)
 *    hubloc_connect     char(255)
 *    hubloc_sitekey     text
 *    hubloc_updated     datetime
 *    hubloc_connected   datetime 	
 *
 */

function zot_get_hublocs($hash) {

	/** Only search for active hublocs - e.g. those that haven't been marked deleted */

	$ret = q("select * from hubloc where hubloc_hash = '%s' and not ( hubloc_flags & %d ) group by hubloc_url ",
		dbesc($hash),
		intval(HUBLOC_FLAGS_DELETED)
	);
	return $ret;
}
	 
/**
 *
 * @function zot_build_packet($channel,$type = 'notify',$recipients = null, $remote_key = null, $secret = null)
 *    builds a zot notification packet that you can either
 *    store in the queue with a message array or call zot_zot to immediately 
 *    zot it to the other side
 *
 * @param array $channel     => sender channel structure
 * @param string $type       => packet type: one of 'ping', 'pickup', 'purge', 'refresh', 'force_refresh', 'notify', 'auth_check'
 * @param array $recipients  => envelope information, array ( 'guid' => string, 'guid_sig' => string ); empty for public posts
 * @param string $remote_key => optional public site key of target hub used to encrypt entire packet
 *    NOTE: remote_key and encrypted packets are required for 'auth_check' packets, optional for all others 
 * @param string $secret     => random string, required for packets which require verification/callback
 *    e.g. 'pickup', 'purge', 'notify', 'auth_check'. Packet types 'ping', 'force_refresh', and 'refresh' do not require verification 
 *
 * @returns string json encoded zot packet
 */

function zot_build_packet($channel,$type = 'notify',$recipients = null, $remote_key = null, $secret = null) {

	$data = array(
		'type' => $type,
		'sender' => array(
			'guid' => $channel['channel_guid'],
			'guid_sig' => base64url_encode(rsa_sign($channel['channel_guid'],$channel['channel_prvkey'])),
			'url' => z_root(),
			'url_sig' => base64url_encode(rsa_sign(z_root(),$channel['channel_prvkey']))
		), 
		'callback' => '/post',
		'version' => ZOT_REVISION
	);

	if($recipients)
		$data['recipients'] = $recipients;

	if($secret) {
		$data['secret'] = $secret; 
		$data['secret_sig'] = base64url_encode(rsa_sign($secret,$channel['channel_prvkey']));
	}

	logger('zot_build_packet: ' . print_r($data,true), LOGGER_DATA);

	// Hush-hush ultra top-secret mode

	if($remote_key) {
		$data = crypto_encapsulate(json_encode($data),$remote_key);
	}

	return json_encode($data);
}


/**
 * @function: zot_zot
 * @param: string $url
 * @param: array $data
 *
 * @returns: array => see z_post_url for returned data format
 */
 


function zot_zot($url,$data) {
	return z_post_url($url,array('data' => $data));
}

/**
 * @function: zot_finger
 *
 * Look up information about channel
 * @param: string $webbie
 *   does not have to be host qualified e.g. 'foo' is treated as 'foo@thishub'
 * @param: array $channel
 *   (optional), if supplied permissions will be enumerated specifically for $channel
 * @param: boolean $autofallback
 *   fallback/failover to http if https connection cannot be established. Default is true.
 *
 * @returns: array => see z_post_url and mod/zfinger.php
 */


function zot_finger($webbie,$channel,$autofallback = true) {


	if(strpos($webbie,'@') === false) {
		$address = $webbie;
		$host = get_app()->get_hostname();
	}
	else {
		$address = substr($webbie,0,strpos($webbie,'@'));
		$host = substr($webbie,strpos($webbie,'@')+1);
	}

	$xchan_addr = $address . '@' . $host;

	if((! $address) || (! $xchan_addr)) {
		logger('zot_finger: no address :' . $webbie);
		return array('success' => false);
	}		

	$r = q("select xchan.*, hubloc.* from xchan 
			left join hubloc on xchan_hash = hubloc_hash
			where xchan_addr = '%s' and (hubloc_flags & %d) limit 1",
		dbesc($xchan_addr),
		intval(HUBLOC_FLAGS_PRIMARY)
	);

	if($r) {
		$url = $r[0]['hubloc_url'];
	}
	else {
		$url = 'https://' . $host;
	}
	
	$rhs = '/.well-known/zot-info';
	$https = ((strpos($url,'https://') === 0) ? true : false);

	logger('zot_finger: ' . $address . ' at ' . $url, LOGGER_DEBUG);

	if($channel) {
		$postvars = array(
			'address'    => $address,
			'target'     => $channel['channel_guid'],
			'target_sig' => $channel['channel_guid_sig'],
			'key'        => $channel['channel_pubkey']
		);

		$result = z_post_url($url . $rhs,$postvars);


		if((! $result['success']) && ($autofallback)) {
			if($https) {
				logger('zot_finger: https failed. falling back to http');
				$result = z_post_url('http://' . $host . $rhs,$postvars);
			}
		}
	}		
	else {
		$rhs .= '?f=&address=' . urlencode($address);

		$result =  z_fetch_url($url . $rhs);
		if((! $result['success']) && ($autofallback)) {
			if($https) {
				logger('zot_finger: https failed. falling back to http');
				$result = z_fetch_url('http://' . $host . $rhs);
			}
		}
	}
	
	if(! $result['success'])
		logger('zot_finger: no results');

	return $result;	 

}

/**
 * @function: zot_refresh($them, $channel = null, $force = false)
 *
 *   zot_refresh is typically invoked when somebody has changed permissions of a channel and they are notified
 *   to fetch new permissions via a finger/discovery operation. This may result in a new connection 
 *   (abook entry) being added to a local channel and it may result in auto-permissions being granted. 
 * 
 *   Friending in zot is accomplished by sending a refresh packet to a specific channel which indicates a
 *   permission change has been made by the sender which affects the target channel. The hub controlling
 *   the target channel does targetted discovery (a zot-finger request requesting permissions for the local
 *   channel). These are decoded here, and if necessary and abook structure (addressbook) is created to store
 *   the permissions assigned to this channel. 
 *   
 *   Initially these abook structures are created with a 'pending' flag, so that no reverse permissions are 
 *   implied until this is approved by the owner channel. A channel can also auto-populate permissions in 
 *   return and send back a refresh packet of its own. This is used by forum and group communication channels
 *   so that friending and membership in the channel's "club" is automatic. 
 * 
 * @param array $them => xchan structure of sender
 * @param array $channel => local channel structure of target recipient, required for "friending" operations
 *
 * @returns boolean true if successful, else false 
 */

function zot_refresh($them,$channel = null, $force = false) {

	logger('zot_refresh: them: ' . print_r($them,true), LOGGER_DATA);
	if($channel)
		logger('zot_refresh: channel: ' . print_r($channel,true), LOGGER_DATA);

	if($them['hubloc_url'])
		$url = $them['hubloc_url'];
	else {
		$r = q("select hubloc_url from hubloc where hubloc_hash = '%s' and ( hubloc_flags & %d ) limit 1",
			dbesc($them['xchan_hash']),
			intval(HUBLOC_FLAGS_PRIMARY)
		);
		if($r)
			$url = $r[0]['hubloc_url'];
	}
	if(! $url) {
		logger('zot_refresh: no url');
		return false;
	}

	$postvars = array();

	if($channel) {
		$postvars['target']     = $channel['channel_guid'];
		$postvars['target_sig'] = $channel['channel_guid_sig'];
		$postvars['key']        = $channel['channel_pubkey'];
	}

	if(array_key_exists('xchan_addr',$them) && $them['xchan_addr'])
		$postvars['address'] = $them['xchan_addr'];
	if(array_key_exists('xchan_hash',$them) && $them['xchan_hash'])
		$postvars['guid_hash'] = $them['xchan_hash'];
	if(array_key_exists('xchan_guid',$them) && $them['xchan_guid'] 
		&& array_key_exists('xchan_guid_sig',$them) && $them['xchan_guid_sig']) {
		$postvars['guid'] = $them['xchan_guid'];
		$postvars['guid_sig'] = $them['xchan_guid_sig'];
	}

	$rhs = '/.well-known/zot-info';

	$result = z_post_url($url . $rhs,$postvars);
	
	logger('zot_refresh: zot-info: ' . print_r($result,true), LOGGER_DATA);

	if($result['success']) {

		$j = json_decode($result['body'],true);

		if(! (($j) && ($j['success']))) {
			logger('zot_refresh: result not decodable');
			return false;
		}

		$x = import_xchan($j,(($force) ? UPDATE_FLAGS_FORCED : UPDATE_FLAGS_UPDATED));

		if(! $x['success'])
			return false;

		$xchan_hash = $x['hash'];

		$their_perms = 0;


		if($channel) {
			$global_perms = get_perms();
			if($j['permissions']['data']) {
				$permissions = crypto_unencapsulate(array(
					'data' => $j['permissions']['data'],
					'key'  => $j['permissions']['key'],
					'iv'   => $j['permissions']['iv']),
					$channel['channel_prvkey']);
				if($permissions)
					$permissions = json_decode($permissions,true);
				logger('decrypted permissions: ' . print_r($permissions,true), LOGGER_DATA);
			}
			else
				$permissions = $j['permissions'];

			$connected_set = false;

			if($permissions && is_array($permissions)) {
				foreach($permissions as $k => $v) {
					// The connected permission means you are in their address book
					if($k === 'connected') {
						$connected_set = intval($v);
						continue;
					}	
					if($v) {
						$their_perms = $their_perms | intval($global_perms[$k][1]);
					}
				}
			}

			$r = q("select * from abook where abook_xchan = '%s' and abook_channel = %d and not (abook_flags & %d) limit 1",
				dbesc($x['hash']),
				intval($channel['channel_id']),
				intval(ABOOK_FLAG_SELF)
			);

			if($r) {

				$current_abook_connected = (($r[0]['abook_flags'] & ABOOK_FLAG_UNCONNECTED) ? 0 : 1);
		
				$y = q("update abook set abook_their_perms = %d
					where abook_xchan = '%s' and abook_channel = %d 
					and not (abook_flags & %d) limit 1",
					intval($their_perms),
					dbesc($x['hash']),
					intval($channel['channel_id']),
					intval(ABOOK_FLAG_SELF)
				);

				if(($connected_set === 0 || $connected_set === 1) && ($connected_set !== $current_abook_unconnected)) {

					// if they are in your address book but you aren't in theirs, and/or this does not
					// match your current connected state setting, toggle it. 

					$y1 = q("update abook set abook_flags = (abook_flags ^ %d)
						where abook_xchan = '%s' and abook_channel = %d 
						and not (abook_flags & %d) limit 1",
						intval(ABOOK_FLAG_UNCONNECTED),
						dbesc($x['hash']),
						intval($channel['channel_id']),
						intval(ABOOK_FLAG_SELF)
					);
				}

				if(! $y)
					logger('abook update failed');
				else {
					// if we were just granted read stream permission and didn't have it before, try to pull in some posts
					if((! ($r[0]['abook_their_perms'] & PERMS_R_STREAM)) && ($their_perms & PERMS_R_STREAM))
						proc_run('php','include/onepoll.php',$r[0]['abook_id']); 
				}
			}
			else {
				$default_perms = 0;
				// look for default permissions to apply in return - e.g. auto-friend
				$z = q("select * from abook where abook_channel = %d and (abook_flags & %d) limit 1",
					intval($channel['channel_id']),
					intval(ABOOK_FLAG_SELF)
				);

				if($z)
					$default_perms = intval($z[0]['abook_my_perms']);		

				$y = q("insert into abook ( abook_account, abook_channel, abook_xchan, abook_their_perms, abook_my_perms, abook_created, abook_updated, abook_flags ) values ( %d, %d, '%s', %d, %d, '%s', '%s', %d )",
					intval($channel['channel_account_id']),
					intval($channel['channel_id']),
					dbesc($x['hash']),
					intval($their_perms),
					intval($default_perms),
					dbesc(datetime_convert()),
					dbesc(datetime_convert()),
					intval(($default_perms) ? 0 : ABOOK_FLAG_PENDING)
				);

				if($y) {
					logger("New introduction received for {$channel['channel_name']}");
					if($default_perms) {
						// send back a permissions update for auto-friend/auto-permissions
						$z = q("select * from abook where abook_xchan = '%s' and abook_channel = %d and not (abook_flags & %d) limit 1",
							dbesc($x['hash']),
							intval($channel['channel_id']),
							intval(ABOOK_FLAG_SELF)
						);
						if($z)
							proc_run('php','include/notifier.php','permission_update',$z[0]['abook_id']);
					}
					$new_connection = q("select abook_id, abook_flags from abook where abook_channel = %d and abook_xchan = '%s' order by abook_created desc limit 1",
						intval($channel['channel_id']),
						dbesc($x['hash'])
					);

					if($new_connection && (! ($new_connection[0]['abook_flags'] & ABOOK_FLAG_PENDING)) && ($their_perms & PERMS_R_STREAM))
							proc_run('php','include/onepoll.php',$new_connection[0]['abook_id']); 

				}
			}
		}
		return true;
	}
	return false;
}

/**
 * @function: zot_gethub
 *
 * A guid and a url, both signed by the sender, distinguish a known sender at a known location
 * This function looks these up to see if the channel is known and therefore previously verified. 
 * If not, we will need to verify it.
 *
 * @param array $arr
 *    $arr must contain: 
 *       string $arr['guid'] => guid of conversant
 *       string $arr['guid_sig'] => guid signed with conversant's private key
 *       string $arr['url'] => URL of the origination hub of this communication
 *       string $arr['url_sig'] => URL signed with conversant's private key
 *  
 *
 * @returns: array => hubloc record
 */


		
function zot_gethub($arr) {

	if($arr['guid'] && $arr['guid_sig'] && $arr['url'] && $arr['url_sig']) {
		$r = q("select * from hubloc 
				where hubloc_guid = '%s' and hubloc_guid_sig = '%s' 
				and hubloc_url = '%s' and hubloc_url_sig = '%s'
				limit 1",
			dbesc($arr['guid']),
			dbesc($arr['guid_sig']),
			dbesc($arr['url']),
			dbesc($arr['url_sig'])
		);
		if($r && count($r)) {
			logger('zot_gethub: found', LOGGER_DEBUG);
			return $r[0];
		}
	}
	logger('zot_gethub: not found: ' . print_r($arr,true), LOGGER_DEBUG);
	return null;
}

/**
 * @function zot_register_hub($arr)
 *
 *   A communication has been received which has an unknown (to us) sender. 
 *   Perform discovery based on our calculated hash of the sender at the origination address.
 *   This will fetch the discovery packet of the sender, which contains the public key we 
 *   need to verify our guid and url signatures.
 *
 * @param array $arr
 *    $arr must contain: 
 *       string $arr['guid'] => guid of conversant
 *       string $arr['guid_sig'] => guid signed with conversant's private key
 *       string $arr['url'] => URL of the origination hub of this communication
 *       string $arr['url_sig'] => URL signed with conversant's private key
 *  
 *
 * @returns array => 'success' (boolean true or false)
 *                   'message' (optional error string only if success is false)
 */


function zot_register_hub($arr) {

	$result = array('success' => false);

	if($arr['url'] && $arr['url_sig'] && $arr['guid'] && $arr['guid_sig']) {

		$guid_hash = base64url_encode(hash('whirlpool',$arr['guid'] . $arr['guid_sig'], true));

		$url = $arr['url'] . '/.well-known/zot-info/?f=&guid_hash=' . $guid_hash;

		logger('zot_register_hub: ' . $url, LOGGER_DEBUG);

		$x = z_fetch_url($url);

		logger('zot_register_hub: ' . print_r($x,true), LOGGER_DATA);

		if($x['success']) {
			$record = json_decode($x['body'],true);

			/* 
			 * We now have a key - only continue registration if our signatures are valid 
			 * AND the guid and guid sig in the returned packet match those provided in
			 * our current communication.
			 */

			if((rsa_verify($arr['guid'],base64url_decode($arr['guid_sig']),$record['key']))
				&& (rsa_verify($arr['url'],base64url_decode($arr['url_sig']),$record['key']))
				&& ($arr['guid'] === $record['guid'])
				&& ($arr['guid_sig'] === $record['guid_sig'])) {

				$c = import_xchan($record);
				if($c['success'])
					$result['success'] = true;
			}
			else {
				logger('zot_register_hub: failure to verify returned packet.');
			}			
		}
	}
	return $result;
}


/**
 * @function import_xchan($arr,$ud_flags = UPDATE_FLAGS_UPDATED)
 *   Takes an associative array of a fetched discovery packet and updates
 *   all internal data structures which need to be updated as a result.
 * 
 * @param array $arr => json_decoded discovery packet
 * @param int $ud_flags
 *    Determines whether to create a directory update record if any changes occur, default is UPDATE_FLAGS_UPDATED (true)
 *    $ud_flags = UPDATE_FLAGS_FORCED indicates a forced refresh where we unconditionally create a directory update record
 *      this typically occurs once a month for each channel as part of a scheduled ping to notify the directory
 *      that the channel still exists
 *
 * @returns array =>  'success' (boolean true or false)
 *                    'message' (optional error string only if success is false)
 */

function import_xchan($arr,$ud_flags = UPDATE_FLAGS_UPDATED) {


	call_hooks('import_xchan', $arr);

	$ret = array('success' => false);
	$dirmode = intval(get_config('system','directory_mode')); 

	$changed = false;
	$what = '';

	if(! (is_array($arr) && array_key_exists('success',$arr) && $arr['success'])) {
		logger('import_xchan: invalid data packet: ' . print_r($arr,true));
		$ret['message'] = t('Invalid data packet');
		return $ret;
	}


	$xchan_hash = base64url_encode(hash('whirlpool',$arr['guid'] . $arr['guid_sig'], true));
	$import_photos = false;

	if(! rsa_verify($arr['guid'],base64url_decode($arr['guid_sig']),$arr['key'])) {
		logger('import_xchan: Unable to verify channel signature for ' . $arr['address']);
		$ret['message'] = t('Unable to verify channel signature');
		return $ret;
	}

	logger('import_xchan: ' . $xchan_hash, LOGGER_DEBUG);

	$r = q("select * from xchan where xchan_hash = '%s' limit 1",
		dbesc($xchan_hash)
	);	

	if(! array_key_exists('connect_url', $arr))
		$arr['connect_url'] = '';		
			
	if(strpos($arr['address'],'/') !== false)
		$arr['address'] = substr($arr['address'],0,strpos($arr['address'],'/'));

	if($r) {
		if($r[0]['xchan_photo_date'] != $arr['photo_updated'])
			$import_photos = true;

		// if we import an entry from a site that's not ours and either or both of us is off the grid - hide the entry.
		// TODO: check if we're the same directory realm, which would mean we are allowed to see it

		$dirmode = get_config('system','directory_mode'); 

		if((($arr['site']['directory_mode'] === 'standalone') || ($dirmode & DIRECTORY_MODE_STANDALONE))
&& ($arr['site']['url'] != z_root()))
			$arr['searchable'] = false;

		$hidden = (1 - intval($arr['searchable']));

		// Be careful - XCHAN_FLAGS_HIDDEN should evaluate to 1
		if(($r[0]['xchan_flags'] & XCHAN_FLAGS_HIDDEN) != $hidden)
			$new_flags = $r[0]['xchan_flags'] ^ XCHAN_FLAGS_HIDDEN;
		else
			$new_flags = $r[0]['xchan_flags'];

		$adult = (($r[0]['xchan_flags'] & XCHAN_FLAGS_SELFCENSORED) ? true : false);
		$adult_changed =  ((intval($adult) != intval($arr['adult_content'])) ? true : false);
		if($adult_changed)
			$new_flags = $new_flags ^ XCHAN_FLAGS_SELFCENSORED;


		if(($r[0]['xchan_name_date'] != $arr['name_updated']) 
			|| ($r[0]['xchan_connurl'] != $arr['connections_url']) 
			|| ($r[0]['xchan_flags'] != $new_flags)
			|| ($r[0]['xchan_addr'] != $arr['address'])
			|| ($r[0]['xchan_follow'] != $arr['follow_url'])
			|| ($r[0]['xchan_connpage'] != $arr['connect_url']) 
			|| ($r[0]['xchan_url'] != $arr['url'])) {
			$r = q("update xchan set xchan_name = '%s', xchan_name_date = '%s', xchan_connurl = '%s', xchan_follow = '%s', 
				xchan_connpage = '%s', xchan_flags = %d,
				xchan_addr = '%s', xchan_url = '%s' where xchan_hash = '%s' limit 1",
				dbesc($arr['name']),
				dbesc($arr['name_updated']),
				dbesc($arr['connections_url']),
				dbesc($arr['follow_url']),
				dbesc($arr['connect_url']),
				intval($new_flags),
				dbesc($arr['address']),
				dbesc($arr['url']),
				dbesc($xchan_hash)
			);

			logger('import_xchan: existing: ' . print_r($r[0],true), LOGGER_DATA);
			logger('import_xchan: new: ' . print_r($arr,true), LOGGER_DATA);
			$what .= 'xchan ';
			$changed = true;
		}
	}
	else {
		$import_photos = true;


		if((($arr['site']['directory_mode'] === 'standalone') || ($dirmode & DIRECTORY_MODE_STANDALONE))
&& ($arr['site']['url'] != z_root()))
			$arr['searchable'] = false;

		$hidden = (1 - intval($arr['searchable']));

		if($hidden)
			$new_flags = XCHAN_FLAGS_HIDDEN;
		else
			$new_flags = 0;
		if($arr['adult_content'])
			$new_flags |= XCHAN_FLAGS_SELFCENSORED;
		
		$x = q("insert into xchan ( xchan_hash, xchan_guid, xchan_guid_sig, xchan_pubkey, xchan_photo_mimetype,
				xchan_photo_l, xchan_addr, xchan_url, xchan_connurl, xchan_follow, xchan_connpage, xchan_name, xchan_network, xchan_photo_date, xchan_name_date, xchan_flags)
				values ( '%s', '%s', '%s', '%s' , '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d) ",
			dbesc($xchan_hash),
			dbesc($arr['guid']),
			dbesc($arr['guid_sig']),
			dbesc($arr['key']),
			dbesc($arr['photo_mimetype']),
			dbesc($arr['photo']),
			dbesc($arr['address']),
			dbesc($arr['url']),
			dbesc($arr['connections_url']),
			dbesc($arr['follow_url']),
			dbesc($arr['connect_url']),
			dbesc($arr['name']),
			dbesc('zot'),
			dbesc($arr['photo_updated']),
			dbesc($arr['name_updated']),
			intval($new_flags)
		);

		$what .= 'new_xchan';
		$changed = true;

	}				


	if($import_photos) {

		require_once('include/photo/photo_driver.php');

		// see if this is a channel clone that's hosted locally - which we treat different from other xchans/connections

		$local = q("select channel_account_id, channel_id from channel where channel_hash = '%s' limit 1",
			dbesc($xchan_hash)
		);
		if($local) {
			$ph = z_fetch_url($arr['photo'],true);
			if($ph['success']) {
				import_channel_photo($ph['body'], $arr['photo_mimetype'], $local[0]['channel_account_id'],$local[0]['channel_id']);
				// reset the names in case they got messed up when we had a bug in this function
				$photos = array(
					z_root() . '/photo/profile/l/' . $local[0]['channel_id'],
					z_root() . '/photo/profile/m/' . $local[0]['channel_id'],
					z_root() . '/photo/profile/s/' . $local[0]['channel_id'],
					$arr['photo_mimetype']
				);
			}
		}
		else {
			$photos = import_profile_photo($arr['photo'],$xchan_hash);
		}
		if($photos) {
			$r = q("update xchan set xchan_photo_date = '%s', xchan_photo_l = '%s', xchan_photo_m = '%s', xchan_photo_s = '%s', xchan_photo_mimetype = '%s'
				where xchan_hash = '%s' limit 1",
				dbesc(datetime_convert('UTC','UTC',$arr['photo_updated'])),
				dbesc($photos[0]),
				dbesc($photos[1]),
				dbesc($photos[2]),
				dbesc($photos[3]),
				dbesc($xchan_hash)
			);

			$what .= 'photo ';
			$changed = true;
		}
	}

	// what we are missing for true hub independence is for any changes in the primary hub to 
	// get reflected not only in the hublocs, but also to update the URLs and addr in the appropriate xchan

	if($arr['locations']) {

		$xisting = q("select hubloc_id, hubloc_url, hubloc_sitekey from hubloc where hubloc_hash = '%s'",
			dbesc($xchan_hash)
		);

		// See if a primary is specified

		$has_primary = false;
		foreach($arr['locations'] as $location) {
			if($location['primary']) {
				$has_primary = true;
				break;
			}
		}

		foreach($arr['locations'] as $location) {
			if(! rsa_verify($location['url'],base64url_decode($location['url_sig']),$arr['key'])) {
				logger('import_xchan: Unable to verify site signature for ' . $location['url']);
				$ret['message'] .= sprintf( t('Unable to verify site signature for %s'), $location['url']) . EOL;
				continue;
			}

			// Ensure that they have one primary hub

			if(! $has_primary)
				$location['primary'] = true;


			for($x = 0; $x < count($xisting); $x ++) {
				if(($xisting[$x]['hubloc_url'] === $location['url']) && ($xisting[$x]['hubloc_sitekey'] === $location['sitekey'])) {
					$xisting[$x]['updated'] = true;
				}
			}

			if(! $location['sitekey']) {
				logger('import_xchan: empty hubloc sitekey. ' . print_r($location,true));
				continue;
			}

			// Catch some malformed entries from the past which still exist

			if(strpos($location['address'],'/') !== false)
				$location['address'] = substr($location['address'],0,strpos($location['address'],'/'));

			// match as many fields as possible in case anything at all changed. 

			$r = q("select * from hubloc where hubloc_hash = '%s' and hubloc_guid = '%s' and hubloc_guid_sig = '%s' and hubloc_url = '%s' and hubloc_url_sig = '%s' and hubloc_host = '%s' and hubloc_addr = '%s' and hubloc_callback = '%s' and hubloc_sitekey = '%s' ",
				dbesc($xchan_hash),
				dbesc($arr['guid']),
				dbesc($arr['guid_sig']),
				dbesc($location['url']),
				dbesc($location['url_sig']),
				dbesc($location['host']),
				dbesc($location['address']),
				dbesc($location['callback']),
				dbesc($location['sitekey'])
			);
			if($r) {
				logger('import_xchan: hub exists: ' . $location['url']);
				// update connection timestamp if this is the site we're talking to
				if($location['url'] == $arr['site']['url']) {
					q("update hubloc set hubloc_connected = '%s', hubloc_updated = '%s' where hubloc_id = %d limit 1",
						dbesc(datetime_convert()),
						dbesc(datetime_convert()),
						intval($r[0]['hubloc_id'])
					);
				}

				// Remove pure duplicates
				if(count($r) > 1) {
					for($h = 1; $h < count($r); $h ++) {
						q("delete from hubloc where hubloc_id = %d limit 1",
							intval($r[$h]['hubloc_id'])
						);
					}
				}

				if((($r[0]['hubloc_flags'] & HUBLOC_FLAGS_PRIMARY) && (! $location['primary']))
					|| ((! ($r[0]['hubloc_flags'] & HUBLOC_FLAGS_PRIMARY)) && ($location['primary']))) {
					$r = q("update hubloc set hubloc_flags = (hubloc_flags ^ %d), hubloc_updated = '%s' where hubloc_id = %d limit 1",
						intval(HUBLOC_FLAGS_PRIMARY),
						dbesc(datetime_convert()),
						intval($r[0]['hubloc_id'])
					);
					$what = 'primary_hub ';
					$changed = true;
				}
				if((($r[0]['hubloc_flags'] & HUBLOC_FLAGS_DELETED) && (! $location['deleted']))
					|| ((! ($r[0]['hubloc_flags'] & HUBLOC_FLAGS_DELETED)) && ($location['deleted']))) {
					$r = q("update hubloc set hubloc_flags = (hubloc_flags ^ %d), hubloc_updated = '%s' where hubloc_id = %d limit 1",
						intval(HUBLOC_FLAGS_DELETED),
						dbesc(datetime_convert()),
						intval($r[0]['hubloc_id'])
					);
					$what = 'delete_hub ';
					$changed = true;
				}
				continue;
			}

			// new hub claiming to be primary. Make it so.

			if(intval($location['primary'])) {
				$r = q("update hubloc set hubloc_flags = (hubloc_flags ^ %d), hubloc_updated = '%s' where hubloc_hash = '%s' and (hubloc_flags & %d )",
					intval(HUBLOC_FLAGS_PRIMARY),
					dbesc(datetime_convert()),
					dbesc($xchan_hash),
					intval(HUBLOC_FLAGS_PRIMARY)
				);
			}
			logger('import_xchan: new hub: ' . $location['url']);
			$r = q("insert into hubloc ( hubloc_guid, hubloc_guid_sig, hubloc_hash, hubloc_addr, hubloc_flags, hubloc_url, hubloc_url_sig, hubloc_host, hubloc_callback, hubloc_sitekey, hubloc_updated, hubloc_connected)
					values ( '%s','%s','%s','%s', %d ,'%s','%s','%s','%s','%s','%s','%s')",
				dbesc($arr['guid']),
				dbesc($arr['guid_sig']),
				dbesc($xchan_hash),
				dbesc($location['address']),
				intval((intval($location['primary'])) ? HUBLOC_FLAGS_PRIMARY : 0),
				dbesc($location['url']),
				dbesc($location['url_sig']),
				dbesc($location['host']),
				dbesc($location['callback']),
				dbesc($location['sitekey']),
				dbesc(datetime_convert()),
				dbesc(datetime_convert())
			);
			$what .= 'newhub ';
			$changed = true;

		}

		// get rid of any hubs we have for this channel which weren't reported.

		if($xisting) {
			foreach($xisting as $x) {
				if(! array_key_exists('updated',$x)) {
					logger('import_xchan: removing unreferenced hub location ' . $x['hubloc_url']);
					$r = q("delete from hubloc where hubloc_id = %d limit 1",
						intval($x['hubloc_id'])
					);
					$what .= 'removed_hub';
					$changed = true;
				}
			}
		}
	}

	// Are we a directory server of some kind?

	if($dirmode != DIRECTORY_MODE_NORMAL) {
		if(array_key_exists('profile',$arr) && is_array($arr['profile'])) {
			$profile_changed = import_directory_profile($xchan_hash,$arr['profile'],$arr['address'],$ud_flags, 1);
			if($profile_changed) {
				$what .= 'profile ';
				$changed = true;
			}
		}
		else {
			logger('import_xchan: profile not available - hiding');
			// they may have made it private
			$r = q("delete from xprof where xprof_hash = '%s' limit 1",
				dbesc($xchan_hash)
			);
			$r = q("delete from xtag where xtag_hash = '%s' limit 1",
				dbesc($xchan_hash)
			);
		}
	}

	if(array_key_exists('site',$arr) && is_array($arr['site'])) {
		$profile_changed = import_site($arr['site'],$arr['key']);
		if($profile_changed) {
			$what .= 'site ';
			$changed = true;
		}
	}
	
	if(($changed) || ($ud_flags == UPDATE_FLAGS_FORCED)) {
		$guid = random_string() . '@' . get_app()->get_hostname();		
		update_modtime($xchan_hash,$guid,$arr['address'],$ud_flags);
		logger('import_xchan: changed: ' . $what,LOGGER_DEBUG);
	}
	elseif(! $ud_flags) {
		// nothing changed but we still need to update the updates record
		q("update updates set ud_flags = ( ud_flags | %d ) where ud_addr = '%s' and not (ud_flags & %d) ",
			intval(UPDATE_FLAGS_UPDATED),
			dbesc($arr['address']),
			intval(UPDATE_FLAGS_UPDATED)
		);
	}

	if(! x($ret,'message')) {
		$ret['success'] = true;
		$ret['hash'] = $xchan_hash;
	}

	logger('import_xchan: result: ' . print_r($ret,true), LOGGER_DATA);
	return $ret;
}

/**
 * @function zot_process_response($hub,$arr,$outq) {
 *    Called immediately after sending a zot message which is using queue processing
 *    Updates the queue item according to the response result and logs any information
 *    returned to aid communications troubleshooting.
 *
 * @param string $hub - url of site we just contacted
 * @param array $arr - output of z_post_url()
 * @param array $outq - The queue structure attached to this request
 *
 * @returns nothing
 */


function zot_process_response($hub,$arr,$outq) {

	if(! $arr['success']) {
		logger('zot_process_response: failed: ' . $hub);
		return;
	}

	$x = json_decode($arr['body'],true);

	if(! $x) {
		logger('zot_process_response: No json from ' . $hub);
		logger('zot_process_response: headers: ' . print_r($arr['header'],true), LOGGER_DATA);
	}

	// synchronous message types are handled immediately
	// async messages remain in the queue until processed.

	if(intval($outq['outq_async'])) {
		$r = q("update outq set outq_delivered = 1, outq_updated = '%s' where outq_hash = '%s' and outq_channel = %d limit 1",
			dbesc(datetime_convert()),
			dbesc($outq['outq_hash']),
			intval($outq['outq_channel'])
		);
	}
	else {
		$r = q("delete from outq where outq_hash = '%s' and outq_channel = %d limit 1",
			dbesc($outq['outq_hash']),
			intval($outq['outq_channel'])
		);
	}

	logger('zot_process_response: ' . print_r($x,true), LOGGER_DATA);
}

/**
 * @function zot_fetch($arr)
 *
 *     We received a notification packet (in mod/post.php) that a message is waiting for us, and we've verified the sender.
 *     Now send back a pickup message, using our message tracking ID ($arr['secret']), which we will sign with our site private key.
 *     The entire pickup message is encrypted with the remote site's public key. 
 *     If everything checks out on the remote end, we will receive back a packet containing one or more messages,
 *     which will be processed and delivered before this function ultimately returns.
 *   
 * @param array $arr
 *     decrypted and json decoded notify packet from remote site
 */
 

function zot_fetch($arr) {

	logger('zot_fetch: ' . print_r($arr,true), LOGGER_DATA);

	$url = $arr['sender']['url'] . $arr['callback'];

	$ret_hub = zot_gethub($arr['sender']);
	if(! $ret_hub) {
		logger('zot_fetch: no hub: ' . print_r($arr['sender'],true));
		return;
	}

	$data = array(
		'type'    => 'pickup',
		'url'     => z_root(),
		'callback_sig' => base64url_encode(rsa_sign(z_root() . '/post',get_config('system','prvkey'))),
		'callback' => z_root() . '/post',
		'secret' => $arr['secret'],
		'secret_sig' => base64url_encode(rsa_sign($arr['secret'],get_config('system','prvkey')))
	);

	$datatosend = json_encode(crypto_encapsulate(json_encode($data),$ret_hub['hubloc_sitekey']));
	
	$fetch = zot_zot($url,$datatosend);
	$result = zot_import($fetch, $arr['sender']['url']);
	return $result;
}

/**
 * @function zot_import
 * 
 * Process an incoming array of messages which were obtained via pickup, and 
 * import, update, delete as directed.
 * 
 * @param array $arr => 'pickup' structure returned from remote site
 * @param string $sender_url => the url specified by the sender in the initial communication
 *       we will verify the sender and url in each returned message structure and also verify
 *       that all the messages returned match the site url that we are currently processing.
 * 
 * The message types handled here are 'activity' (e.g. posts), 'mail' , 'profile', and 'channel_sync'
 * 
 * @returns array => array ( [0] => string $channel_hash, [1] => string $delivery_status, [2] => string $address )
 *    suitable for logging remotely, enumerating the processing results of each message/recipient combination.
 * 
 */

function zot_import($arr, $sender_url) {

	$data = json_decode($arr['body'],true);

	if(! $data) {
		logger('zot_import: empty body');
		return array();
	}

	if(array_key_exists('iv',$data)) {
		$data = json_decode(crypto_unencapsulate($data,get_config('system','prvkey')),true);
    }

	$incoming = $data['pickup'];

	$return = array();

	if(is_array($incoming)) {
		foreach($incoming as $i) {
			$result = null;

			if(array_key_exists('iv',$i['notify'])) {
				$i['notify'] = json_decode(crypto_unencapsulate($i['notify'],get_config('system','prvkey')),true);
    		}

			logger('zot_import: notify: ' . print_r($i['notify'],true), LOGGER_DATA);

			$hub = zot_gethub($i['notify']['sender']);			
			if((! $hub) || ($hub['hubloc_url'] != $sender_url)) {
				logger('zot_import: potential forgery: wrong site for sender: ' . $sender_url . ' != ' . print_r($i['notify'],true));
				continue;
			}


			$i['notify']['sender']['hash'] = base64url_encode(hash('whirlpool',$i['notify']['sender']['guid'] . $i['notify']['sender']['guid_sig'], true));
			$deliveries = null;

			if(array_key_exists('recipients',$i['notify']) && count($i['notify']['recipients'])) {
				logger('specific recipients');
				$recip_arr = array();
				foreach($i['notify']['recipients'] as $recip) {
					$recip_arr[] =  base64url_encode(hash('whirlpool',$recip['guid'] . $recip['guid_sig'], true));
				}
				stringify_array_elms($recip_arr);
				$recips = implode(',',$recip_arr);
				$r = q("select channel_hash as hash from channel where channel_hash in ( " . $recips . " ) and not ( channel_pageflags & %d ) ",
					intval(PAGE_REMOVED)
				);
				if(! $r) {
					logger('recips: no recipients on this site');
					continue;
				}

				$deliveries = $r;

				// We found somebody on this site that's in the recipient list. 

			}
			else {
				if(($i['message']) && (array_key_exists('flags',$i['message'])) && (in_array('private',$i['message']['flags']))) {
					// This should not happen but until we can stop it...
					logger('private message was delivered with no recipients.');
					continue;
				}

				logger('public post');

				// Public post. look for any site members who are or may be accepting posts from this sender
				// and who are allowed to see them based on the sender's permissions

				$deliveries = allowed_public_recips($i);

			}

			// Go through the hash array and remove duplicates. array_unique() won't do this because the array is more than one level.

			$no_dups = array();
			if($deliveries) {
				foreach($deliveries as $d) {
					if(! in_array($d['hash'],$no_dups))
						$no_dups[] = $d['hash'];
				}

				if($no_dups) {
					$deliveries = array();
					foreach($no_dups as $n) {
						$deliveries[] = array('hash' => $n);
					}
				}
			}

			if(! $deliveries) {
				logger('zot_import: no deliveries on this site');
				continue;
			}
							
			if($i['message']) { 
				if($i['message']['type'] === 'activity') {
					$arr = get_item_elements($i['message']);

					if(! array_key_exists('created',$arr)) {
						logger('Activity rejected: probable failure to lookup author/owner. ' . print_r($i['message'],true));
						continue;
					}

					logger('Activity received: ' . print_r($arr,true), LOGGER_DATA);
					logger('Activity recipients: ' . print_r($deliveries,true), LOGGER_DATA);

					$relay = ((array_key_exists('flags',$i['message']) && in_array('relay',$i['message']['flags'])) ? true : false);
					$result = process_delivery($i['notify']['sender'],$arr,$deliveries,$relay);

				}
				elseif($i['message']['type'] === 'mail') {
					$arr = get_mail_elements($i['message']);

					logger('Mail received: ' . print_r($arr,true), LOGGER_DATA);
					logger('Mail recipients: ' . print_r($deliveries,true), LOGGER_DATA);


					$result = process_mail_delivery($i['notify']['sender'],$arr,$deliveries);

				}
				elseif($i['message']['type'] === 'profile') {
					$arr = get_profile_elements($i['message']);

					logger('Profile received: ' . print_r($arr,true), LOGGER_DATA);
					logger('Profile recipients: ' . print_r($deliveries,true), LOGGER_DATA);

					$result = process_profile_delivery($i['notify']['sender'],$arr,$deliveries);

				}
				elseif($i['message']['type'] === 'channel_sync') {
//					$arr = get_channelsync_elements($i['message']);

					$arr = $i['message'];

					logger('Channel sync received: ' . print_r($arr,true), LOGGER_DATA);
					logger('Channel sync recipients: ' . print_r($deliveries,true), LOGGER_DATA);
					
					$result = process_channel_sync_delivery($i['notify']['sender'],$arr,$deliveries);
				}
			}
			if($result){
				$return = array_merge($return,$result);
			}
		}
	}

	return $return;

}


// A public message with no listed recipients can be delivered to anybody who
// has PERMS_NETWORK for that type of post, or PERMS_SITE and is one the same
// site, or PERMS_SPECIFIC and the sender is a contact who is granted 
// permissions via their connection permissions in the address book.
// Here we take a given message and construct a list of hashes of everybody
// on the site that we should deliver to.  


function public_recips($msg) {

	$check_mentions = false;
	if($msg['message']['type'] === 'activity') {
		$col = 'channel_w_stream';
		$field = PERMS_W_STREAM;
		if(array_key_exists('flags',$msg['message']) && in_array('thread_parent', $msg['message']['flags'])) {
			// check mention recipient permissions on top level posts only
			$check_mentions = true;
		}
		else {
			// if this is a comment and it wasn't sent by the post owner, check to see who is allowing them to comment.
			// We should have one specific recipient and this step shouldn't be needed unless somebody stuffed up their software.
			// We may need this step to protect us from bad guys intentionally stuffing up their software.  
			// If it is sent by the post owner, we don't need to do this. We only need to see who is receiving the 
			// owner's stream (which was already set above) - as they control the comment permissions
			if($msg['notify']['sender']['guid_sig'] != $msg['message']['owner']['guid_sig']) {
				$col = 'channel_w_comment';
				$field = PERMS_W_COMMENT;
			}
		}
	}
	elseif($msg['message']['type'] === 'mail') {
		$col = 'channel_w_mail';
		$field = PERMS_W_MAIL;
	}

	if(! $col)
		return NULL;

	
	if($msg['notify']['sender']['url'] === z_root())
		$sql = " where (( " . $col . " & " . PERMS_NETWORK . " )  or ( " . $col . " & " . PERMS_SITE . " ) or ( " . $col . " & " . PERMS_PUBLIC . ")) ";				
	else
		$sql = " where (( " . $col . " & " . PERMS_NETWORK . " )  or ( "  . $col . " & " . PERMS_PUBLIC . ")) ";


	$r = q("select channel_hash as hash from channel $sql or channel_hash = '%s' ",
		dbesc($msg['notify']['sender']['hash'])
	);

	if(! $r)
		$r = array();

	$x = q("select channel_hash as hash from channel left join abook on abook_channel = channel_id where abook_xchan = '%s' and not ( channel_pageflags & " . PAGE_REMOVED . " ) and (( " . $col . " & " . PERMS_SPECIFIC . " )  and ( abook_my_perms & " . $field . " )) OR ( " . $col . " & " . PERMS_CONTACTS . " ) ",
		dbesc($msg['notify']['sender']['hash'])
	); 

	if(! $x)
		$x = array();

	$r = array_merge($r,$x);

	// look for any public mentions on this site
	// They will get filtered by tgroup_check() so we don't need to check permissions now

	if($check_mentions && $msg['message']['tags']) {
		if(is_array($msg['message']['tags']) && $msg['message']['tags']) {
			foreach($msg['message']['tags'] as $tag) {
				if(($tag['type'] === 'mention') && (strpos($tag['url'],z_root()) !== false)) {
					$address = basename($tag['url']);
					if($address) {
						$z = q("select channel_hash as hash from channel where channel_address = '%s' limit 1",
							dbesc($address)
						);
						if($z)
							$r = array_merge($r,$z);
					}
				}
			}
		}
	}

	logger('public_recips: ' . print_r($r,true), LOGGER_DATA);
	return $r;
}

// This is the second part of the above function. We'll find all the channels willing to accept public posts from us,
// then match them against the sender privacy scope and see who in that list that the sender is allowing.

function allowed_public_recips($msg) {


	logger('allowed_public_recips: ' . print_r($msg,true));

	$recips = public_recips($msg);

	if(! $recips)
		return $recips;

	if($msg['message']['type'] === 'mail')
		return $recips;

	if(array_key_exists('public_scope',$msg['message']))
		$scope = $msg['message']['public_scope'];

	$hash = base64url_encode(hash('whirlpool',$msg['notify']['sender']['guid'] . $msg['notify']['sender']['guid_sig'], true));

	if($scope === 'public' || $scope === 'network: red')
		return $recips;

	if(strpos($scope,'site:') === 0) {
		if(($scope === 'site: ' . get_app()->get_hostname()) && ($msg['notify']['sender']['url'] === z_root()))
			return $recips;
		else
			return array();
	}

	if($scope === 'self') {
		foreach($recips as $r)
			if($r['hash'] === $hash)
				return array('hash' => $hash);
	}

	if($scope === 'contacts') {
		$condensed_recips = array();
		foreach($recips as $rr)
			$condensed_recips[] = $rr['hash'];

		$results = array();
		$r = q("select channel_hash as hash from channel left join abook on abook_channel = channel_id where abook_xchan = '%s' and not ( channel_pageflags & %d ) ",
			dbesc($hash),
			intval(PAGE_REMOVED)
		);
		if($r) {
			foreach($r as $rr)
				if(in_array($rr['hash'],$condensed_recips))
					$results[] = array('hash' => $rr['hash']);
		}
		return $results;
	}

	return array();
}


function process_delivery($sender,$arr,$deliveries,$relay) {

	$result = array();


	// We've validated the sender. Now make sure that the sender is the owner or author

	if($sender['hash'] != $arr['owner_xchan'] && $sender['hash'] != $arr['author_xchan']) {
		logger('process_delivery: sender is not owner or author');
		return;
	}
	
	foreach($deliveries as $d) {
		$r = q("select * from channel where channel_hash = '%s' limit 1",
			dbesc($d['hash'])
		);

		if(! $r) {
			$result[] = array($d['hash'],'recipients not found');
			continue;
		}

		$channel = $r[0];

		$tag_delivery = tgroup_check($channel['channel_id'],$arr);

		$perm = (($arr['mid'] == $arr['parent_mid']) ? 'send_stream' : 'post_comments');

		// This is our own post, possibly coming from a channel clone

		if($arr['owner_xchan'] == $d['hash']) {
			$arr['item_flags'] = $arr['item_flags'] | ITEM_WALL;
		}
		else {
			// clear the wall flag if it is set
			if($arr['item_flags'] & ITEM_WALL) {
				$arr['item_flags'] = ($arr['item_flags'] ^ ITEM_WALL);
			}
		}

		if((! perm_is_allowed($channel['channel_id'],$sender['hash'],$perm)) && (! $tag_delivery)) {
			logger("permission denied for delivery {$channel['channel_id']}");
			$result[] = array($d['hash'],'permission denied',$channel['channel_name'] . ' <' . $channel['channel_address'] . '@' . get_app()->get_hostname() . '>');
			continue;
		}
	
		if($arr['item_restrict'] & ITEM_DELETED) {

			// remove_community_tag is a no-op if this isn't a community tag activity
			remove_community_tag($sender,$arr,$channel['channel_id']);

			$item_id = delete_imported_item($sender,$arr,$channel['channel_id']);
			$result[] = array($d['hash'],(($item_id) ? 'deleted' : 'delete_failed'),$channel['channel_name'] . ' <' . $channel['channel_address'] . '@' . get_app()->get_hostname() . '>');

			if($relay && $item_id) {
				logger('process_delivery: invoking relay');
				proc_run('php','include/notifier.php','relay',intval($item_id));
				$result[] = array($d['hash'],'relayed',$channel['channel_name'] . ' <' . $channel['channel_address'] . '@' . get_app()->get_hostname() . '>');
			}

			continue;
		}

		// for events, extract the event info and create an event linked to an item 

		if((x($arr,'obj_type')) && (activity_match($arr['obj_type'],ACTIVITY_OBJ_EVENT))) {
			require_once('include/event.php');
			$ev = bbtoevent($arr['body']);
		if(x($ev,'desc') && x($ev,'start')) {
				$ev['event_xchan'] = $arr['author_xchan'];
				$ev['uid']         = $channel['channel_id'];
				$ev['account']     = $channel['channel_account_id'];
				$ev['edited']      = $arr['edited'];
				$ev['mid']         = $arr['mid'];
				$ev['private']     = $arr['item_private'];

				// is this an edit?

				$r = q("SELECT resource_id FROM item where mid = '%s' and uid = %d and resource_type = 'event' limit 1",
					dbesc($arr['mid']),
					intval($channel['channel_id'])
				);
				if($r) {
					$ev['event_hash'] = $r[0]['resource_id'];
				}

				$xyz = event_store($ev);

				$result = array($d['hash'],'event processed',$channel['channel_name'] . ' <' . $channel['channel_address'] . '@' . get_app()->get_hostname() . '>');
				continue;
			}
		}

		$r = q("select id, edited from item where mid = '%s' and uid = %d limit 1",
			dbesc($arr['mid']),
			intval($channel['channel_id'])
		);
		if($r) {
			if($arr['edited'] > $r[0]['edited']) {
				$arr['id'] = $r[0]['id'];
				$arr['uid'] = $channel['channel_id'];
				update_imported_item($sender,$arr,$channel['channel_id']);
			}	
			$result[] = array($d['hash'],'updated',$channel['channel_name'] . ' <' . $channel['channel_address'] . '@' . get_app()->get_hostname() . '>');
			$item_id = $r[0]['id'];
		}
		else {
			$arr['aid'] = $channel['channel_account_id'];
			$arr['uid'] = $channel['channel_id'];
			$item_result = item_store($arr);
			$item_id = $item_result['item_id'];
			$result[] = array($d['hash'],(($item_id) ? 'posted' : 'storage failed:' . $item_result['message']),$channel['channel_name'] . ' <' . $channel['channel_address'] . '@' . get_app()->get_hostname() . '>');
		}

		if($relay && $item_id) {
			logger('process_delivery: invoking relay');
			proc_run('php','include/notifier.php','relay',intval($item_id));
			$result[] = array($d['hash'],'relayed',$channel['channel_name'] . ' <' . $channel['channel_address'] . '@' . get_app()->get_hostname() . '>');
		}
	}

	if(! $deliveries)
		$result[] = array('','no recipients');

	logger('process_delivery: local results: ' . print_r($result,true), LOGGER_DEBUG);

	return $result;
}


function remove_community_tag($sender,$arr,$uid) {

	if(! (activity_match($arr['verb'],ACTIVITY_TAG) && ($arr['obj_type'] == ACTIVITY_OBJ_TAGTERM)))
		return;

	logger('remove_community_tag: invoked');
 

	if(! get_pconfig($uid,'system','blocktags')) {
		logger('remove_community tag: permission denied.');
		return;
	}

	$r = q("select * from item where mid = '%s' and uid = %d limit 1",
		dbesc($arr['mid']),
		intval($uid)
	);
	if(! $r) {
		logger('remove_community_tag: no item');
		return;
	}

	if(($sender['hash'] != $r[0]['owner_xchan']) && ($sender['hash'] != $r[0]['author_xchan'])) {
		logger('remove_community_tag: sender not authorised.');
		return;
	}

	$i = $r[0];

	if($i['target'])
		$i['target'] = json_decode_plus($i['target']);
	if($i['object'])
		$i['object'] = json_decode_plus($i['object']);

	if(! ($i['target'] && $i['object'])) {
		logger('remove_community_tag: no target/object');
		return;
	}

	$message_id = $i['target']['id'];

	$r = q("select id from item where mid = '%s' and uid = %d limit 1",
		dbesc($message_id),
		intval($uid)
	);
	if(! $r) {
		logger('remove_community_tag: no parent message');
		return;
	}
	
	$x = q("delete from term where uid = %d and oid = %d and otype = %d and type = %d and term = '%s' and url = '%s' limit 1",
		intval($uid),
		intval($r[0]['id']),
		intval(TERM_OBJ_POST),
		intval(TERM_HASHTAG),
		dbesc($i['object']['title']),
		dbesc(get_rel_link($i['object']['link'],'alternate'))
	);

	return;
}

function update_imported_item($sender,$item,$uid) {

	$x = item_store_update($item);
	if(! $x['item_id'])
		logger('update_imported_item: failed: ' . $x['message']);
	else
		logger('update_imported_item');

}

function delete_imported_item($sender,$item,$uid) {

	logger('delete_imported_item invoked',LOGGER_DEBUG);

	$r = q("select id, item_restrict from item where ( author_xchan = '%s' or owner_xchan = '%s' or source_xchan = '%s' )
		and mid = '%s' and uid = %d limit 1",
		dbesc($sender['hash']),
		dbesc($sender['hash']),
		dbesc($sender['hash']),
		dbesc($item['mid']),
		intval($uid)
	);

	if(! $r) {
		logger('delete_imported_item: failed: ownership issue');
		return false;
	}

	if($r[0]['item_restrict'] & ITEM_DELETED) {
		logger('delete_imported_item: item was already deleted');
		return false;
	} 
		
	require_once('include/items.php');
	drop_item($r[0]['id'],false);

	tag_deliver($uid,$r[0]['id']);

	return $r[0]['id'];
}

function process_mail_delivery($sender,$arr,$deliveries) {


	$result = array();


	if($sender['hash'] != $arr['from_xchan']) {
		logger('process_mail_delivery: sender is not mail author');
		return;
	}


	
	foreach($deliveries as $d) {
		$r = q("select * from channel where channel_hash = '%s' limit 1",
			dbesc($d['hash'])
		);

		if(! $r) {
			$result[] = array($d['hash'],'not found');
			continue;
		}

		$channel = $r[0];

		if(! perm_is_allowed($channel['channel_id'],$sender['hash'],'post_mail')) {
			logger("permission denied for mail delivery {$channel['channel_id']}");
			$result[] = array($d['hash'],'permission denied',$channel['channel_name']);
			continue;
		}
	
		$r = q("select id from mail where mid = '%s' and channel_id = %d limit 1",
			dbesc($arr['mid']),
			intval($channel['channel_id'])
		);
		if($r) {
			if($arr['mail_flags'] & MAIL_RECALLED) {
				$x = q("delete from mail where id = %d and channel_id = %d limit 1",
					intval($r[0]['id']),
					intval($channel['channel_id'])
				);
				$result[] = array($d['hash'],'mail recalled',$channel['channel_name']);
				logger('mail_recalled');
			}
			else {				
				$result[] = array($d['hash'],'duplicate mail received',$channel['channel_name']);
				logger('duplicate mail received');
			}
			continue;
		}
		else {
			$arr['account_id'] = $channel['channel_account_id'];
			$arr['channel_id'] = $channel['channel_id'];
			$item_id = mail_store($arr);
			$result[] = array($d['hash'],'mail delivered',$channel['channel_name']);

		}
	}
	return $result;
}

function process_profile_delivery($sender,$arr,$deliveries) {

	// deliveries is irrelevant, what to do about birthday notification....?

	logger('process_profile_delivery', LOGGER_DEBUG);

	$r = q("select xchan_addr from xchan where xchan_hash = '%s' limit 1",
			dbesc($sender['hash'])
	);
	if($r)
		import_directory_profile($sender['hash'],$arr,$r[0]['xchan_addr'], UPDATE_FLAGS_UPDATED, 0);
}


/*
 * @function import_directory_profile
 * 
 * @returns boolean $updated if something changed
 *
 */

function import_directory_profile($hash,$profile,$addr,$ud_flags = UPDATE_FLAGS_UPDATED, $suppress_update = 0) {

	logger('import_directory_profile', LOGGER_DEBUG);
	if(! $hash)
		return false;

	$arr = array();

	$arr['xprof_hash']         = $hash;
	$arr['xprof_desc']         = (($profile['description'])    ? htmlspecialchars($profile['description'],    ENT_COMPAT,'UTF-8',false) : '');
	$arr['xprof_dob']          = datetime_convert('','',$profile['birthday'],'Y-m-d'); // !!!! check this for 0000 year
	$arr['xprof_age']          = (($profile['age']) ? intval($profile['age']) : 0);
	$arr['xprof_gender']       = (($profile['gender'])    ? htmlspecialchars($profile['gender'],    ENT_COMPAT,'UTF-8',false) : '');
	$arr['xprof_marital']      = (($profile['marital'])    ? htmlspecialchars($profile['marital'],    ENT_COMPAT,'UTF-8',false) : '');
	$arr['xprof_sexual']       = (($profile['sexual'])    ? htmlspecialchars($profile['sexual'],    ENT_COMPAT,'UTF-8',false) : '');
	$arr['xprof_locale']       = (($profile['locale'])    ? htmlspecialchars($profile['locale'],    ENT_COMPAT,'UTF-8',false) : '');
	$arr['xprof_region']       = (($profile['region'])    ? htmlspecialchars($profile['region'],    ENT_COMPAT,'UTF-8',false) : '');
	$arr['xprof_postcode']     = (($profile['postcode'])    ? htmlspecialchars($profile['postcode'],    ENT_COMPAT,'UTF-8',false) : '');
	$arr['xprof_country']      = (($profile['country'])    ? htmlspecialchars($profile['country'],    ENT_COMPAT,'UTF-8',false) : '');

	$arr['xprof_about']      = (($profile['about'])    ? htmlspecialchars($profile['about'],    ENT_COMPAT,'UTF-8',false) : '');
	$arr['xprof_homepage']      = (($profile['homepage'])    ? htmlspecialchars($profile['homepage'],    ENT_COMPAT,'UTF-8',false) : '');
	$arr['xprof_hometown']      = (($profile['hometown'])    ? htmlspecialchars($profile['hometown'],    ENT_COMPAT,'UTF-8',false) : '');

	$clean = array();
	if(array_key_exists('keywords',$profile) and is_array($profile['keywords'])) {
		import_directory_keywords($hash,$profile['keywords']);
		foreach($profile['keywords'] as $kw) {
			$kw = trim(htmlspecialchars($kw,ENT_COMPAT,'UTF-8',false));
			$kw = trim($kw,',');
			$clean[] = $kw;
		}
	}

	$arr['xprof_keywords'] = implode(' ',$clean);

	// Self censored, make it so
	// These are not translated, so the German "erwachsenen" keyword will not censor the directory profile. Only the English form - "adult".   


	if(in_arrayi('nsfw',$clean) || in_arrayi('adult',$clean)) {
		q("update xchan set xchan_flags = (xchan_flags | %d) where xchan_hash = '%s' limit 1",
			intval(XCHAN_FLAGS_SELFCENSORED),
			dbesc($hash)
		);
	}


	$r = q("select * from xprof where xprof_hash = '%s' limit 1",
		dbesc($hash)
	);
	if($r) {
		$update = false;
		foreach($r[0] as $k => $v) {
			if((array_key_exists($k,$arr)) && ($arr[$k] != $v)) {
				logger('import_directory_profile: update' . $k . ' => ' . $arr[$k]);
				$update = true;
				break;
			}
		}
		if($update) {
			$x = q("update xprof set 
				xprof_desc = '%s', 
				xprof_dob = '%s', 
				xprof_age = %d,
				xprof_gender = '%s', 
				xprof_marital = '%s', 
				xprof_sexual = '%s', 
				xprof_locale = '%s', 
				xprof_region = '%s', 
				xprof_postcode = '%s', 
				xprof_country = '%s',
				xprof_about = '%s',
				xprof_homepage = '%s',
				xprof_hometown = '%s',
				xprof_keywords = '%s'
				where xprof_hash = '%s' limit 1",
				dbesc($arr['xprof_desc']),
				dbesc($arr['xprof_dob']),
				intval($arr['xprof_age']),
				dbesc($arr['xprof_gender']),
				dbesc($arr['xprof_marital']),
				dbesc($arr['xprof_sexual']),
				dbesc($arr['xprof_locale']),
				dbesc($arr['xprof_region']),
				dbesc($arr['xprof_postcode']),
				dbesc($arr['xprof_country']),
				dbesc($arr['xprof_about']),
				dbesc($arr['xprof_homepage']),
				dbesc($arr['xprof_hometown']),
				dbesc($arr['xprof_keywords']),
				dbesc($arr['xprof_hash'])
			);
		}
	}
	else {
		$update = true;
		logger('import_directory_profile: new profile');
		$x = q("insert into xprof (xprof_hash, xprof_desc, xprof_dob, xprof_age, xprof_gender, xprof_marital, xprof_sexual, xprof_locale, xprof_region, xprof_postcode, xprof_country, xprof_about, xprof_homepage, xprof_hometown, xprof_keywords) values ('%s', '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s') ",
			dbesc($arr['xprof_hash']),
			dbesc($arr['xprof_desc']),
			dbesc($arr['xprof_dob']),
			intval($arr['xprof_age']),
			dbesc($arr['xprof_gender']),
			dbesc($arr['xprof_marital']),
			dbesc($arr['xprof_sexual']),
			dbesc($arr['xprof_locale']),
			dbesc($arr['xprof_region']),
			dbesc($arr['xprof_postcode']),
			dbesc($arr['xprof_country']),
			dbesc($arr['xprof_about']),
			dbesc($arr['xprof_homepage']),
			dbesc($arr['xprof_hometown']),
			dbesc($arr['xprof_keywords'])
		);
	}

	$d = array('xprof' => $arr, 'profile' => $profile, 'update' => $update);
	call_hooks('import_directory_profile', $d);

	if(($d['update']) && (! $suppress_update))
		update_modtime($arr['xprof_hash'],random_string() . '@' . get_app()->get_hostname(), $addr, $ud_flags);
	return $d['update'];
}

function import_directory_keywords($hash,$keywords) {

	$existing = array();
	$r = q("select * from xtag where xtag_hash = '%s'",
		dbesc($hash)
	);

	if($r) {
		foreach($r as $rr)
			$existing[] = $rr['xtag_term'];
	}

	$clean = array();
	foreach($keywords as $kw) {
		$kw = trim(htmlspecialchars($kw,ENT_COMPAT,'UTF-8',false));
		$kw = trim($kw,',');
		$clean[] = $kw;
	}

	foreach($existing as $x) {
		if(! in_array($x,$clean))
			$r = q("delete from xtag where xtag_hash = '%s' and xtag_term = '%s' limit 1",
				dbesc($hash),
				dbesc($x)
			);
	}
	foreach($clean as $x) {
		if(! in_array($x,$existing))
			$r = q("insert into xtag ( xtag_hash, xtag_term) values ( '%s' ,'%s' )",
				dbesc($hash),
				dbesc($x)
			);
	}
}


function update_modtime($hash,$guid,$addr,$flags = 0) {

	$dirmode = intval(get_config('system','directory_mode'));

	if($dirmode == DIRECTORY_MODE_NORMAL)
		return;

	if($flags) {
		q("insert into updates (ud_hash, ud_guid, ud_date, ud_flags, ud_addr ) values ( '%s', '%s', '%s', %d, '%s' )",
			dbesc($hash),
			dbesc($guid),
			dbesc(datetime_convert()),
			intval($flags),
			dbesc($addr)
		);
	}
	else {
		q("update updates set ud_flags = ( ud_flags | %d ) where ud_addr = '%s' and not (ud_flags & %d) ",
			intval(UPDATE_FLAGS_UPDATED),
			dbesc($addr),
			intval(UPDATE_FLAGS_UPDATED)
		);
	}
}


function import_site($arr,$pubkey) {
	if( (! is_array($arr)) || (! $arr['url']) || (! $arr['url_sig']))
		return false;

	if(! rsa_verify($arr['url'],base64url_decode($arr['url_sig']),$pubkey)) {
		logger('import_site: bad url_sig');
		return false;
	}

	$update = false;
	$exists = false;

	$r = q("select * from site where site_url = '%s' limit 1",
		dbesc($arr['url'])
	);
	if($r) {
		$exists = true;
		$siterecord = $r[0];
	}

	$site_directory = 0;
	if($arr['directory_mode'] == 'normal')
		$site_directory = DIRECTORY_MODE_NORMAL;

	if($arr['directory_mode'] == 'primary')
		$site_directory = DIRECTORY_MODE_PRIMARY;
	if($arr['directory_mode'] == 'secondary')
		$site_directory = DIRECTORY_MODE_SECONDARY;
	if($arr['directory_mode'] == 'standalone')
		$site_directory = DIRECTORY_MODE_STANDALONE;

	$register_policy = 0;
	if($arr['register_policy'] == 'closed')
		$register_policy = REGISTER_CLOSED;
	if($arr['register_policy'] == 'open')
		$register_policy = REGISTER_OPEN;
	if($arr['register_policy'] == 'approve')
		$register_policy = REGISTER_APPROVE;

	$access_policy = 0;
	if(array_key_exists('access_policy',$arr)) {
		if($arr['access_policy'] === 'private')
			$access_policy = ACCESS_PRIVATE;
		if($arr['access_policy'] === 'paid')
			$access_policy = ACCESS_PAID;
		if($arr['access_policy'] === 'free')
			$access_policy = ACCESS_FREE;
		if($arr['access_policy'] === 'tiered')
			$access_policy = ACCESS_TIERED;
	}

	// don't let insecure sites register as public hubs

	if(strpos($arr['url'],'https://') === false)
		$access_policy = ACCESS_PRIVATE;

	if($access_policy != ACCESS_PRIVATE) {
		$x = z_fetch_url($arr['url'] . '/siteinfo/json');
		if(! $x['success'])
			$access_policy = ACCESS_PRIVATE;
	}
	
	$directory_url = htmlspecialchars($arr['directory_url'],ENT_COMPAT,'UTF-8',false);
	$url = htmlspecialchars($arr['url'],ENT_COMPAT,'UTF-8',false);
	$sellpage = htmlspecialchars($arr['sellpage'],ENT_COMPAT,'UTF-8',false);
	$site_location = htmlspecialchars($arr['location'],ENT_COMPAT,'UTF-8',false);

	if($exists) {
		if(($siterecord['site_flags'] != $site_directory)
			|| ($siterecord['site_access'] != $access_policy)
			|| ($siterecord['site_directory'] != $directory_url)
			|| ($siterecord['site_sellpage'] != $sellpage)
			|| ($siterecord['site_location'] != $site_location)
			|| ($siterecord['site_register'] != $register_policy)) {
			$update = true;

//			logger('import_site: input: ' . print_r($arr,true));
//			logger('import_site: stored: ' . print_r($siterecord,true));

			$r = q("update site set site_location = '%s', site_flags = %d, site_access = %d, site_directory = '%s', site_register = %d, site_update = '%s', site_sellpage = '%s'
				where site_url = '%s' limit 1",
				dbesc($site_location),
				intval($site_directory),
				intval($access_policy),
				dbesc($directory_url),
				intval($register_policy),
				dbesc(datetime_convert()),
				dbesc($sellpage),
				dbesc($url)
			);
			if(! $r) {
				logger('import_site: update failed. ' . print_r($arr,true));
			}
		}
	}
	else {
		$update = true;
		$r = q("insert into site ( site_location, site_url, site_access, site_flags, site_update, site_directory, site_register, site_sellpage )
			values ( '%s', '%s', %d, %d, '%s', '%s', %d, '%s' )",
			dbesc($site_location),
			dbesc($url),
			intval($access_policy),
			intval($site_directory),
			dbesc(datetime_convert()),
			dbesc($directory_url),
			intval($register_policy),
			dbesc($sellpage)
		);
		if(! $r) {
			logger('import_site: record create failed. ' . print_r($arr,true));
		}
	}

	return $update;

}



/**
 * Send a zot packet to all hubs where this channel is duplicated, refreshing
 * such things as personal settings, channel permissions, address book updates, etc.
 */

function build_sync_packet($uid = 0, $packet = null) {

	$a = get_app();

	logger('build_sync_packet');

	if(! $uid)
		$uid = local_user();

	if(! $uid)
		return;

    $r = q("select * from channel where channel_id = %d limit 1",
		intval($uid)
	);
	if(! $r)
		return;
 
	$channel = $r[0];

	$h = q("select * from hubloc where hubloc_hash = '%s'",
		dbesc($channel['channel_hash'])
	);

	if(! $h)
		return;

	$synchubs = array();

	foreach($h as $x) {
		if($x['hubloc_host'] == $a->get_hostname())
			continue;
		$synchubs[] = $x;
	}

	if(! $synchubs)
		return;

	$r = q("select xchan_guid, xchan_guid_sig from xchan where xchan_hash  = '%s' limit 1",
		dbesc($channel['channel_hash'])
	);
	if(! $r)
		return;

	$env_recips = array();
	$env_recips[] = array('guid' => $r[0]['xchan_guid'],'guid_sig' => $r[0]['xchan_guid_sig']);

	$info = (($packet) ? $packet : array());
	$info['type'] = 'channel_sync';

	if(array_key_exists($uid,$a->config) && array_key_exists('transient',$a->config[$uid])) {
		$settings = $a->config[$uid]['transient'];
		if($settings) {
			$info['config'] = $settings;
		}
	}
	
	if($channel) {
		$info['channel'] = array();
		foreach($channel as $k => $v) {

			// filter out any joined tables like xchan

			if(strpos($k,'channel_') !== 0)
				continue;

			// don't pass these elements, they should not be synchronised

			$disallowed = array('channel_id','channel_account_id','channel_primary','channel_prvkey','channel_address');

			if(in_array($k,$disallowed))
				continue;

			$info['channel'][$k] = $v;
		}
	}

	$interval = ((get_config('system','delivery_interval') !== false) 
			? intval(get_config('system','delivery_interval')) : 2 );


	logger('build_sync_packet: packet: ' . print_r($info,true), LOGGER_DATA);

	foreach($synchubs as $hub) {
		$hash = random_string();
		$n = zot_build_packet($channel,'notify',$env_recips,$hub['hubloc_sitekey'],$hash);
		q("insert into outq ( outq_hash, outq_account, outq_channel, outq_posturl, outq_async, outq_created, outq_updated, outq_notify, outq_msg ) values ( '%s', %d, %d, '%s', %d, '%s', '%s', '%s', '%s' )",
			dbesc($hash),
			intval($channel['channel_account']),
			intval($channel['channel_id']),
			dbesc($hub['hubloc_callback']),
			intval(1),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			dbesc($n),
			dbesc(json_encode($info))
		);

		proc_run('php','include/deliver.php',$hash);
		if($interval)
			@time_sleep_until(microtime(true) + (float) $interval);
	}


}

function process_channel_sync_delivery($sender,$arr,$deliveries) {

// FIXME - this will sync red structures (channel, pconfig and abook). Eventually we need to make this application agnostic.
// TODO: missing group membership changes

	$result = array();
	
	foreach($deliveries as $d) {
		$r = q("select * from channel where channel_hash = '%s' limit 1",
			dbesc($d['hash'])
		);

		if(! $r) {
			$result[] = array($d['hash'],'not found');
			continue;
		}

		$channel = $r[0];

		if($channel['channel_hash'] != $sender['hash']) {
			logger('process_channel_sync_delivery: possible forgery. Sender ' . $sender['hash'] . ' is not ' . $channel['channel_hash']);
			$result[] = array($d['hash'],'channel mismatch',$channel['channel_name']);
			continue;
		}

		if(array_key_exists('config',$arr) && is_array($arr['config']) && count($arr['config'])) {
			foreach($arr['config'] as $cat => $k) {
				foreach($arr['config'][$cat] as $k => $v)
					set_pconfig($channel['channel_id'],$cat,$k,$v);
			}
		}

		if(array_key_exists('channel',$arr) && is_array($arr['channel']) && count($arr['channel'])) {
			$disallowed = array('channel_id','channel_account_id','channel_primary','channel_prvkey', 'channel_address', 'channel_notifyflags');

			$clean = array();
			foreach($arr['channel'] as $k => $v) {
				if(in_array($k,$disallowed))
					continue;
				$clean[$k] = $v;
			}
			if(count($clean)) {
				foreach($clean as $k => $v) {
					$r = dbq("UPDATE channel set " . dbesc($k) . " = '" . dbesc($v) 
						. "' where channel_id = " . intval($channel['channel_id']) . " limit 1");
				}
			}
		}


		if(array_key_exists('abook',$arr) && is_array($arr['abook']) && count($arr['abook'])) {

			$disallowed = array('abook_id','abook_account','abook_channel');

			$clean = array();
			foreach($arr['abook'] as $abook) {

				// Perform discovery if the referenced xchan hasn't ever been seen on this hub.
				// This relies on the undocumented behaviour that red sites send xchan info with the abook

				if($abook['abook_xchan'] && $abook['xchan_address']) {
					$h = zot_get_hublocs($abook['abook_xchan']);
					if(! $h) {
						$f = zot_finger($abook['xchan_address'],$channel);
						if(! $f['success']) {
							logger('process_channel_sync_delivery: abook not probe-able' . $abook['xchan_address']);
							continue;
						}
						$j = json_decode($f['body'],true);
				        if(! ($j['success'] && $j['guid'])) {
							logger('process_channel_sync_delivery: probe failed.');
							continue;
						}

						$x = import_xchan($j);

						if(! $x['success']) {
							logger('process_channel_sync_delivery: import failed.');
							continue;
						}
					}
				}

				foreach($abook as $k => $v) {
					if(in_array($k,$disallowed) || (strpos($k,'abook') !== 0))
						continue;
					$clean[$k] = $v;
				}

				if(! array_key_exists('abook_xchan',$clean))
					continue;

				$r = q("select * from abook where abook_xchan = '%s' and abook_channel = %d limit 1",
					dbesc($clean['abook_xchan']),
					intval($channel['channel_id'])
				);

				// make sure we have an abook entry for this xchan on this system

				if(! $r) {
					q("insert into abook ( abook_xchan, abook_channel ) values ('%s', %d ) ",
						dbesc($clean['abook_xchan']),
						intval($channel['channel_id'])
					);
				} 

				if(count($clean)) {
					foreach($clean as $k => $v) {
						$r = dbq("UPDATE abook set " . dbesc($k) . " = '" . dbesc($v) 
						. "' where abook_xchan = '" . dbesc($clean['abook_xchan']) . "' and abook_channel = " . intval($channel['channel_id']) 
						. " limit 1");
					}
				}
			}
		}
		
		$result[] = array($d['hash'],'channel sync updated',$channel['channel_name']);


	}
	return $result;
}

// We probably should make rpost discoverable.
 
function get_rpost_path($observer) {
	if(! $observer)
		return '';
	$parsed = parse_url($observer['xchan_url']);
	return $parsed['scheme'] . '://' . $parsed['host'] . (($parsed['port']) ? ':' . $parsed['port'] : '') . '/rpost?f=';

}

function import_author_zot($x) {
	$hash = base64url_encode(hash('whirlpool',$x['guid'] . $x['guid_sig'], true));
	$r = q("select hubloc_url from hubloc where hubloc_guid = '%s' and hubloc_guid_sig = '%s' and (hubloc_flags & %d) limit 1",
		dbesc($x['guid']),
		dbesc($x['guid_sig']),
		intval(HUBLOC_FLAGS_PRIMARY)
	);

	if($r) {
		logger('import_author_zot: in cache', LOGGER_DEBUG);
		return $hash;
	}

	logger('import_author_zot: entry not in cache - probing: ' . print_r($x,true), LOGGER_DEBUG);
	
	$them = array('hubloc_url' => $x['url'],'xchan_guid' => $x['guid'], 'xchan_guid_sig' => $x['guid_sig']);
	if(zot_refresh($them))
		return $hash;
	return false;
}

