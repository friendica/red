<?php

require_once('include/crypto.php');
/**
 *
 * @function zot_new_uid($channel_nick)
 * @channel_id = unique nickname of controlling entity
 * @returns string
 *
 */

function zot_new_uid($channel_nick) {
	$rawstr = z_root() . '/' . $channel_nick . '.' . mt_rand();
	return(base64url_encode(hash('whirlpool',$rawstr,true),true));
}


/**
 *
 * Given an array of zot_uid(s), return all distinct hubs
 * If primary is true, return only primary hubs
 * Result is ordered by url to assist in batching.
 * Return only the first primary hub as there should only be one.
 *
 */

function zot_get_hubloc($arr,$primary = false) {

	$tmp = '';
	
	if(is_array($arr)) {
		foreach($arr as $e) {
			if(strlen($tmp))
				$tmp .= ',';
			$tmp .= "'" . dbesc($e) . "'" ;
		}
	}
	
	if(! strlen($tmp))
		return array();

	$sql_extra = (($primary) ? " and hubloc_flags & " . intval(HUBLOC_FLAGS_PRIMARY) : "" );
	$limit = (($primary) ? " limit 1 " : "");
	return q("select * from hubloc where hubloc_hash in ( $tmp ) $sql_extra order by hubloc_url $limit");

}
	 
function zot_notify($channel,$url,$type = 'notify',$recipients = null, $remote_key = null) {


// FIXME json encode all params
// build the packet externally so that here we really are doing just a zot of the packet. 

	$params = array(
		'type' => $type,
		'sender' => json_encode(array(
			'guid' => $channel['channel_guid'],
			'guid_sig' => base64url_encode(rsa_sign($channel['channel_guid'],$channel['channel_prvkey'])),
			'url' => z_root(),
			'url_sig' => base64url_encode(rsa_sign(z_root(),$channel['channel_prvkey']))
		)), 
		'callback' => '/post',
		'version' => ZOT_REVISION
	);


	if($recipients)
		$params['recipients'] = json_encode($recipients);

	// Hush-hush ultra top-secret mode

	if($remote_key) {
		$params = aes_encapsulate($params,$remote_key);
	}

	$x = z_post_url($url,$params);
	return($x);
}

