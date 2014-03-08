<?php

function wfinger_init(&$a) {
	
	$result = array();

	$scheme = '';

	if(x($_SERVER,'HTTPS') && $_SERVER['HTTPS'])
		$scheme = 'https';
	elseif(x($_SERVER,'SERVER_PORT') && (intval($_SERVER['SERVER_PORT']) == 443))
		$scheme = 'https';

	// Don't complain to me - I'm just implementing the spec. 

	if($scheme !== 'https') {
		header($_SERVER["SERVER_PROTOCOL"] . ' ' . 500 . ' ' . 'Webfinger requires HTTPS');
		killme();
	}

	$resource = $_REQUEST['resource'];

	header('Access-Control-Allow-Origin: *');

	header('Content-type: application/jrd+json');

	$r = null;

	if($resource) {

		if(strpos($resource,'acct:') === 0) {
			$channel = str_replace('acct:','',$resource);
			$channel = substr($channel,0,strpos($channel,'@'));
		}
		if(strpos($resource,'http') === 0) {
			$channel = str_replace('~','',basename($resource));
		}

		$r = q("select * from channel left join xchan on channel_hash = xchan_hash 
			where channel_address = '%s' limit 1",
			dbesc($channel)
		);

	}

	if($resource && $r) {

		$result['subject'] = $resource;

		$aliases = array(
			'acct:' . $r[0]['channel_address'] . '@' . $a->get_hostname(),
			z_root() . '/channel/' . $r[0]['channel_address'],
			z_root() . '/~' . $r[0]['channel_address']
		);

		$result['aliases'] = array();

		foreach($aliases as $alias) 
			if($alias != $resource)
				$result['aliases'][] = $alias;


		$result['links'] = array(

			array(
				'rel' => 'http://webfinger.example/rel/avatar',
				'type' => $r[0]['xchan_photo_mimetype'],
				'href' => $r[0]['xchan_photo_l']	
			),

			array(
				'rel' => 'http://webfinger.example/rel/profile-page',
				'href' => z_root() . '/profile/' . $r[0]['channel_address'],
			),

			array(
				'rel' => 'http://webfinger.example/rel/blog',
				'href' => z_root() . '/channel/' . $r[0]['channel_address'],
			),

			array(
				'rel' => 'http://purl.org/zot/protocol',
				'href' => z_root() . '/.well-known/zot-info' . '?address=' . $r[0]['xchan_addr'],
			)
		);

	}
	else {
		header($_SERVER["SERVER_PROTOCOL"] . ' ' . 400 . ' ' . 'Bad Request');
		killme();
	}

	$arr = array('channel' => $r[0], 'request' => $_REQUEST, 'result' => $result);
	call_hooks('webfinger',$arr);

	echo json_encode($arr['result']);
	killme();

}