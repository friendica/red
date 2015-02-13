<?php

/** @file
 *
 * @brief File/attach API with the potential for revision control.
 *
 * TODO: a filesystem storage abstraction which maintains security (and 'data' contains a system filename
 * which is inaccessible from the web). This could get around PHP storage limits and store videos and larger
 * items, using fread or OS methods or native code to read/write or chunk it through.
 * Also an 'append' option to the storage function might be a useful addition. 
 */

require_once('include/permissions.php');
require_once('include/security.php');

/**
 * @brief Guess the mimetype from file ending.
 * 
 * This function takes a file name and guess the mimetype from the
 * filename extension.
 * 
 * @param $filename a string filename
 * @return string The mimetype according to a file ending.
 */
function z_mime_content_type($filename) {

	$mime_types = array(

	'txt' => 'text/plain',
	'htm' => 'text/html',
	'html' => 'text/html',
	'php' => 'text/html',
	'css' => 'text/css',
	'js' => 'application/javascript',
	'json' => 'application/json',
	'xml' => 'application/xml',
	'swf' => 'application/x-shockwave-flash',
	'flv' => 'video/x-flv',
	'epub' => 'application/epub+zip',

	// images
	'png' => 'image/png',
	'jpe' => 'image/jpeg',
	'jpeg' => 'image/jpeg',
	'jpg' => 'image/jpeg',
	'gif' => 'image/gif',
	'bmp' => 'image/bmp',
	'ico' => 'image/vnd.microsoft.icon',
	'tiff' => 'image/tiff',
	'tif' => 'image/tiff',
	'svg' => 'image/svg+xml',
	'svgz' => 'image/svg+xml',

	// archives
	'zip' => 'application/zip',
	'rar' => 'application/x-rar-compressed',
	'exe' => 'application/x-msdownload',
	'msi' => 'application/x-msdownload',
	'cab' => 'application/vnd.ms-cab-compressed',

	// audio/video
	'mp3' => 'audio/mpeg',
	'wav' => 'audio/wav',
	'qt' => 'video/quicktime',
	'mov' => 'video/quicktime',
	'ogg' => 'application/ogg',
	'opus' => 'audio/ogg',
	'webm' => 'video/webm',
//	'webm' => 'audio/webm',
	'mp4' => 'video/mp4',
//	'mp4' => 'audio/mp4',

	// adobe
	'pdf' => 'application/pdf',
	'psd' => 'image/vnd.adobe.photoshop',
	'ai' => 'application/postscript',
	'eps' => 'application/postscript',
	'ps' => 'application/postscript',

	// ms office
	'doc' => 'application/msword',
	'rtf' => 'application/rtf',
	'xls' => 'application/vnd.ms-excel',
	'ppt' => 'application/vnd.ms-powerpoint',

	// open office
	'odt' => 'application/vnd.oasis.opendocument.text',
	'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
	'odp' => 'application/vnd.oasis.opendocument.presentation',
	'odg' => 'application/vnd.oasis.opendocument.graphics',
	'odc' => 'application/vnd.oasis.opendocument.chart',
	'odf' => 'application/vnd.oasis.opendocument.formula',
	'odi' => 'application/vnd.oasis.opendocument.image',
	'odm' => 'application/vnd.oasis.opendocument.text-master',
	'odb' => 'application/vnd.oasis.opendocument.base',
	'odb' => 'application/vnd.oasis.opendocument.database',
	'ott' => 'application/vnd.oasis.opendocument.text-template',
	'ots' => 'application/vnd.oasis.opendocument.spreadsheet-template',
	'otp' => 'application/vnd.oasis.opendocument.presentation-template',
	'otg' => 'application/vnd.oasis.opendocument.graphics-template',
	'otc' => 'application/vnd.oasis.opendocument.chart-template',
	'otf' => 'application/vnd.oasis.opendocument.formula-template',
	'oti' => 'application/vnd.oasis.opendocument.image-template',
	'oth' => 'application/vnd.oasis.opendocument.text-web'
	);

	$dot = strpos($filename, '.');
	if ($dot !== false) {
		$ext = strtolower(substr($filename, $dot + 1));
		if (array_key_exists($ext, $mime_types)) {
			return $mime_types[$ext];
		}
	}

	return 'application/octet-stream';
}

/**
 * @brief Count files/attachments.
 * 
 * 
 * @param $channel_id
 * @param $observer
 * @param $hash (optional)
 * @param $filename (optional)
 * @param $filetype (optional)
 * @return array
 * 	$ret['success'] boolean
 * 	$ret['results'] amount of found results, or false
 * 	$ret['message'] string with error messages if any
 */
function attach_count_files($channel_id, $observer, $hash = '', $filename = '', $filetype = '') {

	$ret = array('success' => false);

	if(! perm_is_allowed($channel_id,$observer, 'read_storage')) {
		$ret['message'] = t('Permission denied.');
		return $ret;
	}

	require_once('include/security.php');
	$sql_extra = permissions_sql($channel_id);

	if($hash)
		$sql_extra .= protect_sprintf(" and hash = '" . dbesc($hash) . "' ");

	if($filename)
		$sql_extra .= protect_sprintf(" and filename like '@" . dbesc($filename) . "@' ");

	if($filetype)
		$sql_extra .= protect_sprintf(" and filetype like '@" . dbesc($filetype) . "@' ");

	$r = q("select id from attach where uid = %d $sql_extra",
		intval($channel_id)
	);

	$ret['success'] = ((is_array($r)) ? true : false);
	$ret['results'] = ((is_array($r)) ? count($r) : false);

	return $ret;
}

