
<h2>$header</h2>

<h3>$addr</h3>

{{ if $notself }}
<div id="connection-flag-tabs">
$tabs
</div>
{{ endif }}

{{ if $self }}
<div id="autoperm-desc" class="descriptive-paragraph">$autolbl</div>
{{ endif }}


<div id="contact-edit-wrapper">

{{ if $notself }}
{{ if $slide }}
<h3>$lbl_slider</h3>

$slide

{{ endif }}
{{ endif }}



<h3>$permlbl</h3>

<form id="abook-edit-form" action="connections/$contact_id" method="post" >
<input type="hidden" name="contact_id" value="$contact_id">
<input id="contact-closeness-mirror" type="hidden" name="closeness" value="$close" />

{{ if $noperms }}
<div id="noperm-desc" class="descriptive-paragraph">$noperms</div>
{{ endif }}


{{ if $is_pending }}
{{inc field_checkbox.tpl with $field=$unapproved }}{{endinc}}
{{ endif }}

<br />
<b>$quick</b>
<ul>
{{ if $self }}
<li><a href="#" onclick="connectForum(); return false;">$forum</a></li>
{{ endif }}
<li><a href="#" onclick="connectFullShare(); return false;">$full</a></li>
<li><a href="#" onclick="connectCautiousShare(); return false;">$cautious</a></li>
<li><a href="#" onclick="connectFollowOnly(); return false;">$follow</a></li>
</ul>

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
