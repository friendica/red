
<h2>$header</h2>

<div id="contact-edit-banner-name">$name</div>

$nettype

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
			<a href="contacts/$contact_id/block" class="icon block" id="contact-edit-block-link" title="$block_text"></a>
			<a href="contacts/$contact_id/ignore" class="icon no" id="contact-edit-ignore-link" title="$ignore_text"></a>
			<a href="crepair/$contact_id" class="icon tools" id="contact-edit-repair" title="$lblcrepair"></a>

		</div>
		<div id="contact-drop-links" >
			<a href="contacts/$contact_id/drop" class="icon drophide" id="contact-edit-drop-link" onclick="return confirmDelete();"  title="$delete" onmouseover="imgbright(this);" onmouseout="imgdull(this);"></a>
		</div>
		<div id="contact-edit-nav-end"></div>

		{{ if $poll_enabled }}
		<div id="contact-edit-poll-wrapper">
			<div id="contact-edit-last-update-text">$lastupdtext<span id="contact-edit-last-updated">$last_update</span></div>
			<div id="contact-edit-poll-text">$updpub</div>
			$poll_interval
			<div id="contact-edit-update-now"><a href="contacts/$contact_id/update" >$udnow</a></div>
		</div>
		{{ endif }}
	</div>
	<div id="contact-edit-end" ></div>

$insecure
$blocked
$ignored

$grps

<div id="view-recent-wrapper"><a href="network/?cid=$contact_id" id="contact-view-recent">$lblrecent</a></div>
$lblsuggest

<div id="contact-edit-info-wrapper">
<h4>$lbl_info1</h4>
<textarea id="contact-edit-info" rows="10" cols="72" name="info" >$info</textarea>
</div>
<div id="contact-edit-info-end"></div>

<input class="contact-edit-submit" type="submit" name="submit" value="$submit" />

<div id="contact-edit-profile-select-text">
<h4>$lbl_vis1</h4>
<p>$lbl_vis2
</p> 
</div>
$profile_select
<div id="contact-edit-profile-select-end"></div>

<input class="contact-edit-submit" type="submit" name="submit" value="$submit" />


<div id="contact-edit-rating-wrapper">
<h4>$lbl_rep1</h4>
<p>
$lbl_rep2 $lbl_rep3
</p>
<div id="contact-edit-rating-select-wrapper">
$rating
</div>
<div id="contact-edit-rating-explain">
<p>
$lbl_rep4
</p>
<textarea id="contact-edit-rating-text" name="reason" rows="3" cols="64" >$reason</textarea>
</div>
</div>
$groups

<input class="contact-edit-submit" type="submit" name="submit" value="$submit" />
</form>
</div>
