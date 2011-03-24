<h3>Det sociala n&auml;tverket Friendika</h3>
<h3>Installation</h3>

<p>
F&ouml;r att kunna installera Friendika m&aring;ste du ange hur man ansluter till din databas. Kontakta ditt webbhotell eller webbplatsadministrat&ouml;r om du har fr&aring;gor om dessa inst&auml;llningar. Databasen du specar nedan m&aring;ste finnas. Skapa databasen innan du forts&auml;tter, om det inte redan &auml;r gjort. 
</p>

<form id="install-form" action="$baseurl/install" method="post">

<input type="hidden" name="phpath" value="$phpath" />

<label for="install-dbhost" id="install-dbhost-label">Servernamn d&auml;r databasen finns</label>
<input type="text" name="dbhost" id="install-dbhost" value="$dbhost" />
<div id="install-dbhost-end"></div>

<label for="install-dbuser" id="install-dbuser-label">Inloggningsnamn f&ouml;r databasen</label>
<input type="text" name="dbuser" id="install-dbuser" value="$dbuser" />
<div id="install-dbuser-end"></div>

<label for="install-dbpass" id="install-dbpass-label">L&ouml;senord f&ouml;r databasen</label>
<input type="password" name="dbpass" id="install-dbpass" value="$dbpass" />
<div id="install-dbpass-end"></div>

<label for="install-dbdata" id="install-dbdata-label">Databasens namn</label>
<input type="text" name="dbdata" id="install-dbdata"  value="$dbdata" />
<div id="install-dbdata-end"></div>

<div id="install-tz-desc">
Ange vilken tidszon som ska vara f&ouml;rvald p&aring; din webbplats
</div>

$tzselect

<div id="install-tz-end" ></div>
<input id="install-submit" type="submit" name="submit" value="$submit" /> 

</form>
<div id="install-end" ></div>
