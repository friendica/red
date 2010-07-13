
<div id="profile-jot-wrapper" >
<p id="profile-jot-desc" >
What's on your mind?
</p>
<form id="profile-jot-form" action="item" method="post" >
<input type="hidden" name="type" value="jot" />
<input type="hidden" name="profile_uid" value="$profile_uid" />

<textarea rows="5" cols="64" class="profile-jot-text" id="profile-jot-text" name="body" ></textarea>

</div>
<div id="profile-jot-submit-wrapper" >
<input type="submit" id="profile-jot-submit" name="submit" value="Submit" />
	<div id="profile-jot-perms" class="profile-jot-perms" style="display: $visitor;" ><img src="images/$lockstate_icon.gif" alt="Permission Settings" title="Permission Settings" onClick="openClose('profile-jot-acl-wrapper');" /></div>
	<div id="profile-jot-perms-end"></div>
	<div id="profile-jot-acl-wrapper" style="display: none;" >$acl</div>
</div>
<div id="profile-jot-end"></div>
</form>
</div>
