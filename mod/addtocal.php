<?php /** @file */


function addtocal_init(&$a) {

	if(! local_user())
		return;

	if(argc() > 1) {
		$post_id = intval(argv(1));

		$r = q("select * from item where id = %d and uid = %d limit 1",
			intval($post_id),
			intval(local_user())
		);

		if(! $r)
			return;
	}

	$arr = $r[0];
	$channel = $a->get_channel();

	if(! $channel)
		return;

	// for events, extract the event info and create an event linked to an item 

	if((x($arr,'obj_type')) && (activity_match($arr['obj_type'],ACTIVITY_OBJ_EVENT))) {

		require_once('include/event.php');
		$ev = bbtoevent($arr['body']);

		if(x($ev,'description') && x($ev,'start')) {
			$ev['event_xchan'] = $arr['author_xchan'];
			$ev['uid']         = $channel['channel_id'];
			$ev['account']     = $channel['channel_account_id'];
			$ev['edited']      = $arr['edited'];
			$ev['mid']         = $arr['mid'];
			$ev['private']     = $arr['item_private'];

			// is this an edit?

			$r = q("SELECT resource_id FROM item where mid = '%s' and uid = %d and resource_type = 'event' limit 1",
				dbesc($arr['mid']),
				intval($channel['channel_id'])
			);
			if($r) {
				$ev['event_hash'] = $r[0]['resource_id'];
			}

			$xyz = event_store($ev);

		}
	}
}