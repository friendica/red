<?php /** @file */

use Sabre\DAV;
require_once('vendor/autoload.php');

require_once('include/attach.php');

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


class RedDirectory extends DAV\Node implements DAV\ICollection {

	private $red_path;
	private $folder_hash;
	private $ext_path;
	private $root_dir = '';
	private $auth;


	function __construct($ext_path,&$auth_plugin) {
		logger('RedDirectory::__construct() ' . $ext_path);
		$this->ext_path = $ext_path;
		$this->red_path = ((strpos($ext_path,'/cloud') === 0) ? substr($ext_path,6) : $ext_path);
		if(! $this->red_path)
			$this->red_path = '/';
		$this->auth = $auth_plugin;
		logger('Red_Directory: ' . print_r($this,true));
		$this->folder_hash = '';

		$this->getDir();
		if($this->auth->browser)
			$this->auth->browser->set_writeable();

	}

	function getChildren() {

		logger('RedDirectory::getChildren : ' . print_r($this,true));

		if(get_config('system','block_public') && (! $this->auth->channel_id) && (! $this->auth->observer)) {
			throw new DAV\Exception\Forbidden('Permission denied.');
			return;
		}

		if(($this->auth->owner_id) && (! perm_is_allowed($this->auth->owner_id,$this->auth->observer,'view_storage'))) {
			throw new DAV\Exception\Forbidden('Permission denied.');
			return;
		}

		$contents =  RedCollectionData($this->red_path,$this->auth);
		return $contents;
	}


	function getChild($name) {


		logger('RedDirectory::getChild : ' . $name);
		logger('RedDirectory::getChild : ' . print_r($this,true));

		if(get_config('system','block_public') && (! $this->auth->channel_id) && (! $this->auth->observer)) {
			throw new DAV\Exception\Forbidden('Permission denied.');
			return;
		}
 
		if(($this->auth->owner_id) && (! perm_is_allowed($this->auth->owner_id,$this->auth->observer,'view_storage'))) {
			throw new DAV\Exception\Forbidden('Permission denied.');
			return;
		}

		if($this->red_path === '/' && $name === 'cloud') {
			return new RedDirectory('/cloud', $this->auth);
		}

		$x = RedFileData($this->ext_path . '/' . $name, $this->auth);
		logger('RedFileData returns: ' . print_r($x,true));
		if($x)
			return $x;
		throw new DAV\Exception\NotFound('The file with name: ' . $name . ' could not be found');
		
	}

	function getName() {
		logger('RedDirectory::getName : ' . print_r($this,true));
		logger('RedDirectory::getName returns: ' . basename($this->red_path));

		return (basename($this->red_path));
	}




