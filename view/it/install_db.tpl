
<h3>Friendika Social Network</h3>
<h3>Installazione</h3>

<p>
Per poter installare Friendika dobbiamo conoscrere come collegarci al tuo database. Contatta il tuo hosting provider o l'amministratore del sito se hai domande su questi settaggi. Il database specificato qui di seguito deve essere già presente. Se non esiste, crealo prima di continuare.
</p>

<form id="install-form" action="$baseurl/install" method="post">

<input type="hidden" name="phpath" value="$phpath" />

<label for="install-dbhost" id="install-dbhost-label">Nome Server Database</label>
<input type="text" name="dbhost" id="install-dbhost" value="$dbhost" />
<div id="install-dbhost-end"></div>

<label for="install-dbuser" id="install-dbuser-label">Nome Login Database</label>
<input type="text" name="dbuser" id="install-dbuser" value="$dbuser" />
<div id="install-dbuser-end"></div>

<label for="install-dbpass" id="install-dbpass-label">Password Login Database</label>
<input type="password" name="dbpass" id="install-dbpass" value="$dbpass" />
<div id="install-dbpass-end"></div>

<label for="install-dbdata" id="install-dbdata-label">Nome Database</label>
<input type="text" name="dbdata" id="install-dbdata"  value="$dbdata" />
<div id="install-dbdata-end"></div>

<div id="install-tz-desc">
Seleziona il fuso orario del tuo sito web
</div>

$tzselect

<div id="install-tz-end" ></div>
<input id="install-submit" type="submit" name="submit" value="$submit" /> 

</form>
<div id="install-end" ></div>

