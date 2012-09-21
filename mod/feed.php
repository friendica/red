<?php


require_once('include/items.php');

function feed_init(&$a) {

	$params = array();

	$params['begin']     = ((x($_REQUEST,'date_begin')) ? $_REQUEST['date_begin']  : '0000-00-00 00:00:00');
	$params['end']       = ((x($_REQUEST,'date_end'))   ? $_REQUEST['date_end']    : '');
	$params['type']      = ((stristr(argv(0),'json'))   ? 'json'                   : 'xml');

	$channel = '';
	if(argc() > 1) {
		$r = q("select * from entity where entity_address = '%s' limit 1",
			dbesc(argv(1))
		);
		if(!($r && count($r)))
			killme();

		$channel = $r[0];

		// check site and channel permissions

		if(!($channel['entity_r_stream'] & PERMS_PUBLIC))
			killme();

		if((intval(get_config('system','block_public'))) && (! get_account_id()))
			killme();
 
		logger('mod_feed: public feed request from ' . $_SERVER['REMOTE_ADDR'] . ' for ' . $channel['entity_address']);
		echo get_public_feed($channel,$params);
		killme();
	}

}


