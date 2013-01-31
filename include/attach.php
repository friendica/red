<?php


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
// can't use this because we're just passing a name, e.g. not a file that can be opened
//	elseif (function_exists('finfo_open')) {
//		$finfo = @finfo_open(FILEINFO_MIME);
//		$mimetype = @finfo_file($finfo, $filename);
//		@finfo_close($finfo);
//		return $mimetype;
//	}
	else {
		return 'application/octet-stream';
	}
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

	$r = q("select id from attach where channel_id = %d $sql_extra",
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

	$r = q("select id, aid, uid, hash, filename, filetype, filesize, created, edited, allow_cid, allow_gid, deny_cid, deny_gid from attach where channel_id = %d $sql_extra $orderby $limit",
		intval($channel_id)
	);

	$ret['success'] = ((is_array($r)) ? true : false);
	$ret['results'] = ((is_array($r)) ? $r : false);
	return $ret; 

}
