<?php /** @file */

/*
 * File/attach API with the potential for revision control.
 *
 * TODO: a filesystem storage abstraction which maintains security (and 'data' contains a system filename
 * which is inaccessible from the web). This could get around PHP storage limits and store videos and larger
 * items, using fread or OS methods or native code to read/write or chunk it through.
 * Also an 'append' option to the storage function might be a useful addition. 
 */

require_once('include/permissions.php');
require_once('include/security.php');

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
	);

	$dot = strpos($filename,'.');
	if($dot !== false) {
		$ext = strtolower(substr($filename,$dot+1));
		if (array_key_exists($ext, $mime_types)) {
			return $mime_types[$ext];
		}


	}

	return 'application/octet-stream';

}



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

// Find an attachment by hash and revision. Returns the entire attach structure including data. 
// This could exhaust memory so most useful only when immediately sending the data.  

function attach_by_hash($hash,$rev = 0) {

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


	$r = q("SELECT * FROM attach WHERE hash = '%s' and uid = %d $sql_extra LIMIT 1",
		dbesc($hash),
		intval($r[0]['uid'])
	);

	if(! $r) {
		$ret['message'] =  t('Permission denied.');
		return $ret;
	}

	$ret['success'] = true;
	$ret['data'] = $r[0];
	return $ret;

}



function attach_by_hash_nodata($hash,$rev = 0) {

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

	$r = q("select id, aid, uid, hash, filename, filetype, filesize, revision, folder, flags, created, edited, allow_cid, allow_gid, deny_cid, deny_gid from attach where uid = %d and hash = '%s' $sql_extra limit 1",
		intval($r[0]['uid']),
		dbesc($hash)
	);

	if(! $r) {
		$ret['message'] =  t('Permission denied.');
		return $ret;
	}

	$ret['success'] = true;
	$ret['data'] = $r[0];
	return $ret;

}




