
<h3>Friendika Social Network</h3>
<h3>Installation</h3>

<p>
Um Friendika installieren zu können müssen wir wissen wie wir die Datenbank erreichen könne. Bitte kontaktire deinen Hosting Anbieter oder Seitenadministrator wenn du Fragen zu diesen Einstellungen hast. Die Datenbank die du weiter unten angibst muss bereits existieren. Sollte dies nicht der Fall sein erzeuge sie bitte bevor du mit der Installation fortfährst.
</p>

<form id="install-form" action="$baseurl/install" method="post">

<input type="hidden" name="phpath" value="$phpath" />

<label for="install-dbhost" id="install-dbhost-label">Datenbank Servername</label>
<input type="text" name="dbhost" id="install-dbhost" value="$dbhost" />
<div id="install-dbhost-end"></div>

<label for="install-dbuser" id="install-dbuser-label">Datenbank Anmeldename</label>
<input type="text" name="dbuser" id="install-dbuser" value="$dbuser" />
<div id="install-dbuser-end"></div>

<label for="install-dbpass" id="install-dbpass-label">Datenbank Anmeldepassword</label>
<input type="password" name="dbpass" id="install-dbpass" value="$dbpass" />
<div id="install-dbpass-end"></div>

<label for="install-dbdata" id="install-dbdata-label">Datenbankname</label>
<input type="text" name="dbdata" id="install-dbdata"  value="$dbdata" />
<div id="install-dbdata-end"></div>

<div id="install-tz-desc">
Bitte wähle die Standard-Zeitzone deiner Webseite
</div>

$tzselect

<div id="install-tz-end" ></div>
<input id="install-submit" type="submit" name="submit" value="$submit" /> 

</form>
<div id="install-end" ></div>

