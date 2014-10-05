<?php
/**
 * @file include/reddav.php
 * @brief DAV related classes from SabreDAV for Red Matrix.
 *
 * This file contains the classes from SabreDAV that got extended to adapt it
 * for Red Matrix.
 *
 * You find the original SabreDAV classes under @ref vendor/sabre/dav/.
 * We need to use SabreDAV 1.8.x for PHP5.3 compatibility. SabreDAV >= 2.0
 * requires PHP >= 5.4.
 *
 * @todo split up the classes into own files.
 */

use Sabre\DAV;
require_once('vendor/autoload.php');
require_once('include/attach.php');


/**
 * @brief RedDirectory class.
 *
 * A class that represents a directory.
 */
class RedDirectory extends DAV\Node implements DAV\ICollection, DAV\IQuota {

	/**
	 * @brief The path inside /cloud
	 *
	 * @var string
	 */
	private $red_path;
	private $folder_hash;
	/**
	 * @brief The full path as seen in the browser.
	 * /cloud + $red_path
	 * @todo I think this is not used anywhere, we always strip '/cloud' and only use it in debug
	 * @var string
	 */
	private $ext_path;
	private $root_dir = '';
	private $auth;
	/**
	 * @brief The real path on the filesystem.
	 * The actual path in store/ with the hashed names.
	 *
	 * @var string
	 */
	private $os_path = '';

	/**
	 * @brief Sets up the directory node, expects a full path.
	 *
	 * @param string $ext_path a full path
	 * @param RedBasicAuth &$auth_plugin
	 */
	public function __construct($ext_path, &$auth_plugin) {
		logger('RedDirectory::__construct() ' . $ext_path, LOGGER_DATA);
		$this->ext_path = $ext_path;
		// remove "/cloud" from the beginning of the path
		$this->red_path = ((strpos($ext_path, '/cloud') === 0) ? substr($ext_path, 6) : $ext_path);
		if (! $this->red_path) {
			$this->red_path = '/';
		}
		$this->auth = $auth_plugin;
		$this->folder_hash = '';
		$this->getDir();

		if ($this->auth->browser) {
			$this->auth->browser->set_writeable();
		}
	}

	private function log() {
		logger('RedDirectory::log() ext_path ' . $this->ext_path, LOGGER_DATA);
		logger('RedDirectory::log() os_path ' . $this->os_path, LOGGER_DATA);
		logger('RedDirectory::log() red_path ' . $this->red_path, LOGGER_DATA);
	}

	/**
	 * @brief Returns an array with all the child nodes.
	 *
	 * @throws DAV\Exception\Forbidden
	 * @return array DAV\INode[]
	 */
	public function getChildren() {
		logger('RedDirectory::getChildren() called for ' . $this->ext_path, LOGGER_DATA);
		$this->log();

		if (get_config('system', 'block_public') && (! $this->auth->channel_id) && (! $this->auth->observer)) {
			throw new DAV\Exception\Forbidden('Permission denied.');
		}

		if (($this->auth->owner_id) && (! perm_is_allowed($this->auth->owner_id, $this->auth->observer, 'view_storage'))) {
			throw new DAV\Exception\Forbidden('Permission denied.');
		}

		$contents =  RedCollectionData($this->red_path, $this->auth);
		return $contents;
	}

	/**
	 * @brief Returns a child by name.
	 *
	 *
	 * @throw DAV\Exception\Forbidden
	 * @throw DAV\Exception\NotFound
	 * @param string $name
	 */
	public function getChild($name) {
		logger('RedDirectory::getChild(): ' . $name, LOGGER_DATA);

		if (get_config('system', 'block_public') && (! $this->auth->channel_id) && (! $this->auth->observer)) {
			throw new DAV\Exception\Forbidden('Permission denied.');
		}

		if (($this->auth->owner_id) && (! perm_is_allowed($this->auth->owner_id, $this->auth->observer, 'view_storage'))) {
			throw new DAV\Exception\Forbidden('Permission denied.');
		}

		if ($this->red_path === '/' && $name === 'cloud') {
			return new RedDirectory('/cloud', $this->auth);
		}

		$x = RedFileData($this->ext_path . '/' . $name, $this->auth);
		if ($x) {
			return $x;
		}

		throw new DAV\Exception\NotFound('The file with name: ' . $name . ' could not be found.');
	}

	/**
	 * @brief Returns the name of the directory.
	 *
	 * @return string
	 */
	public function getName() {
		logger('RedDirectory::getName() returns: ' . basename($this->red_path), LOGGER_DATA);
		return (basename($this->red_path));
	}
	
	/**
	 * @brief Renames the directory.
	 *
	 * @todo handle duplicate directory name
	 *
	 * @throw DAV\Exception\Forbidden
	 * @param string $name The new name of the directory.
	 * @return void
	 */
	public function setName($name) {
		logger('RedDirectory::setName(): ' . basename($this->red_path) . ' -> ' . $name, LOGGER_DATA);

		if ((! $name) || (! $this->auth->owner_id)) {
			logger('RedDirectory::setName(): permission denied');
			throw new DAV\Exception\Forbidden('Permission denied.');
		}

		if (! perm_is_allowed($this->auth->owner_id, $this->auth->observer, 'write_storage')) {
			logger('RedDirectory::setName(): permission denied');
			throw new DAV\Exception\Forbidden('Permission denied.');
		}

		list($parent_path, ) = DAV\URLUtil::splitPath($this->red_path);
		$new_path = $parent_path . '/' . $name;

		$r = q("UPDATE attach SET filename = '%s' WHERE hash = '%s' AND uid = %d LIMIT 1",
			dbesc($name),
			dbesc($this->folder_hash),
			intval($this->auth->owner_id)
		);

		$this->red_path = $new_path;
	}

