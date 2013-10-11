{{if $files}}

	   <div class="generic-content-wrapper">
		{{if $limit}}{{$limitlabel}}{{$limit}}{{/if}} {{if $used}} {{$usedlabel}}{{$used}}{{/if}}


		{{foreach $files as $key => $items}} 
				{{foreach $items as $item}}
					<div class="files-list-item">
					<a href="attach/{{$item.download}}">{{$download}}</a> |
					<a href="{{$baseurl}}/{{$item.id}}/delete">{{$delete}}
					<a href="page/{{$channel}}/{{$item.title}}">{{$title}}</a> {{$item.title}} | 
					{{$item.size}} bytes

</div>
				{{/foreach}}
		{{/foreach}}
	   </div>
	
	   <div class="clear"></div>

{{/if}}
