<div id="datebrowse-sidebar" class="widget">
	<h3>{{$title}}</h3>
<script>function dateSubmit(dateurl) { window.location.href = dateurl; } </script>
<select id="posted-date-selector" name="posted-date-select" onchange="dateSubmit($(this).val());" size="{{$size}}">
{{foreach $dates as $d}}
<option value="{{$url}}?dend={{$d.1}}&dbegin={{$d.2}}" >{{$d.0}}</option>
{{/foreach}}
</select>
</div>
