<h3>Registration</h3>

<form action="register" method="post" >
	<div class="error-message">$registertext</div>
	<div id="register-name-wrapper" >
		<label for="register-name" id="label-register-name" >Your Full Name (e.g. Joe Smith): </label>
		<input type="text" maxlength="60" size="32" name="username" id="register-name" value="" >
	</div>
	<div id="register-name-end" ></div>


	<div id="register-email-wrapper" >
		<label for="register-email" id="label-register-email" >Your Email Address: </label>
		<input type="text" maxlength="60" size="32" name="email" id="register-email" value="" >
	</div>
	<div id="register-email-end" ></div>

	<p id="register-nickname-desc" >
	You will use a unique nickname to identify yourself in our social network. This must begin with a text character.
	Your profile identifier will then be '<strong>nickname@$sitename</strong>'.
	</p>
	<div id="register-nickname-wrapper" >
		<label for="register-nickname" id="label-register-nickname" >Choose a nickname: </label>
		<input type="text" maxlength="60" size="32" name="nickname" id="register-nickname" value="" ><div id="register-sitename">@$sitename</div>
	</div>
	<div id="register-nickname-end" ></div>



	<div id="register-submit-wrapper">
		<input type="submit" name="submit" id="register-submit-button" value="Register" />
	</div>
	<div id="register-submit-end" ></div>
</form>
