<?php /** @file */

require_once('include/chat.php');

function chat_init(&$a) {

	$which = null;
	if(argc() > 1)
		$which = argv(1);
	if(! $which) {
		if(local_user()) {
			$channel = $a->get_channel();
			if($channel && $channel['channel_address'])
			$which = $channel['channel_address'];
		}
	}
	if(! $which) {
		notice( t('You must be logged in to see this page.') . EOL );
		return;
	}

	$profile = 0;
	$channel = $a->get_channel();

	if((local_user()) && (argc() > 2) && (argv(2) === 'view')) {
		$which = $channel['channel_address'];
		$profile = argv(1);		
	}

	$a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . $a->get_baseurl() . '/feed/' . $which .'" />' . "\r\n" ;

	// Run profile_load() here to make sure the theme is set before
	// we start loading content

	profile_load($a,$which,$profile);

}


function chat_content(&$a) {

	$observer = get_observer_hash();
	if(! $observer) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	if(! perm_is_allowed($a->profile['profile_uid'],$observer,'chat')) {
		notice( t('Permission denied.') . EOL);
		return;
	}
	
	if((argc() > 3) && intval(argv(2)) && (argv(3) === 'leave')) {
		chatroom_leave($observer,$room_id,$_SERVER['REMOTE_ADDR']);
		goaway(z_root() . '/channel/' . argv(1));
	}


	if(argc() > 2 && intval(argv(2))) {
		$room_id = intval(argv(2));
		$x = chatroom_enter($observer,$room_id,'online',$_SERVER['REMOTE_ADDR']);
		if(! $x)
			return;
		$o = replace_macros(get_markup_template('chat.tpl'),array(
			'$room_id' => $room_id,
			'$submit' => t('Submit')
		));
		return $o;
	}
	require_once('include/widgets.php');

	return widget_chatroom_list(array());

}