<h1>$title</h1>

<form enctype="multipart/form-data" action="profile_photo" method="post">
<input type='hidden' name='form_security_token' value='$form_security_token'>

<div id="profile-photo-upload-wrapper">

<label id="profile-photo-upload-label" class="form-label" for="profile-photo-upload">$lbl_upfile</label>
<input name="userfile" class="form-input" type="file" id="profile-photo-upload" size="48" />
<div class="clear"></div>

<label id="profile-photo-profiles-label" class="form-label" for="profile-photo-profiles">$lbl_profiles</label>
<select name="profile" id="profile-photo-profiles" class="form-input" >
{{ for $profiles as $p }}
<option value="$p.id" {{ if $p.pdefault }}selected="selected"{{ endif }}>$p.name</option>
{{ endfor }}
</select>
<div class="clear"></div>

<div id="profile-photo-submit-wrapper">
<input type="submit" name="submit" id="profile-photo-submit" value="$submit">
</div>
</div>

</form>

<div id="profile-photo-link-select-wrapper">
$select
</div>