<?php

require_once('include/attach.php');
require_once('include/identity.php');

function wall_attach_post(&$a) {

	if(argc() > 1)
		$channel = get_channel_by_nick(argv(1));
	else
		killme();

	$r = attach_store($channel,get_observer_hash());

	if(! $r['success']) {
		notice( $r['message'] . EOL);
		killme();
	}

	echo  "\n\n" . '[attachment]' . $r['data']['hash'] . ',' . $r['data']['revision'] . '[/attachment]' . "\n";
	killme();

}
