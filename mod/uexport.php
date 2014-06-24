<?php

function uexport_init(&$a) {
	if(! local_user())
		killme();

	$channel = $a->get_channel();

	require_once('include/identity.php');

	header('content-type: application/octet_stream');
	header('content-disposition: attachment; filename="' . $channel['channel_address'] . '.json"' );


	if(argc() > 1 && argv(1) === 'basic') {
		echo json_encode(identity_basic_export(local_user()));
		killme();
	}

	if(argc() > 1 && argv(1) === 'complete') {
		echo json_encode('not yet implemented');
		killme();
	}
	
}