	/**
	 * @brief Creates a new file in the directory.
	 *
	 * Data will either be supplied as a stream resource, or in certain cases
	 * as a string. Keep in mind that you may have to support either.
	 *
	 * After successful creation of the file, you may choose to return the ETag
	 * of the new file here.
	 *
	 * @throws DAV\Exception\Forbidden
	 * @param string $name Name of the file
	 * @param resource|string $data Initial payload
	 * @return null|string ETag
	 */
	public function createFile($name, $data = null) {
		logger('RedDirectory::createFile(): ' . $name, LOGGER_DATA);

		if (! $this->auth->owner_id) {
			logger('RedDirectory::createFile(): permission denied');
			throw new DAV\Exception\Forbidden('Permission denied.');
		}

		if (! perm_is_allowed($this->auth->owner_id, $this->auth->observer, 'write_storage')) {
			logger('RedDirectory::createFile(): permission denied');
			throw new DAV\Exception\Forbidden('Permission denied.');
		}

		$mimetype = z_mime_content_type($name);

		$c = q("SELECT * FROM channel WHERE channel_id = %d AND NOT (channel_pageflags & %d) LIMIT 1",
			intval($this->auth->owner_id),
			intval(PAGE_REMOVED)
		);

		if (! $c) {
			logger('RedDirectory::createFile(): no channel');
			throw new DAV\Exception\Forbidden('Permission denied.');
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

		// returns the number of bytes that were written to the file, or FALSE on failure
		$size = file_put_contents($f, $data);
		// delete attach entry if file_put_contents() failed
		if ($size === false) {
			logger('RedDirectory::createFile(): file_put_contents() failed for ' . $name, LOGGER_DEBUG);
			attach_delete($c[0]['channel_id'], $hash);
			return;
		}

		// returns now
		$edited = datetime_convert(); 

		// updates entry with filesize and timestamp
		$d = q("UPDATE attach SET filesize = '%s', edited = '%s' WHERE hash = '%s' AND uid = %d LIMIT 1",
			dbesc($size),
			dbesc($edited),
			dbesc($hash),
			intval($c[0]['channel_id'])
		);

		// update the folder's lastmodified timestamp
		$e = q("UPDATE attach SET edited = '%s' WHERE hash = '%s' AND uid = %d LIMIT 1",
			dbesc($edited),
			dbesc($this->folder_hash),
			intval($c[0]['channel_id'])
		);

		$maxfilesize = get_config('system', 'maxfilesize');
		if (($maxfilesize) && ($size > $maxfilesize)) {
			attach_delete($c[0]['channel_id'], $hash);
			return;
		}

		// check against service class quota
		$limit = service_class_fetch($c[0]['channel_id'], 'attach_upload_limit');
		if ($limit !== false) {
			$x = q("SELECT SUM(filesize) AS total FROM attach WHERE aid = %d ",
				intval($c[0]['channel_account_id'])
			);
			if (($x) && ($x[0]['total'] + $size > $limit)) {
				logger('reddav: service class limit exceeded for ' . $c[0]['channel_name'] . ' total usage is ' . $x[0]['total'] . ' limit is ' . $limit);
				attach_delete($c[0]['channel_id'], $hash);
				return;
			}
		}
	}

	/**
	 * @brief Creates a new subdirectory.
	 *
	 * @param string $name the directory to create
	 * @return void
	 */
	public function createDirectory($name) {
		logger('RedDirectory::createDirectory(): ' . $name, LOGGER_DEBUG);

		if ((! $this->auth->owner_id) || (! perm_is_allowed($this->auth->owner_id, $this->auth->observer, 'write_storage'))) {
			throw new DAV\Exception\Forbidden('Permission denied.');
		}

		$r = q("SELECT * FROM channel WHERE channel_id = %d AND NOT (channel_pageflags & %d) LIMIT 1",
			intval($this->auth->owner_id),
			intval(PAGE_REMOVED)
		);

		if ($r) {
			$result = attach_mkdir($r[0], $this->auth->observer, array('filename' => $name, 'folder' => $this->folder_hash));
			if (! $result['success']) {
				logger('RedDirectory::createDirectory(): ' . print_r($result, true), LOGGER_DEBUG);
			}
		}
	}

	/**
	 * @brief Checks if a child exists.
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function childExists($name) {
		// On /cloud we show a list of available channels.
		// @todo what happens if no channels are available?
		if ($this->red_path === '/' && $name === 'cloud') {
			logger('RedDirectory::childExists() /cloud: true', LOGGER_DATA);
			return true;
		}

		$x = RedFileData($this->ext_path . '/' . $name, $this->auth, true);
		logger('RedFileData returns: ' . print_r($x, true), LOGGER_DATA);
		if ($x)
			return true;
		return false;
	}

	/**
	 * @todo add description of what this function does.
	 *
	 * @throw DAV\Exception\NotFound
	 * @return void
	 */
	function getDir() {
		logger('RedDirectory::getDir(): ' . $this->ext_path, LOGGER_DEBUG);
		$this->auth->log();

		$file = $this->ext_path;

		$x = strpos($file, '/cloud');
		if ($x === false)
			return;
		if ($x === 0) {
			$file = substr($file, 6);
		}

		if ((! $file) || ($file === '/')) {
			return;
		}

		$file = trim($file, '/');
		$path_arr = explode('/', $file);

		if (! $path_arr)
			return;

		logger('RedDirectory::getDir(): path: ' . print_r($path_arr, true), LOGGER_DATA);

		$channel_name = $path_arr[0];

		$r = q("SELECT channel_id FROM channel WHERE channel_address = '%s' AND NOT ( channel_pageflags & %d ) LIMIT 1",
			dbesc($channel_name),
			intval(PAGE_REMOVED)
		);

		if (! $r) {
			throw new DAV\Exception\NotFound('The file with name: ' . $channel_name . ' could not be found.');
			return;
		}

		$channel_id = $r[0]['channel_id'];
		$this->auth->owner_id = $channel_id;
		$this->auth->owner_nick = $channel_name;

		$path = '/' . $channel_name;
		$folder = '';
		$os_path = '';

		for ($x = 1; $x < count($path_arr); $x++) {		
			$r = q("select id, hash, filename, flags from attach where folder = '%s' and filename = '%s' and uid = %d and (flags & %d)",
				dbesc($folder),
				dbesc($path_arr[$x]),
				intval($channel_id),
				intval(ATTACH_FLAG_DIR)
			);

			if ($r && ( $r[0]['flags'] & ATTACH_FLAG_DIR)) {
				$folder = $r[0]['hash'];
				if (strlen($os_path))
					$os_path .= '/';
				$os_path .= $folder;

				$path = $path . '/' . $r[0]['filename'];
			}
		}
		$this->folder_hash = $folder;
		$this->os_path = $os_path;
		return;
	}

	/**
	 * @brief Returns the last modification time for the directory, as a UNIX
	 *        timestamp.
	 *
	 * It looks for the last edited file in the folder. If it is an empty folder
	 * it returns the lastmodified time of the folder itself, to prevent zero
	 * timestamps.
	 *
	 * @return int last modification time in UNIX timestamp
	 */
	public function getLastModified() {
		$r = q("SELECT edited FROM attach WHERE folder = '%s' AND uid = %d ORDER BY edited DESC LIMIT 1",
			dbesc($this->folder_hash),
			intval($this->auth->owner_id)
		);
		if (! $r) {
			$r = q("SELECT edited FROM attach WHERE hash = '%s' AND uid = %d LIMIT 1",
				dbesc($this->folder_hash),
				intval($this->auth->owner_id)
			);
			if (! $r)
				return '';
		}
		return datetime_convert('UTC', 'UTC', $r[0]['edited'], 'U');
	}

	/**
	 * @brief Return quota usage.
	 *
	 * Do guests relly see the used/free values from filesystem of the complete store directory?
	 *
	 * @return array with used and free values in bytes.
	 */
	public function getQuotaInfo() {
		// values from the filesystem of the complete <i>store/</i> directory
		$limit = disk_total_space('store');
		$free = disk_free_space('store');

		if ($this->auth->owner_id) {
			$c = q("select * from channel where channel_id = %d and not (channel_pageflags & %d) limit 1",
				intval($this->auth->owner_id),
				intval(PAGE_REMOVED)
			);

			$ulimit = service_class_fetch($c[0]['channel_id'], 'attach_upload_limit');
			$limit = (($ulimit) ? $ulimit : $limit);

			$x = q("select sum(filesize) as total from attach where aid = %d",
				intval($c[0]['channel_account_id'])
			);
			$free = (($x) ? $limit - $x[0]['total'] : 0);
		}

		return array(
			$limit - $free,
			$free
		);
	}
} // class RedDirectory



/**
 * RedFile class.
 *
 */
class RedFile extends DAV\Node implements DAV\IFile {