/**
 * @brief Returns a list of files/attachments.
 * 
 * @param $channel_id
 * @param $observer
 * @param $hash (optional)
 * @param $filename (optional)
 * @param $filetype (optional)
 * @param $orderby
 * @param $start
 * @param $entries
 * @return array
 * 	$ret['success'] boolean
 * 	$ret['results'] array with results, or false
 * 	$ret['message'] string with error messages if any
 */
function attach_list_files($channel_id, $observer, $hash = '', $filename = '', $filetype = '', $orderby = 'created desc', $start = 0, $entries = 0) {

	$ret = array('success' => false);

	if(! perm_is_allowed($channel_id,$observer, 'read_storage')) {
		$ret['message'] = t('Permission denied.');
		return $ret;
	}

	require_once('include/security.php');
	$sql_extra = permissions_sql($channel_id);

	if($hash)
		$sql_extra .= protect_sprintf(" and hash = '" . dbesc($hash) . "' ");

	if($filename)
		$sql_extra .= protect_sprintf(" and filename like '@" . dbesc($filename) . "@' ");

	if($filetype)
		$sql_extra .= protect_sprintf(" and filetype like '@" . dbesc($filetype) . "@' ");

	if($entries)
		$limit = " limit " . intval($start) . ", " . intval(entries) . " ";

	// Retrieve all columns except 'data'

	$r = q("select id, aid, uid, hash, filename, filetype, filesize, revision, folder, flags, created, edited, allow_cid, allow_gid, deny_cid, deny_gid from attach where uid = %d $sql_extra $orderby $limit",
		intval($channel_id)
	);

	$ret['success'] = ((is_array($r)) ? true : false);
	$ret['results'] = ((is_array($r)) ? $r : false);

	return $ret;
}

/**
 * @brief Find an attachment by hash and revision.
 * 
 * Returns the entire attach structure including data.
 * 
 * This could exhaust memory so most useful only when immediately sending the data.
 * 
 * @param $hash
 * @param $rev
 */
function attach_by_hash($hash, $rev = 0) {

	$ret = array('success' => false);

	// Check for existence, which will also provide us the owner uid

	$sql_extra = '';
	if($rev == (-1))
		$sql_extra = " order by revision desc ";
	elseif($rev)
		$sql_extra = " and revision = " . intval($rev) . " ";


	$r = q("SELECT uid FROM attach WHERE hash = '%s' $sql_extra LIMIT 1",
		dbesc($hash)
	);
	if(! $r) {
		$ret['message'] = t('Item was not found.');
		return $ret;
	}

	if(! perm_is_allowed($r[0]['uid'], get_observer_hash(), 'view_storage')) {
		$ret['message'] = t('Permission denied.');
		return $ret;
	}

	$sql_extra = permissions_sql($r[0]['uid']);

	// Now we'll see if we can access the attachment

	$r = q("SELECT * FROM attach WHERE hash = '%s' and uid = %d $sql_extra LIMIT 1",
		dbesc($hash),
		intval($r[0]['uid'])
	);

	if(! $r) {
		$ret['message'] = t('Permission denied.');
		return $ret;
	}

	$ret['success'] = true;
	$ret['data'] = $r[0];

	return $ret;
}

/**
 * @brief Find an attachment by hash and revision.
 * 
 * Returns the entire attach structure excluding data.
 * 
 * @see attach_by_hash()
 * @param $hash
 * @param $ref
 */
function attach_by_hash_nodata($hash, $rev = 0) {

	$ret = array('success' => false);

	// Check for existence, which will also provide us the owner uid

	$sql_extra = '';
	if($rev == (-1))
		$sql_extra = " order by revision desc ";
	elseif($rev)
		$sql_extra = " and revision = " . intval($rev) . " ";

	$r = q("SELECT uid FROM attach WHERE hash = '%s' $sql_extra LIMIT 1",
		dbesc($hash)
	);
	if(! $r) {
		$ret['message'] = t('Item was not found.');
		return $ret;
	}

	if(! perm_is_allowed($r[0]['uid'],get_observer_hash(),'view_storage')) {
		$ret['message'] = t('Permission denied.');
		return $ret;
	}

	$sql_extra = permissions_sql($r[0]['uid']);

	// Now we'll see if we can access the attachment

	$r = q("select id, aid, uid, hash, creator, filename, filetype, filesize, revision, folder, flags, created, edited, allow_cid, allow_gid, deny_cid, deny_gid from attach where uid = %d and hash = '%s' $sql_extra limit 1",
		intval($r[0]['uid']),
		dbesc($hash)
	);

	if(! $r) {
		$ret['message'] = t('Permission denied.');
		return $ret;
	}

	$ret['success'] = true;
	$ret['data'] = $r[0];
	return $ret;
}

/**
 * @brief 
 *
 * @param $channel channel array of owner
 * @param $observer_hash hash of current observer
 * @param $options (optional)
 * @param $arr (optional)
 */
