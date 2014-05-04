<h1>{{$notif_header}}</h1>
{{if $notifications_available}}
<a href="#" onclick="markRead('notify');">{{$notif_link_mark_seen}}</a>
{{/if}}
<div class="notif-network-wrapper">
	{{$notif_content}}
</div>
