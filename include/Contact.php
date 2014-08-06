<?php /** @file */



function rconnect_url($channel_id,$xchan) {

	if(! $xchan)
		return '';

	$r = q("select abook_id from abook where abook_channel = %d and abook_xchan = '%s' limit 1",
		intval($channel_id),
		dbesc($xchan)
	);

	if($r)
		return '';

	$r = q("select * from xchan where xchan_hash = '%s' limit 1",
		dbesc($xchan)
	);

	if(($r) && ($r[0]['xchan_follow']))
		return $r[0]['xchan_follow'];

	$r = q("select hubloc_url from hubloc where hubloc_hash = '%s' and ( hubloc_flags & %d ) limit 1",
		dbesc($xchan),
		intval(HUBLOC_FLAGS_PRIMARY)
	);

	if($r)
		return $r[0]['hubloc_url'] . '/follow?f=&url=%s';
	return '';

}

function abook_connections($channel_id, $sql_conditions = '') {
	$r = q("select * from abook left join xchan on abook_xchan = xchan_hash where abook_channel = %d
		and not ( abook_flags & %d ) $sql_conditions",
		intval($channel_id),
		intval(ABOOK_FLAG_SELF)
	);
	return(($r) ? $r : array());
}	

function abook_self($channel_id) {
	$r = q("select * from abook left join xchan on abook_xchan = xchan_hash where abook_channel = %d
		and ( abook_flags & %d ) limit 1",
		intval($channel_id),
		intval(ABOOK_FLAG_SELF)
	);
	return(($r) ? $r[0] : array());
}	

function channelx_by_nick($nick) {
	$r = q("SELECT * FROM channel left join xchan on channel_hash = xchan_hash WHERE channel_address = '%s'  and not ( channel_pageflags & %d ) LIMIT 1",
		dbesc($nick),
		intval(PAGE_REMOVED)
	);
	return(($r) ? $r[0] : false);
}

function channelx_by_hash($hash) {
	$r = q("SELECT * FROM channel left join xchan on channel_hash = xchan_hash WHERE channel_hash = '%s'  and not ( channel_pageflags & %d ) LIMIT 1",
		dbesc($hash),
		intval(PAGE_REMOVED)
	);
	return(($r) ? $r[0] : false);
}

function channelx_by_n($id) {
	$r = q("SELECT * FROM channel left join xchan on channel_hash = xchan_hash WHERE channel_id = %d  and not ( channel_pageflags & %d ) LIMIT 1",
		dbesc($id),
		intval(PAGE_REMOVED)
	);
	return(($r) ? $r[0] : false);
}


function vcard_from_xchan($xchan, $observer = null, $mode = '') {

	$a = get_app();

	if(! $xchan) {
		if($a->poi) {
			$xchan = $a->poi;
		}
		elseif(is_array($a->profile) && $a->profile['channel_hash']) {
			$r = q("select * from xchan where xchan_hash = '%s' limit 1",
				dbesc($a->profile['channel_hash'])
			);
			if($r)
				$xchan = $r[0];
		}
	}

	if(! $xchan)
		return;

// FIXME - show connect button to observer if appropriate
	$connect = false;
	if(local_user()) {
		$r = q("select * from abook where abook_xchan = '%s' and abook_channel = %d limit 1",
			dbesc($xchan['xchan_hash']),
			intval(local_user())
		);
		if(! $r)
			$connect = t('Connect');
	}

	if(array_key_exists('channel_id',$xchan))
		$a->profile_uid = $xchan['channel_id'];

	$url = (($observer) 
		? z_root() . '/magic?f=&dest=' . $xchan['xchan_url'] . '&addr=' . $xchan['xchan_addr'] 
		: $xchan['xchan_url']
	);
					
	return replace_macros(get_markup_template('xchan_vcard.tpl'),array(
		'$name'    => $xchan['xchan_name'],
		'$photo'   => ((is_array($a->profile) && array_key_exists('photo',$a->profile)) ? $a->profile['photo'] : $xchan['xchan_photo_l']),
		'$follow'  => $xchan['xchan_addr'],
		'$connect' => $connect,
		'$newwin'  => (($mode === 'chanview') ? t('New window') : ''),
		'$newtit'  => t('Open the selected location in a different window or browser tab'),
		'$url'     => $url,
	));
}