	function createFile($name,$data = null) {
		logger('RedDirectory::createFile : ' . $name);
		logger('RedDirectory::createFile : ' . print_r($this,true));

//		logger('createFile():' . stream_get_contents($data));


		if(! $this->auth->owner_id) {
			logger('createFile: permission denied');
			throw new DAV\Exception\Forbidden('Permission denied.');
			return;
		}

		if(! perm_is_allowed($this->auth->owner_id,$this->auth->observer,'write_storage')) {
			logger('createFile: permission denied');
			throw new DAV\Exception\Forbidden('Permission denied.');
			return;
		}

		$mimetype = z_mime_content_type($name);


		$c = q("select * from channel where channel_id = %d limit 1",
			intval($this->auth->channel_id)
		);


		$filesize = 0;
		$hash = random_string();

        $r = q("INSERT INTO attach ( aid, uid, hash, filename, folder, filetype, filesize, revision, data, created, edited, allow_cid, allow_gid, deny_cid, deny_gid )
            VALUES ( %d, %d, '%s', '%s', '%s', '%s', %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) ",
            intval($c[0]['channel_account_id']),
            intval($c[0]['channel_id']),
            dbesc($hash),
            dbesc($name),
			dbesc($this->folder_hash),
            dbesc($mimetype),
            intval($filesize),
            intval(0),
            dbesc(stream_get_contents($data)),
            dbesc(datetime_convert()),
            dbesc(datetime_convert()),
			dbesc($c[0]['channel_allow_cid']),
			dbesc($c[0]['channel_allow_gid']),
			dbesc($c[0]['channel_deny_cid']),
			dbesc($c[0]['channel_deny_gid'])


		);

		$r = q("update attach set filesize = length(data) where hash = '%s' and uid = %d limit 1",
			dbesc($hash),
			intval($c[0]['channel_id'])
		);

		$r = q("select filesize from attach where hash = '%s' and uid = %d limit 1",
			dbesc($hash),
			intval($c[0]['channel_id'])
		);

		// FIXME - delete attached file resource if using OS storage

		$maxfilesize = get_config('system','maxfilesize');

		if(($maxfilesize) && ($r[0]['filesize'] > $maxfilesize)) {
			q("delete from attach where hash = '%s' and uid = %d limit 1",
				dbesc($hash),
				intval($c[0]['channel_id'])
			);
			return;
		}

		$limit = service_class_fetch($c[0]['channel_id'],'attach_upload_limit');
		if($limit !== false) {
			$x = q("select sum(filesize) as total from attach where uid = %d ",
				intval($c[0]['channel_id'])
			);
			if(($x) &&  ($x[0]['total'] + $r[0]['filesize'] > $limit)) {
				q("delete from attach where hash = '%s' and uid = %d limit 1",
					dbesc($hash),
					intval($c[0]['channel_id'])
				);
				return;
			}
		}
	}


	function createDirectory($name) {

		logger('RedDirectory::createDirectory: ' . $name);

		if((! $this->auth->owner_id) || (! perm_is_allowed($this->auth->owner_id,$this->auth->observer,'write_storage'))) {
			throw new DAV\Exception\Forbidden('Permission denied.');
			return;
		}

		$r = q("select * from channel where channel_id = %d limit 1",
			dbesc($this->auth->owner_id)
		);

		if($r) {
			$result = attach_mkdir($r[0],$this->auth->observer,array('filename' => $name,'folder' => $this->folder_hash));

			logger('RedDirectory::createDirectory: ' . print_r($result,true));

		}







	}


	function childExists($name) {

		logger('RedDirectory::childExists : ' . print_r($this->auth,true));

		if($this->red_path === '/' && $name === 'cloud') {
			logger('RedDirectory::childExists /cloud: true');
			return true;
		}

		$x = RedFileData($this->ext_path . '/' . $name, $this->auth,true);
		logger('RedFileData returns: ' . print_r($x,true));
		if($x)
			return true;
		return false;

	}

	function getDir() {

		logger('getDir: ' . $this->ext_path);
		$file = $this->ext_path;


		$x = strpos($file,'/cloud');
		if($x === false)
			return;
		if($x === 0) {
			$file = substr($file,6);
		}

		if((! $file) || ($file === '/')) {
			return;
		}

		$file = trim($file,'/');
		$path_arr = explode('/', $file);
	
		if(! $path_arr)
			return;


		logger('getDir(): path: ' . print_r($path_arr,true));

		$channel_name = $path_arr[0];

		$r = q("select channel_id from channel where channel_address = '%s' limit 1",
			dbesc($channel_name)
		);

		if(! $r)
			return;

		$channel_id = $r[0]['channel_id'];
		$this->auth->owner_id = $channel_id;

		$path = '/' . $channel_name;

		$folder = '';

		for($x = 1; $x < count($path_arr); $x ++) {		

			$r = q("select id, hash, filename, flags from attach where folder = '%s' and filename = '%s' and (flags & %d)",
				dbesc($folder),
				dbesc($path_arr[$x]),
				intval($channel_id),
				intval(ATTACH_FLAG_DIR)
			);

			if($r && ( $r[0]['flags'] & ATTACH_FLAG_DIR)) {
				$folder = $r[0]['hash'];
				$path = $path . '/' . $r[0]['filename'];
			}	
		}
		$this->folder_hash = $folder;
		return;
	}





}


