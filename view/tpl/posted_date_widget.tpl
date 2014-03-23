<div id="datebrowse-sidebar" class="widget">
	<h3>{{$title}}</h3>
<script>function dateSubmit(dateurl) { window.location.href = dateurl; } </script>
{{if $style == 'list'}}
<ul id="posted-date-selector">
{{foreach $dates as $d}}
<li class="posted-date-li"><a href="#" onclick="dateSubmit('{{$url}}?f=&dend={{$d.1}}&dbegin={{$d.2}}'); return false;">{{$d.0}}</a></li>
{{/foreach}}
</ul>
{{else}}
<select id="posted-date-selector" name="posted-date-select" onchange="dateSubmit($(this).val());" size="{{$size}}">
{{foreach $dates as $d}}
<option value="{{$url}}?f=&dend={{$d.1}}&dbegin={{$d.2}}" >{{$d.0}}</option>
{{/foreach}}
</select>
{{/if}}
</div>