function attach_store($channel,$observer_hash,$options = '',$arr = null) {


	$ret = array('success' => false);
	$channel_id = $channel['channel_id'];
	$sql_options = '';

	if(! perm_is_allowed($channel_id,get_observer_hash(),'write_storage')) {
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

		$x =q("select id, aid, uid, filename, filetype, filesize, hash, revision, folder, flags, created, edited, allow_cid, allow_gid, deny_cid, deny_gid from attach where hash = '%s' and uid = %d $sql_options limit 1",
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

		$limit = service_class_fetch($channel_id,'attach_upload_limit');
		if($limit !== false) {
			$r = q("select sum(filesize) as total from attach where aid = %d ",
				intval($channel['channel_account_id'])
			);
			if(($r) &&  (($r[0]['total'] + $filesize) > ($limit - $existing_size))) {
				$ret['message'] = upgrade_message(true).sprintf(t("You have reached your limit of %1$.0f Mbytes attachment storage."),$limit / 1024000);
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
		$r = q("update attach set filename = '%s', filetype = '%s', filesize = %d, data = '%s', edited = '%s' where id = %d and uid = %d limit 1",
			dbesc($filename),
			dbesc($mimetype),
			intval($filesize),
			dbesc(@file_get_contents($src)),
			dbesc($created),
			intval($existing_id),
			intval($channel_id)
		);
	}
	elseif($options === 'revise') {
		$r = q("insert into attach ( aid, uid, hash, filename, filetype, filesize, revision, data, created, edited, allow_cid, allow_gid, deny_cid, deny_gid )
			VALUES ( %d, %d, '%s', '%s', '%s', %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) ",
			intval($x[0]['aid']),
			intval($channel_id),
			dbesc($x[0]['hash']),
			dbesc($filename),
			dbesc($mimetype),
			intval($filesize),
			intval($x[0]['revision'] + 1),
			dbesc(@file_get_contents($src)),
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
			allow_cid = '%s', allow_gid = '%s', deny_cid = '%s', deny_gid  = '%s' where id = %d and uid = %d limit 1",
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
		$r = q("INSERT INTO attach ( aid, uid, hash, filename, filetype, filesize, revision, data, created, edited, allow_cid, allow_gid,deny_cid, deny_gid )
			VALUES ( %d, %d, '%s', '%s', '%s', %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) ",
			intval($channel['channel_account_id']),
			intval($channel_id),
			dbesc($hash),
			dbesc($filename),
			dbesc($mimetype),
			intval($filesize),
			intval(0),
			dbesc(@file_get_contents($src)),
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

	$r = q("select id, aid, uid, hash, filename, filetype, filesize, revision, folder, flags, created, edited, allow_cid, allow_gid, deny_cid, deny_gid from attach where uid = %d and hash = '%s' $sql_options limit 1",
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
 * @param string $observer_hash
 * @param string $pathname
 * @param string $parent_hash (optional)
 *
 * @returns array $ret
 * $ret['success'] = boolean true or false
 * $ret['message'] = error message if success is false
 * $ret['data'] = array of attach DB entries without data component
 */

function z_readdir($channel_id,$observer_hash,$pathname, $parent_hash = '') {

	$ret = array('success' => false);
	if(! perm_is_allowed($r[0]['uid'],get_observer_hash(),'view_storage')) {
		$ret['message'] = t('Permission denied.');
		return $ret;
	}


	if(strpos($pathname,'/')) {
		$paths = explode('/',$pathname);
		if(count($paths) > 1) {
			$curpath = array_shift($paths);

			$r = q("select hash, id from attach where uid = %d and filename = '%s' and (flags & %d ) " . permissions_sql($channel_id) . " limit 1",
				intval($channel_id),
				dbesc($curpath),
				intval(ATTACH_FLAG_DIR)
			);
			if(! $r) {
				$ret['message'] = t('Path not available.');		
				return $ret;
			}

			return z_readdir($channel_id,$observer_hash,implode('/',$paths),$r[0]['hash']);
		}
	}
	else
		$paths = array($pathname);
	
	$r = q("select id, aid, uid, hash, filename, filetype, filesize, revision, folder, flags, created, edited, allow_cid, allow_gid, deny_cid, deny_gid from attach where id = %d and folder = '%s' and filename = '%s' and (flags & %d ) " . permissions_sql($channel_id),
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
 * Create directory
 *  
 * @param $channel channel array of owner
 * @param $observer_hash hash of current observer
 * @param $arr parameter array to fulfil request
 * 
 * Required:
 *    $arr['filename']
 *    $arr['folder'] // hash of parent directory, empty string for root directory
 *
 * Optional:
 *    $arr['hash']  // precumputed hash for this node
 *    $arr['allow_cid']
 *    $arr['allow_gid']
 *    $arr['deny_cid']
 *    $arr['deny_gid']
 */

function attach_mkdir($channel,$observer_hash,$arr = null) {

	$ret = array('success' => false);
	$channel_id = $channel['channel_id'];
	$sql_options = '';

	$basepath = 'store/' . $channel['channel_address'];

	logger('attach_mkdir: basepath: ' . $basepath);

	if(! is_dir($basepath))
		mkdir($basepath,STORAGE_DEFAULT_PERMISSIONS,true);


	if(! perm_is_allowed($channel_id, $observer_hash,'write_storage')) {
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

			$r = q("select filename, hash, flags, folder from attach where uid = %d and hash = '%s' and ( flags & %d ) 
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

	$r = q("INSERT INTO attach ( aid, uid, hash, filename, filetype, filesize, revision, folder, flags, data, created, edited, allow_cid, allow_gid, deny_cid, deny_gid )
		VALUES ( %d, %d, '%s', '%s', '%s', %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) ",
		intval($channel['channel_account_id']),
		intval($channel_id),
		dbesc($arr['hash']),
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
		if(mkdir($path,STORAGE_DEFAULT_PERMISSIONS)) {
			$ret['success'] = true;
			$ret['data'] = $arr;
		}
		else {
			logger('attach_mkdir: ' . mkdir . ' ' . $path . 'failed.');
			$ret['message'] = t('mkdir failed.');
		}
	}
	else
		$ret['message'] = t('database storage failed.');

	return $ret;
 
}



function attach_change_permissions($channel_id,$resource,$allow_cid,$allow_gid,$deny_cid,$deny_gid,$recurse = false) {

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
					attach_change_permissions($channel_id,$resource,$allow_cid,$allow_gid,$deny_cid,$deny_gid,$recurse);
				}
			}
		}
	}

	$x = q("update attach set allow_cid = '%s', allow_gid = '%s', deny_cid = '%s', deny_gid = '%s' where hash = '%s' and uid = %d limit 1",
		dbesc($allow_cid),
		dbesc($allow_gid),
		dbesc($deny_cid),
		dbesc($deny_gid),
		dbesc($resource),
		intval($channel_id)
	);

	return;
}
			 	


function attach_delete($channel_id,$resource) {


	$c = q("select channel_address from channel where channel_id = %d limit 1",
		intval($channel_id)
	);

	$channel_address = (($c) ? $c[0]['channel_address'] : 'notfound');

	$r = q("select hash, flags from attach where hash = '%s' and uid = %d limit 1",
		dbesc($resource),
		intval($channel_id)
	);

	if(! $r)
		return;

	if($r[0]['flags'] & ATTACH_FLAG_DIR) {
		$x = q("select hash, flags from attach where folder = '%s' and uid = %d",
			dbesc($resource),
			intval($channel_id)
		);
		if($x) {
			foreach($x as $xx) {
				attach_delete($channel_id,$xx['hash']);
			}
		}
	}
	if($r[0]['flags'] & ATTACH_FLAG_OS) {
		$y = q("select data from attach where hash = '%s' and uid = %d limit 1",
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

	$z = q("delete from attach where hash = '%s' and uid = %d limit 1",
		dbesc($resource),
		intval($channel_id)
	);

	return;
}
			 	





function pipe_streams($in, $out) {
	$size = 0;
    while (!feof($in))
		$size += fwrite($out,fread($in,8192));
	return $size;
}