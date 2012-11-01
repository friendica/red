<?php

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
	 
// Given an item and an identity, sign the data.

function zot_sign(&$item,$identity) {
	$item['signed'] = str_replace(array(" ","\t","\n","\r"),array('','','',''),base64url_encode($item['body'],true));
	$item['signature'] = base64url_encode(rsa_sign($item['signed'],$identity['prvkey']));
}

// Given an item and an identity, verify the signature.

function zot_verify(&$item,$identity) {
	return rsa_verify($item['signed'],base64url_decode($item['signature']),$identity['pubkey']);
}



function zot_notify($channel,$url) {
	$x = z_post_url($url, array(
		'type' => 'notify',
		'guid' => $channel['channel_guid'],
		'guid_sig' => base64url_encode($guid,$channel['prvkey']),
		'hub' => z_root(),
		'hub_sig' => base64url_encode(z_root,$channel['prvkey']), 
		'callback' => '/post', 
		'spec' => ZOT_REVISION)
	);
	return($x);
}

		
function zot_gethub($arr) {

	if((x($arr,'guid')) && (x($arr,'guid_sig')) && (x($arr,'hub')) && (x($arr,'hub_sig'))) {
		$r = q("select * from hubloc 
				where hubloc_guid = '%s' and hubloc_guid_sig = '%s' 
				and hubloc_url = '%s' and hubloc_url_sig = '%s'
				limit 1",
			dbesc($arr['guid']),
			dbesc($arr['guid_sig']),
			dbesc($arr['hub']),
			dbesc($arr['hub_sig'])
		);
		if($r && count($r))
			return $r[0];
	}
	return null;
}

function zot_register_hub($arr) {
	$total = 0;
	if((x($arr,'hub')) && (x($arr,'guid'))) {
		$x = z_fetch_url($arr['hub'] . '/.well-known/zot-guid/' . $arr['guid']);
		if($x['success']) {
			$record = json_decode($x['body']);
			if($record->hub && count($record->hub)) {
				foreach($record->hub as $h) {
					// store any hubs we don't know about
					if( ! zot_gethub(
							array('guid' => $arr['guid'],
								'guid_sig' => $arr['guid_sig'],
								'hub' => $h->url, 
								'hub_sig' => $h->url_sig))) {
						$r = q("insert into hubloc (hubloc_guid, hubloc_guid_sig, hubloc_flags, hubloc_url, 
								hubloc_url_sig, hubloc_callback, hubloc_sitekey, hubloc_key)
							values ( '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s' )",
							dbesc($arr['guid']),
							dbesc($arr['guid_sig']),
							intval((($h->primary) ? HUBLOC_FLAGS_PRIMARY : 0) | HUBLOC_FLAGS_UNVERIFIED ),
							dbesc($h->url),
							dbesc($h->url_sig),
							dbesc($h->callback),
							dbesc($h->sitekey),
							dbesc($record->key)
						);
						if($r)
							$total ++;
					}
				}
			}
		}
	}
	return $total;
}
