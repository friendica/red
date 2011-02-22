
<h2>$header</h2>

<div id="contact-edit-banner-name">$name</div>

<form action="contacts/$contact_id" method="post" >
<input type="hidden" name="contact_id" value="$contact_id">

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



		<div id="contact-edit-poll-wrapper">
			<div id="contact-edit-last-update-text">$lastupdtext<span id="contact-edit-last-updated">$last_update</span</div>
			<div id="contact-edit-poll-text">$updpub</div>
			$poll_interval
			<div id="contact-edit-update-now"><a href="contacts/$contact_id/update">$udnow</a></div>
		</div>
	</div>
	<div id="contact-edit-end" ></div>

$insecure
$blocked
$ignored


<div id="contact-edit-info-wrapper">
<h4>Informations / Notes du contact</h4>
<textarea id="contact-edit-info" rows="10" cols="72" name="info" >$info</textarea>
</div>
<div id="contact-edit-info-end"></div>

<input class="contact-edit-submit" type="submit" name="submit" value="Submit" />

<div id="contact-edit-profile-select-text">
<h4>Visibilité du profil</h4>
<p>Merci de choisir le profil que vous souhaitez afficher à $name lorsqu'il consulte votre page de manière sécurisée.
</p> 
</div>
$profile_select
<div id="contact-edit-profile-select-end"></div>

<input class="contact-edit-submit" type="submit" name="submit" value="Submit" />


<div id="contact-edit-rating-wrapper">
<h4>Réputation</h4>
<p>
De temps à autre, vos amis peuvent vouloir en savoir plus sur la légitimité de cette personne "en ligne". Vous pouvez les aider à décider s'ils veulent ou non interagir avec cette personne en indiquant une "réputation".
</p>
<div id="contact-edit-rating-select-wrapper">
$rating
</div>
<div id="contact-edit-rating-explain">
<p>
Merci de prendre un moment pour développer si vous pensez que cela peut être utile à d'autres.
</p>
<textarea id="contact-edit-rating-text" name="reason" rows="3" cols="64" >$reason</textarea>
</div>
</div>
$groups

<input class="contact-edit-submit" type="submit" name="submit" value="Sauver" />
</form>
</div>