function abook_toggle_flag($abook,$flag) {

	$r = q("UPDATE abook set abook_flags = (abook_flags ^ %d) where abook_id = %d and abook_channel = %d limit 1",
		intval($flag),
		intval($abook['abook_id']),
		intval($abook['abook_channel'])
	);

	// if unsetting the archive bit, update the timestamps so we'll try to connect for an additional 30 days. 

	if(($flag === ABOOK_FLAG_ARCHIVED) && ($abook['abook_flags'] & ABOOK_FLAG_ARCHIVED)) {
		$r = q("update abook set abook_connected = '%s', abook_updated = '%s' 
			where abook_id = %d and abook_channel = %d limit 1",
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			intval($abook['abook_id']),
			intval($abook['abook_channel'])
		);
	}

	$a = get_app();
	if($a->data['abook'])
		$a->data['abook']['abook_flags'] = $a->data['abook']['abook_flags'] ^ $flag;
	return $r;

}


// Included here for completeness, but this is a very dangerous operation.
// It is the caller's responsibility to confirm the requestor's intent and
// authorisation to do this.

function user_remove($uid) {

}

function account_remove($account_id,$local = true) {

	logger('account_remove: ' . $account_id);

	if(! intval($account_id)) {
		logger('account_remove: no account.');
		return false;
	}

	// Don't let anybody nuke the only admin account.

	$r = q("select account_id from account where (account_roles & %d)",
		intval(ACCOUNT_ROLE_ADMIN)
	);

	if($r !== false && count($r) == 1 && $r[0]['account_id'] == $account_id) {
		logger("Unable to remove the only remaining admin account");
		return false;
	}

	$r = q("select * from account where account_id = %d limit 1",
		intval($account_id)
	);

	if(! $r) {
		logger('account_remove: No account with id: ' . $account_id);
		return false;
	}

	$x = q("select channel_id from channel where channel_account_id = %d",
		intval($account_id)
	);
	if($x) {
		foreach($x as $xx) {
			channel_remove($xx['channel_id'],$local);
		}
	}

	$r = q("delete from account where account_id = %d limit 1",
		intval($account_id)
	);

	return $r;

}

