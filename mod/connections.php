<?php

require_once('include/Contact.php');
require_once('include/socgraph.php');
require_once('include/contact_selectors.php');
require_once('include/group.php');
require_once('include/contact_widgets.php');
require_once('include/zot.php');

function connections_init(&$a) {

	if(! local_user())
		return;

	if((argc() == 2) && intval(argv(1))) {
		$r = q("SELECT abook.*, xchan.* 
			FROM abook left join xchan on abook_xchan = xchan_hash
			WHERE abook_channel = %d and abook_id = %d LIMIT 1",
			intval(local_user()),
			intval(argv(1))
		);
		if($r) {
			$a->data['abook'] = $r[0];
		}
	}

	$channel = $a->get_channel();
	if($channel)
		head_set_icon($channel['xchan_photo_s']);

}

function connections_aside(&$a) {

	if (! local_user())
		return;
	
	if(x($a->data,'abook')) {
		$a->set_widget('vcard',vcard_from_xchan($a->data['abook'],$a->get_observer()));
	}
	else {
		$a->set_widget('follow', follow_widget());
	}

	$a->set_widget('collections', group_side('connections','group',false,0,((array_key_exists('abook',$a->data)) ? $a->data['abook']['abook_xchan'] : '')));

	$a->set_widget('suggest',suggest_widget());
	$a->set_widget('findpeople',findpeople_widget());

}



function connections_post(&$a) {
	
	if(! local_user())
		return;

	$contact_id = intval(argv(1));
	if(! $contact_id)
		return;

	$orig_record = q("SELECT * FROM abook WHERE abook_id = %d AND abook_channel = %d LIMIT 1",
		intval($contact_id),
		intval(local_user())
	);

	if(! $orig_record) {
		notice( t('Could not access contact record.') . EOL);
		goaway($a->get_baseurl(true) . '/connections');
		return; // NOTREACHED
	}

	call_hooks('contact_edit_post', $_POST);

	$profile_id = $_POST['profile-assign'];
	if($profile_id) {
		$r = q("SELECT profile_guid FROM profile WHERE profile_guid = '%s' AND `uid` = %d LIMIT 1",
			dbesc($profile_id),
			intval(local_user())
		);
		if(! count($r)) {
			notice( t('Could not locate selected profile.') . EOL);
			return;
		}
	}

	$hidden = intval($_POST['hidden']);

	$priority = intval($_POST['poll']);
	if($priority > 5 || $priority < 0)
		$priority = 0;

	$closeness = intval($_POST['closeness']);
	if($closeness < 0)
		$closeness = 99;

	$abook_my_perms = 0;

	foreach($_POST as $k => $v) {
		if(strpos($k,'perms_') === 0) {
			$abook_my_perms += $v;
		}
	}			

	$abook_flags = $orig_record[0]['abook_flags'];
	$new_friend = false;

	if(($_REQUEST['pending']) && ($abook_flags & ABOOK_FLAG_PENDING)) {
		$abook_flags = ( $abook_flags ^ ABOOK_FLAG_PENDING );
		$new_friend = true;
	}

	$r = q("UPDATE abook SET abook_profile = '%s', abook_my_perms = %d , abook_closeness = %d, abook_flags = %d
		where abook_id = %d AND abook_channel = %d LIMIT 1",
		dbesc($profile_id),
		intval($abook_my_perms),
		intval($closeness),
		intval($abook_flags),
		intval($contact_id),
		intval(local_user())
	);
	if($r)
		info( t('Connection updated.') . EOL);
	else
		notice( t('Failed to update connection record.') . EOL);

	if((x($a->data,'abook')) && $a->data['abook']['abook_my_perms'] != $abook_my_perms 
		&& (! ($a->data['abook']['abook_flags'] & ABOOK_FLAG_SELF))) {
		proc_run('php', 'include/notifier.php', 'permission_update', $contact_id);
	}

	if($new_friend) {
		$channel = $a->get_channel();		
		$default_group = $channel['channel_default_group'];
		if($default_group) {
			require_once('include/group.php');
			$g = group_rec_byhash(local_user(),$default_group);
			if($g)
				group_add_member(local_user(),'',$a->data['abook_xchan'],$g['id']);
		}



		// Check if settings permit ("post new friend activity" is allowed, and 
		// friends in general or this friend in particular aren't hidden) 
		// and send out a new friend activity
		// TODO

		// pull in a bit of content if there is any to pull in
		proc_run('php','include/onepoll.php',$contact_id);

	}

	// Refresh the structure in memory with the new data

	$r = q("SELECT abook.*, xchan.* 
		FROM abook left join xchan on abook_xchan = xchan_hash
		WHERE abook_channel = %d and abook_id = %d LIMIT 1",
		intval(local_user()),
		intval($contact_id)
	);
	if($r) {
		$a->data['abook'] = $r[0];
	}

	if($new_friend) {
		$arr = array('channel_id' => local_user(), 'abook' => $a->data['abook']);
		call_hooks('accept_follow', $arr);
	}

	connections_clone($a);

	return;

}

