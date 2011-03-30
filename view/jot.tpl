
<div id="profile-jot-wrapper" >
	<div id="profile-jot-banner-wrapper">
		<div id="profile-jot-desc" >&nbsp;</div>
		<div id="character-counter" class="grey"></div>
	</div>
	<div id="profile-jot-banner-end"></div>

	<form id="profile-jot-form" action="$action" method="post" >
		<input type="hidden" name="type" value="wall" />
		<input type="hidden" name="profile_uid" value="$profile_uid" />
		<input type="hidden" name="return" value="$return_path" />
		<input type="hidden" name="location" id="jot-location" value="$defloc" />
		<input type="hidden" name="coord" id="jot-coord" value="" />
		<input type="hidden" name="title" id="jot-title" value="" />
		<input type="hidden" name="post_id" value="$post_id" />

		<textarea rows="5" cols="64" class="profile-jot-text" id="profile-jot-text" name="body" >$content</textarea>


<div id="profile-jot-submit-wrapper" >
<input type="submit" id="profile-jot-submit" name="submit" value="$share" />
	<div id="profile-upload-wrapper" style="display: $visitor;" >
		<div id="wall-image-upload-div" ><img id="wall-image-upload" src="images/camera-icon.gif" alt="$upload" title="$upload" /></div>
	</div> 
	<div id="profile-link-wrapper" style="display: $visitor;" ondragenter="linkdropper(event);" ondragover="linkdropper(event);" ondrop="linkdrop(event);" >
		<img id="profile-link" src="images/link-icon.gif" alt="$weblink" title="$weblink" ondragenter="return linkdropper(event);" ondragover="return linkdropper(event);" ondrop="linkdrop(event);" onclick="jotGetLink();" />
	</div> 
	<div id="profile-youtube-wrapper" style="display: $visitor;" >
		<img id="profile-youtube" src="images/youtube_icon.gif" alt="$youtube" title="$youtube" onclick="jotGetVideo();" />
	</div> 
	<div id="profile-video-wrapper" style="display: $visitor;" >
		<img id="profile-video" src="images/video.gif" alt="$video" title="$video" onclick="jotVideoURL();" />
	</div> 
	<div id="profile-audio-wrapper" style="display: $visitor;" >
		<img id="profile-audio" src="images/audio.gif" alt="$audio" title="$audio" onclick="jotAudioURL();" />
	</div> 
	<div id="profile-location-wrapper" style="display: $visitor;" >
		<img id="profile-location" src="images/globe.gif" alt="$setloc" title="$setloc" onclick="jotGetLocation();" />
	</div> 
	<div id="profile-nolocation-wrapper" style="display: none;" >
		<img id="profile-nolocation" src="images/noglobe.gif" alt="$noloc" title="$noloc" onclick="jotClearLocation();" />
	</div> 
	<div id="profile-title-wrapper" style="display: $visitor;" >
		<img id="profile-title" src="images/article.gif" alt="$title" title="$title" onclick="jotTitle();" />
	</div> 

	<div id="profile-jot-plugin-wrapper">
  	$jotplugins
	</div>

	<div id="profile-rotator-wrapper" style="display: $visitor;" >
		<img id="profile-rotator" src="images/rotator.gif" alt="$wait" title="$wait" style="display: none;" />
	</div> 
	<div id="profile-jot-perms" class="profile-jot-perms" style="display: $visitor;" ><img id="jot-perms-icon" src="images/$lockstate_icon.gif"  alt="$permset" title="$permset" onClick="openClose('profile-jot-acl-wrapper'); openClose('profile-jot-email-wrapper'); openClose('profile-jot-networks');" />$bang</div>
	<div id="profile-jot-perms-end"></div>
	<div id="profile-jot-email-wrapper" style="display: none;" >
	<div id="profile-jot-email-label">$emailcc</div><input type="text" name="emailcc" id="profile-jot-email" title="$emtitle">
	<div id="profile-jot-email-end"></div>
	</div>
	<div id="profile-jot-networks" style="display: none;" >
	$jotnets
	</div>
	<div id="profile-jot-networks-end"></div>
	<div id="profile-jot-acl-wrapper" style="display: none;" >$acl</div>
</div>

<div id="profile-jot-end"></div>
</form>
</div>