function attach_store($channel, $observer_hash, $options = '', $arr = null) {

	$ret = array('success' => false);
	$channel_id = $channel['channel_id'];
	$sql_options = '';

	if(! perm_is_allowed($channel_id,get_observer_hash(), 'write_storage')) {
		$ret['message'] = t('Permission denied.');
		return $ret;
	}

	// The 'update' option sets db values without uploading a new attachment
	// 'replace' replaces the existing uploaded data
	// 'revision' creates a new revision with new upload data
	// Default is to upload a new file

	// revise or update must provide $arr['hash'] of the thing to revise/update

	if($options !== 'update') {
		if(! x($_FILES,'userfile')) {
			$ret['message'] = t('No source file.');
			return $ret;
		}

		$src      = $_FILES['userfile']['tmp_name'];
		$filename = basename($_FILES['userfile']['name']);
		$filesize = intval($_FILES['userfile']['size']);
	}

	$existing_size = 0;

	if($options === 'replace') {
		$x = q("select id, hash, filesize from attach where id = %d and uid = %d limit 1",	
			intval($replace),
			intval($channel_id)
		);
		if(! $x) {
			$ret['message'] = t('Cannot locate file to replace');
			return $ret;
		}
		$existing_id = $x[0]['id'];
		$existing_size = intval($x[0]['filesize']);
		$hash = $x[0]['hash'];
	}
	
	if($options === 'revise' || $options === 'update') {
		$sql_options = " order by revision desc ";
		if($options === 'update' &&  $arr && array_key_exists('revision',$arr))
			$sql_options = " and revision = " . intval($arr['revision']) . " ";

		$x = q("select id, aid, uid, filename, filetype, filesize, hash, revision, folder, flags, created, edited, allow_cid, allow_gid, deny_cid, deny_gid from attach where hash = '%s' and uid = %d $sql_options limit 1",
			dbesc($arr['hash']),
			intval($channel_id)
		);
		if(! $x) {
			$ret['message'] = t('Cannot locate file to revise/update');
			return $ret;
		}
		$hash = $x[0]['hash'];
	}

	// Check storage limits
	if($options !== 'update') {
		$maxfilesize = get_config('system','maxfilesize');

		if(($maxfilesize) && ($filesize > $maxfilesize)) {
			$ret['message'] = sprintf( t('File exceeds size limit of %d'), $maxfilesize);
			@unlink($src);
			return $ret;
		}

		$limit = service_class_fetch($channel_id, 'attach_upload_limit');

		if($limit !== false) {
			$r = q("select sum(filesize) as total from attach where aid = %d ",
				intval($channel['channel_account_id'])
			);
			if(($r) &&  (($r[0]['total'] + $filesize) > ($limit - $existing_size))) {
				$ret['message'] = upgrade_message(true) . sprintf(t("You have reached your limit of %1$.0f Mbytes attachment storage."), $limit / 1024000);
				@unlink($src);
				return $ret;
			}
		}
		$mimetype = z_mime_content_type($filename);
	}

	if(! isset($hash))
		$hash = random_string();

	$created = datetime_convert();

	if($options === 'replace') {
		$r = q("update attach set filename = '%s', filetype = '%s', filesize = %d, data = '%s', edited = '%s' where id = %d and uid = %d",
			dbesc($filename),
			dbesc($mimetype),
			intval($filesize),
			dbescbin(@file_get_contents($src)),
			dbesc($created),
			intval($existing_id),
			intval($channel_id)
		);
	}
	elseif($options === 'revise') {
		$r = q("insert into attach ( aid, uid, hash, creator, filename, filetype, filesize, revision, data, created, edited, allow_cid, allow_gid, deny_cid, deny_gid )
			VALUES ( %d, %d, '%s', '%s', '%s', '%s', %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) ",
			intval($x[0]['aid']),
			intval($channel_id),
			dbesc($x[0]['hash']),
			dbesc(get_observer_hash()),
			dbesc($filename),
			dbesc($mimetype),
			intval($filesize),
			intval($x[0]['revision'] + 1),
			dbescbin(@file_get_contents($src)),
			dbesc($created),
			dbesc($created),
			dbesc($x[0]['allow_cid']),
			dbesc($x[0]['allow_gid']),
			dbesc($x[0]['deny_cid']),
			dbesc($x[0]['deny_gid'])
		);
	}
	elseif($options === 'update') {
		$r = q("update attach set filename = '%s', filetype = '%s', edited = '%s', 
			allow_cid = '%s', allow_gid = '%s', deny_cid = '%s', deny_gid  = '%s' where id = %d and uid = %d",
			dbesc((array_key_exists('filename',$arr))  ? $arr['filename']  : $x[0]['filename']),
			dbesc((array_key_exists('filetype',$arr))  ? $arr['filetype']  : $x[0]['filetype']),
			dbesc($created),
			dbesc((array_key_exists('allow_cid',$arr)) ? $arr['allow_cid'] : $x[0]['allow_cid']),
			dbesc((array_key_exists('allow_gid',$arr)) ? $arr['allow_gid'] : $x[0]['allow_gid']),
			dbesc((array_key_exists('deny_cid',$arr))  ? $arr['deny_cid']  : $x[0]['deny_cid']),
			dbesc((array_key_exists('deny_gid',$arr))  ? $arr['deny_gid']  : $x[0]['deny_gid']),
			intval($x[0]['id']),
			intval($x[0]['uid'])
		);
	}
	else {
		$r = q("INSERT INTO attach ( aid, uid, hash, creator, filename, filetype, filesize, revision, data, created, edited, allow_cid, allow_gid,deny_cid, deny_gid )
			VALUES ( %d, %d, '%s', '%s', '%s', '%s', %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) ",
			intval($channel['channel_account_id']),
			intval($channel_id),
			dbesc($hash),
			dbesc(get_observer_hash()),
			dbesc($filename),
			dbesc($mimetype),
			intval($filesize),
			intval(0),
			dbescbin(@file_get_contents($src)),
			dbesc($created),
			dbesc($created),
			dbesc(($arr && array_key_exists('allow_cid',$arr)) ? $arr['allow_cid'] : '<' . $channel['channel_hash'] . '>'),
			dbesc(($arr && array_key_exists('allow_gid',$arr)) ? $arr['allow_gid'] : ''),
			dbesc(($arr && array_key_exists('deny_cid',$arr))  ? $arr['deny_cid']  : ''),
			dbesc(($arr && array_key_exists('deny_gid',$arr))  ? $arr['deny_gid']  : '')
		);
	}

	if($options !== 'update')
		@unlink($src);

	if(! $r) {
		$ret['message'] = t('File upload failed. Possible system limit or action terminated.');
		return $ret;
	}

	// Caution: This re-uses $sql_options set further above

	$r = q("select id, aid, uid, hash, creator, filename, filetype, filesize, revision, folder, flags, created, edited, allow_cid, allow_gid, deny_cid, deny_gid from attach where uid = %d and hash = '%s' $sql_options limit 1",
		intval($channel_id),
		dbesc($hash)
	);

	if(! $r) {
		$ret['message'] = t('Stored file could not be verified. Upload failed.');
		return $ret;
	}

	$ret['success'] = true;
	$ret['data'] = $r[0];

	return $ret;
}

