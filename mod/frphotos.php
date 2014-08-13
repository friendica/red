<?php



function frphotos_init(&$a) {

	if(! local_user())
		return;

	if(intval(get_pconfig(local_user(),'frphotos','complete')))
		return;

	$channel = $a->get_channel();
	
	$fr_server = $_REQUEST['fr_server'];
	$fr_username = $_REQUEST['fr_username'];
	$fr_password = $_REQUEST['fr_password'];

	$cookies = 'store/[data]/frphoto_cookie_' . $channel['channel_address'];

	if($fr_server && $fr_username && $fr_password) {

		$ch = curl_init($fr_server . '/api/friendica/photos/list');

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
       	curl_setopt ($ch, CURLOPT_COOKIEFILE, $cookies);
        curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookies);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $fr_username . ':' . $fr_password); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);                          
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);                           
		curl_setopt($ch, CURLOPT_USERAGENT, 'RedMatrix');
 
		$output = curl_exec($ch);
		curl_close($ch);

		$j = json_decode($output,true);

//		echo print_r($j,true);

		$total = 0;
		if(count($j)) {
			foreach($j as $jj) {
				$total ++;
				proc_run('php','util/frphotohelper.php',$jj, $channel['channel_address'], urlencode($fr_server));
				sleep(3);
			}
		}
		if($total) {
			set_pconfig(local_user(),'frphotos','complete','1');
		}
		@unlink($cookies);
		goaway(z_root() . '/photos/' . $channel['channel_address']);
	}
}


function frphotos_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied') . EOL);
		return;
	}

	if(intval(get_pconfig(local_user(),'frphotos','complete'))) {
		info('Friendica photos have already been imported into this channel.');
		return;
	}

	$o = replace_macros(get_markup_template('frphotos.tpl'),array( 
		'$header' => t('Friendica Photo Album Import'),
		'$desc' => t('This will import all your Friendica photo albums to this Red channel.'),
		'$fr_server' => array('fr_server', t('Friendica Server base URL'),'',''),
		'$fr_username' => array('fr_username', t('Friendica Login Username'),'',''),
		'$fr_password' => array('fr_password', t('Friendica Login Password'),'',''),
		'$submit' => t('Submit'),
	));
	return $o;
}
