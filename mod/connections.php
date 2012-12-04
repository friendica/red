<?php

require_once('include/Contact.php');
require_once('include/socgraph.php');
require_once('include/contact_selectors.php');
require_once('include/group.php');
require_once('include/contact_widgets.php');

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
}

function connections_aside(&$a) {

	if(x($a->data,'abook'))
		$a->set_widget('vcard',vcard_from_xchan($a->data['abook']));
	else
		$a->set_widget('follow', follow_widget());

	$a->set_widget('collections', group_side('connnections','group',false,0,$abook_id));
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
		goaway($a->get_baseurl(true) . '/connnections');
		return; // NOTREACHED
	}

	call_hooks('contact_edit_post', $_POST);

	$profile_id = intval($_POST['profile-assign']);
	if($profile_id) {
		$r = q("SELECT `id` FROM `profile` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($profile_id),
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

	$r = q("UPDATE abook SET abook_profile = %d, abook_my_perms = %d , abook_closeness = %d
		where abook_id = %d AND abook_channel = %d LIMIT 1",
		intval($profile_id),
		intval($abook_my_perms),
		intval($closeness),
		intval($contact_id),
		intval(local_user())
	);
	if($r)
		info( t('Connection updated.') . EOL);
	else
		notice( t('Failed to update connnection record.') . EOL);


	if((x($a->data,'abook')) && $a->data['abook']['abook_my_perms'] != $abook_my_perms) {
		// FIXME - this message type is not yet handled in the notifier
		proc_run('php', 'include/notifier.php', 'permission_update', $contact_id);
	}

	// Refresh the structure in memory with the new data

	$r = q("SELECT abook.*, xchan.* 
		FROM abook left join xchan on abook_xchan = xchan_hash
		WHERE abook_channel = %d and abook_id = %d LIMIT 1",
		intval(local_user()),
		intval($contact_id)
	);
	if($r)
		$a->data['abook'] = $r[0];

	return;

}



function connections_content(&$a) {

	$sort_type = 0;
	$o = '';
	nav_set_selected('connections');


	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	if(argc() == 3) {

		$contact_id = intval(argv(1));
		if(! $contact_id)
			return;

		$cmd = argv(2);

		$orig_record = q("SELECT abook.*, xchan.* FROM abook left join xchan on abook_xchan = xchan_hash
			WHERE abook_id = %d AND abook_channel = %d AND NOT ( abook_flags & %d ) and not abook_flags & %d) LIMIT 1",
			intval($contact_id),
			intval(local_user()),
			intval(ABOOK_FLAG_SELF),
			intval(ABOOK_FLAG_PENDING)
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

		if($cmd === 'block') {
			if(abook_toggle_flag($orig_record[0],ABOOK_FLAG_BLOCKED))
				info((($orig_record[0]['abook_flags'] & ABOOK_FLAG_BLOCKED) 
					? t('Channel has been unblocked') 
					: t('Channel has been blocked')) . EOL );
			else
				notice(t('Unable to set address book parameters.') . EOL);
			goaway($a->get_baseurl(true) . '/connections/' . $contact_id);
		}

		if($cmd === 'ignore') {
			if(abook_toggle_flag($orig_record[0],ABOOK_FLAG_IGNORED))
				info((($orig_record[0]['abook_flags'] & ABOOK_FLAG_IGNORED) 
					? t('Channel has been unignored') 
					: t('Channel has been ignored')) . EOL );
			else
				notice(t('Unable to set address book parameters.') . EOL);
			goaway($a->get_baseurl(true) . '/connections/' . $contact_id);
		}

		if($cmd === 'archive') {
			if(abook_toggle_flag($orig_record[0],ABOOK_FLAG_ARCHIVED))
				info((($orig_record[0]['abook_flags'] & ABOOK_FLAG_ARCHIVED) 
					? t('Channel has been unarchived') 
					: t('Channel has been archived')) . EOL );
			else
				notice(t('Unable to set address book parameters.') . EOL);
			goaway($a->get_baseurl(true) . '/connections/' . $contact_id);
		}

		if($cmd === 'hide') {
			if(abook_toggle_flag($orig_record[0],ABOOK_FLAG_HIDDEN))
				info((($orig_record[0]['abook_flags'] & ABOOK_FLAG_HIDDEN) 
					? t('Channel has been unhidden') 
					: t('Channel has been hidden')) . EOL );
			else
				notice(t('Unable to set address book parameters.') . EOL);
			goaway($a->get_baseurl(true) . '/connections/' . $contact_id);
		}


		if($cmd === 'drop') {

			require_once('include/Contact.php');
// FIXME
//			terminate_friendship($a->get_channel(),$orig_record[0]);

			contact_remove($orig_record[0]['abook_id']);
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

	$tab_tpl = get_markup_template('common_tabs.tpl');
	$t = replace_macros($tab_tpl, array('$tabs'=>$tabs));




		$a->page['htmlhead'] .= replace_macros(get_markup_template('contact_head.tpl'), array(
			'$baseurl' => $a->get_baseurl(true),
			'$editselect' => $editselect,
		));

		require_once('include/contact_selectors.php');

		$tpl = get_markup_template("abook_edit.tpl");

		if(feature_enabled(local_user(),'affinity')) {

			$slider_tpl = get_markup_template('contact_slider.tpl');
			$slide = replace_macros($slider_tpl,array(
				'$me' => t('Me'),
				'$val' => $contact['abook_closeness'],
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
		foreach($global_perms as $k => $v) {
			$perms[] = array('perms_' . $k, $v[3], (($contact['abook_their_perms'] & $v[1]) ? "1" : ""),(($contact['abook_my_perms'] & $v[1]) ? "1" : ""), $v[1], (($channel[$v[0]] == PERMS_SPECIFIC) ? '' : '1'), $v[4]);
		}


		$o .= replace_macros($tpl,array(

			'$header' => t('Contact Settings') . ' for ' . $contact['xchan_name'],
			'$viewprof' => t('View Profile'),
			'$lbl_slider' => t('Slide to adjust your degree of friendship'),
			'$slide' => $slide,
			'$tabs' => $t,
			'$tab_str' => $tab_str,
			'$submit' => t('Submit'),
			'$lbl_vis1' => t('Profile Visibility'),
			'$lbl_vis2' => sprintf( t('Please choose the profile you would like to display to %s when viewing your profile securely.'), $contact['name']),
			'$lbl_info1' => t('Contact Information / Notes'),
			'$infedit' => t('Edit contact notes'),
			'$close' => $contact['abook_closeness'],
			'$them' => t('Their Settings'),
			'$me' => t('My Settings'),
			'$perms' => $perms,


			'$common_link' => $a->get_baseurl(true) . '/common/loc/' . local_user() . '/' . $contact['id'],
			'$all_friends' => $all_friends,
			'$relation_text' => $relation_text,
			'$visit' => sprintf( t('Visit %s\'s profile [%s]'),$contact['xchan_name'],$contact['xchan_url']),
			'$blockunblock' => t('Block/Unblock contact'),
			'$ignorecont' => t('Ignore contact'),
			'$lblcrepair' => t("Repair URL settings"),
			'$lblrecent' => t('View conversations'),
			'$lblsuggest' => $lblsuggest,
			'$delete' => t('Delete contact'),
			'$nettype' => $nettype,
			'$poll_interval' => contact_poll_interval($contact['priority'],(! $poll_enabled)),
			'$poll_enabled' => $poll_enabled,
			'$lastupdtext' => t('Last update:'),
			'$lost_contact' => $lost_contact,
			'$updpub' => t('Update public posts'),
			'$last_update' => $last_update,
			'$udnow' => t('Update now'),
			'$profile_select' => contact_profile_assign($contact['profile_id'],(($contact['network'] !== NETWORK_DFRN) ? true : false)),
			'$contact_id' => $contact['abook_id'],
			'$block_text' => (($contact['blocked']) ? t('Unblock') : t('Block') ),
			'$ignore_text' => (($contact['readonly']) ? t('Unignore') : t('Ignore') ),
			'$insecure' => (($contact['network'] !== NETWORK_DFRN && $contact['network'] !== NETWORK_MAIL && $contact['network'] !== NETWORK_FACEBOOK && $contact['network'] !== NETWORK_DIASPORA) ? $insecure : ''),
			'$info' => $contact['info'],
			'$blocked' => (($contact['blocked']) ? t('Currently blocked') : ''),
			'$ignored' => (($contact['readonly']) ? t('Currently ignored') : ''),
			'$archived' => (($contact['archive']) ? t('Currently archived') : ''),
			'$hidden' => array('hidden', t('Hide this contact from others'), ($contact['hidden'] == 1), t('Replies/likes to your public posts <strong>may</strong> still be visible')),
			'$photo' => $contact['photo'],
			'$name' => $contact['name'],
			'$dir_icon' => $dir_icon,
			'$alt_text' => $alt_text,
			'$sparkle' => $sparkle,
			'$url' => $url

		));

		$arr = array('contact' => $contact,'output' => $o);

		call_hooks('contact_edit', $arr);

		return $arr['output'];

	}

	$blocked = false;
	$hidden = false;
	$ignored = false;
	$archived = false;
	$unblocked = false;
	$all = false;

	$_SESSION['return_url'] = $a->query_string;

	$search_flags = 0;

	if(argc() == 2) {
		switch(argv(1)) {
			case 'blocked':
				$search_flags = ABOOK_FLAG_BLOCKED;
				$blocked = true;
				break;
			case 'ignored':
				$search_flags = ABOOK_FLAG_IGNORED;
				$ignored = true;
				break;
			case 'hidden':
				$search_flags = ABOOK_FLAG_HIDDEN;
				$hidden = true;
				break;
			case 'archived':
				$search_flags = ABOOK_FLAG_ARCHIVED;
				$archived = true;
				break;
			case 'all':
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

	$nets = ((x($_GET,'nets')) ? notags(trim($_GET['nets'])) : '');

	$tabs = array(
		array(
			'label' => t('Suggestions'),
			'url'   => $a->get_baseurl(true) . '/suggest', 
			'sel'   => '',
			'title' => t('Suggest new connections'),
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

	if($nets)
		$sql_extra .= sprintf(" AND xchan_network = '%s' ", dbesc($nets));
 	
	$r = q("SELECT COUNT(abook.abook_id) AS total FROM abook left join xchan on abook.abook_xchan = xchan.xchan_hash 
		where abook_channel = %d and not (abook_flags & %d) and not (abook_flags & %d) $sql_extra $sql_extra2 ",
		intval(local_user()),
		intval(ABOOK_FLAG_SELF),
		intval(ABOOK_FLAG_PENDING)
	);
	if(count($r)) {
		$a->set_pager_total($r[0]['total']);
		$total = $r[0]['total'];
	}
dbg(1);
	$r = q("SELECT abook.*, xchan.* FROM abook left join xchan on abook.abook_xchan = xchan.xchan_hash
		WHERE abook_channel = %d and not (abook_flags & %d) and not (abook_flags & %d) $sql_extra $sql_extra2 ORDER BY xchan_name LIMIT %d , %d ",
		intval(local_user()),
		intval(ABOOK_FLAG_SELF),
		intval(ABOOK_FLAG_PENDING),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);
dbg(0);
	$contacts = array();

	if(count($r)) {

		foreach($r as $rr) {

			$contacts[] = array(
				'img_hover' => sprintf( t('%1$s [%2$s]'),$rr['xchan_name'],$rr['xchan_url']),
				'edit_hover' => t('Edit contact'),
				'photo_menu' => contact_photo_menu($rr),
				'id' => $rr['abook_id'],
				'alt_text' => $alt_text,
				'dir_icon' => $dir_icon,
				'thumb' => $rr['xchan_photo_m'], 
				'name' => $rr['xchan_name'],
				'username' => $rr['xchan_name'],
				'sparkle' => $sparkle,
				'edit' => z_root() . '/connections/' . $rr['abook_id'],
				'url' => $rr['xchan_url'],
				'network' => network_to_name($rr['network']),
			);
		}

		

	}
	
	$tpl = get_markup_template("contacts-template.tpl");
	$o .= replace_macros($tpl,array(
		'$header' => t('Connnections') . (($nets) ? ' - ' . network_to_name($nets) : ''),
		'$tabs' => $t,
		'$total' => $total,
		'$search' => $search_hdr,
		'$desc' => t('Search your connnections'),
		'$finding' => (($searching) ? t('Finding: ') . "'" . $search . "'" : ""),
		'$submit' => t('Find'),
		'$cmd' => $a->cmd,
		'$contacts' => $contacts,
		'$paginate' => paginate($a),

	)); 
	
	return $o;
}
