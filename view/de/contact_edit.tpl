
<h2>$header</h2>

<div id="contact-edit-banner-name">$name</div>


<div id="contact-edit-wrapper" >

	<div id="contact-edit-photo-wrapper" >
		<img id="contact-edit-direction-icon" src="$dir_icon" alt="$alt_text" title="$alt_text" />
		<div id="contact-edit-photo" >
			<a href="$url" title="$visit" /><img src="$photo" $sparkle alt="$name" /></a>
		</div>
		<div id="contact-edit-photo-end" ></div>
	</div>
	<div id="contact-edit-nav-wrapper" >

		<div id="contact-edit-links" >
			<a href="contacts/$contact_id/block" id="contact-edit-block-link" ><img src="images/b_block.gif" alt="$blockunblock" title="$block_text"/></a>
			<a href="contacts/$contact_id/ignore" id="contact-edit-ignore-link" ><img src="images/no.gif" alt="$ignorecont" title="$ignore_text"/></a>
		</div>
		<div id="contact-drop-links" >
			<a href="contacts/$contact_id/drop" id="contact-edit-drop-link" onclick="return confirmDelete();" ><img src="images/b_drophide.gif" alt="$delete" title="$delete" onmouseover="imgbright(this);" onmouseout="imgdull(this);" /></a>
		</div>
		<div id="contact-edit-nav-end"></div>

<form action="contacts/$contact_id" method="post" >
<input type="hidden" name="contact_id" value="$contact_id">

		<div id="contact-edit-poll-wrapper">
			<div id="contact-edit-last-update-text">$lastupdtext<span id="contact-edit-last-updated">$last_update</span</div>
			<div id="contact-edit-poll-text">$updpub</div>
			$poll_interval
		</div>
	</div>
	<div id="contact-edit-end" ></div>

$insecure
$blocked
$ignored


<div id="contact-edit-info-wrapper">
<h4>Kontaktinformation / Notizen</h4>
<textarea id="contact-edit-info" rows="10" cols="72" name="info" >$info</textarea>
</div>
<div id="contact-edit-info-end"></div>

<input class="contact-edit-submit" type="submit" name="submit" value="Submit" />

<div id="contact-edit-profile-select-text">
<h4>Profil Sichtbarkeit</h4>
<p>Bitte wähle das Profil, das du $name gezeigt werden soll, wenn er sich dein
Profil in Friendika betrachtet.
</p> 
</div>
$profile_select
<div id="contact-edit-profile-select-end"></div>

<input class="contact-edit-submit" type="submit" name="submit" value="Submit" />


<div id="contact-edit-rating-wrapper">
<h4>Online Reputation</h4>
<p>
Gelegentlich werden sich deine Freunde nach der online Legitimität dieser
Person erkundigen. Du kannst ihnen helfen bei der Entscheidung ob sie mit
dieser Person interagieren wollen indem du den "Ruf" der Person bewertest.
</p>
<div id="contact-edit-rating-select-wrapper">
$rating
</div>
<div id="contact-edit-rating-explain">
<p>
Bitte nimm dir einen Moment um deine Auswahl zu kommentieren wenn du meinst das
könnte anderen weiter helfen.
</p>
<textarea id="contact-edit-rating-text" name="reason" rows="3" cols="64" >$reason</textarea>
</div>
</div>
$groups

<input class="contact-edit-submit" type="submit" name="submit" value="Submit" />
</form>
</div>
