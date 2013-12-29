{{if $things}}
{{foreach $things as $key => $items}}
<b>{{$items.profile}} {{$key}}</b>
<ul class="profile-thing-list">
{{foreach $items as $item}}
<li>{{if $item.img}}<img src="{{$item.img}}" width="100" height="100" alt="{{$item.term}}" />{{/if}}
<a href="{{$item.url}}" >{{$item.term}}</a>
</li>
{{/foreach}}
</ul>
<div class="clear"></div>
{{/foreach}}
{{/if}}
