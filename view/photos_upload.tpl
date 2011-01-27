<h3>$pagename</h3>
<form action="photos/$nickname" enctype="multipart/form-data" method="post" name="photos-upload-form" id="photos-upload-form" >
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

	<div id="photos_upload_spacer"></div>

	$uploader

	$default

	<div class="photos-upload-end" ></div>
</form>

