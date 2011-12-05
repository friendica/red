<?php

function viewcontacts_init(&$a) {

	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		return;
	}

	profile_load($a,$a->argv[1]);
}


function viewcontacts_content(&$a) {

	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		notice( t('Public access denied.') . EOL);
		return;
	}

	if(((! count($a->profile)) || ($a->profile['hide-friends']))) {
		notice( t('Permission denied.') . EOL);
		return;
	} 

	$o .= '<h3>' . t('View Contacts') . '</h3>';


	$r = q("SELECT COUNT(*) as `total` FROM `contact` WHERE `uid` = %d AND `blocked` = 0 AND `pending` = 0 AND `hidden` = 0 ",
		intval($a->profile['uid'])
	);
	if(count($r))
		$a->set_pager_total($r[0]['total']);

	$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `blocked` = 0 AND `pending` = 0 AND `hidden` = 0 ORDER BY `name` ASC LIMIT %d , %d ",
		intval($a->profile['uid']),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);
	if(! count($r)) {
		info( t('No contacts.') . EOL );
		return $o;
	}

	$tpl = get_markup_template("viewcontact_template.tpl");

	foreach($r as $rr) {
		if($rr['self'])
			continue;

	    $url = $rr['url'];

		// route DFRN profiles through the redirect

		$is_owner = ((local_user() && ($a->profile['profile_uid'] == local_user())) ? true : false);

		if($is_owner && ($rr['network'] === NETWORK_DFRN) && ($rr['rel']))
			$url = 'redir/' . $rr['id'];

		$o .= replace_macros($tpl, array(
			'$id' => $rr['id'],
			'$alt_text' => sprintf( t('Visit %s\'s profile [%s]'), $rr['name'], $rr['url']),
			'$thumb' => $rr['thumb'], 
			'$name' => substr($rr['name'],0,20),
			'$username' => $rr['name'],
			'$url' => $url
		));
	}

	$o .= '<div id="view-contact-end"></div>';

	$o .= paginate($a);

	return $o;
}
