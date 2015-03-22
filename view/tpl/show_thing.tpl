<h2>{{$header}}</h2>
{{if $thing}}
<div class="thing-show">
{{if $thing.imgurl}}<img src="{{$thing.imgurl}}" width="175" height="175" alt="{{$thing.term}}" />{{/if}}
<a href="{{$thing.url}}" >{{$thing.term}}</a>
</div>
{{if $canedit}}
<div class="thing-edit-links">
<a href="thing/edit/{{$thing.term_hash}}" title="{{$edit}}" class="btn btn-default" ><i class="icon-pencil thing-edit-icon"></i></a>
<a href="thing/drop/{{$thing.term_hash}}" onclick="return confirmDelete();" title="{{$delete}}" class="btn btn-default" ><i class="icon-trash drop-icons"></i></a>
</div>
<div class="thing-edit-links-end"></div>
{{/if}}

{{/if}}