	private $data;
	private $auth;
	private $name;

	/**
	 * Sets up the node, expects a full path name.
	 *
	 * @param string $name
	 * @param array $data from attach table
	 * @param &$auth
	 */
	public function __construct($name, $data, &$auth) {
		$this->name = $name;
		$this->data = $data;
		$this->auth = $auth;

		logger('RedFile::__construct(): ' . print_r($this->data, true), LOGGER_DATA);
	}

	/**
	 * @brief Returns the name of the file.
	 *
	 * @return string
	 */
	public function getName() {
		logger('RedFile::getName(): ' . basename($this->name), LOGGER_DEBUG);
		return basename($this->name);
	}

	/**
	 * @brief Renames the file.
	 *
	 * @throw DAV\Exception\Forbidden
	 * @param string $name The new name of the file.
	 * @return void
	 */
	public function setName($newName) {
		logger('RedFile::setName(): ' . basename($this->name) . ' -> ' . $newName, LOGGER_DEBUG);

		if ((! $newName) || (! $this->auth->owner_id) || (! perm_is_allowed($this->auth->owner_id, $this->auth->observer, 'write_storage'))) {
			throw new DAV\Exception\Forbidden('Permission denied.');
		}

		$newName = str_replace('/', '%2F', $newName);

		$r = q("UPDATE attach SET filename = '%s' WHERE hash = '%s' AND id = %d LIMIT 1",
			dbesc($this->data['filename']),
			intval($this->data['id'])
		);
	}