function connections_clone(&$a) {

		if(! array_key_exists('abook',$a->data))
			return;
		$clone = $a->data['abook'];

		unset($clone['abook_id']);
		unset($clone['abook_account']);
		unset($clone['abook_channel']);

		build_sync_packet(0 /* use the current local_user */, array('abook' => array($clone)));
}


function connections_content(&$a) {

	$sort_type = 0;
	$o = '';


	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return login();
	}

	if(argc() == 3) {

		$contact_id = intval(argv(1));
		if(! $contact_id)
			return;

		$cmd = argv(2);

		$orig_record = q("SELECT abook.*, xchan.* FROM abook left join xchan on abook_xchan = xchan_hash
			WHERE abook_id = %d AND abook_channel = %d AND NOT ( abook_flags & %d ) and not ( abook_flags & %d ) LIMIT 1",
			intval($contact_id),
			intval(local_user()),
			intval(ABOOK_FLAG_SELF),
			// allow drop even if pending, just duplicate the self query
			intval(($cmd === 'drop') ? ABOOK_FLAG_SELF : ABOOK_FLAG_PENDING)
		);

		if(! count($orig_record)) {
			notice( t('Could not access address book record.') . EOL);
			goaway($a->get_baseurl(true) . '/connections');
		}
		
		if($cmd === 'update') {

			// pull feed and consume it, which should subscribe to the hub.
			proc_run('php',"include/poller.php","$contact_id");
			goaway($a->get_baseurl(true) . '/connections/' . $contact_id);

		}

		if($cmd === 'refresh') {
			if(! zot_refresh($orig_record[0],get_app()->get_channel())) 
				notice( t('Refresh failed - channel is currently unavailable.') );
			goaway($a->get_baseurl(true) . '/connections/' . $contact_id);
		}

		if($cmd === 'block') {
			if(abook_toggle_flag($orig_record[0],ABOOK_FLAG_BLOCKED)) {
				info((($orig_record[0]['abook_flags'] & ABOOK_FLAG_BLOCKED) 
					? t('Channel has been unblocked') 
					: t('Channel has been blocked')) . EOL );
				connections_clone($a);
			}
			else
				notice(t('Unable to set address book parameters.') . EOL);
			goaway($a->get_baseurl(true) . '/connections/' . $contact_id);
		}

		if($cmd === 'ignore') {
			if(abook_toggle_flag($orig_record[0],ABOOK_FLAG_IGNORED)) {
				info((($orig_record[0]['abook_flags'] & ABOOK_FLAG_IGNORED) 
					? t('Channel has been unignored') 
					: t('Channel has been ignored')) . EOL );
				connections_clone($a);
			}
			else
				notice(t('Unable to set address book parameters.') . EOL);
			goaway($a->get_baseurl(true) . '/connections/' . $contact_id);
		}

		if($cmd === 'archive') {
			if(abook_toggle_flag($orig_record[0],ABOOK_FLAG_ARCHIVED)) {
				info((($orig_record[0]['abook_flags'] & ABOOK_FLAG_ARCHIVED) 
					? t('Channel has been unarchived') 
					: t('Channel has been archived')) . EOL );
				connections_clone($a);
			}
			else
				notice(t('Unable to set address book parameters.') . EOL);
			goaway($a->get_baseurl(true) . '/connections/' . $contact_id);
		}

		if($cmd === 'hide') {
			if(abook_toggle_flag($orig_record[0],ABOOK_FLAG_HIDDEN)) {
				info((($orig_record[0]['abook_flags'] & ABOOK_FLAG_HIDDEN) 
					? t('Channel has been unhidden') 
					: t('Channel has been hidden')) . EOL );
				connections_clone($a);
			}
			else
				notice(t('Unable to set address book parameters.') . EOL);
			goaway($a->get_baseurl(true) . '/connections/' . $contact_id);
		}

		// We'll prevent somebody from unapproving a contact.

		if($cmd === 'approve') {
			if($orig_record[0]['abook_flags'] & ABOOK_FLAG_PENDING) {
				if(abook_toggle_flag($orig_record[0],ABOOK_FLAG_PENDING)) {
					info((($orig_record[0]['abook_flags'] & ABOOK_FLAG_PENDING) 
						? t('Channel has been approved') 
						: t('Channel has been unapproved')) . EOL );
					connections_clone($a);
				}
				else
					notice(t('Unable to set address book parameters.') . EOL);
			}
			goaway($a->get_baseurl(true) . '/connections/' . $contact_id);
		}


		if($cmd === 'drop') {

			require_once('include/Contact.php');
// FIXME
//			terminate_friendship($a->get_channel(),$orig_record[0]);

			contact_remove(local_user(), $orig_record[0]['abook_id']);
// FIXME - send to clones
			info( t('Contact has been removed.') . EOL );
			if(x($_SESSION,'return_url'))
				goaway($a->get_baseurl(true) . '/' . $_SESSION['return_url']);
			goaway($a->get_baseurl(true) . '/contacts');

		}
	}

	if((x($a->data,'abook')) && (is_array($a->data['abook']))) {

		$contact_id = $a->data['abook']['abook_id'];
		$contact = $a->data['abook'];


		$tabs = array(

			array(
				'label' => t('View Profile'),
				'url'   => $a->get_baseurl(true) . '/chanview/?f=&cid=' . $contact['abook_id'], 
				'sel'   => '',
				'title' => sprintf( t('View %s\'s profile'), $contact['xchan_name']),
			),

			array(
				'label' => t('Refresh Permissions'),
				'url'   => $a->get_baseurl(true) . '/connections/' . $contact['abook_id'] . '/refresh', 
				'sel'   => '',
				'title' => t('Fetch updated permissions'),
			),

			array(
				'label' => t('Recent Activity'),
				'url'   => $a->get_baseurl(true) . '/network/?f=&cid=' . $contact['abook_id'], 
				'sel'   => '',
				'title' => t('View recent posts and comments'),
			),

			array(
				'label' => (($contact['abook_flags'] & ABOOK_FLAG_BLOCKED) ? t('Unblock') : t('Block')),
				'url'   => $a->get_baseurl(true) . '/connections/' . $contact['abook_id'] . '/block', 
				'sel'   => (($contact['abook_flags'] & ABOOK_FLAG_BLOCKED) ? 'active' : ''),
				'title' => t('Block or Unblock this connection'),
			),

			array(
				'label' => (($contact['abook_flags'] & ABOOK_FLAG_IGNORED) ? t('Unignore') : t('Ignore')),
				'url'   => $a->get_baseurl(true) . '/connections/' . $contact['abook_id'] . '/ignore', 
				'sel'   => (($contact['abook_flags'] & ABOOK_FLAG_IGNORED) ? 'active' : ''),
				'title' => t('Ignore or Unignore this connection'),
			),
			array(
				'label' => (($contact['abook_flags'] & ABOOK_FLAG_ARCHIVED) ? t('Unarchive') : t('Archive')),
				'url'   => $a->get_baseurl(true) . '/connections/' . $contact['abook_id'] . '/archive', 
				'sel'   => (($contact['abook_flags'] & ABOOK_FLAG_ARCHIVED) ? 'active' : ''),
				'title' => t('Archive or Unarchive this connection'),
			),
			array(
				'label' => (($contact['abook_flags'] & ABOOK_FLAG_HIDDEN) ? t('Unhide') : t('Hide')),
				'url'   => $a->get_baseurl(true) . '/connections/' . $contact['abook_id'] . '/hide', 
				'sel'   => (($contact['abook_flags'] & ABOOK_FLAG_HIDDEN) ? 'active' : ''),
				'title' => t('Hide or Unhide this connection'),
			),

			array(
				'label' => t('Delete'),
				'url'   => $a->get_baseurl(true) . '/connections/' . $contact['abook_id'] . '/drop', 
				'sel'   => '',
				'title' => t('Delete this connection'),
			),

		);

		$self = false;

		if(! ($contact['abook_flags'] & ABOOK_FLAG_SELF)) {
			$tab_tpl = get_markup_template('common_tabs.tpl');
			$t = replace_macros($tab_tpl, array('$tabs'=>$tabs));
		}
		else
			$self = true;

		$a->page['htmlhead'] .= replace_macros(get_markup_template('contact_head.tpl'), array(
			'$baseurl' => $a->get_baseurl(true),
			'$editselect' => $editselect
		));

		require_once('include/contact_selectors.php');

		$tpl = get_markup_template("abook_edit.tpl");

		if(feature_enabled(local_user(),'affinity')) {

			$slider_tpl = get_markup_template('contact_slider.tpl');
			$slide = replace_macros($slider_tpl,array(
				'$me' => t('Me'),
				'$val' => (($contact['abook_closeness']) ? $contact['abook_closeness'] : 99),
				'$intimate' => t('Best Friends'),
				'$friends' => t('Friends'),
				'$oldfriends' => t('Former Friends'),
				'$acquaintances' => t('Acquaintances'),
				'$world' => t('Unknown')
			));
		}

		$perms = array();
		$channel = $a->get_channel();

		$global_perms = get_perms();
		$existing = get_all_perms(local_user(),$contact['abook_xchan']); 

		$unapproved = array('pending', t('Approve this connection'), '', t('Accept connection to allow communication'));
		
		foreach($global_perms as $k => $v) {
			$thisperm = (($contact['abook_my_perms'] & $v[1]) ? "1" : '');

			// For auto permissions (when $self is true) we don't want to look at existing
			// permissions because they are enabled for the channel owner

			if((! $self) && ($existing[$k]))
				$thisperm = "1";

			$perms[] = array('perms_' . $k, $v[3], (($contact['abook_their_perms'] & $v[1]) ? "1" : ""),$thisperm, $v[1], (($channel[$v[0]] == PERMS_SPECIFIC) ? '' : '1'), $v[4]);
		}

		$o .= replace_macros($tpl,array(

			'$header'         => (($self) ? t('Automatic Permissions Settings') : sprintf( t('Connections: settings for %s'),$contact['xchan_name'])),
			'$addr'           => $contact['xchan_addr'],
			'$notself'        => (($self) ? '' : '1'),
			'$self'           => (($self) ? '1' : ''),
			'$autolbl'        => t('When receiving a channel introduction, any permissions provided here will be applied to the new connection automatically and the introduction approved. Leave this page if you do not wish to use this feature.'),
			'$viewprof'       => t('View Profile'),
			'$lbl_slider'     => t('Slide to adjust your degree of friendship'),
			'$slide'          => $slide,
			'$tabs'           => $t,
			'$tab_str'        => $tab_str,
			'$is_pending'     => (($contact['abook_flags'] & ABOOK_FLAG_PENDING) ? 1 : ''),
			'$unapproved'     => $unapproved,
			'$inherited'      => t('inherited'),
			'$approve'        => t('Approve this connection'),
			'$noperms'        => (((! $self) && (! $contact['abook_my_perms'])) ? t('Connection has no individual permissions!') : ''),
			'$noperm_desc'    => (((! $self) && (! $contact['abook_my_perms'])) ? t('This may be appropriate based on your <a href="settings">privacy settings</a>, though you may wish to review the "Advanced Permissions".') : ''),
			'$submit'         => t('Submit'),
			'$lbl_vis1'       => t('Profile Visibility'),
			'$lbl_vis2'       => sprintf( t('Please choose the profile you would like to display to %s when viewing your profile securely.'), $contact['xchan_name']),
			'$lbl_info1'      => t('Contact Information / Notes'),
			'$infedit'        => t('Edit contact notes'),
			'$close'          => $contact['abook_closeness'],
			'$them'           => t('Their Settings'),
			'$me'             => t('My Settings'),
			'$perms'          => $perms,
			'$forum'          => t('Forum Members'),
			'$soapbox'        => t('Soapbox'),
			'$full'           => t('Full Sharing'),
			'$cautious'       => t('Cautious Sharing'),
			'$follow'         => t('Follow Only'),
			'$permlbl'        => t('Individual Permissions'),
			'$permnote'       => t('Some permissions may be inherited from your channel <a href="settings">privacy settings</a>, which have higher priority. Changing those inherited settings on this page will have no effect.'),
			'$advanced'       => t('Advanced Permissions'),
			'$quick'          => t('Quick Links'),
			'$common_link'    => $a->get_baseurl(true) . '/common/loc/' . local_user() . '/' . $contact['id'],
			'$all_friends'    => $all_friends,
			'$relation_text'  => $relation_text,
			'$visit'          => sprintf( t('Visit %s\'s profile - %s'),$contact['xchan_name'],$contact['xchan_url']),
			'$blockunblock'   => t('Block/Unblock contact'),
			'$ignorecont'     => t('Ignore contact'),
			'$lblcrepair'     => t("Repair URL settings"),
			'$lblrecent'      => t('View conversations'),
			'$lblsuggest'     => $lblsuggest,
			'$delete'         => t('Delete contact'),
			'$poll_interval'  => contact_poll_interval($contact['priority'],(! $poll_enabled)),
			'$poll_enabled'   => $poll_enabled,
			'$lastupdtext'    => t('Last update:'),
			'$lost_contact'   => $lost_contact,
			'$updpub'         => t('Update public posts'),
			'$last_update'    => $last_update,
			'$udnow'          => t('Update now'),
			'$profile_select' => contact_profile_assign($contact['abook_profile']),
			'$multiprofs'     => feature_enabled(local_user(),'multi_profiles'),
			'$contact_id'     => $contact['abook_id'],
			'$block_text'     => (($contact['blocked']) ? t('Unblock') : t('Block') ),
			'$ignore_text'    => (($contact['readonly']) ? t('Unignore') : t('Ignore') ),
			'$blocked'        => (($contact['blocked']) ? t('Currently blocked') : ''),
			'$ignored'        => (($contact['readonly']) ? t('Currently ignored') : ''),
			'$archived'       => (($contact['archive']) ? t('Currently archived') : ''),
			'$pending'        => (($contact['archive']) ? t('Currently pending') : ''),
			'$hidden'         => array('hidden', t('Hide this contact from others'), ($contact['hidden'] == 1), t('Replies/likes to your public posts <strong>may</strong> still be visible')),
			'$photo'          => $contact['photo'],
			'$name'           => $contact['name'],
			'$dir_icon'       => $dir_icon,
			'$alt_text'       => $alt_text,
			'$sparkle'        => $sparkle,
			'$url'            => $url

		));

		$arr = array('contact' => $contact,'output' => $o);

		call_hooks('contact_edit', $arr);

		return $arr['output'];

	}

	$blocked   = false;
	$hidden    = false;
	$ignored   = false;
	$archived  = false;
	$unblocked = false;
	$pending   = false;

	$all = false;

	$_SESSION['return_url'] = $a->query_string;

	$search_flags = 0;
	$head = '';

	if(argc() == 2) {
		switch(argv(1)) {
			case 'blocked':
				$search_flags = ABOOK_FLAG_BLOCKED;
				$head = t('Blocked');
				$blocked = true;
				break;
			case 'ignored':
				$search_flags = ABOOK_FLAG_IGNORED;
				$head = t('Ignored');
				$ignored = true;
				break;
			case 'hidden':
				$search_flags = ABOOK_FLAG_HIDDEN;
				$head = t('Hidden');
				$hidden = true;
				break;
			case 'archived':
				$search_flags = ABOOK_FLAG_ARCHIVED;
				$head = t('Archived');
				$archived = true;
				break;
			case 'pending':
				$search_flags = ABOOK_FLAG_PENDING;
				$head = t('New');
				$pending = true;
				nav_set_selected('intros');
				break;

			case 'all':
				$head = t('All');
			default:
				$search_flags = 0;
				$all = true;
				break;

		}

		$sql_extra = (($search_flags) ? " and ( abook_flags & " . $search_flags . " ) " : "");


	}
	else {
		$sql_extra = " and not ( abook_flags & " . ABOOK_FLAG_BLOCKED . " ) ";
		$unblocked = true;
	}

	$search = ((x($_REQUEST,'search')) ? notags(trim($_REQUEST['search'])) : '');

	$tabs = array(
		array(
			'label' => t('Suggestions'),
			'url'   => $a->get_baseurl(true) . '/suggest', 
			'sel'   => '',
			'title' => t('Suggest new connections'),
		),
		array(
			'label' => t('New Connections'),
			'url'   => $a->get_baseurl(true) . '/connections/pending', 
			'sel'   => ($pending) ? 'active' : '',
			'title' => t('Show pending (new) connections'),
		),
		array(
			'label' => t('All Connections'),
			'url'   => $a->get_baseurl(true) . '/connections/all', 
			'sel'   => ($all) ? 'active' : '',
			'title' => t('Show all connections'),
		),
		array(
			'label' => t('Unblocked'),
			'url'   => $a->get_baseurl(true) . '/connections',
			'sel'   => (($unblocked) && (! $search) && (! $nets)) ? 'active' : '',
			'title' => t('Only show unblocked connections'),
		),

		array(
			'label' => t('Blocked'),
			'url'   => $a->get_baseurl(true) . '/connections/blocked',
			'sel'   => ($blocked) ? 'active' : '',
			'title' => t('Only show blocked connections'),
		),

		array(
			'label' => t('Ignored'),
			'url'   => $a->get_baseurl(true) . '/connections/ignored',
			'sel'   => ($ignored) ? 'active' : '',
			'title' => t('Only show ignored connections'),
		),

		array(
			'label' => t('Archived'),
			'url'   => $a->get_baseurl(true) . '/connections/archived',
			'sel'   => ($archived) ? 'active' : '',
			'title' => t('Only show archived connections'),
		),

		array(
			'label' => t('Hidden'),
			'url'   => $a->get_baseurl(true) . '/connections/hidden',
			'sel'   => ($hidden) ? 'active' : '',
			'title' => t('Only show hidden connections'),
		),

	);

	$tab_tpl = get_markup_template('common_tabs.tpl');
	$t = replace_macros($tab_tpl, array('$tabs'=>$tabs));

	$searching = false;
	if($search) {
		$search_hdr = $search;
		$search_txt = dbesc(protect_sprintf(preg_quote($search)));
		$searching = true;
	}
	$sql_extra .= (($searching) ? protect_sprintf(" AND xchan_name like '%$search_txt%' ") : "");

 	
	$r = q("SELECT COUNT(abook.abook_id) AS total FROM abook left join xchan on abook.abook_xchan = xchan.xchan_hash 
		where abook_channel = %d and not (abook_flags & %d) $sql_extra $sql_extra2 ",
		intval(local_user()),
		intval(ABOOK_FLAG_SELF)
	);
	if(count($r)) {
		$a->set_pager_total($r[0]['total']);
		$total = $r[0]['total'];
	}

	$r = q("SELECT abook.*, xchan.* FROM abook left join xchan on abook.abook_xchan = xchan.xchan_hash
		WHERE abook_channel = %d and not (abook_flags & %d) $sql_extra $sql_extra2 ORDER BY xchan_name LIMIT %d , %d ",
		intval(local_user()),
		intval(ABOOK_FLAG_SELF),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);

	$contacts = array();

	if(count($r)) {

		foreach($r as $rr) {
			if($rr['xchan_url']) {
				$contacts[] = array(
					'img_hover' => sprintf( t('%1$s [%2$s]'),$rr['xchan_name'],$rr['xchan_url']),
					'edit_hover' => t('Edit contact'),
					'id' => $rr['abook_id'],
					'alt_text' => $alt_text,
					'dir_icon' => $dir_icon,
					'thumb' => $rr['xchan_photo_m'], 
					'name' => $rr['xchan_name'],
					'username' => $rr['xchan_name'],
					'sparkle' => $sparkle,
					'link' => z_root() . '/connections/' . $rr['abook_id'],
					'url' => $rr['xchan_url'],
					'network' => network_to_name($rr['network']),
				);
			}
		}
	}
	

	$tpl = get_markup_template("contacts-template.tpl");
	$o .= replace_macros($tpl,array(
		'$header' => t('Connections') . (($head) ? ' - ' . $head : ''),
		'$tabs' => $t,
		'$total' => $total,
		'$search' => $search_hdr,
		'$desc' => t('Search your connections'),
		'$finding' => (($searching) ? t('Finding: ') . "'" . $search . "'" : ""),
		'$submit' => t('Find'),
		'$cmd' => $a->cmd,
		'$contacts' => $contacts,
		'$paginate' => paginate($a),

	)); 
	
	return $o;
}
