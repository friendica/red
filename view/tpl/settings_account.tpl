<h1>$title</h1>


<div id="settings-remove-account-link">
<a href="removeme" title="$permanent" >$removeme</a>
</div>


<form action="settings/account" id="settings-account-form" method="post" autocomplete="off" >
<input type='hidden' name='form_security_token' value='$form_security_token'>

{{inc field_input.tpl with $field=$email }}{{endinc}}


<h3 class="settings-heading">$h_pass</h3>

{{inc field_password.tpl with $field=$password1 }}{{endinc}}
{{inc field_password.tpl with $field=$password2 }}{{endinc}}


<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="$submit" />
</div>

$account_settings



