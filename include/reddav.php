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


		$c = q("select * from channel where channel_id = %d and not (channel_pageflags & %d) limit 1",
			intval($this->auth->owner_id),
			intval(PAGE_REMOVED)

		);

		if(! $c) {
			logger('createFile: no channel');
			throw new DAV\Exception\Forbidden('Permission denied.');
			return;
		}


		$filesize = 0;
		$hash = random_string();

        $r = q("INSERT INTO attach ( aid, uid, hash, creator, filename, folder, flags, filetype, filesize, revision, data, created, edited, allow_cid, allow_gid, deny_cid, deny_gid )
            VALUES ( %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) ",
            intval($c[0]['channel_account_id']),
            intval($c[0]['channel_id']),
            dbesc($hash),
			dbesc($this->auth->observer),
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

		$edited = datetime_convert(); 

		$d = q("update attach set filesize = '%s', edited = '%s' where hash = '%s' and uid = %d limit 1",
			dbesc($size),
			dbesc($edited),
			dbesc($hash),
			intval($c[0]['channel_id'])
		);

		$e = q("update attach set edited = '%s' where folder = '%s' and uid = %d limit 1",
			dbesc($edited),
			dbesc($this->folder_hash),
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
				logger('reddav: service class limit exceeded for ' . $c[0]['channel_name'] . ' total usage is ' . $x[0]['total'] . ' limit is ' . $limit);
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

		$r = q("select * from channel where channel_id = %d and not (channel_pageflags & %d) limit 1",
			intval(PAGE_REMOVED),
			intval($this->auth->owner_id)
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


		$r = q("select channel_id from channel where channel_address = '%s' and not ( channel_pageflags & %d ) limit 1",
			dbesc($channel_name),
			intval(PAGE_REMOVED)
		);

		if(! $r) {
			throw new DAV\Exception\NotFound('The file with name: ' . $channel_name . ' could not be found');

			return;
		}
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


	function getLastModified() {
		$r = q("select edited from attach where folder = '%s' and uid = %d order by edited desc limit 1",
			dbesc($this->folder_hash),
			intval($this->auth->owner_id)			
		);
		if($r)
			return datetime_convert('UTC','UTC', $r[0]['edited'],'U');
		return '';
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

		$c = q("select * from channel where channel_id = %d and not (channel_pageflags & %d) limit 1",
			intval(PAGE_REMOVED),
			intval($this->auth->owner_id)
		);

		$r = q("select flags, folder, data from attach where hash = '%s' and uid = %d limit 1",
			dbesc($this->data['hash']),
			intval($c[0]['channel_id'])
		);
		if($r) {
			if($r[0]['flags'] & ATTACH_FLAG_OS) {
				$f = 'store/' . $this->auth->owner_nick . '/' . (($r[0]['data']) ? $r[0]['data'] : '');
				@file_put_contents($f, $data);
				$size = @filesize($f);
				logger('reddav: put() filename: ' . $f . ' size: ' . $size, LOGGER_DEBUG);
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

		$edited = datetime_convert(); 

		$d = q("update attach set filesize = '%s', edited = '%s' where hash = '%s' and uid = %d limit 1",
			dbesc($size),
			dbesc($edited),
			dbesc($this->data['hash']),
			intval($c[0]['channel_id'])
		);

		$e = q("update attach set edited = '%s' where folder = '%s' and uid = %d limit 1",
			dbesc($edited),
			dbesc($r[0]['folder']),
			intval($c[0]['channel_id'])
		);		

		$maxfilesize = get_config('system','maxfilesize');

		if(($maxfilesize) && ($size > $maxfilesize)) {
			attach_delete($c[0]['channel_id'],$this->data['hash']);
			return;
		}

		$limit = service_class_fetch($c[0]['channel_id'],'attach_upload_limit');
		if($limit !== false) {
			$x = q("select sum(filesize) as total from attach where aid = %d ",
				intval($c[0]['channel_account_id'])
			);
			if(($x) &&  ($x[0]['total'] + $size > $limit)) {
				logger('reddav: service class limit exceeded for ' . $c[0]['channel_name'] . ' total usage is ' . $x[0]['total'] . ' limit is ' . $limit);
				attach_delete($c[0]['channel_id'],$this->data['hash']);
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
		return datetime_convert('UTC','UTC',$this->data['edited'],'U');
	}


	function delete() {
		if((! $this->auth->owner_id) || (! perm_is_allowed($this->auth->owner_id,$this->auth->observer,'write_storage'))) {
			throw new DAV\Exception\Forbidden('Permission denied.');
			return;
		}

		if($this->auth->owner_id !== $this->auth->channel_id) {
			if(($this->auth->observer !== $this->data['creator']) || ($this->data['flags'] & ATTACH_FLAG_DIR)) {
				throw new DAV\Exception\Forbidden('Permission denied.');
				return;
			}
		}

		attach_delete($this->auth->owner_id,$this->data['hash']);
	}

}

function RedChannelList(&$auth) {

	$ret = array();

	$r = q("select channel_id, channel_address from channel where not (channel_pageflags & %d) and not (channel_pageflags & %d) ",
		intval(PAGE_REMOVED),
		intval(PAGE_HIDDEN)
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
	public $timezone;

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

    public function generateDirectoryIndex($path) {

		if($this->auth->timezone)
			date_default_timezone_set($this->auth->timezone);

        $version = '';

        $html = "
<body>
  <h1>Index for " . $this->escapeHTML($path) . "/</h1>
  <table>
    <tr><th width=\"24\"></th><th>Name</th><th>Type</th><th>Size</th><th>Last modified</th></tr>
    <tr><td colspan=\"5\"><hr /></td></tr>";

        $files = $this->server->getPropertiesForPath($path,array(
            '{DAV:}displayname',
            '{DAV:}resourcetype',
            '{DAV:}getcontenttype',
            '{DAV:}getcontentlength',
            '{DAV:}getlastmodified',
        ),1);

        $parent = $this->server->tree->getNodeForPath($path);


        if ($path) {

            list($parentUri) = DAV\URLUtil::splitPath($path);
            $fullPath = DAV\URLUtil::encodePath($this->server->getBaseUri() . $parentUri);

            $icon = $this->enableAssets?'<a href="' . $fullPath . '"><img src="' . $this->getAssetUrl('icons/parent' . $this->iconExtension) . '" width="24" alt="Parent" /></a>':'';
            $html.= "<tr>
    <td>$icon</td>
    <td><a href=\"{$fullPath}\">..</a></td>
    <td>[parent]</td>
    <td></td>
    <td></td>
    </tr>";

        }

        foreach($files as $file) {

            // This is the current directory, we can skip it
            if (rtrim($file['href'],'/')==$path) continue;

            list(, $name) = DAV\URLUtil::splitPath($file['href']);

            $type = null;


            if (isset($file[200]['{DAV:}resourcetype'])) {
                $type = $file[200]['{DAV:}resourcetype']->getValue();

                // resourcetype can have multiple values
                if (!is_array($type)) $type = array($type);

                foreach($type as $k=>$v) {

                    // Some name mapping is preferred
                    switch($v) {
                        case '{DAV:}collection' :
                            $type[$k] = 'Collection';
                            break;
                        case '{DAV:}principal' :
                            $type[$k] = 'Principal';
                            break;
                        case '{urn:ietf:params:xml:ns:carddav}addressbook' :
                            $type[$k] = 'Addressbook';
                            break;
                        case '{urn:ietf:params:xml:ns:caldav}calendar' :
                            $type[$k] = 'Calendar';
                            break;
                        case '{urn:ietf:params:xml:ns:caldav}schedule-inbox' :
                            $type[$k] = 'Schedule Inbox';
                            break;
                        case '{urn:ietf:params:xml:ns:caldav}schedule-outbox' :
                            $type[$k] = 'Schedule Outbox';
                            break;
                        case '{http://calendarserver.org/ns/}calendar-proxy-read' :
                            $type[$k] = 'Proxy-Read';
                            break;
                        case '{http://calendarserver.org/ns/}calendar-proxy-write' :
                            $type[$k] = 'Proxy-Write';
                            break;
                    }

                }
                $type = implode(', ', $type);
            }

            // If no resourcetype was found, we attempt to use
            // the contenttype property
            if (!$type && isset($file[200]['{DAV:}getcontenttype'])) {
                $type = $file[200]['{DAV:}getcontenttype'];
            }
            if (!$type) $type = 'Unknown';

            $size = isset($file[200]['{DAV:}getcontentlength'])?(int)$file[200]['{DAV:}getcontentlength']:'';
            $lastmodified = ((isset($file[200]['{DAV:}getlastmodified']))? $file[200]['{DAV:}getlastmodified']->getTime()->format('Y-m-d H:i:s') :'');

            $fullPath = DAV\URLUtil::encodePath('/' . trim($this->server->getBaseUri() . ($path?$path . '/':'') . $name,'/'));

            $displayName = isset($file[200]['{DAV:}displayname'])?$file[200]['{DAV:}displayname']:$name;

            $displayName = $this->escapeHTML($displayName);
            $type = $this->escapeHTML($type);

            $icon = '';

            if ($this->enableAssets) {
                $node = $this->server->tree->getNodeForPath(($path?$path.'/':'') . $name);
                foreach(array_reverse($this->iconMap) as $class=>$iconName) {

                    if ($node instanceof $class) {
                        $icon = '<a href="' . $fullPath . '"><img src="' . $this->getAssetUrl($iconName . $this->iconExtension) . '" alt="" width="24" /></a>';
                        break;
                    }


                }

            }

            $html.= "<tr>
    <td>$icon</td>
    <td><a href=\"{$fullPath}\">{$displayName}</a></td>
    <td>{$type}</td>
    <td>{$size}</td>
    <td>" . (($lastmodified) ? datetime_convert('UTC', date_default_timezone_get(),$lastmodified) : '') . "</td>
    </tr>";

        }

        $html.= "<tr><td colspan=\"5\"><hr /></td></tr>";

        $output = '';

        if ($this->enablePost) {
            $this->server->broadcastEvent('onHTMLActionsPanel',array($parent, &$output));
        }

        $html.=$output;

        $html.= "</table>";

		get_app()->page['content'] = $html;
		construct_page(get_app());

//        return $html;

    }


    public function htmlActionsPanel(DAV\INode $node, &$output) {

        if (!$node instanceof DAV\ICollection)
            return;

        // We also know fairly certain that if an object is a non-extended
        // SimpleCollection, we won't need to show the panel either.

        if (get_class($node)==='Sabre\\DAV\\SimpleCollection')
            return;

        $output.= '<tr><td colspan="2"><form method="post" action="">
            <h3>Create new folder</h3>
            <input type="hidden" name="sabreAction" value="mkcol" />
            Name: <input type="text" name="name" />
            <input type="submit" value="create" />
            </form>
            <form method="post" action="" enctype="multipart/form-data">
            <h3>Upload file</h3>
            <input type="hidden" name="sabreAction" value="put" />
            Name (optional): <input type="text" name="name" /><br />
            File: <input type="file" name="file" /><br />
            <input type="submit" value="upload" />
            </form>
            </td></tr>';


		if($this->auth->owner_id && $this->auth->owner_id == $this->auth->channel_id) {
			$channel = get_app()->get_channel();
			if($channel) {
				$output .= '<tr><td>&nbsp;</td></tr><tr><td colspan="2"><a href="filestorage/' . $channel['channel_address'] . '" >' . t('Edit File properties') . '</a></td></tr>';
			}
		}

    }


}
