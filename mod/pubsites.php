<?php

function pubsites_content(&$a) {
	require_once('include/dir_fns.php'); 
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

	$o .= '<div class="descriptive-text">' . 
		t('The listed sites allow public registration into the Red Matrix. All sites in the matrix are interlinked so membership on any of them conveys membership in the matrix as a whole. Some sites may require subscription or provide tiered service plans. The provider links <strong>may</strong> provide additional details.') . '</div>' . EOL;

	$ret = z_fetch_url($url);
	if($ret['success']) {
		$j = json_decode($ret['body'],true);
		if($j) {
			$o .= '<table border="1"><tr><td>' . t('Site URL') . '</td><td>' . t('Access Type') . '</td><td>' . t('Registration Policy') . '</td><td>' . t('Location') . '</td></tr>';
			foreach($j['sites'] as $jj) {
				$o .= '<tr><td>' . '<a href="'. (($jj['sellpage']) ? $jj['sellpage'] : $jj['url'] . '/register' ) . '" >' . $jj['url'] . '</a>' . '</td><td>' . $jj['access'] . '</td><td>' . $jj['register'] . '</td><td>' . $jj['location'] . '</td></tr>';
			}
	
			$o .= '</table>';
		}
	}
	return $o;
}
