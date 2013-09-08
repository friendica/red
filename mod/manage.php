<?php

function manage_content(&$a) {

	if(! get_account_id()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	require_once('include/security.php');

	$change_channel = ((argc() > 1) ? intval(argv(1)) : 0);

	if((argc() > 2) && (argv(2) === 'default')) {
		$r = q("select channel_id from channel where channel_id = %d and channel_account_id = %d limit 1",
			intval($change_channel),
			intval(get_account_id())
		);
		if($r) {
			q("update account set account_default_channel = %d where account_id = %d limit 1",
				intval($change_channel),
				intval(get_account_id())
			);
		}
		goaway(z_root() . '/manage');
	}

	if($change_channel) {
		$r = change_channel($change_channel);

		if($r && $r['channel_startpage'])
			goaway(z_root() . '/' . $r['channel_startpage']);
		goaway(z_root());
	}

	$channels = null;

	if(local_user()) {
		$r = q("select channel.*, xchan.* from channel left join xchan on channel.channel_hash = xchan.xchan_hash where channel.channel_account_id = %d order by channel_name ",
			intval(get_account_id())
		);

		$selected_channel = null;
		$account = get_app()->get_account();

		if($r && count($r)) {
			$channels = $r;
			for($x = 0; $x < count($channels); $x ++) {
				$channels[$x]['link'] = 'manage/' . intval($channels[$x]['channel_id']);
				if($channels[$x]['channel_id'] == local_user())
					$selected_channel = $channels[$x];
				$channels[$x]['default'] = (($channels[$x]['channel_id'] == $account['account_default_channel']) ? "1" : ''); 
				$channels[$x]['default_links'] = '1';
			}
		}
		
	    $r = q("select count(channel_id) as total from channel where channel_account_id = %d ",
		intval($account)
		);
		$limit = service_class_fetch(local_user(),'total_identities');
		if($limit !== false) {
			$channel_usage_message = sprintf( t("You have created %1$.0f of %2$.0f allowed channels."), $r[0]['total'], $limit);
		}
		else {
			$channel_usage_message = '';
 		}
	}

	$links = array(
		array( 'new_channel', t('Create a new channel'), t('Create a new channel'))
	);


	$o = replace_macros(get_markup_template('channels.tpl'), array(
		'$header'           => t('Channel Manager'),
		'$msg_selected'     => t('Current Channel'),
		'$selected'         => $selected_channel,
		'$desc'             => t('Attach to one of your channels by selecting it.'),
		'$msg_default'      => t('Default Channel'),
		'$msg_make_default' => t('Make Default'),
		'$links'            => $links,
		'$all_channels'     => $channels,
		'$channel_usage_message' => $channel_usage_message,
	));


	return $o;

}
