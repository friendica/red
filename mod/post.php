<?php

/**
 * Zot endpoint
 */


require_once('include/zot.php');
	
function post_post(&$a) {

	$ret = array('result' => false, 'message' => '');

	$msgtype = ((x($_REQUEST,'type')) ? $_REQUEST['type'] : '');

	$hub = zot_gethub($_REQUEST);
	if(! $hub) {
		$result = zot_register_hub($_REQUEST);
		if((! $result) || (! zot_gethub($_REQUEST))) {
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


