<div class="channel-selection">
{{if $channel.default_links}}
{{if $channel.default}}
<div class="channel-selection-default default"><i class="icon-check"></i> {{$msg_default}}</div>
{{else}}
<div class="channel-selection-default"><a href="manage/{{$channel.channel_id}}/default"><i class="icon-check-empty" title="{{$msg_make_default}}"></i></a></div>
{{/if}}
{{/if}}
<a href="{{$channel.link}}" class="channel-selection-photo-link" title="{{$channel.channel_name}}"><img class="channel-photo" src="{{$channel.xchan_photo_m}}" alt="{{$channel.channel_name}}" /></a>
<a href="{{$channel.link}}" class="channel-selection-name-link" title="{{$channel.channel_name}}"><div class="channel-name">{{$channel.channel_name}}</div></a>
{{if $channel.mail != 0}}<span style="color:red;"><i class="icon-envelope"></i> {{$channel.mail}}</span>{{else}}<i class="icon-envelope"></i> &nbsp;{{/if}} {{if $channel.intros != 0}}<span style="color:red;"><i class="icon-user"></i> {{$channel.intros}}</span>{{else}}<i class="icon-user"></i> &nbsp;{{/if}}
</div>

<div class="channel-selection-end"></div>
