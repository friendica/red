<?php
require_once('include/contact_selectors.php');
require_once('include/Contact.php');

function viewconnections_init(&$a) {

	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		return;
	}
	if(argc() > 1)
		profile_load($a,argv(1));
}

function viewconnections_content(&$a) {

	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		notice( t('Public access denied.') . EOL);
		return;
	}

	if(((! count($a->profile)) || ($a->profile['hide_friends']))) {
		notice( t('Permission denied.') . EOL);
		return;
	} 

	if(! perm_is_allowed($a->profile['uid'], get_observer_hash(),'view_contacts')) {
		notice( t('Permission denied.') . EOL);
		return;
	} 

	$is_owner = ((local_user() && local_user() == $a->profile['uid']) ? true : false);

	$abook_flags = ABOOK_FLAG_PENDING|ABOOK_FLAG_SELF;
	$xchan_flags = XCHAN_FLAGS_ORPHAN|XCHAN_FLAGS_DELETED;
	if(! $is_owner) {
		$abook_flags = $abook_flags | ABOOK_FLAGS_HIDDEN;
		$xchan_flags = $xchan_flags | XCHAN_FLAGS_HIDDEN;
	}

	$r = q("SELECT count(*) as total FROM abook left join xchan on abook_xchan = xchan_hash where abook_channel = %d and not (abook_flags & %d ) and not ( xchan_flags & %d ) ",
		intval($a->profile['uid']),
		intval($abook_flags),
		intval($xchan_flags)
	);
	if($r) {
		$a->set_pager_total($r[0]['total']);
	}

	$r = q("SELECT * FROM abook left join xchan on abook_xchan = xchan_hash where abook_channel = %d and not ( abook_flags & %d ) and not ( xchan_flags & %d ) order by xchan_name LIMIT %d , %d ",
		intval($a->profile['uid']),
		intval($abook_flags),
		intval($xchan_flags),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);

	if(! $r) {
		info( t('No connections.') . EOL );
		return $o;
	}

	$contacts = array();

	foreach($r as $rr) {

	    $url = chanlink_url($rr['xchan_url']);
		if($url) {
			$contacts[] = array(
				'id' => $rr['abook_id'],
				'img_hover' => sprintf( t('Visit %s\'s profile [%s]'), $rr['xchan_name'], $rr['xchan_url']),
				'thumb' => $rr['xchan_photo_m'], 
				'name' => substr($rr['xchan_name'],0,20),
				'username' => $rr['xchan_addr'],
				'link' => $url,
				'sparkle' => '',
				'itemurl' => $rr['url'],
				'network' => '',
			);
		}
	}


	$tpl = get_markup_template("viewcontact_template.tpl");
	$o .= replace_macros($tpl, array(
		'$title' => t('View Connnections'),
		'$contacts' => $contacts,
		'$paginate' => paginate($a),
	));


	return $o;
}