	/**
	 * @brief Updates the data of the file.
	 *
	 * @param resource $data
	 * @return void
	 */
	public function put($data) {
		logger('RedFile::put(): ' . basename($this->name), LOGGER_DEBUG);
		$size = 0;

		// @todo only 3 values are needed
		$c = q("SELECT * FROM channel WHERE channel_id = %d AND NOT (channel_pageflags & %d) LIMIT 1",
			intval($this->auth->owner_id),
			intval(PAGE_REMOVED)
		);

		$r = q("SELECT flags, folder, data FROM attach WHERE hash = '%s' AND uid = %d LIMIT 1",
			dbesc($this->data['hash']),
			intval($c[0]['channel_id'])
		);
		if ($r) {
			if ($r[0]['flags'] & ATTACH_FLAG_OS) {
				$f = 'store/' . $this->auth->owner_nick . '/' . (($r[0]['data']) ? $r[0]['data'] : '');
				// @todo check return value and set $size directly
				@file_put_contents($f, $data);
				$size = @filesize($f);
				logger('RedFile::put(): filename: ' . $f . ' size: ' . $size, LOGGER_DEBUG);
			} else {
				$r = q("UPDATE attach SET data = '%s' WHERE hash = '%s' AND uid = %d LIMIT 1",
					dbesc(stream_get_contents($data)),
					dbesc($this->data['hash']),
					intval($this->data['uid'])
				);
				$r = q("SELECT length(data) AS fsize FROM attach WHERE hash = '%s' AND uid = %d LIMIT 1",
					dbesc($this->data['hash']),
					intval($this->data['uid'])
				);
				if ($r) {
					$size = $r[0]['fsize'];
				}
			}
		}

		// returns now()
		$edited = datetime_convert(); 

		$d = q("UPDATE attach SET filesize = '%s', edited = '%s' WHERE hash = '%s' AND uid = %d LIMIT 1",
			dbesc($size),
			dbesc($edited),
			dbesc($this->data['hash']),
			intval($c[0]['channel_id'])
		);

		// update the folder's lastmodified timestamp
		$e = q("UPDATE attach SET edited = '%s' WHERE hash = '%s' AND uid = %d LIMIT 1",
			dbesc($edited),
			dbesc($r[0]['folder']),
			intval($c[0]['channel_id'])
		);

		// @todo do we really want to remove the whole file if an update fails
		// because of maxfilesize or quota?
		// There is an Exception "InsufficientStorage" or "PaymentRequired" for
		// our service class from SabreDAV we could use.

		$maxfilesize = get_config('system', 'maxfilesize');
		if (($maxfilesize) && ($size > $maxfilesize)) {
			attach_delete($c[0]['channel_id'], $this->data['hash']);
			return;
		}

		$limit = service_class_fetch($c[0]['channel_id'], 'attach_upload_limit');
		if ($limit !== false) {
			$x = q("select sum(filesize) as total from attach where aid = %d ",
				intval($c[0]['channel_account_id'])
			);
			if (($x) && ($x[0]['total'] + $size > $limit)) {
				logger('RedFile::put(): service class limit exceeded for ' . $c[0]['channel_name'] . ' total usage is ' . $x[0]['total'] . ' limit is ' . $limit);
				attach_delete($c[0]['channel_id'], $this->data['hash']);
				return;
			}
		}
	}

	/**
	 * @brief Returns the raw data.
	 *
	 * @return string
	 */
	public function get() {
		logger('RedFile::get(): ' . basename($this->name), LOGGER_DEBUG);

		$r = q("select data, flags, filename, filetype from attach where hash = '%s' and uid = %d limit 1",
			dbesc($this->data['hash']),
			intval($this->data['uid'])
		);
		if ($r) {
			// @todo this should be a global definition
			$unsafe_types = array('text/html', 'text/css', 'application/javascript');

			if (in_array($r[0]['filetype'], $unsafe_types)) {
				header('Content-disposition: attachment; filename="' . $r[0]['filename'] . '"');
				header('Content-type: text/plain');
			}

			if ($r[0]['flags'] & ATTACH_FLAG_OS ) {
				$f = 'store/' . $this->auth->owner_nick . '/' . (($this->os_path) ? $this->os_path . '/' : '') . $r[0]['data'];
				return fopen($f, 'rb');
			}
			return $r[0]['data'];
		}
	}

	/**
	 * @brief Returns the ETag for a file.
	 *
	 * An ETag is a unique identifier representing the current version of the file. If the file changes, the ETag MUST change.
	 * The ETag is an arbitrary string, but MUST be surrounded by double-quotes.
	 *
	 * Return null if the ETag can not effectively be determined.
	 *
	 * @return mixed
	 */
	public function getETag() {
		$ret = null;
		if ($this->data['hash']) {
			$ret = '"' . $this->data['hash'] . '"';
		}
		return $ret;
	}

	/**
	 * @brief Returns the mime-type for a file.
	 *
	 * If null is returned, we'll assume application/octet-stream
	 *
	 * @return mixed
	 */
	public function getContentType() {
		// @todo this should be a global definition.
		$unsafe_types = array('text/html', 'text/css', 'application/javascript');
		if (in_array($this->data['filetype'], $unsafe_types)) {
			return 'text/plain';
		}
		return $this->data['filetype'];
	}

	/**
	 * @brief Returns the size of the node, in bytes.
	 *
	 * @return int
	 */
	public function getSize() {
		return $this->data['filesize'];
	}

	/**
	 * @brief Returns the last modification time for the file, as a unix
	 *        timestamp.
	 *
	 * @return int last modification time in UNIX timestamp
	 */
	public function getLastModified() {
		return datetime_convert('UTC', 'UTC', $this->data['edited'], 'U');
	}

