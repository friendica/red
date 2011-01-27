<?php


function js_upload_install() {
	register_hooks('photo_post_init', 'addon/js_upload/js_upload.php', 'js_upload_post_init');
	register_hooks('photo_post_file', 'addon/js_upload/js_upload.php', 'js_upload_post_file');
	register_hooks('photo_post_end',  'addon/js_upload/js_upload.php', 'js_upload_post_end');
}


function js_upload_uninstall() {
	register_hooks('photo_post_init', 'addon/js_upload/js_upload.php', 'js_upload_post_init');
	register_hooks('photo_post_file', 'addon/js_upload/js_upload.php', 'js_upload_post_file');
	register_hooks('photo_post_end',  'addon/js_upload/js_upload.php', 'js_upload_post_end');
}


function js_upload_post_init(&$a,&$b) {

	// list of valid extensions, ex. array("jpeg", "xml", "bmp")

	$allowedExtensions = array("jpeg","gif","png","jpg");

	// max file size in bytes

	$sizeLimit = 6 * 1024 * 1024;

	$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
	$result = $uploader->handleUpload('uploads/');

	// to pass data through iframe you will need to encode all html tags
	$a->data['upload_jsonresponse'] =  htmlspecialchars(json_encode($result), ENT_NOQUOTES);

	if(isset($result['error'])) {
		logger('mod/photos.php: photos_post(): error uploading photo: ' . $result['error'] , 'LOGGER_DEBUG');
		killme();
	}


}

function js_upload_photo_post_file(&$a,&$b) {

	$b['src']		= 'uploads/'.$result['filename'];
	$b['filename']	= $result['filename'];
	$b['filesize']	= filesize($src);
}


function js_upload_photo_post_end(&$a,&$b) {

	if(x($a->data,'upload_jsonresponse')) {
		echo $a->data['upload_jsonresponse'];
		@unlink($src);
		killme();
	}

}


/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr {
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {    
        $input = fopen("php://input", "r");
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);
        
        if ($realSize != $this->getSize()){            
            return false;
        }
        
        $target = fopen($path, "w");        
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);
        
        return true;
    }
    function getName() {
        return $_GET['qqfile'];
    }
    function getSize() {
        if (isset($_SERVER["CONTENT_LENGTH"])){
            return (int)$_SERVER["CONTENT_LENGTH"];            
        } else {
            throw new Exception('Getting content length is not supported.');
        }      
    }   
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm {  
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {
        if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)){
            return false;
        }
        return true;
    }
    function getName() {
        return $_FILES['qqfile']['name'];
    }
    function getSize() {
        return $_FILES['qqfile']['size'];
    }
}
class qqFileUploader {
    private $allowedExtensions = array();
    private $sizeLimit = 10485760;
    private $file;

    function __construct(array $allowedExtensions = array(), $sizeLimit = 10485760){        
        $allowedExtensions = array_map("strtolower", $allowedExtensions);
            
        $this->allowedExtensions = $allowedExtensions;        
        $this->sizeLimit = $sizeLimit;
        
        $this->checkServerSettings();       

        if (isset($_GET['qqfile'])) {
            $this->file = new qqUploadedFileXhr();
        } elseif (isset($_FILES['qqfile'])) {
            $this->file = new qqUploadedFileForm();
        } else {
            $this->file = false; 
        }
    }
    
    private function checkServerSettings(){        
        $postSize = $this->toBytes(ini_get('post_max_size'));
        $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));        
		logger('mod/photos.php: qqFileUploader(): upload_max_filesize=' . $uploadSize , 'LOGGER_DEBUG');
        if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit){
            $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';             
            die("{'error':'increase post_max_size and upload_max_filesize to $size'}");    
        }        
    }
    
    private function toBytes($str){
        $val = trim($str);
        $last = strtolower($str[strlen($str)-1]);
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;        
        }
        return $val;
    }
    
    /**
     * Returns array('success'=>true) or array('error'=>'error message')
     */
    function handleUpload($uploadDirectory, $replaceOldFile = FALSE){
        if (!is_writable($uploadDirectory)){
            return array('error' => t('Server error. Upload directory isn't writable.'));
        }
        
        if (!$this->file){
            return array('error' => t('No files were uploaded.'));
        }
        
        $size = $this->file->getSize();
        
        if ($size == 0) {
            return array('error' => t('Uploaded file is empty'));
        }
        
        if ($size > $this->sizeLimit) {

            return array('error' => t('Uploaded file is too large'));
        }
        

		$maximagesize = get_config('system','maximagesize');

		if(($maximagesize) && ($size > $maximagesize)) {
			return array('error' => t('Image exceeds size limit of ') . $maximagesize );

		}

        $pathinfo = pathinfo($this->file->getName());
        $filename = $pathinfo['filename'];
        //$filename = md5(uniqid());
        $ext = $pathinfo['extension'];

        if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
            $these = implode(', ', $this->allowedExtensions);
            return array('error' => t('File has an invalid extension, it should be one of ') . $these . '.');
        }
        
        if(!$replaceOldFile){
            /// don't overwrite previous files that were uploaded
            while (file_exists($uploadDirectory . $filename . '.' . $ext)) {
                $filename .= rand(10, 99);
            }
        }
        
        if ($this->file->save($uploadDirectory . $filename . '.' . $ext)){
            return array('success'=>true,'filename' => $filename . '.' . $ext);
        } else {
            return array('error'=> t('Could not save uploaded file.') .
                t('The upload was cancelled, or server error encountered'),'filename' => $filename . '.' . $ext);
        }
        
    }    
}
