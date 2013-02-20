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


function viewconnections_aside(&$a) {

	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		return;
	}

	profile_create_sidebar($a);
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



	$r = q("SELECT COUNT(abook_id) as total FROM abook WHERE abook_channel = %d AND abook_flags = 0 ",
		intval($a->profile['uid'])
	);
	if($r)
		$a->set_pager_total($r[0]['total']);

	$r = q("SELECT * FROM abook left join xchan on abook_xchan = xchan_hash where abook_channel = %d and abook_flags = 0 order by xchan_name LIMIT %d , %d ",
		intval($a->profile['uid']),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);

	if(! $r) {
		info( t('No connections.') . EOL );
		return $o;
	}

	$contacts = array();

	foreach($r as $rr) {

	    $url = zid($rr['xchan_url']);

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


	$tpl = get_markup_template("viewcontact_template.tpl");
	$o .= replace_macros($tpl, array(
		'$title' => t('View Connnections'),
		'$contacts' => $contacts,
		'$paginate' => paginate($a),
	));


	return $o;
}