	/**
	 * @brief Delete the file.
	 *
	 * @throw DAV\Exception\Forbidden
	 * @return void
	 */
	public function delete() {
		logger('RedFile::delete(): ' . basename($this->name), LOGGER_DEBUG);

		if ((! $this->auth->owner_id) || (! perm_is_allowed($this->auth->owner_id, $this->auth->observer, 'write_storage'))) {
			throw new DAV\Exception\Forbidden('Permission denied.');
		}

		if ($this->auth->owner_id !== $this->auth->channel_id) {
			if (($this->auth->observer !== $this->data['creator']) || ($this->data['flags'] & ATTACH_FLAG_DIR)) {
				throw new DAV\Exception\Forbidden('Permission denied.');
			}
		}

		attach_delete($this->auth->owner_id, $this->data['hash']);
	}
} // class RedFile


/**
 * @brief Returns an array with viewable channels.
 *
 * Get a list of RedDirectory objects with all the channels where the visitor
 * has <b>view_storage</b> perms.
 *
 * @todo Is there any reason why this is not inside RedDirectory class?
 *
 * @param $auth
 * @return array containing RedDirectory objects
 */
function RedChannelList(&$auth) {
	$ret = array();

	$r = q("SELECT channel_id, channel_address FROM channel WHERE NOT (channel_pageflags & %d) AND NOT (channel_pageflags & %d)",
		intval(PAGE_REMOVED),
		intval(PAGE_HIDDEN)
	);

	if ($r) {
		foreach ($r as $rr) {
			if (perm_is_allowed($rr['channel_id'], $auth->observer, 'view_storage')) {
				logger('RedChannelList: ' . '/cloud/' . $rr['channel_address'], LOGGER_DATA);
				// @todo can't we drop '/cloud'? It gets stripped off anyway in RedDirectory
				$ret[] = new RedDirectory('/cloud/' . $rr['channel_address'], $auth);
			}
		}
	}
	return $ret;
}


/**
 * @brief TODO what exactly does this function?
 *
 * Array with all RedDirectory and RedFile DAV\Node items for the given path.
 *
 * @todo Is there any reason why this is not inside RedDirectory class? Seems only to be used there and we could simplify it a bit there.
 *
 * @param string $file path to a directory
 * @param &$auth
 * @returns array DAV\INode[]
 */
function RedCollectionData($file, &$auth) {
	$ret = array();

	$x = strpos($file, '/cloud');
	if ($x === 0) {
		$file = substr($file, 6);
	}

	// return a list of channel if we are not inside a channel
	if ((! $file) || ($file === '/')) {
		return RedChannelList($auth);
	}

	$file = trim($file, '/');
	$path_arr = explode('/', $file);
	
	if (! $path_arr)
		return null;

	$channel_name = $path_arr[0];

	$r = q("SELECT channel_id FROM channel WHERE channel_address = '%s' LIMIT 1",
		dbesc($channel_name)
	);

	if (! $r)
		return null;

	$channel_id = $r[0]['channel_id'];
	$perms = permissions_sql($channel_id);

	$auth->owner_id = $channel_id;

	$path = '/' . $channel_name;

	$folder = '';
	$errors = false;
	$permission_error = false;

	for ($x = 1; $x < count($path_arr); $x++) {
		$r = q("SELECT id, hash, filename, flags FROM attach WHERE folder = '%s' AND filename = '%s' AND uid = %d AND (flags & %d) $perms LIMIT 1",
			dbesc($folder),
			dbesc($path_arr[$x]),
			intval($channel_id),
			intval(ATTACH_FLAG_DIR)
		);
		if (! $r) {
			// path wasn't found. Try without permissions to see if it was the result of permissions.
			$errors = true;
			$r = q("select id, hash, filename, flags from attach where folder = '%s' and filename = '%s' and uid = %d and (flags & %d) limit 1",
				dbesc($folder),
				basename($path_arr[$x]),
				intval($channel_id),
				intval(ATTACH_FLAG_DIR)
			);
			if ($r) {
				$permission_error = true;
			}
			break;
		}

		if ($r && ($r[0]['flags'] & ATTACH_FLAG_DIR)) {
			$folder = $r[0]['hash'];
			$path = $path . '/' . $r[0]['filename'];
		}
	}

	if ($errors) {
		if ($permission_error) {
			throw new DAV\Exception\Forbidden('Permission denied.');
		} else {
			throw new DAV\Exception\NotFound('A component of the request file path could not be found.');
		}
	}

	// This should no longer be needed since we just returned errors for paths not found
	if ($path !== '/' . $file) {
		logger("RedCollectionData: Path mismatch: $path !== /$file");
		return NULL;
	}

	$r = q("select id, uid, hash, filename, filetype, filesize, revision, folder, flags, created, edited from attach where folder = '%s' and uid = %d $perms group by filename",
		dbesc($folder),
		intval($channel_id)
	);

	foreach ($r as $rr) {
		logger('RedCollectionData: filename: ' . $rr['filename'], LOGGER_DATA);

		if ($rr['flags'] & ATTACH_FLAG_DIR) {
			// @todo can't we drop '/cloud'? it gets stripped off anyway in RedDirectory
			$ret[] = new RedDirectory('/cloud' . $path . '/' . $rr['filename'], $auth);
		} else {
			$ret[] = new RedFile('/cloud' . $path . '/' . $rr['filename'], $rr, $auth);
		}
	}

	return $ret;
}


/**
 * @brief TODO What exactly is this function for?
 *
 * @param string $file
 * @param &$auth
 * @param boolean $test (optional) enable test mode
 */
function RedFileData($file, &$auth, $test = false) {
	logger('RedFileData:' . $file . (($test) ? ' (test mode) ' : ''), LOGGER_DEBUG);

	$x = strpos($file, '/cloud');
	if ($x === 0) {
		$file = substr($file, 6);
	}

	if ((! $file) || ($file === '/')) {
		return new RedDirectory('/', $auth);
	}

	$file = trim($file, '/');

	$path_arr = explode('/', $file);
	
	if (! $path_arr)
		return null;

	$channel_name = $path_arr[0];

	$r = q("select channel_id from channel where channel_address = '%s' limit 1",
		dbesc($channel_name)
	);

	if (! $r)
		return null;

	$channel_id = $r[0]['channel_id'];

	$path = '/' . $channel_name;

	$auth->owner_id = $channel_id;

	$permission_error = false;

	$folder = '';

	require_once('include/security.php');
	$perms = permissions_sql($channel_id);

	$errors = false;

	for ($x = 1; $x < count($path_arr); $x++) {		
		$r = q("select id, hash, filename, flags from attach where folder = '%s' and filename = '%s' and uid = %d and (flags & %d) $perms",
			dbesc($folder),
			dbesc($path_arr[$x]),
			intval($channel_id),
			intval(ATTACH_FLAG_DIR)
		);

		if ($r && ( $r[0]['flags'] & ATTACH_FLAG_DIR)) {
			$folder = $r[0]['hash'];
			$path = $path . '/' . $r[0]['filename'];
		}	
		if (! $r) {
			$r = q("select id, uid, hash, filename, filetype, filesize, revision, folder, flags, created, edited from attach 
				where folder = '%s' and filename = '%s' and uid = %d $perms group by filename limit 1",
				dbesc($folder),
				dbesc(basename($file)),
				intval($channel_id)
			);
		}
		if (! $r) {
			$errors = true;
			$r = q("select id, uid, hash, filename, filetype, filesize, revision, folder, flags, created, edited from attach 
				where folder = '%s' and filename = '%s' and uid = %d group by filename limit 1",
				dbesc($folder),
				dbesc(basename($file)),
				intval($channel_id)
			);
			if ($r)
				$permission_error = true;
		}
	}

	if ($path === '/' . $file) {
		if ($test)
			return true;
		// final component was a directory.
		return new RedDirectory('/cloud/' . $file, $auth);
	}

	if ($errors) {
		logger('RedFileData: not found');
		if ($test)
			return false;
		if ($permission_error) {
			logger('RedFileData: permission error');	
			throw new DAV\Exception\Forbidden('Permission denied.');
		}
		return;
	}

	if ($r) {
		if ($test)
			return true;

		if ($r[0]['flags'] & ATTACH_FLAG_DIR) {
			// @todo can't we drop '/cloud'? it gets stripped off anyway in RedDirectory
			return new RedDirectory('/cloud' . $path . '/' . $r[0]['filename'], $auth);
		} else {
			return new RedFile('/cloud' . $path . '/' . $r[0]['filename'], $r[0], $auth);
		}
	}
	return false;
}



/**
 * @brief Authentication backend class for RedDAV.
 *
 * This class also contains some data which is not necessary for authentication
 * like timezone settings.
 *
 */
class RedBasicAuth extends DAV\Auth\Backend\AbstractBasic {

