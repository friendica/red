<?php

/**
 *
 * @function zot_new_uid($entity_id)
 * @entity_id = integer id of controlling entity
 * @returns string
 *
 */

function zot_new_uid($entity_id) {
	$rawstr = z_root() . '/' . $entity_id . '.' . mt_rand();
	return(base64url_encode(hash('whirlpool',$rawstr,true),true) . '.' . mt_rand());
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
	return q("select * from hubloc where hubloc_guid in ( $tmp ) $sql_extra order by hubloc_url $limit");

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



function zot_notify($entity,$url) {
	$x = z_post_url($url, array(
		'type' => 'notify',
		'guid' => $entity['entity_global_id'],
		'hub' => z_root(), 
		'callback' => '/post', 
		'spec' => ZOT_REVISION)
	);
	return($x);
}

		
function zot_gethub($arr) {

	if((x($arr,'hub')) && (x($arr,'guid'))) {
		$r = q("select * from hubloc 
				where hubloc_guid = '%s' and hubloc_url = '%s' 
				limit 1",
			dbesc($arr['guid']),
			dbesc($arr['hub'])
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
					if( ! zot_gethub(array('hub' => $h->url, 'guid' => $arr['guid']))) {
						$r = q("insert into hubloc (hubloc_guid, hubloc_flags, hubloc_url, 
								hubloc_callback, hubloc_sitekey, hubloc_key)
							values ( '%s', %d, '%s', '%s', '%s', '%s' )",
							dbesc($arr['guid']),
							intval((($h->primary) ? HUBLOC_FLAGS_PRIMARY : 0) | HUBLOC_FLAGS_UNVERIFIED ),
							dbesc($h->url),
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
