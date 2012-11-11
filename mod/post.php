<?php

/**
 * Zot endpoint
 */


require_once('include/zot.php');
	
function post_post(&$a) {

	$ret = array('result' => false, 'message' => '');

	$msgtype = ((array_key_exists('type',$_REQUEST)) ? $_REQUEST['type'] : '');

	if(array_key_exists('sender',$_REQUEST)) {
		$j_sender = json_decode($_REQUEST['sender']);
	}	

	$hub = zot_gethub($j_sender);
	if(! $hub) {
		$result = zot_register_hub($j_sender);
		if((! $result['success']) || (! zot_gethub($j_sender))) {
			$ret['message'] = 'Hub not available.';
			json_return_and_die($ret);
		}
	}

	// check which hub is primary and take action if mismatched


	if($msgtype === 'refresh') {

		// Need to pass the recipient in the message

		// look up recipient

		// format args
		// $r = zot_refresh($them,$channel);

		return;

	}

	if($msgtype === 'notify') {


		// add to receive queue
		// qreceive_add($_REQUEST);

		$ret['result'] = true;
		json_return_and_die($ret);
	}

}


