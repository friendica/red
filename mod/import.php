<?php

// Import a channel, either by direct file upload or via
// connection to original server. 


function import_post(&$a) {

	$src      = $_FILES['userfile']['tmp_name'];
	$filename = basename($_FILES['userfile']['name']);
	$filesize = intval($_FILES['userfile']['size']);
	$filetype = $_FILES['userfile']['type'];


	if(($src) && (! $filesize)) {
		logger('mod_import: empty file.');
		notice( t('Imported file is empty.');
		return;
	}



}



function import_content(&$a) {




}