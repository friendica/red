<div class="channel-selection">
{{ if $channel.default_links }}
{{ if $channel.default }}
<div class="channel-selection-default default">$msg_default</div>
{{ else }}
<div class="channel-selection-default"><a href="manage/$channel.channel_id/default">$msg_make_default</a></div>
{{ endif }}
{{ endif }}
<a href="$channel.link" class="channel-selection-photo-link" title="$channel.channel_name"><img class="channel-photo" src="$channel.xchan_photo_m" alt="$channel.channel_name" /></a>
<a href="$channel.link" class="channel-selection-name-link" title="$channel.channel_name"><div class="channel-name">$channel.channel_name</div></a>
</div>
<div class="channel-selection-end"></div>
