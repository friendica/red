<?php /** @file */

use Sabre\DAV;
    require_once('vendor/autoload.php');

class RedInode implements DAV\INode {

	private $attach;

	function __construct($attach) {
		$this->attach = $attach;
	}


	function delete() {
		if(! perm_is_allowed($this->channel_id,'','view_storage'))
			return;

		/**
		 * Since I don't believe this is documented elsewhere -
		 * ATTACH_FLAG_OS means that the file contents are stored in the OS
		 * rather than in the DB - as is the case for attachments.
		 * Exactly how they are stored (what path and filename) are still
		 * TBD. We will probably not be using the original filename but 
		 * instead the attachment 'hash' as this will prevent folks from 
		 * uploading PHP code onto misconfigured servers and executing it.
		 * It's easy to misconfigure servers because we can provide a 
		 * rule for Apache, but folks using nginx will then be susceptible.
		 * Then there are those who don't understand these kinds of exploits
		 * and don't have any idea allowing uploaded PHP files to be executed
		 * by the server could be a problem. We also don't have any idea what
		 * executable types are served on their system - like .py, .pyc, .pl, .sh
		 * .cgi, .exe, .bat, .net, whatever.  
		 */

		if($this->attach['flags'] & ATTACH_FLAG_OS) {
			// FIXME delete physical file
		}
		if($this->attach['flags'] & ATTACH_FLAG_DIR) {
			// FIXME delete contents (recursive?)
		}
		
		q("delete from attach where id = %d limit 1",
			intval($this->attach['id'])
		);

	}

	function getName() {
		return $this->attach['filename'];
	}

	function setName($newName) {

		if((! $newName) || (! perm_is_allowed($this->channel_id,'','view_storage')))
			return;

		$this->attach['filename'] = $newName;
		$r = q("update attach set filename = '%s' where id = %d limit 1",
			dbesc($this->attach['filename']),
			intval($this->attach['id'])
		);

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
			throw new DAV\Exception\Forbidden('Permission denied.');
			return;
		}

// FIXME check revisions

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
		if($r)
			return true;
		return false;

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





