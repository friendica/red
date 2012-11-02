<div class="channel-selection">
{{ if $channel.primary_links }}
{{ if $channel.channel_primary }}
<div class="channel-selection-primary primary">$msg_primary</div>
{{ else }}
<div class="channel-selection-primary"><a href="manage/$channel.channel_id/primary">$msg_make_primary</a></div>
{{ endif }}
{{ endif }}
<a href="$channel.link" class="channel-selection-photo-link" title="$channel.channel_name"><img class="channel-photo" src="$channel.xchan_photo_m" alt="$channel.channel_name" /></a>
<a href="$channel.link" class="channel-selection-name-link" title="$channel.channel_name"><div class="channel-name">$channel.channel_name</div></a>
</div>
<div class="channel-selection-end"></div>
