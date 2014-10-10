<div class="generic-content-wrapper-styled">

<h1>{{$notif_header}}</h1>

{{if $notifications_available}}
<a href="#" onclick="markRead('notify'); setTimeout(function() { window.location.href=window.location.href; },1500); return false;">{{$notif_link_mark_seen}}</a>
{{/if}}
<div class="notif-network-wrapper">
	{{$notif_content}}
</div>
</div>
