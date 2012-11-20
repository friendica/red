<?php

/**
 * Zot endpoint
 */


require_once('include/zot.php');
	
function post_post(&$a) {

	logger('mod_zot: ' . print_r($_REQUEST,true), LOGGER_DEBUG);

	$ret = array('result' => false);

	$data = json_decode($_REQUEST['data'],true);

	logger('mod_zot: data: ' . print_r($data,true), LOGGER_DATA);

	if(array_key_exists('iv',$data)) {
		$data = aes_unencapsulate($data,get_config('system','prvkey'));
		logger('mod_zot: decrypt1: ' . $data);
		$data = json_decode($data,true);
	}

	logger('mod_zot: decoded data: ' . print_r($data,true), LOGGER_DATA);

	$msgtype = ((array_key_exists('type',$data)) ? $data['type'] : '');


	if($msgtype === 'pickup') {

		if((! $data['secret']) || (! $data['secret_sig'])) {
			$ret['message'] = 'no verification signature';
			logger('mod_zot: pickup: ' . $ret['message']);
			json_return_and_die($ret);
		}
		$r = q("select hubloc_sitekey from hubloc where hubloc_url = '%s' and hubloc_callback = '%s' and hubloc_sitekey != '' limit 1",
			dbesc($data['url']),
			dbesc($data['callback'])
		);
		if(! $r) {
			$ret['message'] = 'site not found';
			logger('mod_zot: pickup: ' . $ret['message']);
			json_return_and_die($ret);
		}
		// verify the url_sig
		$sitekey = $r[0]['hubloc_sitekey'];
		logger('sitekey: ' . $sitekey);

		if(! rsa_verify($data['callback'],base64url_decode($data['callback_sig']),$sitekey)) {
			$ret['message'] = 'possible site forgery';
			logger('mod_zot: pickup: ' . $ret['message']);
			json_return_and_die($ret);
		}

		if(! rsa_verify($data['secret'],base64url_decode($data['secret_sig']),$sitekey)) {
			$ret['message'] = 'secret validation failed';
			logger('mod_zot: pickup: ' . $ret['message']);
			json_return_and_die($ret);
		}

		// If we made it to here, we've got a valid pickup. Grab everything for this host and send it.

		$r = q("select outq_posturl from outq where outq_hash = '%s' and outq_posturl = '%s' limit 1",
			dbesc($data['secret']),
			dbesc($data['callback'])
		);
		if(! $r) {
			$ret['message'] = 'nothing to pick up';
			logger('mod_zot: pickup: ' . $ret['message']);
			json_return_and_die($ret);
		}

		$r = q("select * from outq where outq_posturl = '%s'",
			dbesc($data['callback'])
		);
		if($r) {
			$ret['success'] = true;
			$ret['pickup'] = array();
			foreach($r as $rr) {
				$ret['pickup'][] = array('notify' => $rr['outq_notify'],'message' => $rr['outq_msg']);

				$x = q("delete from outq where outq_hash = '%s' limit 1",
					dbesc($rr['outq_hash'])
				);
			}
		}
		$encrypted = aes_encapsulate(json_encode($ret),$sitekey);
		json_return_and_die($encrypted);
	}



	if(array_key_exists('sender',$data)) {
		$sender = $data['sender'];
	}	

	$hub = zot_gethub($sender);
	if(! $hub) {
		// (!!) this will validate the sender
		$result = zot_register_hub($sender);
		if((! $result['success']) || (! zot_gethub($sender))) {
			$ret['message'] = 'Hub not available.';
			logger('mod_zot: no hub');
			json_return_and_die($ret);
		}
	}

	// TODO: check which hub is primary and take action if mismatched

	if(array_key_exists('recipients',$data))
		$recipients = $data['recipients'];

	if($msgtype === 'refresh') {

		// remote channel info (such as permissions or photo or something)
		// has been updated. Grab a fresh copy and sync it.

		if($recipients) {

			// This would be a permissions update, typically for one connection

			foreach($recipients as $recip) {	
				$r = q("select channel.*,xchan.* from channel 
					left join xchan on channel_hash = xchan_hash
					where channel_guid = '%s' and channel_guid_sig = '%s' limit 1",
					dbesc($recip['guid']),
					dbesc($recip['guid_sig'])
				);

				$x = zot_refresh(array(
						'xchan_guid'     => $sender['guid'], 
						'xchan_guid_sig' => $sender['guid_sig'],
						'hubloc_url'     => $sender['url']
				),$r[0]);
			}
		}
		else {

			// system wide refresh
				
			$x = zot_refresh(array(
				'xchan_guid'     => $sender['guid'], 
				'xchan_guid_sig' => $sender['guid_sig'],
				'hubloc_url'     => $sender['url']
			),null);
		}
		$ret['result'] = true;
		json_return_and_die($ret);
	}

	if($msgtype === 'notify') {
		$async = get_config('system','queued_fetch');

		
		if($async) {
			// add to receive queue
			// qreceive_add($data);
		}
		else {
			$x = zot_fetch($data);
		}

		$ret['result'] = true;
		json_return_and_die($ret);

	}

}


