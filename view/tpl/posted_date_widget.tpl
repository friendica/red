<div id="datebrowse-sidebar" class="widget">
	<h3>{{$title}}</h3>
	<script>function dateSubmit(dateurl) { window.location.href = dateurl; } </script>
	<ul id="posted-date-selector" class="nav nav-pills nav-stacked">
		{{foreach $dates as $y => $arr}}
		<li id="posted-date-selector-year-{{$y}}">
			<a href="#" onclick="openClose('posted-date-selector-{{$y}}'); return false;">{{$y}}</a>
		</li>
		<div id="posted-date-selector-{{$y}}" style="display: none;">
			<ul class="posted-date-selector-months nav nav-pills nav-stacked">
				{{foreach $arr as $d}}
				<li>
					<a href="#" onclick="dateSubmit('{{$url}}?f=&dend={{$d.1}}&dbegin={{$d.2}}'); return false;">{{$d.0}}</a>
				</li>
				{{/foreach}}
			</ul>
		</div>
		{{/foreach}}
	</ul>
</div>