	/**
	 * @brief This variable holds the currently logged-in channel_address.
	 *
	 * It is used for building path in filestorage/.
	 *
	 * @var string|null
	 */
	protected $channel_name = null;
	/**
	 * channel_id of the current channel of the logged-in account.
	 *
	 * @var int
	 */
	public $channel_id = 0;
	/**
	 * channel_hash of the current channel of the logged-in account.
	 *
	 * @var string
	 */
	public $channel_hash = '';
	/**
	 * Set in mod/cloud.php to observer_hash.
	 *
	 * @var string
	 */
	public $observer = '';
	/**
	 *
	 * @see RedBrowser::set_writeable()
	 * @var DAV\Browser\Plugin
	 */
	public $browser;
	/**
	 * channel_id of the current visited path. Set in RedDirectory::getDir().
	 *
	 * @var int
	 */
	public $owner_id = 0;
	/**
	 * channel_name of the current visited path. Set in RedDirectory::getDir().
	 *
	 * Used for creating the path in cloud/
	 *
	 * @var string
	 */
	public $owner_nick = '';
	/**
	 * Timezone from the visiting channel's channel_timezone.
	 *
	 * Used in @ref RedBrowser
	 *
	 * @var string
	 */
	protected $timezone = '';


	/**
	 * @brief Validates a username and password.
	 *
	 * Guest access is granted with the password "+++".
	 *
	 * @see DAV\Auth\Backend\AbstractBasic::validateUserPass
	 * @param string $username
	 * @param string $password
	 * @return bool
	 */
	protected function validateUserPass($username, $password) {
		if (trim($password) === '+++') {
			logger('(DAV): RedBasicAuth::validateUserPass(): guest ' . $username);
			return true;
		}

		require_once('include/auth.php');
		$record = account_verify_password($username, $password);
		if ($record && $record['account_default_channel']) {
			$r = q("SELECT * FROM channel WHERE channel_account_id = %d AND channel_id = %d LIMIT 1",
				intval($record['account_id']),
				intval($record['account_default_channel'])
			);
			if ($r) {
				return $this->setAuthenticated($r[0]);
			}
		}
		$r = q("SELECT * FROM channel WHERE channel_address = '%s' LIMIT 1",
			dbesc($username)
		);
		if ($r) {
			$x = q("SELECT account_flags, account_salt, account_password FROM account WHERE account_id = %d LIMIT 1",
				intval($r[0]['channel_account_id'])
			);
			if ($x) {
				// @fixme this foreach should not be needed?
				foreach ($x as $record) {
					if (($record['account_flags'] == ACCOUNT_OK) || ($record['account_flags'] == ACCOUNT_UNVERIFIED)
					&& (hash('whirlpool', $record['account_salt'] . $password) === $record['account_password'])) {
						logger('(DAV) RedBasicAuth: password verified for ' . $username);
						return $this->setAuthenticated($r[0]);
					}
				}
			}
		}
		logger('(DAV) RedBasicAuth: password failed for ' . $username);
		return false;
	}

	/**
	 * @brief Sets variables and session parameters after successfull authentication.
	 * 
	 * @param array $r
	 *  Array with the values for the authenticated channel.
	 * @return bool
	 */
	protected function setAuthenticated($r) {
		$this->channel_name = $r['channel_address'];
		$this->channel_id = $r['channel_id'];
		$this->channel_hash = $this->observer = $r['channel_hash'];
		$_SESSION['uid'] = $r['channel_id'];
		$_SESSION['account_id'] = $r['channel_account_id'];
		$_SESSION['authenticated'] = true;
		return true;
	}

	/**
	 * Sets the channel_name from the currently logged-in channel.
	 *
	 * @param string $name
	 *  The channel's name
	 */
	public function setCurrentUser($name) {
		$this->channel_name = $name;
	}
	/**
	 * Returns information about the currently logged-in channel.
	 *
	 * If nobody is currently logged in, this method should return null.
	 *
	 * @see DAV\Auth\Backend\AbstractBasic::getCurrentUser
	 * @return string|null
	 */
	public function getCurrentUser() {
		return $this->channel_name;
	}

	/**
	 * @brief Sets the timezone from the channel in RedBasicAuth.
	 *
	 * Set in mod/cloud.php if the channel has a timezone set.
	 *
	 * @param string $timezone
	 *  The channel's timezone.
	 * @return void
	 */
	public function setTimezone($timezone) {
		$this->timezone = $timezone;
	}
	/**
	 * @brief Returns the timezone.
	 *
	 * @return string
	 *  Return the channel's timezone.
	 */
	public function getTimezone() {
		return $this->timezone;
	}

	/**
	 * @brief Set browser plugin for SabreDAV.
	 *
	 * @see RedBrowser::set_writeable()
	 * @param DAV\Browser\Plugin $browser
	 */
	public function setBrowserPlugin($browser) {
		$this->browser = $browser;
	}

