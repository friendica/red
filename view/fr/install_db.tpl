
<h3>Réseau Social Friendika</h3>
<h3>Installation</h3>

<p>
Pour pouvoir installer Friendika, nous avons besoin de contacter votre base de données. Merci de contacter votre hébergeur et/ou administrateur si vous avez des questions à ce sujet. La base que vous spécifierez ci-dessous doit exister. Si ce n'est pas le cas, merci de la créer avant toute chose.
</p>

<form id="install-form" action="$baseurl/install" method="post">

<input type="hidden" name="phpath" value="$phpath" />

<label for="install-dbhost" id="install-dbhost-label">Nom du serveur</label>
<input type="text" name="dbhost" id="install-dbhost" value="$dbhost" />
<div id="install-dbhost-end"></div>

<label for="install-dbuser" id="install-dbuser-label">Nom d'utilisateur</label>
<input type="text" name="dbuser" id="install-dbuser" value="$dbuser" />
<div id="install-dbuser-end"></div>

<label for="install-dbpass" id="install-dbpass-label">Mot de passe</label>
<input type="password" name="dbpass" id="install-dbpass" value="$dbpass" />
<div id="install-dbpass-end"></div>

<label for="install-dbdata" id="install-dbdata-label">Nom de la base</label>
<input type="text" name="dbdata" id="install-dbdata"  value="$dbdata" />
<div id="install-dbdata-end"></div>

<div id="install-tz-desc">
Merci de choisir un fuseau horaire par défaut
</div>

$tzselect

<div id="install-tz-end" ></div>
<input id="install-submit" type="submit" name="submit" value="$submit" /> 

</form>
<div id="install-end" ></div>