class RedFile extends DAV\Node implements DAV\IFile {

	private $data;
	private $auth;
	private $name;

	function __construct($name, $data, &$auth) {
		logger('RedFile::_construct: ' . $name);
		$this->name = $name;
		$this->data = $data;
		$this->auth = $auth;

		logger('RedFile::_construct: ' . print_r($this->data,true));
	}


	function getName() {
		logger('RedFile::getName: ' . basename($this->name));
		return basename($this->name);

	}


	function setName($newName) {
		logger('RedFile::setName: ' . basename($this->name) . ' -> ' . $newName);

		if((! $newName) || (! $this->auth->owner_id) || (! perm_is_allowed($this->auth->owner_id,$this->auth->observer,'write_storage'))) {
			throw new DAV\Exception\Forbidden('Permission denied.');
			return;
		}

		$newName = str_replace('/','%2F',$newName);

		$r = q("update attach set filename = '%s' where hash = '%s' and id = %d limit 1",
			dbesc($this->data['filename']),
			intval($this->data['id'])
		);

	}



	function put($data) {
		logger('RedFile::put: ' . basename($this->name));
//		logger('put():' . stream_get_contents($data));

		$r = q("update attach set data = '%s' where hash = '%s' and uid = %d limit 1",
			dbesc(stream_get_contents($data)),
			dbesc($this->data['hash']),
			intval($this->data['uid'])
		);
		$r = q("update attach set filesize = length(data) where hash = '%s' and uid = %d limit 1",
			dbesc($this->data['hash']),
			intval($this->data['uid'])
		);

		$r = q("select filesize from attach where hash = '%s' and uid = %d limit 1",
			dbesc($this->data['hash']),
			intval($c[0]['channel_id'])
		);

		$maxfilesize = get_config('system','maxfilesize');

		if(($maxfilesize) && ($r[0]['filesize'] > $maxfilesize)) {
			q("delete from attach where hash = '%s' and uid = %d limit 1",
				dbesc($this->data['hash']),
				intval($c[0]['channel_id'])
			);
			return;
		}

		$limit = service_class_fetch($c[0]['channel_id'],'attach_upload_limit');
		if($limit !== false) {
			$x = q("select sum(filesize) as total from attach where uid = %d ",
				intval($c[0]['channel_id'])
			);
			if(($x) &&  ($x[0]['total'] + $r[0]['filesize'] > $limit)) {
				q("delete from attach where hash = '%s' and uid = %d limit 1",
					dbesc($this->data['hash']),
					intval($c[0]['channel_id'])
				);
				return;
			}
		}
	}


	function get() {
		logger('RedFile::get: ' . basename($this->name));

		$r = q("select data from attach where hash = '%s' and uid = %d limit 1",
			dbesc($this->data['hash']),
			intval($this->data['uid'])
		);
		if($r) return $r[0]['data'];

	}

	function getETag() {
		logger('RedFile::getETag: ' . basename($this->name));
		return $this->data['hash'];

	}


	function getContentType() {
		return $this->data['filetype'];
	}


	function getSize() {
		return $this->data['filesize'];
	}


	function getLastModified() {
		logger('RedFile::getLastModified: ' . basename($this->name));
		return $this->data['edited'];
	}


}

function RedChannelList(&$auth) {

	$ret = array();

	$r = q("select channel_id, channel_address from channel where not (channel_pageflags & %d)",
		intval(PAGE_REMOVED)
	);

	if($r) {
		foreach($r as $rr) {
			if(perm_is_allowed($rr['channel_id'],$auth->observer,'view_storage')) {
				$ret[] = new RedDirectory('/cloud/' . $rr['channel_address'],$auth);
			}
		}
	}
	return $ret;

}


