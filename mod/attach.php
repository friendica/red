<?php

require_once('include/security.php');
require_once('include/attach.php');

function attach_init(&$a) {

	if(argc() < 2) {
		notice( t('Item not available.') . EOL);
		return;
	}

	$r = attach_by_hash(argv(1),((argc() > 2) ? intval(argv(2)) : 0));

	if(! $r['success']) {
		notice( $r['message'] . EOL);
		return;
	}

	$c = q("select channel_address from channel where channel_id = %d limit 1",
		intval($r['data']['uid'])
	);

	if(! $c)
		return;

	header('Content-type: ' . $r['data']['filetype']);
	header('Content-disposition: attachment; filename="' . $r['data']['filename'] . '"');
	if($r['data']['flags'] & ATTACH_FLAG_OS ) {
		$istream = fopen('store/' . $c[0]['channel_address'] . '/' . $r['data']['data'],'rb');
		$ostream = fopen('php://output','wb');
		if($istream && $ostream) {
			pipe_streams($istream,$ostream);
			fclose($istream);
			fclose($ostream);
		}
	}
	else
		echo $r['data']['data'];
	killme();

}
