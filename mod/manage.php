<?php

function manage_content(&$a) {

	if(! get_account_id()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$change_channel = ((argc() > 1) ? intval(argv(1)) : 0);
	if($change_channel) {
		$r = q("select * from channel where channel_id = %d and channel_account_id = %d limit 1",
			intval($change_channel),
			intval(get_account_id())
		);
		if($r && count($r)) {
			$_SESSION['uid'] = intval($r[0]['channel_id']);
			get_app()->set_channel($r[0]);
			$_SESSION['theme'] = $r[0]['channel_theme'];
			date_default_timezone_set($r[0]['channel_timezone']);
		}
		if($r[0]['channel_startpage'])
			goaway(z_root() . '/' . $r[0]['channel_startpage']);
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
