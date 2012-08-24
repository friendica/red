<?php

require_once('include/zot.php');
require_once('include/crypto.php');

function create_identity($arr) {

	$ret = array('success' => false, 'message' => '');
	$nick = trim($_POST['nickname']);
	$name = escape_tags($_POST['name']);

	if(check_webbie(array($nick)) !== $nick) {
		$ret['message'] = t('Nickname has unsupported characters or is already being used on this site.');
		return $ret;
	}

	$guid = zot_new_uid($nick);
	$key = new_keypair(4096);

	$primary = true;
		
	$r = q("insert into entity ( entity_account_id, entity_primary, 
		entity_name, entity_address, entity_global_id, entity_prvkey,
		entity_pubkey, entity_pageflags )
		values ( %d, %d, '%s', '%s', '%s', '%s', '%s', %d ) ",

		intval(local_user()),
		intval($primary),
		dbesc($name),
		dbesc($nick),
		dbesc($guid),
		dbesc($key['prvkey']),
		dbesc($key['pubkey']),
		intval(PAGE_NORMAL)
	);
			
	$r = q("select * from entity where entity_account_id = %d 
		and entity_global_id = '%s' limit 1",
		intval(local_user()),
		dbesc($guid)
	);
	if(! ($r && count($r))) {
		$ret['message'] = t('Unable to retrieve created identity');
		return $ret;
	}
	$ret['entity'] = $r[0];
	$ret['success'] = true;
	return $ret;

}






class simple_identity {

	private $identity_uid;
	private $identity_name;
	private $identity_url;
	private $identity_photo;

	function __construct($uid = '',$name = '',$url = '',$photo = '') {
		$this->identity_uid    = $uid;
		$this->identity_name   = $name;
		$this->identity_url    = $url;
		$this->identity_photo  = $photo;
	}

	function to_array() {
		return array(
			'zuid'  => $this->identity_uid,
			'name'  => $this->identity_name,
			'url'   => $this->identity_url,
			'photo' => $this->identity_photo
		);
	}
}