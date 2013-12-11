<?php /** @file */

function notes_init(&$a) {
	if(! local_user())
		return;
	logger('mod_notes: ' . print_r($_REQUEST,true));

	$ret = array('success' => true);
	if($_REQUEST['note_text'] || $_REQUEST['note_text'] == '') {
		$body = escape_tags($_REQUEST['note_text']);
		set_pconfig(local_user(),'notes','text',$body);
	}
	logger('notes saved.');
	json_return_and_die($ret);
	
}
