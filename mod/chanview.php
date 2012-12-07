<?php

require_once('include/Contact.php');

function chanview_content(&$a) {

	$xchan = null;

	$r = null;

	if($_REQUEST['hash']) {
		$r = q("select * from xchan where xchan_hash = '%s' limit 1",
			dbesc($_REQUEST['hash'])
		);
	}
	elseif(local_user() && intval($_REQUEST['cid'])) {
		$r = q("SELECT abook.*, xchan.* 
			FROM abook left join xchan on abook_xchan = xchan_hash
			WHERE abook_channel = %d and abook_id = %d LIMIT 1",
			intval(local_user()),
			intval($_REQUEST['cid'])
		);
	}	
	elseif($_REQUEST['url']) {
		$r = q("select * from xchan where xchan_url = '%s' limit 1",
			dbesc($_REQUEST['url'])
		);
		if(! $r)
			$r = array(array('xchan_url' => $_REQUEST['url']));
	}
	if($r) {
		$xchan = $r[0];
		if($xchan['xchan_hash'])
			$a->set_widget('vcard',vcard_from_xchan($xchan));

	}
	else {
		notice( t('No valid channel provided.') . EOL);
		return;
	}

	$o = replace_macros(get_markup_template('chanview.tpl'),array(
		'$url' => $xchan['xchan_url']
	));

	return $o;

}