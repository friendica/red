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
 *
 */

function zot_get_hubloc($arr,$primary) {

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

	$sql_extra = (($primary) ? " and hubloc_primary = 1 " : "" );
	return q("select * from hubloc where hubloc_zuid in ( $tmp ) $sql_extra order by hubloc_url");

}
	 
// Given an item and an identity, sign the data.

function zot_sign(&$item,$identity) {
	$item['signed'] = str_replace(array(" ","\t","\n","\r"),array('','','',''),base64url_encode($item['body'],true));
	$item['signature'] = base64url_encode(rsa_sign($item['signed'],$identity['prvkey']));
}

// Given an item and an identity, verify the signature.

function zot_verify(&$item,$identity) {
	return rsa_verify($item[signed'],base64url_decode($item['signature']),$identity['pubkey']);
}



function zot_notify($entity,$url) {
	$x = z_post_url($url,
		array('zot_uid' => $entity_global_id, 'callback' => z_root() . '/zot', 'spec' => ZOT_REVISION));
	return($x);
}

		
