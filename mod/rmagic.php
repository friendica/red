<?php


function rmagic_init(&$a) {

	if(local_user())
		goaway(z_root());

	$me = get_my_address();
	if($me) {
		$r = q("select hubloc_url from hubloc where hubloc_addr = '%s' limit 1",
			dbesc($me)
		);		
		if($r) {	
			if($r[0]['hubloc_url'] === z_root())
				goaway(z_root() . '/login');
			$dest = z_root() . '/' . str_replace('zid=','zid_=',$get_app()->query_string);
			goaway($r[0]['hubloc_url'] . '/magic' . '?f=&dest=' . $dest);
		}
	}
}

function rmagic_post(&$a) {

	$address = $_REQUEST['address'];
	if(strpos($address,'@') === false) {
		notice('Invalid address.');
		return;
	}

	$r = null;
	if($address) {
		$r = q("select hubloc_url from hubloc where hubloc_addr = '%s' limit 1",
			dbesc($address)
		);		
	}
	if($r) {
		$url = $r[0]['hubloc_url'];
	}
	else {
		$url = 'https://' . substr($address,strpos($address,'@')+1);
	}	

	if($url) {	
		$dest = z_root() . '/' . str_replace('zid=','zid_=',$a->query_string);
		goaway($url . '/magic' . '?f=&dest=' . $dest);
	}


}


function rmagic_content(&$a) {

	$o = replace_macros(get_markup_template('rmagic.tpl'),array(
		'$title' => t('Remote Authentication'),
		'$desc' => t('Enter your channel address (e.g. channel@example.com)'),
		'$submit' => t('Authenticate')
	));
	return $o;

}