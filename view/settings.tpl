$tabs

<h1>$ptitle</h1>

$nickname_block

<div id="uexport-link"><a href="uexport" >$uexport</a></div>


<form action="settings" id="settings-form" method="post" autocomplete="off" >


<h3 class="settings-heading">$h_pass</h3>

{{inc field_password.tpl with $field=$password1 }}{{endinc}}
{{inc field_password.tpl with $field=$password2 }}{{endinc}}

{{inc field_input.tpl with $field=$openid }}{{endinc}}


<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="$submit" />
</div>


<h3 class="settings-heading">$h_basic</h3>

{{inc field_input.tpl with $field=$username }}{{endinc}}
{{inc field_input.tpl with $field=$email }}{{endinc}}
{{inc field_custom.tpl with $field=$timezone }}{{endinc}}
{{inc field_input.tpl with $field=$defloc }}{{endinc}}
{{inc field_checkbox.tpl with $field=$allowloc }}{{endinc}}
{{inc field_select.tpl with $field=$theme }}{{endinc}}


<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="$submit" />
</div>


<h3 class="settings-heading">$h_prv</h3>


<input type="hidden" name="visibility" value="$visibility" />

{{inc field_input.tpl with $field=$maxreq }}{{endinc}}

$profile_in_dir

$profile_in_net_dir

$hide_friends

$hide_wall

<div id="settings-default-perms" class="settings-default-perms" >
	<div id="settings-default-perms-menu" class="fakelink" onClick="openClose('settings-default-perms-select');" >$permissions $permdesc</div>
	<div id="settings-default-perms-menu-end"></div>

	<div id="settings-default-perms-select" style="display: none; margin-bottom: 20px" >
	
		$aclselect

	</div>
</div>
<div id="settings-default-perms-end"></div>

{{inc field_checkbox.tpl with $field=$blockwall }}{{endinc}}

{{inc field_input.tpl with $field=$expire }}{{endinc}}



<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="Submit" />
</div>



<h3 class="settings-heading">$h_not</h3>

<div id="settings-notify-desc">$lbl_not </div>

<div class="group">
{{inc field_intcheckbox.tpl with $field=$notify1 }}{{endinc}}
{{inc field_intcheckbox.tpl with $field=$notify2 }}{{endinc}}
{{inc field_intcheckbox.tpl with $field=$notify3 }}{{endinc}}
{{inc field_intcheckbox.tpl with $field=$notify4 }}{{endinc}}
{{inc field_intcheckbox.tpl with $field=$notify5 }}{{endinc}}
</div>


<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="$submit" />
</div>

{{ if $mail_disabled }}

{{ else }}
	<h3 class="settings-heading">$h_imap</h3>
	<p>$imap_desc</p>
	{{inc field_custom.tpl with $field=$imap_lastcheck }}{{endinc}}
	{{inc field_input.tpl with $field=$mail_server }}{{endinc}}
	{{inc field_input.tpl with $field=$mail_port }}{{endinc}}
	{{inc field_select.tpl with $field=$mail_ssl }}{{endinc}}
	{{inc field_input.tpl with $field=$mail_user }}{{endinc}}
	{{inc field_password.tpl with $field=$mail_pass }}{{endinc}}
	{{inc field_input.tpl with $field=$mail_replyto }}{{endinc}}
	{{inc field_checkbox.tpl with $field=$mail_pubmail }}{{endinc}}

	<div class="settings-submit-wrapper" >
		<input type="submit" name="submit" class="settings-submit" value="$submit" />
	</div>
{{ endif }}




<h3 class="settings-heading">$h_advn</h3>

$pagetype

<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="$submit" />
</div>


