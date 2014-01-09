<?php /** @file */

use Sabre\DAV;
require_once('vendor/autoload.php');

require_once('include/attach.php');

class RedDirectory extends DAV\Node implements DAV\ICollection {

	private $red_path;
	private $folder_hash;
	private $ext_path;
	private $root_dir = '';
	private $auth;
	private $os_path = '';

	function __construct($ext_path,&$auth_plugin) {
		logger('RedDirectory::__construct() ' . $ext_path, LOGGER_DEBUG);
		$this->ext_path = $ext_path;
		$this->red_path = ((strpos($ext_path,'/cloud') === 0) ? substr($ext_path,6) : $ext_path);
		if(! $this->red_path)
			$this->red_path = '/';
		$this->auth = $auth_plugin;
		$this->folder_hash = '';

		$this->getDir();

		if($this->auth->browser)
			$this->auth->browser->set_writeable();

	}

	function getChildren() {

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

		logger('RedDirectory::getChild : ' . $name, LOGGER_DATA);


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
		if($x)
			return $x;

		throw new DAV\Exception\NotFound('The file with name: ' . $name . ' could not be found');
		
	}

	function getName() {
		logger('RedDirectory::getName returns: ' . basename($this->red_path), LOGGER_DATA);
		return (basename($this->red_path));
	}




	function createFile($name,$data = null) {
		logger('RedDirectory::createFile : ' . $name, LOGGER_DEBUG);

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
			intval($this->auth->owner_id)
		);


		if(! $c) {
			logger('createFile: no channel');
			throw new DAV\Exception\Forbidden('Permission denied.');
			return;
		}


		$filesize = 0;
		$hash = random_string();

        $r = q("INSERT INTO attach ( aid, uid, hash, filename, folder, flags, filetype, filesize, revision, data, created, edited, allow_cid, allow_gid, deny_cid, deny_gid )
            VALUES ( %d, %d, '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) ",
            intval($c[0]['channel_account_id']),
            intval($c[0]['channel_id']),
            dbesc($hash),
            dbesc($name),
			dbesc($this->folder_hash),
			dbesc(ATTACH_FLAG_OS),
            dbesc($mimetype),
            intval($filesize),
            intval(0),
            dbesc($this->os_path . '/' . $hash),
            dbesc(datetime_convert()),
            dbesc(datetime_convert()),
			dbesc($c[0]['channel_allow_cid']),
			dbesc($c[0]['channel_allow_gid']),
			dbesc($c[0]['channel_deny_cid']),
			dbesc($c[0]['channel_deny_gid'])


		);

		$f = 'store/' . $this->auth->owner_nick . '/' . (($this->os_path) ? $this->os_path . '/' : '') . $hash;

		file_put_contents($f, $data);
		$size = filesize($f);


		$r = q("update attach set filesize = '%s' where hash = '%s' and uid = %d limit 1",
			dbesc($size),
			dbesc($hash),
			intval($c[0]['channel_id'])
		);

		$maxfilesize = get_config('system','maxfilesize');

		if(($maxfilesize) && ($size > $maxfilesize)) {
			attach_delete($c[0]['channel_id'],$hash);
			return;
		}

		$limit = service_class_fetch($c[0]['channel_id'],'attach_upload_limit');
		if($limit !== false) {
			$x = q("select sum(filesize) as total from attach where aid = %d ",
				intval($c[0]['channel_account_id'])
			);
			if(($x) &&  ($x[0]['total'] + $size > $limit)) {
				attach_delete($c[0]['channel_id'],$hash);
				return;
			}
		}
	}


	function createDirectory($name) {

		logger('RedDirectory::createDirectory: ' . $name, LOGGER_DEBUG);

		if((! $this->auth->owner_id) || (! perm_is_allowed($this->auth->owner_id,$this->auth->observer,'write_storage'))) {
			throw new DAV\Exception\Forbidden('Permission denied.');
			return;
		}

		$r = q("select * from channel where channel_id = %d limit 1",
			dbesc($this->auth->owner_id)
		);

		if($r) {
			$result = attach_mkdir($r[0],$this->auth->observer,array('filename' => $name,'folder' => $this->folder_hash));
			if(! $result['success'])
				logger('RedDirectory::createDirectory: ' . print_r($result,true), LOGGER_DEBUG);
		}
	}


	function childExists($name) {

		if($this->red_path === '/' && $name === 'cloud') {
			logger('RedDirectory::childExists /cloud: true', LOGGER_DATA);
			return true;
		}

		$x = RedFileData($this->ext_path . '/' . $name, $this->auth,true);
		logger('RedFileData returns: ' . print_r($x,true), LOGGER_DATA);
		if($x)
			return true;
		return false;
	}

