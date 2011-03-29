<h1>Account Einstellungen</h1>

<div id="plugin-settings-link"><a href="settings/addon">Plugin Einstellungen</a></div>

$uexport

$nickname_block


<form action="settings" id="settings-form" method="post" autocomplete="off" >


<h3 class="settings-heading">Grundeinstellungen</h3>

<div id="settings-username-wrapper" >
<label id="settings-username-label" for="settings-username" >Kompletter Name: </label>
<input type="text" name="username" id="settings-username" value="$username" />
</div>
<div id="settings-username-end" ></div>

<div id="settings-email-wrapper" >
<label id="settings-email-label" for="settings-email" >EMail Adresse: </label>
<input type="text" name="email" id="settings-email" value="$email" />
</div>
<div id="settings-email-end" ></div>



<div id="settings-timezone-wrapper" >
<label id="settings-timezone-label" for="timezone_select" >Deine Zeitzone: </label>
$zoneselect
</div>
<div id="settings-timezone-end" ></div>

<div id="settings-defloc-wrapper" >
<label id="settings-defloc-label" for="settings-defloc" >Standardwert Sendestandort: </label>
<input type="text" name="defloc" id="settings-defloc" value="$defloc" />
</div>
<div id="settings-defloc-end" ></div>

<div id="settings-allowloc-wrapper" >
<label id="settings-allowloc-label" for="settings-allowloc" >Browser Standort verwenden: </label>
<input type="checkbox" name="allow_location" id="settings-allowloc" value="1" $loc_checked />
</div>
<div id="settings-allowloc-end" ></div>




<div id="settings-theme-select">
<label id="settings-theme-label" for="theme-select" >Anzeige Thema: </label>
$theme
</div>
<div id="settings-theme-end"></div>

<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="Submit" />
</div>


<h3 class="settings-heading">Sicherheits und Privatsphären Einstellungen</h3>


<input type="hidden" name="visibility" value="$visibility" />

<div id="settings-maxreq-wrapper">
<label id="settings-maxreq-label" for="settings-maxreq" >Maximale Anzahl an Freundschaftsanfragen pro Tagy</label>
<input id="settings-maxreq" name="maxreq" value="$maxreq" />
<div id="settings-maxreq-desc">(um SPAM zu verhindern)</div>
</div>
<div id="settings-maxreq-end"></div>




$profile_in_dir

$profile_in_net_dir



<div id="settings-default-perms" class="settings-default-perms" >
	<div id="settings-default-perms-menu" class="fakelink" onClick="openClose('settings-default-perms-select');" >$permissions</div>
	<div id="settings-default-perms-menu-end"></div>

	<div id="settings-default-perms-select" style="display: none;" >
	
		$aclselect

	</div>
</div>
<div id="settings-default-perms-end"></div>

<div id="settings-blockw-wrapper" >
<label id="settings-blockw-label" for="settings-blockw" >Allow friends to post to your profile page: </label>
<input type="checkbox" name="blockwall" id="settings-blockw" value="1" $blockw_checked />
</div>
<div id="settings-blockw-end" ></div>



<div id="settings-expire-desc">Automatically expire (delete) posts older than <input type="text" size="3" name="expire" value="$expire" /> days</div>
<div id="settings-expire-end"></div>


<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="Submit" />
</div>



<h3 class="settings-heading">Benachrichtigungs Einstellungen</h3>


<div id="settings-notify-wrapper">
<div id="settings-notify-desc">Benachrichtigungsemail senden wenn: </div>
<label for="notify1" id="settings-label-notify1">Du eine Vorstellung empfängst</label>
<input id="notify1" type="checkbox" $sel_notify1 name="notify1" value="1" />
<div id="notify1-end"></div>
<label for="notify2" id="settings-label-notify2">Deine Vorstellung bestätigt wurde</label>
<input id="notify2" type="checkbox" $sel_notify2 name="notify2" value="2" />
<div id="notify2-end"></div>
<label for="notify3" id="settings-label-notify3">Jemand etwas auf deiner Profilwand postet</label>
<input id="notify3" type="checkbox" $sel_notify3 name="notify3" value="4" />
<div id="notify3-end"></div>
<label for="notify4" id="settings-label-notify4">Jemand einen Folgebeitrag schreibt</label>
<input id="notify4" type="checkbox" $sel_notify4 name="notify4" value="8" />
<div id="notify4-end"></div>
<label for="notify5" id="settings-label-notify5">Du eine private Nachricht erhälst</label>
<input id="notify5" type="checkbox" $sel_notify5 name="notify5" value="16" />
<div id="notify5-end"></div>
</div>
<div id="settings=notify-end"></div>

<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="Submit" />
</div>


<h3 class="settings-heading">Passwort Einstellungen</h3>


<div id="settings-password-wrapper" >
<p id="settings-password-desc" >
Lass die Passwortfelder frei außer du willst das Passwort ändern.
</p>
<label id="settings-password-label" for="settings-password" >Neues Passwort: </label>
<input type="password" id="settings-password" name="npassword" />
</div>
<div id="settings-password-end" ></div>

<div id="settings-confirm-wrapper" >
<label id="settings-confirm-label" for="settings-confirm" >Bestätigen: </label>
<input type="password" id="settings-confirm" name="confirm" />
</div>
<div id="settings-confirm-end" ></div>

<div id="settings-openid-wrapper" >
	$oidhtml
</div>
<div id="settings-openid-end" ></div>


<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="Submit" />
</div>


<h3 class="settings-heading">Erweiterte Seiteneinstellungen</h3>

$pagetype

<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="Submit" />
</div>