function channel_remove($channel_id, $local = true) {

	if(! $channel_id)
		return;
	$a = get_app();
	logger('Removing channel: ' . $channel_id);
	logger('channel_remove: local only: ' . intval($local));

	$r = q("select * from channel where channel_id = %d limit 1", intval($channel_id));
	if(! $r) {
		logger('channel_remove: channel not found: ' . $channel_id);
		return;
	}

	$channel = $r[0];

	call_hooks('channel_remove',$r[0]);
	
	if(! $local) {

		$r = q("update channel set channel_deleted = '%s', channel_pageflags = (channel_pageflags | %d), channel_r_stream = 0, channel_r_profile = 0,
			channel_r_photos = 0, channel_r_abook = 0, channel_w_stream = 0, channel_w_wall = 0, channel_w_tagwall = 0,
			channel_w_comment = 0, channel_w_mail = 0, channel_w_photos = 0, channel_w_chat = 0, channel_a_delegate = 0,
			channel_r_storage = 0, channel_w_storage = 0, channel_r_pages = 0, channel_w_pages = 0, channel_a_republish = 0 
			where channel_id = %d limit 1",
			dbesc(datetime_convert()),
			intval(PAGE_REMOVED),
			intval($channel_id)
		);

		$r = q("update hubloc set hubloc_flags = hubloc_flags | %d where hubloc_hash = '%s'",
			intval(HUBLOC_FLAGS_DELETED),
			dbesc($channel['channel_hash'])
		);

		$r = q("update xchan set xchan_flags = xchan_flags | %d where xchan_hash = '%s'",
			intval(XCHAN_FLAGS_DELETED),
			dbesc($channel['channel_hash'])
		);

		proc_run('php','include/notifier.php','purge_all',$channel_id);


	}

	q("DELETE FROM `groups` WHERE `uid` = %d", intval($channel_id));
	q("DELETE FROM `group_member` WHERE `uid` = %d", intval($channel_id));
	q("DELETE FROM `event` WHERE `uid` = %d", intval($channel_id));
	q("DELETE FROM `item` WHERE `uid` = %d", intval($channel_id));
	q("DELETE FROM `item_id` WHERE `uid` = %d", intval($channel_id));
	q("DELETE FROM `mail` WHERE `channel_id` = %d", intval($channel_id));
	q("DELETE FROM `notify` WHERE `uid` = %d", intval($channel_id));
	q("DELETE FROM `photo` WHERE `uid` = %d", intval($channel_id));
	q("DELETE FROM `attach` WHERE `uid` = %d", intval($channel_id));
	q("DELETE FROM `profile` WHERE `uid` = %d", intval($channel_id));
	q("DELETE FROM `pconfig` WHERE `uid` = %d", intval($channel_id));
	q("DELETE FROM `spam` WHERE `uid` = %d", intval($channel_id));


	q("delete from abook where abook_xchan = '%s' and abook_flags & %d limit 1",
		dbesc($channel['channel_hash']),
		dbesc(ABOOK_FLAG_SELF)
	);

	$r = q("update channel set channel_deleted = '%s', channel_pageflags = (channel_pageflags | %d) where channel_id = %d limit 1",
		dbesc(datetime_convert()),
		intval(PAGE_REMOVED),
		intval($channel_id)
	);

	$r = q("update hubloc set hubloc_flags = hubloc_flags | %d where hubloc_hash = '%s' and hubloc_url = '%s' ",
		intval(HUBLOC_FLAGS_DELETED),
		dbesc($channel['channel_hash']),
		dbesc(z_root())
	);

	$r = q("update xchan set xchan_flags = xchan_flags | %d where xchan_hash = '%s' ",
		intval(XCHAN_FLAGS_DELETED),
		dbesc($channel['channel_hash'])
	);


	proc_run('php','include/directory.php',$channel_id);

	if($channel_id == local_user()) {
		unset($_SESSION['authenticated']);
		unset($_SESSION['uid']);
		goaway($a->get_baseurl());
	}

}

/**
 * mark any hubs "offline" that haven't been heard from in more than 30 days
 * Allow them to redeem themselves if they come back later.
 * Then go through all those that are newly marked and see if any other hubs
 * are attached to the controlling xchan that are still alive.
 * If not, they're dead (although they could come back some day).
 */


function mark_orphan_hubsxchans() {

	$dirmode = intval(get_config('system','directory_mode'));
	if($dirmode == DIRECTORY_MODE_NORMAL)
		return;

    $r = q("update hubloc set hubloc_status = (hubloc_status | %d) where not (hubloc_status & %d) 
		and hubloc_connected < utc_timestamp() - interval 36 day",
        intval(HUBLOC_OFFLINE),
        intval(HUBLOC_OFFLINE)
    );

	$r = q("select hubloc_id, hubloc_hash from hubloc where (hubloc_status & %d) and not (hubloc_flags & %d)",
		intval(HUBLOC_OFFLINE),
		intval(HUBLOC_FLAGS_ORPHANCHECK)
	);

	if($r) {
		foreach($r as $rr) {

			// see if any other hublocs are still alive for this channel

			$x = q("select * from hubloc where hubloc_hash = '%s' and not (hubloc_status & %d)",
				dbesc($rr['hubloc_hash']),
				intval(HUBLOC_OFFLINE)
			);
			if($x) {

				// yes - if the xchan was marked as an orphan, undo it

				$y = q("update xchan set xchan_flags = (xchan_flags ^ %d) where (xchan_flags & %d) and xchan_hash = '%s' limit 1",
					intval(XCHAN_FLAGS_ORPHAN),
					intval(XCHAN_FLAGS_ORPHAN),
					dbesc($rr['hubloc_hash'])
				);

			}
			else {

				// nope - mark the xchan as an orphan

				$y = q("update xchan set xchan_flags = (xchan_flags | %d) where xchan_hash = '%s' limit 1",
					intval(XCHAN_FLAGS_ORPHAN),
					dbesc($rr['hubloc_hash'])
				);
			}

			// mark that we've checked this entry so we don't need to do it again

			$y = q("update hubloc set hubloc_flags = (hubloc_flags | %d) where hubloc_id = %d limit 1",
				intval(HUBLOC_FLAGS_ORPHANCHECK),
				dbesc($rr['hubloc_id'])
			);
		}
	}

}




