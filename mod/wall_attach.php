<?php

require_once('include/attach.php');
require_once('include/datetime.php');

function wall_attach_post(&$a) {

	// Figure out who owns the page and if they allow attachments

	if(argc() > 1) {
		$nick = argv(1);
		$r = q("SELECT channel.* from channel where channel_address = '%s' limit 1",
			dbesc($nick)
		);
		if(! $r)
			killme();
		$channel = $r[0];

	}

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
