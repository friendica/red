{{if $files}}

	   <div class="generic-content-wrapper">
		{{if $limit}}{{$limitlabel}}{{$limit}}{{/if}} {{if $used}} {{$usedlabel}}{{$used}}{{/if}}


		{{foreach $files as $key => $items}} 
				{{foreach $items as $item}}
					<div class="files-list-item">
					<a href="{{$baseurl}}/{{$item.id}}/edit">{{$edit}}</a> |
					<a href="{{$baseurl}}/{{$item.id}}/delete">{{$delete}}</a> |
					[attachment]{{$item.download}},{{$item.rev}}[/attachment] |
					<a href="attach/{{$item.download}}">{{$item.title}}</a> | 
					{{$item.size}} bytes

</div>
				{{/foreach}}
		{{/foreach}}
	   </div>
	
	   <div class="clear"></div>

{{/if}}
