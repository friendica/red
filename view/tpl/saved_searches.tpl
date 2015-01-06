<div class="widget saved-search-widget">
	<h3 id="search">{{$title}}</h3>
	{{$searchbox}}
	
	<ul id="saved-search-list" class="nav nav-pills nav-stacked">
		{{foreach $saved as $search}}
		<li id="search-term-{{$search.id}}">
			<a class="pull-right group-edit-icon" title="{{$search.delete}}" onclick="return confirmDelete();" id="drop-saved-search-term-{{$search.id}}" href="{{$search.dellink}}"><i id="dropicon-saved-search-term-{{$search.id}}" class="icon-remove" ></i></a>
			<a id="saved-search-term-{{$search.id}}"{{if $search.selected}} class="search-selected"{{/if}} href="{{$search.srchlink}}">{{$search.displayterm}}</a>
		</li>
		{{/foreach}}
	</ul>
	<div class="clear"></div>
</div>
