<?php

function follow_widget() {

	return replace_macros(get_markup_template('follow.tpl'),array(
		'$connect' => t('Add New Contact'),
		'$desc' => t('Enter address or web location'),
		'$hint' => t('Example: bob@example.com, http://example.com/barbara'),
		'$follow' => t('Connect')
	));

}

function findpeople_widget() {

	$a = get_app();

	$inv = (($a->config['register_policy'] != REGISTER_CLOSED) ? t('Invite Friends') : '');

	if(get_config('system','invitation_only')) {
		$x = get_pconfig(local_user(),'system','invites_remaining');
		if($x || is_site_admin()) {
			$a->page['aside'] .= '<div class="side-link" id="side-invite-remain">' 
			. sprintf( tt('%d invitation available','%d invitations available',$x), $x) 
			. '</div>' . $inv;
		}
	}
 
	return replace_macros(get_markup_template('peoplefind.tpl'),array(
		'$findpeople' => t('Find People'),
		'$desc' => t('Enter name or interest'),
		'$label' => t('Connect/Follow'),
		'$hint' => t('Examples: Robert Morgenstein, Fishing'),
		'$findthem' => t('Find'),
		'$similar' => t('Similar Interests'),
		'$inv' => $inv
	));

}

