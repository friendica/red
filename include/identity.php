<?php



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