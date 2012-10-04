<?php

function manage_content(&$a) {

	if(! get_account_id()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	require_once('include/security.php');

	$change_channel = ((argc() > 1) ? intval(argv(1)) : 0);

	if($change_channel) {
		$r = change_channel($change_channel);

		if($r && $r['channel_startpage'])
			goaway(z_root() . '/' . $r['channel_startpage']);
		goaway(z_root());
	}

	$channels = null;

	if(local_user()) {
		$r = q("select channel.*, contact.* from channel left join contact on channel.channel_id = contact.uid 
			where channel.channel_account_id = %d and contact.self = 1",
			intval(get_account_id())
		);

		if($r && count($r)) {
			$channels = $r;
			for($x = 0; $x < count($channels); $x ++)
				$channels[$x]['link'] = 'manage/' . intval($channels[$x]['channel_id']);
		}
	}

	$links = array(
		array( 'zchannel', t('Create a new channel'), t('New Channel'))
	);


	$o = replace_macros(get_markup_template('channels.tpl'), array(
		'$header'       => t('Channel Manager'),
		'$desc'         => t('Attach to one of your channels by selecting it.'),
		'$links'        => $links,
		'$all_channels' => $channels,
	));


	return $o;

}
