<?php


function format_notification($item) {

	$ret = '';

return array();


	require_once('include/conversation.php');

	// Call localize_item with the "brief" flag to get a one line status for activities. 
	// This should set $item['localized'] to indicate we have a brief summary.

	localize_item($item);

// FIXME - we may need the parent

	if(! $item['localize']) {
		$itemem_text = (($item['item_flags'] & ITEM_THREAD_TOP)
			? sprintf( t("%s created a new post"), $item['author']['xchan_name'])
			: sprintf( t("%s commented on %s's post"), $item['author']['xchan_name'], $item['pname']));
	}
	else
		$itemem_text = $item['localize'];

	// convert this logic into a json array just like the system notifications

	return array(
		'notify_link' => $a->get_baseurl() . '/notify/view/' . $zz['id'], 
		'name' => $zz['name'],
		'url' => $zz['url'],
		'photo' => $zz['photo'],
		'when' => relative_date($zz['date']), 
		'class' => (($zz['seen']) ? 'notify-seen' : 'notify-unseen'), 
		'message' => strip_tags(bbcode($zz['msg']))
	);

}