/**
 * Read a virtual directory and return contents, checking permissions of all parent components.
 * @function z_readdir
 * @param integer $channel_id
 * @param string $observer_hash hash of current observer
 * @param string $pathname
 * @param string $parent_hash (optional)
 *
 * @returns array $ret
 * $ret['success'] = boolean true or false
 * $ret['message'] = error message if success is false
 * $ret['data'] = array of attach DB entries without data component
 */
function z_readdir($channel_id, $observer_hash, $pathname, $parent_hash = '') {
	$ret = array('success' => false);

	if(! perm_is_allowed($r[0]['uid'], get_observer_hash(), 'view_storage')) {
		$ret['message'] = t('Permission denied.');
		return $ret;
	}

	if(strpos($pathname, '/')) {
		$paths = explode('/', $pathname);
		if(count($paths) > 1) {
			$curpath = array_shift($paths);

			$r = q("select hash, id from attach where uid = %d and filename = '%s' and (flags & %d )>0 " . permissions_sql($channel_id) . " limit 1",
				intval($channel_id),
				dbesc($curpath),
				intval(ATTACH_FLAG_DIR)
			);
			if(! $r) {
				$ret['message'] = t('Path not available.');		
				return $ret;
			}

			return z_readdir($channel_id, $observer_hash, implode('/', $paths), $r[0]['hash']);
		}
	}
	else
		$paths = array($pathname);
	
	$r = q("select id, aid, uid, hash, creator, filename, filetype, filesize, revision, folder, flags, created, edited, allow_cid, allow_gid, deny_cid, deny_gid from attach where id = %d and folder = '%s' and filename = '%s' and (flags & %d )>0 " . permissions_sql($channel_id),
		intval($channel_id),
		dbesc($parent_hash),
		dbesc($paths[0]),
		intval(ATTACH_FLAG_DIR)
	);
	if(! $r) {
		$ret['message'] = t('Path not available.');
		return $ret;
	}
	$ret['success'] = true;
	$ret['data'] = $r;

	return $ret;
}

/**
 * @function attach_mkdir($channel,$observer_hash,$arr);
 *
 * @brief Create directory.
 *
 * @param array $channel channel array of owner
 * @param string $observer_hash hash of current observer
 * @param array $arr parameter array to fulfil request
 * Required:
 *    $arr['filename']
 *    $arr['folder'] // hash of parent directory, empty string for root directory
 * Optional:
 *    $arr['hash']  // precumputed hash for this node
 *    $arr['allow_cid']
 *    $arr['allow_gid']
 *    $arr['deny_cid']
 *    $arr['deny_gid']
 */

