<?php

function pubsites_content(&$a) {

	$dirmode = intval(get_config('system','directory_mode'));

	if(($dirmode == DIRECTORY_MODE_PRIMARY) || ($dirmode == DIRECTORY_MODE_STANDALONE)) {
		$url = z_root() . '/dirsearch';
	}
	if(! $url) {
		$directory = find_upstream_directory($dirmode);

		if($directory) {
			$url = $directory['url'] . '/dirsearch';
		}
		else {
			$url = DIRECTORY_FALLBACK_MASTER . '/dirsearch';
		}
	}
	$url .= '/sites';

	$o .= '<h1>' . t('Public Sites') . '</h1>';

	$ret = z_fetch_url($url);
	if($ret['success']) {
		$j = json_decode($ret['body'],true);
		if($j) {
			$o .= '<table border="1"><tr><td>' . t('Site URL') . '</td><td>' . t('Access Type') . '</td><td>' . t('Registration Policy') . '</td></tr>';
			foreach($j['sites'] as $jj) {
				$o .= '<tr><td>' . '<a href="'. $jj['url'] . '" >' . $jj['url'] . '</a>' . '</td><td>' . $jj['access'] . '</td><td>' . $jj['register'] . '</td></tr>';
			}
	
			$o .= '</table>';
		}
	}
	return $o;
}
