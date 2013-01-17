<?php

function uexport_init(&$a) {
	if(! local_user())
		killme();

	require_once('include/identity.php');

	if(argc() > 1 && argv(1) === 'basic')
		json_return_and_die(identity_basic_export(local_user()));

	if(argc() > 1 && argv(1) === 'complete')
		json_return_and_die('not yet implemented');
	
}