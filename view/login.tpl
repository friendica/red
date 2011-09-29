
<form action="" method="post" >
<input type="hidden" name="auth-params" value="login" />
<div id="login-name-wrapper">
        <label for="login-name" id="label-login-name">$namelabel</label>
        <input type="text" maxlength="60" name="openid_url" class="$classname" id="login-name" value="" />
</div>
<div id="login-name-end" ></div>
<div id="login-password-wrapper">
        <label for="login-password" id="label-login-password">$passlabel</label>
        <input type="password" maxlength="60" name="password" id="login-password" value="" />
</div>
<div id="login-password-end"></div>
<div id="login-extra-links" class=".button">
	<div id="login-extra-filler">&nbsp;</div>
	$register_html
        <a href="lostpass" title="$lostpass" id="lost-password-link" >$lostlink</a>
</div>
<div id="login-extra-end"></div>
<div id="login-submit-wrapper" >
        <input type="submit" name="submit" id="login-submit-button" value="$login" />
</div>
<div id="login-submit-end"></div>
</form>

