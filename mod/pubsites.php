<?php

function pubsites_content(&$a) {
	require_once('include/dir_fns.php'); 
	$dirmode = intval(get_config('system','directory_mode'));

	if(($dirmode == DIRECTORY_MODE_PRIMARY) || ($dirmode == DIRECTORY_MODE_STANDALONE)) {
		$url = z_root() . '/dirsearch';
	}
	if(! $url) {
		$directory = find_upstream_directory($dirmode);
		$url = $directory['url'] . '/dirsearch';
	}
	$url .= '/sites';

	$o .= '<h1>' . t('Public Sites') . '</h1>';

	$o .= '<div class="descriptive-text">' . 
		t('The listed sites allow public registration into the Red Matrix. All sites in the matrix are interlinked so membership on any of them conveys membership in the matrix as a whole. Some sites may require subscription or provide tiered service plans. The provider links <strong>may</strong> provide additional details.') . '</div>' . EOL;

	$ret = z_fetch_url($url);
	if($ret['success']) {
		$j = json_decode($ret['body'],true);
		if($j) {
			$rate_meta = ((local_channel()) ? '<td>' . t('Rate this hub') . '</td>' : '');
			$o .= '<table border="1"><tr><td>' . t('Site URL') . '</td><td>' . t('Access Type') . '</td><td>' . t('Registration Policy') . '</td><td>' . t('Location') . '</td><td>' . t('View hub ratings') . '</td>' . $rate_meta . '</tr>';
			if($j['sites']) {
				foreach($j['sites'] as $jj) {
					$host = strtolower(substr($jj['url'],strpos($jj['url'],'://')+3));
					$rate_links = ((local_channel()) ? '<td><a href="rate?f=&target=' . $host . '" class="btn-btn-default"><i class="icon-check"></i> ' . t('Rate') . '</a></td>' : '');
					$o .= '<tr><td>' . '<a href="'. (($jj['sellpage']) ? $jj['sellpage'] : $jj['url'] . '/register' ) . '" >' . $jj['url'] . '</a>' . '</td><td>' . $jj['access'] . '</td><td>' . $jj['register'] . '</td><td>' . $jj['location'] . '</td><td><a href="ratings/' . $host . '" class="btn-btn-default"><i class="icon-eye-open"></i> ' . t('View ratings') . '</a></td>' . $rate_links . '</tr>';
				}
			}
	
			$o .= '</table>';
		}
	}
	return $o;
}
