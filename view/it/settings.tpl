<h1>Impostazioni account</h1>

<div id="plugin-settings-link"><a href="settings/addon">Impostazioni Plugin</a></div>


$nickname_block


<form action="settings" id="settings-form" method="post" autocomplete="off" >


<h3 class="settings-heading">Impostazioni base</h3>

<div id="settings-username-wrapper" >
<label id="settings-username-label" for="settings-username" >Nome Completo: </label>
<input type="text" name="username" id="settings-username" value="$username" />
</div>
<div id="settings-username-end" ></div>

<div id="settings-email-wrapper" >
<label id="settings-email-label" for="settings-email" >Indirizzo Email: </label>
<input type="text" name="email" id="settings-email" value="$email" />
</div>
<div id="settings-email-end" ></div>



<div id="settings-timezone-wrapper" >
<label id="settings-timezone-label" for="timezone_select" >Il tuo fuso orario: </label>
$zoneselect
</div>
<div id="settings-timezone-end" ></div>

<div id="settings-defloc-wrapper" >
<label id="settings-defloc-label" for="settings-defloc" >Localit&agrave; di default per l'invio: </label>
<input type="text" name="defloc" id="settings-defloc" value="$defloc" />
</div>
<div id="settings-defloc-end" ></div>

<div id="settings-allowloc-wrapper" >
<label id="settings-allowloc-label" for="settings-allowloc" >Usa la localit&agrave; rilevata dal browser: </label>
<input type="checkbox" name="allow_location" id="settings-allowloc" value="1" $loc_checked />
</div>
<div id="settings-allowloc-end" ></div>




<div id="settings-theme-select">
<label id="settings-theme-label" for="theme-select" >Tema: </label>
$theme
</div>
<div id="settings-theme-end"></div>

<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="Aggiorna" />
</div>


<h3 class="settings-heading">Impostazioni si Sicurezza e Privacy</h3>


<input type="hidden" name="visibility" value="$visibility" />

<div id="settings-maxreq-wrapper">
<label id="settings-maxreq-label" for="settings-maxreq" >Numero massimo di richieste di amicizia per giorno</label>
<input id="settings-maxreq" name="maxreq" value="$maxreq" />
<div id="settings-maxreq-desc">(per prevenire lo spam)</div>
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

<div id="settings-expire-desc">Automatically expire (delete) posts older than <input type="text" size="3" name="expire" value="$expire" /> days</div>
<div id="settings-expire-end"></div>

<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="Aggiorna" />
</div>



<h3 class="settings-heading">Impostazione Notifiche</h3>


<div id="settings-notify-wrapper">
<div id="settings-notify-desc">Invia una mail di notifica quando: </div>
<label for="notify1" id="settings-label-notify1">Ricevi una presentazione</label>
<input id="notify1" type="checkbox" $sel_notify1 name="notify1" value="1" />
<div id="notify1-end"></div>
<label for="notify2" id="settings-label-notify2">Le tue presentazioni sono confermate</label>
<input id="notify2" type="checkbox" $sel_notify2 name="notify2" value="2" />
<div id="notify2-end"></div>
<label for="notify3" id="settings-label-notify3">Qualcuno scrive sulla bacheca del tuo profilo</label>
<input id="notify3" type="checkbox" $sel_notify3 name="notify3" value="4" />
<div id="notify3-end"></div>
<label for="notify4" id="settings-label-notify4">Qualcuno scrive un commento a un tuo messaggio</label>
<input id="notify4" type="checkbox" $sel_notify4 name="notify4" value="8" />
<div id="notify4-end"></div>
<label for="notify5" id="settings-label-notify5">Ricevi un messaggio privato</label>
<input id="notify5" type="checkbox" $sel_notify5 name="notify5" value="16" />
<div id="notify5-end"></div>
</div>
<div id="settings=notify-end"></div>

<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="Aggiorna" />
</div>


<h3 class="settings-heading">Impostazioni Password</h3>


<div id="settings-password-wrapper" >
<p id="settings-password-desc" >
Lascia questi campi in bianco per non effettuare variazioni alla password
</p>
<label id="settings-password-label" for="settings-password" >Nuova Password: </label>
<input type="password" id="settings-password" name="npassword" />
</div>
<div id="settings-password-end" ></div>

<div id="settings-confirm-wrapper" >
<label id="settings-confirm-label" for="settings-confirm" >Conferma: </label>
<input type="password" id="settings-confirm" name="confirm" />
</div>
<div id="settings-confirm-end" ></div>

<div id="settings-openid-wrapper" >
	$oidhtml
</div>
<div id="settings-openid-end" ></div>


<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="Aggiorna" />
</div>


<h3 class="settings-heading">Impostazioni Avanzate Account</h3>

$pagetype

<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="Aggiorna" />
</div>


