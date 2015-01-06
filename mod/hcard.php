<?php

function hcard_init(&$a) {

   if(argc() > 1)
        $which = argv(1);
    else {
        notice( t('Requested profile is not available.') . EOL );
        $a->error = 404;
        return;
    }

    $profile = '';
    $channel = $a->get_channel();

    if((local_user()) && (argc() > 2) && (argv(2) === 'view')) {
        $which = $channel['channel_address'];
        $profile = argv(1);
        $r = q("select profile_guid from profile where id = %d and uid = %d limit 1",
            intval($profile),
            intval(local_user())
        );
        if(! $r)
            $profile = '';
        $profile = $r[0]['profile_guid'];
    }

    $a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . $a->get_baseurl() . '/feed/' . $which .'" />' . "\r\n" ;

    if(! $profile) {
        $x = q("select channel_id as profile_uid from channel where channel_address = '%s' limit 1",
            dbesc(argv(1))
        );
        if($x) {
            $a->profile = $x[0];
        }
    }

	profile_load($a,$which,$profile);


}


function hcard_content(&$a) {

	require_once('include/widgets.php');
	return widget_profile(array());



}


