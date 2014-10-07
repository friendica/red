<div id="categories-sidebar" class="widget">
	<h3>{{$title}}</h3>
	<div id="categories-sidebar-desc">{{$desc}}</div>
	
	<ul class="nav nav-pills nav-stacked">
		<li><a href="{{$base}}"{{if $sel_all}} class="categories-selected"{{/if}}>{{$all}}</a></li>
		{{foreach $terms as $term}}
		<li><a href="{{$base}}?f=&cat={{$term.name}}"{{if $term.selected}} class="categories-selected"{{/if}}>{{$term.name}}</a></li>
		{{/foreach}}
	</ul>
	
</div>
