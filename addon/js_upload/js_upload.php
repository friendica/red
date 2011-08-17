<?php

/**
 * Name: JS Uploader
 * Description: JavaScript photo/image uploader. Uses Valum 'qq' Uploader.
 * Version: 1.0
 * Author: Chris Case <http://friendika.openmindspace.org/profile/chris_case>
 */

/**
 *
 * JavaScript Photo/Image Uploader
 *
 * Uses Valum 'qq' Uploader. 
 * Module Author: Chris Case
 *
 */


function js_upload_install() {
	register_hook('photo_upload_form', 'addon/js_upload/js_upload.php', 'js_upload_form');
	register_hook('photo_post_init',   'addon/js_upload/js_upload.php', 'js_upload_post_init');
	register_hook('photo_post_file',   'addon/js_upload/js_upload.php', 'js_upload_post_file');
	register_hook('photo_post_end',    'addon/js_upload/js_upload.php', 'js_upload_post_end');
}


function js_upload_uninstall() {
	unregister_hook('photo_upload_form', 'addon/js_upload/js_upload.php', 'js_upload_form');
	unregister_hook('photo_post_init',   'addon/js_upload/js_upload.php', 'js_upload_post_init');
	unregister_hook('photo_post_file',   'addon/js_upload/js_upload.php', 'js_upload_post_file');
	unregister_hook('photo_post_end',    'addon/js_upload/js_upload.php', 'js_upload_post_end');
}


function js_upload_form(&$a,&$b) {

	$b['default_upload'] = false;

	$b['addon_text'] .= '<link href="' . $a->get_baseurl() . '/addon/js_upload/file-uploader/client/fileuploader.css" rel="stylesheet" type="text/css">';
	$b['addon_text'] .= '<script src="' . $a->get_baseurl() . '/addon/js_upload/file-uploader/client/fileuploader.js" type="text/javascript"></script>';
   
	$upload_msg = t('Upload a file');
	$drop_msg = t('Drop files here to upload');
	$cancel = t('Cancel');
	$failed = t('Failed');

	$b['addon_text'] .= <<< EOT
	
 <div id="file-uploader-demo1">		
  <noscript>			
   <p>Please enable JavaScript to use file uploader.</p>
   <!-- or put a simple form for upload here -->
  </noscript> 
 </div>

<script type="text/javascript">
var uploader = null;       
function getSelected(opt) {
            var selected = new Array();
            var index = 0;
            for (var intLoop = 0; intLoop < opt.length; intLoop++) {
               if ((opt[intLoop].selected) ||
                   (opt[intLoop].checked)) {
                  index = selected.length;
                  //selected[index] = new Object;
                  selected[index] = opt[intLoop].value;
                  //selected[index] = intLoop;
               }
            }
            return selected;
         } 
function createUploader() {
	uploader = new qq.FileUploader({
		element: document.getElementById('file-uploader-demo1'),
		action: '{$b['post_url']}',

        template: '<div class="qq-uploader">' + 
                '<div class="qq-upload-drop-area"><span>$drop_msg</span></div>' +
                '<div class="qq-upload-button">$upload_msg</div>' +
                '<ul class="qq-upload-list"></ul>' + 
             '</div>',

        // template for one item in file list
        fileTemplate: '<li>' +
                '<span class="qq-upload-file"></span>' +
                '<span class="qq-upload-spinner"></span>' +
                '<span class="qq-upload-size"></span>' +
                '<a class="qq-upload-cancel" href="#">$cancel</a>' +
                '<span class="qq-upload-failed-text">$failed</span>' +
            '</li>',        

		debug: true,
		onSubmit: function(id,filename) {
			if (typeof acl!="undefined"){
				uploader.setParams( {
					newalbum		:	document.getElementById('photos-upload-newalbum').value,
					album			:	document.getElementById('photos-upload-album-select').value,
					group_allow		:	acl.allow_gid.join(','),
					contact_allow	:	acl.allow_cid.join(','),
					group_deny		:	acl.deny_gid.join(','),
					contact_deny	:	acl.deny_cid.join(',')
				});
			} else {
				uploader.setParams( {
					newalbum		:	document.getElementById('photos-upload-newalbum').value,
					album			:	document.getElementById('photos-upload-album-select').value,
					group_allow		:	getSelected(document.getElementById('group_allow')).join(','),
					contact_allow	:	getSelected(document.getElementById('contact_allow')).join(','),
					group_deny		:	getSelected(document.getElementById('group_deny')).join(','),
					contact_deny	:	getSelected(document.getElementById('contact_deny')).join(',')
				});
			}
		}
	});           
}


// in your app create uploader as soon as the DOM is ready
// don't wait for the window to load  
window.onload = createUploader;     


</script>
 
EOT;


}

