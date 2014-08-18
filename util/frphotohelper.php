<?php

require_once('include/cli_startup.php');

cli_startup();

$a = get_app();


$photo_id = $argv[1];
$channel_address = $argv[2];
$fr_server = urldecode($argv[3]);
require_once('include/photos.php');

$cookies = 'store/[data]/frphoto_cookie_' . $channel_address;

	$c = q("select * from channel left join xchan on channel_hash = xchan_hash where channel_address = '%s' limit 1",
		dbesc($channel_address)
	);
	if(! $c) {
		logger('frphotohelper: channel not found');
		killme();
	}
	$channel = $c[0];	


	    $ch = curl_init($fr_server . '/api/friendica/photo?f=&photo_id=' . $photo_id);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_COOKIEFILE, $cookies);
        curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookies);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'RedMatrix');

        $output = curl_exec($ch);
        curl_close($ch);

		$j = json_decode($output,true);

//		logger('frphotohelper: ' . print_r($j,true));

		$args = array();
		$args['data'] = base64_decode($j['data']);
		$args['filename'] = $j['filename'];
		$args['resource_id'] = $j['resource-id'];
		$args['scale'] = $j['scale'];
		$args['album'] = $j['album'];
		$args['not_visible'] = 1;
		$args['created'] = $j['created'];
		$args['edited'] = $j['edited'];
		$args['title'] = $j['title'];
		$args['description'] = $j['desc'];

		if($j['allow_cid'] || $j['allow_gid'] || $j['deny_cid'] || $j['deny_gid'])
			$args['contact_allow'] = $channel['channel_hash'];		

		$args['type'] = $j['type']; 



		$r = q("select * from photo where resource_id = '%s' and uid = %d limit 1",
			dbesc($args['resource_id']),
			intval($channel['channel_id'])
		);
		if($r) {
			killme();
		}


		$ret = photo_upload($channel,$channel,$args);
		logger('photo_import: ' . print_r($ret,true));

		killme();

