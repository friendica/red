<h2>$title</h2>

<form action="zregister" method="post" id="zregister-form">

{{ if $registertext }}
<div id="zregister-desc" class="descriptive-paragraph">$registertext</div>
{{ endif }}

{{ if $invitations }}
	<p id="register-invite-desc">$invite_desc</p>

	<label for="zregister-invite" id="label-zregister-invite" class="zregister-label">$label_invite</label>
	<input type="text" maxlength="72" size="32" name="invite_id" id="zregister-invite" class="zregister-input" value="$invite_id" />
	</div>
	<div id="zregister-invite-feedback" class="zregister-feedback"></div>
	<div id="zregister-invite-end" class="zregister-field-end"></div>

{{ endif }}


	<label for="zregister-email" id="label-zregister-email" class="zregister-label" >$label_email</label>
	<input type="text" maxlength="72" size="32" name="email" id="zregister-email" class="zregister-input" value="$email" />
	<div id="zregister-email-feedback" class="zregister-feedback"></div>
	<div id="zregister-email-end"  class="zregister-field-end"></div>

	<label for="zregister-password" id="label-zregister-password" class="zregister-label" >$label_pass1</label>
	<input type="password" maxlength="72" size="32" name="password" id="zregister-password" class="zregister-input" value="$pass1" />
	<div id="zregister-password-feedback" class="zregister-feedback"></div>
	<div id="zregister-password-end"  class="zregister-field-end"></div>

	<label for="zregister-password2" id="label-zregister-password2" class="zregister-label" >$label_pass2</label>
	<input type="password" maxlength="72" size="32" name="password2" id="zregister-password2" class="zregister-input" value="$pass2" />
	<div id="zregister-password2-feedback" class="zregister-feedback"></div>
	<div id="zregister-password2-end"  class="zregister-field-end"></div>

	{{ if $enable_tos }}
	<input type="checkbox" name="tos" id="zregister-tos" value="1" />
	<label for="zregister-tos" id="label-zregister-tos">$label_tos</label>
	<div id="zregister-tos-feedback" class="zregister-feedback"></div>
	<div id="zregister-tos-end"  class="zregister-field-end"></div>
	{{ else }}
	<input type="hidden" name="tos" value="1" />
	{{ endif }}

	<input type="submit" name="submit" id="zregister-submit-button" value="$submit" />
	<div id="zregister-submit-end" class="zregister-field-end"></div>

</form>
