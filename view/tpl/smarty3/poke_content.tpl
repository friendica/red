<h3>{{$title}}</h3>

<div id="poke-desc">{{$desc}}</div>

<form action="poke" method="get">
<br />
<br />

<div id="poke-recip-label">{{$clabel}}</div>
<br />
<input id="poke-recip" type="text" size="64" maxlength="255" value="{{$name}}" name="pokename" autocomplete="off" />
<input id="poke-recip-complete" type="hidden" value="{{$id}}" name="cid" />
<input id="poke-parent" type="hidden" value="{{$parent}}" name="parent" />
<br />
<br />
<div id="poke-action-label">{{$choice}}</div>
<br />
<br />
<select name="verb" id="poke-verb-select" >
{{foreach $verbs as $v}}
<option value="{{$v.0}}">{{$v.1}}</option>
{{/foreach}}
</select>
<br />
<br />
<div id="poke-private-desc">{{$prv_desc}}</div>
<input type="checkbox" name="private" {{if $parent}}disabled="disabled"{{/if}} value="1" />
<br />
<br />
<input type="submit" name="submit" value="{{$submit}}" />
</form>

