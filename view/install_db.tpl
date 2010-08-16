
<h3>Mistpark Personal Edition</h3>
<h3>Installation</h3>

<p>
In order to install Mistpark we need to know how to contact your database. Please contact your hosting provider or site administrator if you have questions about these settings. The database you specify below must already exist. If it does not, please create it before continuing. 
</p>

<form id="install-form" action="install" method="post">

<input type="hidden" name="phpath" value="$phpath" />

<label for="install-dbhost" id="install-dbhost-label">Database Server Name</label>
<input type="text" name="dbhost" id="install-dbhost" value="$dbhost" />
<div id="install-dbhost-end"></div>

<label for="install-dbuser" id="install-dbuser-label">Database Login Name</label>
<input type="text" name="dbuser" id="install-dbuser" value="$dbuser" />
<div id="install-dbuser-end"></div>

<label for="install-dbpass" id="install-dbpass-label">Database Login Password</label>
<input type="password" name="dbpass" id="install-dbpass" value="$dbpass" />
<div id="install-dbpass-end"></div>

<label for="install-dbdata" id="install-dbdata-label">Database Name</label>
<input type="text" name="dbdata" id="install-dbdata"  value="$dbdata" />
<div id="install-dbdata-end"></div>

<div id="install-tz-desc">
Please select a default timezone for your website
</div>

$tzselect

<div id="install-tz-end" ></div>
<input id="install-submit" type="submit" name="submit" value="$submit" /> 

</form>
<div id="install-end" ></div>