function RedCollectionData($file,&$auth) {

	$ret = array();

	$x = strpos($file,'/cloud');
	if($x === 0) {
		$file = substr($file,6);
	}


logger('RedCollectionData: ' . $file); 

	if((! $file) || ($file === '/')) {
		return RedChannelList($auth);

	}

	$file = trim($file,'/');
	$path_arr = explode('/', $file);
	
	if(! $path_arr)
		return null;

	$channel_name = $path_arr[0];

	$r = q("select channel_id from channel where channel_address = '%s' limit 1",
		dbesc($channel_name)
	);

logger('dbg1: ' . print_r($r,true));

	if(! $r)
		return null;

	$channel_id = $r[0]['channel_id'];
	$auth->owner_id = $channel_id;

	$path = '/' . $channel_name;

	$folder = '';

	for($x = 1; $x < count($path_arr); $x ++) {		
		$r = q("select id, hash, filename, flags from attach where folder = '%s' and filename = '%s' and (flags & %d)",
			dbesc($folder),
			dbesc($path_arr[$x]),
			intval(ATTACH_FLAG_DIR)
		);
		if($r && ( $r[0]['flags'] & ATTACH_FLAG_DIR)) {
			$folder = $r[0]['hash'];
			$path = $path . '/' . $r[0]['filename'];
		}	
	}

logger('dbg2: ' . print_r($r,true));

	if($path !== '/' . $file) {
		logger("RedCollectionData: Path mismatch: $path !== /$file");
		return NULL;
	}

	$ret = array();


	$r = q("select id, uid, hash, filename, filetype, filesize, revision, folder, flags, created, edited from attach where folder = '%s' and uid = %d group by filename",
		dbesc($folder),
		intval($channel_id)
	);

logger('dbg2: ' . print_r($r,true));

	foreach($r as $rr) {
		if($rr['flags'] & ATTACH_FLAG_DIR)
			$ret[] = new RedDirectory('/cloud' . $path . '/' . $rr['filename'],$auth);
		else
			$ret[] = new RedFile('/cloud' . $path . '/' . $rr['filename'],$rr,$auth);
	}

	return $ret;

}

function RedFileData($file, &$auth,$test = false) {

logger('RedFileData:' . $file . (($test) ? ' (test mode) ' : ''));


	$x = strpos($file,'/cloud');
	if($x === 0) {
		$file = substr($file,6);
	}

logger('RedFileData2: ' . $file);

	if((! $file) || ($file === '/')) {
		return RedDirectory('/',$auth);

	}

	$file = trim($file,'/');

logger('file=' . $file);

	$path_arr = explode('/', $file);
	
	if(! $path_arr)
		return null;

	logger("file = $file - path = " . print_r($path_arr,true));

	$channel_name = $path_arr[0];


	$r = q("select channel_id from channel where channel_address = '%s' limit 1",
		dbesc($channel_name)
	);

	logger('dbg0: ' . print_r($r,true));

	if(! $r)
		return null;

	$channel_id = $r[0]['channel_id'];

	$path = '/' . $channel_name;

	$auth->owner_id = $channel_id;

	$permission_error = false;


	$folder = '';
//dbg(1);

	require_once('include/security.php');
	$perms = permissions_sql($channel_id);

	$errors = false;

	for($x = 1; $x < count($path_arr); $x ++) {		
dbg(1);
		$r = q("select id, hash, filename, flags from attach where folder = '%s' and filename = '%s' and uid = %d and (flags & %d) $perms",
			dbesc($folder),
			dbesc($path_arr[$x]),
			intval($channel_id),
			intval(ATTACH_FLAG_DIR)
		);
dbg(0);
	logger('dbg1: ' . print_r($r,true));

		if($r && ( $r[0]['flags'] & ATTACH_FLAG_DIR)) {
			$folder = $r[0]['hash'];
			$path = $path . '/' . $r[0]['filename'];
		}	
		if(! $r) {
			$r = q("select id, uid, hash, filename, filetype, filesize, revision, folder, flags, created, edited from attach 
				where folder = '%s' and filename = '%s' and uid = %d $perms group by filename limit 1",
				dbesc($folder),
				basename($file),
				intval($channel_id)

			);
		}
		if(! $r) {

			$errors = true;
			$r = q("select id, uid, hash, filename, filetype, filesize, revision, folder, flags, created, edited from attach 
				where folder = '%s' and filename = '%s' and uid = %d group by filename limit 1",
				dbesc($folder),
				basename($file),
				intval($channel_id)
			);
			if($r)
				$permission_error = true;

		}

	}

	logger('dbg1: ' . print_r($r,true));

	if($path === '/' . $file) {
		if($test)
			return true;
		// final component was a directory.
		return new RedDirectory('/cloud/' . $file,$auth);
	}

	if($errors) {
		if($test)
			return false;
		if($permission_error) {
			logger('RedFileData: permission error');	
			throw new DAV\Exception\Forbidden('Permission denied.');
		}
		logger('RedFileData: not found');
		return;
	}

	if($r) {
		if($test)
			return true;

		if($r[0]['flags'] & ATTACH_FLAG_DIR)
			return new RedDirectory('/cloud' . $path . '/' . $r[0]['filename'],$auth);
		else
			return new RedFile('/cloud' . $path . '/' . $r[0]['filename'],$r[0],$auth);
	}
	return false;
}


