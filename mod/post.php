<?php

/**
 * Zot endpoint
 */


require_once('include/zot.php');
	
function post_post(&$a) {

	$ret = array('result' => false, 'message' => '');

	$msgtype = ((x($_REQUEST,'type')) ? $_REQUEST['type'] : '');

	if($msgtype === 'notify') {

		// add to receive queue
		// qreceive_add($_REQUEST);

		$ret['result'] = true;
		json_return_and_die($ret);
	}

}


