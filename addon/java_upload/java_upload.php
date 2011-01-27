<?php




function java_upload_form(&$a,&$b) {


$b .= <<< EOT;

	<div id="photos-upload-select-files-text">$filestext</div>

	<div id="photos_upload_applet_wrapper">
		<applet name="jumpLoaderApplet"
			code="jmaster.jumploader.app.JumpLoaderApplet.class"
			archive="$archive"
			width="700"
			height="600"
			mayscript >
			<param name="uc_uploadUrl" value="$uploadurl" />
			<param name="uc_uploadFormName" value="photos-upload-form" />
			<param name="gc_loggingLeveL" value="FATAL" />
			<param name="uc_fileParameterName" value="userfile" />
			<param name="uc_cookie" value="PHPSESSID=$sessid; path=/;" />
			<param name="vc_disableLocalFileSystem" value="false" />
			<param name="vc_uploadViewMenuBarVisible" value="false" />
			<param name="vc_mainViewFileListViewVisible" value="true" />
			<param name="vc_mainViewFileListViewHeightPercent" value="50" />
			<param name="vc_mainViewFileTreeViewVisible" value="true" />
			<param name="vc_mainViewFileTreeViewWidthPercent" value="35" />
			<param name="vc_lookAndFeel" value="system" />
	
		</applet>
		
	</div>

	<div id="photos-upload-no-java-message" >
	$nojava
	</div>	

EOT;

}





function java_upload_photo_post_init(&$a,&$b) {

	if($_POST['partitionCount'])
		$a->data['java_upload'] = true;
	else
		$a->data['java_upload'] = false;


}


function java_upload_photo_post_end(&$a,&$b) {

	if(x($a->data,'java_upload') && $a->data['java_upload'])
		killme();

}