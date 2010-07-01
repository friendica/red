
<form action="process-login" method="post" >
<div class="login-name-wrapper">
	<label for="login-name" id="label-login-name">Email address: </label>
	<input type="text" maxlength="60" name="login-name" id="login-name" value="" />
</div>
<div class="login-password-wrapper">
	<label for="login-password" id="label-login-password">Password: </label>
	<input type="password" maxlength="60" name="password" id="password" value="" />
</div>
</div>
<div class="login-extra-links">
	<?php if($register) { ?>
	<a href="register" name="Register" id="register" >Register</a>
	<?php } ?>
	<a href="lost-password" name="Lost your password?" id="lost-password">Password Reset</a>
</div>
	<input type="submit" name="submit" id="login-submit" value="Login" />
</form>
