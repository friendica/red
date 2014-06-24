{{if $files}}

	   <div class="generic-content-wrapper">
		{{if $limit}}{{$limitlabel}}{{$limit}}{{/if}} {{if $used}} {{$usedlabel}}{{$used}}{{/if}}
		<br />
		<br />

		{{foreach $files as $key => $items}} 
				{{foreach $items as $item}}
					<div class="files-list-item">
					<a href="{{$baseurl}}/{{$item.id}}/edit" title="{{$edit}}"><i class="icon-pencil"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;
					<a href="{{$baseurl}}/{{$item.id}}/delete" title="{{$delete}}"><i class="icon-remove drop-icons"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;
					{{if ! $item.dir}}<a href="attach/{{$item.download}}">{{/if}}{{$item.title}}{{if ! $item.dir}}</a>{{/if}}
					{{if ! $item.dir}} | {{$item.size}} bytes{{else}}{{$directory}}{{/if}}

</div>
				{{/foreach}}
		{{/foreach}}
	   </div>
	
	   <div class="clear"></div>

{{/if}}
