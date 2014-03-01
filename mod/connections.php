<?php

require_once('include/Contact.php');
require_once('include/socgraph.php');
require_once('include/contact_selectors.php');
require_once('include/group.php');
require_once('include/contact_widgets.php');
require_once('include/zot.php');
require_once('include/widgets.php');

function connections_init(&$a) {

	if(! local_user())
		return;

	$channel = $a->get_channel();
	if($channel)
		head_set_icon($channel['xchan_photo_s']);

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

	$profile_id = $_POST['profile_assign'];
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
		if(argv(1) === 'pending')
			$sql_extra .= " and not ( abook_flags & " . ABOOK_FLAG_IGNORED . " ) ";

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
		where abook_channel = %d and not (abook_flags & %d) and not (xchan_flags & %d ) $sql_extra $sql_extra2 ",
		intval(local_user()),
		intval(ABOOK_FLAG_SELF),
		intval(XCHAN_FLAGS_DELETED)
	);
	if(count($r)) {
		$a->set_pager_total($r[0]['total']);
		$total = $r[0]['total'];
	}

	$r = q("SELECT abook.*, xchan.* FROM abook left join xchan on abook.abook_xchan = xchan.xchan_hash
		WHERE abook_channel = %d and not (abook_flags & %d) and not ( xchan_flags & %d) $sql_extra $sql_extra2 ORDER BY xchan_name LIMIT %d , %d ",
		intval(local_user()),
		intval(ABOOK_FLAG_SELF),
		intval(XCHAN_FLAGS_DELETED),
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
					'link' => z_root() . '/connedit/' . $rr['abook_id'],
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
