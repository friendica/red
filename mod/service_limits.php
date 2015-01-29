<?php /** @file */


function service_limits_content(&$a) {

	if(! local_channel()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$account = $a->get_account();
	if($account['account_service_class']) {
		$x =  get_config('service_class',$account['account_service_class']);
		if($x) {
			$o = print_r($x,true);
			return $o;
		}
	}
	return t('No service class restrictions found.');
}
		

		