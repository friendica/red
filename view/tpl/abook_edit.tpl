
<h2>$header</h2>

<h3>$addr</h3>

<div id="connection-flag-tabs">
$tabs
</div>

<div id="contact-edit-wrapper">

{{ if $slide }}
<h3>$lbl_slider</h3>

$slide

{{ endif }}

<h3>Permissions</h3>

<form action="connections/$contact_id" method="post" >
<input type="hidden" name="contact_id" value="$contact_id">
<input id="contact-closeness-mirror" type="hidden" name="closeness" value="$close" />

<br />
<b>$quick</b>
<ul>
<li><a href="#" onclick="connectFullShare(); return false;">$full</a></li>
<li><a href="#" onclick="connectCautiousShare(); return false;">$cautious</a></li>
<li><a href="#" onclick="connectFollowOnly(); return false;">$follow</a></li>
<br />

<div id="abook-advanced" class="fakelink" onclick="openClose('abook-advanced-panel');">$advanced</div>

<div id="abook-advanced-panel" style="display: none;">

<span class="abook-them">$them</span><span class="abook-me">$me</span>
<br />
<br />
{{ for $perms as $prm }}
{{inc field_acheckbox.tpl with $field=$prm }}{{endinc}}
{{ endfor }}
<br />

</div>

<input class="contact-edit-submit" type="submit" name="submit" value="$submit" />

</form>
</div>
