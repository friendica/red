
<h2>$header</h2>

<div id="contact-edit-wrapper" >
	<div id="contact-edit-banner-name">$name</div>
	<div id="contact-edit-photo" >
		<a href="$url" title="$visit" /><img src="$photo" $sparkle alt="$name" /></a>
	</div>

	<div id="contact-edit-drop-link" >
		<a href="contacts/$contact_id/drop" class="icon drophide" id="contact-edit-drop-link" onclick="return confirmDelete();"  title="$delete" onmouseover="imgbright(this);" onmouseout="imgdull(this);"></a>
	</div>

	<div id="contact-edit-drop-link-end"></div>

	<div id="contact-edit-nav-wrapper" >
		<div id="contact-edit-info-links">
			<div id="contact-edit-nettype">$nettype</div>
			<div id="contact-edit-rel">$relation_text</div>
			{{ if $insecure }}
				<div id="insecure message"><span class="icon unlock"></span> $insecure</div>
			{{ endif }}
			{{ if $blocked }}
				<div id="block-message">$blocked</div>
			{{ endif }}
			{{ if $ignored }}
				<div id="ignore-message">$ignored</div>
			{{ endif }}
			{{ if $common_text }}
				<div id="contact-edit-common"><a href="common/$contact_id">$common_text</a></div>
			{{ endif }}
			{{ if $all_friends }}
				<div id="contact-edit-allfriends"><a href="allfriends/$contact_id">$all_friends</a></div>
			{{ endif }}
 		</div>


		<div id="contact-edit-links" >
			<ul>
				<li><a href="network/?cid=$contact_id" id="contact-view-recent">$lblrecent</a></li>
				{{ if $lblsuggest }}
				<li><a href="fsuggest/$contact_id" id="contact-edit-suggest">$lblsuggest</a></li>
				{{ endif }}
				<li><a href="contacts/$contact_id/block" id="contact-edit-block-link" title="$block_text">$block_text</a></li>
				<li><a href="contacts/$contact_id/ignore" id="contact-edit-ignore-link" title="$ignore_text">$ignore_text</a></li>
				<li><a href="crepair/$contact_id" id="contact-edit-repair" title="$lblcrepair">$lblcrepair</a></li>
			</ul>
		</div>
	</div>
	<div id="contact-edit-nav-end"></div>


<form action="contacts/$contact_id" method="post" >
<input type="hidden" name="contact_id" value="$contact_id">

	{{ if $poll_enabled }}
		<div id="contact-edit-poll-wrapper">
			<div id="contact-edit-last-update-text">$lastupdtext<span id="contact-edit-last-updated">$last_update</span></div>
			<span id="contact-edit-poll-text">$updpub</span> $poll_interval <span id="contact-edit-update-now" class="button"><a href="contacts/$contact_id/update" >$udnow</a></span>
		</div>
	{{ endif }}
	<div id="contact-edit-end" ></div>



<div id="contact-edit-info-wrapper">
<h4>$lbl_info1</h4>
	<textarea id="contact-edit-info" rows=8 cols=72 name="info" >$info</textarea>
	<input class="contact-edit-submit" type="submit" name="submit" value="$submit" />
</div>
<div id="contact-edit-info-end"></div>


<div id="contact-edit-profile-select-text">
<h4>$lbl_vis1</h4>
<p>$lbl_vis2</p> 
</div>
$profile_select
<div id="contact-edit-profile-select-end"></div>

<input class="contact-edit-submit" type="submit" name="submit" value="$submit" />

</form>
</div>
