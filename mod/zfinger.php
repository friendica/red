<?php

function zfinger_init(&$a) {

	require_once('include/zot.php');
	require_once('include/crypto.php');

	$ret = array('success' => false);

	$zhash     = ((x($_REQUEST,'guid_hash'))  ? $_REQUEST['guid_hash']   : '');
	$zguid     = ((x($_REQUEST,'guid'))       ? $_REQUEST['guid']        : '');
	$zguid_sig = ((x($_REQUEST,'guid_sig'))   ? $_REQUEST['guid_sig']    : '');
	$zaddr     = ((x($_REQUEST,'address'))    ? $_REQUEST['address']     : '');
	$ztarget   = ((x($_REQUEST,'target'))     ? $_REQUEST['target']      : '');
	$zsig      = ((x($_REQUEST,'target_sig')) ? $_REQUEST['target_sig']  : '');
	$zkey      = ((x($_REQUEST,'key'))        ? $_REQUEST['key']         : '');
	$mindate   = ((x($_REQUEST,'mindate'))    ? $_REQUEST['mindate']     : '');
	$feed      = ((x($_REQUEST,'feed'))       ? intval($_REQUEST['feed']) : 0);

	if($ztarget) {
		if((! $zkey) || (! $zsig) || (! rsa_verify($ztarget,base64url_decode($zsig),$zkey))) {
			logger('zfinger: invalid target signature');
			$ret['message'] = t("invalid target signature");
			json_return_and_die($ret);
		}
	}

	// allow re-written domains so bob@foo.example.com can provide an address of bob@example.com
	// The top-level domain also needs to redirect .well-known/zot-info to the sub-domain with a 301 or 308

	// TODO: Make 308 work in include/network.php for zot_fetch_url and zot_post_url

	if(($zaddr) && ($s = get_config('system','zotinfo_domainrewrite'))) {
		$arr = explode('^',$s);
		if(count($arr) == 2) 
			$zaddr = str_replace($arr[0],$arr[1],$zaddr);
	}

	$r = null;

	if(strlen($zhash)) {
		$r = q("select channel.*, xchan.* from channel left join xchan on channel_hash = xchan_hash 
			where channel_hash = '%s' limit 1",
			dbesc($zhash)
		);
	}
	elseif(strlen($zguid) && strlen($zguid_sig)) {
		$r = q("select channel.*, xchan.* from channel left join xchan on channel_hash = xchan_hash 
			where channel_guid = '%s' and channel_guid_sig = '%s' limit 1",
			dbesc($zguid),
			dbesc($zguid_sig)
		);
	}
	elseif(strlen($zaddr)) {
		$r = q("select channel.*, xchan.* from channel left join xchan on channel_hash = xchan_hash
			where ( channel_address = '%s' or xchan_addr = '%s' ) limit 1",
			dbesc($zaddr),
			dbesc($zaddr)
		);
	}
	else {
		$ret['message'] = 'Invalid request';
		json_return_and_die($ret);
	}

	if(! $r) {
		$ret['message'] = 'Item not found.';
		json_return_and_die($ret);
	}

	$e = $r[0];

	$id = $e['channel_id'];

	$special_channel = (($e['channel_pageflags'] & PAGE_PREMIUM) ? true : false);
	$adult_channel   = (($e['channel_pageflags'] & PAGE_ADULT)   ? true : false);
	$searchable      = (($e['channel_pageflags'] & PAGE_HIDDEN)  ? false : true);
	if($e['xchan_flags'] & XCHAN_FLAGS_HIDDEN)
		$searchable = false;
	 
	//  This is for birthdays and keywords, but must check access permissions
	$p = q("select * from profile where uid = %d and is_default = 1",
		intval($e['channel_id'])
	);

	$profile = array();

	if($p) {

		if(! intval($p[0]['publish']))
			$searchable = false; 

		$profile['description']   = $p[0]['pdesc'];
		$profile['birthday']      = $p[0]['dob'];
		if($profile['birthday'] != '0000-00-00')
			$profile['next_birthday'] = z_birthday($p[0]['dob'],$e['channel_timezone']);

		if($age = age($p[0]['dob'],$e['channel_timezone'],''))  
			$profile['age'] = $age;
		$profile['gender']        = $p[0]['gender'];
		$profile['marital']       = $p[0]['marital'];
		$profile['sexual']        = $p[0]['sexual'];
		$profile['locale']        = $p[0]['locality'];
		$profile['region']        = $p[0]['region'];
		$profile['postcode']      = $p[0]['postal_code'];
		$profile['country']       = $p[0]['country_name'];
		if($p[0]['keywords']) {
			$tags = array();
			$k = explode(' ',$p[0]['keywords']);
			if($k) {
				foreach($k as $kk) {
					if(trim($kk)) {
						$tags[] = trim($kk," \t\n\r\0\x0B,");
					}
				}
			}
			if($tags)
				$profile['keywords'] = $tags;
		}
	}

	$ret['success'] = true;

	// Communication details

	$ret['guid']           = $e['xchan_guid'];
	$ret['guid_sig']       = $e['xchan_guid_sig'];
	$ret['key']            = $e['xchan_pubkey'];
	$ret['name']           = $e['xchan_name'];
	$ret['name_updated']   = $e['xchan_name_date'];
	$ret['address']        = $e['xchan_addr'];
	$ret['photo_mimetype'] = $e['xchan_photo_mimetype'];
	$ret['photo']          = $e['xchan_photo_l'];
	$ret['photo_updated']  = $e['xchan_photo_date'];
	$ret['url']            = $e['xchan_url'];
	$ret['connections_url']= (($e['xchan_connurl']) ? $e['xchan_connurl'] : z_root() . '/poco/' . $e['channel_address']);
	$ret['target']         = $ztarget;
	$ret['target_sig']     = $zsig;
	$ret['searchable']     = $searchable;
	$ret['adult_content']  = $adult_channel;
		

	// premium or other channel desiring some contact with potential followers before connecting.
	// This is a template - %s will be replaced with the follow_url we discover for the return channel.

	if($special_channel) 
		$ret['connect_url'] = z_root() . '/connect/' . $e['channel_address'];

	// This is a template for our follow url, %s will be replaced with a webbie

	$ret['follow_url'] = z_root() . '/follow?f=&url=%s';

	$permissions = get_all_perms($e['channel_id'],(($ztarget && $zsig) 
			? base64url_encode(hash('whirlpool',$ztarget . $zsig,true)) 
			: '' ),false);


	$ret['permissions'] = (($ztarget && $zkey) ? aes_encapsulate(json_encode($permissions),$zkey) : $permissions);

	if($permissions['view_profile'])
		$ret['profile']  = $profile;


	// array of (verified) hubs this channel uses

	$ret['locations'] = array();

	$x = zot_get_hublocs($e['channel_hash']);
	if($x && count($x)) {
		foreach($x as $hub) {
			if(! ($hub['hubloc_flags'] & HUBLOC_FLAGS_UNVERIFIED)) {
				$ret['locations'][] = array(
					'host'     => $hub['hubloc_host'],
					'address'  => $hub['hubloc_addr'],
					'primary'  => (($hub['hubloc_flags'] & HUBLOC_FLAGS_PRIMARY) ? true : false),
					'url'      => $hub['hubloc_url'],
					'url_sig'  => $hub['hubloc_url_sig'],
					'callback' => $hub['hubloc_callback'],
					'sitekey'  => $hub['hubloc_sitekey'],
					'deleted'  => (($hub['hubloc_flags'] & HUBLOC_FLAGS_DELETED) ? true : false)
				);
			}
		}
	}

	$ret['site'] = array();
	$ret['site']['url'] = z_root();
	$ret['site']['url_sig'] = base64url_encode(rsa_sign(z_root(),$e['channel_prvkey']));

	$dirmode = get_config('system','directory_mode');
	if(($dirmode === false) || ($dirmode == DIRECTORY_MODE_NORMAL))
		$ret['site']['directory_mode'] = 'normal';
	if($dirmode == DIRECTORY_MODE_PRIMARY)
		$ret['site']['directory_mode'] = 'primary';
	elseif($dirmode == DIRECTORY_MODE_SECONDARY)
		$ret['site']['directory_mode'] = 'secondary';
	elseif($dirmode == DIRECTORY_MODE_STANDALONE)
		$ret['site']['directory_mode'] = 'standalone';
	if($dirmode != DIRECTORY_MODE_NORMAL)
		$ret['site']['directory_url'] = z_root() . '/dirsearch';


	// hide detailed site information if you're off the grid

	if($dirmode != DIRECTORY_MODE_STANDALONE) {

		$register_policy = intval(get_config('system','register_policy'));


		if($register_policy == REGISTER_CLOSED)
			$ret['site']['register_policy'] = 'closed';
		if($register_policy == REGISTER_APPROVE)
			$ret['site']['register_policy'] = 'approve';
		if($register_policy == REGISTER_OPEN)
			$ret['site']['register_policy'] = 'open';


		$access_policy = intval(get_config('system','access_policy'));

		if($access_policy == ACCESS_PRIVATE)
			$ret['site']['access_policy'] = 'private';
		if($access_policy == ACCESS_PAID)
			$ret['site']['access_policy'] = 'paid';
		if($access_policy == ACCESS_FREE)
			$ret['site']['access_policy'] = 'free';
		if($access_policy == ACCESS_TIERED)
			$ret['site']['access_policy'] = 'tiered';

		require_once('include/account.php');
		$ret['site']['accounts'] = account_total();
	
		require_once('include/identity.php');
		$ret['site']['channels'] = channel_total();


		$ret['site']['version'] = RED_PLATFORM . ' ' . RED_VERSION . '[' . DB_UPDATE_VERSION . ']';

		$ret['site']['admin'] = get_config('system','admin_email');

		$visible_plugins = array();
		if(is_array($a->plugins) && count($a->plugins)) {
			$r = q("select * from addon where hidden = 0");
			if($r)
				foreach($r as $rr)
					$visible_plugins[] = $rr['name'];
		}

		$ret['site']['plugins'] = $visible_plugins;
		$ret['site']['sitehash'] = get_config('system','location_hash');
		$ret['site']['sitename'] = get_config('system','sitename');
		$ret['site']['sellpage'] = get_config('system','sellpage');
		$ret['site']['location'] = get_config('system','site_location');

	}
	call_hooks('zot_finger',$ret);
	json_return_and_die($ret);

}
