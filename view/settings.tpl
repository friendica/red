<h1>Account Settings</h1>

$nickname_block


<form action="settings" id="settings-form" method="post" >

<div id="settings-username-wrapper" >
<label id="settings-username-label" for="settings-username" >Username: </label>
<input type="text" name="username" id="settings-username" value="$username" />
</div>
<div id="settings-username-end" ></div>

<div id="settings-email-wrapper" >
<label id="settings-email-label" for="settings-email" >Email Address: </label>
<input type="text" name="email" id="settings-email" value="$email" />
</div>
<div id="settings-email-end" ></div>



<div id="settings-timezone-wrapper" >
<label id="settings-timezone-label" for="timezone_select" >Your Timezone: </label>
$zoneselect
</div>
<div id="settings-timezone-end" ></div>

<div id="settings-default-perms" class="settings-default-perms" >
	<div id="settings-default-perms-menu" onClick="openClose('settings-default-perms-select');" />$permissions</div>
	<div id="settings-default-perms-menu-end"></div>

	<div id="settings-default-perms-select" style="display: none;" >
	
		$aclselect

	</div>
</div>
<div id="settings-default-perms-end"></div>



<div id="settings-password-wrapper" >
<p id="settings-password-desc" >
Leave password fields blank unless changing
</p>
<label id="settings-password-label" for="settings-password" >New Password: </label>
<input type="password" id="settings-password" name="password" ></input>
</div>
<div id="settings-password-end" ></div>

<div id="settings-confirm-wrapper" >
<label id="settings-confirm-label" for="settings-confirm" >Confirm: </label>
<input type="password" id="settings-confirm" name="confirm" ></input>
</div>
<div id="settings-confirm-end" ></div>






<div id="settings-submit-wrapper" >
<input type="submit" name="submit" id="settings-submit" value="Submit" />
</div>

</form>