function js_upload_post_init(&$a,&$b) {

	// list of valid extensions, ex. array("jpeg", "xml", "bmp")

	$allowedExtensions = array("jpeg","gif","png","jpg");

	// max file size in bytes

	$sizeLimit = get_config('system','maximagesize'); //6 * 1024 * 1024;

	$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);

	$result = $uploader->handleUpload();


	// to pass data through iframe you will need to encode all html tags
	$a->data['upload_jsonresponse'] =  htmlspecialchars(json_encode($result), ENT_NOQUOTES);

	if(isset($result['error'])) {
		logger('mod/photos.php: photos_post(): error uploading photo: ' . $result['error'] , 'LOGGER_DEBUG');
		echo json_encode($result);
		killme();
	}

	$a->data['upload_result'] = $result;

}

function js_upload_post_file(&$a,&$b) {

	$result = $a->data['upload_result'];

	$b['src']		= $result['path'];
	$b['filename']	= $result['filename'];
	$b['filesize']	= filesize($b['src']);

}


function js_upload_post_end(&$a,&$b) {

logger('upload_post_end');
	if(x($a->data,'upload_jsonresponse')) {
		echo $a->data['upload_jsonresponse'];
		killme();
	}

}


/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr {

	private $pathnm = '';

    /**
     * Save the file in the temp dir.
     * @return boolean TRUE on success
     */
    function save() {    
        $input = fopen("php://input", "r");
        $this->pathnm = tempnam(sys_get_temp_dir(),'frn');
		$temp = fopen($this->pathnm,"w");
        $realSize = stream_copy_to_stream($input, $temp);

        fclose($input);
		fclose($temp);
        
        if ($realSize != $this->getSize()){            
            return false;
        }
        return true;
    }

	function getPath() {
		return $this->pathnm;
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


    function save() {
        return true;
    }

	function getPath() {
		return $_FILES['qqfile']['tmp_name'];
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
        
        if (isset($_GET['qqfile'])) {
            $this->file = new qqUploadedFileXhr();
        } elseif (isset($_FILES['qqfile'])) {
            $this->file = new qqUploadedFileForm();
        } else {
            $this->file = false; 
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
    function handleUpload(){
        
        if (!$this->file){
            return array('error' => t('No files were uploaded.'));
        }
        
        $size = $this->file->getSize();
        
        if ($size == 0) {
            return array('error' => t('Uploaded file is empty'));
        }
        
//        if ($size > $this->sizeLimit) {

//            return array('error' => t('Uploaded file is too large'));
//        }
        

		$maximagesize = get_config('system','maximagesize');

		if(($maximagesize) && ($size > $maximagesize)) {
			return array('error' => t('Image exceeds size limit of ') . $maximagesize );

		}

        $pathinfo = pathinfo($this->file->getName());
        $filename = $pathinfo['filename'];

        $ext = $pathinfo['extension'];

        if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
            $these = implode(', ', $this->allowedExtensions);
            return array('error' => t('File has an invalid extension, it should be one of ') . $these . '.');
        }
        
        if ($this->file->save()){
            return array(
				'success'=>true,
				'path' => $this->file->getPath(), 
				'filename' => $filename . '.' . $ext
			);
        } else {
            return array(
				'error'=> t('Upload was cancelled, or server error encountered'),
				'path' => $this->file->getPath(), 
				'filename' => $filename . '.' . $ext
			);
        }
        
    }    
}
