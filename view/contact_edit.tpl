
<h2>Contact Editor</h2>

<div id="contact-edit-banner-name">$name</div>


<div id="contact-edit-wrapper" >

	<div id="contact-edit-photo-wrapper" >
		<img id="contact-edit-direction-icon" src="$dir_icon" alt="$alt_text" title="$alt_text" />
		<div id="contact-edit-photo" >
			<a href="$url" title="Visit $name's profile" /><img src="$photo" alt="$name" /></a>
		</div>
		<div id="contact-edit-photo-end" ></div>
	</div>
	<div id="contact-edit-nav-wrapper" >

		<div id="contact-edit-links" >
			<a href="contacts/$contact_id/block" id="contact-edit-block-link" ><img src="images/b_block.gif" alt="Block/Unblock contact" title="$block_text"/></a>
			<a href="contacts/$contact_id/ignore" id="contact-edit-ignore-link" ><img src="images/no.gif" alt="Ignore contact" title="$ignore_text"/></a>
		</div>
		<div id="contact-drop-links" >
			<a href="contacts/$contact_id/drop" id="contact-edit-drop-link" onclick="return confirmDelete();" ><img src="images/b_drophide.gif" alt="Delete contact" title="Delete contact" onmouseover="imgbright(this);" onmouseout="imgdull(this);" /></a>
		</div>
		<div id="contact-edit-nav-end"></div>
		<div id="contact-edit-poll-wrapper">
			<div id="contact-edit-last-update-text">Last updated: <span id="contact-edit-last-updated">$last_update</span</div>
			<div id="contact-edit-poll-text">Update public posts: </div>
			$poll_interval
		</div>
	</div>
	<div id="contact-edit-end" ></div>

$blocked
$ignored

<form action="contacts/$contact_id" method="post" >
<input type="hidden" name="contact_id" value="$contact_id">

<div id="contact-edit-profile-select-text">
<h4>Profile Visibility</h4>
<p>Please choose the profile you would like to display to $name when viewing your profile securely.
</p> 
</div>
$profile_select
<div id="contact-edit-profile-select-end"></div>

<input class="contact-edit-submit" type="submit" name="submit" value="Submit" />


<div id="contact-edit-rating-wrapper">
<h4>Online Reputation</h4>
<p>
Occasionally your friends may wish to inquire about this person's online legitimacy. You may help them choose whether or not to interact with this person by providing a 'reputation' to guide them.
</p>
<div id="contact-edit-rating-select-wrapper">
$rating
</div>
<div id="contact-edit-rating-explain">
<p>
Please take a moment to elaborate on this selection if you feel it could be helpful to others.
</p>
<textarea id="contact-edit-rating-text" name="reason" rows="3" cols="64" >$reason</textarea>
</div>
</div>
$groups

<input class="contact-edit-submit" type="submit" name="submit" value="Submit" />
</form>
</div>
