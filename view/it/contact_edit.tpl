
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
<h4>Informazioni di contatto / Note</h4>
<textarea id="contact-edit-info" rows="10" cols="72" name="info" >$info</textarea>
</div>
<div id="contact-edit-info-end"></div>

<input class="contact-edit-submit" type="submit" name="submit" value="Aggiorna" />


<div id="contact-edit-profile-select-text">
<h4>Visibilt&agrave; Profilo</h4>
<p>Scegli il profilo che vuoi mostrare a $name quando guarda il tuo profilo in modo sicuro.</p> 
</div>
$profile_select
<div id="contact-edit-profile-select-end"></div>

<input class="contact-edit-submit" type="submit" name="submit" value="Aggiorna" />


<div id="contact-edit-rating-wrapper">
<h4>Reputazione Online</h4>
<p>Puo' capitare che i tuoi amici vogliano sapere la legittimit&agrave; online dei questa persona. Puoi aiutarli a scegliere se interagire o no con questa persona fornendo una 'reputazione' per guidarli.</p>
<div id="contact-edit-rating-select-wrapper">
$rating
</div>
<div id="contact-edit-rating-explain">
<p>
Prenditi un momento per pensare su questa selezione se senti che puo' essere utile ad altri.
</p>
<textarea id="contact-edit-rating-text" name="reason" rows="3" cols="64" >$reason</textarea>
</div>
</div>
$groups

<input class="contact-edit-submit" type="submit" name="submit" value="Aggiorna" />
</form>
</div>
