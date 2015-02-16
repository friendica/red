{{foreach $events as $event}}
	<div class="event-wrapper">
	<div class="event">
	<div class="event-owner">
	{{if $event.item.author.xchan_name}}<a href="{{$event.item.author.xchan_url}}" ><img src="{{$event.item.author.xchan_photo_s}}" height="64" width="64" />{{$event.item.author.xchan_name}}</a>{{/if}}
	</div>
	{{$event.html}}
	<div class="event-buttons">
	{{if $event.item.plink}}<a href="{{$event.plink.0}}" title="{{$event.plink.1}}"  class="plink-event-link"><i class="icon-external-link btn btn-default" ></i></a>{{/if}}
	{{if $event.edit}}<a href="{{$event.edit.0}}" title="{{$event.edit.1}}" class="edit-event-link"><i class="icon-pencil btn btn-default"></i></a>{{/if}}
	{{if $event.drop}}<a href="{{$event.drop.0}}" title="{{$event.drop.1}}" class="drop-event-link"><i class="icon-trash btn btn-default"></i></a>{{/if}}
	</div>
	</div>
	<div class="clear"></div>
	</div>
{{/foreach}}
