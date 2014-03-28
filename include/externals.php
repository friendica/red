<?php /** @file */

require_once('boot.php');
require_once('include/cli_startup.php');
require_once('include/zot.php');
require_once('include/identity.php');

function externals_run($argv, $argc){

	cli_startup();
	$a = get_app();
	

	$total = 0;
	$attempts = 0;

	// pull in some public posts


	while($total == 0 && $attempts < 3) {
		$arr = array('url' => '');
		call_hooks('externals_url_select',$arr);

		if($arr['url']) {
			$url = $arr['url'];
		} 
		else {
			$r = q("select site_url from site where site_url != '%s' and site_flags != %d order by rand() limit 1",
				dbesc(z_root()),
				intval(DIRECTORY_MODE_STANDALONE)
			);
			if($r)
				$url = $r[0]['site_url'];
		}

		$attempts ++;

		if($url) {
			$days = get_config('externals','since_days');
			if($days === false)
				$days = 15;

			$feedurl = $url . '/zotfeed?f=&mindate=' . urlencode(datetime_convert('','','now - ' . intval($days) . ' days'));

			logger('externals: pulling public content from ' . $feedurl, LOGGER_DEBUG);
			$x = z_fetch_url($feedurl);

			if(($x) && ($x['success'])) {
				$j = json_decode($x['body'],true);
				if($j['success'] && $j['messages']) {
					$sys = get_sys_channel();
					foreach($j['messages'] as $message) {
						$results = process_delivery(array('hash' => 'undefined'), get_item_elements($message),
							array(array('hash' => $sys['xchan_hash'])), false, true);
						$total ++;
					}
					logger('externals: import_public_posts: ' . $total . ' messages imported', LOGGER_DEBUG);
				}
			}
		}
	}
}

if (array_search(__file__,get_included_files())===0){
  externals_run($argv,$argc);
  killme();
}
