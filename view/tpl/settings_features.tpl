<h1>$title</h1>


<form action="settings/features" method="post" autocomplete="off">
<input type='hidden' name='form_security_token' value='$form_security_token'>

{{ for $features as $f }}
	{{ inc field_yesno.tpl with $field=$f }}{{endinc}}
{{ endfor }}

<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-features-submit" value="$submit" />
</div>

</form>

