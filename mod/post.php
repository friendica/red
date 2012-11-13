<?php

/**
 * Zot endpoint
 */


require_once('include/zot.php');
	
function post_post(&$a) {

	$ret = array('result' => false);

	if(array_key_exists('iv',$_REQUEST)) {
		// hush-hush ultra top secret mode
		$data = aes_unencapsulate($_REQUEST,get_config('system','site_prvkey'));
	}
	else {
		$data = $_REQUEST;
	}

	$msgtype = ((array_key_exists('type',$data)) ? $data['type'] : '');

	if(array_key_exists('sender',$data)) {
		$j_sender = json_decode($data['sender']);
	}	

	$hub = zot_gethub($j_sender);
	if(! $hub) {
		// (!!) this will validate the sender
		$result = zot_register_hub($j_sender);
		if((! $result['success']) || (! zot_gethub($j_sender))) {
			$ret['message'] = 'Hub not available.';
			json_return_and_die($ret);
		}
	}

	// TODO: check which hub is primary and take action if mismatched

	if(array_key_exists('recipients',$data))
		$j_recipients = json_decode($data['recipients']);

	if($msgtype === 'refresh') {

		// remote channel info (such as permissions or photo or something)
		// has been updated. Grab a fresh copy and sync it.

		if($j_recipients) {

			// This would be a permissions update, typically for one connection

			foreach($j_recipients as $recip) {	
				$r = q("select channel.*,xchan.* from channel 
					left join xchan on channel_hash = xchan_hash
					where channel_guid = '%s' and channel_guid_sig = '%s' limit 1",
					dbesc($recip->guid),
					dbesc($recip->guid_sig)
				);

				$x = zot_refresh(array(
						'xchan_guid'     => $j_sender->guid, 
						'xchan_guid_sig' => $j_sender->guid_sig,
						'hubloc_url'     => $j_sender->url
				),$r[0]);
			}
		}
		else {

			// system wide refresh
				
			$x = zot_refresh(array(
				'xchan_guid'     => $j_sender->guid, 
				'xchan_guid_sig' => $j_sender->guid_sig,
				'hubloc_url'     => $j_sender->url
			),null);
		}
		$ret['result'] = true;
		json_return_and_die($ret);
	}

	if($msgtype === 'notify') {


		// add to receive queue
		// qreceive_add($data);

		$ret['result'] = true;
		json_return_and_die($ret);
	}

}


