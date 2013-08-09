<?php

function webpages_content(&$a) {

	if(argc() > 1)
		$which = argv(1);
	else {
		notice( t('Requested profile is not available.') . EOL );
		$a->error = 404;
		return;
	}

	$profile = 0;
	$channel = $a->get_channel();

	if((local_user()) && (argc() > 2) && (argv(2) === 'view')) {
		$which = $channel['channel_address'];
		$profile = argv(1);		
	}

	profile_load($a,$which,$profile);


        $r = q("select channel_id from channel where channel_address = '%s'",
                dbesc($which)
                );
               if($r) {
                $owner = intval($r[0]['channel_id']);
	}
// We can do better, but since editing only works for local users and all posts are webpages, return anyone else for now.

        $observer = $a->get_observer();
        $ob_hash = (($observer) ? $observer['xchan_hash'] : '');

        $perms = get_all_perms($owner,$ob_hash);

        if(! $perms['write_pages']) {
                notice( t('Permission denied.') . EOL);
                return;
        }

// Create a status editor (for now - we'll need a WYSIWYG eventually) to create pages
require_once ('include/conversation.php');
		$x = array(
			'webpage' => 1,
			'is_owner' => true,
			'nickname' => $a->profile['channel_address'],
			'lockstate' => (($group || $cid || $channel['channel_allow_cid'] || $channel['channel_allow_gid'] || $channel['channel_deny_cid'] || $channel['channel_deny_gid']) ? 'lock' : 'unlock'),
			'bang' => (($group || $cid) ? '!' : ''),
			'visitor' => 'block',
			'profile_uid' => intval($owner),
		);

		$o .= status_editor($a,$x);

//Get a list of webpages.  We can't display all them because endless scroll makes that unusable, so just list titles and an edit link.
// FIXME - we should sort these results, but it's not obvious what order yet.  Alphabetical?  Created order?

$r = q("select * from item_id where uid = %d and service = 'WEBPAGE'",
	intval($owner)
);

		$pages = null;

		if($r) {
			$pages = array();
			foreach($r as $rr) {
				$pages[$rr['iid']][] = array('url' => $rr['iid'],'title' => $rr['sid']);
			} 
		}


//Build the base URL for edit links
		$url = z_root() . "/editwebpage/" . $which; 
// This isn't pretty, but it works.  Until I figure out what to do with the UI, it's Good Enough(TM).
       return $o . replace_macros(get_markup_template("webpagelist.tpl"), array(
		'$baseurl' => $url,
		'$edit' => t('Edit'),
		'$pages' => $pages,
		'$channel' => $which,
		'$view' => t('View'),
	
        ));
    

}
