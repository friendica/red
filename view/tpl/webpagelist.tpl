{{if $pages}}

	   <div id="pagelist-content-wrapper">
		{{foreach $pages as $key => $items}} 
				{{foreach $items as $item}}
					<div class="page-list-item"><a href="editwebpage/{{$item.url}}">{{$editlink}}</a> | <a href="page/{{$channel}}/{{$item.title}}">{{$view}}</a> {{$item.title}}</div>
				{{/foreach}}
		{{/foreach}}
	   </div>
	
	   <div class="clear"></div>

{{/if}}