	/**
	 * Prints out all RedBasicAuth variables to logger().
	 *
	 * @return void
	 */
	public function log() {
		logger('dav: auth: channel_name ' . $this->channel_name, LOGGER_DATA);
		logger('dav: auth: channel_id ' . $this->channel_id, LOGGER_DATA);
		logger('dav: auth: channel_hash ' . $this->channel_hash, LOGGER_DATA);
		logger('dav: auth: observer ' . $this->observer, LOGGER_DATA);
		logger('dav: auth: owner_id ' . $this->owner_id, LOGGER_DATA);
		logger('dav: auth: owner_nick ' . $this->owner_nick, LOGGER_DATA);
	}

} // class RedBasicAuth



/**
 * @brief RedBrowser class.
 *
 * RedBrowser is a SabreDAV server-plugin to provide a view to the DAV in
 * the browser
 */
class RedBrowser extends DAV\Browser\Plugin {

	/**
	 * @var RedBasicAuth
	 */
	private $auth;

	/**
	 * @brief Constructor for RedBrowser.
	 *
	 * @param RedBasicAuth &$auth
	 */
	function __construct(&$auth) {
		$this->auth = $auth;
		$this->enableAssets = false;
	}

	// The DAV browser is instantiated after the auth module and directory classes but before we know the current
	// directory and who the owner and observer are. So we add a pointer to the browser into the auth module and vice 
	// versa. Then when we've figured out what directory is actually being accessed, we call the following function
	// to decide whether or not to show web elements which include writeable objects.
	// @todo Maybe this can be solved with some $server->subscribeEvent()?
	function set_writeable() {
		if (! $this->auth->owner_id) {
			$this->enablePost = false;
		}

		if (! perm_is_allowed($this->auth->owner_id, get_observer_hash(), 'write_storage')) {
			$this->enablePost = false;
		} else {
			$this->enablePost = true;
		}
	}

