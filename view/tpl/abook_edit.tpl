
<h2>$header</h2>


<div id="connection-flag-tabs">
$tabs
</div>

<div id="contact-edit-wrapper">
<br />
<h3>Slide to adjust your degree of friendship</h3>

$slide



<h3>Permissions</h3>

<form action="abook/$contact_id" method="post" >
<input type="hidden" name="contact_id" value="$contact_id">
<input id="contact-closeness-mirror" type="hidden" name="closeness" value="$close" />

<br />
<b>Quick Links:</b>
<a href="" style="background-color: #CCC; padding: 3px; border-radius: 5px; margin-left: 15px;">Full Sharing</a><a href="" style="background-color: #CCC; padding: 3px; border-radius: 5px; margin-left: 15px;">Cautious Sharing</a><a href="" style="background-color: #CCC; padding: 3px; border-radius: 5px; margin-left: 15px;">Follow Only</a><br />
<br />

<div id="abook-advanced" class="fakelink" onclick="openClose('abook-advanced-panel');">Advanced Permissions</div>

<div id="abook-advanced-panel" style="display: none;">

<span class="abook-them">$them</span><span class="abook-me">$me</span>
<br />
<br />
{{inc field_acheckbox.tpl with $field=$perm01 }}{{endinc}}
{{inc field_acheckbox.tpl with $field=$perm02 }}{{endinc}}
{{inc field_acheckbox.tpl with $field=$perm03 }}{{endinc}}
{{inc field_acheckbox.tpl with $field=$perm04 }}{{endinc}}
{{inc field_acheckbox.tpl with $field=$perm05 }}{{endinc}}
{{inc field_acheckbox.tpl with $field=$perm06 }}{{endinc}}
{{inc field_acheckbox.tpl with $field=$perm07 }}{{endinc}}
{{inc field_acheckbox.tpl with $field=$perm08 }}{{endinc}}
{{inc field_acheckbox.tpl with $field=$perm09 }}{{endinc}}
{{inc field_acheckbox.tpl with $field=$perm10 }}{{endinc}}

<br />

</div>

<input class="contact-edit-submit" type="submit" name="submit" value="$submit" />

</form>
</div>
