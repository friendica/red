<h3>$title</h3>

<div id="poke-desc">$desc</div>

<form action="poke" method="get">
<br />
<br />

<div id="poke-recip-label">$clabel</div>
<br />
<input id="recip" type="text" size="64" maxlength="255" value="$name" name="pokename" autocomplete="off">
<input id="recip-complete" type="hidden" value="$id" name="cid">

<br />
<br />
<div id="poke-action-label">$choice</div>
<br />
<br />
<select name="verb" id="poke-verb-select" >
{{ for $verbs as $v }}
<option value="$v.0">$v.1</option>
{{ endfor }}
</select>
<br />
<br />

<input type="submit" name="submit" value="$submit" />
</form>

