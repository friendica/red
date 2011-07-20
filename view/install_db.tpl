
<h3>$lbl_01</h3>
<h3>$lbl_02</h3>

<p>
$lbl_03 $lbl_04 $lbl_05
</p>

<form id="install-form" action="$baseurl/install" method="post">

<input type="hidden" name="phpath" value="$phpath" />

<label for="install-dbhost" id="install-dbhost-label">$lbl_06</label>
<input type="text" name="dbhost" id="install-dbhost" value="$dbhost" />
<div id="install-dbhost-end"></div>

<label for="install-dbuser" id="install-dbuser-label">$lbl_07</label>
<input type="text" name="dbuser" id="install-dbuser" value="$dbuser" />
<div id="install-dbuser-end"></div>

<label for="install-dbpass" id="install-dbpass-label">$lbl_08</label>
<input type="password" name="dbpass" id="install-dbpass" value="$dbpass" />
<div id="install-dbpass-end"></div>

<label for="install-dbdata" id="install-dbdata-label">$lbl_09</label>
<input type="text" name="dbdata" id="install-dbdata"  value="$dbdata" />
<div id="install-dbdata-end"></div>

<label for="install-admin" id="install-admin-label">$lbl_11</label>
<input type="text" name="adminmail" id="install-admin"  value="$adminmail" />
<div id="install-admin-end"></div>

<div id="install-tz-desc">
$lbl_10
</div>

$tzselect

<div id="install-tz-end" ></div>
<input id="install-submit" type="submit" name="submit" value="$submit" /> 

</form>
<div id="install-end" ></div>

