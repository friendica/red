<h3>$pagename</h3>
<form action="photos" enctype="multipart/form-data" method="post" name="photos-upload-form" id="photos-upload-form" >
	<div id="photos-upload-new-wrapper" >
		<div id="photos-upload-newalbum-div">
			<label id="photos-upload-newalbum-text" for="photos-upload-newalbum" >$newalbum</label>
		</div>
		<input id="photos-upload-newalbum" type="text" name="newalbum" />
	</div>
	<div id="photos-upload-new-end"></div>
	<div id="photos-upload-exist-wrapper">
		<div id="photos-upload-existing-album-text">$existalbumtext</div>
		$albumselect
	</div>
	<div id="photos-upload-exist-end"></div>


	<div id="photos-upload-perms" class="photos-upload-perms" ><div id="photos-upload-perms-menu" onClick="openClose('photos-upload-permissions-wrapper');" />$permissions</div>
	<div id="photos-upload-perms-end"></div>

	<div id="photos-upload-permissions-wrapper" style="display: none;" >
	
		$aclselect

	</div>

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

	<input type="file" name="userfile" />

	<div class="photos-upload-submit-wrapper" >
		<input type="submit" name="submit" value="$submit" id="photos-upload-submit" />
	</div>
	<div class="photos-upload-end" ></div>
</form>