function attach_mkdir($channel, $observer_hash, $arr = null) {

	$ret = array('success' => false);
	$channel_id = $channel['channel_id'];
	$sql_options = '';

	$basepath = 'store/' . $channel['channel_address'];

	logger('attach_mkdir: basepath: ' . $basepath);

	if(! is_dir($basepath))
		os_mkdir($basepath,STORAGE_DEFAULT_PERMISSIONS, true);

	if(! perm_is_allowed($channel_id, $observer_hash, 'write_storage')) {
		$ret['message'] = t('Permission denied.');
		return $ret;
	}

	if(! $arr['filename']) {
		$ret['message'] = t('Empty pathname');
		return $ret;
	}

	$arr['hash'] = (($arr['hash']) ? $arr['hash'] : random_string());

	// Check for duplicate name.
	// Check both the filename and the hash as we will be making use of both.
	
	$r = q("select hash from attach where ( filename = '%s' or hash = '%s' ) and folder = '%s' and uid = %d limit 1",
		dbesc($arr['filename']),
		dbesc($arr['hash']),
		dbesc($arr['folder']),
		intval($channel['channel_id'])
	);
	if($r) {
		$ret['message'] = t('duplicate filename or path');
		return $ret;
	}

	if($arr['folder']) {

		// Walk the directory tree from parent back to root to make sure the parent is valid and name is unique and we
		// have permission to see this path. This implies the root directory itself is public since we won't have permissions
		// set on the psuedo-directory. We can however set permissions for anything and everything contained within it.

		$lpath = '';
		$lfile = $arr['folder'];
		$sql_options = permissions_sql($channel['channel_id']);

		do {
			$r = q("select filename, hash, flags, folder from attach where uid = %d and hash = '%s' and ( flags & %d )>0 
				$sql_options limit 1",
				intval($channel['channel_id']),
				dbesc($lfile),
				intval(ATTACH_FLAG_DIR)
			);

			if(! $r) {
				logger('attach_mkdir: hash ' . $lfile . ' not found in ' . $lpath);
				$ret['message'] = t('Path not found.');
				return $ret;
			}
			if($lfile)
				$lpath = $r[0]['hash'] . '/' . $lpath;
			$lfile = $r[0]['folder'];
		} while ( ($r[0]['folder']) && ($r[0]['flags'] & ATTACH_FLAG_DIR)) ;
		$path = $basepath . '/' . $lpath;			
	}
	else
		$path = $basepath . '/';

	$path .= $arr['hash'];

	$created = datetime_convert();

	$r = q("INSERT INTO attach ( aid, uid, hash, creator, filename, filetype, filesize, revision, folder, flags, data, created, edited, allow_cid, allow_gid, deny_cid, deny_gid )
		VALUES ( %d, %d, '%s', '%s', '%s', '%s', %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) ",
		intval($channel['channel_account_id']),
		intval($channel_id),
		dbesc($arr['hash']),
		dbesc(get_observer_hash()),
		dbesc($arr['filename']),
		dbesc('multipart/mixed'),
		intval(0),
		intval(0),
		dbesc($arr['folder']),
		intval(ATTACH_FLAG_DIR|ATTACH_FLAG_OS),
		dbesc($path),
		dbesc($created),
		dbesc($created),
		dbesc(($arr && array_key_exists('allow_cid',$arr)) ? $arr['allow_cid'] : $channel['channel_allow_cid']),
		dbesc(($arr && array_key_exists('allow_gid',$arr)) ? $arr['allow_gid'] : $channel['channel_allow_gid']),
		dbesc(($arr && array_key_exists('deny_cid',$arr))  ? $arr['deny_cid']  : $channel['channel_deny_cid']),
		dbesc(($arr && array_key_exists('deny_gid',$arr))  ? $arr['deny_gid']  : $channel['channel_deny_gid'])
	);

	if($r) {
		if(os_mkdir($path, STORAGE_DEFAULT_PERMISSIONS, true)) {
			$ret['success'] = true;
			$ret['data'] = $arr;

			// update the parent folder's lastmodified timestamp
			$e = q("UPDATE attach SET edited = '%s' WHERE hash = '%s' AND uid = %d",
				dbesc($created),
				dbesc($arr['folder']),
				intval($channel_id)
			);
		}
		else {
			logger('attach_mkdir: ' . mkdir . ' ' . $path . 'failed.');
			$ret['message'] = t('mkdir failed.');
		}
	}
	else {
		$ret['message'] = t('database storage failed.');
	}

	return $ret;
}

/**
 * @brief Changes permissions of a file.
 * 
 * @param $channel_id
 * @param $resource
 * @param $allow_cid
 * @param $allow_gid
 * @param $deny_cid
 * @param $deny_gid
 * @param $recurse
 */
function attach_change_permissions($channel_id, $resource, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $recurse = false) {

	$r = q("select hash, flags from attach where hash = '%s' and uid = %d limit 1",
		dbesc($resource),
		intval($channel_id)
	);

	if(! $r)
		return;

	if($r[0]['flags'] & ATTACH_FLAG_DIR) {
		if($recurse) {
			$r = q("select hash, flags from attach where folder = '%s' and uid = %d",
				dbesc($resource),
				intval($channel_id)
			);
			if($r) {
				foreach($r as $rr) {
					attach_change_permissions($channel_id, $rr['hash'], $allow_cid, $allow_gid, $deny_cid, $deny_gid, $recurse);
				}
			}
		}
	}

	$x = q("update attach set allow_cid = '%s', allow_gid = '%s', deny_cid = '%s', deny_gid = '%s' where hash = '%s' and uid = %d",
		dbesc($allow_cid),
		dbesc($allow_gid),
		dbesc($deny_cid),
		dbesc($deny_gid),
		dbesc($resource),
		intval($channel_id)
	);
}

/**
 * @brief Delete a file/directory from a channel.
 *
 * If the provided resource hash is from a directory it will delete everything
 * recursively under this directory.
 *
 * @param int $channel_id
 *  The id of the channel
 * @param string $resource
 *  The hash to delete
 * @return void
 */
