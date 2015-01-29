<?php

function uexport_init(&$a) {
	if(! local_channel())
		killme();

	if(argc() > 1) {
		$channel = $a->get_channel();

		require_once('include/identity.php');

		header('content-type: application/octet_stream');
		header('content-disposition: attachment; filename="' . $channel['channel_address'] . '.json"' );


		if(argc() > 1 && argv(1) === 'basic') {
			echo json_encode(identity_basic_export(local_channel()));
			killme();
		}

		// FIXME - this basically doesn't work in the wild with a channel more than a few months old due to memory and execution time limits.  
		// It probably needs to be built at the CLI and offered to download as a tarball.  Maybe stored in the members dav.

		if(argc() > 1 && argv(1) === 'complete') {
			echo json_encode(identity_basic_export(local_channel(),true));
			killme();
		}
	}
}
	
function uexport_content(&$a) {
	$o = replace_macros(get_markup_template('uexport.tpl'), array(
		'$title' => t('Export Channel'),
		'$basictitle' => t('Export Channel'),
		'$basic' => t('Export your basic channel information to a small file.  This acts as a backup of your connections, permissions, profile and basic data, which can be used to import your data to a new hub, but	does not contain your content.'),
		'$fulltitle' => t('Export Content'),
		'$full' => t('Export your channel information and all the content to a JSON backup. This backs up all of your connections, permissions, profile data and all of your content, but is generally not suitable for importing a channel to a new hub as this file may be VERY large.  Please be patient - it may take several minutes for this download to begin.')
	));
return $o;
}