	function getDir() {
		logger('getDir: ' . $this->ext_path, LOGGER_DEBUG);

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
		$this->auth->owner_nick = $channel_name;

		$path = '/' . $channel_name;

		$folder = '';
		$os_path = '';

		for($x = 1; $x < count($path_arr); $x ++) {		

			$r = q("select id, hash, filename, flags from attach where folder = '%s' and filename = '%s' and (flags & %d)",
				dbesc($folder),
				dbesc($path_arr[$x]),
				intval($channel_id),
				intval(ATTACH_FLAG_DIR)
			);

			if($r && ( $r[0]['flags'] & ATTACH_FLAG_DIR)) {
				$folder = $r[0]['hash'];
				if(strlen($os_path))
					$os_path .= '/';
				$os_path .= $folder;

				$path = $path . '/' . $r[0]['filename'];
			}	
		}
		$this->folder_hash = $folder;
		$this->os_path = $os_path;
		return;
	}





}


class RedFile extends DAV\Node implements DAV\IFile {

	private $data;
	private $auth;
	private $name;

	function __construct($name, $data, &$auth) {
		$this->name = $name;
		$this->data = $data;
		$this->auth = $auth;

		logger('RedFile::_construct: ' . print_r($this->data,true), LOGGER_DATA);
	}


	function getName() {
		logger('RedFile::getName: ' . basename($this->name), LOGGER_DEBUG);
		return basename($this->name);

	}


	function setName($newName) {
		logger('RedFile::setName: ' . basename($this->name) . ' -> ' . $newName, LOGGER_DEBUG);

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
		logger('RedFile::put: ' . basename($this->name), LOGGER_DEBUG);


		$r = q("select flags, data from attach where hash = '%s' and uid = %d limit 1",
			dbesc($hash),
			intval($c[0]['channel_id'])
		);
		if($r) {
			if($r[0]['flags'] & ATTACH_FLAG_OS) {
				@file_put_contents($r[0]['data'], $data);
				$size = @filesize($r[0]['data']);
			}
			else {
				$r = q("update attach set data = '%s' where hash = '%s' and uid = %d limit 1",
					dbesc(stream_get_contents($data)),
					dbesc($this->data['hash']),
					intval($this->data['uid'])
				);
				$r = q("select length(data) as fsize from attach where hash = '%s' and uid = %d limit 1",
					dbesc($this->data['hash']),
					intval($this->data['uid'])
				);
				if($r)
					$size = $r[0]['fsize'];
			}
		}
 
		$r = q("update attach set filesize = '%s' where hash = '%s' and uid = %d limit 1",
			dbesc($size),
			dbesc($hash),
			intval($c[0]['channel_id'])
		);


		$maxfilesize = get_config('system','maxfilesize');

		if(($maxfilesize) && ($size > $maxfilesize)) {
			attach_delete($c[0]['channel_id'],$hash);
			return;
		}

		$limit = service_class_fetch($c[0]['channel_id'],'attach_upload_limit');
		if($limit !== false) {
			$x = q("select sum(filesize) as total from attach where aid = %d ",
				intval($c[0]['channel_account_id'])
			);
			if(($x) &&  ($x[0]['total'] + $size > $limit)) {
				attach_delete($c[0]['channel_id'],$hash);
				return;
			}
		}
	}


	function get() {
		logger('RedFile::get: ' . basename($this->name), LOGGER_DEBUG);

		$r = q("select data, flags from attach where hash = '%s' and uid = %d limit 1",
			dbesc($this->data['hash']),
			intval($this->data['uid'])
		);
		if($r) {
			if($r[0]['flags'] & ATTACH_FLAG_OS ) {
				$f = 'store/' . $this->auth->owner_nick . '/' . (($this->os_path) ? $this->os_path . '/' : '') . $r[0]['data'];
				return fopen($f,'rb');
			}
			return $r[0]['data'];
		}

	}

	function getETag() {
		return $this->data['hash'];
	}


	function getContentType() {
		return $this->data['filetype'];
	}


	function getSize() {
		return $this->data['filesize'];
	}


	function getLastModified() {
		return $this->data['edited'];
	}


