<script>

function toggle_posted_date_button() {
	if($('#posted-date-dropdown').is(':visible')) {
		$('#posted-date-icon').removeClass('icon-caret-up');
		$('#posted-date-icon').addClass('icon-caret-down');
		$('#posted-date-dropdown').hide();
	}
	else {
		$('#posted-date-icon').addClass('icon-caret-up');
		$('#posted-date-icon').removeClass('icon-caret-down');
		$('#posted-date-dropdown').show();
	}
}
</script>
		

<div id="datebrowse-sidebar" class="widget">
	<h3>{{$title}}</h3>
	<script>function dateSubmit(dateurl) { window.location.href = dateurl; } </script>
	<ul id="posted-date-selector" class="nav nav-pills nav-stacked">
		{{foreach $dates as $y => $arr}}
		{{if $y == $cutoff_year}}
		</ul>
		<div id="posted-date-dropdown" style="display: none;">
		<ul id="posted-date-selector-drop" class="nav nav-pills nav-stacked">
		{{/if}} 
		<li id="posted-date-selector-year-{{$y}}">
			<a href="#" onclick="openClose('posted-date-selector-{{$y}}'); return false;">{{$y}}</a>
		</li>
		<div id="posted-date-selector-{{$y}}" style="display: none;">
			<ul class="posted-date-selector-months nav nav-pills nav-stacked">
				{{foreach $arr as $d}}
				<li>
					<a href="#" onclick="dateSubmit('{{$url}}?f=&dend={{$d.1}}{{if $showend}}&dbegin={{$d.2}}{{/if}}'); return false;">{{$d.0}}</a>
				</li>
				{{/foreach}}
			</ul>
		</div>
		{{/foreach}}
		{{if $cutoff}}
		</div>
		<button class="btn btn-default btn-sm" onclick="toggle_posted_date_button(); return false;"><i id="posted-date-icon" class="icon-caret-down"></i></button>
		{{/if}}
	</ul>
</div>