function remove_all_xchan_resources($xchan, $channel_id = 0) {

	if(intval($channel_id)) {



	}
	else {

		$dirmode = intval(get_config('system','directory_mode'));


		$r = q("delete from photo where xchan = '%s'",
			dbesc($xchan)
		);
		$r = q("select resource_id, resource_type, uid, id from item where ( author_xchan = '%s' or owner_xchan = '%s' ) ",
			dbesc($xchan),
			dbesc($xchan)
		);
		if($r) {
			foreach($r as $rr) {
				drop_item($rr,false);
			}
		}
		$r = q("delete from event where event_xchan = '%s'",
			dbesc($xchan)
		);
		$r = q("delete from group_member where xchan = '%s'",
			dbesc($xchan)
		);
		$r = q("delete from mail where ( from_xchan = '%s' or to_xchan = '%s' )",
			dbesc($xchan),
			dbesc($xchan)
		);
		$r = q("delete from xlink where ( xlink_xchan = '%s' or xlink_link = '%s' )",
			dbesc($xchan),
			dbesc($xchan)
		);

		$r = q("delete from abook where abook_xchan = '%s'",
			dbesc($xchan)
		);


		if($dirmode === false || $dirmode == DIRECTORY_MODE_NORMAL) {

			$r = q("delete from xchan where xchan_hash = '%s' limit 1",
				dbesc($xchan)
			);
			$r = q("delete from hubloc where hubloc_hash = '%s'",
				dbesc($xchan)
			);

		}
		else {

			// directory servers need to keep the record around for sync purposes - mark it deleted

	        $r = q("update hubloc set hubloc_flags = hubloc_flags | %d where hubloc_hash = '%s'",
    	        intval(HUBLOC_FLAGS_DELETED),
        	    dbesc($xchan)
        	);

        	$r = q("update xchan set xchan_flags = xchan_flags | %d where xchan_hash = '%s'",
            	intval(XCHAN_FLAGS_DELETED),
            	dbesc($xchan)
        	);
		}
	}
}


function contact_remove($channel_id, $abook_id) {

	if((! $channel_id) || (! $abook_id))
		return false;

	$archive = get_pconfig($channel_id, 'system','archive_removed_contacts');
	if($archive) {
		q("update abook set abook_flags = ( abook_flags | %d ) where abook_id = %d and abook_channel = %d limit 1",
			intval(ABOOK_FLAG_ARCHIVED),
			intval($abook_id),
			intval($channel_id)
		);
		return true;
	}

	$r = q("select * from abook where abook_id = %d and abook_channel = %d limit 1",
		intval($abook_id),
		intval($channel_id)
	);

	if(! $r)
		return false;

	$abook = $r[0];

	if($abook['abook_flags'] & ABOOK_FLAG_SELF)
		return false;


	$r = q("select * from item where author_xchan = '%s' and uid = %d",
		dbesc($abook['abook_xchan']),
		intval($channel_id)
	);
	if($r) {
		foreach($r as $rr) {
			drop_item($rr,false);
		}
	}
	
	q("delete from abook where abook_id = %d and abook_channel = %d limit 1",
		intval($abook['abook_id']),
		intval($channel_id)
	);

	$r = q("delete from event where event_xchan = '%s' and uid = %d",
		dbesc($abook['abook_xchan']),
		intval($channel_id)
	);

	$r = q("delete from group_member where xchan = '%s' and uid = %d",
		dbesc($abook['abook_xchan']),
		intval($channel_id)
	);

	$r = q("delete from mail where ( from_xchan = '%s' or to_xchan = '%s' ) and channel_id = %d ",
		dbesc($abook['abook_xchan']),
		dbesc($abook['abook_xchan']),
		intval($channel_id)
	);

	return true;
}



function random_profile() {
	$r = q("select xchan_url from xchan left join hubloc on hubloc_hash = xchan_hash where hubloc_connected > UTC_TIMESTAMP() - interval 30 day order by rand() limit 1");
	if($r)
		return $r[0]['xchan_url'];
	return '';
}