function attach_delete($channel_id, $resource) {

	$c = q("SELECT channel_address FROM channel WHERE channel_id = %d LIMIT 1",
		intval($channel_id)
	);

	$channel_address = (($c) ? $c[0]['channel_address'] : 'notfound');

	$r = q("SELECT hash, flags, folder FROM attach WHERE hash = '%s' AND uid = %d limit 1",
		dbesc($resource),
		intval($channel_id)
	);

	if(! $r)
		return;

	$cloudpath = get_parent_cloudpath($channel_id, $channel_address, $resource);
	$object = get_file_activity_object($channel_id, $resource, $cloudpath);

	// If resource is a directory delete everything in the directory recursive
	if($r[0]['flags'] & ATTACH_FLAG_DIR) {
		$x = q("SELECT hash, flags FROM attach WHERE folder = '%s' AND uid = %d",
			dbesc($resource),
			intval($channel_id)
		);
		if($x) {
			foreach($x as $xx) {
				attach_delete($channel_id, $xx['hash']);
			}
		}
	}

	// delete a file from filesystem
	if($r[0]['flags'] & ATTACH_FLAG_OS) {
		$y = q("SELECT data FROM attach WHERE hash = '%s' AND uid = %d LIMIT 1",
			dbesc($resource),
			intval($channel_id)
		);

		if($y) {
			$f = 'store/' . $channel_address . '/' . $y[0]['data'];
			if(is_dir($f))
				@rmdir($f);
			elseif(file_exists($f))
				unlink($f);
		}
	}

	// delete from database
	$z = q("DELETE FROM attach WHERE hash = '%s' AND uid = %d",
		dbesc($resource),
		intval($channel_id)
	);

	// update the parent folder's lastmodified timestamp
	$e = q("UPDATE attach SET edited = '%s' WHERE hash = '%s' AND uid = %d",
		dbesc(datetime_convert()),
		dbesc($r[0]['folder']),
		intval($channel_id)
	);

	file_activity($channel_id, $object, $allow_cid='', $allow_gid='', $deny_cid='', $deny_gid='', 'update', $no_activity=false);

}

/**
 * @brief Returns path to file in cloud/.
 * This function cannot be used with mod/dav as it always returns a path valid under mod/cloud
 * 
 * @param array
 *  $arr[uid] int the channels uid
 *  $arr[folder] string
 *  $arr[filename]] string
 * @return string
 *  path to the file in cloud/
 */
function get_cloudpath($arr) {
	$basepath = 'cloud/';

	if($arr['uid']) {
		$r = q("select channel_address from channel where channel_id = %d limit 1",
			intval($arr['uid'])
		);
		if($r)
			$basepath .= $r[0]['channel_address'] . '/';
	}

	$path = $basepath;

	if($arr['folder']) {
		$lpath = '';
		$lfile = $arr['folder'];

		do {
			$r = q("select filename, hash, flags, folder from attach where uid = %d and hash = '%s' and ( flags & %d )>0 
				limit 1",
				intval($arr['uid']),
				dbesc($lfile),
				intval(ATTACH_FLAG_DIR)
			);

			if(! $r)
				break;

			if($lfile)
				$lpath = $r[0]['filename'] . '/' . $lpath;
			$lfile = $r[0]['folder'];

		} while ( ($r[0]['folder']) && ($r[0]['flags'] & ATTACH_FLAG_DIR));

		$path .= $lpath;
	}
	$path .= $arr['filename'];

	return $path;
}

/**
 * @brief Returns path to parent folder in cloud/.
 * This function cannot be used with mod/dav as it always returns a path valid under mod/cloud
 *
 * @param int $channel_id
 *  The id of the channel
 * @param string $channel_name
 *  The name of the channel
 * @param string $attachHash
 * @return string with the full folder path
 */
function get_parent_cloudpath($channel_id, $channel_name, $attachHash) {
	// build directory tree
	$parentHash = $attachHash;
	do {
		$parentHash = find_folder_hash_by_attach_hash($channel_id, $parentHash);
		if ($parentHash) {
			$parentName = find_filename_by_hash($channel_id, $parentHash);
			$parentFullPath = $parentName . '/' . $parentFullPath;
		}
	} while ($parentHash);
	$parentFullPath = z_root() . '/cloud/' . $channel_name . '/' . $parentFullPath;

	return $parentFullPath;
}

/**
 * @brief Return the hash of an attachment's folder.
 *
 * @param int $channel_id
 *  The id of the channel
 * @param string $attachHash
 *  The hash of the attachment
 * @return string
 */
function find_folder_hash_by_attach_hash($channel_id, $attachHash) {
	$r = q("SELECT folder FROM attach WHERE uid = %d AND hash = '%s' LIMIT 1",
		intval($channel_id),
		dbesc($attachHash)
	);
	$hash = '';
	if ($r) {
		$hash = $r[0]['folder'];
	}
	return $hash;
}

/**
 * @brief Returns the filename of an attachment in a given channel.
 *
 * @param mixed $channel_id
 *  The id of the channel
 * @param mixed $attachHash
 *  The hash of the attachment
 * @return string
 *  The filename of the attachment
 */
function find_filename_by_hash($channel_id, $attachHash) {
	$r = q("SELECT filename FROM attach WHERE uid = %d AND hash = '%s' LIMIT 1",
		intval($channel_id),
		dbesc($attachHash)
	);
	$filename = '';
	if ($r) {
		$filename = $r[0]['filename'];
	}
	return $filename;
}

/**
 * 
 * @param $in
 * @param $out
 */
function pipe_streams($in, $out) {
	$size = 0;
	while (!feof($in))
		$size += fwrite($out, fread($in, 8192));
	return $size;
}

