<?php

function viewcontacts_init(&$a) {

	profile_load($a,$a->argv[1]);

}


function viewcontacts_content(&$a) {

	if(((! count($a->profile)) || ($a->profile['hide-friends']))) {
		notice( t('Permission denied.') . EOL);
		return;
	} 

	$o .= '<h3>' . t('View Contacts') . '</h3>';


	$r = q("SELECT COUNT(*) as `total` FROM `contact` WHERE `uid` = %d AND `blocked` = 0 AND `pending` = 0",
		intval($a->profile['uid'])
	);
	if(count($r))
		$a->set_pager_total($r[0]['total']);

	$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `blocked` = 0 AND `pending` = 0 ORDER BY `name` ASC LIMIT %d , %d ",
		intval($a->profile['uid']),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);
	if(! count($r)) {
		notice( t('No contacts.') . EOL );
		return $o;
	}

	$tpl = load_view_file("view/viewcontact_template.tpl");

	foreach($r as $rr) {
		if($rr['self'])
			continue;

		$o .= replace_macros($tpl, array(
			'$id' => $rr['id'],
			'$alt_text' => t('Visit $username\'s profile'),
			'$thumb' => $rr['thumb'], 
			'$name' => substr($rr['name'],0,20),
			'$username' => $rr['name'],
			'$url' => $rr['url'] 
		));
	}

	$o .= '<div id="view-contact-end"></div>';

	$o .= paginate($a);

	return $o;
}