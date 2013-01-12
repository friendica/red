<?php

// Import a channel, either by direct file upload or via
// connection to original server. 


function import_post(&$a) {


	$sieze    = ((x($_REQUEST,'make_primary')) ? intval($_REQUEST['make_primary']) : 0);

	$src      = $_FILES['userfile']['tmp_name'];
	$filename = basename($_FILES['userfile']['name']);
	$filesize = intval($_FILES['userfile']['size']);
	$filetype = $_FILES['userfile']['type'];


	if(($src) && (! $filesize)) {
		logger('mod_import: empty file.');
		notice( t('Imported file is empty.') . EOL);
		return;
	}

	if(! $src) {
		$old_address = ((x($_REQUEST,'old_address')) ? $_REQUEST['old_address'] : '');
		if(! $old_address) {
			logger('mod_import: nothing to import.');
			notice( t('Nothing to import.') . EOL);
			return;
		}

		// Connect to API of old server with credentials given and suck in the data we need


	}



	// import channel
	
	// import contacts





	if($sieze) {
		// notify old server that it is no longer primary.
		
	}


	// send out refresh requests

}



function import_content(&$a) {




}