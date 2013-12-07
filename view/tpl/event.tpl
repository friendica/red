{{foreach $events as $event}}
	<div class="event">
	
	{{if $event.item.author.xchan_name}}<a href="{{$event.item.author.xchan_url}}" ><img src="{{$event.item.author.xchan_photo_s}}" height="32" width="32" />{{$event.item.author.xchan_name}}</a>{{/if}}
	{{$event.html}}
	{{if $event.item.plink}}<a href="{{$event.plink.0}}" title="{{$event.plink.1}}"  class="plink-event-link"><i class="icon-external-link" ></i></a>{{/if}}
	{{if $event.edit}}<a href="{{$event.edit.0}}" title="{{$event.edit.1}}" class="edit-event-link"><i class="icon-pencil"></i></a>{{/if}}
	</div>
	<div class="clear"></div>
{{/foreach}}
