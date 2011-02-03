
<div id="profile-jot-wrapper" >
	<div id="profile-jot-banner-wrapper">
		<div id="profile-jot-desc" >&nbsp;</div>
		<div id="character-counter" class="grey"></div>
	</div>
	<div id="profile-jot-banner-end"></div>

	<form id="profile-jot-form" action="item" method="post" >
		<input type="hidden" name="type" value="wall" />
		<input type="hidden" name="profile_uid" value="$profile_uid" />
		<input type="hidden" name="return" value="$return_path" />
		<input type="hidden" name="location" id="jot-location" value="$defloc" />
		<input type="hidden" name="coord" id="jot-coord" value="" />

		<textarea rows="5" cols="64" class="profile-jot-text" id="profile-jot-text" name="body" ></textarea>


<div id="profile-jot-submit-wrapper" >
<input type="submit" id="profile-jot-submit" name="submit" value="Condividi" />
	<div id="profile-upload-wrapper" style="display: $visitor;" >
		<div id="wall-image-upload-div" ><img id="wall-image-upload" src="images/camera-icon.gif" alt="Carica foto" title="Carica foto"  /></div>
	</div> 
	<div id="profile-link-wrapper" style="display: $visitor;" ondragenter="linkdropper(event);" ondragover="linkdropper(event);" ondrop="linkdrop(event);" >
		<img id="profile-link" src="images/link-icon.gif" alt="Inserisci collegamento" title="Inserisci collegamento" ondragenter="return linkdropper(event);" ondragover="return linkdropper(event);" ondrop="linkdrop(event);" onclick="jotGetLink();" />
	</div> 
	<div id="profile-youtube-wrapper" style="display: $visitor;" >
		<img id="profile-video" src="images/youtube_icon.gif" alt="Inserisci video da YouTube" title="Inserisci video da YouTube" onclick="jotGetVideo();" />
	</div> 
	<div id="profile-location-wrapper" style="display: $visitor;" >
		<img id="profile-location" src="images/globe.gif" alt="Imposta la tua posizione" title="Imposta la tua posizione" onclick="jotGetLocation();" />
	</div> 
	<div id="profile-nolocation-wrapper" style="display: none;" >
		<img id="profile-nolocation" src="images/noglobe.gif" alt="Cancella la tua posizione data dal browser" title="Cancella la tua posizione data dal browser" onclick="jotClearLocation();" />
	</div> 

	<div id="profile-jot-plugin-wrapper" >
	$jotplugins
	</div>

	<div id="profile-rotator-wrapper" style="display: $visitor;" >
		<img id="profile-rotator" src="images/rotator.gif" alt="Attendi" title="Attendi" style="display: none;" />
	</div> 
	<div id="profile-jot-perms" class="profile-jot-perms" style="display: $visitor;" ><img id="jot-perms-icon" src="images/$lockstate_icon.gif"  alt="Impostazione permessi" title="Impostazione permessi" onClick="openClose('profile-jot-acl-wrapper'); openClose('profile-jot-email-wrapper');" />$bang</div>
	<div id="profile-jot-perms-end"></div>
	<div id="profile-jot-email-wrapper" style="display: none;" >
	<div id="profile-jot-email-label">$emailcc</div><input type="text" name="emailcc" id="profile-jot-email" title="$emtitle">
	<div id="profile-jot-email-end"></div>
	</div>

	<div id="profile-jot-acl-wrapper" style="display: none;" >$acl</div>
</div>



<div id="profile-jot-end"></div>
</form>
</div>