function file_activity($channel_id, $object, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $verb, $no_activity) {

	require_once('include/items.php');

	$poster = get_app()->get_observer();

	//if we got no object something went wrong
	if(!$object)
		return;

	$is_dir = (($object['flags'] & ATTACH_FLAG_DIR) ? true : false);

	//do not send activity for folders for now
	if($is_dir)
		return;

	//check for recursive perms if we are in a folder
	if($object['folder']) {

		$folder_hash = $object['folder'];

		$r_perms = recursive_activity_recipients($allow_cid, $allow_gid, $deny_cid, $deny_gid, $folder_hash);

		$allow_cid = perms2str($r_perms['allow_cid']);
		$allow_gid = perms2str($r_perms['allow_gid']);
		$deny_cid = perms2str($r_perms['deny_cid']);
		$deny_gid = perms2str($r_perms['deny_gid']);

	}

	$mid = item_message_id();

	$objtype = ACTIVITY_OBJ_FILE;

	$item_flags = ITEM_WALL|ITEM_ORIGIN;
;

	$private = (($allow_cid || $allow_gid || $deny_cid || $deny_gid) ? 1 : 0);

	$jsonobject = json_encode($object);

	//check if item for this object exists
	$y = q("SELECT * FROM item WHERE verb = '%s' AND obj_type = '%s' AND resource_id = '%s' AND uid = %d LIMIT 1",
		dbesc(ACTIVITY_POST),
		dbesc($objtype),
		dbesc($object['hash']),
		intval(local_channel())
	);

	if($y) {
		$update = true;
		$object['d_mid'] = $y[0]['mid']; //attach mid of the old object
		$u_jsonobject = json_encode($object);

		//we have got the relevant info - delete the old item before we create the new one
		$z = q("DELETE FROM item WHERE obj_type = '%s' AND verb = '%s' AND mid = '%s'",
			dbesc(ACTIVITY_OBJ_FILE),
			dbesc(ACTIVITY_POST),
			dbesc($y[0]['mid'])
		);

	}

	if($update && $verb == 'post' ) {
		//send update activity and create a new one

		$u_mid = item_message_id();

		$arr = array();

		$arr['aid']           = get_account_id();
		$arr['uid']           = $channel_id;
		$arr['mid']           = $u_mid;
		$arr['parent_mid']    = $u_mid;
		$arr['item_flags']    = $item_flags;
		$arr['item_unseen']   = 1;
		$arr['author_xchan']  = $poster['xchan_hash'];
		$arr['owner_xchan']   = $poster['xchan_hash'];
		$arr['title']         = '';
		//updates should be visible to everybody -> perms may have changed
		$arr['allow_cid']     = '';
		$arr['allow_gid']     = '';
		$arr['deny_cid']      = '';
		$arr['deny_gid']      = '';
		$arr['item_restrict']  = ITEM_HIDDEN;
		$arr['item_private']  = 0;
		$arr['verb']          = ACTIVITY_UPDATE;
		$arr['obj_type']      = $objtype;
		$arr['object']        = $u_jsonobject;
		$arr['resource_id']   = $object['hash'];
		$arr['resource_type'] = 'attach';
		$arr['body']          = '';

		$post = item_store($arr);
		$item_id = $post['item_id'];
		if($item_id) {
			proc_run('php',"include/notifier.php","activity",$item_id);
		}

		call_hooks('post_local_end', $arr);

		$update = false;

		//notice( t('File activity updated') . EOL);

	}

	if($no_activity) {
		return;
	}

	$arr = array();

	$arr['aid']           = get_account_id();
	$arr['uid']           = $channel_id;
	$arr['mid']           = $mid;
	$arr['parent_mid']    = $mid;
	$arr['item_flags']    = $item_flags;
	$arr['author_xchan']  = $poster['xchan_hash'];
	$arr['owner_xchan']   = $poster['xchan_hash'];
	$arr['title']         = '';
	$arr['allow_cid']     = $allow_cid;
	$arr['allow_gid']     = $allow_gid;
	$arr['deny_cid']      = $deny_cid;
	$arr['deny_gid']      = $deny_gid;
	$arr['item_restrict']  = ITEM_HIDDEN;
	$arr['item_private']  = $private;
	$arr['verb']          = (($update) ? ACTIVITY_UPDATE : ACTIVITY_POST);
	$arr['obj_type']      = $objtype;
	$arr['resource_id']   = $object['hash'];
	$arr['resource_type'] = 'attach';
	$arr['object']        = (($update) ? $u_jsonobject : $jsonobject);
	$arr['body']          = '';

	$post = item_store($arr);
	$item_id = $post['item_id'];

	if($item_id) {
		proc_run('php',"include/notifier.php","activity",$item_id);
	}

	call_hooks('post_local_end', $arr);

	//(($verb === 'post') ?  notice( t('File activity posted') . EOL) : notice( t('File activity dropped') . EOL));

	return;

}

function get_file_activity_object($channel_id, $hash, $cloudpath) {

	$x = q("SELECT creator, filename, filetype, filesize, revision, folder, flags, created, edited FROM attach WHERE uid = %d AND hash = '%s' LIMIT 1",
		intval($channel_id),
		dbesc($hash)
	);

	$url = rawurlencode($cloudpath . $x[0]['filename']);

	$links   = array();
	$links[] = array(
		'rel'  => 'alternate',
		'type' => 'text/html',
		'href' => $url
	);

	$object = array(
		'type'  => ACTIVITY_OBJ_FILE,
		'title' => $x[0]['filename'],
		'id'    => $url,
		'link'  => $links,

		'hash'		=> $hash,
		'creator'	=> $x[0]['creator'],
		'filename'	=> $x[0]['filename'],
		'filetype'	=> $x[0]['filetype'],
		'filesize'	=> $x[0]['filesize'],
		'revision'	=> $x[0]['revision'],
		'folder'	=> $x[0]['folder'],
		'flags'		=> $x[0]['flags'],
		'created'	=> $x[0]['created'],
		'edited'	=> $x[0]['edited']
	);
	return $object;

}

