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

<div id="settings-theme-select">
<label id="settings-theme-label" for="theme-select" >Display Theme: </label>
$theme
</div>
<div id="settings-theme-end"></div>

<input type="hidden" name="visibility" value="$visibility" />

$profile_in_dir

$profile_in_net_dir

<div id="settings-default-perms" class="settings-default-perms" >
	<div id="settings-default-perms-menu" onClick="openClose('settings-default-perms-select');" />$permissions</div>
	<div id="settings-default-perms-menu-end"></div>

	<div id="settings-default-perms-select" style="display: none;" >
	
		$aclselect

	</div>
</div>
<div id="settings-default-perms-end"></div>

<div id="settings-notify-wrapper">
<div id="settings-notify-desc">Send me a notification email when: </div>
<label for="notify1" id="settings-label-notify1">I receive an introduction</label>
<input id="notify1" type="checkbox" $sel_notify1 name="notify1" value="1" />
<div id="notify1-end"></div>
<label for="notify2" id="settings-label-notify2">My introductions are confirmed</label>
<input id="notify2" type="checkbox" $sel_notify2 name="notify2" value="2" />
<div id="notify2-end"></div>
<label for="notify3" id="settings-label-notify3">Someone writes on my profile wall</label>
<input id="notify3" type="checkbox" $sel_notify3 name="notify3" value="4" />
<div id="notify3-end"></div>
<label for="notify4" id="settings-label-notify4">Someone writes a followup comment</label>
<input id="notify4" type="checkbox" $sel_notify4 name="notify4" value="8" />
<div id="notify4-end"></div>
<label for="notify5" id="settings-label-notify5">I receive a private message</label>
<input id="notify5" type="checkbox" $sel_notify5 name="notify5" value="16" />
<div id="notify5-end"></div>
</div>
<div id="settings=notify-end"></div>

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



