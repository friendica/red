<div class="widget{{if $class}} {{$class}}{{/if}}">
	{{if $title}}<h3>{{$title}}</h3>{{/if}}
	{{if $desc}}<div class="desc">{{$desc}}</div>{{/if}}
	
	<ul class="nav nav-pills nav-stacked">
		{{foreach $items as $item}}
		<li><a href="{{$item.url}}" class="{{if $item.selected}}active{{/if}}">{{$item.label}}</a></li>
		{{/foreach}}
	</ul>
	
</div>
