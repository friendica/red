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
			$r = q("select site_url, site_pull from site where site_url != '%s' and site_flags != %d order by rand() limit 1",
				dbesc(z_root()),
				intval(DIRECTORY_MODE_STANDALONE)
			);
			if($r)
				$url = $r[0]['site_url'];
		}

		// Note: blacklisted sites must be stored in the config as an array. 

		$blacklisted = false;
		$bl1 = get_config('system','blacklisted_sites');
		if(is_array($bl1) && $bl1) {
			foreach($bl1 as $bl) {
				if(strpos($url,$bl) !== false) {
					$blacklisted = true;
					break;
				}
			}
		}

		$attempts ++;

		// make sure we can eventually break out if somebody blacklists all known sites

		if($blacklisted) {
			if($attempts > 20)
				break;
			$attempts --;
			continue;
		}

		if($url) {
			if($r[0]['site_pull'] !== '0000-00-00 00:00:00')
				$mindate = urlencode($r[0]['site_pull']);
			else {
				$days = get_config('externals','since_days');
				if($days === false)
					$days = 15;
				$mindate = urlencode(datetime_convert('','','now - ' . intval($days) . ' days'));
			}

			$feedurl = $url . '/zotfeed?f=&mindate=' . $mindate;

			logger('externals: pulling public content from ' . $feedurl, LOGGER_DEBUG);

			$x = z_fetch_url($feedurl);
			if(($x) && ($x['success'])) {

				q("update site set site_pull = '%s' where site_url = '%s' limit 1",
					dbesc(datetime_convert()),
					dbesc($url)
				);

				$j = json_decode($x['body'],true);
				if($j['success'] && $j['messages']) {
					$sys = get_sys_channel();
					foreach($j['messages'] as $message) {
						$results = process_delivery(array('hash' => 'undefined'), get_item_elements($message),
							array(array('hash' => $sys['xchan_hash'])), false, true);
						$total ++;
//						$z = q("select id from item where mid = '%s' and uid = %d limit 1",
//							dbesc($message['message_id']),
//							intval($sys['channel_id'])
//						);
$z = null;
						if($z) {
							$flag_bits = ITEM_WALL|ITEM_ORIGIN|ITEM_UPLINK;
							// preserve the source

							$r = q("update item set source_xchan = owner_xchan where id = %d limit 1",
								intval($z[0]['id'])
							);

    						$r = q("update item set item_flags = ( item_flags | %d ), owner_xchan = '%s' 
								where id = %d limit 1",
								intval($flag_bits),
								dbesc($sys['xchan_hash']),
								intval($z[0]['id'])
							);
						}
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
