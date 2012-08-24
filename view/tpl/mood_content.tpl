<h3>$title</h3>

<div id="mood-desc">$desc</div>

<form action="mood" method="get">
<br />
<br />

<input id="mood-parent" type="hidden" value="$parent" name="parent" />

<select name="verb" id="mood-verb-select" >
{{ for $verbs as $v }}
<option value="$v.0">$v.1</option>
{{ endfor }}
</select>
<br />
<br />
<input type="submit" name="submit" value="$submit" />
</form>

