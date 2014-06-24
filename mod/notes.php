<?php /** @file */

function notes_init(&$a) {

	if(! local_user())
		return;

	$ret = array('success' => true);
	if($_REQUEST['note_text'] || $_REQUEST['note_text'] == '') {
		$body = escape_tags($_REQUEST['note_text']);
		set_pconfig(local_user(),'notes','text',$body);
	}

	// push updates to channel clones

	if((argc() > 1) && (argv(1) === 'sync')) {
		require_once('include/zot.php');
		build_sync_packet();
	}

	logger('notes saved.', LOGGER_DEBUG);
	json_return_and_die($ret);
	
}