	/**
	 * @brief Creates the directory listing for the given path.
	 *
	 * @param string $path which should be displayed
	 */
	public function generateDirectoryIndex($path) {
		// (owner_id = channel_id) is visitor owner of this directory?
		$is_owner = ((local_user() && $this->auth->owner_id == local_user()) ? true : false);

		if ($this->auth->getTimezone())
			date_default_timezone_set($this->auth->getTimezone());

		require_once('include/conversation.php');

		if ($this->auth->owner_nick) {
			$html = profile_tabs(get_app(), (($is_owner) ? true : false), $this->auth->owner_nick);
		}

		$files = $this->server->getPropertiesForPath($path, array(
			'{DAV:}displayname',
			'{DAV:}resourcetype',
			'{DAV:}getcontenttype',
			'{DAV:}getcontentlength',
			'{DAV:}getlastmodified',
			), 1);

		$parent = $this->server->tree->getNodeForPath($path);

		$parentpath = array();
		// only show parent if not leaving /cloud/; TODO how to improve this? 
		if ($path && $path != "cloud") {
			list($parentUri) = DAV\URLUtil::splitPath($path);
			$fullPath = DAV\URLUtil::encodePath($this->server->getBaseUri() . $parentUri);

			$parentpath['icon'] = $this->enableAssets ? '<a href="' . $fullPath . '"><img src="' . $this->getAssetUrl('icons/parent' . $this->iconExtension) . '" width="24" alt="' . t('parent') . '"></a>' : '';
			$parentpath['path'] = $fullPath;
		}

		$f = array();
		foreach ($files as $file) {
			$ft = array();
			$type = null;

			// This is the current directory, we can skip it
			if (rtrim($file['href'],'/')==$path) continue;

			list(, $name) = DAV\URLUtil::splitPath($file['href']);

			if (isset($file[200]['{DAV:}resourcetype'])) {
				$type = $file[200]['{DAV:}resourcetype']->getValue();

				// resourcetype can have multiple values
				if (!is_array($type)) $type = array($type);

				foreach ($type as $k=>$v) {
					// Some name mapping is preferred
					switch ($v) {
						case '{DAV:}collection' :
							$type[$k] = t('Collection');
							break;
						case '{DAV:}principal' :
							$type[$k] = t('Principal');
							break;
						case '{urn:ietf:params:xml:ns:carddav}addressbook' :
							$type[$k] = t('Addressbook');
							break;
						case '{urn:ietf:params:xml:ns:caldav}calendar' :
							$type[$k] = t('Calendar');
							break;
						case '{urn:ietf:params:xml:ns:caldav}schedule-inbox' :
							$type[$k] = t('Schedule Inbox');
							break;
						case '{urn:ietf:params:xml:ns:caldav}schedule-outbox' :
							$type[$k] = t('Schedule Outbox');
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
			if (!$type) $type = t('Unknown');

			$size = isset($file[200]['{DAV:}getcontentlength']) ? (int)$file[200]['{DAV:}getcontentlength'] : '';
			$lastmodified = ((isset($file[200]['{DAV:}getlastmodified'])) ? $file[200]['{DAV:}getlastmodified']->getTime()->format('Y-m-d H:i:s') : '');

			$fullPath = DAV\URLUtil::encodePath('/' . trim($this->server->getBaseUri() . ($path ? $path . '/' : '') . $name, '/'));

			$displayName = isset($file[200]['{DAV:}displayname']) ? $file[200]['{DAV:}displayname'] : $name;

			$displayName = $this->escapeHTML($displayName);
			$type = $this->escapeHTML($type);

			$icon = '';
			if ($this->enableAssets) {
				$node = $this->server->tree->getNodeForPath(($path ? $path . '/' : '') . $name);
				foreach (array_reverse($this->iconMap) as $class=>$iconName) {
					if ($node instanceof $class) {
						$icon = '<a href="' . $fullPath . '"><img src="' . $this->getAssetUrl($iconName . $this->iconExtension) . '" alt="" width="24"></a>';
						break;
					}
				}
			}
	
			$parentHash = "";
			$owner = $this->auth->owner_id;
			$splitPath = split("/", $fullPath);
			if (count($splitPath) > 3) {
				for ($i = 3; $i < count($splitPath); $i++) {
					$attachName = urldecode($splitPath[$i]);
					$attachHash = $this->findAttachHash($owner, $parentHash, $attachName);
					$parentHash = $attachHash;
				}
			}

			$attachIcon = ""; // "<a href=\"attach/".$attachHash."\" title=\"".$displayName."\"><i class=\"icon-download\"></i></a>";

			// put the array for this file together
			$ft['attachId'] = $this->findAttachIdByHash($attachHash);
			$ft['fileStorageUrl'] = substr($fullPath, 0, strpos($fullPath, "cloud/")) . "filestorage/" . $this->auth->getCurrentUser();
			$ft['icon'] = $icon;
			$ft['attachIcon'] = (($size) ? $attachIcon : '');
			// @todo Should this be an item value, not a global one?
			$ft['is_owner'] = $is_owner;
			$ft['fullPath'] = $fullPath;
			$ft['displayName'] = $displayName;
			$ft['type'] = $type;
			$ft['size'] = $size;
			$ft['sizeFormatted'] = $this->userReadableSize($size);
			$ft['lastmodified'] = (($lastmodified) ? datetime_convert('UTC', date_default_timezone_get(), $lastmodified) : '');

			$f[] = $ft;
		}

		// Storage and quota for the account (all channels of the owner of this directory)!
		$limit = service_class_fetch($owner, 'attach_upload_limit');
		$r = q("SELECT SUM(filesize) AS total FROM attach WHERE aid = %d",
			intval($this->auth->channel_account_id)
		);
		$used = $r[0]['total'];
		if ($used) {
			$quotaDesc = t('%1$s used');
			$quotaDesc = sprintf($quotaDesc,
				$this->userReadableSize($used));
		}
		if ($limit && $used) {
			$quotaDesc = t('%1$s used of %2$s (%3$s&#37;)');
			$quotaDesc = sprintf($quotaDesc,
				$this->userReadableSize($used),
				$this->userReadableSize($limit),
				round($used / $limit, 1));
		}

		// prepare quota for template
		$quota['used'] = $used;
		$quota['limit'] = $limit;
		$quota['desc'] = $quotaDesc;

		$html .= replace_macros(get_markup_template('cloud_directory.tpl'), array(
				'$header' => t('Files') . ": " . $this->escapeHTML($path) . "/",
				'$parentpath' => $parentpath,
				'$entries' => $f,
				'$quota' => $quota,
				'$name' => t('Name'),
				'$type' => t('Type'),
				'$size' => t('Size'),
				'$lastmod' => t('Last Modified'),
				'$parent' => t('parent'),
				'$edit' => t('Edit'),
				'$delete' => t('Delete'),
				'$total' => t('Total')		
			));

		$output = '';
		if ($this->enablePost) {
			$this->server->broadcastEvent('onHTMLActionsPanel', array($parent, &$output));
		}
		$html .= $output;
	
		get_app()->page['content'] = $html;
		construct_page(get_app());
	}

	function userReadableSize($size) {
		$ret = "";
		if (is_numeric($size)) {
			$incr = 0;
			$k = 1024;
			$unit = array('bytes', 'KB', 'MB', 'GB', 'TB', 'PB');
			while (($size / $k) >= 1){
				$incr++;
				$size = round($size / $k, 2);
			}
			$ret = $size . " " . $unit[$incr];
		}
		return $ret;
	}

	/**
	 * Creates a form to add new folders and upload files.
	 *
	 * @param DAV\INode $node
	 * @param string &$output
	 */
	public function htmlActionsPanel(DAV\INode $node, &$output) {

	//Removed link to filestorage page
	//if($this->auth->owner_id && $this->auth->owner_id == $this->auth->channel_id) {
	//		$channel = get_app()->get_channel();
	//	if($channel) {
	//		$output .= '<tr><td colspan="2"><a href="filestorage/' . $channel['channel_address'] . '" >' . t('Edit File properties') . '</a></td></tr><tr><td>&nbsp;</td></tr>';
	//	}
	//}

		if (! $node instanceof DAV\ICollection)
			return;

		// We also know fairly certain that if an object is a non-extended
		// SimpleCollection, we won't need to show the panel either.
		if (get_class($node) === 'Sabre\\DAV\\SimpleCollection')
			return;

		$output .= replace_macros(get_markup_template('cloud_actionspanel.tpl'), array(
				'$folder_header' => t('Create new folder'),
				'$folder_submit' => t('Create'),
				'$upload_header' => t('Upload file'),
				'$upload_submit' => t('Upload')
			));
	}

	/**
	 * This method takes a path/name of an asset and turns it into url
	 * suiteable for http access.
	 *
	 * @param string $assetName
	 * @return string
	 */
	protected function getAssetUrl($assetName) {
		return z_root() . '/cloud/?sabreAction=asset&assetName=' . urlencode($assetName);
	}

	protected function findAttachHash($owner, $parentHash, $attachName) {
		$r = q("SELECT * FROM attach WHERE uid = %d AND folder = '%s' AND filename = '%s' ORDER BY edited desc LIMIT 1",
			intval($owner),
			dbesc($parentHash),
			dbesc($attachName)
		);
		$hash = "";
		if ($r) {
			foreach ($r as $rr) {
				$hash = $rr['hash'];
			}
		}
		return $hash;
	}

	protected function findAttachIdByHash($attachHash) {
		$r = q("SELECT * FROM attach WHERE hash = '%s' ORDER BY edited DESC LIMIT 1",
			dbesc($attachHash)
		);
		$id = "";
		if ($r) {
			foreach ($r as $rr) {
				$id = $rr['id'];
			}
		}
		return $id;
	}

} // class RedBrowser
