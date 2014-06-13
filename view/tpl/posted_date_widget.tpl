<div id="datebrowse-sidebar" class="widget">
	<h3>{{$title}}</h3>
<script>function dateSubmit(dateurl) { window.location.href = dateurl; } </script>
<ul id="posted-date-selector">
{{foreach $dates as $y => $arr}}
<li id="posted-date-selector-year-{{$y}}" class="fakelink" onclick="openClose('posted-date-selector-{{$y}}');">{{$y}}</li>
<div id="posted-date-selector-{{$y}}" style="display: none;">
<ul class="posted-date-selector-months">
{{foreach $arr as $d}}
<li class="posted-date-li"><a href="#" onclick="dateSubmit('{{$url}}?f=&dend={{$d.1}}&dbegin={{$d.2}}'); return false;">{{$d.0}}</a></li>
{{/foreach}}
</ul>
</div>
{{/foreach}}
</ul>
</div>
