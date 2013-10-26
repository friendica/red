<?php /** @file */

use Sabre\DAV;
    require_once('vendor/autoload.php');

class RedInode implements DAV\INode {

	private $attach;

	function __construct($attach) {
		$this->attach = $attach;
	}


	function delete() {

	}

	function getName() {
		return $this->attach['filename'];
	}

	function setName($newName) {
		$this->attach['filename'] = $newName;
		// FIXME save the DB record
	}

	function getLastModified() {
		return $this->attach['edited'];
	}

}


abstract class RedDirectory extends DAV\Node implements DAV\ICollection {

	private $red_path;
	private $dir_key;
	private $auth;
	private $channel_id;

	function __construct($red_path,$auth_plugin) {
		$this->red_path = $red_path;
		$this->auth = $auth_plugin;
	}

	function getChildren() {

		if(! perm_is_allowed($this->channel_id,'','view_storage'))
			return array();

		$ret = array();
		$r = q("select distinct filename from attach where folder = '%s' and uid = %d group by filename",
			dbesc($this->dir_key),
			intval($this->channel_id)
		);
		if($r) {
			foreach($r as $rr) {
				$ret[] = $rr['filename'];
			}
		}
		return $ret;

	}


	function getChild($name) {
		if(! perm_is_allowed($this->channel_id,'','view_storage')) {
//check this			throw new DAV\Exception\PermissionDenied('Permission denied.');
			return;
		}

		$r = q("select * from attach where folder = '%s' and filename = '%s' and uid = %d limit 1",
			dbesc($this->dir_key),
			dbesc($name),
			dbesc($this->channel_id)
		);
		if(! $r) {
			throw new DAV\Exception\NotFound('The file with name: ' . $name . ' could not be found');
      	}

		
	}


	function createFile($name,$data = null) {


	}

	function createDirectory($name) {



	}


	function childExists($name) {
		$r = q("select distinct filename from attach where folder = '%s' and filename = '%s' and uid = %d group by filename",
			dbesc($this->dir_key),
			dbesc($name),
			intval($this->channel_id)
		);


	}

}


abstract class RedFile extends DAV\Node implements DAV\IFile {

	private $data;


	function __construct($data) {
		$this->data = $data;

	}



	function put($data) {

	}


	function get() {


	}

	function getETag() {



	}


	function getContentType() {
		return $this->data['filetype'];
	}


	function getSize() {
		return $this->data['filesize'];
	}

}