class RedBasicAuth extends Sabre\DAV\Auth\Backend\AbstractBasic {

	public $channel_name = '';
	public $channel_id = 0;
	public $channel_hash = '';
	public $observer = '';
	public $browser;
	public $owner_id;

    protected function validateUserPass($username, $password) {
		require_once('include/auth.php');
		$record = account_verify_password($email,$pass);
		if($record && $record['account_default_channel']) {
			$r = q("select * from channel where channel_account_id = %d and channel_id = %d limit 1",
				intval($record['account_id']),
				intval($record['account_default_channel'])
			);
			if($r) {
				$this->currentUser = $r[0]['channel_address'];
				$this->channel_name = $r[0]['channel_address'];
				$this->channel_id = $r[0]['channel_id'];
				$this->channel_hash = $this->observer = $r[0]['channel_hash'];
				return true;
			}
		}
		$r = q("select * from channel where channel_address = '%s' limit 1",
			dbesc($username)
		);
		if($r) {
			$x = q("select * from account where account_id = %d limit 1",
				intval($r[0]['channel_account_id'])
			);
			if($x) {
			    foreach($x as $record) {
			        if(($record['account_flags'] == ACCOUNT_OK) || ($record['account_flags'] == ACCOUNT_UNVERIFIED)
            		&& (hash('whirlpool',$record['account_salt'] . $password) === $record['account_password'])) {
			            logger('(DAV) RedBasicAuth: password verified for ' . $username);
						$this->currentUser = $r[0]['channel_address'];
						$this->channel_name = $r[0]['channel_address'];
						$this->channel_id = $r[0]['channel_id'];
						$this->channel_hash = $this->observer = $r[0]['channel_hash'];
            			return true;
        			}
    			}
			}
		}
	    logger('(DAV) RedBasicAuth: password failed for ' . $username);
    	return false;
	}

	function setCurrentUser($name) {
		$this->currentUser = $name;
	}

	function setBrowserPlugin($browser) {
		$this->browser = $browser;
	}
		
}


class RedBrowser extends DAV\Browser\Plugin {

	private $auth;

	function __construct(&$auth) {

		$this->auth = $auth;


	}

	function set_writeable() {
		logger('RedBrowser: ' . print_r($this->auth,true));

		if(! $this->auth->owner_id)
			$this->enablePost = false;


		if(! perm_is_allowed($this->auth->owner_id, get_observer_hash(), 'write_storage'))
			$this->enablePost = false;
		else
			$this->enablePost = true;

	}

}