	function delete() {
		if((! $this->auth->owner_id) || (! perm_is_allowed($this->auth->owner_id,$this->auth->observer,'write_storage'))) {
			throw new DAV\Exception\Forbidden('Permission denied.');
			return;
		}

		attach_delete($this->auth->owner_id,$this->data['hash']);
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

	if(! $r)
		return null;

	$channel_id = $r[0]['channel_id'];
	$perms = permissions_sql($channel_id);

	$auth->owner_id = $channel_id;

	$path = '/' . $channel_name;

	$folder = '';
	$errors = false;
	$permission_error = false;

	for($x = 1; $x < count($path_arr); $x ++) {		
		$r = q("select id, hash, filename, flags from attach where folder = '%s' and filename = '%s' and (flags & %d) $perms limit 1",
			dbesc($folder),
			dbesc($path_arr[$x]),
			intval(ATTACH_FLAG_DIR)
		);
		if(! $r) {
			// path wasn't found. Try without permissions to see if it was the result of permissions.
			$errors = true;
			$r = q("select id, hash, filename, flags from attach where folder = '%s' and filename = '%s' and (flags & %d) limit 1",
				dbesc($folder),
				basename($path_arr[$x]),
				intval(ATTACH_FLAG_DIR)
			);
			if($r) {
				$permission_error = true;
			}
			break;
		}

		if($r && ( $r[0]['flags'] & ATTACH_FLAG_DIR)) {
			$folder = $r[0]['hash'];
			$path = $path . '/' . $r[0]['filename'];
		}	
	}

	if($errors) {
		if($permission_error) {
			throw new DAV\Exception\Forbidden('Permission denied.');
			return;
		}
		else {
			throw new DAV\Exception\NotFound('A component of the request file path could not be found');
			return;
		}
	}

	// This should no longer be needed since we just returned errors for paths not found

	if($path !== '/' . $file) {
		logger("RedCollectionData: Path mismatch: $path !== /$file");
		return NULL;
	}

	$ret = array();

	$r = q("select id, uid, hash, filename, filetype, filesize, revision, folder, flags, created, edited from attach where folder = '%s' and uid = %d $perms group by filename",
		dbesc($folder),
		intval($channel_id)
	);

	foreach($r as $rr) {
		if($rr['flags'] & ATTACH_FLAG_DIR)
			$ret[] = new RedDirectory('/cloud' . $path . '/' . $rr['filename'],$auth);
		else
			$ret[] = new RedFile('/cloud' . $path . '/' . $rr['filename'],$rr,$auth);
	}

	return $ret;

}

function RedFileData($file, &$auth,$test = false) {

	logger('RedFileData:' . $file . (($test) ? ' (test mode) ' : ''), LOGGER_DEBUG);


	$x = strpos($file,'/cloud');
	if($x === 0) {
		$file = substr($file,6);
	}

	if((! $file) || ($file === '/')) {
		return RedDirectory('/',$auth);

	}

	$file = trim($file,'/');

	$path_arr = explode('/', $file);
	
	if(! $path_arr)
		return null;


	$channel_name = $path_arr[0];


	$r = q("select channel_id from channel where channel_address = '%s' limit 1",
		dbesc($channel_name)
	);

	if(! $r)
		return null;

	$channel_id = $r[0]['channel_id'];

	$path = '/' . $channel_name;

	$auth->owner_id = $channel_id;

	$permission_error = false;

	$folder = '';

	require_once('include/security.php');
	$perms = permissions_sql($channel_id);

	$errors = false;

	for($x = 1; $x < count($path_arr); $x ++) {		
		$r = q("select id, hash, filename, flags from attach where folder = '%s' and filename = '%s' and uid = %d and (flags & %d) $perms",
			dbesc($folder),
			dbesc($path_arr[$x]),
			intval($channel_id),
			intval(ATTACH_FLAG_DIR)
		);

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

	if($path === '/' . $file) {
		if($test)
			return true;
		// final component was a directory.
		return new RedDirectory('/cloud/' . $file,$auth);
	}

	if($errors) {
		logger('RedFileData: not found');
		if($test)
			return false;
		if($permission_error) {
			logger('RedFileData: permission error');	
			throw new DAV\Exception\Forbidden('Permission denied.');
		}
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
	public $owner_nick = '';

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

	// The DAV browser is instantiated after the auth module and directory classes but before we know the current
	// directory and who the owner and observer are. So we add a pointer to the browser into the auth module and vice 
	// versa. Then when we've figured out what directory is actually being accessed, we call the following function
	// to decide whether or not to show web elements which include writeable objects.


	function set_writeable() {

		if(! $this->auth->owner_id)
			$this->enablePost = false;

		if(! perm_is_allowed($this->auth->owner_id, get_observer_hash(), 'write_storage'))
			$this->enablePost = false;
		else
			$this->enablePost = true;

	}
}