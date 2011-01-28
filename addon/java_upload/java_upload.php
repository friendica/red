<?php

/**
 *
 * Java photo uploader, uses Jumploader
 *
 * WARNING: This module currently has privacy issues.
 * The java package does not pass the permissions array intact and could lead to
 * photos being seen by people that were excluded from seeing them.
 *
 */


function java_upload_install() {
	register_hook('photo_upload_form', 'addon/java_upload/java_upload.php', 'java_upload_form');
	register_hook('photo_post_init',   'addon/java_upload/java_upload.php', 'java_upload_post_init');
	register_hook('photo_post_end',    'addon/java_upload/java_upload.php', 'java_upload_post_end');
}


function java_upload_uninstall() {
	unregister_hook('photo_upload_form', 'addon/java_upload/java_upload.php', 'java_upload_form');
	unregister_hook('photo_post_init',   'addon/java_upload/java_upload.php', 'java_upload_post_init');
	unregister_hook('photo_post_end',    'addon/java_upload/java_upload.php', 'java_upload_post_end');
}


function java_upload_form(&$a,&$b) {

	$uploadurl = $b['post_url'];
	$sessid = session_id();
	$archive = $a->get_baseurl() . '/addon/java_upload/jumploader_z.jar';
	$filestext = t('Select files to upload: ');

	$nojava = t('Use the following controls only if the Java uploader [above] fails to launch.');

	$b['default_upload'] = true;


$b['addon_text'] .= <<< EOT

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