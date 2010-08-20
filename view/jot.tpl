
<div id="profile-jot-wrapper" >
<div id="profile-jot-banner-wrapper">
	<div id="profile-jot-desc" >What's on your mind?</div>
	<div id="character-counter" class="grey"></div>
</div>
<div id="profile-jot-banner-end"></div>
<form id="profile-jot-form" action="item" method="post" >
<input type="hidden" name="type" value="wall" />
<input type="hidden" name="profile_uid" value="$profile_uid" />
<input type="hidden" name="return" value="$return_path" />

<textarea rows="5" cols="64" class="profile-jot-text" id="profile-jot-text" name="body" ></textarea>

</div>
<div id="profile-jot-submit-wrapper" >
<input type="submit" id="profile-jot-submit" name="submit" value="Submit" />
	<div id="profile-upload-wrapper" style="display: $visitor;" >
		<div id="wall-image-upload-div" ><img id="wall-image-upload" src="images/camera-icon.gif" alt="Upload Photo" title="Upload Photo" /></div>
	</div> 
	<div id="profile-link-wrapper" style="display: $visitor;" ondragenter="linkdropper(event);" ondragover="linkdropper(event);" ondrop="linkdrop(event);" >
		<img id="profile-link" src="images/link-icon.gif" alt="Insert web link" title="Insert web link" ondragenter="return linkdropper(event);" ondragover="return linkdropper(event);" ondrop="linkdrop(event);" onclick="jotGetLink();" />
	</div> 
	<div id="profile-youtube-wrapper" style="display: $visitor;" >
		<img id="profile-video" src="images/youtube_icon.gif" alt="Insert YouTube video" title="Insert YouTube video" onclick="jotGetVideo();" />
	</div> 
	<div id="profile-rotator-wrapper" style="display: $visitor;" >
		<img id="profile-rotator" src="images/rotator.gif" alt="Please wait" title="Please wait" style="display: none;" />
	</div> 
	<div id="profile-jot-perms" class="profile-jot-perms" style="display: $visitor;" ><img src="images/$lockstate_icon.gif" alt="Permission Settings" title="Permission Settings" onClick="openClose('profile-jot-acl-wrapper');" /></div>
	<div id="profile-jot-perms-end"></div>
	<div id="profile-jot-acl-wrapper" style="display: none;" >$acl</div>
</div>
<div id="profile-jot-end"></div>
</form>
</div>