/*
 *
 * zot_build_packet builds a notification packet that you can either
 * store in the queue with a message array or call zot_zot to immediately 
 * zot it to the other side
 *
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

	if($secret)
		$data['secret'] = $secret; 

	// Hush-hush ultra top-secret mode

	if($remote_key) {
		$data = aes_encapsulate($data,$remote_key);
	}

	return json_encode($data);
}


function zot_zot($url,$data) {
	return z_post_url($url,array('data' => $data));
}

function zot_finger($webbie,$channel) {


	if(strpos($webbie,'@') === false) {
		$address = $webbie;
		$host = get_app()->get_hostname();
	}
	else {
		$address = substr($webbie,0,strpos($webbie,'@'));
		$host = substr($webbie,strpos($webbie,'@')+1);
	}

	$xchan_addr = $address . '@' . $host;

	$r = q("select xchan.*, hubloc.* from xchan 
			left join hubloc on xchan_hash = hubloc_hash
			where xchan_addr = '%s' and (hubloc_flags & %d) limit 1",
		dbesc($xchan_address),
		intval(HUBLOC_FLAGS_PRIMARY)
	);

	if($r) {
		$url = $r[0]['hubloc_url'];
	}
	else {
		$url = 'https://' . $host;
	}
	
	$rhs = '/.well-known/zot-info';

	if($channel) {
		$postvars = array(
			'address'    => $address,
			'target'     => $channel['channel_guid'],
			'target_sig' => $channel['channel_guid_sig'],
			'key'        => $channel['channel_pubkey']
		);
		$result = z_post_url($url . $rhs,$postvars);
		if(! $result['success'])
			$result = z_post_url('http://' . $host . $rhs,$postvars);
	}		
	else {
		$rhs .= 'address=' . urlencode($address);

		$result =  z_fetch_url($url . $rhs);
		if(! $result['success'])
			$result = z_fetch_url('http://' . $host . $rhs);
	}
	
	return $result;	 

}

function zot_refresh($them,$channel = null) {

	if($them['hubloc_url'])
		$url = $them['hubloc_url'];
	else {
		$r = q("select hubloc_url from hubloc where hubloc_hash = '%s' and hubloc_flags & %d limit 1",
			dbesc($them['xchan_hash']),
			intval(HUBLOC_FLAGS_PRIMARY)
		);
		if($r)
			$url = $r[0]['hubloc_url'];
	}
	if(! $url)
		return;

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
	
	if($result['success']) {

		$j = json_decode($result['body']);

		$x = import_xchan_from_json($j);

		if(! $x['success']) 
			return $x;

		$xchan_hash = $x['hash'];

		$their_perms = 0;


		if($channel) {
			$global_perms = get_perms();
			if($j->permissions->data) {
				$permissions = aes_unencapsulate(array(
					'data' => $j->permissions->data,
					'key'  => $j->permissions->key,
					'iv'   => $j->permissions->iv),
					$channel['channel_prvkey']);
				if($permissions)
					$permissions = json_decode($permissions);
				logger('decrypted permissions: ' . print_r($permissions,true), LOGGER_DATA);
			}
			else
				$permissions = $j->permissions;

			foreach($permissions as $k => $v) {
				if($v) {
					$their_perms = $their_perms | intval($global_perms[$k][1]);
				}
			}

			$r = q("update abook set abook_their_perms = %d 
				where abook_xchan = '%s' and abook_channel = %d 
				and not (abook_flags & %d) limit 1",
				intval($their_perms),
				dbesc($x['hash']),
				intval($channel['channel_id']),
				intval(ABOOK_FLAG_SELF)
			);

			if(! $r)
				logger('abook update failed');
		}

		return true;
	}
	return false;

}

		
function zot_gethub($jarr) {

	if($jarr->guid && $jarr->guid_sig && $jarr->url && $jarr->url_sig) {
		$r = q("select * from hubloc 
				where hubloc_guid = '%s' and hubloc_guid_sig = '%s' 
				and hubloc_url = '%s' and hubloc_url_sig = '%s'
				limit 1",
			dbesc($jarr->guid),
			dbesc($jarr->guid_sig),
			dbesc($jarr->url),
			dbesc($jarr->url_sig)
		);
		if($r && count($r))
			return $r[0];
	}
	return null;
}

function zot_register_hub($arr) {

	$result = array('success' => false);

	if($arr->hub && $arr->hub_sig && $arr->guid && $arr->guid_sig) {

		$guid_hash = base64url_encode(hash('whirlpool',$arr->guid . $arr->guid_sig, true));

		$x = z_fetch_url($arr->hub . '/.well-known/zot-info/?f=&hash=' . $guid_hash);

		if($x['success']) {
			$record = json_decode($x['body']);
			$c = import_xchan_from_json($record);
			if($c['success'])
				$result['success'] = true;			
		}
	}
	return $result;
}


// Takes a json array from zot_finger and imports the xchan and hublocs
// If the xchan already exists, update the name and photo if these have changed.
// 


function import_xchan_from_json($j) {

	$ret = array('success' => false);

	$xchan_hash = base64url_encode(hash('whirlpool',$j->guid . $j->guid_sig, true));
	$import_photos = false;

	if(! rsa_verify($j->guid,base64url_decode($j->guid_sig),$j->key)) {
		logger('import_xchan_from_json: Unable to verify channel signature for ' . $j->address);
		$ret['message'] = t('Unable to verify channel signature');
		return $ret;
	}

	$r = q("select * from xchan where xchan_hash = '%s' limit 1",
		dbesc($xchan_hash)
	);	

	if($r) {
		if($r[0]['xchan_photo_date'] != $j->photo_updated)
			$update_photos = true;
		if($r[0]['xchan_name_date'] != $j->name_updated) {
			$r = q("update xchan set xchan_name = '%s', xchan_name_date = '%s' where xchan_hash = '%s' limit 1",
				dbesc($j->name),
				dbesc($j->name_updated),
				dbesc($xchan_hash)
			);
		}
	}
	else {
		$import_photos = true;
		$x = q("insert into xchan ( xchan_hash, xchan_guid, xchan_guid_sig, xchan_pubkey, xchan_photo_mimetype,
				xchan_photo_l, xchan_addr, xchan_url, xchan_name, xchan_network, xchan_photo_date, xchan_name_date)
				values ( '%s', '%s', '%s', '%s' , '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s') ",
			dbesc($xchan_hash),
			dbesc($j->guid),
			dbesc($j->guid_sig),
			dbesc($j->key),
			dbesc($j->photo_mimetype),
			dbesc($j->photo),
			dbesc($j->address),
			dbesc($j->url),
			dbesc($j->name),
			dbesc('zot'),
			dbesc($j->photo_updated),
			dbesc($j->name_updated)
		);

	}				


	if($import_photos) {

		require_once("Photo.php");

		$photos = import_profile_photo($j->photo,0,$xchan_hash);
		$r = q("update xchan set xchan_photo_date = '%s', xchan_photo_l = '%s', xchan_photo_m = '%s', xchan_photo_s = '%s', xchan_photo_mimetype = '%s'
				where xchan_hash = '%s' limit 1",
				dbesc($j->photo_updated),
				dbesc($photos[0]),
				dbesc($photos[1]),
				dbesc($photos[2]),
				dbesc($photos[3]),
				dbesc($xchan_hash)
		);
	}

	if($j->locations) {
		foreach($j->locations as $location) {
			if(! rsa_verify($location->url,base64url_decode($location->url_sig),$j->key)) {
				logger('import_xchan_from_json: Unable to verify site signature for ' . $location->url);
				$ret['message'] .= sprintf( t('Unable to verify site signature for %s'), $location->url) . EOL;
				continue;
			}

			$r = q("select * from hubloc where hubloc_hash = '%s' and hubloc_url = '%s' limit 1",
				dbesc($xchan_hash),
				dbesc($location->url)
			);
			if($r) {
				if(($r[0]['hubloc_flags'] & HUBLOC_FLAGS_PRIMARY) && (! $location->primary)) {
					$r = q("update hubloc set hubloc_flags = (hubloc_flags ^ %d) where hubloc_id = %d limit 1",
						intval(HUBLOC_FLAGS_PRIMARY),
						intval($r[0]['hubloc_id'])
					);
				}
				continue;
			}

			$r = q("insert into hubloc ( hubloc_guid, hubloc_guid_sig, hubloc_hash, hubloc_addr, hubloc_flags, hubloc_url, hubloc_url_sig, hubloc_host, hubloc_callback, hubloc_sitekey)
					values ( '%s','%s','%s','%s', %d ,'%s','%s','%s','%s','%s')",
				dbesc($j->guid),
				dbesc($j->guid_sig),
				dbesc($xchan_hash),
				dbesc($location->address),
				intval((intval($location->primary)) ? HUBLOC_FLAGS_PRIMARY : 0),
				dbesc($location->url),
				dbesc($location->url_sig),
				dbesc($location->host),
				dbesc($location->callback),
				dbesc($location->sitekey)
			);

		}

	}

	if(! x($ret,'message')) {
		$ret['success'] = true;
		$ret['hash'] = $xchan_hash;
	}
	return $ret;
}

// Takes a json associative array from zot_finger and imports the xchan and hublocs
// If the xchan already exists, update the name and photo if these have changed.
// 


function import_xchan($j) {

	$ret = array('success' => false);

	$xchan_hash = base64url_encode(hash('whirlpool',$j['guid'] . $j['guid_sig'], true));
	$import_photos = false;

	if(! rsa_verify($j['guid'],base64url_decode($j['guid_sig']),$j['key'])) {
		logger('import_xchan_from_json: Unable to verify channel signature for ' . $j['address']);
		$ret['message'] = t('Unable to verify channel signature');
		return $ret;
	}

	$r = q("select * from xchan where xchan_hash = '%s' limit 1",
		dbesc($xchan_hash)
	);	

	if($r) {
		if($r[0]['xchan_photo_date'] != $j['photo_updated'])
			$update_photos = true;
		if($r[0]['xchan_name_date'] != $j['name_updated']) {
			$r = q("update xchan set xchan_name = '%s', xchan_name_date = '%s' where xchan_hash = '%s' limit 1",
				dbesc($j['name']),
				dbesc($j['name_updated']),
				dbesc($xchan_hash)
			);
		}
	}
	else {
		$import_photos = true;
		$x = q("insert into xchan ( xchan_hash, xchan_guid, xchan_guid_sig, xchan_pubkey, xchan_photo_mimetype,
				xchan_photo_l, xchan_addr, xchan_url, xchan_name, xchan_network, xchan_photo_date, xchan_name_date)
				values ( '%s', '%s', '%s', '%s' , '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s') ",
			dbesc($xchan_hash),
			dbesc($j['guid']),
			dbesc($j['guid_sig']),
			dbesc($j['key']),
			dbesc($j['photo_mimetype']),
			dbesc($j['photo']),
			dbesc($j['address']),
			dbesc($j['url']),
			dbesc($j['name']),
			dbesc('zot'),
			dbesc($j['photo_updated']),
			dbesc($j['name_updated'])
		);

	}				


	if($import_photos) {

		require_once("Photo.php");

		$photos = import_profile_photo($j['photo'],0,$xchan_hash);
		$r = q("update xchan set xchan_photo_date = '%s', xchan_photo_l = '%s', xchan_photo_m = '%s', xchan_photo_s = '%s', xchan_photo_mimetype = '%s'
				where xchan_hash = '%s' limit 1",
				dbesc($j['photo_updated']),
				dbesc($photos[0]),
				dbesc($photos[1]),
				dbesc($photos[2]),
				dbesc($photos[3]),
				dbesc($xchan_hash)
		);
	}

	if($j['locations']) {
		foreach($j['locations'] as $location) {
			if(! rsa_verify($location['url'],base64url_decode($location['url_sig']),$j['key'])) {
				logger('import_xchan_from_json: Unable to verify site signature for ' . $location['url']);
				$ret['message'] .= sprintf( t('Unable to verify site signature for %s'), $location['url']) . EOL;
				continue;
			}

			$r = q("select * from hubloc where hubloc_hash = '%s' and hubloc_url = '%s' limit 1",
				dbesc($xchan_hash),
				dbesc($location['url'])
			);
			if($r) {
				if(($r[0]['hubloc_flags'] & HUBLOC_FLAGS_PRIMARY) && (! $location['primary'])) {
					$r = q("update hubloc set hubloc_flags = (hubloc_flags ^ %d) where hubloc_id = %d limit 1",
						intval(HUBLOC_FLAGS_PRIMARY),
						intval($r[0]['hubloc_id'])
					);
				}
				continue;
			}

			$r = q("insert into hubloc ( hubloc_guid, hubloc_guid_sig, hubloc_hash, hubloc_addr, hubloc_flags, hubloc_url, hubloc_url_sig, hubloc_host, hubloc_callback, hubloc_sitekey)
					values ( '%s','%s','%s','%s', %d ,'%s','%s','%s','%s','%s')",
				dbesc($j['guid']),
				dbesc($j['guid_sig']),
				dbesc($xchan_hash),
				dbesc($location['address']),
				intval((intval($location['primary'])) ? HUBLOC_FLAGS_PRIMARY : 0),
				dbesc($location['url']),
				dbesc($location['url_sig']),
				dbesc($location['host']),
				dbesc($location['callback']),
				dbesc($location['sitekey'])
			);

		}

	}

	if(! x($ret,'message')) {
		$ret['success'] = true;
		$ret['hash'] = $xchan_hash;
	}
	return $ret;
}

