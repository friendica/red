<?php /** @file */


function format_notification($item) {

	$ret = '';

// return array();


	require_once('include/conversation.php');

	// Call localize_item with the "brief" flag to get a one line status for activities. 
	// This should set $item['localized'] to indicate we have a brief summary.

	localize_item($item);

	if($item_localize) {
		$itemem_text = $item['localize'];
	}
	else {
		$itemem_text = (($item['item_flags'] & ITEM_THREAD_TOP)
			? t('created a new post')
			: sprintf( t('commented on %s\'s post'), $item['owner']['xchan_name']));
	}

	// convert this logic into a json array just like the system notifications

	return array(
		'notify_link' => $item['llink'], 
		'name' => $item['author']['xchan_name'],
		'url' => $item['author']['xchan_url'],
		'photo' => $item['author']['xchan_photo_s'],
		'when' => relative_date($item['created']), 
		'class' => (($item['item_flags'] & ITEM_UNSEEN) ? 'notify-unseen' : 'notify-seen'), 
		'message' => strip_tags(bbcode($itemem_text))
	);

}