function recursive_activity_recipients($allow_cid, $allow_gid, $deny_cid, $deny_gid, $folder_hash) {

	$poster = get_app()->get_observer();

	$arr_allow_cid = expand_acl($allow_cid);
	$arr_allow_gid = expand_acl($allow_gid);
	$arr_deny_cid = expand_acl($deny_cid);
	$arr_deny_gid = expand_acl($deny_gid);

	$count = 0;
	while($folder_hash) {
		$x = q("SELECT allow_cid, allow_gid, deny_cid, deny_gid, folder FROM attach WHERE hash = '%s' LIMIT 1",
			dbesc($folder_hash)
		);

		//only process private folders
		if($x[0]['allow_cid'] || $x[0]['allow_gid'] || $x[0]['deny_cid'] || $x[0]['deny_gid']) {

			$parent_arr['allow_cid'][] = expand_acl($x[0]['allow_cid']);
			$parent_arr['allow_gid'][] = expand_acl($x[0]['allow_gid']);

			//TODO: should find a much better solution for the allow_cid <-> allow_gid problem.
			//Do not use allow_gid for now. Instead lookup the members of the group directly and add them to allow_cid.
			if($parent_arr['allow_gid']) {
				foreach($parent_arr['allow_gid'][$count] as $gid) {
					$in_group = in_group($gid);
					$parent_arr['allow_cid'][$count] = array_unique(array_merge($parent_arr['allow_cid'][$count], $in_group));
				}
			}

			$parent_arr['deny_cid'][] = expand_acl($x[0]['deny_cid']);
			$parent_arr['deny_gid'][] = expand_acl($x[0]['deny_gid']);

			$count++;

		}

		$folder_hash = $x[0]['folder'];

	}

	//if none of the parent folders is private just return file perms
	if(!$parent_arr['allow_cid'] && !$parent_arr['allow_gid'] && !$parent_arr['deny_cid'] && !$parent_arr['deny_gid']) {
		$ret['allow_gid'] = $arr_allow_gid;
		$ret['allow_cid'] = $arr_allow_cid;
		$ret['deny_gid'] = $arr_deny_gid;
		$ret['deny_cid'] = $arr_deny_cid;

		return $ret;
	}

	//if there are no perms on the file we get them from the first parent folder
	if(!$arr_allow_cid && !$arr_allow_gid && !$arr_deny_cid && !$arr_deny_gid) {
		$arr_allow_cid = $parent_arr['allow_cid'][0];
		$arr_allow_gid = $parent_arr['allow_gid'][0];
		$arr_deny_cid = $parent_arr['deny_cid'][0];
		$arr_deny_gid = $parent_arr['deny_gid'][0];
	}

	//allow_cid
	$r_arr_allow_cid = false;
	foreach ($parent_arr['allow_cid'] as $folder_arr_allow_cid) {
		foreach ($folder_arr_allow_cid as $ac_hash) {
			$count_values[$ac_hash]++;
		}
	}
	foreach ($arr_allow_cid as $fac_hash) {
		if($count_values[$fac_hash] == $count)
			$r_arr_allow_cid[] = $fac_hash;
	}

	//allow_gid
	$r_arr_allow_gid = false;
	foreach ($parent_arr['allow_gid'] as $folder_arr_allow_gid) {
		foreach ($folder_arr_allow_gid as $ag_hash) {
			$count_values[$ag_hash]++;
		}
	}
	foreach ($arr_allow_gid as $fag_hash) {
		if($count_values[$fag_hash] == $count)
			$r_arr_allow_gid[] = $fag_hash;
	}

	//deny_gid
	foreach($parent_arr['deny_gid'] as $folder_arr_deny_gid) {
		$r_arr_deny_gid = array_merge($arr_deny_gid, $folder_arr_deny_gid);
	}
	$r_arr_deny_gid = array_unique($r_arr_deny_gid);

	//deny_cid
	foreach($parent_arr['deny_cid'] as $folder_arr_deny_cid) {
		$r_arr_deny_cid = array_merge($arr_deny_cid, $folder_arr_deny_cid);
	}
	$r_arr_deny_cid = array_unique($r_arr_deny_cid);

	//if none is allowed restrict to self
	if(($r_arr_allow_gid === false) && ($r_arr_allow_cid === false)) {
		$ret['allow_cid'] = $poster['xchan_hash'];
	} else {
		$ret['allow_gid'] = $r_arr_allow_gid;
		$ret['allow_cid'] = $r_arr_allow_cid;
		$ret['deny_gid'] = $r_arr_deny_gid;
		$ret['deny_cid'] = $r_arr_deny_cid;
	}

	return $ret;

}

function in_group($group_id) {
	$r = q("SELECT xchan FROM group_member left join groups on group_member.gid = group.id WHERE hash = '%s' ",
		dbesc($group_id)
	);

	foreach($r as $ig) {
		$group_members[] = $ig['xchan'];
	}

	return $group_members;
}
